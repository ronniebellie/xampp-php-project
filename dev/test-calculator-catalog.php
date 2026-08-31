<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/calculator_catalog.php';

$failures = [];
$checks = 0;

function catalog_assert(bool $condition, string $message): void
{
    global $failures, $checks;
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
}

$catalog = rb_calculator_catalog();
$active = rb_active_calculators();
$advisor = rb_advisor_calculators();
$totals = rb_calculator_feature_totals();
$root = dirname(__DIR__);

catalog_assert(count($active) === 27, 'Expected exactly 27 active calculators.');
catalog_assert($totals['free'] === 27, 'Expected exactly 27 free calculators.');
catalog_assert($totals['premium'] === 20, 'Expected exactly 20 calculators with working Premium capability.');
catalog_assert($totals['save'] === 13, 'Expected exactly 13 calculators with working Save support.');
catalog_assert($totals['pdf'] === 8, 'Expected exactly 8 calculators with working PDF support.');
catalog_assert($totals['csv'] === 8, 'Expected exactly 8 calculators with working CSV support.');
catalog_assert($totals['ai'] === 19, 'Expected exactly 19 calculators with working AI Explain support.');
catalog_assert($totals['compare'] === 9, 'Expected exactly 9 calculators with working saved-scenario comparison.');
catalog_assert(count($advisor) === 16, 'Expected exactly 16 curated advisor calculators.');

$ids = [];
$routes = [];
$advisorIds = [];
foreach ($catalog as $key => $calculator) {
    catalog_assert($key === $calculator['id'], "Catalog key and stable ID differ for {$key}.");
    catalog_assert(!isset($ids[$calculator['id']]), "Duplicate stable ID: {$calculator['id']}.");
    catalog_assert(!isset($routes[$calculator['route']]), "Duplicate route: {$calculator['route']}.");
    $ids[$calculator['id']] = true;
    $routes[$calculator['route']] = true;

    if ($calculator['active']) {
        catalog_assert(isset(RB_CALCULATOR_MASTER_CATEGORIES[$calculator['master_category']]), "Invalid master category for {$key}.");
        $entry = $root . rtrim($calculator['route'], '/') . '/index.php';
        catalog_assert(is_file($entry), "Active route has no index.php: {$calculator['route']}.");
    }

    if ($calculator['advisor']) {
        catalog_assert($calculator['active'] === true, "Advisor calculator is inactive: {$key}.");
        catalog_assert(isset(RB_CALCULATOR_ADVISOR_CATEGORIES[$calculator['advisor_category']]), "Invalid advisor category for {$key}.");
        catalog_assert(!isset($advisorIds[$key]), "Duplicate advisor calculator: {$key}.");
        $advisorIds[$key] = true;
    } else {
        catalog_assert($calculator['advisor_category'] === null, "Excluded advisor calculator has an advisor category: {$key}.");
    }
}

$nonCalculators = [
    'estate-legacy-planning-suite',
    'retirement-app',
    'ai-budget-auditor',
    'manual-budget-beta',
];
foreach ($nonCalculators as $id) {
    catalog_assert(rb_calculator_by_id($id) === null, "Excluded legacy/non-calculator item is present: {$id}.");
}
catalog_assert(rb_calculator_by_route('/estate-planning/') === null, 'Estate & Legacy Planning Suite landing page must not be counted.');
catalog_assert(rb_calculator_by_route('/retirement-app/') === null, 'Legacy retirement-app route must not be counted.');

$groupedCount = 0;
foreach (rb_advisor_calculators_grouped() as $category => $calculators) {
    catalog_assert(isset(RB_CALCULATOR_ADVISOR_CATEGORIES[$category]), "Unknown grouped advisor category: {$category}.");
    $groupedCount += count($calculators);
}
catalog_assert($groupedCount === count($advisor), 'Grouped advisor catalog count differs from advisor catalog count.');

catalog_assert(rb_calculator_by_id('rmd-impact')['route'] === '/rmd-impact/', 'ID lookup failed.');
catalog_assert(rb_calculator_by_route('/rmd-impact')['id'] === 'rmd-impact', 'Route lookup failed without trailing slash.');

if ($failures) {
    fwrite(STDERR, "Calculator catalog validation failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Calculator catalog validation passed ({$checks} checks).\n";
echo json_encode($totals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
