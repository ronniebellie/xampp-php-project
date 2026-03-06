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

$stmt = $conn->prepare('SELECT stripe_customer_id, stripe_subscription_id, status FROM calcforadvisors_subscribers WHERE id = ?');
$stmt->bind_param('i', $sub['id']);
$stmt->execute();
$stmt->bind_result($stripe_customer_id, $stripe_subscription_id, $status);
$row = $stmt->fetch();
$stmt->close();
$conn->close();

if (!$row || $status !== 'active' || empty($stripe_customer_id)) {
    header('Location: account.php?msg=no_billing');
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'calcforadvisors.com';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$return_url = rtrim($scheme . '://' . $host . $path, '/') . '/account.php';

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
    $_SESSION['billing_portal_error'] = $e->getMessage();
    header('Location: account.php?msg=error');
    exit;
}
