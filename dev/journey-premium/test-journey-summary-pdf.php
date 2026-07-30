<?php
/**
 * Journey summary PDF static + generation checks.
 *
 * Usage:
 *   php dev/journey-premium/test-journey-summary-pdf.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expectPdf(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

/**
 * Extract readable strings from a PDF binary (compressed streams + plain text).
 */
function journey_pdf_extract_text(string $raw): string
{
    $chunks = [$raw];
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $matches)) {
        foreach ($matches[1] as $chunk) {
            $chunk = ltrim($chunk, "\r\n");
            $dec = @gzuncompress($chunk);
            if ($dec === false) {
                $dec = @gzinflate($chunk);
            }
            if (is_string($dec) && $dec !== '') {
                $chunks[] = $dec;
            }
        }
    }
    $blob = implode("\n", $chunks);
    // Pull literal PDF text draws: [(Hello)] or (Hello)
    $text = '';
    if (preg_match_all('/\[((?:\\([^)]*\\)|[^\]])*)\]\s*TJ/s', $blob, $m)) {
        foreach ($m[1] as $part) {
            if (preg_match_all('/\\((.*?)\\)/s', $part, $mm)) {
                foreach ($mm[1] as $piece) {
                    $text .= stripcslashes($piece) . ' ';
                }
            }
        }
    }
    if (preg_match_all('/\\((.*?)\\)\\s*Tj/s', $blob, $m2)) {
        foreach ($m2[1] as $piece) {
            $text .= stripcslashes($piece) . ' ';
        }
    }
    // Fallback: visible ASCII runs
    if (strlen(trim($text)) < 40) {
        if (preg_match_all('/[\x20-\x7E]{4,}/', $blob, $m3)) {
            $text .= ' ' . implode(' ', $m3[0]);
        }
    }
    return $text;
}

$api = (string) file_get_contents($root . '/api/generate_journey_summary_pdf.php');
$helper = (string) file_get_contents($root . '/includes/journey_summary_pdf.php');
$page = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/survivor-planning.php');
$js = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/survivor-planning-phase.js');

expectPdf('api exists', is_file($root . '/api/generate_journey_summary_pdf.php'));
expectPdf('helper exists', is_file($root . '/includes/journey_summary_pdf.php'));
expectPdf('api requires journey premium', strpos($api, 'has_journey_premium_access') !== false);
expectPdf('api does not use calculator premium gate', strpos($api, 'has_premium_access()') === false);
expectPdf('api uses TCPDF helper', strpos($api, 'journey_summary_pdf_build') !== false);
expectPdf('helper disables tcpdflink branding', strpos($helper, 'tcpdflink = false') !== false);
expectPdf('helper brands journey URL', strpos($helper, 'journey.ronbelisle.com') !== false);
expectPdf('helper includes report version', strpos($helper, 'JOURNEY_SUMMARY_PDF_VERSION') !== false);
expectPdf('helper includes executive summary', strpos($helper, 'Executive Summary') !== false);
expectPdf('helper includes visual summaries', strpos($helper, 'Visual Summaries') !== false);
expectPdf('helper includes funding breakdown chart', strpos($helper, 'funding breakdown') !== false);
expectPdf('helper includes income comparison chart', strpos($helper, 'Monthly income comparison') !== false);
expectPdf('helper includes withdrawal-rate visual', strpos($helper, 'withdrawal-rate visual') !== false);
expectPdf('helper includes QR code', strpos($helper, 'write2DBarcode') !== false && strpos($helper, 'QRCODE') !== false);
expectPdf('helper includes all six phases', strpos($helper, 'Phase 1') !== false && strpos($helper, 'Phase 6') !== false);
expectPdf('helper includes educational footer', strpos($helper, 'not financial, tax, or legal advice') !== false);
expectPdf('helper sets PDF metadata title', strpos($helper, 'Retirement Planning Journey Summary') !== false);
expectPdf('phase6 page has download button', strpos($page, 'Download My Retirement Plan (PDF)') !== false);
expectPdf('download button is premium-only', strpos($page, 'id="downloadJourneyPdfBtn"') !== false && strpos($page, 'data-journey-premium-only') !== false);
expectPdf('js wires pdf download', strpos($js, 'downloadJourneyPdf') !== false && strpos($js, 'generate_journey_summary_pdf.php') !== false);
expectPdf('js hides pdf for non-premium', strpos($js, 'setPdfExportVisibility') !== false);

require_once $root . '/vendor/autoload.php';
require_once $root . '/includes/journey_summary_pdf.php';

$fixture = [
    'spending-goals' => true,
    'social-security' => true,
    'build-your-plan' => true,
    'stress-test' => true,
    'tax-strategy' => true,
    'survivor-planning' => true,
    'records' => [
        'spending-goals' => [
            'result' => [
                'dataForLaterPhases' => [
                    'monthlyRetirementSpendingTarget' => 9000,
                    'annualRetirementSpendingTarget' => 108000,
                    'monthlyOtherRegularRetirementIncome' => 250,
                ],
            ],
        ],
        'social-security' => [
            'claimAge' => 66,
            'estimatedMonthlyBenefit' => 3500,
            'benefitAtFra' => 3500,
            'decisionStatus' => 'provisional',
            'lastSavedPlanning' => [
                'claimAge' => 66,
                'estimatedMonthlyBenefit' => 3500,
                'benefitAtFra' => 3500,
                'decisionStatus' => 'provisional',
            ],
        ],
        'build-your-plan' => [
            'monthlyRetirementSpendingGoal' => 9000,
            'monthlySocialSecurityAssumption' => 3500,
            'monthlyOtherDependableIncome' => 250,
            'monthlyNeededFromRetirementSavings' => 5250,
            'annualNeededFromRetirementSavings' => 63000,
            'retirementSavingsBalance' => 1400000,
            'impliedInitialWithdrawalRate' => 0.045,
            'baseCaseAssessment' => 'close',
        ],
        'stress-test' => [
            'overallResilienceLabel' => 'Sensitive to one or more risks',
            'nextAdjustmentLabel' => 'Temporarily reduce spending after a market decline',
            'pressureSentence' => 'Weaker growth created strain in the tests.',
        ],
        'tax-strategy' => [
            'nextPriorityLabel' => 'Keep the current approach and review annually',
            'result' => [
                'mainIssueStatement' => 'Taxes may require larger withdrawals than your spending goal alone suggests.',
            ],
        ],
        'survivor-planning' => [
            'nextPriorityLabel' => 'Keep the current approach and review it annually',
            'result' => [
                'issueTitles' => ['No single issue stands out strongly from these answers.'],
            ],
        ],
    ],
];

$outPath = $root . '/dev/journey-premium/fixtures/journey-summary-enhanced-sample.pdf';
@mkdir(dirname($outPath), 0775, true);
$raw = '';
$pageCount = 0;
try {
    $pdf = journey_summary_pdf_build($fixture, 'Bob Smith');
    $pageCount = (int) $pdf->getNumPages();
    $raw = $pdf->Output('journey-summary.pdf', 'S');
    file_put_contents($outPath, $raw);
    if ($pageCount <= 0 && is_string($raw)) {
        $pageCount = preg_match_all('/\/Type\s*\/Page[^s]/', $raw);
    }
    expectPdf('pdf binary generated', is_string($raw) && strlen($raw) > 2000);
    expectPdf('pdf header present', is_string($raw) && strncmp($raw, '%PDF', 4) === 0);
} catch (Throwable $e) {
    expectPdf('pdf binary generated', false, $e->getMessage());
    expectPdf('pdf header present', false);
}

expectPdf('pdf has multiple pages', $pageCount >= 2, 'pages=' . $pageCount);
expectPdf('pdf has no empty trailing page risk', $pageCount > 0 && $pageCount <= 6, 'pages=' . $pageCount);

$text = $raw !== '' ? journey_pdf_extract_text($raw) : '';
if ($text === '' && is_file($outPath) && trim((string) shell_exec('which pdftotext 2>/dev/null')) !== '') {
    $text = (string) shell_exec('pdftotext -layout ' . escapeshellarg($outPath) . ' - 2>/dev/null');
}

$today = date('F j, Y');
expectPdf('text has journey URL', stripos($text, 'journey.ronbelisle.com') !== false);
expectPdf('text has display name', stripos($text, 'Bob Smith') !== false);
expectPdf('text has generation date', stripos($text, $today) !== false || stripos($text, date('F')) !== false);
expectPdf('text has executive summary', stripos($text, 'Executive Summary') !== false);
expectPdf('text has report title', stripos($text, 'Initial Retirement Planning Summary') !== false);
expectPdf('text has phase 1', stripos($text, 'Phase 1') !== false);
expectPdf('text has phase 2', stripos($text, 'Phase 2') !== false);
expectPdf('text has phase 3', stripos($text, 'Phase 3') !== false);
expectPdf('text has phase 4', stripos($text, 'Phase 4') !== false);
expectPdf('text has phase 5', stripos($text, 'Phase 5') !== false);
expectPdf('text has phase 6', stripos($text, 'Phase 6') !== false);
expectPdf('text has spending goal value', strpos($text, '9,000') !== false || strpos($text, '$9000') !== false || stripos($text, '9000') !== false);
expectPdf('text has visual summary section', stripos($text, 'Visual Summar') !== false || stripos($text, 'funding breakdown') !== false);
expectPdf('text has withdrawal measure disclaimer', stripos($text, 'not a guarantee') !== false);
expectPdf('text has QR return label', stripos($text, 'Return to your Retirement Planning Journey') !== false);
expectPdf('text has report version', stripos($text, 'Version 1.0') !== false || stripos($text, 'Journey Report Version') !== false);
expectPdf('no visible TCPDF branding text', stripos($text, 'Powered by TCPDF') === false);

// Metadata markers in PDF binary
expectPdf('metadata title present', strpos($raw, 'Retirement Planning Journey Summary') !== false);
expectPdf('metadata author present', strpos($raw, 'Retirement Planning Journey') !== false);
expectPdf('metadata keywords/site present', strpos($raw, 'journey.ronbelisle.com') !== false);

// Chart helpers produce files when GD is available
if (extension_loaded('gd')) {
    $donut = journey_summary_pdf_chart_donut([
        ['Social Security', 3500],
        ['Other', 250],
        ['Investments', 5250],
    ]);
    expectPdf('donut chart generated', is_string($donut) && is_file((string) $donut));
    if (is_string($donut) && is_file($donut)) {
        @unlink($donut);
    }
    $bars = journey_summary_pdf_chart_bars([
        ['Spending', 9000],
        ['Dependable', 3750],
        ['Investments', 5250],
    ]);
    expectPdf('bar chart generated', is_string($bars) && is_file((string) $bars));
    if (is_string($bars) && is_file($bars)) {
        @unlink($bars);
    }
    $rate = journey_summary_pdf_chart_rate(0.045, 'Looks close and may need adjustment');
    expectPdf('rate chart generated', is_string($rate) && is_file((string) $rate));
    if (is_string($rate) && is_file($rate)) {
        @unlink($rate);
    }
} else {
    expectPdf('gd extension available for charts', false, 'GD missing — charts will fall back to text');
}

echo "Journey summary PDF tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
if ($outPath !== '' && is_file($outPath)) {
    echo 'Sample PDF: ' . $outPath . ' (' . filesize($outPath) . " bytes)\n";
}
exit(count($failed) === 0 ? 0 : 1);
