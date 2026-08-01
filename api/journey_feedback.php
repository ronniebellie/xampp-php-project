<?php
/**
 * Journey feedback API.
 * GET  — csrf + optional email prefill (credentials from Journey subdomain)
 * POST — store feedback (JSON or form-urlencoded)
 */
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_plan_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_feedback.php';

journey_plan_handle_options_preflight();
journey_plan_apply_cors_headers();

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $ctx = journey_feedback_session_context($conn);
    journey_plan_json_response([
        'success' => true,
        'csrfToken' => rb_csrf_token(),
        'authenticated' => $ctx['is_signed_in'],
        'email' => $ctx['email'],
        'isPremium' => $ctx['is_premium'],
    ]);
}

if ($method !== 'POST') {
    journey_plan_json_response([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Use GET or POST.',
    ], 405);
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$body = [];
if (strpos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($decoded)) {
        journey_plan_json_response([
            'success' => false,
            'error' => 'invalid_json',
            'message' => 'Request body must be valid JSON.',
            'csrfToken' => rb_csrf_token(),
        ], 400);
    }
    $body = $decoded;
} else {
    $body = $_POST;
}

$csrf = isset($body['csrf_token']) ? (string) $body['csrf_token'] : (isset($body['csrfToken']) ? (string) $body['csrfToken'] : '');
if (!rb_csrf_validate($csrf)) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'invalid_csrf',
        'message' => 'Your session expired. Please reload the page and try again.',
        'csrfToken' => rb_csrf_token(),
    ], 403);
}

$ctx = journey_feedback_session_context($conn);
$result = journey_feedback_store($conn, [
    'trying_to_do' => (string) ($body['trying_to_do'] ?? ''),
    'what_happened' => (string) ($body['what_happened'] ?? ''),
    'email' => (string) ($body['email'] ?? ($ctx['email'] ?? '')),
    'page_url' => (string) ($body['page_url'] ?? ''),
    'journey_phase' => (string) ($body['journey_phase'] ?? ''),
    'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'user_id' => $ctx['user_id'],
    'is_signed_in' => $ctx['is_signed_in'],
    'is_premium' => $ctx['is_premium'],
]);

if (empty($result['ok'])) {
    $code = 400;
    if (($result['error'] ?? '') === 'schema_missing') {
        $code = 503;
    }
    journey_plan_json_response([
        'success' => false,
        'error' => $result['error'] ?? 'save_failed',
        'message' => $result['message'] ?? 'Could not save feedback.',
        'csrfToken' => rb_csrf_token(),
    ], $code);
}

journey_plan_json_response([
    'success' => true,
    'id' => (int) ($result['id'] ?? 0),
    'message' => 'Thank you — your feedback was sent.',
]);
