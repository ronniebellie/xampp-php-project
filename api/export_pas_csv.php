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
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Year', 'PAS Portfolio', 'PAS Annual Fee', 'PAS Cumulative Fees', 'Target Date Portfolio', 'Target Date Annual Fee', 'Target Date Cumulative Fees', 'Portfolio Difference']);
for ($i = 0; $i < count($pRows) && $i < count($tRows); $i++) {
    $p = $pRows[$i];
    $t = $tRows[$i];
    fputcsv($out, [
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
