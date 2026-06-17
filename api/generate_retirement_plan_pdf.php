<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once '../includes/db_config.php';
require_once '../vendor/autoload.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'No data provided']));
}

$required = ['inputs', 'summary', 'projections'];
foreach ($required as $key) {
    if (!isset($data[$key])) {
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['error' => 'Missing required field: ' . $key]));
    }
}
if (!is_array($data['projections'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Projections must be an array']));
}

function rp_h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function rp_money($n) {
    return '$' . number_format((float) $n, 0);
}

function rp_embed_chart($pdf, $chartImage, $title, $noteIfMissing) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(102, 126, 234);
    $pdf->Cell(0, 8, $title, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    if (!empty($chartImage) && $canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $chartImage));
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->Image($tempFile, 20, $pdf->GetY(), 170, 0, 'PNG');
        unlink($tempFile);
        $pdf->Ln(85);
        return;
    }

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 6, $noteIfMissing, 0, 1);
    $pdf->Ln(6);
    $pdf->SetTextColor(0, 0, 0);
}

$inputs = $data['inputs'];
$summary = $data['summary'];
$projections = $data['projections'];
$monteCarlo = isset($data['monteCarlo']) && is_array($data['monteCarlo']) ? $data['monteCarlo'] : null;

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('RonBelisle.com');
$pdf->SetAuthor('Retirement Plan Builder');
$pdf->SetTitle('Retirement Plan Report');
$pdf->SetSubject('Retirement Planning');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$pdf->SetFillColor(102, 126, 234);
$pdf->Rect(0, 0, 210, 40, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetY(12);
$pdf->Cell(0, 10, 'Retirement Plan Builder', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetY(24);
$pdf->Cell(0, 6, 'Your Personalized Retirement Plan Report', 0, 1, 'C');
$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetY(33);
$pdf->Cell(0, 5, 'Generated: ' . date('F j, Y'), 0, 1, 'C');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(50);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Plan Snapshot', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$statusHeadline = isset($summary['statusHeadline']) ? $summary['statusHeadline'] : 'Retirement plan';
$statusDetail = isset($summary['statusDetail']) ? $summary['statusDetail'] : '';

$snapshotHtml = '<div style="background-color:#f8fafc;border-left:4px solid #667eea;padding:10px;margin-bottom:8px;">'
    . '<p style="font-size:14px;font-weight:bold;margin:0 0 6px 0;">' . rp_h($statusHeadline) . '</p>'
    . '<p style="font-size:10px;color:#374151;margin:0;">' . rp_h($statusDetail) . '</p></div>';

$snapshotHtml .= '<table border="0" cellpadding="8"><tr>'
    . '<td width="50%" style="background-color:#f0f9ff;border:2px solid #667eea;"><div style="text-align:center;">'
    . '<div style="font-size:10px;color:#666;">Projected at retirement</div>'
    . '<div style="font-size:18px;font-weight:bold;color:#667eea;">' . rp_money($summary['balanceAtRetirement'] ?? 0) . '</div></div></td>'
    . '<td width="50%" style="background-color:#f0fdf4;border:2px solid #10b981;"><div style="text-align:center;">'
    . '<div style="font-size:10px;color:#666;">Rule-of-thumb target</div>'
    . '<div style="font-size:18px;font-weight:bold;color:#10b981;">'
    . (($summary['targetNestEgg'] ?? 0) > 0 ? rp_money($summary['targetNestEgg']) : 'Not required')
    . '</div></div></td>'
    . '</tr><tr>'
    . '<td style="background-color:#fffbeb;border:2px solid #f59e0b;"><div style="text-align:center;">'
    . '<div style="font-size:10px;color:#666;">Retirement income (plan running)</div>'
    . '<div style="font-size:18px;font-weight:bold;color:#f59e0b;">' . rp_money($summary['retirementAnnualIncome'] ?? 0) . '</div></div></td>'
    . '<td style="background-color:#fef2f2;border:2px solid #ef4444;"><div style="text-align:center;">'
    . '<div style="font-size:10px;color:#666;">Lifetime est. federal tax</div>'
    . '<div style="font-size:18px;font-weight:bold;color:#ef4444;">' . rp_money($summary['lifetimeFederalTax'] ?? 0) . '</div></div></td>'
    . '</tr></table>';

$pdf->writeHTML($snapshotHtml, true, false, true, false, '');
$pdf->Ln(6);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Your Inputs', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

$inputRows = [
    ['Current age', $inputs['currentAge'] ?? '—'],
    ['Retirement age', $inputs['retirementAge'] ?? '—'],
    ['Plan through age', $inputs['planEndAge'] ?? '90'],
    ['Retirement savings today', rp_money($inputs['balance'] ?? 0)],
    ['Annual contributions (pre-retirement)', rp_money($inputs['annualContribution'] ?? 0)],
    ['Annual spending in retirement', rp_money($inputs['baseAnnualSpending'] ?? 0)],
    ['Social Security at FRA (monthly)', rp_money($inputs['ssPiaMonthly'] ?? 0)],
    ['Claim Social Security at age', $inputs['ssClaimAge'] ?? '—'],
    ['Other guaranteed income (annual)', rp_money($inputs['otherGuaranteedAnnual'] ?? 0)],
    ['Tax filing status', ucfirst($inputs['filingStatus'] ?? 'married')],
    ['Tax-deferred share of portfolio', ($inputs['taxDeferredPct'] ?? 85) . '%'],
    ['Expected return (pre-retirement)', ($inputs['returnPreRetirement'] ?? 0) . '%'],
    ['Expected return (retirement)', ($inputs['returnRetirement'] ?? 0) . '%'],
];

$inputsHtml = '<table border="0" cellpadding="7" style="background-color:#f9fafb;">';
foreach ($inputRows as $i => $row) {
    $border = $i < count($inputRows) - 1 ? 'border-bottom:1px solid #e5e7eb;' : '';
    $inputsHtml .= '<tr><td width="55%" style="' . $border . '"><b>' . rp_h($row[0]) . ':</b></td>'
        . '<td width="45%" style="' . $border . '">' . rp_h($row[1]) . '</td></tr>';
}
$inputsHtml .= '</table>';
$pdf->writeHTML($inputsHtml, true, false, true, false, '');
$pdf->Ln(6);

if (isset($data['chartImage']) && !empty($data['chartImage'])) {
    rp_embed_chart(
        $pdf,
        $data['chartImage'],
        'Portfolio Balance Over Time',
        '(Chart omitted — server needs PHP GD or Imagick extension for images.)'
    );
}

if ($monteCarlo && !empty($monteCarlo['successRate'])) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(102, 126, 234);
    $pdf->Cell(0, 8, 'Monte Carlo Stress Test', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);

    $mcHtml = '<table border="0" cellpadding="8"><tr>'
        . '<td width="35%" style="background-color:#ecfdf5;border:2px solid #0d9488;text-align:center;">'
        . '<div style="font-size:10px;color:#666;">Success rate</div>'
        . '<div style="font-size:22px;font-weight:bold;color:#0f766e;">' . rp_h($monteCarlo['successRate']) . '%</div></td>'
        . '<td width="65%" style="background-color:#f8fafc;border:1px solid #d1d5db;">'
        . '<div style="font-size:10px;"><b>Simulations:</b> ' . rp_h($monteCarlo['numSims'] ?? '') . '</div>'
        . '<div style="font-size:10px;"><b>Volatility:</b> ' . rp_h($monteCarlo['volatilityPct'] ?? '') . '%</div>'
        . '<div style="font-size:10px;"><b>Ending balance percentiles:</b> 25th ' . rp_money($monteCarlo['p25'] ?? 0)
        . ', median ' . rp_money($monteCarlo['p50'] ?? 0) . ', 75th ' . rp_money($monteCarlo['p75'] ?? 0) . '</div>'
        . '</td></tr></table>';
    $pdf->writeHTML($mcHtml, true, false, true, false, '');
    $pdf->Ln(4);

    if (isset($data['mcChartImage']) && !empty($data['mcChartImage'])) {
        rp_embed_chart(
            $pdf,
            $data['mcChartImage'],
            'Monte Carlo Ending Balance Distribution',
            '(Monte Carlo chart omitted — server needs PHP GD or Imagick.)'
        );
    }
}

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(102, 126, 234);
$pdf->Cell(0, 8, 'Year-by-Year Timeline', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$tableHtml = '<table border="1" cellpadding="5" style="border-collapse:collapse;font-size:8px;">'
    . '<thead><tr style="background-color:#667eea;color:white;font-weight:bold;text-align:center;">'
    . '<th width="8%">Age</th>'
    . '<th width="14%">Portfolio</th>'
    . '<th width="12%">Withdrawal</th>'
    . '<th width="12%">SS</th>'
    . '<th width="10%">Other</th>'
    . '<th width="10%">RMD</th>'
    . '<th width="12%">Est. tax</th>'
    . '<th width="12%">Income</th>'
    . '</tr></thead><tbody>';

foreach ($projections as $i => $row) {
    $rowColor = ($i % 2 === 0) ? '#f9fafb' : '#ffffff';
    $tableHtml .= '<tr style="background-color:' . $rowColor . ';text-align:right;">'
        . '<td style="text-align:center;">' . (int) ($row['age'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['balanceEnd'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['withdrawal'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['socialSecurity'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['otherIncome'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['rmd'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['federalTax'] ?? 0) . '</td>'
        . '<td>' . rp_money($row['totalIncome'] ?? 0) . '</td>'
        . '</tr>';
}
$tableHtml .= '</tbody></table>';
$pdf->SetFont('helvetica', '', 8);
$pdf->writeHTML($tableHtml, true, false, true, false, '');

$pdf->Ln(8);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(80, 80, 80);
$disclaimer = 'Educational model only. Federal tax estimates are simplified. RMDs apply to the tax-deferred '
    . 'portion only. Monte Carlo results (if shown) are not predictions of future markets. Not financial or tax advice.';
$pdf->MultiCell(0, 5, $disclaimer, 0, 'L');

$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Generated by RonBelisle.com — Retirement Plan Builder', 0, 0, 'C');

$pdfBytes = $pdf->Output('', 'S');
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Retirement_Plan_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
