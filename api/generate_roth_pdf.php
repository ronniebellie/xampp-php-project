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
if (!$data || !isset($data['withConversion'], $data['withoutConversion']) || !is_array($data['withConversion']['yearlyData'] ?? null)) {
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
$pdf->SetFillColor(5, 150, 105);
$pdf->Rect(0, 0, 210, 38, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetY(10);
$pdf->Cell(0, 10, 'Roth Conversion Analysis', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetY(26);
$pdf->Cell(0, 6, 'Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(48);

// Your information
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Your Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$info = 'Age: ' . ($data['currentAge'] ?? '') . '  |  Traditional IRA: $' . number_format((float)($data['traditionalIRA'] ?? 0), 0) . '  |  Roth: $' . number_format((float)($data['rothIRA'] ?? 0), 0);
$info .= '  |  Conversion: $' . number_format((float)($data['conversionAmount'] ?? 0), 0) . '/yr for ' . ($data['conversionYears'] ?? 0) . ' years';
$pdf->Cell(0, 6, $info, 0, 1);
$pdf->Ln(4);

// Key results
$taxSavings = $data['taxSavings'] ?? 0;
$breakEven = $data['breakEvenAge'] ?? null;
$convCost = $data['conversionTaxCost'] ?? 0;
$effectiveRate = $data['effectiveTaxRate'] ?? 0;
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$resultsHtml = '<table border="0" cellpadding="6"><tr style="background:#f0fdf4;"><td><b>Lifetime tax savings (with conversion)</b></td><td>$' . number_format($taxSavings, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Break-even age</b></td><td>' . ($breakEven ? $breakEven : 'N/A') . '</td></tr>';
$resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>First-year conversion tax cost</b></td><td>$' . number_format($convCost, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Effective rate on conversion</b></td><td>' . number_format($effectiveRate, 2) . '%</td></tr></table>';
$pdf->writeHTML($resultsHtml, true, false, true, false, '');
$pdf->Ln(6);

// Chart
if (!empty($data['chartImage'])) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage']));
        $tempFile = tempnam(sys_get_temp_dir(), 'rothchart_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Cumulative Taxes Paid Over Time', 0, 1);
        $pdf->Ln(2);
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        unlink($tempFile);
        $pdf->Ln(70);
    }
}

// Year-by-year table
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Year-by-Year Projection (With Conversion)', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$rows = $data['withConversion']['yearlyData'];
$tableHtml = '<table border="1" cellpadding="4" style="font-size:8px;"><tr style="background:#059669;color:white;font-weight:bold;"><th>Age</th><th>Year</th><th>Conversion</th><th>RMD</th><th>Income</th><th>Federal Tax</th><th>Trad IRA</th><th>Roth IRA</th></tr>';
foreach ($rows as $r) {
    $tableHtml .= '<tr><td>' . $r['age'] . '</td><td>' . $r['year'] . '</td><td>$' . number_format($r['conversion'], 0) . '</td><td>$' . number_format($r['rmd'], 0) . '</td><td>$' . number_format($r['income'], 0) . '</td><td>$' . number_format($r['federalTax'], 0) . '</td><td>$' . number_format($r['traditionalBalance'], 0) . '</td><td>$' . number_format($r['rothBalance'], 0) . '</td></tr>';
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
header('Content-Disposition: attachment; filename="Roth_Conversion_Report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
