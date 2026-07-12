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
if (!$data || empty($data['result'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data. Run the analysis first.']));
}

$r = $data['result'];
$opts = $data['opts'] ?? [];
$d = $r['delayAnalysis'] ?? [];
$h = $r['higher'] ?? [];
$l = $r['lower'] ?? [];

function fmt0($n) {
    return '$' . number_format((float)$n, 0);
}

function fmtPhase($phase) {
    $labels = [
        'both_alive' => 'Both spouses living',
        'survivor_lower' => 'Lower earner surviving',
        'survivor_higher' => 'Higher earner surviving',
        'both_deceased' => 'Both spouses deceased'
    ];
    return $labels[$phase] ?? $phase;
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$pdf->SetFillColor(37, 99, 235);
$pdf->Rect(0, 0, 210, 40, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetY(12);
$pdf->Cell(0, 10, 'Social Security Survivor Impact Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetY(28);
$pdf->Cell(0, 6, 'Household couples claiming analysis — ' . date('F j, Y'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(48);

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Key takeaway', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$hero = strip_tags($data['heroText'] ?? '');
$pdf->MultiCell(0, 6, $hero ?: 'Run the calculator for a personalized summary.', 0, 'L');
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Inputs', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$he = $opts['higherEarner'] ?? [];
$le = $opts['lowerEarner'] ?? [];
$longSrc = ($opts['longevitySource'] ?? 'actuarial') === 'actuarial' ? 'SSA 2021 actuarial' : 'Custom';
$pdf->Cell(0, 5, 'Higher earner: born ' . ($he['birthYear'] ?? '') . ', ' . ucfirst($he['sex'] ?? '') . ', age ' . ($he['currentAge'] ?? '') . ', PIA ' . fmt0($he['pia'] ?? 0) . '/mo, claims ' . ($he['claimAge'] ?? '') . ', death age ' . ($he['deathAge'] ?? '') . '.', 0, 1);
$pdf->Cell(0, 5, 'Lower earner: born ' . ($le['birthYear'] ?? '') . ', ' . ucfirst($le['sex'] ?? '') . ', age ' . ($le['currentAge'] ?? '') . ', PIA ' . fmt0($le['pia'] ?? 0) . '/mo, claims ' . ($le['claimAge'] ?? '') . ', death age ' . ($le['deathAge'] ?? '') . '.', 0, 1);
$pdf->Cell(0, 5, 'Longevity: ' . $longSrc . '. COLA: ' . ($opts['colaRate'] ?? 0) . '%.', 0, 1);
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Household results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$html = '<table border="0" cellpadding="5" cellspacing="0">';
$html .= '<tr style="background:#eff6ff;"><td><b>Lifetime household SS</b></td><td>' . fmt0($r['totalHousehold'] ?? 0) . '</td></tr>';
$html .= '<tr><td><b>Before first death</b></td><td>' . fmt0($r['beforeFirstDeath'] ?? 0) . '</td></tr>';
$html .= '<tr style="background:#eff6ff;"><td><b>After first death</b></td><td>' . fmt0($r['afterFirstDeath'] ?? 0) . '</td></tr>';
$html .= '<tr><td><b>Higher earner monthly (at claim)</b></td><td>' . fmt0($h['startMonthly'] ?? 0) . '</td></tr>';
$html .= '<tr style="background:#eff6ff;"><td><b>Lower earner monthly (at claim)</b></td><td>' . fmt0($l['startMonthly'] ?? 0) . '</td></tr>';
$html .= '<tr><td><b>Survivor floor at higher earner death</b></td><td>' . fmt0($d['higherAtDeath'] ?? 0) . '/mo</td></tr>';
$html .= '<tr style="background:#eff6ff;"><td><b>Lower earner income forgone by waiting</b></td><td>' . fmt0($d['forgone'] ?? 0) . '</td></tr>';
$html .= '<tr><td><b>Delay premium recovered before survivor switch</b></td><td>' . fmt0($d['recovered'] ?? 0) . '</td></tr>';
$html .= '<tr style="background:#eff6ff;"><td><b>Net loss on lower earner own record</b></td><td>' . fmt0($d['netLoss'] ?? 0) . '</td></tr>';
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(4);

if (!empty($data['chartImage'])) {
    $canEmbed = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbed) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage']));
        if ($imageData) {
            $tempFile = tempnam(sys_get_temp_dir(), 'sssurv_') . '.png';
            file_put_contents($tempFile, $imageData);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'Household income over time', 0, 1);
            $pdf->Ln(2);
            $chartY = $pdf->GetY();
            $chartH = $pdf->Image($tempFile, 18, $chartY, 174, 0, 'PNG');
            @unlink($tempFile);
            $pdf->SetY($chartY + ($chartH ?: 55) + 12);
        }
    }
}

$strategies = $data['strategies'] ?? [];
if (!empty($strategies)) {
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(37, 99, 235);
    $pdf->Cell(0, 8, 'Strategy comparison', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $stHtml = '<table border="1" cellpadding="4" style="font-size:9px;"><tr style="background:#2563eb;color:#fff;"><th>Strategy</th><th>H claims</th><th>L claims</th><th>Lifetime SS</th></tr>';
    foreach ($strategies as $s) {
        $sr = $s['result'] ?? [];
        $stHtml .= '<tr><td>' . htmlspecialchars($s['name'] ?? '') . '</td><td>' . ($sr['higher']['claimAge'] ?? '') . '</td><td>' . ($sr['lower']['claimAge'] ?? '') . '</td><td>' . fmt0($sr['totalHousehold'] ?? 0) . '</td></tr>';
    }
    $stHtml .= '</table>';
    $pdf->writeHTML($stHtml, true, false, true, false, '');
}

$yearly = $data['yearly'] ?? [];
if (!empty($yearly)) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(37, 99, 235);
    $pdf->Cell(0, 8, 'Year-by-year household income', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $tbl = '<table border="1" cellpadding="3" style="font-size:7px;"><tr style="background:#2563eb;color:#fff;"><th>Year</th><th>H age</th><th>L age</th><th>Phase</th><th>Household/mo</th><th>Cumulative</th></tr>';
    foreach ($yearly as $row) {
        $tbl .= '<tr><td>' . ($row['calendarYear'] ?? '') . '</td><td>' . ($row['higherAge'] ?? '') . '</td><td>' . ($row['lowerAge'] ?? '') . '</td><td>' . htmlspecialchars(fmtPhase($row['phase'] ?? '')) . '</td><td>' . fmt0($row['householdMonthly'] ?? 0) . '</td><td>' . fmt0($row['cumulativeHousehold'] ?? 0) . '</td></tr>';
    }
    $tbl .= '</table>';
    $pdf->writeHTML($tbl, true, false, true, false, '');
}

$pdf->Ln(6);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 4, 'Generated by RonBelisle.com. Simplified SSA rules for planning only; survivor benefits must be applied for separately. Not tax or legal advice.', 0, 'C');

$pdfBytes = $pdf->Output('', 'S');
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="SS_Survivor_Impact_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
