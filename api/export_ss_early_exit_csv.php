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
if (!$data || empty($data['scenarios'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="SS_Early_Exit_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
rb_csv_context($out, 'Early Exit Social Security Impact', $data);
rb_csv_row($out, ['# Early Exit Social Security Impact']);
rb_csv_row($out, ['Birth date', $data['birthDate'] ?? '']);
rb_csv_row($out, ['Planned stop age', $data['plannedRetirementAge'] ?? '']);
rb_csv_row($out, ['Actual stop age', $data['actualStopAge'] ?? '']);
rb_csv_row($out, ['Claiming age', $data['claimingAge'] ?? '']);
rb_csv_row($out, ['Current annual earnings', $data['currentAnnualEarnings'] ?? '']);
rb_csv_row($out, ['Earnings growth %', $data['earningsGrowthRatePct'] ?? '']);
rb_csv_row($out, ['SSA benefit monthly', $data['ssaBenefitMonthly'] ?? '']);
rb_csv_row($out, ['Life expectancy', $data['lifeExpectancy'] ?? '']);
rb_csv_row($out, ['COLA %', $data['colaRatePct'] ?? '']);
rb_csv_row($out, ['Withdrawal rate %', $data['withdrawalRatePct'] ?? '']);
rb_csv_row($out, ['Monthly reduction', $data['deltaMo'] ?? '']);
rb_csv_row($out, ['Lifetime hit', $data['deltaLife'] ?? '']);
rb_csv_row($out, ['Extra nest egg', $data['nestEgg'] ?? '']);
rb_csv_row($out, []);
rb_csv_row($out, ['Stop age', 'Label', 'PIA at FRA', 'Benefit at claim', 'vs Plan $/mo', 'Extra nest egg', 'Is actual stop']);

foreach ($data['scenarios'] as $s) {
    rb_csv_row($out, [
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
