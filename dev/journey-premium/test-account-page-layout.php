<?php
/**
 * Account page layout static checks (Journey-first UX).
 *
 * Usage:
 *   php dev/journey-premium/test-account-page-layout.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$path = $root . '/account.php';
$src = is_file($path) ? (string) file_get_contents($path) : '';
$passed = [];
$failed = [];

function expectLayout(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

expectLayout('account.php exists', $src !== '');
expectLayout('heading is My Account', strpos($src, '<h1>My Account</h1>') !== false);
expectLayout('old heading removed', strpos($src, 'Account Management') === false);
expectLayout('Journey Premium section present', strpos($src, '<h2>Journey Premium</h2>') !== false);
expectLayout('Other Products teaser for non-calculator users', strpos($src, 'Other Products') !== false);
expectLayout('Calculator teaser detail', strpos($src, 'Advanced planning features for the retirement calculators.') !== false);
expectLayout('Learn More link to premium.html', strpos($src, 'href="premium.html">Learn More</a>') !== false);
expectLayout('full Calculator section only when subscribed', strpos($src, '<?php if ($is_calculator_premium): ?>') !== false);
expectLayout('teaser only when not subscribed', strpos($src, '<?php if (!$is_calculator_premium): ?>') !== false);
expectLayout('subscribed manage CTA retained', strpos($src, 'Manage Calculator Premium subscription') !== false);
expectLayout('upgrade CTA removed for free calculator users', strpos($src, 'Upgrade Calculator Premium') === false);
expectLayout('entitlement detection unchanged', strpos($src, "(\$user['subscription_status'] === 'premium')") !== false);
expectLayout('journey status helper unchanged', strpos($src, 'rb_account_journey_status($conn, $user_id)') !== false);

echo "Account page layout tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
