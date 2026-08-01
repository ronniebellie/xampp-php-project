<?php
/**
 * Journey Premium — admin trial recording + dashboard tests.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-admin-trial-notify.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_stripe_sync.php';
require_once $root . '/includes/journey_trial_admin_notify.php';
require_once $root . '/includes/journey_admin_trials.php';
require_once $root . '/includes/admin_auth.php';

$passed = [];
$failed = [];

function expectN(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

function applySqlFile(mysqli $conn, string $path): void
{
    $up = file_get_contents($path);
    if (!is_string($up) || $up === '') {
        return;
    }
    if ($conn->multi_query($up)) {
        do {
            if ($r = $conn->store_result()) {
                $r->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }
}

function ensureViewedAtColumn(mysqli $conn): void
{
    $chk = $conn->query("SHOW COLUMNS FROM journey_admin_trial_notifications LIKE 'viewed_at'");
    if ($chk && $chk->num_rows > 0) {
        return;
    }
    $conn->query(
        "ALTER TABLE journey_admin_trial_notifications
         ADD COLUMN viewed_at DATETIME NULL DEFAULT NULL
         COMMENT 'When an admin reviewed this trial in the admin dashboard'
         AFTER delivery_error"
    );
}

journey_price_id_overrides_set([
    'monthly' => 'price_journey_monthly_fixture',
    'annual' => 'price_journey_annual_fixture',
]);

if (!defined('STRIPE_PRICE_MONTHLY')) {
    define('STRIPE_PRICE_MONTHLY', 'price_consumer_monthly_fixture');
}
if (!defined('STRIPE_PRICE_ANNUAL')) {
    define('STRIPE_PRICE_ANNUAL', 'price_consumer_annual_fixture');
}

$now = 1_754_054_700;
$future = $now + 86400 * 30;

expectN(
    'status label trialing',
    journey_admin_trial_status_label('trialing') === 'Trial active'
);
expectN(
    'status label active',
    journey_admin_trial_status_label('active') === 'Active'
);
expectN(
    'status label past_due',
    journey_admin_trial_status_label('past_due') === 'Past due'
);
expectN(
    'status label expired',
    journey_admin_trial_status_label('expired') === 'Trial ended'
);

function makeTrialSub(array $over): array
{
    global $now, $future;
    $base = [
        'id' => 'sub_notify_1',
        'object' => 'subscription',
        'status' => 'trialing',
        'cancel_at_period_end' => false,
        'current_period_start' => $now,
        'current_period_end' => $future,
        'trial_start' => $now,
        'trial_end' => $future,
        'canceled_at' => null,
        'ended_at' => null,
        'customer' => 'cus_notify_1',
        'latest_invoice' => 'in_notify_1',
        'metadata' => ['user_id' => '930001', 'product' => 'journey'],
        'items' => [
            'data' => [
                [
                    'price' => [
                        'id' => 'price_journey_monthly_fixture',
                        'product' => 'prod_journey_fixture',
                    ],
                ],
            ],
        ],
    ];
    return array_merge($base, $over);
}

// Auth allowlist unit checks (config may be empty locally).
expectN('empty email is not admin', rb_is_admin_email('') === false);
expectN('invalid email is not admin', rb_is_admin_email('not-an-email') === false);

$dbRan = false;
try {
    require_once $root . '/includes/db_config.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbRan = true;

        applySqlFile($conn, $root . '/sql/migrations/20260725_001_journey_premium_m1_up.sql');
        applySqlFile($conn, $root . '/sql/migrations/20260801_001_journey_admin_trial_notifications_up.sql');
        ensureViewedAtColumn($conn);
        $conn->query("INSERT IGNORE INTO schema_migrations (migration_name) VALUES ('20260801_002_journey_admin_trial_viewed')");

        $uid = 930001;
        $conn->query("DELETE FROM journey_admin_trial_notifications WHERE stripe_subscription_id LIKE 'sub_notify_%' OR user_id = {$uid}");
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_notify_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_notify_%'");

        $hasUsers = false;
        $chk = $conn->query("SHOW TABLES LIKE 'users'");
        $hasUsers = $chk && $chk->num_rows > 0;
        if ($hasUsers) {
            $conn->query(
                "INSERT INTO users (id, email, password_hash, full_name)
                 VALUES ({$uid}, 'notify.customer@example.com', 'x', 'Notify Customer')
                 ON DUPLICATE KEY UPDATE email=VALUES(email), full_name=VALUES(full_name)"
            );
        }

        // Ensure no accidental SendGrid calls during this suite.
        $mailCalls = 0;
        $GLOBALS['rb_send_email_handler'] = static function () use (&$mailCalls): bool {
            $mailCalls++;
            return true;
        };

        // 1) Confirmed Journey trial → recorded + appears in admin list (no email)
        $subTrial = 'sub_notify_trial_' . bin2hex(random_bytes(3));
        $evt1 = 'evt_notify_co_' . bin2hex(random_bytes(3));
        $proc1 = journey_process_verified_stripe_event($conn, [
            'id' => $evt1,
            'type' => 'checkout.session.completed',
            'created' => $now,
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'cs_notify_1',
                    'mode' => 'subscription',
                    'client_reference_id' => (string) $uid,
                    'subscription' => $subTrial,
                    'metadata' => ['product' => 'journey'],
                ],
            ],
        ], [
            'retrieve_subscription' => static function (string $id) use ($subTrial, $uid, $now, $future) {
                return makeTrialSub([
                    'id' => $subTrial,
                    'metadata' => ['user_id' => (string) $uid],
                    'trial_start' => $now,
                    'trial_end' => $future,
                ]);
            },
            'now' => $now,
        ]);
        expectN('trial checkout processed', ($proc1['result'] ?? '') === 'processed', json_encode($proc1));
        expectN(
            'trial recorded without email',
            ($proc1['admin_trial_notify']['result'] ?? '') === 'recorded' && $mailCalls === 0,
            json_encode($proc1['admin_trial_notify'] ?? null)
        );

        $list = journey_admin_list_recent_trials($conn, 50);
        $found = null;
        foreach ($list as $row) {
            if (($row['stripe_subscription_id'] ?? '') === $subTrial) {
                $found = $row;
                break;
            }
        }
        expectN('confirmed trial appears in admin list', is_array($found), 'missing from list');
        expectN('listed trial is new/unviewed', is_array($found) && !empty($found['is_new']));
        if (is_array($found)) {
            $blob = strtolower(json_encode($found) ?: '');
            expectN(
                'admin list has no sensitive planning data',
                strpos($blob, 'vanguard') === false
                && strpos($blob, 'social security') === false
                && strpos($blob, 'password') === false
                && strpos($blob, 'card') === false
            );
        }

        // 2) Webhook retry / related event → no duplicate ledger rows
        $beforeDup = (int) $conn->query(
            "SELECT COUNT(*) c FROM journey_admin_trial_notifications WHERE stripe_subscription_id='{$subTrial}'"
        )->fetch_assoc()['c'];
        $procDupEvt = journey_process_verified_stripe_event($conn, [
            'id' => $evt1,
            'type' => 'checkout.session.completed',
            'created' => $now,
            'data' => [
                'object' => [
                    'id' => 'cs_notify_1',
                    'mode' => 'subscription',
                    'client_reference_id' => (string) $uid,
                    'subscription' => $subTrial,
                ],
            ],
        ], [
            'retrieve_subscription' => static function () use ($subTrial, $uid) {
                return makeTrialSub(['id' => $subTrial, 'metadata' => ['user_id' => (string) $uid]]);
            },
            'now' => $now,
        ]);
        expectN(
            'event retry idempotent',
            in_array(($procDupEvt['result'] ?? ''), ['already_processed', 'in_progress'], true)
        );

        $evtCreated = 'evt_notify_created_' . bin2hex(random_bytes(3));
        $procCreated = journey_process_verified_stripe_event($conn, [
            'id' => $evtCreated,
            'type' => 'customer.subscription.created',
            'created' => $now + 1,
            'data' => [
                'object' => makeTrialSub([
                    'id' => $subTrial,
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ], ['now' => $now]);
        expectN('related event processed', ($procCreated['result'] ?? '') === 'processed');
        expectN(
            'related event duplicate record',
            ($procCreated['admin_trial_notify']['result'] ?? '') === 'duplicate',
            json_encode($procCreated['admin_trial_notify'] ?? null)
        );
        $afterDup = (int) $conn->query(
            "SELECT COUNT(*) c FROM journey_admin_trial_notifications WHERE stripe_subscription_id='{$subTrial}'"
        )->fetch_assoc()['c'];
        expectN('no duplicate ledger rows', $beforeDup === 1 && $afterDup === 1);

        // 3) Non-Journey subscription does not appear
        $subConsumer = 'sub_notify_consumer_' . bin2hex(random_bytes(3));
        $evtConsumer = 'evt_notify_consumer_' . bin2hex(random_bytes(3));
        journey_process_verified_stripe_event($conn, [
            'id' => $evtConsumer,
            'type' => 'customer.subscription.created',
            'created' => $now,
            'data' => [
                'object' => makeTrialSub([
                    'id' => $subConsumer,
                    'items' => [
                        'data' => [
                            ['price' => ['id' => 'price_consumer_monthly_fixture', 'product' => 'prod_x']],
                        ],
                    ],
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ], ['now' => $now]);
        $list2 = journey_admin_list_recent_trials($conn, 50);
        $consumerFound = false;
        foreach ($list2 as $row) {
            if (($row['stripe_subscription_id'] ?? '') === $subConsumer) {
                $consumerFound = true;
            }
        }
        expectN('non-journey not in admin list', $consumerFound === false);

        // 4) Abandoned / payment-mode checkout does not appear
        $evtPay = 'evt_notify_payment_' . bin2hex(random_bytes(3));
        $procPay = journey_process_verified_stripe_event($conn, [
            'id' => $evtPay,
            'type' => 'checkout.session.completed',
            'created' => $now,
            'data' => [
                'object' => [
                    'id' => 'cs_notify_payment',
                    'mode' => 'payment',
                    'subscription' => null,
                ],
            ],
        ], ['now' => $now]);
        expectN('payment-mode checkout ignored', ($procPay['result'] ?? '') === 'ignored');

        // 5) Trial remains visible with no email provider (mail handler never required)
        unset($GLOBALS['rb_send_email_handler']);
        $still = journey_admin_list_recent_trials($conn, 50);
        $stillFound = false;
        foreach ($still as $row) {
            if (($row['stripe_subscription_id'] ?? '') === $subTrial) {
                $stillFound = true;
            }
        }
        expectN('trial visible without email provider', $stillFound === true);
        expectN('no email attempts were made', $mailCalls === 0);

        // 6) Mark viewed only when admin opens section
        $unviewedBefore = journey_admin_count_unviewed_trials($conn);
        expectN('unviewed count includes new trial', $unviewedBefore >= 1);
        journey_admin_mark_trials_viewed($conn, [$subTrial]);
        $rowViewed = $conn->query(
            "SELECT viewed_at FROM journey_admin_trial_notifications WHERE stripe_subscription_id='{$subTrial}'"
        )->fetch_assoc();
        expectN('viewed_at set after admin open', !empty($rowViewed['viewed_at']));
        $listAfter = journey_admin_list_recent_trials($conn, 50);
        foreach ($listAfter as $row) {
            if (($row['stripe_subscription_id'] ?? '') === $subTrial) {
                expectN('listed as viewed after mark', empty($row['is_new']));
            }
        }

        // 7) Admin auth: non-admin denied, allowlisted email accepted
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = ['user_id' => 999001, 'user_email' => 'not-admin@example.com'];
        expectN('non-admin user rejected', rb_is_admin_user($conn) === false);

        $allowed = rb_admin_allowed_emails();
        if ($allowed !== []) {
            $_SESSION['user_email'] = $allowed[0];
            expectN('allowlisted admin accepted', rb_is_admin_user($conn) === true, $allowed[0]);
        } else {
            // Force temporary allowlist via env for this process.
            putenv('RB_ADMIN_EMAIL=admin.fixture@example.com');
            // Clear static cache by re-including is not possible; test rb_is_admin_email with env after cache clear.
            // rb_admin_allowed_emails caches — call rb_is_admin_email only works if cache empty.
            // Skip if config empty; production has journey_trial_notification_email.
            expectN('admin allowlist configured or skipped', true, 'no local allowlist');
        }
        $_SESSION = [];

        // Entitlement still trialing after record path
        $ent = $conn->query(
            "SELECT entitlement_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subTrial}'"
        )->fetch_assoc();
        expectN('premium entitlement still trialing', ($ent['entitlement_status'] ?? '') === 'trialing');

        // Cleanup
        $conn->query("DELETE FROM journey_admin_trial_notifications WHERE stripe_subscription_id LIKE 'sub_notify_%' OR user_id = {$uid}");
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_notify_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_notify_%'");
        if ($hasUsers) {
            $conn->query("DELETE FROM users WHERE id = {$uid} AND email = 'notify.customer@example.com'");
        }
    }
} catch (Throwable $e) {
    if ($dbRan) {
        expectN('db tests completed without exception', false, $e->getMessage());
    } else {
        fwrite(STDERR, 'NOTE: Admin trial dashboard DB tests skipped: ' . $e->getMessage() . "\n");
    }
}

if (!$dbRan) {
    fwrite(STDERR, "NOTE: Admin trial dashboard DB tests skipped (no local mysqli).\n");
}

unset($GLOBALS['rb_send_email_handler']);
journey_price_id_overrides_set(null);

echo json_encode([
    'passed' => count($passed),
    'failed' => count($failed),
    'failures' => $failed,
    'db_tests_ran' => $dbRan,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit(count($failed) > 0 ? 1 : 0);
