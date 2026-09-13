<?php
declare(strict_types=1);
require_once __DIR__ . '/cfa_subscription_sync.php';

/** One introductory window per account; switching to card never restarts it. */
function cfa_checkout_trial_end(array $row, bool $previousCheckout, int $now): ?int
{
    if ($previousCheckout || !empty($row['stripe_subscription_id']) || ($row['plan'] ?? '') !== 'free') return null;
    $created = cfa_parse_utc_datetime($row['created_at'] ?? null);
    $end = $created ? $created->getTimestamp() + CFA_LEGACY_TRIAL_DAYS * 86400 : null;
    return $end !== null && $end >= $now + 172800 ? $end : null;
}

/** All callbacks are injectable; no request-supplied customer/account/trial data. */
function cfa_start_checkout(CfaSqlSubscriptionStore $store, int $accountId, string $plan, string $price, string $base, array $api): string
{
    $store->lock();
    try {
        $row = $store->subscriber($accountId);
        if (!$row) throw new RuntimeException('Account missing');
        if (!empty($row['stripe_subscription_id'])) {
            $current = $api['subscription']($row['stripe_subscription_id']);
            if (($current['id'] ?? '') !== $row['stripe_subscription_id'] || ($current['customer'] ?? '') !== $row['stripe_customer_id']
                || !in_array($current['status'] ?? '', ['canceled', 'incomplete_expired'], true)) {
                throw new RuntimeException('Manage your existing subscription through billing before starting another.');
            }
        }
        $customer = $row['stripe_customer_id'] ?? '';
        if (!$customer) {
            $customer = cfa_stripe_id($api['customer'](['email' => $row['email'], 'metadata' => ['cfa_subscriber_id' => (string) $accountId]], 'cfa-customer-' . $accountId)['id'] ?? null, 'cus');
            $store->query('UPDATE calcforadvisors_subscribers SET stripe_customer_id=? WHERE id=?', [$customer,$accountId]);
        }
        $owner = $store->byCustomer($customer);
        if (!$owner || (int) $owner['id'] !== $accountId) throw new RuntimeException('Customer identity mismatch');
        $previous = $store->query('SELECT * FROM cfa_checkout_sessions WHERE subscriber_id=? ORDER BY created_at DESC, request_id DESC LIMIT 1', [$accountId]);
        $pending = $previous[0] ?? null;
        if ($pending && !empty($pending['stripe_session_id'])) {
            $session = $api['session']($pending['stripe_session_id']);
            if (($session['customer'] ?? '') !== $customer) throw new RuntimeException('Session identity mismatch');
            if (($session['status'] ?? '') === 'open') return (string) $session['url'];
            if (($session['status'] ?? '') === 'complete') {
                $sessionSub = cfa_stripe_id($session['subscription'] ?? null, 'sub');
                $completed = $api['subscription']($sessionSub);
                if (!in_array($completed['status'] ?? '', ['canceled','incomplete_expired'], true)) return $base . '/account.php?msg=sync_pending';
            } elseif (($session['status'] ?? '') !== 'expired') throw new RuntimeException('Unknown checkout state');
            $pending = null;
        }
        if ($pending === null) {
            $pending = ['request_id' => bin2hex(random_bytes(24)), 'plan' => $plan, 'trial_end' => cfa_checkout_trial_end($row, (bool) $previous, time())];
            $store->query('INSERT INTO cfa_checkout_sessions (request_id,subscriber_id,stripe_customer_id,plan,trial_end) VALUES (?,?,?,?,?)',
                [$pending['request_id'],$accountId,$customer,$plan,$pending['trial_end']]);
        } else {
            // Never reuse an uncertain idempotency key after Stripe's retention window.
            if (strtotime($pending['created_at'] . ' UTC') < time() - 82800 || $pending['plan'] !== $plan) throw new RuntimeException('Pending checkout requires reconciliation');
        }
        $data = ['metadata' => ['cfa_subscriber_id' => (string) $accountId]];
        if ($pending['trial_end'] !== null) $data['trial_end'] = (int) $pending['trial_end'];
        $session = $api['create']([
            'mode' => 'subscription', 'customer' => $customer, 'client_reference_id' => (string) $accountId,
            'payment_method_types' => ['card'], 'line_items' => [['price' => $price,'quantity' => 1]],
            'subscription_data' => $data, 'metadata' => ['plan' => $plan],
            'success_url' => $base . '/success.php?session_id={CHECKOUT_SESSION_ID}', 'cancel_url' => $base . '/account.php',
        ], 'cfa-checkout-' . $pending['request_id']);
        $id = cfa_stripe_id($session['id'] ?? null, 'cs');
        if (($session['customer'] ?? '') !== $customer || empty($session['url'])) throw new RuntimeException('Invalid checkout response');
        $store->query('UPDATE cfa_checkout_sessions SET stripe_session_id=? WHERE request_id=?', [$id,$pending['request_id']]);
        $store->query('UPDATE calcforadvisors_subscribers SET trial_used_at=COALESCE(trial_used_at,UTC_TIMESTAMP()) WHERE id=?', [$accountId]);
        return $session['url'];
    } finally { $store->unlock(); }
}
