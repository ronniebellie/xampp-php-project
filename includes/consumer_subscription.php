<?php
/** Consumer-only storage; reuse the tested shared Stripe reducer/event processor. */
declare(strict_types=1);
require_once __DIR__ . '/cfa_subscription_sync.php';

function rb_consumer_prices(): array {
    $prices=[];
    foreach (['STRIPE_PRICE_MONTHLY'=>'monthly','STRIPE_PRICE_ANNUAL'=>'annual'] as $key=>$plan) {
        if(defined($key) && preg_match('/^price_[A-Za-z0-9]+$/D',(string)constant($key)) && constant($key)!=='price_xxx') $prices[constant($key)]=$plan;
    }
    if(count($prices)!==2)throw new RuntimeException('Consumer prices unavailable');
    return $prices;
}

function rb_consumer_evaluate(array $user, ?array $row, ?DateTimeImmutable $now=null): array {
    $now=$now??new DateTimeImmutable('now',new DateTimeZone('UTC'));
    $inactive=['has_premium'=>false,'state'=>'inactive','in_grace'=>false,'access_until'=>null];
    if(!$user)return $inactive;
    if($row!==null && !empty($row['stripe_subscription_id'])) {
        if(empty($row['last_stripe_event_created']))return $inactive;
        return cfa_evaluate_advisor_entitlement($row,$now);
    }
    // Preserve explicit manual grants only; a legacy linked Stripe flag cannot grant access.
    $end=cfa_parse_utc_datetime($user['subscription_end_date']??null);
    if(($user['subscription_status']??'')==='premium' && empty($user['stripe_subscription_id'])
        && (empty($user['subscription_end_date']) || ($end!==null && $now<$end))) return array_replace($inactive,['has_premium'=>true,'state'=>'manual','access_until'=>cfa_datetime_string($end)]);
    return $inactive;
}

function rb_consumer_status(mysqli $db,int $userId): array {
    $store=new RbConsumerSubscriptionStore($db);
    $user=$store->query('SELECT id,subscription_status,subscription_end_date,stripe_subscription_id FROM users WHERE id=?',[$userId])[0]??[];
    $row=$store->query('SELECT * FROM consumer_subscriptions WHERE id=?',[$userId])[0]??null;
    return rb_consumer_evaluate($user,$row)+['billing_customer'=>$row ? cfa_billing_customer($row,$userId):null];
}
function rb_consumer_has_premium(mysqli $db,int $userId):bool {
    try{return rb_consumer_status($db,$userId)['has_premium']===true;}
    catch(Throwable $e){error_log('Consumer entitlement unavailable');return false;}
}

final class RbConsumerSubscriptionStore implements CfaSubscriptionStore
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
    public function lock(): void { if ((int) ($this->query("SELECT GET_LOCK('rb_consumer_subscription_sync', 10) AS acquired")[0]['acquired'] ?? 0) !== 1) throw new RuntimeException('Lifecycle busy'); }
    public function unlock(): void { $this->query("SELECT RELEASE_LOCK('rb_consumer_subscription_sync')"); }
    public function begin(): void { if (!$this->db->begin_transaction()) throw new RuntimeException('Transaction failed'); }
    public function commit(): void { if (!$this->db->commit()) throw new RuntimeException('Commit failed'); }
    public function rollback(): void { $this->db->rollback(); }
    public function seen(string $event): bool { return (bool) $this->query('SELECT event_id FROM consumer_webhook_events WHERE event_id=?', [$event]); }
    public function mark(string $event, string $type): void { $this->query('INSERT INTO consumer_webhook_events (event_id,event_type) VALUES (?,?)', [$event,$type]); }
    public function subscriber(int $id): ?array { return $this->query('SELECT * FROM consumer_subscriptions WHERE id=? FOR UPDATE', [$id])[0] ?? null; }
    private function unique(string $field, string $id): ?array {
        $rows = $this->query('SELECT * FROM consumer_subscriptions WHERE ' . $field . '=? FOR UPDATE', [$id]);
        if (count($rows) > 1) throw new RuntimeException('Ambiguous Stripe identity');
        return $rows[0] ?? null;
    }
    public function bySubscription(string $id): ?array { return $this->unique('stripe_subscription_id', $id); }
    public function byCustomer(string $id): ?array { return $this->unique('stripe_customer_id', $id); }
    public function checkout(string $id): ?array { return $this->query('SELECT * FROM consumer_checkout_sessions WHERE stripe_session_id=?', [$id])[0] ?? null; }
    public function save(array $row): void {
        $fields = ['stripe_customer_id','stripe_subscription_id','stripe_subscription_created','stripe_subscription_status','status','plan','cancel_at_period_end','trial_ends_at','access_ends_at','past_due_started_at','trial_used_at','last_stripe_event_created'];
        $args = array_map(static fn($key) => $row[$key] ?? null, $fields); $args[] = $row['id'];
        $this->query('UPDATE consumer_subscriptions SET ' . implode(',', array_map(static fn($key) => $key . '=?', $fields)) . ' WHERE id=?', $args);
    }
}
