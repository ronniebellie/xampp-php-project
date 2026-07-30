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

$api = (string) file_get_contents($root . '/api/generate_journey_summary_pdf.php');
$helper = (string) file_get_contents($root . '/includes/journey_summary_pdf.php');
$page = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/survivor-planning.php');
$js = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/survivor-planning-phase.js');

expectPdf('api exists', is_file($root . '/api/generate_journey_summary_pdf.php'));
expectPdf('helper exists', is_file($root . '/includes/journey_summary_pdf.php'));
expectPdf('api requires journey premium', strpos($api, 'has_journey_premium_access') !== false);
expectPdf('api does not use calculator premium gate', strpos($api, 'has_premium_access()') === false);
expectPdf('api uses TCPDF helper', strpos($api, 'journey_summary_pdf_build') !== false);
expectPdf('helper includes all six phases', strpos($helper, 'Phase 1') !== false && strpos($helper, 'Phase 6') !== false);
expectPdf('helper includes educational footer', strpos($helper, 'not financial, tax, or legal advice') !== false);
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

try {
    $pdf = journey_summary_pdf_build($fixture, 'Bob Smith');
    $raw = $pdf->Output('journey-summary.pdf', 'S');
    expectPdf('pdf binary generated', is_string($raw) && strlen($raw) > 1000);
    expectPdf('pdf header present', is_string($raw) && strncmp($raw, '%PDF', 4) === 0);
} catch (Throwable $e) {
    expectPdf('pdf binary generated', false, $e->getMessage());
    expectPdf('pdf header present', false);
}

echo "Journey summary PDF tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
