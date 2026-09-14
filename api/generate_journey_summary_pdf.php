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
if ($body === null) journey_plan_json_response(['success' => false, 'error' => 'invalid_json', 'message' => 'Invalid report request.'], 400);
journey_plan_require_csrf($body);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/api_resources.php';
// Export exactly the account plan after a successful save, never a merged plan.
$cloud = journey_plan_fetch($conn, $userId);
$progress = $cloud['payload']['progress'] ?? [];
if (!is_array($progress) || !journey_summary_pdf_ready($progress)) {
    journey_plan_json_response(['success' => false, 'error' => 'plan_needs_review',
        'message' => 'Review and save all six phases with current inputs before downloading your summary.'], 409);
}
try { $progress = rb_pdf_data($progress); }
catch (Throwable $e) { journey_plan_json_response(['success' => false, 'message' => 'The report data is invalid or too large.'], 400); }

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
if ($displayName === '') {
    $displayName = null;
}

session_write_close();
try { $reportLease = rb_ai_lease('user:' . $userId, sys_get_temp_dir() . '/rb-journey-reports'); }
catch (Throwable $e) { journey_plan_json_response(['success' => false, 'message' => 'Report generation is temporarily unavailable.'], 503); }
if ($reportLease === null) journey_plan_json_response(['success' => false, 'message' => 'Please wait a minute before generating another report.'], 429);
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
