<?php
/**
 * POST /api/generate_journey_summary_pdf.php
 * Journey Premium-only PDF summary of the completed Retirement Planning Journey.
 */
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_plan_store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_summary_pdf.php';

journey_plan_handle_options_preflight();
journey_plan_apply_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Use POST to generate the Journey summary PDF.',
    ]);
    exit;
}

$userId = journey_plan_session_user_id();
if ($userId <= 0) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'not_authenticated',
        'message' => 'Sign in to download your Journey summary.',
    ]);
    exit;
}

if (!has_journey_premium_access($conn, $userId)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'premium_required',
        'message' => 'Journey Premium is required to download this PDF.',
    ]);
    exit;
}

$body = journey_plan_read_json_body();
$clientProgress = [];
if (is_array($body) && isset($body['progress']) && is_array($body['progress'])) {
    $clientProgress = $body['progress'];
}

$progress = $clientProgress;
$cloud = journey_plan_fetch($conn, $userId);
if (is_array($cloud) && isset($cloud['payload'])) {
    $payload = $cloud['payload'];
    if (is_string($payload)) {
        $decoded = json_decode($payload, true);
        $payload = is_array($decoded) ? $decoded : [];
    }
    if (is_array($payload) && isset($payload['progress']) && is_array($payload['progress'])) {
        // Prefer cloud as source of truth; fill missing phase records from client if needed.
        $cloudProgress = $payload['progress'];
        $cloudRecords = isset($cloudProgress['records']) && is_array($cloudProgress['records'])
            ? $cloudProgress['records']
            : [];
        $clientRecords = isset($clientProgress['records']) && is_array($clientProgress['records'])
            ? $clientProgress['records']
            : [];
        foreach ($clientRecords as $key => $record) {
            if (!isset($cloudRecords[$key]) && is_array($record)) {
                $cloudRecords[$key] = $record;
            }
        }
        $cloudProgress['records'] = $cloudRecords;
        $progress = $cloudProgress;
    }
}

if ($progress === [] || !isset($progress['records']) || !is_array($progress['records'])) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'missing_plan',
        'message' => 'No Journey plan was found to export.',
    ]);
    exit;
}

$displayName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($displayName === '' && is_array($body) && isset($body['displayName'])) {
    $displayName = trim((string) $body['displayName']);
}
if ($displayName === '') {
    $displayName = null;
}

try {
    $pdf = journey_summary_pdf_build($progress, $displayName);
    $filename = 'Retirement-Planning-Journey-Summary.pdf';
    $pdf->Output($filename, 'D');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'pdf_failed',
        'message' => 'Could not generate the PDF. Please try again.',
    ]);
    exit;
}
