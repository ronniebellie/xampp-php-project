<?php
/** Advisor-only lifecycle. Verified events are notifications, never payment authority. */
declare(strict_types=1);
require_once __DIR__ . '/calcforadvisors_entitlement.php';

interface CfaSubscriptionStore
{
    public function lock(): void;
    public function unlock(): void;
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
    public function seen(string $event): bool;
    public function mark(string $event, string $type): void;
    public function subscriber(int $id): ?array;
    public function bySubscription(string $id): ?array;
    public function byCustomer(string $id): ?array;
    public function checkout(string $id): ?array;
    public function save(array $row): void;
}

function cfa_stripe_id($value, string $prefix): string
{
    if (is_array($value)) $value = $value['id'] ?? null;
    if (!is_string($value) || !preg_match('/^' . preg_quote($prefix, '/') . '_[A-Za-z0-9]+$/D', $value)) {
        throw new RuntimeException('Invalid Stripe identity');
    }
    return $value;
}

function cfa_stripe_time($value): ?int
{
    return is_int($value) && $value > 0 ? $value : null;
}

function cfa_subscription_plan(array $sub, array $prices): ?string
{
    $items = $sub['items']['data'] ?? [];
    if (count($items) !== 1 || !empty($sub['items']['has_more'])) return null;
    $price = $items[0]['price'] ?? null;
    $price = is_array($price) ? ($price['id'] ?? '') : $price;
    return is_string($price) && isset($prices[$price]) && $price !== '' ? $prices[$price] : null;
}

/** Pure reduction of a freshly retrieved subscription, not an invoice snapshot. */
function cfa_subscription_state(array $row, array $sub, string $plan, int $created, int $now): array
{
    $status = $sub['status'] ?? '';
    if (!in_array($status, ['active', 'trialing', 'past_due', 'unpaid', 'paused', 'incomplete', 'incomplete_expired', 'canceled'], true)) {
        throw new RuntimeException('Unknown subscription status');
    }
    $period = cfa_stripe_time($sub['current_period_end'] ?? $sub['items']['data'][0]['current_period_end'] ?? null);
    $trial = cfa_stripe_time($sub['trial_end'] ?? null);
    $cancel = cfa_stripe_time($sub['cancel_at'] ?? null);
    $scheduled = ($sub['cancel_at_period_end'] ?? false) === true || $cancel !== null;
    if (in_array($status, ['active', 'trialing'], true) && $period === null) throw new RuntimeException('Missing subscription period');
    if ($status === 'trialing' && $trial === null) throw new RuntimeException('Missing trial endpoint');
    $end = $period;
    if ($cancel !== null) $end = $end === null ? $cancel : min($end, $cancel);
    if ($status === 'canceled') $end = cfa_stripe_time($sub['ended_at'] ?? null) ?? $now;
    $pastDue = $status === 'past_due'
        ? (($row['stripe_subscription_status'] ?? '') === 'past_due' ? ($row['past_due_started_at'] ?? null) : gmdate('Y-m-d H:i:s', min($created, $now)))
        : null;
    return array_replace($row, [
        'stripe_customer_id' => cfa_stripe_id($sub['customer'] ?? null, 'cus'),
        'stripe_subscription_id' => cfa_stripe_id($sub['id'] ?? null, 'sub'),
        'stripe_subscription_created' => cfa_stripe_time($sub['created'] ?? null),
        'stripe_subscription_status' => $status,
        'status' => $status === 'trialing' ? 'active' : $status,
        'plan' => $plan,
        'cancel_at_period_end' => $scheduled ? 1 : 0,
        'trial_ends_at' => $trial ? gmdate('Y-m-d H:i:s', $trial) : null,
        'access_ends_at' => $end ? gmdate('Y-m-d H:i:s', $end) : null,
        'past_due_started_at' => $pastDue,
        'trial_used_at' => $row['trial_used_at'] ?? ($trial ? gmdate('Y-m-d H:i:s', $now) : null),
        'last_stripe_event_created' => $created,
    ]);
}

/** Callbacks retrieve current Stripe state inside the serialization lock.
 * No fallback to event snapshots on API failure. All DB effects and event receipt
 * commit together; a failed attempt is retryable. No emails/provisioning side effects.
 */
function cfa_process_subscription_event(CfaSubscriptionStore $store, array $event, callable $retrieve, array $prices, ?int $now = null): string
{
    $types = ['checkout.session.completed', 'customer.subscription.created', 'customer.subscription.updated',
        'customer.subscription.deleted', 'customer.subscription.paused', 'customer.subscription.resumed',
        'invoice.payment_failed', 'invoice.paid', 'invoice.payment_succeeded'];
    $type = $event['type'] ?? '';
    if (!in_array($type, $types, true)) return 'ignored';
    $eventId = cfa_stripe_id($event['id'] ?? null, 'evt');
    $created = cfa_stripe_time($event['created'] ?? null);
    if ($created === null) throw new RuntimeException('Missing event timestamp');
    $object = $event['data']['object'] ?? [];
    $now = $now ?? time();
    $store->lock();
    try {
        $store->begin();
        if ($store->seen($eventId)) { $store->commit(); return 'duplicate'; }
        $finish = static function (string $result) use ($store, $eventId, $type): string {
            $store->mark($eventId, $type); $store->commit(); return $result;
        };
        if ($type === 'checkout.session.completed' && ($object['mode'] ?? '') !== 'subscription') return $finish('ignored');
        $subValue = str_starts_with($type, 'customer.subscription.') ? ($object['id'] ?? null)
            : ($object['subscription'] ?? $object['parent']['subscription_details']['subscription'] ?? null);
        if ($subValue === null && str_starts_with($type, 'invoice.')) return $finish('ignored');
        $subId = cfa_stripe_id($subValue, 'sub');
        $sub = $retrieve($subId);
        if (!is_array($sub) || ($sub['id'] ?? '') !== $subId) throw new RuntimeException('Subscription retrieval failed');
        $plan = cfa_subscription_plan($sub, $prices);
        $row = $store->bySubscription($subId);
        // An existing advisor subscription changing to an unknown product must
        // not keep its old entitlement. Require operator reconciliation.
        if ($plan === null) {
            if ($row !== null) {
                $row['stripe_subscription_status'] = 'unresolved'; $row['status'] = 'inactive';
                $store->save($row);
            }
            return $finish('ignored_product');
        }
        $customer = cfa_stripe_id($sub['customer'] ?? null, 'cus');
        $owner = $store->byCustomer($customer); // ambiguous identities throw, never LIMIT 1
        $binding = $type === 'checkout.session.completed' ? $store->checkout(cfa_stripe_id($object['id'] ?? null, 'cs')) : null;
        if ($binding !== null) {
            if ($binding['stripe_customer_id'] !== $customer || cfa_stripe_id($object['customer'] ?? null, 'cus') !== $customer) throw new RuntimeException('Checkout customer mismatch');
            $bound = $store->subscriber((int) $binding['subscriber_id']);
            if ($bound === null || ($owner !== null && $owner['id'] !== $bound['id']) || ($row !== null && $row['id'] !== $bound['id'])) throw new RuntimeException('Checkout owner mismatch');
            $row = $bound;
        }
        if ($row === null) {
            if ($owner !== null && !empty($owner['stripe_subscription_id'])) return $finish('retired_or_unbound_subscription');
            // A subscription event preceding checkout must be retried, not
            // provisioned via email or a user-controlled metadata identifier.
            throw new RuntimeException('Subscription requires bound checkout or existing identity');
        }
        if (($row['stripe_customer_id'] ?? '') !== $customer) throw new RuntimeException('Subscription customer mismatch');
        $oldId = $row['stripe_subscription_id'] ?? '';
        if ($oldId !== '' && $oldId !== null && $oldId !== $subId) {
            if ($binding === null) return $finish('retired_subscription');
            $old = $retrieve($oldId);
            $newCreated = cfa_stripe_time($sub['created'] ?? null);
            $oldCreated = cfa_stripe_time($old['created'] ?? null);
            if (!is_array($old) || ($old['id'] ?? '') !== $oldId || !in_array($old['status'] ?? '', ['canceled', 'incomplete_expired'], true)
                || $newCreated === null || $oldCreated === null || $newCreated <= $oldCreated) throw new RuntimeException('Unsafe subscription replacement');
            $row['last_stripe_event_created'] = null;
            $row['past_due_started_at'] = null;
        } elseif ($created < (int) ($row['last_stripe_event_created'] ?? 0)) {
            return $finish('stale');
        } elseif (($row['stripe_subscription_status'] ?? '') === 'canceled' && ($sub['status'] ?? '') !== 'canceled') {
            return $finish('terminal');
        }
        $store->save(cfa_subscription_state($row, $sub, $plan, $created, $now));
        return $finish('processed');
    } catch (Throwable $e) {
        $store->rollback(); throw $e;
    } finally { $store->unlock(); }
}

/** Parameterized SQL only; one short global advisor lock also covers Checkout. */
final class CfaSqlSubscriptionStore implements CfaSubscriptionStore
{
    private mysqli $db;
    public function __construct(mysqli $db) { $this->db = $db; }
    public function query(string $sql, array $args = []): array {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Prepare failed');
        try {
            if ($args) $stmt->bind_param(str_repeat('s', count($args)), ...$args);
            if (!$stmt->execute()) throw new RuntimeException('Write failed');
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } finally { $stmt->close(); }
    }
    public function lock(): void { if ((int) ($this->query("SELECT GET_LOCK('cfa_subscription_sync', 10) AS acquired")[0]['acquired'] ?? 0) !== 1) throw new RuntimeException('Lifecycle busy'); }
    public function unlock(): void { $this->query("SELECT RELEASE_LOCK('cfa_subscription_sync')"); }
    public function begin(): void { if (!$this->db->begin_transaction()) throw new RuntimeException('Transaction failed'); }
    public function commit(): void { if (!$this->db->commit()) throw new RuntimeException('Commit failed'); }
    public function rollback(): void { $this->db->rollback(); }
    public function seen(string $event): bool { return (bool) $this->query('SELECT event_id FROM cfa_webhook_events WHERE event_id=?', [$event]); }
    public function mark(string $event, string $type): void { $this->query('INSERT INTO cfa_webhook_events (event_id,event_type) VALUES (?,?)', [$event,$type]); }
    public function subscriber(int $id): ?array { return $this->query('SELECT * FROM calcforadvisors_subscribers WHERE id=? FOR UPDATE', [$id])[0] ?? null; }
    private function unique(string $field, string $id): ?array {
        $rows = $this->query('SELECT * FROM calcforadvisors_subscribers WHERE ' . $field . '=? FOR UPDATE', [$id]);
        if (count($rows) > 1) throw new RuntimeException('Ambiguous Stripe identity');
        return $rows[0] ?? null;
    }
    public function bySubscription(string $id): ?array { return $this->unique('stripe_subscription_id', $id); }
    public function byCustomer(string $id): ?array { return $this->unique('stripe_customer_id', $id); }
    public function checkout(string $id): ?array { return $this->query('SELECT * FROM cfa_checkout_sessions WHERE stripe_session_id=?', [$id])[0] ?? null; }
    public function save(array $row): void {
        $fields = ['stripe_customer_id','stripe_subscription_id','stripe_subscription_created','stripe_subscription_status','status','plan','cancel_at_period_end','trial_ends_at','access_ends_at','past_due_started_at','trial_used_at','last_stripe_event_created'];
        $args = array_map(static fn($key) => $row[$key] ?? null, $fields); $args[] = $row['id'];
        $this->query('UPDATE calcforadvisors_subscribers SET ' . implode(',', array_map(static fn($key) => $key . '=?', $fields)) . ' WHERE id=?', $args);
    }
}
