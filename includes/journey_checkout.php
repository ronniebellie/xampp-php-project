<?php
/**
 * Journey Premium Checkout helpers (Milestone 3).
 *
 * Builds Stripe Checkout Session parameters for Journey-only Prices.
 * Does not grant entitlement — webhook sync remains authoritative.
 */

if (defined('JOURNEY_CHECKOUT_LOADED')) {
    return;
}
define('JOURNEY_CHECKOUT_LOADED', 1);

require_once __DIR__ . '/journey_entitlement.php';

/** Card-required free trial length for Journey Premium. */
const JOURNEY_CHECKOUT_TRIAL_DAYS = 30;

/**
 * Map an internal plan key to the configured Journey Price ID.
 * Rejects unknown plans and never accepts arbitrary browser Price IDs.
 */
function journey_resolve_plan_price_id(string $plan): ?string
{
    $plan = strtolower(trim($plan));
    if ($plan === 'monthly') {
        $id = journey_stripe_monthly_price_id();
        return ($id !== '' && strpos($id, 'price_') === 0) ? $id : null;
    }
    if ($plan === 'annual') {
        $id = journey_stripe_annual_price_id();
        return ($id !== '' && strpos($id, 'price_') === 0) ? $id : null;
    }
    return null;
}

/**
 * Whether $plan is an allowed Journey plan key.
 */
function journey_is_allowed_checkout_plan(string $plan): bool
{
    return journey_resolve_plan_price_id($plan) !== null;
}

/**
 * Look up a reusable Stripe customer id for this user from Journey rows only.
 * Does not read calcforadvisors_subscribers.
 */
function journey_lookup_stripe_customer_id(mysqli $conn, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }
    $sql = "SELECT stripe_customer_id FROM user_product_subscriptions
            WHERE user_id = ? AND product_key = ?
              AND stripe_customer_id IS NOT NULL AND stripe_customer_id != ''
            ORDER BY updated_at DESC, id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $product = JOURNEY_PRODUCT_KEY;
    $stmt->bind_param('is', $userId, $product);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    $cid = is_array($row) ? trim((string) ($row['stripe_customer_id'] ?? '')) : '';
    return $cid !== '' ? $cid : null;
}

/**
 * Server-side Journey Premium access from user_product_subscriptions.
 * Never uses users.subscription_status.
 */
function has_journey_premium_access(mysqli $conn, int $userId, ?int $nowTs = null): bool
{
    if ($userId <= 0) {
        return false;
    }
    $now = $nowTs ?? time();
    $sql = "SELECT stripe_status, entitlement_status, cancel_at_period_end, current_period_end, trial_end
            FROM user_product_subscriptions
            WHERE user_id = ? AND product_key = ?
            ORDER BY updated_at DESC, id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $product = JOURNEY_PRODUCT_KEY;
    $stmt->bind_param('is', $userId, $product);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        return false;
    }

    $entitlement = (string) ($row['entitlement_status'] ?? '');
    if ($entitlement !== '') {
        return journey_entitlement_allows_premium_access($entitlement);
    }

    $eval = journey_evaluate_subscription_entitlement([
        'status' => (string) ($row['stripe_status'] ?? ''),
        'cancel_at_period_end' => !empty($row['cancel_at_period_end']),
        'current_period_end' => $row['current_period_end'] ?? null,
        'trial_end' => $row['trial_end'] ?? null,
        'product_key' => JOURNEY_PRODUCT_KEY,
    ], $now);

    return !empty($eval['accessAllowed']);
}

/**
 * Build Stripe Checkout Session::create parameters for Journey Premium.
 * Pure builder — does not call Stripe. Safe for unit tests.
 *
 * @return array{ok:bool,error?:string,params?:array<string,mixed>,price_id?:string,plan?:string}
 */
function journey_build_checkout_session_params(
    int $userId,
    string $plan,
    string $customerEmail,
    string $successUrl,
    string $cancelUrl,
    ?string $existingCustomerId = null
): array {
    if ($userId <= 0) {
        return ['ok' => false, 'error' => 'invalid_user'];
    }
    if (!journey_stripe_checkout_config_ready()) {
        return ['ok' => false, 'error' => 'catalog_not_configured'];
    }

    $plan = strtolower(trim($plan));
    $priceId = journey_resolve_plan_price_id($plan);
    if ($priceId === null) {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }

    // Hard reject non-Journey Prices (defense in depth).
    if (!journey_is_journey_price_id($priceId)) {
        return ['ok' => false, 'error' => 'non_journey_price'];
    }
    if (defined('STRIPE_PRICE_MONTHLY') && STRIPE_PRICE_MONTHLY !== '' && hash_equals((string) STRIPE_PRICE_MONTHLY, $priceId)) {
        return ['ok' => false, 'error' => 'consumer_price_rejected'];
    }
    if (defined('STRIPE_PRICE_ANNUAL') && STRIPE_PRICE_ANNUAL !== '' && hash_equals((string) STRIPE_PRICE_ANNUAL, $priceId)) {
        return ['ok' => false, 'error' => 'consumer_price_rejected'];
    }
    if (defined('CALCFORADVISORS_PRICE_MONTHLY') && CALCFORADVISORS_PRICE_MONTHLY !== '' && hash_equals((string) CALCFORADVISORS_PRICE_MONTHLY, $priceId)) {
        return ['ok' => false, 'error' => 'cfa_price_rejected'];
    }
    if (defined('CALCFORADVISORS_PRICE_ANNUAL') && CALCFORADVISORS_PRICE_ANNUAL !== '' && hash_equals((string) CALCFORADVISORS_PRICE_ANNUAL, $priceId)) {
        return ['ok' => false, 'error' => 'cfa_price_rejected'];
    }

    $email = trim($customerEmail);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email'];
    }

    $meta = [
        'product' => JOURNEY_PRODUCT_KEY,
        'product_key' => JOURNEY_PRODUCT_KEY,
        'user_id' => (string) $userId,
        'plan' => $plan,
        'source' => 'journey',
    ];

    $params = [
        'mode' => 'subscription',
        'payment_method_types' => ['card'],
        // Always collect a payment method for the card-required trial.
        'payment_method_collection' => 'always',
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'subscription_data' => [
            'trial_period_days' => JOURNEY_CHECKOUT_TRIAL_DAYS,
            'description' => 'Retirement Planning Journey Premium',
            'metadata' => $meta,
        ],
        'custom_text' => [
            'submit' => [
                'message' => 'Starting Retirement Planning Journey Premium: 30-day free trial. A payment method is required. You will not be charged today. Cancel before the trial ends to avoid being charged.',
            ],
        ],
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => (string) $userId,
        'metadata' => $meta,
    ];

    $existing = $existingCustomerId !== null ? trim($existingCustomerId) : '';
    if ($existing !== '' && strpos($existing, 'cus_') === 0) {
        $params['customer'] = $existing;
    } else {
        $params['customer_email'] = $email;
    }

    return [
        'ok' => true,
        'params' => $params,
        'price_id' => $priceId,
        'plan' => $plan,
    ];
}

/**
 * True when a success page must not grant entitlement (documentation / tests).
 * Journey success handlers must never UPDATE users.subscription_status.
 */
function journey_success_page_grants_entitlement(): bool
{
    return false;
}
