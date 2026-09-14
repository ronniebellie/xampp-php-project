<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/report_csv.php';
rb_api_errors();
require_once '../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = rb_read_api_json(2097152);
try { $data=rb_pdf_data($data); } catch(Throwable $e) { rb_api_error(400, 'Invalid or oversized CSV data'); }
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
if (!$data || !isset($data['dataA'], $data['dataB'], $data['dataC'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$a = $data['claimAgeA'] ?? 62;
$b = $data['claimAgeB'] ?? 67;
$c = $data['claimAgeC'] ?? 70;
$dataA = $data['dataA'];
$dataB = $data['dataB'];
$dataC = $data['dataC'];

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="SS_Claiming_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
rb_csv_context($out, 'Social Security Claiming', $data);
rb_csv_row($out, ['Age', 'Scenario A (' . $a . ') Monthly', 'Scenario A Cumulative PV at age 62', 'Scenario B (' . $b . ') Monthly', 'Scenario B Cumulative PV at age 62', 'Scenario C (' . $c . ') Monthly', 'Scenario C Cumulative PV at age 62']);

$ages = [];
foreach ($dataA as $r) $ages[$r['age']] = true;
foreach ($dataB as $r) $ages[$r['age']] = true;
foreach ($dataC as $r) $ages[$r['age']] = true;
ksort($ages);

foreach (array_keys($ages) as $age) {
    $ra = null; $rb = null; $rc = null;
    foreach ($dataA as $r) { if ($r['age'] == $age) { $ra = $r; break; } }
    foreach ($dataB as $r) { if ($r['age'] == $age) { $rb = $r; break; } }
    foreach ($dataC as $r) { if ($r['age'] == $age) { $rc = $r; break; } }
    rb_csv_row($out, [
        $age,
        $ra ? number_format($ra['monthlyBenefit'], 2) : '',
        $ra ? number_format($ra['cumulativeTotal'], 2) : '',
        $rb ? number_format($rb['monthlyBenefit'], 2) : '',
        $rb ? number_format($rb['cumulativeTotal'], 2) : '',
        $rc ? number_format($rc['monthlyBenefit'], 2) : '',
        $rc ? number_format($rc['cumulativeTotal'], 2) : ''
    ]);
}
fclose($out);
exit;
?>
