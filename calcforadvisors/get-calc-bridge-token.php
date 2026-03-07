<?php
/**
 * Generate signed token and redirect to ronbelisle.com calculators.
 * Requires calcforadvisors login.
 */
session_start();
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';

if (empty($_SESSION['calcforadvisors_subscriber_id'])) {
    $_SESSION['calcforadvisors_redirect_after_login'] = 'get-calc-bridge-token.php';
    header('Location: login.php');
    exit;
}

$subId = (int) $_SESSION['calcforadvisors_subscriber_id'];
$plan = $_SESSION['calcforadvisors_plan'] ?? 'free';

$expiry = time() + 3600; // 1 hour
$encId = base64_encode((string) $subId);
$encExpiry = base64_encode((string) $expiry);
$payload = $encId . '.' . $encExpiry;
$sig = hash_hmac('sha256', $payload, CALCFORADVISORS_AUTH_SECRET);
$token = $payload . '.' . $sig;

$ronbelisle = 'https://ronbelisle.com';
$bridgeUrl = $ronbelisle . '/calcforadvisors-bridge.php?token=' . urlencode($token);

header('Location: ' . $bridgeUrl);
exit;
