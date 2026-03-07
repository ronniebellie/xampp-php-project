<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start(); // Prevent any stray output from corrupting the CSV
session_start();
require_once '../includes/db_config.php';

// Check Premium access (ronbelisle or calcforadvisors paid)
require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'No data provided']));
}

$required = ['currentAge', 'accountBalance', 'growthRate', 'socialSecurity', 'pension', 'otherIncome', 'filingStatus', 'summary', 'projections'];
foreach ($required as $key) {
    if (!isset($data[$key])) {
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['error' => 'Missing required field: ' . $key]));
    }
}
if (!isset($data['summary']['firstRMD']) || !is_array($data['projections'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing summary or projections data']));
}

// Generate CSV
ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="RMD_Analysis_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');

// Output BOM for Excel UTF-8 support
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, ['Age', 'Account Balance', 'RMD Amount', 'Total Income', 'Taxable Income', 'Tax Bracket (%)']);

// Write data rows
foreach ($data['projections'] as $row) {
    fputcsv($output, [
        $row['age'],
        number_format($row['balance'], 2),
        number_format($row['rmdAmount'], 2),
        number_format($row['totalIncome'], 2),
        number_format($row['taxableIncome'], 2),
        $row['taxBracket']
    ]);
}

fclose($output);
exit;
?>
