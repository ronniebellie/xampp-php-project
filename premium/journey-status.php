<?php
/**
 * Journey Premium account-state JSON for journey.ronbelisle.com.
 * Powers account chrome (M5 P2) and Phase 6 completion CTAs.
 * Reuses existing session + entitlement helpers. Does not start Checkout.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_status.php';

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
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'method_not_allowed',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(journey_status_build_response($conn), JSON_UNESCAPED_SLASHES);
