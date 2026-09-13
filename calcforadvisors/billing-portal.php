<?php
/**
 * Redirect calcforadvisors subscribers to Stripe Billing Portal.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/auth_helpers.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';
require_once CALCFORADVISORS_VENDOR . '/autoload.php';

calcforadvisors_require_login();
$sub = calcforadvisors_get_subscriber();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !calcforadvisors_csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(403); exit('Invalid request.');
}
$stripe_customer_id = cfa_billing_customer($sub, (int) $_SESSION['calcforadvisors_subscriber_id']);
if ($stripe_customer_id === null) {
    header('Location: account.php?msg=no_billing');
    exit;
}

$return_url = rtrim(defined('CALCFORADVISORS_BASE_URL') ? CALCFORADVISORS_BASE_URL : 'https://calcforadvisors.com', '/') . '/account.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $session = \Stripe\BillingPortal\Session::create([
        'customer' => $stripe_customer_id,
        'return_url' => $return_url,
    ]);
    header('Location: ' . $session->url);
    exit;
} catch (Exception $e) {
    error_log('calcforadvisors billing portal: ' . $e->getMessage());
    $_SESSION['billing_portal_error'] = 'Billing is temporarily unavailable. Please try again.';
    header('Location: account.php?msg=error');
    exit;
}
