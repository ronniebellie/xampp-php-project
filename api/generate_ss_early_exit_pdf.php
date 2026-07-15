<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['scenarios'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$fra = $data['fra'] ?? ['years' => 67, 'months' => 0];
$fraStr = $fra['years'] . ($fra['months'] > 0 ? ' + ' . $fra['months'] . 'mo' : '');
$planStop = $data['plannedRetirementAge'] ?? '';
$actualStop = $data['actualStopAge'] ?? '';
$claimAge = $data['claimingAge'] ?? '';
$deltaMo = (float) ($data['deltaMo'] ?? 0);
$deltaLife = (float) ($data['deltaLife'] ?? 0);
$nestEgg = (float) ($data['nestEgg'] ?? 0);
$planMonthly = (float) ($data['planMonthly'] ?? 0);
$actualMonthly = (float) ($data['actualMonthly'] ?? 0);
$planPia = (float) ($data['planPia'] ?? 0);
$actualPia = (float) ($data['actualPia'] ?? 0);
$life = (int) ($data['lifeExpectancy'] ?? 85);
$cola = $data['colaRatePct'] ?? 2.5;
$wdr = $data['withdrawalRatePct'] ?? 4;
$scenarios = $data['scenarios'];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$pdf->SetFillColor(37, 99, 235);
$pdf->Rect(0, 0, 210, 38, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetY(10);
$pdf->Cell(0, 10, 'Early Exit Social Security Impact', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetY(26);
$pdf->Cell(0, 6, 'Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(48);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Your Inputs', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$info = 'Birth: ' . ($data['birthDate'] ?? '')
    . '  |  FRA: ' . $fraStr
    . '  |  Plan stop: ' . $planStop
    . '  |  Actual stop: ' . $actualStop
    . '  |  Claim: ' . $claimAge;
$pdf->MultiCell(0, 5, $info, 0, 'L');
$pdf->MultiCell(0, 5, 'Current earnings: $' . number_format((float) ($data['currentAnnualEarnings'] ?? 0), 0)
    . '  |  Growth: ' . ($data['earningsGrowthRatePct'] ?? 0) . '%'
    . '  |  SSA benefit entered: $' . number_format((float) ($data['ssaBenefitMonthly'] ?? 0), 0) . '/mo'
    . '  |  Life expectancy: ' . $life
    . '  |  COLA: ' . $cola . '%', 0, 'L');
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Key Results', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);

$resultsHtml = '<table border="0" cellpadding="6">'
    . '<tr style="background:#f0f9ff;"><td><b>Monthly reduction</b></td><td>$' . number_format(abs($deltaMo), 0) . '/mo less if you stop at ' . htmlspecialchars((string) $actualStop) . '</td></tr>'
    . '<tr><td><b>Lifetime hit (to ' . $life . ')</b></td><td>$' . number_format(abs($deltaLife), 0) . '</td></tr>'
    . '<tr style="background:#f0f9ff;"><td><b>Extra nest egg @ ' . htmlspecialchars((string) $wdr) . '%</b></td><td>$' . number_format($nestEgg, 0) . '</td></tr>'
    . '<tr><td><b>Benefit as planned (stop ' . htmlspecialchars((string) $planStop) . ')</b></td><td>$' . number_format($planMonthly, 0) . '/mo (PIA $' . number_format($planPia, 0) . ')</td></tr>'
    . '<tr style="background:#fef2f2;"><td><b>Benefit if stop at ' . htmlspecialchars((string) $actualStop) . '</b></td><td>$' . number_format($actualMonthly, 0) . '/mo (PIA $' . number_format($actualPia, 0) . ')</td></tr>'
    . '</table>';
$pdf->writeHTML($resultsHtml, true, false, true, false, '');
$pdf->Ln(4);

if (!empty($data['chartImage'])) {
    $canEmbedPng = extension_loaded('gd') || extension_loaded('imagick');
    if ($canEmbedPng) {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['chartImage']));
        $tempFile = tempnam(sys_get_temp_dir(), 'eechart_') . '.png';
        file_put_contents($tempFile, $imageData);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->Cell(0, 6, 'Monthly Benefit by Work-Stop Scenario', 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        @unlink($tempFile);
        $pdf->Ln(85);
    }
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 8, 'Scenario Comparison', 0, 1);
$pdf->SetTextColor(0, 0, 0);

$tableHtml = '<table border="1" cellpadding="4" style="font-size:9px;">'
    . '<tr style="background:#2563eb;color:white;font-weight:bold;">'
    . '<th>Stop age</th><th>Label</th><th>PIA at FRA</th><th>Benefit at claim</th><th>vs Plan $/mo</th><th>Extra nest egg</th></tr>';

foreach ($scenarios as $s) {
    $stop = $s['stopAge'] ?? '';
    $label = htmlspecialchars((string) ($s['label'] ?? ''));
    $pia = (float) ($s['pia'] ?? 0);
    $monthly = (float) ($s['monthly'] ?? 0);
    $vs = (float) ($s['vsPlan'] ?? 0);
    $egg = (float) ($s['nestEgg'] ?? 0);
    $bg = !empty($s['isActual']) ? 'background:#fef2f2;' : '';
    $tableHtml .= '<tr style="' . $bg . '"><td>' . htmlspecialchars((string) $stop) . '</td><td>' . $label . '</td>'
        . '<td>$' . number_format($pia, 0) . '</td><td>$' . number_format($monthly, 0) . '</td>'
        . '<td>' . ($vs == 0 ? '—' : (($vs > 0 ? '−' : '+') . '$' . number_format(abs($vs), 0))) . '</td>'
        . '<td>' . ($egg > 0 ? '$' . number_format($egg, 0) : '—') . '</td></tr>';
}
$tableHtml .= '</table>';
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($tableHtml, true, false, true, false, '');
$pdf->Ln(6);

$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, 'Educational estimate only — not an official SSA projection. Approximates highest-35 earnings impact; ignores WEP/GPO, disability, spouse/survivor benefits, and exact wage indexing.', 0, 'L');

$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Generated by RonBelisle.com — For informational purposes only.', 0, 0, 'C');

$pdfBytes = $pdf->Output('', 'S');
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="SS_Early_Exit_Report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
exit;
