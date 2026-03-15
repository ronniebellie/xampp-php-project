<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Header
$pdf->SetFillColor(220, 38, 38);
$pdf->Rect(0, 0, 210, 38, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetY(10);
$pdf->Cell(0, 10, 'Vanguard PAS vs Target Date Funds', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetY(26);
$pdf->Cell(0, 6, 'Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(48);

// Your information
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(0, 8, 'Your Portfolio & Assumptions', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$info = 'Portfolio: $' . number_format((float)($data['portfolioValue'] ?? 0), 0) . '  |  PAS Fee: ' . ($data['pasFee'] ?? 0.30) . '%  |  Target Date Fee: ' . ($data['targetDateFee'] ?? 0.08) . '%';
$info .= '  |  Years: ' . ($data['years'] ?? 0) . '  |  Return: ' . ($data['returnRate'] ?? 0) . '%';
if (!empty($data['withdrawalPct'])) {
    $info .= '  |  Withdrawal: ' . $data['withdrawalPct'] . '%';
}
$pdf->Cell(0, 6, $info, 0, 1);
$pdf->Ln(4);

// Key results
$oppCost = $data['opportunityCost'] ?? 0;
$feeDiff = $data['directFeeDiff'] ?? 0;
$lostGrowth = $data['lostGrowth'] ?? 0;
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$resultsHtml = '<table border="0" cellpadding="6"><tr style="background:#fef2f2;"><td><b>Total Opportunity Cost</b></td><td>$' . number_format($oppCost, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Direct Fee Difference (PAS vs Target Date)</b></td><td>$' . number_format($feeDiff, 0) . '</td></tr>';
$resultsHtml .= '<tr style="background:#fef2f2;"><td><b>Lost Growth</b></td><td>$' . number_format($lostGrowth, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Final Value (PAS)</b></td><td>$' . number_format((float)($data['pasFinal'] ?? 0), 0) . '</td></tr>';
$resultsHtml .= '<tr style="background:#fef2f2;"><td><b>Final Value (Target Date)</b></td><td>$' . number_format((float)($data['targetFinal'] ?? 0), 0) . '</td></tr></table>';
$pdf->writeHTML($resultsHtml, true, false, true, false, '');
$pdf->Ln(6);

// Charts
if (!empty($data['chartImage1'])) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage1']));
        $tempFile = tempnam(sys_get_temp_dir(), 'paschart1_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Portfolio Growth Over Time', 0, 1);
        $pdf->Ln(2);
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        unlink($tempFile);
        $pdf->Ln(70);
    }
}

if (!empty($data['chartImage2'])) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage2']));
        $tempFile = tempnam(sys_get_temp_dir(), 'paschart2_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Cumulative Fees Paid Over Time', 0, 1);
        $pdf->Ln(2);
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        unlink($tempFile);
        $pdf->Ln(70);
    }
}

// Year-by-year table
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(0, 8, 'Year-by-Year Comparison', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$pRows = $data['pasData'];
$tRows = $data['targetData'];
$tableHtml = '<table border="1" cellpadding="4" style="font-size:8px;"><tr style="background:#dc2626;color:white;font-weight:bold;"><th>Year</th><th>PAS Balance</th><th>PAS Fee</th><th>Target Balance</th><th>Target Fee</th><th>Difference</th></tr>';
for ($i = 0; $i < count($pRows) && $i < count($tRows); $i++) {
    $p = $pRows[$i];
    $t = $tRows[$i];
    $diff = ($t['balance'] ?? 0) - ($p['balance'] ?? 0);
    $tableHtml .= '<tr><td>' . ($p['year'] ?? $i) . '</td><td>$' . number_format($p['balance'] ?? 0, 0) . '</td><td>$' . number_format($p['fee'] ?? 0, 0) . '</td><td>$' . number_format($t['balance'] ?? 0, 0) . '</td><td>$' . number_format($t['fee'] ?? 0, 0) . '</td><td>$' . number_format($diff, 0) . '</td></tr>';
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
header('Content-Disposition: attachment; filename="Vanguard_PAS_vs_Target_Date_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
