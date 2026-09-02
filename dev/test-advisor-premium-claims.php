<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/calculator_catalog.php';

$failures = [];
$checks = 0;
$expect = static function (bool $ok, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$ok) $failures[] = $message;
};
$read = static function (string $path) use ($root): string {
    $source = file_get_contents($root . '/' . $path);
    if ($source === false) throw new RuntimeException("Could not read {$path}");
    return $source;
};

$required = $read('required-vs-desired/index.php');
foreach (['saveScenarioBtn', 'loadScenarioBtn', 'explainResultsBtnInResults'] as $id) {
    $expect(strpos($required, 'id="' . $id . '"') !== false, "Required vs Desired lost {$id}.");
}
foreach (['compareScenariosBtn', 'downloadPdfBtn', 'downloadCsvBtn'] as $id) {
    $expect(strpos($required, 'id="' . $id . '"') === false, "Required vs Desired still exposes {$id}.");
}
foreach (['function compareScenarios()', 'function downloadPDF()', 'function downloadCSV()', 'export PDFs and CSVs', 'save and compare scenarios'] as $claim) {
    $expect(strpos($required, $claim) === false, "Required vs Desired still contains unsupported behavior or copy: {$claim}");
}
$expect(substr_count($required, 'Premium lets you save and reload scenarios and receive an AI-generated plain-language explanation of your results.') === 2, 'Required vs Desired truthful Premium copy is missing from a Premium or upsell state.');

$gapIndex = $read('ss-gap/index.php');
$gapJs = $read('ss-gap/calculator.js');
foreach (['saveScenarioBtn', 'loadScenarioBtn', 'compareScenariosBtn', 'explainResultsBtnInResults'] as $id) {
    $expect(strpos($gapIndex, 'id="' . $id . '"') !== false, "Social Security Gap lost {$id}.");
}
foreach (['downloadPdfBtn', 'downloadCsvBtn'] as $id) {
    $expect(strpos($gapIndex, 'id="' . $id . '"') === false, "Social Security Gap still exposes {$id}.");
    $expect(strpos($gapJs, "getElementById('{$id}')") === false, "Social Security Gap still wires {$id}.");
}
foreach (['function downloadPDF()', 'function downloadCSV()', 'export PDF and CSV reports'] as $claim) {
    $expect(strpos($gapIndex . $gapJs, $claim) === false, "Social Security Gap still contains unsupported export behavior or copy: {$claim}");
}
$expect(strpos($gapJs, 'function compareScenarios()') !== false, 'Social Security Gap Compare handler was removed.');
$expect(strpos($gapJs, "CompareScenariosModal.open(SSG_API_BASE, 'ss-gap'") !== false, 'Social Security Gap Compare no longer opens the shared comparison modal.');
$expect(substr_count($gapIndex, 'Premium lets you save, reload, and compare spending-gap scenarios and receive an AI-generated explanation of your results.') === 2, 'Social Security Gap truthful Premium copy is missing from a Premium or upsell state.');

$plan = $read('plan-success/index.php');
foreach (['saveScenarioBtn', 'loadScenarioBtn', 'explainResultsBtnInResults'] as $id) {
    $expect(strpos($plan, 'id="' . $id . '"') !== false, "Plan Success lost {$id}.");
}
$expect(strpos($plan, 'Upgrade to Premium to save and reload scenarios and get AI-generated plain-language explanations of your results.') !== false, 'Plan Success truthful upsell copy is missing.');
$expect(strpos($plan, 'save and compare scenarios, export PDF and CSV') === false, 'Plan Success still makes unsupported Compare/PDF/CSV claims.');

$survivor = $read('survivor-gap/index.php');
foreach (['saveScenarioBtn', 'loadScenarioBtn', 'explainResultsBtnInResults'] as $id) {
    $expect(strpos($survivor, 'id="' . $id . '"') !== false, "Survivor Gap lost {$id}.");
}
$expect(strpos($survivor, 'save and reload survivor gap scenarios') !== false, 'Survivor Gap truthful upsell copy is missing.');
$expect(strpos($survivor, 'save and compare survivor gap scenarios') === false, 'Survivor Gap still claims saved-scenario Compare.');

$expectedFlags = [
    'required-vs-desired-spending' => ['save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true],
    'social-security-spending-gap' => ['save' => true, 'compare' => true, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true],
    'plan-success-monte-carlo' => ['save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true],
    'survivor-gap' => ['save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true],
];
foreach ($expectedFlags as $id => $flags) {
    $calculator = rb_calculator_by_id($id);
    $expect($calculator !== null, "Catalog entry missing: {$id}.");
    if ($calculator === null) continue;
    foreach ($flags as $feature => $expected) {
        $expect($calculator[$feature] === $expected, "Catalog flag changed unexpectedly: {$id}.{$feature}");
    }
}

if ($failures) {
    fwrite(STDERR, "Advisor Premium claim tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Advisor Premium claim tests passed ({$checks} checks).\n";
