<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/report_csv.php';
rb_api_errors();
require_once __DIR__ . '/../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = rb_read_api_json(2097152);
try { $data=rb_pdf_data($data); } catch(Throwable $e) { rb_api_error(400, 'Invalid or oversized CSV data'); }
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
if (!$data || !isset($data['withConversion']['yearlyData']) || !is_array($data['withConversion']['yearlyData'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing data']));
}

$withRows = $data['withConversion']['yearlyData'];
$withoutRows = isset($data['withoutConversion']['yearlyData']) && is_array($data['withoutConversion']['yearlyData'])
    ? $data['withoutConversion']['yearlyData']
    : [];

$includeIrmaa = !empty($data['includeIrmaa']) && $data['includeIrmaa'] !== 'false' && $data['includeIrmaa'] !== '0';
$includeNiit = !empty($data['includeNiit']) && $data['includeNiit'] !== 'false' && $data['includeNiit'] !== '0';
$hasDiscount = isset($data['discountRate']) && (float)$data['discountRate'] > 0;

function sumField(array $rows, string $field): float {
    $sum = 0.0;
    foreach ($rows as $r) {
        $sum += (float)($r[$field] ?? 0);
    }
    return $sum;
}

function writeScenarioRows($out, string $scenario, array $rows): void {
    foreach ($rows as $r) {
        rb_csv_row($out, [
            $scenario,
            $r['age'],
            $r['year'],
            $r['filingStatus'] ?? '',
            number_format($r['conversion'], 2),
            number_format($r['rmd'], 2),
            number_format($r['socialSecurity'] ?? 0, 2),
            number_format($r['taxableSocialSecurity'] ?? 0, 2),
            number_format($r['totalWithdrawal'] ?? 0, 2),
            number_format($r['taxableWithdrawal'] ?? 0, 2),
            number_format($r['realizedCapitalGain'] ?? 0, 2),
            number_format($r['income'], 2),
            number_format($r['magi'] ?? $r['income'], 2),
            number_format($r['taxableIncome'], 2),
            number_format($r['federalTax'], 2),
            number_format($r['irmaa'] ?? 0, 2),
            number_format($r['niit'] ?? 0, 2),
            number_format($r['allInTax'] ?? $r['federalTax'], 2),
            number_format($r['totalTaxesPaid'], 2),
            number_format($r['totalDiscountedTaxesPaid'] ?? $r['totalTaxesPaid'], 2),
            number_format($r['netCash'] ?? 0, 2),
            number_format($r['traditionalBalance'], 2),
            number_format($r['rothBalance'], 2),
            number_format($r['taxableBalance'] ?? 0, 2),
            number_format($r['requestedSpending'] ?? 0, 2), number_format($r['spendingShortfall'] ?? 0, 2),
            number_format($r['taxesPaid'] ?? 0, 2), number_format($r['taxShortfall'] ?? 0, 2)
        ]);
    }
}

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Roth_Conversion_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
rb_csv_context($out, 'Roth Conversion', $data);

rb_csv_row($out, ['Roth Conversion Calculator — All-In Tax Export']);
rb_csv_row($out, ['Generated', date('Y-m-d H:i:s')]);
rb_csv_row($out, ['Nominal lifetime tax savings (with conversion)', number_format($data['taxSavings'] ?? 0, 2)]);
if ($hasDiscount) {
    rb_csv_row($out, ['Discounted lifetime tax savings', number_format($data['discountedTaxSavings'] ?? 0, 2)]);
    rb_csv_row($out, ['Discount rate', ((float)($data['discountRate'] ?? 0) * 100) . '%']);
}
rb_csv_row($out, ['Break-even age (nominal)', $data['breakEvenAge'] ?? '']);
if ($hasDiscount) {
    rb_csv_row($out, ['Break-even age (discounted)', $data['breakEvenAgeDiscounted'] ?? '']);
}
if ($includeIrmaa) {
    rb_csv_row($out, ['Lifetime IRMAA assessed — no conversion', number_format(sumField($withoutRows, 'irmaa'), 2)]);
    rb_csv_row($out, ['Lifetime IRMAA assessed — with conversion', number_format(sumField($withRows, 'irmaa'), 2)]);
    rb_csv_row($out, ['IRMAA paid reduction', number_format($data['irmaaReduction'] ?? 0, 2)]);
}
if ($includeNiit) {
    rb_csv_row($out, ['Lifetime NIIT assessed — no conversion', number_format(sumField($withoutRows, 'niit'), 2)]);
    rb_csv_row($out, ['Lifetime NIIT assessed — with conversion', number_format(sumField($withRows, 'niit'), 2)]);
    rb_csv_row($out, ['NIIT paid reduction', number_format($data['niitReduction'] ?? 0, 2)]);
}
rb_csv_row($out, []);

$header = [
    'Scenario', 'Age', 'Year', 'Filing Status', 'Conversion', 'RMD', 'Social Security', 'Taxable Social Security', 'Portfolio Withdrawal', 'Taxable Brokerage Withdrawal', 'Realized Capital Gain',
    'Total Income', 'MAGI', 'Taxable Income', 'Federal Tax', 'IRMAA', 'NIIT',
    'All-In Tax', 'Cumulative All-In Tax', 'Cumulative All-In Tax (PV)',
    'Funded Spending', 'Traditional IRA', 'Roth IRA', 'Taxable Brokerage', 'Requested Spending', 'Spending Shortfall', 'Taxes Paid', 'Unpaid Tax'
];
rb_csv_row($out, $header);

writeScenarioRows($out, 'With Conversion', $withRows);
writeScenarioRows($out, 'No Conversion', $withoutRows);

fclose($out);
exit;
