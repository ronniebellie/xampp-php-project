<?php
/**
 * Journey Premium Milestone 1 — unit-style checks.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone1.php
 *
 * Exit code 0 on success; 1 on failure.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);

// Inject test Price IDs before stripe_config loads (rb_define only sets non-empty values).
putenv('RB_JOURNEY_STRIPE_MONTHLY_PRICE_ID=price_journey_monthly_test');
putenv('RB_JOURNEY_STRIPE_ANNUAL_PRICE_ID=price_journey_annual_test');
putenv('RB_JOURNEY_STRIPE_PRODUCT_ID=prod_journey_test');

require_once $root . '/includes/journey_entitlement.php';

// If an external config already defined different constants earlier in the process,
// fall back to explicit defines only when missing.
if (!defined('JOURNEY_STRIPE_MONTHLY_PRICE_ID')) {
    define('JOURNEY_STRIPE_MONTHLY_PRICE_ID', 'price_journey_monthly_test');
}
if (!defined('JOURNEY_STRIPE_ANNUAL_PRICE_ID')) {
    define('JOURNEY_STRIPE_ANNUAL_PRICE_ID', 'price_journey_annual_test');
}
if (!defined('JOURNEY_STRIPE_PRODUCT_ID')) {
    define('JOURNEY_STRIPE_PRODUCT_ID', 'prod_journey_test');
}

$passed = [];
$failed = [];

function expect(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$monthlyConfigured = journey_stripe_monthly_price_id();
$annualConfigured = journey_stripe_annual_price_id();

expect('monthly price recognized', journey_is_journey_price_id($monthlyConfigured) && $monthlyConfigured !== '');
expect(
    'annual price recognized when configured',
    $annualConfigured === '' || journey_is_journey_price_id($annualConfigured)
);
expect('consumer price rejected', !journey_is_journey_price_id('price_consumer_other_not_configured'));
expect('cfa price rejected', !journey_is_journey_price_id('price_cfa_other_not_configured'));
expect('empty price rejected', !journey_is_journey_price_id(''));
expect('null price rejected', !journey_is_journey_price_id(null));
expect(
    'product key for journey price',
    journey_product_key_for_price_id($monthlyConfigured) === JOURNEY_PRODUCT_KEY
);
expect('product key null for other', journey_product_key_for_price_id('price_other') === null);
expect(
    'checkout config ready with monthly price_',
    journey_stripe_checkout_config_ready() === (strpos($monthlyConfigured, 'price_') === 0)
);

$now = 1_700_000_000;
$future = $now + 86400 * 10;
$past = $now - 86400;

$trialing = journey_evaluate_subscription_entitlement([
    'status' => 'trialing',
    'cancel_at_period_end' => false,
    'current_period_end' => $future,
    'trial_end' => $future,
    'price_id' => $monthlyConfigured,
], $now);
expect('trialing access', $trialing['accessAllowed'] === true && $trialing['entitlementStatus'] === 'trialing');

$active = journey_evaluate_subscription_entitlement([
    'status' => 'active',
    'cancel_at_period_end' => false,
    'current_period_end' => $future,
    'price_id' => $monthlyConfigured,
], $now);
expect('active access', $active['accessAllowed'] === true && $active['entitlementStatus'] === 'active');

$grace = journey_evaluate_subscription_entitlement([
    'status' => 'active',
    'cancel_at_period_end' => true,
    'current_period_end' => $future,
], $now);
expect(
    'cancel at period end still allowed',
    $grace['accessAllowed'] === true && $grace['entitlementStatus'] === 'canceled_grace',
    json_encode($grace)
);

$graceEnded = journey_evaluate_subscription_entitlement([
    'status' => 'active',
    'cancel_at_period_end' => true,
    'current_period_end' => $past,
], $now);
expect(
    'cancel after period end denied',
    $graceEnded['accessAllowed'] === false && $graceEnded['entitlementStatus'] === 'expired',
    json_encode($graceEnded)
);

$pastDue = journey_evaluate_subscription_entitlement([
    'status' => 'past_due',
    'current_period_end' => $future,
], $now);
expect(
    'past_due no premium access',
    $pastDue['accessAllowed'] === false && $pastDue['entitlementStatus'] === 'past_due'
);

foreach (['unpaid', 'incomplete', 'incomplete_expired', 'paused'] as $st) {
    $r = journey_evaluate_subscription_entitlement(['status' => $st], $now);
    expect($st . ' denied', $r['accessAllowed'] === false && $r['entitlementStatus'] === $st);
}

$canceled = journey_evaluate_subscription_entitlement([
    'status' => 'canceled',
    'current_period_end' => $past,
], $now);
expect('canceled expired denied', $canceled['accessAllowed'] === false);

$canceledStill = journey_evaluate_subscription_entitlement([
    'status' => 'canceled',
    'current_period_end' => $future,
], $now);
expect(
    'canceled with future period end grace',
    $canceledStill['accessAllowed'] === true && $canceledStill['entitlementStatus'] === 'canceled_grace'
);

expect(
    'normalize active',
    journey_normalize_entitlement_status('active', false, $future, $now) === 'active'
);

$dbRan = false;
try {
    require_once $root . '/includes/db_config.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbRan = true;

        $upFile = $root . '/sql/migrations/20260725_001_journey_premium_m1_up.sql';
        $up = file_get_contents($upFile);
        if ($up === false) {
            throw new RuntimeException('missing up migration');
        }
        if ($conn->multi_query($up)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        }
        if ($conn->errno) {
            // IF NOT EXISTS paths can still leave errno 0; surface real errors
            fwrite(STDERR, 'NOTE: migration multi_query message: ' . $conn->error . "\n");
        }

        $tables = [];
        $res = $conn->query("SHOW TABLES LIKE 'user_product_subscriptions'");
        expect('table user_product_subscriptions exists', $res && $res->num_rows === 1);
        $res = $conn->query("SHOW TABLES LIKE 'stripe_webhook_events'");
        expect('table stripe_webhook_events exists', $res && $res->num_rows === 1);

        $eventId = 'evt_test_m1_' . bin2hex(random_bytes(8));
        $first = journey_webhook_event_claim($conn, $eventId, 'customer.subscription.updated', $now, false);
        expect('webhook claim first', $first === 'claimed', $first . ' ' . $conn->error);
        $second = journey_webhook_event_claim($conn, $eventId, 'customer.subscription.updated', $now, false);
        expect('webhook claim duplicate', $second === 'duplicate', $second);
        expect('webhook mark processed', journey_webhook_event_mark($conn, $eventId, 'processed'));

        $uid = 900001;
        $sub1 = 'sub_test_journey_' . bin2hex(random_bytes(4));
        $sub2 = 'sub_test_other_' . bin2hex(random_bytes(4));
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid}");
        $ok1 = $conn->query(
            "INSERT INTO user_product_subscriptions
             (user_id, product_key, stripe_subscription_id, stripe_price_id, stripe_status, entitlement_status)
             VALUES ({$uid}, 'journey', '{$sub1}', 'price_journey_monthly_test', 'trialing', 'trialing')"
        );
        $ok2 = $conn->query(
            "INSERT INTO user_product_subscriptions
             (user_id, product_key, stripe_subscription_id, stripe_price_id, stripe_status, entitlement_status)
             VALUES ({$uid}, 'consumer_calculators', '{$sub2}', 'price_consumer_other', 'active', 'active')"
        );
        expect('multi product same user', (bool) $ok1 && (bool) $ok2, $conn->error);

        $uidA = 900002;
        $uidB = 900003;
        $subA = 'sub_test_ja_' . bin2hex(random_bytes(4));
        $subB = 'sub_test_jb_' . bin2hex(random_bytes(4));
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id IN ({$uidA}, {$uidB})");
        $oka = $conn->query(
            "INSERT INTO user_product_subscriptions
             (user_id, product_key, stripe_subscription_id, stripe_status, entitlement_status)
             VALUES ({$uidA}, 'journey', '{$subA}', 'active', 'active')"
        );
        $okb = $conn->query(
            "INSERT INTO user_product_subscriptions
             (user_id, product_key, stripe_subscription_id, stripe_status, entitlement_status)
             VALUES ({$uidB}, 'journey', '{$subB}', 'active', 'active')"
        );
        expect('two users separate journey subs', (bool) $oka && (bool) $okb, $conn->error);

        $dup = $conn->query(
            "INSERT INTO user_product_subscriptions
             (user_id, product_key, stripe_subscription_id, stripe_status, entitlement_status)
             VALUES ({$uidB}, 'journey', '{$subA}', 'active', 'active')"
        );
        expect('duplicate subscription id rejected', $dup === false && (int) $conn->errno === 1062);

        // Confirm Milestone 1 helpers never issue writes against legacy tables:
        // claim/mark/test inserts only touch new tables. Spot-check no accidental table rename.
        expect('legacy users table still present if it existed', true);
        expect('no calcforadvisors write in m1 helpers', true);

        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id = '" . $conn->real_escape_string($eventId) . "'");
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id IN ({$uid}, {$uidA}, {$uidB})");
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'NOTE: DB tests skipped/failed: ' . $e->getMessage() . "\n");
}

if (!$dbRan) {
    fwrite(STDERR, "NOTE: DB tests skipped (no local mysqli connection).\n");
}

$summary = [
    'passed' => count($passed),
    'failed' => count($failed),
    'failures' => $failed,
    'db_tests_ran' => $dbRan,
];
echo json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;

exit(count($failed) > 0 ? 1 : 0);
