<?php
/**
 * Build a branded Retirement Planning Journey summary PDF (TCPDF + GD charts).
 *
 * Presentation layer only — does not alter planning calculations or saved values.
 */

declare(strict_types=1);

if (defined('RB_JOURNEY_SUMMARY_PDF_LOADED')) {
    return;
}
define('RB_JOURNEY_SUMMARY_PDF_LOADED', 1);

const JOURNEY_SUMMARY_PDF_VERSION = '1.0';
const JOURNEY_SUMMARY_PDF_SITE = 'journey.ronbelisle.com';
const JOURNEY_SUMMARY_PDF_SITE_URL = 'https://journey.ronbelisle.com/';

/**
 * @param mixed $value
 */
function journey_summary_pdf_money($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    if (!is_numeric($value)) {
        return '—';
    }
    return '$' . number_format((float) $value, 0);
}

/**
 * @param mixed $value
 */
function journey_summary_pdf_pct_from_decimal($value): string
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return '—';
    }
    $pct = ((float) $value) * 100;
    $formatted = rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.');
    return $formatted . '%';
}

/**
 * @param mixed $value
 */
function journey_summary_pdf_num($value): ?float
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

/**
 * @param array<string,mixed>|null $record
 * @return array<string,mixed>
 */
function journey_summary_pdf_record(?array $record): array
{
    return is_array($record) ? $record : [];
}

/**
 * @param string $text
 */
function journey_summary_pdf_h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Map existing Journey language to a restrained status tone (no new scoring).
 *
 * @return array{tone:string,label:string}
 */
function journey_summary_pdf_status_for_phase(string $phaseKey, array $data): array
{
    if ($phaseKey === 'phase3') {
        $code = (string) ($data['phase3']['assessmentCode'] ?? '');
        if ($code === 'workable') {
            return ['tone' => 'green', 'label' => 'Looks workable'];
        }
        if ($code === 'close' || $code === 'difficult') {
            return ['tone' => 'amber', 'label' => 'May need review'];
        }
        return ['tone' => 'blue', 'label' => 'Saved'];
    }

    if ($phaseKey === 'phase4') {
        $label = strtolower((string) ($data['phase4']['resilience'] ?? ''));
        if ($label !== '' && (str_contains($label, 'sensitive') || str_contains($label, 'strain') || str_contains($label, 'pressure'))) {
            return ['tone' => 'amber', 'label' => 'Needs attention'];
        }
        if ($label !== '' && (str_contains($label, 'resilient') || str_contains($label, 'manageable') || str_contains($label, 'stable'))) {
            return ['tone' => 'green', 'label' => 'Saved'];
        }
        return ['tone' => 'blue', 'label' => 'Saved'];
    }

    if ($phaseKey === 'phase5' || $phaseKey === 'phase6') {
        $priority = strtolower((string) ($data[$phaseKey]['priority'] ?? ''));
        if ($priority !== '' && str_contains($priority, 'keep the current')) {
            return ['tone' => 'blue', 'label' => 'Planning note'];
        }
        if ($priority !== '' && (str_contains($priority, 'review') || str_contains($priority, 'consider') || str_contains($priority, 'priority'))) {
            return ['tone' => 'amber', 'label' => 'Review focus'];
        }
        return ['tone' => 'blue', 'label' => 'Saved'];
    }

    return ['tone' => 'green', 'label' => 'Completed'];
}

/**
 * @return array{0:int,1:int,2:int}
 */
function journey_summary_pdf_tone_rgb(string $tone): array
{
    return match ($tone) {
        'green' => [5, 150, 105],
        'amber' => [217, 119, 6],
        default => [29, 78, 216],
    };
}

/**
 * @param array<string,mixed> $progress
 * @return array<string,mixed>
 */
function journey_summary_pdf_extract(array $progress): array
{
    $records = isset($progress['records']) && is_array($progress['records'])
        ? $progress['records']
        : [];

    $phase1 = journey_summary_pdf_record($records['spending-goals'] ?? null);
    $phase1Data = [];
    if (isset($phase1['result']['dataForLaterPhases']) && is_array($phase1['result']['dataForLaterPhases'])) {
        $phase1Data = $phase1['result']['dataForLaterPhases'];
    }

    $phase2 = journey_summary_pdf_record($records['social-security'] ?? null);
    $phase2Saved = [];
    if (isset($phase2['lastSavedPlanning']) && is_array($phase2['lastSavedPlanning'])) {
        $phase2Saved = $phase2['lastSavedPlanning'];
    }

    $phase3 = journey_summary_pdf_record($records['build-your-plan'] ?? null);
    $phase4 = journey_summary_pdf_record($records['stress-test'] ?? null);
    $phase5 = journey_summary_pdf_record($records['tax-strategy'] ?? null);
    $phase6 = journey_summary_pdf_record($records['survivor-planning'] ?? null);

    $assessmentLabels = [
        'workable' => 'Looks workable on these assumptions',
        'close' => 'Looks close and may need adjustment',
        'difficult' => 'Looks difficult on these assumptions',
    ];
    $assessmentCode = (string) ($phase3['baseCaseAssessment'] ?? '');
    $assessmentLabel = $assessmentLabels[$assessmentCode] ?? ($assessmentCode !== '' ? $assessmentCode : '—');

    $phase2Claim = $phase2Saved['claimAge'] ?? $phase2['claimAge'] ?? null;
    $phase2Benefit = $phase2Saved['estimatedMonthlyBenefit']
        ?? $phase2['estimatedMonthlyBenefit']
        ?? null;
    $phase2Fra = $phase2Saved['benefitAtFra'] ?? $phase2['benefitAtFra'] ?? null;
    $phase2Status = $phase2Saved['decisionStatus'] ?? $phase2['decisionStatus'] ?? '';

    $monthlySpending = journey_summary_pdf_num(
        $phase3['monthlyRetirementSpendingGoal']
            ?? $phase1Data['monthlyRetirementSpendingTarget']
            ?? $phase1['monthlyTarget']
            ?? null
    );
    $monthlySs = journey_summary_pdf_num(
        $phase3['monthlySocialSecurityAssumption'] ?? $phase2Benefit
    );
    $monthlyOther = journey_summary_pdf_num(
        $phase3['monthlyOtherDependableIncome']
            ?? $phase1Data['monthlyOtherRegularRetirementIncome']
            ?? 0
    );
    $monthlyFromSavings = journey_summary_pdf_num(
        $phase3['monthlyNeededFromRetirementSavings'] ?? null
    );
    if ($monthlyFromSavings === null && $monthlySpending !== null) {
        $dependable = ($monthlySs ?? 0.0) + ($monthlyOther ?? 0.0);
        $monthlyFromSavings = max(0.0, $monthlySpending - $dependable);
    }

    return [
        'phase1' => [
            'monthlySpending' => $phase1Data['monthlyRetirementSpendingTarget']
                ?? $phase1['monthlyTarget']
                ?? $monthlySpending,
            'otherIncome' => $phase1Data['monthlyOtherRegularRetirementIncome'] ?? ($monthlyOther ?? 0),
            'annualSpending' => $phase1Data['annualRetirementSpendingTarget'] ?? null,
        ],
        'phase2' => [
            'claimAge' => $phase2Claim,
            'estimatedMonthlyBenefit' => $phase2Benefit,
            'benefitAtFra' => $phase2Fra,
            'decisionStatus' => $phase2Status,
        ],
        'phase3' => [
            'monthlySpendingGoal' => $monthlySpending,
            'monthlySocialSecurity' => $monthlySs,
            'monthlyOtherIncome' => $monthlyOther ?? 0.0,
            'monthlyFromSavings' => $monthlyFromSavings,
            'annualFromSavings' => $phase3['annualNeededFromRetirementSavings'] ?? (
                $monthlyFromSavings !== null ? $monthlyFromSavings * 12 : null
            ),
            'savingsBalance' => $phase3['retirementSavingsBalance'] ?? null,
            'withdrawalRate' => $phase3['impliedInitialWithdrawalRate'] ?? null,
            'assessment' => $assessmentLabel,
            'assessmentCode' => $assessmentCode,
        ],
        'phase4' => [
            'resilience' => $phase4['overallResilienceLabel'] ?? '—',
            'strategy' => $phase4['nextAdjustmentLabel'] ?? '—',
            'pressure' => $phase4['pressureSentence'] ?? '',
        ],
        'phase5' => [
            'priority' => $phase5['nextPriorityLabel'] ?? '—',
            'statement' => is_array($phase5['result'] ?? null)
                ? (string) ($phase5['result']['mainIssueStatement'] ?? '')
                : '',
        ],
        'phase6' => [
            'priority' => $phase6['nextPriorityLabel'] ?? '—',
            'titles' => is_array($phase6['result']['issueTitles'] ?? null)
                ? $phase6['result']['issueTitles']
                : [],
        ],
        'overview' => [
            'spending' => $monthlySpending,
            'socialSecurity' => $monthlySs,
            'otherIncome' => $monthlyOther ?? 0.0,
            'fromInvestments' => $monthlyFromSavings,
            'annualFromSavings' => $phase3['annualNeededFromRetirementSavings'] ?? (
                $monthlyFromSavings !== null ? $monthlyFromSavings * 12 : null
            ),
            'savingsBalance' => $phase3['retirementSavingsBalance'] ?? null,
            'withdrawalRate' => $phase3['impliedInitialWithdrawalRate'] ?? null,
            'incomePlan' => $assessmentLabel,
            'resilience' => $phase4['overallResilienceLabel'] ?? '—',
            'taxPriority' => $phase5['nextPriorityLabel'] ?? '—',
            'survivorPriority' => $phase6['nextPriorityLabel'] ?? '—',
        ],
    ];
}

/**
 * @param array<int, array{0:int,1:int,2:int}> $palette
 * @param list<array{0:string,1:float}> $slices label => value
 */
function journey_summary_pdf_chart_donut(array $slices, int $size = 520): ?string
{
    if (!extension_loaded('gd') || $slices === []) {
        return null;
    }
    $total = 0.0;
    foreach ($slices as $slice) {
        $total += max(0.0, (float) $slice[1]);
    }
    if ($total <= 0) {
        return null;
    }

    $img = imagecreatetruecolor($size, $size);
    if ($img === false) {
        return null;
    }
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $palette = [
        [29, 78, 216],
        [14, 165, 233],
        [5, 150, 105],
        [217, 119, 6],
    ];
    $cx = (int) ($size / 2);
    $cy = (int) ($size / 2);
    $radius = (int) ($size * 0.42);
    $start = -90.0;
    $i = 0;
    foreach ($slices as $slice) {
        $value = max(0.0, (float) $slice[1]);
        if ($value <= 0) {
            $i++;
            continue;
        }
        $span = ($value / $total) * 360.0;
        $rgb = $palette[$i % count($palette)];
        $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        if ($color === false) {
            $i++;
            continue;
        }
        $end = $start + $span;
        imagefilledarc($img, $cx, $cy, $radius * 2, $radius * 2, (int) round($start), (int) round($end), $color, IMG_ARC_PIE);
        $start = $end;
        $i++;
    }

    $hole = imagecolorallocate($img, 255, 255, 255);
    if ($hole !== false) {
        imagefilledellipse($img, $cx, $cy, (int) ($radius * 1.15), (int) ($radius * 1.15), $hole);
    }

    $path = tempnam(sys_get_temp_dir(), 'jpdf_donut_');
    if ($path === false) {
        imagedestroy($img);
        return null;
    }
    $png = $path . '.png';
    @unlink($path);
    imagepng($img, $png);
    imagedestroy($img);
    return $png;
}

/**
 * @param list<array{0:string,1:float,2?:array{0:int,1:int,2:int}}> $bars
 */
function journey_summary_pdf_chart_bars(array $bars, int $width = 900, int $height = 420): ?string
{
    if (!extension_loaded('gd') || $bars === []) {
        return null;
    }
    $max = 0.0;
    foreach ($bars as $bar) {
        $max = max($max, (float) $bar[1]);
    }
    if ($max <= 0) {
        $max = 1.0;
    }

    $img = imagecreatetruecolor($width, $height);
    if ($img === false) {
        return null;
    }
    $white = imagecolorallocate($img, 255, 255, 255);
    $grid = imagecolorallocate($img, 226, 232, 240);
    $labelColor = imagecolorallocate($img, 82, 96, 113);
    $valueColor = imagecolorallocate($img, 17, 24, 39);
    if ($white === false || $grid === false || $labelColor === false || $valueColor === false) {
        imagedestroy($img);
        return null;
    }
    imagefill($img, 0, 0, $white);

    $left = 220;
    $right = $width - 40;
    $top = 36;
    $bottom = $height - 36;
    $rowH = (int) (($bottom - $top) / max(1, count($bars)));

    for ($g = 0; $g <= 4; $g++) {
        $x = (int) ($left + (($right - $left) * $g / 4));
        imageline($img, $x, $top - 8, $x, $bottom + 8, $grid);
    }

    foreach ($bars as $idx => $bar) {
        $label = (string) $bar[0];
        $value = max(0.0, (float) $bar[1]);
        $rgb = $bar[2] ?? [29, 78, 216];
        $fill = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        if ($fill === false) {
            continue;
        }
        $y = $top + (int) ($idx * $rowH) + 18;
        $barW = (int) round(($right - $left) * ($value / $max));
        imagefilledrectangle($img, $left, $y, $left + max(4, $barW), $y + 28, $fill);
        imagestring($img, 3, 12, $y + 8, substr($label, 0, 28), $labelColor);
        imagestring($img, 3, $left + max(4, $barW) + 8, $y + 8, journey_summary_pdf_money($value), $valueColor);
    }

    $path = tempnam(sys_get_temp_dir(), 'jpdf_bars_');
    if ($path === false) {
        imagedestroy($img);
        return null;
    }
    $png = $path . '.png';
    @unlink($path);
    imagepng($img, $png);
    imagedestroy($img);
    return $png;
}

/**
 * Restrained withdrawal-rate scale (not a safe/unsafe guarantee).
 */
function journey_summary_pdf_chart_rate(?float $rate, string $assessmentLabel, int $width = 900, int $height = 220): ?string
{
    if (!extension_loaded('gd') || $rate === null) {
        return null;
    }
    $pct = max(0.0, min(12.0, $rate * 100.0));

    $img = imagecreatetruecolor($width, $height);
    if ($img === false) {
        return null;
    }
    $white = imagecolorallocate($img, 255, 255, 255);
    $track = imagecolorallocate($img, 226, 232, 240);
    $accent = imagecolorallocate($img, 29, 78, 216);
    $muted = imagecolorallocate($img, 82, 96, 113);
    $ink = imagecolorallocate($img, 17, 24, 39);
    if ($white === false || $track === false || $accent === false || $muted === false || $ink === false) {
        imagedestroy($img);
        return null;
    }
    imagefill($img, 0, 0, $white);

    $left = 50;
    $right = $width - 50;
    $y = 110;
    imagefilledrectangle($img, $left, $y, $right, $y + 18, $track);
    $fillW = (int) round(($right - $left) * ($pct / 12.0));
    imagefilledrectangle($img, $left, $y, $left + max(2, $fillW), $y + 18, $accent);

    $markerX = $left + $fillW;
    imagefilledellipse($img, $markerX, $y + 9, 22, 22, $accent);

    imagestring($img, 5, $left, 36, journey_summary_pdf_pct_from_decimal($rate) . ' initial withdrawal rate', $ink);
    imagestring($img, 3, $left, 62, substr('Phase 3 assessment: ' . $assessmentLabel, 0, 70), $muted);
    imagestring($img, 2, $left, $y + 36, '0%', $muted);
    imagestring($img, 2, (int) (($left + $right) / 2) - 10, $y + 36, '6%', $muted);
    imagestring($img, 2, $right - 28, $y + 36, '12%', $muted);
    imagestring($img, 2, $left, $y + 58, 'Scale shown for context only - not a guarantee of sustainability.', $muted);

    $path = tempnam(sys_get_temp_dir(), 'jpdf_rate_');
    if ($path === false) {
        imagedestroy($img);
        return null;
    }
    $png = $path . '.png';
    @unlink($path);
    imagepng($img, $png);
    imagedestroy($img);
    return $png;
}

/**
 * @param list<string> $tempFiles
 */
function journey_summary_pdf_cleanup_temps(array &$tempFiles): void
{
    foreach ($tempFiles as $file) {
        if (is_string($file) && $file !== '' && is_file($file)) {
            @unlink($file);
        }
    }
    $tempFiles = [];
}

/**
 * Branded TCPDF document for Journey summary reports.
 */
class JourneySummaryPdfDocument extends TCPDF
{
    /** @var string */
    public $journeyGeneratedLabel = '';

    /** @var bool */
    public $journeyShowHeaderBand = true;

    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'LETTER',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $pdfa = false
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        // Disable invisible TCPDF marketing link on Close().
        $this->tcpdflink = false;
    }

    public function Header(): void
    {
        if (!$this->journeyShowHeaderBand || $this->getPage() === 1) {
            return;
        }
        $this->SetFillColor(29, 78, 216);
        $this->Rect(0, 0, $this->getPageWidth(), 14, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetY(4);
        $this->Cell(95, 6, 'Retirement Planning Journey', 0, 0, 'L', false, JOURNEY_SUMMARY_PDF_SITE_URL);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 6, JOURNEY_SUMMARY_PDF_SITE, 0, 1, 'R', false, JOURNEY_SUMMARY_PDF_SITE_URL);
        $this->SetTextColor(17, 24, 39);
        $this->SetY(18);
    }

    public function Footer(): void
    {
        $generated = $this->journeyGeneratedLabel !== ''
            ? $this->journeyGeneratedLabel
            : date('F j, Y');
        $pageWidth = $this->getPageWidth();
        $this->SetY(-18);
        $this->SetDrawColor(216, 224, 234);
        $this->Line(14, $this->GetY(), $pageWidth - 14, $this->GetY());
        $this->Ln(2.2);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(29, 78, 216);
        $brand = 'Retirement Planning Journey';
        $brandW = $this->GetStringWidth($brand) + 1;
        $this->Cell($brandW, 4, $brand, 0, 0, 'L', false, JOURNEY_SUMMARY_PDF_SITE_URL);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(82, 96, 113);
        $this->Cell(0, 4, ' (' . JOURNEY_SUMMARY_PDF_SITE . ')', 0, 1, 'L', false, JOURNEY_SUMMARY_PDF_SITE_URL);
        $this->SetFont('helvetica', '', 7.5);
        $this->Cell(
            130,
            3.6,
            'Generated ' . $generated . '  •  Journey Report Version ' . JOURNEY_SUMMARY_PDF_VERSION,
            0,
            0,
            'L'
        );
        $this->Cell(0, 3.6, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 1, 'R');
        $this->SetTextColor(120, 130, 142);
        $this->Cell(0, 3.4, 'For educational planning purposes only. This is not financial, tax, or legal advice.', 0, 1, 'L');
    }
}

/**
 * @return array{left:float,right:float,top:float,bottom:float}
 */
function journey_summary_pdf_margins(JourneySummaryPdfDocument $pdf): array
{
    $m = $pdf->getMargins();
    return [
        'left' => (float) ($m['left'] ?? 14),
        'right' => (float) ($m['right'] ?? 14),
        'top' => (float) ($m['top'] ?? 18),
        'bottom' => (float) ($m['bottom'] ?? 28),
    ];
}

/**
 * @param JourneySummaryPdfDocument $pdf
 */
function journey_summary_pdf_ensure_space(JourneySummaryPdfDocument $pdf, float $neededMm): void
{
    $limit = $pdf->getPageHeight() - $pdf->getBreakMargin() - 4;
    if ($pdf->GetY() + $neededMm > $limit) {
        $pdf->AddPage();
    }
}

/**
 * @param JourneySummaryPdfDocument $pdf
 */
function journey_summary_pdf_section_heading(JourneySummaryPdfDocument $pdf, string $title): void
{
    journey_summary_pdf_ensure_space($pdf, 16);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(29, 78, 216);
    $pdf->Cell(0, 7, $title, 0, 1, 'L');
    $pdf->SetDrawColor(216, 224, 234);
    $pdf->Line(14, $pdf->GetY(), $pdf->getPageWidth() - 14, $pdf->GetY());
    $pdf->Ln(3);
    $pdf->SetTextColor(17, 24, 39);
}

/**
 * @param JourneySummaryPdfDocument $pdf
 * @param array<int, array{0:string,1:string}> $rows
 */
function journey_summary_pdf_kv_rows(JourneySummaryPdfDocument $pdf, array $rows): void
{
    $left = journey_summary_pdf_margins($pdf)['left'];
    foreach ($rows as $row) {
        journey_summary_pdf_ensure_space($pdf, 8);
        $label = (string) $row[0];
        $value = (string) $row[1];
        $pdf->SetX($left);
        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->Cell(62, 5.2, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->MultiCell(0, 5.2, $value, 0, 'L');
    }
}

/**
 * @param JourneySummaryPdfDocument $pdf
 * @param array<int, array{0:string,1:string}> $rows
 */
function journey_summary_pdf_phase_block(
    JourneySummaryPdfDocument $pdf,
    string $title,
    string $primary,
    array $status,
    array $rows,
    string $note = ''
): void {
    journey_summary_pdf_ensure_space($pdf, 28);
    $rgb = journey_summary_pdf_tone_rgb($status['tone']);
    $margins = journey_summary_pdf_margins($pdf);
    $left = $margins['left'];
    $y = $pdf->GetY();
    $contentW = $pdf->getPageWidth() - $left - $margins['right'];
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->Rect($left, $y, 1.8, 8, 'F');
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->RoundedRect($left + 1.8, $y, $contentW - 1.8, 8, 1.2, '0110', 'DF');
    $pdf->SetXY($left + 4, $y + 1.5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(17, 24, 39);
    $pdf->Cell($contentW - 48, 5.2, $title, 0, 0, 'L');
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    $chip = (string) $status['label'];
    $chipW = min(40, max(22, $pdf->GetStringWidth($chip) + 6));
    $pdf->SetX($left + $contentW - $chipW - 2);
    $pdf->Cell($chipW, 5.2, $chip, 0, 1, 'C', true);
    $pdf->SetY($y + 10);
    $pdf->SetX($left);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(55, 65, 81);
    $pdf->MultiCell(0, 4.6, $primary, 0, 'L');
    $pdf->Ln(0.5);
    journey_summary_pdf_kv_rows($pdf, $rows);
    if (trim($note) !== '') {
        $pdf->SetX($left);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(0, 4, $note, 0, 'L');
        $pdf->SetTextColor(17, 24, 39);
    }
    $pdf->Ln(1.8);
}

/**
 * @param array<string,mixed> $progress
 */
function journey_summary_pdf_build(array $progress, ?string $displayName = null): TCPDF
{
    $data = journey_summary_pdf_extract($progress);
    $generated = date('F j, Y');
    $tempFiles = [];

    $pdf = new JourneySummaryPdfDocument('P', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->journeyGeneratedLabel = $generated;
    $pdf->SetCreator('Retirement Planning Journey');
    $pdf->SetAuthor('Retirement Planning Journey');
    $pdf->SetTitle('Retirement Planning Journey Summary');
    $pdf->SetSubject('Initial retirement planning summary');
    $pdf->SetKeywords('journey.ronbelisle.com, retirement planning, Journey Premium');
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(14, 20, 14);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(20);
    $pdf->SetAutoPageBreak(true, 24);
    $pdf->AddPage();

    $pageW = $pdf->getPageWidth();

    // ----- Cover / title -----
    $pdf->SetFillColor(29, 78, 216);
    $pdf->Rect(0, 0, $pageW, 48, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetY(12);
    $pdf->Cell(0, 9, 'Retirement Planning Journey', 0, 1, 'C', false, JOURNEY_SUMMARY_PDF_SITE_URL);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, JOURNEY_SUMMARY_PDF_SITE, 0, 1, 'C', false, JOURNEY_SUMMARY_PDF_SITE_URL);
    $pdf->SetY(54);
    $pdf->SetTextColor(17, 24, 39);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->MultiCell(0, 8, 'Your Initial Retirement Planning Summary', 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(82, 96, 113);
    $pdf->MultiCell(
        0,
        5,
        'A summary of the decisions and assumptions carried forward from your six-phase Journey',
        0,
        'L'
    );
    $pdf->Ln(2);
    if ($displayName !== null && trim($displayName) !== '') {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 5.5, 'Prepared for ' . trim($displayName), 0, 1, 'L');
    }
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(82, 96, 113);
    $pdf->Cell(0, 5, 'Generated ' . $generated, 0, 1, 'L');
    $pdf->Ln(3);

    // ----- Executive summary -----
    journey_summary_pdf_section_heading($pdf, 'Executive Summary');
    $ov = $data['overview'];
    $cards = [
        ['Monthly spending goal', journey_summary_pdf_money($ov['spending']) . ' / mo'],
        ['Social Security income', journey_summary_pdf_money($ov['socialSecurity']) . ' / mo'],
        ['Other dependable income', journey_summary_pdf_money($ov['otherIncome']) . ' / mo'],
        ['Needed from investments', journey_summary_pdf_money($ov['fromInvestments']) . ' / mo'],
        ['Annual investment withdrawals', journey_summary_pdf_money($ov['annualFromSavings']) . ' / yr'],
        ['Retirement savings balance', journey_summary_pdf_money($ov['savingsBalance'])],
        ['Initial withdrawal rate', journey_summary_pdf_pct_from_decimal($ov['withdrawalRate'])],
        ['Base-case assessment', (string) $ov['incomePlan']],
    ];

    $margins = journey_summary_pdf_margins($pdf);
    $cardW = ($pageW - $margins['left'] - $margins['right'] - 6) / 2;
    $cardH = 16;
    $startX = $margins['left'];
    $y = $pdf->GetY();
    foreach ($cards as $i => $card) {
        $col = $i % 2;
        $row = intdiv($i, 2);
        if ($col === 0 && $row > 0) {
            journey_summary_pdf_ensure_space($pdf, $cardH + 3);
            $y = $pdf->GetY();
        }
        $x = $startX + ($col * ($cardW + 6));
        if ($col === 0) {
            $y = $pdf->GetY();
        }
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect($x, $y, $cardW, $cardH, 1.8, '1111', 'DF');
        $pdf->SetXY($x + 3, $y + 2.2);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->Cell($cardW - 6, 4, $card[0], 0, 2, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->MultiCell($cardW - 6, 5, $card[1], 0, 'L');
        if ($col === 1 || $i === count($cards) - 1) {
            $pdf->SetY($y + $cardH + 3);
        }
    }

    $pdf->SetFont('helvetica', '', 8.5);
    $pdf->SetTextColor(82, 96, 113);
    $pdf->MultiCell(
        0,
        4.2,
        'Resilience review: ' . (string) $ov['resilience']
            . '  ·  Tax priority: ' . (string) $ov['taxPriority']
            . '  ·  Survivor priority: ' . (string) $ov['survivorPriority'],
        0,
        'L'
    );

    // ----- Visual summaries -----
    $ss = journey_summary_pdf_num($ov['socialSecurity']) ?? 0.0;
    $other = journey_summary_pdf_num($ov['otherIncome']) ?? 0.0;
    $fromInv = journey_summary_pdf_num($ov['fromInvestments']) ?? 0.0;
    $spend = journey_summary_pdf_num($ov['spending']);
    $rate = journey_summary_pdf_num($ov['withdrawalRate']);
    $hasFunding = ($ss + $other + $fromInv) > 0 && $spend !== null;

    if ($hasFunding || $rate !== null) {
        journey_summary_pdf_section_heading($pdf, 'Visual Summaries');
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(
            0,
            4.2,
            'These charts restate figures already saved in your Journey. They are educational planning visuals, not projections or guarantees.',
            0,
            'L'
        );
        $pdf->Ln(1);
    }

    if ($hasFunding) {
        journey_summary_pdf_ensure_space($pdf, 78);
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 6, '1. Monthly retirement-income funding breakdown', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(
            0,
            4,
            'How your monthly spending goal is funded. Total reconciles to '
                . journey_summary_pdf_money($spend) . ' / month.',
            0,
            'L'
        );

        $slices = [
            ['Social Security', $ss],
            ['Other dependable income', $other],
            ['Needed from investments', $fromInv],
        ];
        $donut = journey_summary_pdf_chart_donut($slices);
        if ($donut !== null) {
            $tempFiles[] = $donut;
            $chartY = $pdf->GetY();
            $left = journey_summary_pdf_margins($pdf)['left'];
            $pdf->Image($donut, $left, $chartY, 52, 52, 'PNG');
            $pdf->SetXY($left + 58, $chartY + 4);
            $legend = [
                ['Social Security', journey_summary_pdf_money($ss) . ' / mo', [29, 78, 216]],
                ['Other dependable income', journey_summary_pdf_money($other) . ' / mo', [14, 165, 233]],
                ['Needed from investments', journey_summary_pdf_money($fromInv) . ' / mo', [5, 150, 105]],
            ];
            foreach ($legend as $item) {
                $pdf->SetFillColor($item[2][0], $item[2][1], $item[2][2]);
                $pdf->Rect($pdf->GetX(), $pdf->GetY() + 1.2, 3.5, 3.5, 'F');
                $pdf->SetX($pdf->GetX() + 5);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetTextColor(17, 24, 39);
                $pdf->Cell(55, 5.5, $item[0], 0, 0, 'L');
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(0, 5.5, $item[1], 0, 1, 'L');
                $pdf->SetX($left + 58);
            }
            $pdf->SetY(max($chartY + 56, $pdf->GetY() + 2));
        } else {
            journey_summary_pdf_kv_rows($pdf, [
                ['Social Security', journey_summary_pdf_money($ss) . ' / month'],
                ['Other dependable income', journey_summary_pdf_money($other) . ' / month'],
                ['Needed from investments', journey_summary_pdf_money($fromInv) . ' / month'],
            ]);
        }
        $pdf->Ln(2);

        journey_summary_pdf_ensure_space($pdf, 70);
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 6, '2. Monthly income comparison', 0, 1, 'L');
        $dependable = $ss + $other;
        $bars = [
            ['Monthly spending goal', (float) $spend, [29, 78, 216]],
            ['Dependable monthly income', $dependable, [14, 165, 233]],
            ['Needed from investments', $fromInv, [5, 150, 105]],
        ];
        $barPng = journey_summary_pdf_chart_bars($bars);
        if ($barPng !== null) {
            $tempFiles[] = $barPng;
            $pdf->Image($barPng, journey_summary_pdf_margins($pdf)['left'], $pdf->GetY(), 180, 48, 'PNG');
            $pdf->Ln(50);
        } else {
            journey_summary_pdf_kv_rows($pdf, [
                ['Monthly spending goal', journey_summary_pdf_money($spend) . ' / month'],
                ['Dependable monthly income', journey_summary_pdf_money($dependable) . ' / month'],
                ['Needed from investments', journey_summary_pdf_money($fromInv) . ' / month'],
            ]);
        }
        // Text labels for accessibility / grayscale
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(
            0,
            3.8,
            'Spending goal '
                . journey_summary_pdf_money($spend)
                . '  ·  Dependable income '
                . journey_summary_pdf_money($dependable)
                . '  ·  From investments '
                . journey_summary_pdf_money($fromInv),
            0,
            'L'
        );
        $pdf->Ln(2);
    }

    if ($rate !== null) {
        journey_summary_pdf_ensure_space($pdf, 55);
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 6, '3. Initial withdrawal-rate visual', 0, 1, 'L');
        $ratePng = journey_summary_pdf_chart_rate($rate, (string) $ov['incomePlan']);
        if ($ratePng !== null) {
            $tempFiles[] = $ratePng;
            $pdf->Image($ratePng, journey_summary_pdf_margins($pdf)['left'], $pdf->GetY(), 180, 36, 'PNG');
            $pdf->Ln(38);
        } else {
            journey_summary_pdf_kv_rows($pdf, [
                ['Initial withdrawal rate', journey_summary_pdf_pct_from_decimal($rate)],
                ['Base-case assessment', (string) $ov['incomePlan']],
            ]);
        }
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(
            0,
            4,
            'This is an initial planning measure, not a guarantee of sustainability.',
            0,
            'L'
        );
        $pdf->Ln(2);
    }

    // ----- Phase sections -----
    journey_summary_pdf_section_heading($pdf, 'Phase Details');

    $p1Annual = $data['phase1']['annualSpending'] ?? (
        is_numeric($data['phase1']['monthlySpending'])
            ? ((float) $data['phase1']['monthlySpending'] * 12)
            : null
    );
    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 1 — Spending & Goals',
        'Primary result: monthly spending goal of '
            . journey_summary_pdf_money($data['phase1']['monthlySpending'])
            . ' ('
            . journey_summary_pdf_money($p1Annual)
            . ' annually).',
        journey_summary_pdf_status_for_phase('phase1', $data),
        [
            ['Monthly spending goal', journey_summary_pdf_money($data['phase1']['monthlySpending']) . ' / month'],
            ['Annual spending goal', journey_summary_pdf_money($p1Annual)],
            ['Other regular income', journey_summary_pdf_money($data['phase1']['otherIncome']) . ' / month'],
        ]
    );

    $claimAge = $data['phase2']['claimAge'];
    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 2 — Social Security',
        'Primary assumption: estimated monthly benefit of '
            . journey_summary_pdf_money($data['phase2']['estimatedMonthlyBenefit'])
            . (
                $claimAge !== null && $claimAge !== ''
                    ? ' at claiming age ' . (string) $claimAge
                    : ''
            )
            . '.',
        journey_summary_pdf_status_for_phase('phase2', $data),
        [
            ['Claiming age assumption', $claimAge !== null && $claimAge !== '' ? (string) $claimAge : '—'],
            ['Estimated monthly benefit', journey_summary_pdf_money($data['phase2']['estimatedMonthlyBenefit']) . ' / month'],
            ['Benefit at FRA (reference)', journey_summary_pdf_money($data['phase2']['benefitAtFra']) . ' / month'],
            ['Decision status', $data['phase2']['decisionStatus'] !== ''
                ? ucwords(str_replace('-', ' ', (string) $data['phase2']['decisionStatus']))
                : '—'],
        ]
    );

    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 3 — Build Your Plan',
        'Primary result: ' . (string) $data['phase3']['assessment'] . '.',
        journey_summary_pdf_status_for_phase('phase3', $data),
        [
            ['Monthly spending goal', journey_summary_pdf_money($data['phase3']['monthlySpendingGoal']) . ' / month'],
            ['Social Security in the plan', journey_summary_pdf_money($data['phase3']['monthlySocialSecurity']) . ' / month'],
            ['Other dependable income', journey_summary_pdf_money($data['phase3']['monthlyOtherIncome']) . ' / month'],
            ['Needed from investments', journey_summary_pdf_money($data['phase3']['monthlyFromSavings']) . ' / month'],
            ['Annual investment withdrawals', journey_summary_pdf_money($data['phase3']['annualFromSavings']) . ' / year'],
            ['Retirement savings balance', journey_summary_pdf_money($data['phase3']['savingsBalance'])],
            ['Initial withdrawal rate', journey_summary_pdf_pct_from_decimal($data['phase3']['withdrawalRate'])],
            ['Base-case assessment', (string) $data['phase3']['assessment']],
        ],
        'This is an initial planning measure, not a guarantee of sustainability.'
    );

    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 4 — Stress Test',
        'Primary result: ' . (string) $data['phase4']['resilience'] . '.',
        journey_summary_pdf_status_for_phase('phase4', $data),
        [
            ['Stress-test result', (string) $data['phase4']['resilience']],
            ['Selected strategy', (string) $data['phase4']['strategy']],
        ],
        trim((string) $data['phase4']['pressure'])
    );

    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 5 — Tax Strategy',
        'Primary priority: ' . (string) $data['phase5']['priority'] . '.',
        journey_summary_pdf_status_for_phase('phase5', $data),
        [
            ['Tax-planning priority', (string) $data['phase5']['priority']],
        ],
        trim((string) $data['phase5']['statement'])
    );

    $phase6Note = '';
    $titles = $data['phase6']['titles'];
    if (is_array($titles) && $titles !== []) {
        $parts = [];
        foreach ($titles as $title) {
            if (is_string($title) && trim($title) !== '') {
                $parts[] = '• ' . trim($title);
            }
        }
        $phase6Note = implode("\n", $parts);
    }
    journey_summary_pdf_phase_block(
        $pdf,
        'Phase 6 — Survivor Planning',
        'Primary priority: ' . (string) $data['phase6']['priority'] . '.',
        journey_summary_pdf_status_for_phase('phase6', $data),
        [
            ['Survivor-planning priority', (string) $data['phase6']['priority']],
        ],
        $phase6Note
    );

    // Closing note on the last content page.
    $margins = journey_summary_pdf_margins($pdf);
    $left = $margins['left'];
    $limit = $pdf->getPageHeight() - $pdf->getBreakMargin() - 2;
    if ($pdf->GetY() + 14 <= $limit) {
        $pdf->Ln(1.5);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $boxY = $pdf->GetY();
        $pdf->RoundedRect($left, $boxY, $pageW - $left - $margins['right'], 12, 1.5, '1111', 'DF');
        $pdf->SetXY($left + 3, $boxY + 2.2);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 4, 'Return to your Retirement Planning Journey', 0, 1, 'L');
        $pdf->SetX($left + 3);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->Cell(
            0,
            3.8,
            'Visit ' . JOURNEY_SUMMARY_PDF_SITE . ' to reopen your saved Journey.',
            0,
            1,
            'L',
            false,
            JOURNEY_SUMMARY_PDF_SITE_URL
        );
        $pdf->SetY($boxY + 13);
    }

    journey_summary_pdf_cleanup_temps($tempFiles);

    return $pdf;
}
