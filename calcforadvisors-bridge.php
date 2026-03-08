<?php
/**
 * Bridge: calcforadvisors subscribers → ronbelisle.com Premium access.
 * Validates signed token from calcforadvisors, sets session, redirects to calculators.
 */
session_start();
require_once __DIR__ . '/includes/db_config.php';

$token = $_GET['token'] ?? '';
$redirect = $_GET['redirect'] ?? '/rmd-impact/';

if (empty($token)) {
    header('Location: https://calcforadvisors.com/login.php?msg=bridge_missing');
    exit;
}

// Load secret (same as calcforadvisors set-password)
$secret = null;
if (file_exists(__DIR__ . '/includes/stripe_config.php')) {
    require_once __DIR__ . '/includes/stripe_config.php';
    $secret = defined('CALCFORADVISORS_AUTH_SECRET') ? CALCFORADVISORS_AUTH_SECRET : null;
}

if (!$secret || $secret === 'replace-with-random-secret-32chars') {
    header('Location: /');
    exit;
}

$parts = explode('.', $token);
if (count($parts) !== 3) {
    header('Location: https://calcforadvisors.com/account.php?msg=bridge_invalid');
    exit;
}

list($encId, $encExpiry, $sig) = $parts;
$payload = $encId . '.' . $encExpiry;
if (!hash_equals(hash_hmac('sha256', $payload, $secret), $sig)) {
    header('Location: https://calcforadvisors.com/account.php?msg=bridge_invalid');
    exit;
}

$subId = (int) base64_decode($encId, true);
$expiry = (int) base64_decode($encExpiry, true);
if ($subId < 1 || $expiry < time()) {
    header('Location: https://calcforadvisors.com/account.php?msg=bridge_expired');
    exit;
}

$stmt = $conn->prepare('SELECT id, plan, status FROM calcforadvisors_subscribers WHERE id = ?');
$stmt->bind_param('i', $subId);
$stmt->execute();
$stmt->bind_result($id, $plan, $status);
if (!$stmt->fetch() || $status !== 'active') {
    $stmt->close();
    header('Location: https://calcforadvisors.com/account.php?msg=bridge_invalid');
    exit;
}
$stmt->close();

$_SESSION['calcforadvisors_subscriber_id'] = $id;
$_SESSION['calcforadvisors_plan'] = $plan;

$target = (strpos($redirect, '/') === 0) ? $redirect : '/rmd-impact/';
header('Location: ' . $target);
exit;
