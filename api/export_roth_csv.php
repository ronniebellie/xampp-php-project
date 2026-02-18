<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once __DIR__ . '/../includes/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    die(json_encode(['error' => 'Not logged in']));
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sub = null;
$stmt->bind_result($sub);
$user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
$stmt->close();
if (!$user || $user['subscription_status'] !== 'premium') {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['withConversion']['yearlyData']) || !is_array($data['withConversion']['yearlyData'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$rows = $data['withConversion']['yearlyData'];
$withoutRows = isset($data['withoutConversion']['yearlyData']) && is_array($data['withoutConversion']['yearlyData']) ? $data['withoutConversion']['yearlyData'] : [];

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Roth_Conversion_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Age', 'Year', 'Conversion', 'RMD', 'Total Income', 'Taxable Income', 'Federal Tax', 'Cumulative Tax', 'Traditional IRA', 'Roth IRA']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['age'],
        $r['year'],
        number_format($r['conversion'], 2),
        number_format($r['rmd'], 2),
        number_format($r['income'], 2),
        number_format($r['taxableIncome'], 2),
        number_format($r['federalTax'], 2),
        number_format($r['totalTaxesPaid'], 2),
        number_format($r['traditionalBalance'], 2),
        number_format($r['rothBalance'], 2)
    ]);
}
fclose($out);
exit;
