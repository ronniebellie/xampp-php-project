<?php
/**
 * Phase 3 Premium cloud-save messaging and saveNow wiring.
 *
 * Usage:
 *   php dev/journey-premium/test-phase3-cloud-save.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expectP3(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$js = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/build-your-plan-phase.js');
$php = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/build-your-plan.php');
$sync = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/journey-sync.js');

expectP3('phase3 still writes progress localStorage', strpos($js, "localStorage.setItem(storageKey") !== false);
expectP3('phase3 schedules sync on write', strpos($js, "scheduleSave('phase')") !== false);
expectP3('phase3 uses saveNow for premium confirmation', strpos($js, 'persistCloudNow') !== false && strpos($js, "saveNow(reason || 'phase')") !== false);
expectP3('premium success mentions Journey account', strpos($js, 'saved to your Journey account') !== false);
expectP3('free/local success mentions this browser', strpos($js, 'saved in this browser') !== false);
expectP3('hardcoded browser-only confirmation removed from php', strpos($php, 'saved in this browser.</strong>') === false);
expectP3('cache bust updated', strpos($php, 'build-your-plan-phase.js?v=20260730-phase3-cloud') !== false);
expectP3('sync payload includes progress records', strpos($sync, 'progress: progress') !== false);

echo "Phase 3 cloud-save tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
