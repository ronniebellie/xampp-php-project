<?php
declare(strict_types=1);

$root = dirname(__DIR__);
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

$api = $read('api/load_scenarios.php');
$expect(strpos($api, "'id' => \$row['id']") !== false, 'Scenario ID mapping changed.');
$expect(strpos($api, "'scenario_name' => \$row['scenario_name']") !== false, 'Canonical scenario_name field is missing.');
$expect(strpos($api, "'name' => \$row['scenario_name']") !== false, 'Compatibility name alias is missing.');
$expect(strpos($api, "'data' => json_decode(\$row['scenario_data'], true)") !== false, 'Decoded scenario data mapping changed.');
$expect(substr_count($api, "\$owner['id']") === 1, 'Load API owner binding changed unexpectedly.');
$expect(strpos($api, 'FROM scenarios WHERE user_id = ? AND calculator_type = ?') !== false, 'Consumer scenario query is not owner-scoped.');
$expect(strpos($api, 'FROM calcforadvisors_scenarios WHERE subscriber_id = ? AND calculator_type = ?') !== false, 'Advisor scenario query is not owner-scoped.');
$expect(strpos($api, '$owner = get_scenario_owner();') !== false, 'Scenario owner is not derived from the authenticated session.');
$expect(!preg_match('/\$_(?:GET|POST|REQUEST)\s*\[\s*[\'\"](?:owner|owner_id|user_id|subscriber_id)[\'\"]\s*\]/', $api), 'Load API trusts a browser-supplied owner identifier.');

$fixture = ['id' => 42, 'scenario_name' => 'Advisor plan', 'scenario_data' => '{"age":67,"balance":500000}'];
$mapped = [
    'id' => $fixture['id'],
    'scenario_name' => $fixture['scenario_name'],
    'name' => $fixture['scenario_name'],
    'data' => json_decode($fixture['scenario_data'], true),
];
$expect($mapped['id'] === 42, 'Compatibility mapping changed the scenario ID.');
$expect($mapped['scenario_name'] === 'Advisor plan', 'Canonical scenario name changed.');
$expect($mapped['name'] === $mapped['scenario_name'], 'Compatibility alias differs from scenario_name.');
$expect($mapped['data'] === ['age' => 67, 'balance' => 500000], 'Decoded scenario data changed.');

$modal = $read('js/compare-scenarios-modal.js');
$expect(strpos($modal, 'scenario.scenario_name || scenario.name') !== false, 'Shared comparison modal does not prefer scenario_name.');
$expect(strpos($modal, "'Untitled scenario'") !== false, 'Shared comparison modal lacks a nonempty fallback.');
$expect(strpos($modal, 'escapeHtml(scenarioDisplayName(s))') !== false, 'Shared comparison modal does not HTML-escape normalized names.');

$clients = [
    'social-security-claiming-analyzer/calculator.js',
    'ss-early-exit/calculator.js',
    'ss-survivor-impact/calculator.js',
    'ss-gap/calculator.js',
    'required-vs-desired/index.php',
    'roth-conv/calculator.js',
    'rmd-impact/calculator.js',
    'plan-success/calculator.js',
    'survivor-gap/calculator.js',
    'managed-vs-vanguard/calculator.js',
    'vanguard-pas-vs-target-date/calculator.js',
];

$legacySavedScenarioExpressions = [
    '${s.name}',
    '${scenario.name}',
    "' + s.name + '",
    "' + del.name + '",
    "' + data.scenarios[index].name + '",
    'selected[0].name',
    'selected[1].name',
    'selected[2].name',
    'showComparisonSS(s1.name, s2.name',
    'showScenarioComparison(s1.name, s2.name',
    'showRothComparison(s1.name, s2.name',
    'showMVComparison(s1.name, s2.name',
];

foreach ($clients as $path) {
    $source = $read($path);
    $expect(strpos($source, 'function scenarioDisplayName(') !== false, "{$path} lacks scenario name normalization.");
    $expect(strpos($source, 'scenario.scenario_name || scenario.name') !== false, "{$path} does not prefer canonical scenario_name.");
    $expect(strpos($source, "'Untitled scenario'") !== false, "{$path} lacks the safe scenario-name fallback.");
    foreach ($legacySavedScenarioExpressions as $expression) {
        // This calculator also has internal strategy objects whose canonical field is name.
        if ($path === 'ss-survivor-impact/calculator.js' && $expression === "' + s.name + '") continue;
        $expect(strpos($source, $expression) === false, "{$path} still contains unnormalized saved-scenario display code: {$expression}");
    }
}

$retirementPlan = $read('retirement-plan/calculator.js');
$expect(strpos($retirementPlan, 'scenario.scenario_name') !== false, 'Retirement Plan reference implementation no longer uses scenario_name.');

if ($failures) {
    fwrite(STDERR, "Scenario name compatibility tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Scenario name compatibility tests passed ({$checks} checks).\n";
