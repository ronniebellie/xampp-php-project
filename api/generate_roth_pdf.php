<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once __DIR__ . '/../vendor/autoload.php';
$qaInput = PHP_SAPI === 'cli' ? getenv('ROTH_PDF_QA_INPUT') : false;
if ($qaInput) {
    $data = json_decode((string) file_get_contents($qaInput), true);
} else {
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
}
if (!$data || !isset($data['withConversion'], $data['withoutConversion']) || !is_array($data['withConversion']['yearlyData'] ?? null)) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

function rothEmbedChartImage(TCPDF $pdf, ?string $chartImage, string $title, float $x, float $y, float $width, float $height): void {
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
    $pdf->SetXY($x, $y);
    $pdf->Cell($width, 6, $title, 0, 1);
    $pdf->Image($tempFile, $x, $y + 8, $width, $height, 'PNG');
    unlink($tempFile);
}

function rothSumField(array $rows, string $field): float {
    $sum = 0.0;
    foreach ($rows as $r) {
        $sum += (float)($r[$field] ?? 0);
    }
    return $sum;
}

function rothBuildYearlyTableHtml(array $rows, bool $includeIrmaa, bool $includeNiit): string {
    $widths = [4, 5, 6, 6, 7, 7, 8, 7];
    $headers = ['Age', 'Year', 'Status', 'Conv', 'RMD', 'SS', 'MAGI', 'Fed Tax'];
    if ($includeIrmaa) { $headers[] = 'IRMAA'; $widths[] = 6; }
    if ($includeNiit) { $headers[] = 'NIIT'; $widths[] = 5; }
    $headers = array_merge($headers, ['All-In', 'Spending', 'Trad IRA', 'Roth IRA', 'Taxable']);
    $widths = array_merge($widths, [7, 7, 9, 8, 8]);
    $html = '<table width="100%" border="1" cellpadding="2" style="font-size:6.5px;table-layout:fixed;"><thead><tr style="background-color:#059669;color:#ffffff;font-weight:bold;">';
    foreach ($headers as $i => $header) {
        $html .= '<th width="' . $widths[$i] . '%">' . $header . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $values = [
            $r['age'], $r['year'], (($r['filingStatus'] ?? '') === 'married' ? 'MFJ' : ucfirst($r['filingStatus'] ?? '')),
            '$' . number_format($r['conversion'], 0), '$' . number_format($r['rmd'], 0),
            '$' . number_format($r['socialSecurity'] ?? 0, 0), '$' . number_format($r['magi'] ?? $r['income'], 0),
            '$' . number_format($r['federalTax'], 0)
        ];
    if ($includeIrmaa) {
            $values[] = '$' . number_format($r['irmaa'] ?? 0, 0);
    }
    if ($includeNiit) {
            $values[] = '$' . number_format($r['niit'] ?? 0, 0);
    }
        $values = array_merge($values, [
            '$' . number_format($r['allInTax'] ?? $r['federalTax'], 0), '$' . number_format($r['spending'] ?? 0, 0),
            '$' . number_format($r['traditionalBalance'], 0), '$' . number_format($r['rothBalance'], 0),
            '$' . number_format($r['taxableBalance'] ?? 0, 0)
        ]);
        $html .= '<tr>';
        foreach ($values as $i => $value) {
            $html .= '<td width="' . $widths[$i] . '%">' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
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
$assumptions .= '  |  Investment income: $' . number_format((float)($data['annualOrdinaryInvestmentIncome'] ?? 0) + (float)($data['annualLongTermGains'] ?? 0), 0);
$pdf->Cell(0, 6, $assumptions, 0, 1);
$detail = 'Filing: ' . (($data['filingStatus'] ?? '') === 'married' ? 'Married filing jointly' : ucfirst((string)($data['filingStatus'] ?? '')));
$detail .= '  |  Social Security: $' . number_format((float)($data['socialSecuritySelf'] ?? 0) + (float)($data['socialSecuritySpouse'] ?? 0), 0);
$detail .= '  |  Taxable brokerage: $' . number_format((float)($data['taxableAccount'] ?? 0), 0);
$detail .= '  |  Target spending: $' . number_format((float)($data['targetAfterTaxSpending'] ?? 0), 0);
$pdf->Cell(0, 6, $detail, 0, 1);
$survivorDetail = 'Tax source: ' . ucfirst((string)($data['taxPaymentSource'] ?? 'taxable'));
$survivorDetail .= '  |  Assumed death age: ' . ((float)($data['deathAge'] ?? 0) > 0 ? (int)$data['deathAge'] : 'None');
$survivorDetail .= '  |  Survivor spending: ' . number_format((float)($data['survivorSpendingPercent'] ?? 75), 0) . '%';
$pdf->Cell(0, 6, $survivorDetail, 0, 1);
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
$resultsHtml .= '<tr style="background:#f0fdf4;"><td><b>Ending after-tax wealth (no conversion)</b></td><td>$' . number_format($data['withoutConversion']['finalAfterTaxEstate'] ?? 0, 0) . '</td></tr>';
$resultsHtml .= '<tr><td><b>Ending after-tax wealth (with conversion)</b></td><td>$' . number_format($data['withConversion']['finalAfterTaxEstate'] ?? 0, 0) . '</td></tr>';
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

if (!empty($data['chartImage']) || !empty($data['chartNoConvImage']) || !empty($data['chartWithConvImage'])) {
    $pdf->AddPage();
    rothEmbedChartImage($pdf, $data['chartImage'] ?? null, 'Cumulative All-In Taxes Paid Over Time', 15, 18, 180, 82);
    rothEmbedChartImage($pdf, $data['chartNoConvImage'] ?? null, 'Annual All-In Tax Cost - No Conversion', 15, 120, 85, 62);
    rothEmbedChartImage($pdf, $data['chartWithConvImage'] ?? null, 'Annual All-In Tax Cost - With Conversion', 110, 120, 85, 62);
}

$pdf->AddPage('L');
$pdf->SetXY(15, 15);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(5, 150, 105);
$pdf->Cell(0, 8, 'Year-by-Year — With Conversion', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 7);
$pdf->writeHTML(rothBuildYearlyTableHtml($withRows, $includeIrmaa, $includeNiit), true, false, true, false, '');

$pdf->AddPage('L');
$pdf->SetXY(15, 15);
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
if ($qaInput) {
    $qaOutput = getenv('ROTH_PDF_QA_OUTPUT');
    if (!$qaOutput) {
        fwrite(STDERR, "ROTH_PDF_QA_OUTPUT is required\n");
        exit(2);
    }
    file_put_contents($qaOutput, $pdfBytes);
    fwrite(STDOUT, $qaOutput . "\n");
    exit;
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Roth_Conversion_Report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
