<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once __DIR__ . '/../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
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
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Year', 'Managed Portfolio', 'Managed Annual Fee', 'Managed Cumulative Fees', 'Vanguard Portfolio', 'Vanguard Annual Fee', 'Vanguard Cumulative Fees', 'Portfolio Difference']);
for ($i = 0; $i < count($mRows) && $i < count($vRows); $i++) {
    $m = $mRows[$i];
    $v = $vRows[$i];
    fputcsv($out, [
        $m['year'],
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
