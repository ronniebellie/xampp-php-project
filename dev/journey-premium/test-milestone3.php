<?php
/**
 * Journey Premium Milestone 3 — Checkout parameter and auth tests.
 *
 * Does not create live Stripe Checkout Sessions.
 *
 * Usage:
 *   php dev/journey-premium/test-milestone3.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $_SESSION = [];
}

require_once $root . '/includes/auth_flow_helpers.php';
require_once $root . '/includes/csrf.php';
require_once $root . '/includes/journey_checkout.php';
require_once $root . '/includes/journey_stripe_sync.php';

$passed = [];
$failed = [];

function expect3(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

// Use fixture Journey Prices for deterministic classification (no live Session create).
journey_price_id_overrides_set([
    'monthly' => 'price_journey_m3_monthly',
    'annual' => 'price_journey_m3_annual',
]);

expect3('checkout config ready with fixtures', journey_stripe_checkout_config_ready());
expect3('monthly resolves to journey monthly', journey_resolve_plan_price_id('monthly') === 'price_journey_m3_monthly');
expect3('annual resolves to journey annual', journey_resolve_plan_price_id('annual') === 'price_journey_m3_annual');
expect3('unknown plan rejected', journey_resolve_plan_price_id('lifetime') === null);
expect3('empty plan rejected', journey_resolve_plan_price_id('') === null);
expect3('browser price id cannot be used as plan', journey_resolve_plan_price_id('price_1TxEfuHLmh7rIjELlLr13kvE') === null);

$builtMonthly = journey_build_checkout_session_params(
    42,
    'monthly',
    'tester@example.com',
    'https://ronbelisle.com/premium/journey-success.php?session_id={CHECKOUT_SESSION_ID}',
    'https://ronbelisle.com/premium/journey.php?canceled=1&plan=monthly'
);
expect3('monthly builder ok', !empty($builtMonthly['ok']));
$pm = $builtMonthly['params'] ?? [];
expect3('mode subscription', ($pm['mode'] ?? '') === 'subscription');
expect3('quantity 1', ($pm['line_items'][0]['quantity'] ?? 0) === 1);
expect3('monthly price only', ($pm['line_items'][0]['price'] ?? '') === 'price_journey_m3_monthly');
expect3('trial 30 days', (int) ($pm['subscription_data']['trial_period_days'] ?? 0) === 30);
expect3('payment_method_collection always', ($pm['payment_method_collection'] ?? '') === 'always');
expect3('card payment method', ($pm['payment_method_types'][0] ?? '') === 'card');
expect3('client_reference_id set', ($pm['client_reference_id'] ?? '') === '42');
expect3('session metadata product', ($pm['metadata']['product'] ?? '') === 'journey');
expect3('session metadata product_key', ($pm['metadata']['product_key'] ?? '') === 'journey');
expect3('session metadata user_id', ($pm['metadata']['user_id'] ?? '') === '42');
expect3('session metadata plan monthly', ($pm['metadata']['plan'] ?? '') === 'monthly');
expect3('subscription metadata plan monthly', ($pm['subscription_data']['metadata']['plan'] ?? '') === 'monthly');
expect3(
    'subscription description journey branded',
    ($pm['subscription_data']['description'] ?? '') === 'Retirement Planning Journey Premium'
);
expect3(
    'submit helper mentions Journey Premium',
    strpos((string) ($pm['custom_text']['submit']['message'] ?? ''), 'Journey Premium') !== false
);
expect3('no promotion codes key', !array_key_exists('allow_promotion_codes', $pm));
expect3('customer_email when no customer', ($pm['customer_email'] ?? '') === 'tester@example.com');
expect3('no customer key when none', !isset($pm['customer']));

$builtAnnual = journey_build_checkout_session_params(
    7,
    'annual',
    'a@example.com',
    'https://example.com/s',
    'https://example.com/c',
    'cus_existing_fixture'
);
expect3('annual builder ok', !empty($builtAnnual['ok']));
$pa = $builtAnnual['params'] ?? [];
expect3('annual price only', ($pa['line_items'][0]['price'] ?? '') === 'price_journey_m3_annual');
expect3('annual trial 30', (int) ($pa['subscription_data']['trial_period_days'] ?? 0) === 30);
expect3('annual plan metadata', ($pa['metadata']['plan'] ?? '') === 'annual');
expect3('reuses customer id', ($pa['customer'] ?? '') === 'cus_existing_fixture');
expect3('no customer_email when customer set', !isset($pa['customer_email']));

$badUser = journey_build_checkout_session_params(0, 'monthly', 'a@example.com', 'https://x/s', 'https://x/c');
expect3('invalid user rejected', empty($badUser['ok']) && ($badUser['error'] ?? '') === 'invalid_user');

$badPlan = journey_build_checkout_session_params(1, 'weekly', 'a@example.com', 'https://x/s', 'https://x/c');
expect3('invalid plan builder error', empty($badPlan['ok']) && ($badPlan['error'] ?? '') === 'invalid_plan');

// Defense: even if overrides somehow pointed at consumer prices, reject.
$consumerMonthly = defined('STRIPE_PRICE_MONTHLY') ? (string) STRIPE_PRICE_MONTHLY : '';
if ($consumerMonthly !== '' && strpos($consumerMonthly, 'price_') === 0) {
    journey_price_id_overrides_set(['monthly' => $consumerMonthly, 'annual' => 'price_journey_m3_annual']);
    $rej = journey_build_checkout_session_params(1, 'monthly', 'a@example.com', 'https://x/s', 'https://x/c');
    expect3(
        'consumer price cannot be selected',
        empty($rej['ok']) && in_array($rej['error'] ?? '', ['non_journey_price', 'consumer_price_rejected'], true),
        (string) ($rej['error'] ?? 'none')
    );
    journey_price_id_overrides_set([
        'monthly' => 'price_journey_m3_monthly',
        'annual' => 'price_journey_m3_annual',
    ]);
} else {
    expect3('consumer price cannot be selected', true, 'skipped (no consumer price configured)');
}

$cfaMonthly = defined('CALCFORADVISORS_PRICE_MONTHLY') ? (string) CALCFORADVISORS_PRICE_MONTHLY : '';
if ($cfaMonthly !== '' && strpos($cfaMonthly, 'price_') === 0) {
    journey_price_id_overrides_set(['monthly' => $cfaMonthly, 'annual' => 'price_journey_m3_annual']);
    $rejCfa = journey_build_checkout_session_params(1, 'monthly', 'a@example.com', 'https://x/s', 'https://x/c');
    expect3(
        'cfa price cannot be selected',
        empty($rejCfa['ok']) && in_array($rejCfa['error'] ?? '', ['non_journey_price', 'cfa_price_rejected'], true),
        (string) ($rejCfa['error'] ?? 'none')
    );
    journey_price_id_overrides_set([
        'monthly' => 'price_journey_m3_monthly',
        'annual' => 'price_journey_m3_annual',
    ]);
} else {
    expect3('cfa price cannot be selected', true, 'skipped (no cfa price configured)');
}

expect3('success page never grants entitlement', journey_success_page_grants_entitlement() === false);

// CSRF
$token = rb_csrf_token();
expect3('csrf token non-empty', $token !== '');
expect3('csrf validates', rb_csrf_validate($token));
expect3('csrf rejects garbage', rb_csrf_validate('not-the-token') === false);

// Auth intent: journey_trial
$_SESSION = [];
$_GET = ['intent' => 'journey_trial'];
expect3('journey trial intent detected', rb_auth_is_journey_trial_intent());
expect3('journey trial is not calculator trial', rb_auth_is_trial_intent() === false);

$_SESSION = ['auth_intent' => 'journey_trial', 'redirect_after_login' => '/premium/journey.php?plan=annual'];
$_GET = [];
expect3('session journey trial intent', rb_auth_is_journey_trial_intent());
$companion = rb_auth_companion_query();
expect3('companion preserves journey_trial', strpos($companion, 'intent=journey_trial') !== false);
expect3('companion preserves premium return path', strpos($companion, 'return=') !== false);

$_SESSION = ['auth_intent' => 'trial'];
$_GET = [];
expect3('calculator trial still works', rb_auth_is_trial_intent());
expect3('calculator trial not journey', rb_auth_is_journey_trial_intent() === false);

expect3('safe path rejects protocol-relative', rb_auth_safe_redirect_path('//evil.example') === '/');
expect3('safe path accepts journey premium', rb_auth_safe_redirect_path('/premium/journey.php') === '/premium/journey.php');

// Production-configured classification (clear overrides).
journey_price_id_overrides_set(null);
$prodMonthly = journey_stripe_monthly_price_id();
$prodAnnual = journey_stripe_annual_price_id();
if ($prodMonthly !== '' && $prodAnnual !== '') {
    expect3('prod monthly classifies journey', journey_classify_price_id($prodMonthly) === 'journey');
    expect3('prod annual classifies journey', journey_classify_price_id($prodAnnual) === 'journey');
    $prodBuilt = journey_build_checkout_session_params(
        99,
        'monthly',
        'prodcheck@example.com',
        'https://ronbelisle.com/premium/journey-success.php?session_id={CHECKOUT_SESSION_ID}',
        'https://ronbelisle.com/premium/journey.php?canceled=1&plan=monthly'
    );
    expect3('prod monthly builder uses configured price', !empty($prodBuilt['ok']) && ($prodBuilt['price_id'] ?? '') === $prodMonthly);
    expect3('prod monthly not consumer/cfa', journey_classify_price_id($prodMonthly) !== 'consumer' && journey_classify_price_id($prodMonthly) !== 'cfa');
} else {
    expect3('prod monthly classifies journey', true, 'skipped (catalog unset in this environment)');
    expect3('prod annual classifies journey', true, 'skipped');
    expect3('prod monthly builder uses configured price', true, 'skipped');
    expect3('prod monthly not consumer/cfa', true, 'skipped');
}

// Optional HTTP checks when JOURNEY_M3_HTTP_BASE is set (e.g. https://ronbelisle.com)
$httpBase = getenv('JOURNEY_M3_HTTP_BASE') ?: '';
if (is_string($httpBase) && $httpBase !== '') {
    $base = rtrim($httpBase, '/');
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 15]]);
    $body = @file_get_contents($base . '/premium/journey.php', false, $ctx);
    $headers = $http_response_header ?? [];
    $statusLine = $headers[0] ?? '';
    $loc = '';
    foreach ($headers as $h) {
        if (stripos($h, 'Location:') === 0) {
            $loc = trim(substr($h, 9));
        }
    }
    expect3(
        'unauthenticated journey page redirects',
        strpos($statusLine, '302') !== false || strpos($statusLine, '303') !== false,
        $statusLine
    );
    expect3(
        'unauthenticated redirect goes to auth',
        strpos($loc, '/auth/') !== false,
        $loc
    );

    $ctxPost = stream_context_create(['http' => ['method' => 'GET', 'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 15]]);
    @file_get_contents($base . '/premium/journey-checkout.php', false, $ctxPost);
    $headers2 = $http_response_header ?? [];
    $status2 = $headers2[0] ?? '';
    expect3('checkout GET rejected', strpos($status2, '405') !== false, $status2);
} else {
    expect3('unauthenticated journey page redirects', true, 'skipped (set JOURNEY_M3_HTTP_BASE)');
    expect3('unauthenticated redirect goes to auth', true, 'skipped');
    expect3('checkout GET rejected', true, 'skipped');
}

echo json_encode([
    'passed' => count($passed),
    'failed' => count($failed),
    'failures' => $failed,
], JSON_PRETTY_PRINT) . "\n";

exit(count($failed) > 0 ? 1 : 0);
