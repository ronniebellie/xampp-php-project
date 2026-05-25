<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once __DIR__ . '/../includes/db_config.php';

require_once __DIR__ . '/../includes/has_premium_access.php';
if (!has_premium_access()) {
    header('Content-Type: application/json');
    http_response_code(403);
    die(json_encode(['error' => 'Premium subscription required']));
}

$data = json_decode(file_get_contents('php://input'), true);
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
        fputcsv($out, [
            $scenario,
            $r['age'],
            $r['year'],
            number_format($r['conversion'], 2),
            number_format($r['rmd'], 2),
            number_format($r['totalWithdrawal'] ?? 0, 2),
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
            number_format($r['rothBalance'], 2)
        ]);
    }
}

ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Roth_Conversion_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');

fputcsv($out, ['Roth Conversion Calculator — All-In Tax Export']);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, ['Nominal lifetime tax savings (with conversion)', number_format($data['taxSavings'] ?? 0, 2)]);
if ($hasDiscount) {
    fputcsv($out, ['Discounted lifetime tax savings', number_format($data['discountedTaxSavings'] ?? 0, 2)]);
    fputcsv($out, ['Discount rate', ((float)($data['discountRate'] ?? 0) * 100) . '%']);
}
fputcsv($out, ['Break-even age (nominal)', $data['breakEvenAge'] ?? '']);
if ($hasDiscount) {
    fputcsv($out, ['Break-even age (discounted)', $data['breakEvenAgeDiscounted'] ?? '']);
}
if ($includeIrmaa) {
    fputcsv($out, ['Lifetime IRMAA — no conversion', number_format(sumField($withoutRows, 'irmaa'), 2)]);
    fputcsv($out, ['Lifetime IRMAA — with conversion', number_format(sumField($withRows, 'irmaa'), 2)]);
    fputcsv($out, ['IRMAA reduction', number_format($data['irmaaReduction'] ?? 0, 2)]);
}
if ($includeNiit) {
    fputcsv($out, ['Lifetime NIIT — no conversion', number_format(sumField($withoutRows, 'niit'), 2)]);
    fputcsv($out, ['Lifetime NIIT — with conversion', number_format(sumField($withRows, 'niit'), 2)]);
    fputcsv($out, ['NIIT reduction', number_format($data['niitReduction'] ?? 0, 2)]);
}
fputcsv($out, []);

$header = [
    'Scenario', 'Age', 'Year', 'Conversion', 'RMD', 'Portfolio Withdrawal',
    'Total Income', 'MAGI', 'Taxable Income', 'Federal Tax', 'IRMAA', 'NIIT',
    'All-In Tax', 'Cumulative All-In Tax', 'Cumulative All-In Tax (PV)',
    'Net Cash', 'Traditional IRA', 'Roth IRA'
];
fputcsv($out, $header);

writeScenarioRows($out, 'With Conversion', $withRows);
writeScenarioRows($out, 'No Conversion', $withoutRows);

fclose($out);
exit;
