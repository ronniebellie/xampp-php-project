<?php
/**
 * Journey Premium account-state JSON for journey.ronbelisle.com completion CTAs.
 * Reuses existing session + entitlement helpers. Does not start Checkout.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_checkout.php';

$origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
$allowedOrigins = [
    'https://journey.ronbelisle.com',
    'http://journey.ronbelisle.com',
];
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Accept');
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$authenticated = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
$hasAccess = false;
$entitlementStatus = 'none';
$hadJourneySubscription = false;

if ($authenticated) {
    $userId = (int) $_SESSION['user_id'];
    $hasAccess = has_journey_premium_access($conn, $userId);

    $sql = "SELECT entitlement_status, stripe_status
            FROM user_product_subscriptions
            WHERE user_id = ? AND product_key = ?
            ORDER BY updated_at DESC, id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $product = JOURNEY_PRODUCT_KEY;
        $stmt->bind_param('is', $userId, $product);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (is_array($row)) {
            $hadJourneySubscription = true;
            $entitlementStatus = trim((string) ($row['entitlement_status'] ?? ''));
            if ($entitlementStatus === '') {
                $entitlementStatus = strtolower(trim((string) ($row['stripe_status'] ?? 'none')));
            }
        }
    }
}

if ($hasAccess) {
    $cta = 'open_workspace';
} elseif ($authenticated && $hadJourneySubscription) {
    // Prior Journey subscription exists but access is not active — do not promise another trial.
    $cta = 'subscribe';
} else {
    $cta = 'start_trial';
}

echo json_encode([
    'authenticated' => $authenticated,
    'hasAccess' => $hasAccess,
    'entitlementStatus' => $entitlementStatus !== '' ? $entitlementStatus : 'none',
    'cta' => $cta,
    'checkoutUrl' => 'https://ronbelisle.com/premium/journey.php',
    'workspaceUrl' => 'https://journey.ronbelisle.com/',
    'trialDays' => JOURNEY_CHECKOUT_TRIAL_DAYS,
], JSON_UNESCAPED_SLASHES);
