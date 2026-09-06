<?php
/**
 * Creates a Stripe Checkout Session for calcforadvisors subscription.
 * Redirects the user to Stripe's hosted payment page.
 *
 * Requires in includes/stripe_config.php:
 *   CALCFORADVISORS_PRICE_MONTHLY
 *   CALCFORADVISORS_PRICE_ANNUAL
 *
 * For sandbox testing, use sk_test_... and pk_test_... in stripe_config.php
 */
$root = dirname(__DIR__);
$includes = file_exists($root . '/includes/stripe_config.php') ? $root . '/includes' : $root . '/html/includes';
$vendor = file_exists($root . '/vendor/autoload.php') ? $root . '/vendor' : $root . '/html/vendor';
require_once $includes . '/stripe_config.php';
require_once $vendor . '/autoload.php';

// Plan: monthly or annual
$plan = $_GET['plan'] ?? 'monthly';
$plan = ($plan === 'annual') ? 'annual' : 'monthly';

$price_id = ($plan === 'annual')
    ? (defined('CALCFORADVISORS_PRICE_ANNUAL') ? CALCFORADVISORS_PRICE_ANNUAL : STRIPE_PRICE_ANNUAL)
    : (defined('CALCFORADVISORS_PRICE_MONTHLY') ? CALCFORADVISORS_PRICE_MONTHLY : STRIPE_PRICE_MONTHLY);

// Reject placeholder – user must set real price IDs
if ($price_id === 'price_xxx') {
    http_response_code(500);
    echo 'Stripe not configured for calcforadvisors. Add CALCFORADVISORS_PRICE_MONTHLY and CALCFORADVISORS_PRICE_ANNUAL to includes/stripe_config.php with your Stripe price IDs.';
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$base = defined('CALCFORADVISORS_BASE_URL')
    ? rtrim(CALCFORADVISORS_BASE_URL, '/')
    : 'https://calcforadvisors.com';

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        // 30-day trial: card collected now; first charge after trial (matches pricing page copy)
        'subscription_data' => [
            'trial_period_days' => 30,
            'metadata' => ['plan' => $plan],
        ],
        'success_url' => $base . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $base . '/index.html#pricing',
        'metadata' => ['plan' => $plan],
    ]);

    header('Location: ' . $session->url);
    exit;

} catch (Exception $e) {
    error_log('CalcForAdvisors checkout session creation failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Unable to start checkout. Please try again or contact support.';
    exit;
}
