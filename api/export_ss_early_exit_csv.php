<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['scenarios'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="SS_Early_Exit_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['# Early Exit Social Security Impact']);
fputcsv($out, ['Birth date', $data['birthDate'] ?? '']);
fputcsv($out, ['Planned stop age', $data['plannedRetirementAge'] ?? '']);
fputcsv($out, ['Actual stop age', $data['actualStopAge'] ?? '']);
fputcsv($out, ['Claiming age', $data['claimingAge'] ?? '']);
fputcsv($out, ['Current annual earnings', $data['currentAnnualEarnings'] ?? '']);
fputcsv($out, ['Earnings growth %', $data['earningsGrowthRatePct'] ?? '']);
fputcsv($out, ['SSA benefit monthly', $data['ssaBenefitMonthly'] ?? '']);
fputcsv($out, ['Life expectancy', $data['lifeExpectancy'] ?? '']);
fputcsv($out, ['COLA %', $data['colaRatePct'] ?? '']);
fputcsv($out, ['Withdrawal rate %', $data['withdrawalRatePct'] ?? '']);
fputcsv($out, ['Monthly reduction', $data['deltaMo'] ?? '']);
fputcsv($out, ['Lifetime hit', $data['deltaLife'] ?? '']);
fputcsv($out, ['Extra nest egg', $data['nestEgg'] ?? '']);
fputcsv($out, []);
fputcsv($out, ['Stop age', 'Label', 'PIA at FRA', 'Benefit at claim', 'vs Plan $/mo', 'Extra nest egg', 'Is actual stop']);

foreach ($data['scenarios'] as $s) {
    fputcsv($out, [
        $s['stopAge'] ?? '',
        $s['label'] ?? '',
        isset($s['pia']) ? number_format((float) $s['pia'], 2, '.', '') : '',
        isset($s['monthly']) ? number_format((float) $s['monthly'], 2, '.', '') : '',
        isset($s['vsPlan']) ? number_format((float) $s['vsPlan'], 2, '.', '') : '',
        isset($s['nestEgg']) ? number_format((float) $s['nestEgg'], 2, '.', '') : '',
        !empty($s['isActual']) ? 'yes' : ''
    ]);
}

fclose($out);
exit;
