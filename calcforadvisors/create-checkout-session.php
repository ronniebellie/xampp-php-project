<?php
/** Authenticated advisor checkout; GET displays confirmation, POST creates. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/auth_helpers.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';
require_once CALCFORADVISORS_INCLUDES . '/cfa_checkout.php';
require_once CALCFORADVISORS_VENDOR . '/autoload.php';
calcforadvisors_require_login();
$plan = ($_GET['plan'] ?? $_POST['plan'] ?? '') === 'annual' ? 'annual' : 'monthly';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo '<h1>Confirm subscription</h1><p>Your account has one 30-day introductory window. Any eligible remaining time carries into checkout; it does not restart. Checkout shows the exact billing date and amount.</p><form method="post">'
        . calcforadvisors_csrf_field() . '<input type="hidden" name="plan" value="' . $plan . '"><button>Continue to secure checkout</button></form>';
    exit;
}
if (!calcforadvisors_csrf_validate($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid request.'); }
try {
    $key = $plan === 'annual' ? 'CALCFORADVISORS_PRICE_ANNUAL' : 'CALCFORADVISORS_PRICE_MONTHLY';
    $price = defined($key) ? constant($key) : '';
    if (!$price || $price === 'price_xxx') throw new RuntimeException('Price unavailable');
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $base = rtrim(defined('CALCFORADVISORS_BASE_URL') ? CALCFORADVISORS_BASE_URL : 'https://calcforadvisors.com', '/');
    $url = cfa_start_checkout(new CfaSqlSubscriptionStore($conn), (int) $_SESSION['calcforadvisors_subscriber_id'], $plan, $price, $base, [
        'subscription' => static fn($id) => \Stripe\Subscription::retrieve($id)->toArray(),
        'customer' => static fn($params,$key) => \Stripe\Customer::create($params,['idempotency_key'=>$key])->toArray(),
        'session' => static fn($id) => \Stripe\Checkout\Session::retrieve($id)->toArray(),
        'create' => static fn($params,$key) => \Stripe\Checkout\Session::create($params,['idempotency_key'=>$key])->toArray(),
    ]);
    header('Location: ' . $url); exit;
} catch (Throwable $e) {
    error_log('Advisor checkout: ' . $e->getMessage());
    http_response_code(503);
    echo 'Unable to start another checkout. Manage an existing subscription from your account, or contact support if the problem persists.';
}
