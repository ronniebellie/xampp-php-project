<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once '../includes/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    die(json_encode(['error' => 'Not logged in']));
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user['subscription_status'] !== 'premium') {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
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
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Age', 'Scenario A (' . $a . ') Monthly', 'Scenario A Cumulative', 'Scenario B (' . $b . ') Monthly', 'Scenario B Cumulative', 'Scenario C (' . $c . ') Monthly', 'Scenario C Cumulative']);

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
    fputcsv($out, [
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
