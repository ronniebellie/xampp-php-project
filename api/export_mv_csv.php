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
if (!$data || !isset($data['managedData'], $data['vanguardData']) || !is_array($data['managedData'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$mRows = $data['managedData'];
$vRows = $data['vanguardData'];

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Managed_vs_Vanguard_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
rb_csv_context($out, 'Managed vs Vanguard', $data);
rb_csv_row($out, ['Year', 'Contributions This Year', 'Cumulative Contributions', 'Managed Ending Balance', 'Managed Annual Fee', 'Managed Cumulative Fees', 'Vanguard Ending Balance', 'Vanguard Annual Fee', 'Vanguard Cumulative Fees', 'Portfolio Difference']);
for ($i = 0; $i < count($mRows) && $i < count($vRows); $i++) {
    $m = $mRows[$i];
    $v = $vRows[$i];
    rb_csv_row($out, [
        $m['year'],
        number_format((float)($m['contributions'] ?? 0), 2),
        number_format((float)($m['cumulativeContributions'] ?? 0), 2),
        number_format($m['balance'], 2),
        number_format($m['fee'], 2),
        number_format($m['totalFees'], 2),
        number_format($v['balance'], 2),
        number_format($v['fee'], 2),
        number_format($v['totalFees'], 2),
        number_format($v['balance'] - $m['balance'], 2)
    ]);
}
fclose($out);
exit;
