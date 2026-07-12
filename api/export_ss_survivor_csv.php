<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once '../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['yearly'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing yearly data. Run the analysis first.']));
}

$yearly = $data['yearly'];

function fmtPhaseCsv($phase) {
    $labels = [
        'both_alive' => 'Both spouses living',
        'survivor_lower' => 'Lower earner surviving',
        'survivor_higher' => 'Higher earner surviving',
        'both_deceased' => 'Both spouses deceased'
    ];
    return $labels[$phase] ?? $phase;
}

function fmtMoneyCsv($n) {
    return '$' . number_format((float)$n, 0);
}

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="SS_Survivor_Impact_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, [
    'Calendar year',
    'Higher earner age',
    'Lower earner age',
    'Phase',
    'Higher monthly',
    'Lower monthly',
    'Household monthly',
    'Annual household',
    'Cumulative household'
]);

foreach ($yearly as $row) {
    fputcsv($out, [
        $row['calendarYear'] ?? '',
        $row['higherAge'] ?? '',
        $row['lowerAge'] ?? '',
        fmtPhaseCsv($row['phase'] ?? ''),
        isset($row['monthlyHigher']) ? fmtMoneyCsv($row['monthlyHigher']) : '',
        isset($row['monthlyLower']) ? fmtMoneyCsv($row['monthlyLower']) : '',
        isset($row['householdMonthly']) ? fmtMoneyCsv($row['householdMonthly']) : '',
        isset($row['annualHousehold']) ? fmtMoneyCsv($row['annualHousehold']) : '',
        isset($row['cumulativeHousehold']) ? fmtMoneyCsv($row['cumulativeHousehold']) : ''
    ]);
}

fclose($out);
exit;
