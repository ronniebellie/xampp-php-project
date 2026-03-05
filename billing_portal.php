<?php
/**
 * Stripe Billing Portal – redirects premium users to Stripe's Customer Portal
 * to manage subscription (cancel, update payment method, view invoices).
 */
session_start();
require_once 'includes/db_config.php';
require_once 'includes/stripe_config.php';
require_once 'vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT subscription_status, stripe_subscription_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sub = null;
$stripe_sub_id = null;
$stmt->bind_result($sub, $stripe_sub_id);
$user = $stmt->fetch() ? ['subscription_status' => $sub, 'stripe_subscription_id' => $stripe_sub_id] : null;
$stmt->close();

if (!$user || $user['subscription_status'] !== 'premium') {
    header('Location: account.php');
    exit;
}

if (empty($user['stripe_subscription_id'])) {
    // Premium but no Stripe subscription (e.g. manual grant) – no portal
    header('Location: account.php?msg=no_stripe');
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ronbelisle.com';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$return_url = rtrim($scheme . '://' . $host . $path, '/') . '/account.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $subscription = \Stripe\Subscription::retrieve($user['stripe_subscription_id']);
    $customer_id = $subscription->customer;
} catch (Exception $e) {
    error_log('Billing portal - subscription retrieve failed: ' . $e->getMessage());
    $_SESSION['billing_portal_error'] = $e->getMessage();
    header('Location: account.php?msg=error');
    exit;
}

try {
    $session = \Stripe\BillingPortal\Session::create([
        'customer' => $customer_id,
        'return_url' => $return_url,
    ]);
    header('Location: ' . $session->url);
    exit;
} catch (Exception $e) {
    error_log('Billing portal - session create failed: ' . $e->getMessage());
    $_SESSION['billing_portal_error'] = $e->getMessage();
    header('Location: account.php?msg=error');
    exit;
}
