<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
if (!$data || !isset($data['dataA'], $data['dataB'], $data['dataC'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$fra = $data['fra'] ?? ['years' => 67, 'months' => 0];
$fraStr = $fra['years'] . ($fra['months'] > 0 ? ' + ' . $fra['months'] . 'mo' : '');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Header
$pdf->SetFillColor(59, 130, 246);
$pdf->Rect(0, 0, 210, 38, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetY(10);
$pdf->Cell(0, 10, 'Social Security Claiming Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetY(26);
$pdf->Cell(0, 6, 'Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(48);

// Your information
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(59, 130, 246);
$pdf->Cell(0, 8, 'Your Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$info = 'Birth Date: ' . ($data['birthDate'] ?? '') . '  |  PIA: $' . number_format($data['monthlyPIA'] ?? 0, 0) . '/mo  |  Life Expectancy: ' . ($data['lifeExpectancy'] ?? 85) . '  |  FRA: ' . $fraStr;
$pdf->Cell(0, 6, $info, 0, 1);
$pdf->Ln(4);

// Key results
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(59, 130, 246);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$a = $data['claimAgeA'] ?? 62; $b = $data['claimAgeB'] ?? 67; $c = $data['claimAgeC'] ?? 70;
$best = $data['bestScenario'] ?? [];
$bestAge = $best['age'] ?? $b;
$resultsHtml = '<table border="0" cellpadding="6"><tr style="background:#f0f9ff;"><td><b>Monthly at Age ' . $a . '</b></td><td>$' . number_format($data['monthlyA'] ?? 0, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Monthly at Age ' . $b . '</b></td><td>$' . number_format($data['monthlyB'] ?? 0, 0) . '</td></tr>';
$resultsHtml .= '<tr style="background:#f0f9ff;"><td><b>Monthly at Age ' . $c . '</b></td><td>$' . number_format($data['monthlyC'] ?? 0, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Best option to age ' . ($data['lifeExpectancy'] ?? 85) . '</b></td><td>Claim at age ' . $bestAge . '</td></tr>';
$resultsHtml .= '<tr style="background:#f0f9ff;"><td><b>Lifetime total (best)</b></td><td>$' . number_format($best['total'] ?? 0, 0) . '</td></tr></table>';
$pdf->writeHTML($resultsHtml, true, false, true, false, '');
$pdf->Ln(6);

// Chart
if (!empty($data['chartImage'])) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage']));
        $tempFile = tempnam(sys_get_temp_dir(), 'sschart_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Cumulative Lifetime Benefits', 0, 1);
        $pdf->Ln(2);
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        unlink($tempFile);
        $pdf->Ln(70);
    }
}

// Year-by-year table
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(59, 130, 246);
$pdf->Cell(0, 8, 'Year-by-Year Comparison', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$dataA = $data['dataA']; $dataB = $data['dataB']; $dataC = $data['dataC'];
$tableHtml = '<table border="1" cellpadding="4" style="font-size:8px;"><tr style="background:#3b82f6;color:white;font-weight:bold;"><th>Age</th><th>A(' . $a . ') Mo</th><th>A Cum</th><th>B(' . $b . ') Mo</th><th>B Cum</th><th>C(' . $c . ') Mo</th><th>C Cum</th></tr>';
$ages = [];
foreach ($dataA as $r) { $ages[$r['age']] = true; }
foreach (array_keys($ages) as $age) {
    $ra = null; $rb = null; $rc = null;
    foreach ($dataA as $r) { if ($r['age'] == $age) { $ra = $r; break; } }
    foreach ($dataB as $r) { if ($r['age'] == $age) { $rb = $r; break; } }
    foreach ($dataC as $r) { if ($r['age'] == $age) { $rc = $r; break; } }
    $tableHtml .= '<tr><td>' . $age . '</td><td>' . ($ra ? '$' . number_format($ra['monthlyBenefit'], 0) : '-') . '</td><td>' . ($ra ? '$' . number_format($ra['cumulativeTotal'], 0) : '-') . '</td>';
    $tableHtml .= '<td>' . ($rb ? '$' . number_format($rb['monthlyBenefit'], 0) : '-') . '</td><td>' . ($rb ? '$' . number_format($rb['cumulativeTotal'], 0) : '-') . '</td>';
    $tableHtml .= '<td>' . ($rc ? '$' . number_format($rc['monthlyBenefit'], 0) : '-') . '</td><td>' . ($rc ? '$' . number_format($rc['cumulativeTotal'], 0) : '-') . '</td></tr>';
}
$tableHtml .= '</table>';
$pdf->SetFont('helvetica', '', 8);
$pdf->writeHTML($tableHtml, true, false, true, false, '');

$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Generated by RonBelisle.com - For informational purposes only.', 0, 0, 'C');

$pdfBytes = $pdf->Output('', 'S');
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="SS_Claiming_Report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
?>
