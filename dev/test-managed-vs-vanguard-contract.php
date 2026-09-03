<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$expect = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$read = static function (string $path) use ($root): string {
    $source = file_get_contents($root . '/' . $path);
    if ($source === false) {
        throw new RuntimeException("Could not read {$path}");
    }
    return $source;
};

$index = $read('managed-vs-vanguard/index.php');
foreach (['id="contributionAmount"', 'id="contributionFrequency"', 'All projections are pre-tax', 'projection.js'] as $required) {
    $expect(strpos($index, $required) !== false, "Calculator page is missing {$required}.");
}

$projection = $read('managed-vs-vanguard/projection.js');
foreach (['projectPortfolio', 'monthlyReturn', 'monthlyFeeRate', 'End-of-period deposit', 'cumulativeContributions', 'endingBalance'] as $required) {
    $expect(strpos($projection, $required) !== false, "Projection engine is missing {$required}.");
}
foreach (['taxRate', 'afterTax', 'taxable', 'roth', 'accountType'] as $forbidden) {
    $expect(stripos($projection, $forbidden) === false, "Projection engine must not introduce {$forbidden} modeling.");
}

$calculator = $read('managed-vs-vanguard/calculator.js');
foreach (['normalizeScenarioInputs', "? 0", "'monthly'", 'projectScenario', 'contributionAmount', 'contributionFrequency', 'cumulativeContributions', 'All values are pre-tax'] as $required) {
    $expect(strpos($calculator, $required) !== false, "Calculator client is missing {$required}.");
}
$expect(strpos($calculator, 'calculatePortfolio(') === false, 'Calculator client still has the retired separate projection path.');

$pdf = $read('api/generate_mv_pdf.php');
foreach (['contributionAmount', 'contributionFrequency', 'cumulativeContributions', 'totalInvestedCapital', 'Managed Ending Balance', 'pre-tax'] as $required) {
    $expect(strpos($pdf, $required) !== false, "PDF contract is missing {$required}.");
}

$csv = $read('api/export_mv_csv.php');
foreach (['Contributions This Year', 'Cumulative Contributions', 'Managed Ending Balance', 'Vanguard Ending Balance'] as $required) {
    $expect(strpos($csv, $required) !== false, "CSV contract is missing {$required}.");
}

if ($failures) {
    fwrite(STDERR, "Managed vs Vanguard contract tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Managed vs Vanguard contract tests passed ({$checks} checks).\n";
