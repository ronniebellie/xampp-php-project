<?php
/**
 * Build a Retirement Planning Journey summary PDF (TCPDF).
 */

declare(strict_types=1);

if (defined('RB_JOURNEY_SUMMARY_PDF_LOADED')) {
    return;
}
define('RB_JOURNEY_SUMMARY_PDF_LOADED', 1);

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
 * @param array<string,mixed>|null $record
 * @return array<string,mixed>
 */
function journey_summary_pdf_record(?array $record): array
{
    return is_array($record) ? $record : [];
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

    return [
        'phase1' => [
            'monthlySpending' => $phase1Data['monthlyRetirementSpendingTarget']
                ?? $phase1['monthlyTarget']
                ?? null,
            'otherIncome' => $phase1Data['monthlyOtherRegularRetirementIncome'] ?? 0,
            'annualSpending' => $phase1Data['annualRetirementSpendingTarget'] ?? null,
        ],
        'phase2' => [
            'claimAge' => $phase2Claim,
            'estimatedMonthlyBenefit' => $phase2Benefit,
            'benefitAtFra' => $phase2Fra,
            'decisionStatus' => $phase2Status,
        ],
        'phase3' => [
            'monthlySpendingGoal' => $phase3['monthlyRetirementSpendingGoal'] ?? null,
            'monthlySocialSecurity' => $phase3['monthlySocialSecurityAssumption'] ?? null,
            'monthlyOtherIncome' => $phase3['monthlyOtherDependableIncome'] ?? null,
            'monthlyFromSavings' => $phase3['monthlyNeededFromRetirementSavings'] ?? null,
            'annualFromSavings' => $phase3['annualNeededFromRetirementSavings'] ?? null,
            'savingsBalance' => $phase3['retirementSavingsBalance'] ?? null,
            'withdrawalRate' => $phase3['impliedInitialWithdrawalRate'] ?? null,
            'assessment' => $assessmentLabel,
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
            'spending' => $phase1Data['monthlyRetirementSpendingTarget']
                ?? $phase3['monthlyRetirementSpendingGoal']
                ?? null,
            'socialSecurity' => $phase3['monthlySocialSecurityAssumption'] ?? $phase2Benefit,
            'incomePlan' => $assessmentLabel,
            'resilience' => $phase4['overallResilienceLabel'] ?? '—',
            'taxPriority' => $phase5['nextPriorityLabel'] ?? '—',
            'survivorPriority' => $phase6['nextPriorityLabel'] ?? '—',
        ],
    ];
}

/**
 * @param TCPDF $pdf
 */
function journey_summary_pdf_section_title($pdf, string $title): void
{
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(29, 78, 216); // Journey accent
    $pdf->Cell(0, 8, $title, 0, 1, 'L');
    $pdf->SetTextColor(17, 24, 39);
    $pdf->SetDrawColor(216, 224, 234);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
}

/**
 * @param TCPDF $pdf
 * @param array<int, array{0:string,1:string}> $rows
 */
function journey_summary_pdf_kv_rows($pdf, array $rows): void
{
    foreach ($rows as $row) {
        $label = (string) $row[0];
        $value = (string) $row[1];
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->Cell(62, 6, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->MultiCell(0, 6, $value, 0, 'L');
    }
}

/**
 * @param array<string,mixed> $progress
 */
function journey_summary_pdf_build(array $progress, ?string $displayName = null): TCPDF
{
    $data = journey_summary_pdf_extract($progress);
    $generated = date('F j, Y');

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Retirement Planning Journey');
    $pdf->SetAuthor('Retirement Planning Journey');
    $pdf->SetTitle('Retirement Planning Journey Summary');
    $pdf->SetSubject('Completed Journey summary');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 16, 15);
    $pdf->SetAutoPageBreak(true, 28);
    $pdf->SetFooterMargin(0);
    $pdf->AddPage();

    // Header band
    $pdf->SetFillColor(29, 78, 216);
    $pdf->Rect(0, 0, 210, 36, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetY(10);
    $pdf->Cell(0, 8, 'Retirement Planning Journey', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Your completed plan summary', 0, 1, 'C');
    $pdf->SetY(42);
    $pdf->SetTextColor(17, 24, 39);

    if ($displayName !== null && trim($displayName) !== '') {
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->Cell(0, 6, 'Prepared for ' . trim($displayName), 0, 1, 'L');
        $pdf->SetTextColor(17, 24, 39);
    }
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(82, 96, 113);
    $pdf->Cell(0, 6, 'Generated ' . $generated, 0, 1, 'L');
    $pdf->SetTextColor(17, 24, 39);
    $pdf->Ln(2);

    journey_summary_pdf_section_title($pdf, 'Overall Journey Summary');
    journey_summary_pdf_kv_rows($pdf, [
        ['Retirement spending goal', journey_summary_pdf_money($data['overview']['spending']) . ' / month'],
        ['Social Security assumption', journey_summary_pdf_money($data['overview']['socialSecurity']) . ' / month'],
        ['Retirement income plan', (string) $data['overview']['incomePlan']],
        ['Resilience review', (string) $data['overview']['resilience']],
        ['Tax-planning priority', (string) $data['overview']['taxPriority']],
        ['Survivor-planning priority', (string) $data['overview']['survivorPriority']],
    ]);

    journey_summary_pdf_section_title($pdf, 'Phase 1 — Spending & Goals');
    journey_summary_pdf_kv_rows($pdf, [
        ['Monthly spending goal', journey_summary_pdf_money($data['phase1']['monthlySpending']) . ' / month'],
        ['Annual spending goal', journey_summary_pdf_money(
            $data['phase1']['annualSpending'] ?? (
                is_numeric($data['phase1']['monthlySpending'])
                    ? ((float) $data['phase1']['monthlySpending'] * 12)
                    : null
            )
        )],
        ['Other regular income', journey_summary_pdf_money($data['phase1']['otherIncome']) . ' / month'],
    ]);

    journey_summary_pdf_section_title($pdf, 'Phase 2 — Social Security');
    $claimAge = $data['phase2']['claimAge'];
    journey_summary_pdf_kv_rows($pdf, [
        ['Claiming age assumption', $claimAge !== null && $claimAge !== '' ? (string) $claimAge : '—'],
        ['Estimated monthly benefit', journey_summary_pdf_money($data['phase2']['estimatedMonthlyBenefit']) . ' / month'],
        ['Benefit at FRA (reference)', journey_summary_pdf_money($data['phase2']['benefitAtFra']) . ' / month'],
        ['Decision status', $data['phase2']['decisionStatus'] !== ''
            ? ucwords(str_replace('-', ' ', (string) $data['phase2']['decisionStatus']))
            : '—'],
    ]);

    journey_summary_pdf_section_title($pdf, 'Phase 3 — Build Your Plan');
    journey_summary_pdf_kv_rows($pdf, [
        ['Monthly spending goal', journey_summary_pdf_money($data['phase3']['monthlySpendingGoal']) . ' / month'],
        ['Social Security in the plan', journey_summary_pdf_money($data['phase3']['monthlySocialSecurity']) . ' / month'],
        ['Other dependable income', journey_summary_pdf_money($data['phase3']['monthlyOtherIncome']) . ' / month'],
        ['Needed from investments', journey_summary_pdf_money($data['phase3']['monthlyFromSavings']) . ' / month'],
        ['Annual investment withdrawals', journey_summary_pdf_money($data['phase3']['annualFromSavings']) . ' / year'],
        ['Retirement savings balance', journey_summary_pdf_money($data['phase3']['savingsBalance'])],
        ['Implied initial withdrawal rate', journey_summary_pdf_pct_from_decimal($data['phase3']['withdrawalRate'])],
        ['Base-case assessment', (string) $data['phase3']['assessment']],
    ]);

    journey_summary_pdf_section_title($pdf, 'Phase 4 — Stress Test');
    journey_summary_pdf_kv_rows($pdf, [
        ['Stress-test result', (string) $data['phase4']['resilience']],
        ['Selected strategy', (string) $data['phase4']['strategy']],
    ]);
    if (trim((string) $data['phase4']['pressure']) !== '') {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(0, 5, (string) $data['phase4']['pressure'], 0, 'L');
        $pdf->SetTextColor(17, 24, 39);
    }

    journey_summary_pdf_section_title($pdf, 'Phase 5 — Tax Strategy');
    journey_summary_pdf_kv_rows($pdf, [
        ['Tax-planning priority', (string) $data['phase5']['priority']],
    ]);
    if (trim((string) $data['phase5']['statement']) !== '') {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(82, 96, 113);
        $pdf->MultiCell(0, 5, (string) $data['phase5']['statement'], 0, 'L');
        $pdf->SetTextColor(17, 24, 39);
    }

    journey_summary_pdf_section_title($pdf, 'Phase 6 — Survivor Planning');
    journey_summary_pdf_kv_rows($pdf, [
        ['Survivor-planning priority', (string) $data['phase6']['priority']],
    ]);
    $titles = $data['phase6']['titles'];
    if (is_array($titles) && $titles !== []) {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(82, 96, 113);
        foreach ($titles as $title) {
            if (!is_string($title) || trim($title) === '') {
                continue;
            }
            $pdf->MultiCell(0, 5, '• ' . $title, 0, 'L');
        }
        $pdf->SetTextColor(17, 24, 39);
    }

    // Footer near bottom of current page (avoid creating an extra TCPDF page)
    $pdf->Ln(6);
    $y = $pdf->GetY();
    if ($y > 250) {
        $pdf->AddPage();
    }
    $pdf->SetY(max($pdf->GetY(), 255));
    $pdf->SetDrawColor(216, 224, 234);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(82, 96, 113);
    $pdf->MultiCell(
        0,
        4,
        "Retirement Planning Journey\nGenerated {$generated}\nFor educational planning purposes only. This is not financial, tax, or legal advice.",
        0,
        'C'
    );

    return $pdf;
}
