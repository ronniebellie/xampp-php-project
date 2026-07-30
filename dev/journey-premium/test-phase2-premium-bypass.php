<?php
/**
 * Premium users bypass continue-to-phase-2 interstitial.
 *
 * Usage:
 *   php dev/journey-premium/test-phase2-premium-bypass.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expectB(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$phase1 = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/spending-goals.php');
$continue = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/continue-to-phase-2.php');
$chrome = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/journey-auth-chrome.js');

expectB('phase1 premium CTA goes to social-security', strpos($phase1, 'href="/phases/social-security.php" data-journey-premium-only') !== false);
expectB('phase1 non-premium CTA keeps interstitial', strpos($phase1, 'href="/phases/continue-to-phase-2.php" data-journey-non-premium-only') !== false);
expectB('chrome supports non-premium-only', strpos($chrome, 'data-journey-non-premium-only') !== false);
expectB('continue page redirects premium users', strpos($continue, 'status.hasAccess') !== false && strpos($continue, 'location.replace') !== false);
expectB('obsolete cloud-coming copy removed', strpos($continue, 'Cloud Journey saving for Journey Premium is coming') === false);
expectB('obsolete browser-only premium note removed', strpos($continue, 'still live in this browser for now') === false);
expectB('free-auth copy mentions cloud saving with Premium', strpos($continue, 'Journey Premium adds cloud saving') !== false);
expectB('continue page uses free-auth-only for signed-in free users', strpos($continue, 'data-journey-free-auth-only') !== false);

echo "Phase 2 Premium bypass tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
