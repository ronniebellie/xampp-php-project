<?php
/**
 * Journey Premium Milestone 5 / R1 — P2 status payload checks.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone5-p2.php
 *
 * Exit code 0 on success; 1 on failure.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/includes/session_bootstrap.php';
rb_session_start();
require_once $root . '/includes/db_config.php';
require_once $root . '/includes/journey_status.php';

$passed = [];
$failed = [];

function expect2(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

function expect_keys(array $payload, array $keys): void
{
    foreach ($keys as $key) {
        expect2('status has ' . $key, array_key_exists($key, $payload));
    }
}

$requiredKeys = [
    'authenticated',
    'userId',
    'userName',
    'firstName',
    'userEmail',
    'hasAccess',
    'accessMode',
    'subscriptionStatus',
    'canCloudRead',
    'canCloudWrite',
    'cloudPlanExists',
    'planSavedAt',
    'cta',
    'loginUrl',
    'logoutUrl',
    'workspaceUrl',
    'checkoutUrl',
    'trialDays',
];

// Anonymous (clear any leftover session for this CLI process).
$_SESSION = [];
$anon = journey_status_build_response($conn);
expect_keys($anon, $requiredKeys);
expect2('anonymous not authenticated', $anon['authenticated'] === false);
expect2('anonymous accessMode', $anon['accessMode'] === 'anonymous');
expect2('anonymous no personal data', $anon['userEmail'] === null && $anon['firstName'] === null && $anon['userId'] === null);
expect2('anonymous cannot cloud write', $anon['canCloudWrite'] === false);
expect2('anonymous cannot cloud read', $anon['canCloudRead'] === false);
expect2('anonymous cta start_trial', $anon['cta'] === 'start_trial');
expect2('anonymous loginUrl present', is_string($anon['loginUrl']) && strpos($anon['loginUrl'], 'login.php') !== false);
expect2('anonymous logoutUrl present', is_string($anon['logoutUrl']) && strpos($anon['logoutUrl'], 'logout.php') !== false);
expect2('anonymous entitlementStatus preserved', ($anon['entitlementStatus'] ?? '') === 'none');

expect2('first name helper', journey_status_first_name('Bob Smith') === 'Bob');
expect2('first name empty', journey_status_first_name('  ') === '');

$metaGhost = journey_status_cloud_plan_meta($conn, 999999001);
expect2('ghost cloud meta missing', $metaGhost['exists'] === false && $metaGhost['planSavedAt'] === null);

// Authenticated free ghost user (no Premium, no cloud plan).
$_SESSION['user_id'] = 999999001;
$_SESSION['user_name'] = 'Free Tester';
$_SESSION['user_email'] = 'free-tester@example.com';
$free = journey_status_build_response($conn);
expect2('free authenticated', $free['authenticated'] === true);
expect2('free firstName', $free['firstName'] === 'Free');
expect2('free email', $free['userEmail'] === 'free-tester@example.com');
expect2('free accessMode', $free['accessMode'] === 'free');
expect2('free no hasAccess', $free['hasAccess'] === false);
expect2('free cannot write', $free['canCloudWrite'] === false);
expect2('free cannot read without plan', $free['canCloudRead'] === false);
expect2('free cta start_trial', $free['cta'] === 'start_trial');

// Simulate former Premium with cloud plan (read-only): insert plan for ghost, no entitlement.
$payload = json_encode([
    'schemaVersion' => 1,
    'progress' => ['spending-goals' => true],
    'calculators' => [],
], JSON_UNESCAPED_SLASHES);
$uid = 999999001;
$del = $conn->prepare('DELETE FROM journey_plans WHERE user_id = ?');
if ($del) {
    $del->bind_param('i', $uid);
    $del->execute();
    $del->close();
}
$ins = $conn->prepare(
    'INSERT INTO journey_plans (user_id, schema_version, payload) VALUES (?, 1, ?)'
);
expect2('insert readonly fixture', (bool) $ins);
if ($ins) {
    $ins->bind_param('is', $uid, $payload);
    expect2('insert readonly ok', $ins->execute());
    $ins->close();
}

$readonly = journey_status_build_response($conn);
expect2('readonly accessMode', $readonly['accessMode'] === 'readonly');
expect2('readonly can read', $readonly['canCloudRead'] === true);
expect2('readonly cannot write', $readonly['canCloudWrite'] === false);
expect2('readonly cloud exists', $readonly['cloudPlanExists'] === true);
expect2('readonly planSavedAt present', is_string($readonly['planSavedAt']) && $readonly['planSavedAt'] !== '');
expect2('readonly cta subscribe when prior sub missing stays start_trial or subscribe', in_array($readonly['cta'], ['start_trial', 'subscribe'], true));

// Cleanup fixture.
$del2 = $conn->prepare('DELETE FROM journey_plans WHERE user_id = ?');
if ($del2) {
    $del2->bind_param('i', $uid);
    $del2->execute();
    $del2->close();
}

$_SESSION = [];

echo "Milestone 5 P2 status tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}

exit(count($failed) === 0 ? 0 : 1);
