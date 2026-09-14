<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/report_csv.php';
rb_api_errors();
require_once __DIR__ . '/../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = rb_read_api_json(2097152);
try { $data=rb_pdf_data($data); } catch(Throwable $e) { rb_api_error(400, 'Invalid or oversized CSV data'); }
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
if (!$data || !isset($data['pasData'], $data['targetData']) || !is_array($data['pasData'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$pRows = $data['pasData'];
$tRows = $data['targetData'];

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Vanguard_PAS_vs_Target_Date_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
rb_csv_context($out, 'Vanguard PAS vs Target Date', $data);
rb_csv_row($out, ['Year', 'PAS Portfolio', 'PAS Annual Fee', 'PAS Cumulative Fees', 'Target Date Portfolio', 'Target Date Annual Fee', 'Target Date Cumulative Fees', 'Portfolio Difference']);
for ($i = 0; $i < count($pRows) && $i < count($tRows); $i++) {
    $p = $pRows[$i];
    $t = $tRows[$i];
    rb_csv_row($out, [
        $p['year'] ?? $i,
        number_format($p['balance'] ?? 0, 2),
        number_format($p['fee'] ?? 0, 2),
        number_format($p['totalFees'] ?? 0, 2),
        number_format($t['balance'] ?? 0, 2),
        number_format($t['fee'] ?? 0, 2),
        number_format($t['totalFees'] ?? 0, 2),
        number_format(($t['balance'] ?? 0) - ($p['balance'] ?? 0), 2)
    ]);
}
fclose($out);
exit;
