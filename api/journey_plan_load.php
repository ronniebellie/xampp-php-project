<?php
/**
 * GET /api/journey_plan_load.php
 * Load the authenticated user's cloud Journey plan.
 *
 * Access:
 * - Journey Premium write access (trialing/active/canceled_grace), or
 * - Existing cloud plan (read-only for inactive former Premium users)
 */
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_plan_store.php';

journey_plan_handle_options_preflight();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    journey_plan_json_response([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Use GET to load a Journey plan.',
    ], 405);
}

if (!journey_cloud_save_enabled()) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'cloud_save_disabled',
        'message' => 'Journey cloud save is temporarily disabled.',
    ], 503);
}

$userId = journey_plan_session_user_id();
if ($userId <= 0) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'not_authenticated',
        'message' => 'Sign in to load your Journey account plan.',
        'csrfToken' => rb_csrf_token(),
    ], 401);
}

$canWrite = journey_plan_can_write($conn, $userId);
$canRead = journey_plan_can_read($conn, $userId);

if (!$canRead) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'premium_required',
        'message' => 'Journey Premium access is required to use account-backed Journey plans.',
        'authenticated' => true,
        'canWrite' => false,
        'readOnly' => false,
        'csrfToken' => rb_csrf_token(),
    ], 403);
}

if (!journey_plan_tables_ready($conn)) {
    journey_plan_json_response([
        'success' => false,
        'error' => 'schema_missing',
        'message' => 'Journey plan tables are not available.',
        'csrfToken' => rb_csrf_token(),
    ], 503);
}

$plan = journey_plan_fetch($conn, $userId);
$readOnly = !$canWrite;

if (!$plan) {
    journey_plan_json_response([
        'success' => true,
        'authenticated' => true,
        'canWrite' => $canWrite,
        'readOnly' => $readOnly,
        'exists' => false,
        'plan' => null,
        'csrfToken' => rb_csrf_token(),
        'message' => $canWrite
            ? 'No Journey plan is saved to your account yet.'
            : 'No Journey plan is available on your account.',
    ], 200);
}

journey_plan_json_response([
    'success' => true,
    'authenticated' => true,
    'canWrite' => $canWrite,
    'readOnly' => $readOnly,
    'exists' => true,
    'plan' => $plan,
    'csrfToken' => rb_csrf_token(),
    'message' => $readOnly
        ? 'Reviewing saved account plan. Cloud updates require active Journey Premium access.'
        : 'Loaded Journey plan from your account.',
], 200);
