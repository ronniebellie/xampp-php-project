<?php
/**
 * Journey Premium Milestone 5 / R1 — P3 sync module static checks.
 *
 * Usage:
 *   php dev/journey-premium/test-milestone5-p3.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expect3(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$syncPath = $root . '/journey.ronbelisle.com/assets/js/journey-sync.js';
$chromePath = $root . '/journey.ronbelisle.com/assets/js/journey-auth-chrome.js';
$headerPath = $root . '/journey.ronbelisle.com/includes/site-header.php';

expect3('sync module exists', is_file($syncPath));
expect3('chrome module exists', is_file($chromePath));
expect3('site header exists', is_file($headerPath));

$sync = is_file($syncPath) ? (string) file_get_contents($syncPath) : '';
$chrome = is_file($chromePath) ? (string) file_get_contents($chromePath) : '';
$header = is_file($headerPath) ? (string) file_get_contents($headerPath) : '';

expect3('exposes rbJourneySync', strpos($sync, 'window.rbJourneySync') !== false);
expect3('has scheduleSave', strpos($sync, 'scheduleSave') !== false);
expect3('has afterReady', strpos($sync, 'afterReady') !== false);
expect3('loads cloud plan API', strpos($sync, '/api/journey_plan_load.php') !== false);
expect3('saves cloud plan API', strpos($sync, '/api/journey_plan_save.php') !== false);
expect3('uses credentials include', strpos($sync, "credentials: 'include'") !== false);
expect3('defers import confirmation', strpos($sync, 'needsImport') !== false);
expect3('offline pending queue', strpos($sync, 'rbJourneySyncPendingV1') !== false);
expect3('saving message', strpos($sync, 'Saving to your Journey account') !== false);
expect3('saved message', strpos($sync, 'Saved to your Journey account') !== false);
expect3('loaded message does not claim save', strpos($sync, "setSaveState('loaded', 'Journey Premium is active.')") !== false);
expect3('retry message', strpos($sync, 'Saved on this browser; cloud save will retry') !== false);
expect3('readonly message', strpos($sync, 'Cloud updates require active Journey Premium access') !== false);
expect3('does not call import API in P3', strpos($sync, 'journey_plan_import.php') === false);
expect3('header loads sync before chrome', strpos($header, 'journey-sync.js') !== false && strpos($header, 'journey-sync.js') < strpos($header, 'journey-auth-chrome.js'));
expect3('chrome listens for sync state', strpos($chrome, 'rb-journey-sync-state') !== false);
expect3('chrome removed temporary P2-only note', strpos($chrome, 'Cloud plan saving will be connected in the next implementation step') === false);

$hookFiles = [
    'journey-progress.js',
    'retirement-spending-plan.js',
    'social-security-phase.js',
    'build-your-plan-phase.js',
    'stress-test-phase.js',
    'tax-strategy-phase.js',
    'survivor-planning-phase.js',
    'spending-goals-phase.js',
];
foreach ($hookFiles as $file) {
    $path = $root . '/journey.ronbelisle.com/assets/js/' . $file;
    $src = is_file($path) ? (string) file_get_contents($path) : '';
    expect3($file . ' waits for sync ready', strpos($src, 'afterReady') !== false);
    if ($file !== 'spending-goals-phase.js') {
        expect3($file . ' schedules cloud save', strpos($src, 'scheduleSave') !== false);
    }
}

echo "Milestone 5 P3 sync tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
