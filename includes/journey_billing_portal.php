<?php
/**
 * Journey Premium Stripe Customer Portal helpers.
 *
 * Journey subscriptions are owned exclusively through
 * user_product_subscriptions; legacy users subscription fields are never read.
 */

declare(strict_types=1);

if (defined('RB_JOURNEY_BILLING_PORTAL_LOADED')) {
    return;
}
define('RB_JOURNEY_BILLING_PORTAL_LOADED', 1);

require_once __DIR__ . '/journey_entitlement.php';

/**
 * Return the logged-in user's current manageable Journey subscription.
 *
 * @return array{stripe_subscription_id:string,stripe_customer_id:string}|null
 */
function journey_manageable_subscription(mysqli $conn, int $userId, ?int $nowTs = null): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT stripe_subscription_id, stripe_customer_id, stripe_status,
                entitlement_status, current_period_end
         FROM user_product_subscriptions
         WHERE user_id = ? AND product_key = ?
         ORDER BY updated_at DESC, id DESC"
    );
    if (!$stmt) {
        return null;
    }
    $product = JOURNEY_PRODUCT_KEY;
    $stmt->bind_param('is', $userId, $product);
    $stmt->execute();
    $res = $stmt->get_result();
    $now = $nowTs ?? time();

    while ($res && ($row = $res->fetch_assoc())) {
        if (!journey_subscription_row_is_manageable($row, $now)) {
            continue;
        }

        $stmt->close();
        return [
            'stripe_subscription_id' => trim((string) $row['stripe_subscription_id']),
            'stripe_customer_id' => trim((string) $row['stripe_customer_id']),
        ];
    }

    $stmt->close();
    return null;
}

/** @param array<string,mixed> $row */
function journey_subscription_row_is_manageable(array $row, ?int $nowTs = null): bool
{
    $subscriptionId = trim((string) ($row['stripe_subscription_id'] ?? ''));
    $customerId = trim((string) ($row['stripe_customer_id'] ?? ''));
    $stripeStatus = strtolower(trim((string) ($row['stripe_status'] ?? '')));
    $entitlement = strtolower(trim((string) ($row['entitlement_status'] ?? '')));
    $periodEnd = journey_parse_time_value($row['current_period_end'] ?? null);
    $now = $nowTs ?? time();

    if (strpos($subscriptionId, 'sub_') !== 0 || strpos($customerId, 'cus_') !== 0) {
        return false;
    }
    if (!in_array($stripeStatus, ['trialing', 'active', 'past_due', 'unpaid', 'paused'], true)) {
        return false;
    }
    return $entitlement !== 'canceled_grace' || ($periodEnd !== null && $periodEnd > $now);
}

/**
 * Verify that Stripe returned the exact Journey subscription stored locally.
 *
 * @param object|array $subscription
 */
function journey_portal_subscription_matches(array $local, $subscription): bool
{
    $arr = is_array($subscription)
        ? $subscription
        : (is_object($subscription) && method_exists($subscription, 'toArray') ? $subscription->toArray() : []);
    $subscriptionId = (string) ($arr['id'] ?? '');
    $customer = $arr['customer'] ?? '';
    if (is_array($customer)) {
        $customer = $customer['id'] ?? '';
    }

    return $subscriptionId !== ''
        && hash_equals((string) ($local['stripe_subscription_id'] ?? ''), $subscriptionId)
        && is_string($customer)
        && hash_equals((string) ($local['stripe_customer_id'] ?? ''), $customer)
        && journey_is_journey_price_id(journey_subscription_price_id_for_portal($arr));
}

/** @param array<string,mixed> $subscription */
function journey_subscription_price_id_for_portal(array $subscription): string
{
    $items = $subscription['items']['data'] ?? [];
    if (!is_array($items) || !isset($items[0])) {
        return '';
    }
    $price = $items[0]['price'] ?? null;
    if (is_array($price)) {
        return (string) ($price['id'] ?? '');
    }
    if (is_object($price)) {
        return (string) ($price->id ?? '');
    }
    return is_string($price) ? $price : '';
}

/** @return array<string,mixed> */
function journey_build_portal_session_params(string $customerId, string $subscriptionId): array
{
    return [
        'customer' => $customerId,
        'return_url' => 'https://ronbelisle.com/account.php',
        // Open Stripe's hosted cancellation management flow directly. Both
        // identifiers come from the authenticated user's server-side row.
        'flow_data' => [
            'type' => 'subscription_cancel',
            'subscription_cancel' => [
                'subscription' => $subscriptionId,
            ],
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => [
                    'return_url' => 'https://ronbelisle.com/account.php',
                ],
            ],
        ],
    ];
}
