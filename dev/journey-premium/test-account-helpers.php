<?php
/**
 * Account helpers + entitlement display checks.
 *
 * Usage:
 *   php dev/journey-premium/test-account-helpers.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/session_bootstrap.php';
rb_session_start();
require_once $root . '/includes/db_config.php';
require_once $root . '/includes/account_helpers.php';

$passed = [];
$failed = [];

function expectA(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$hash = password_hash('OldPassword1', PASSWORD_DEFAULT);

$badCurrent = rb_account_validate_password_change('wrong', 'NewPassword1', 'NewPassword1', $hash);
expectA('rejects wrong current password', empty($badCurrent['ok']));

$short = rb_account_validate_password_change('OldPassword1', 'short', 'short', $hash);
expectA('rejects short password', empty($short['ok']));

$mismatch = rb_account_validate_password_change('OldPassword1', 'NewPassword1', 'NewPassword2', $hash);
expectA('rejects mismatch', empty($mismatch['ok']));

$same = rb_account_validate_password_change('OldPassword1', 'OldPassword1', 'OldPassword1', $hash);
expectA('rejects unchanged password', empty($same['ok']));

$ok = rb_account_validate_password_change('OldPassword1', 'NewPassword1', 'NewPassword1', $hash);
expectA('accepts valid password change', !empty($ok['ok']));

$ghostId = 999999002;
$del = $conn->prepare('DELETE FROM user_product_subscriptions WHERE user_id = ?');
if ($del) {
    $del->bind_param('i', $ghostId);
    $del->execute();
    $del->close();
}

$free = rb_account_journey_status($conn, $ghostId);
expectA('ghost has no journey access', $free['hasAccess'] === false);
expectA('ghost label not enrolled', $free['label'] === 'Not enrolled');

$product = JOURNEY_PRODUCT_KEY;
$ins = $conn->prepare(
    "INSERT INTO user_product_subscriptions
        (user_id, product_key, stripe_subscription_id, stripe_status, entitlement_status, updated_at)
     VALUES (?, ?, ?, 'trialing', 'trialing', NOW())"
);
$subId = 'sub_test_account_helpers_' . $ghostId;
expectA('insert journey trial fixture', (bool) $ins);
if ($ins) {
    $ins->bind_param('iss', $ghostId, $product, $subId);
    expectA('insert journey trial ok', $ins->execute());
    $ins->close();
}

$trial = rb_account_journey_status($conn, $ghostId);
expectA('trial has access', $trial['hasAccess'] === true);
expectA('trial label mentions Journey Premium', strpos($trial['label'], 'Journey Premium') !== false);
expectA('trial entitlement status', $trial['entitlementStatus'] === 'trialing');

$cleanup = $conn->prepare('DELETE FROM user_product_subscriptions WHERE user_id = ?');
if ($cleanup) {
    $cleanup->bind_param('i', $ghostId);
    $cleanup->execute();
    $cleanup->close();
}

echo "Account helpers tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
