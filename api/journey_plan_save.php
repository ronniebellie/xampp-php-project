<?php
/**
 * PUT /api/journey_plan_save.php
 * Upsert the authenticated Premium user's cloud Journey plan.
 */
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_plan_store.php';

journey_plan_handle_options_preflight();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'PUT' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    journey_plan_json_response([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Use PUT (or POST) to save a Journey plan.',
    ], 405);
}

$body = journey_plan_read_json_body();
if ($body === null) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'invalid_json',
        'message' => 'Request body must be valid JSON.',
    ], 400);
}

$userId = journey_plan_session_user_id();
if ($userId <= 0) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'not_authenticated',
        'message' => 'Sign in to save your Journey to your account.',
        'csrfToken' => rb_csrf_token(),
    ], 401);
}

journey_plan_require_csrf($body);

$payload = $body['payload'] ?? null;
if (!is_array($payload)) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'invalid_payload',
        'message' => 'Missing payload object.',
        'csrfToken' => rb_csrf_token(),
    ], 400);
}

$clientUpdatedAt = isset($body['clientUpdatedAt']) && is_string($body['clientUpdatedAt'])
    ? $body['clientUpdatedAt']
    : null;
$force = !empty($body['force']);
$reason = isset($body['reason']) && is_string($body['reason']) ? $body['reason'] : 'autosave';

$result = journey_plan_save($conn, $userId, $payload, $clientUpdatedAt, $reason, $force);
if (empty($result['ok'])) {
    $error = (string) ($result['error'] ?? 'save_failed');
    $status = 400;
    if ($error === 'not_authenticated') {
        $status = 401;
    } elseif ($error === 'premium_required' || $error === 'invalid_csrf') {
        $status = 403;
    } elseif ($error === 'conflict') {
        $status = 409;
    } elseif ($error === 'cloud_save_disabled' || $error === 'schema_missing') {
        $status = 503;
    }

    journey_plan_json_response([
        'success' => false,
        'error' => $error,
        'message' => $result['message'] ?? 'Save failed.',
        'conflict' => $result['conflict'] ?? null,
        'canWrite' => journey_plan_can_write($conn, $userId),
        'csrfToken' => rb_csrf_token(),
    ], $status);
}

journey_plan_json_response([
    'success' => true,
    'authenticated' => true,
    'canWrite' => true,
    'readOnly' => false,
    'exists' => true,
    'plan' => $result['plan'],
    'csrfToken' => rb_csrf_token(),
    'message' => 'Saved to your Journey account.',
], 200);
