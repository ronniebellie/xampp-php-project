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
if (!$data || !isset($data['withConversion'], $data['withoutConversion']) || !is_array($data['withConversion']['yearlyData'] ?? null)) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

function rothEmbedChartImage(TCPDF $pdf, ?string $chartImage, string $title, float $width = 180): void {
    if (empty($chartImage)) {
        return;
    }
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if (!$canEmbedPng) {
        return;
    }
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $chartImage));
    if ($imageData === false || $imageData === '') {
        return;
    }
    $tempFile = tempnam(sys_get_temp_dir(), 'rothchart_') . '.png';
    file_put_contents($tempFile, $imageData);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, $title, 0, 1);
    $pdf->Ln(2);
    $yBefore = $pdf->GetY();
    $pdf->Image($tempFile, 15, $yBefore, $width, 0, 'PNG');
    unlink($tempFile);
    $pdf->Ln(62);
}

function rothSumField(array $rows, string $field): float {
    $sum = 0.0;
    foreach ($rows as $r) {
        $sum += (float)($r[$field] ?? 0);
    }
    return $sum;
}

function rothBuildYearlyTableHtml(array $rows, bool $includeIrmaa, bool $includeNiit): string {
    $html = '<table border="1" cellpadding="3" style="font-size:7px;"><tr style="background:#059669;color:white;font-weight:bold;">';
    $html .= '<th>Age</th><th>Year</th><th>Conv</th><th>RMD</th><th>Income</th><th>MAGI</th><th>Fed Tax</th>';
    if ($includeIrmaa) {
        $html .= '<th>IRMAA</th>';
    }
    if ($includeNiit) {
        $html .= '<th>NIIT</th>';
    }
    $html .= '<th>All-In</th><th>Cumul.</th><th>Trad IRA</th><th>Roth IRA</th></tr>';
    foreach ($rows as $r) {
        $allIn = $r['allInTax'] ?? $r['federalTax'];
        $html .= '<tr><td>' . $r['age'] . '</td><td>' . $r['year'] . '</td>';
        $html .= '<td>$' . number_format($r['conversion'], 0) . '</td><td>$' . number_format($r['rmd'], 0) . '</td>';
        $html .= '<td>$' . number_format($r['income'], 0) . '</td><td>$' . number_format($r['magi'] ?? $r['income'], 0) . '</td>';
        $html .= '<td>$' . number_format($r['federalTax'], 0) . '</td>';
        if ($includeIrmaa) {
            $html .= '<td>$' . number_format($r['irmaa'] ?? 0, 0) . '</td>';
        }
        if ($includeNiit) {
            $html .= '<td>$' . number_format($r['niit'] ?? 0, 0) . '</td>';
        }
        $html .= '<td>$' . number_format($allIn, 0) . '</td>';
        $html .= '<td>$' . number_format($r['totalTaxesPaid'], 0) . '</td>';
        $html .= '<td>$' . number_format($r['traditionalBalance'], 0) . '</td>';
        $html .= '<td>$' . number_format($r['rothBalance'], 0) . '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

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

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Your Information', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$info = 'Age: ' . ($data['currentAge'] ?? '') . '  |  Traditional IRA: $' . number_format((float)($data['traditionalIRA'] ?? 0), 0);
$info .= '  |  Roth: $' . number_format((float)($data['rothIRA'] ?? 0), 0);
$info .= '  |  Conversion: $' . number_format((float)($data['conversionAmount'] ?? 0), 0) . '/yr for ' . ($data['conversionYears'] ?? 0) . ' years';
$pdf->Cell(0, 6, $info, 0, 1);

$assumptions = 'Discount rate: ' . number_format((float)($data['discountRate'] ?? 0) * 100, 1) . '%';
$assumptions .= '  |  IRMAA: ' . ((!empty($data['includeIrmaa']) && $data['includeIrmaa'] !== 'false') ? 'Yes' : 'No');
$assumptions .= '  |  NIIT: ' . ((!empty($data['includeNiit']) && $data['includeNiit'] !== 'false') ? 'Yes' : 'No');
$assumptions .= '  |  Investment income: $' . number_format((float)($data['investmentIncome'] ?? 0), 0);
$pdf->Cell(0, 6, $assumptions, 0, 1);
$pdf->Ln(4);

$taxSavings = $data['taxSavings'] ?? 0;
$discountedTaxSavings = $data['discountedTaxSavings'] ?? null;
$discountRate = isset($data['discountRate']) ? (float)$data['discountRate'] * 100 : 0;
$breakEven = $data['breakEvenAge'] ?? null;
$breakEvenDiscounted = $data['breakEvenAgeDiscounted'] ?? null;
$convCost = $data['conversionTaxCost'] ?? 0;
$effectiveRate = $data['effectiveTaxRate'] ?? 0;
$includeIrmaa = !empty($data['includeIrmaa']) && $data['includeIrmaa'] !== 'false' && $data['includeIrmaa'] !== '0';
$includeNiit = !empty($data['includeNiit']) && $data['includeNiit'] !== 'false' && $data['includeNiit'] !== '0';

$withRows = $data['withConversion']['yearlyData'];
$withoutRows = $data['withoutConversion']['yearlyData'] ?? [];

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$resultsHtml = '<table border="0" cellpadding="6"><tr style="background:#f0fdf4;"><td><b>Nominal lifetime tax savings (with conversion)</b></td><td>$' . number_format($taxSavings, 0) . '</td></tr>';
if ($discountRate > 0 && $discountedTaxSavings !== null) {
    $resultsHtml .= '<tr><td><b>Discounted lifetime tax savings (' . number_format($discountRate, 1) . '%)</b></td><td>$' . number_format($discountedTaxSavings, 0) . '</td></tr>';
}
$resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>Break-even age (nominal)</b></td><td>' . ($breakEven ? $breakEven : 'N/A') . '</td></tr>';
if ($discountRate > 0) {
    $resultsHtml .= '<tr><td><b>Break-even age (discounted)</b></td><td>' . ($breakEvenDiscounted ? $breakEvenDiscounted : 'N/A') . '</td></tr>';
}
$resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>First-year conversion tax cost</b></td><td>$' . number_format($convCost, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Effective rate on conversion</b></td><td>' . number_format($effectiveRate, 2) . '%</td></tr>';
if ($includeIrmaa && isset($data['withConversion']['totalIrmaaPaid'], $data['withoutConversion']['totalIrmaaPaid'])) {
    $resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>Lifetime IRMAA (no conversion)</b></td><td>$' . number_format($data['withoutConversion']['totalIrmaaPaid'], 0) . '</td></tr>';
    $resultsHtml .= '<tr><td><b>Lifetime IRMAA (with conversion)</b></td><td>$' . number_format($data['withConversion']['totalIrmaaPaid'], 0) . '</td></tr>';
    $resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>IRMAA reduction</b></td><td>$' . number_format($data['irmaaReduction'] ?? 0, 0) . '</td></tr>';
}
if ($includeNiit && isset($data['withConversion']['totalNiitPaid'], $data['withoutConversion']['totalNiitPaid'])) {
    $resultsHtml .= '<tr><td><b>Lifetime NIIT (no conversion)</b></td><td>$' . number_format($data['withoutConversion']['totalNiitPaid'], 0) . '</td></tr>';
    $resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>Lifetime NIIT (with conversion)</b></td><td>$' . number_format($data['withConversion']['totalNiitPaid'], 0) . '</td></tr>';
    $resultsHtml .= '<tr><td><b>NIIT reduction</b></td><td>$' . number_format($data['niitReduction'] ?? 0, 0) . '</td></tr>';
}
$resultsHtml .= '</table>';
$pdf->writeHTML($resultsHtml, true, false, true, false, '');
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 6, 'Lifetime All-In Tax Breakdown', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$breakdownHtml = '<table border="1" cellpadding="5" style="font-size:9px;"><tr style="background:#e5e7eb;font-weight:bold;"><th>Component</th><th>No Conversion</th><th>With Conversion</th><th>Difference</th></tr>';
$components = [
    ['Federal income tax', 'federalTax'],
    ['Medicare IRMAA', 'irmaa', $includeIrmaa],
    ['NIIT (3.8%)', 'niit', $includeNiit],
    ['Total all-in tax', 'allInTax', true, true]
];
foreach ($components as $comp) {
    if (isset($comp[2]) && !$comp[2]) {
        continue;
    }
    $field = $comp[1];
    $noVal = rothSumField($withoutRows, $field);
    $withVal = rothSumField($withRows, $field);
    $diff = $noVal - $withVal;
    $bold = !empty($comp[3]) ? 'font-weight:bold;background:#f9fafb;' : '';
    $breakdownHtml .= '<tr style="' . $bold . '"><td>' . $comp[0] . '</td><td>$' . number_format($noVal, 0) . '</td><td>$' . number_format($withVal, 0) . '</td><td>$' . number_format($diff, 0) . '</td></tr>';
}
$breakdownHtml .= '</table>';
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($breakdownHtml, true, false, true, false, '');
$pdf->Ln(4);

rothEmbedChartImage($pdf, $data['chartImage'] ?? null, 'Cumulative All-In Taxes Paid Over Time');
rothEmbedChartImage($pdf, $data['chartNoConvImage'] ?? null, 'Annual All-In Tax Cost — No Conversion', 88);
$pdf->Ln(2);
rothEmbedChartImage($pdf, $data['chartWithConvImage'] ?? null, 'Annual All-In Tax Cost — With Conversion', 88);

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Year-by-Year — With Conversion', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 7);
$pdf->writeHTML(rothBuildYearlyTableHtml($withRows, $includeIrmaa, $includeNiit), true, false, true, false, '');

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Year-by-Year — No Conversion', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 7);
$pdf->writeHTML(rothBuildYearlyTableHtml($withoutRows, $includeIrmaa, $includeNiit), true, false, true, false, '');

$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Generated by RonBelisle.com — For informational purposes only. All-in tax includes federal tax, IRMAA, and NIIT where enabled.', 0, 0, 'C');

$pdfBytes = $pdf->Output('', 'S');
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Roth_Conversion_Report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
