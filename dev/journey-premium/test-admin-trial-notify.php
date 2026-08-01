<?php
/**
 * Journey Premium — administrator trial notification tests.
 *
 * Uses mocked email delivery only (no real SendGrid calls).
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-admin-trial-notify.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_stripe_sync.php';
require_once $root . '/includes/journey_trial_admin_notify.php';

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

$now = 1_754_054_700; // ~2025-08-01-ish fixture
$future = $now + 86400 * 30;
$mailLog = [];

$mockMail = static function (string $to, string $subject, string $body) use (&$mailLog): bool {
    $mailLog[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
    return true;
};

$failMail = static function (string $to, string $subject, string $body) use (&$mailLog): bool {
    $mailLog[] = ['to' => $to, 'subject' => $subject, 'body' => $body, 'forced_fail' => true];
    $GLOBALS['rb_send_email_last_error'] = 'credits_exceeded';
    return false;
};

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

// Pure body builder checks (no DB).
$body = journey_trial_admin_notification_build_body([
    'full_name' => 'Bob Smith',
    'email' => 'customer@example.com',
    'trial_start' => $now,
    'trial_end' => $future,
    'user_id' => 123,
    'stripe_customer_id' => 'cus_example',
    'stripe_subscription_id' => 'sub_example',
]);
expectN('body has subject line', strpos($body, 'New Journey Premium Trial Started') !== false);
expectN('body has name', strpos($body, 'Bob Smith') !== false);
expectN('body has email', strpos($body, 'customer@example.com') !== false);
expectN('body has stripe ids', strpos($body, 'cus_example') !== false && strpos($body, 'sub_example') !== false);
expectN('body has trial active statement', strpos($body, '30-day Journey Premium trial is now active') !== false);
expectN(
    'body has no sensitive planning data',
    stripos($body, 'vanguard') === false
    && stripos($body, 'social security') === false
    && stripos($body, 'portfolio') === false
    && stripos($body, 'password') === false
    && stripos($body, 'card') === false
);

$dbRan = false;
try {
    require_once $root . '/includes/db_config.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbRan = true;

        foreach ([
            $root . '/sql/migrations/20260725_001_journey_premium_m1_up.sql',
            $root . '/sql/migrations/20260801_001_journey_admin_trial_notifications_up.sql',
        ] as $sqlFile) {
            $up = file_get_contents($sqlFile);
            if (!is_string($up)) {
                continue;
            }
            if ($conn->multi_query($up)) {
                do {
                    if ($r = $conn->store_result()) {
                        $r->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            }
        }

        $uid = 930001;
        $conn->query("DELETE FROM journey_admin_trial_notifications WHERE stripe_subscription_id LIKE 'sub_notify_%' OR user_id = {$uid}");
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_notify_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_notify_%'");

        // Ensure fixture user row exists for name/email lookup (best-effort).
        $hasUsers = false;
        try {
            $chk = $conn->query("SHOW TABLES LIKE 'users'");
            $hasUsers = $chk && $chk->num_rows > 0;
        } catch (Throwable $e) {
            $hasUsers = false;
        }
        if ($hasUsers) {
            $conn->query(
                "INSERT INTO users (id, email, password_hash, full_name)
                 VALUES ({$uid}, 'notify.customer@example.com', 'x', 'Notify Customer')
                 ON DUPLICATE KEY UPDATE email=VALUES(email), full_name=VALUES(full_name)"
            );
        }

        $recipient = 'admin-notify-test@example.com';
        $mailLog = [];

        // 1) Valid Journey Premium trial → one admin notification
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
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('trial checkout processed', ($proc1['result'] ?? '') === 'processed', json_encode($proc1));
        expectN(
            'trial notifies admin once',
            ($proc1['admin_trial_notify']['result'] ?? '') === 'sent' && count($mailLog) === 1,
            json_encode(['notify' => $proc1['admin_trial_notify'] ?? null, 'mails' => count($mailLog)])
        );
        expectN('subject correct', ($mailLog[0]['subject'] ?? '') === 'New Journey Premium Trial Started');
        expectN('recipient correct', ($mailLog[0]['to'] ?? '') === $recipient);
        expectN(
            'email body includes subscription id',
            strpos((string) ($mailLog[0]['body'] ?? ''), $subTrial) !== false
        );
        $ent = $conn->query(
            "SELECT entitlement_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subTrial}'"
        )->fetch_assoc();
        expectN('premium entitlement active/trialing', ($ent['entitlement_status'] ?? '') === 'trialing');

        // 2) Same Stripe event retry → no second email
        $beforeRetry = count($mailLog);
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
            'retrieve_subscription' => static function (string $id) use ($subTrial, $uid, $now, $future) {
                return makeTrialSub(['id' => $subTrial, 'metadata' => ['user_id' => (string) $uid]]);
            },
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('event retry idempotent', ($procDupEvt['result'] ?? '') === 'already_processed' || ($procDupEvt['result'] ?? '') === 'in_progress');
        expectN('event retry no second email', count($mailLog) === $beforeRetry);

        // 3) Related subscription.created for same sub → no second email
        $evtCreated = 'evt_notify_created_' . bin2hex(random_bytes(3));
        $beforeRelated = count($mailLog);
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
        ], [
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('related event processed', ($procCreated['result'] ?? '') === 'processed', json_encode($procCreated));
        expectN(
            'related event no second email',
            count($mailLog) === $beforeRelated
            && in_array(($procCreated['admin_trial_notify']['result'] ?? ''), ['duplicate', 'skipped'], true),
            json_encode($procCreated['admin_trial_notify'] ?? null)
        );

        // 4) Non-Journey product → no notification
        $mailBeforeNon = count($mailLog);
        $subConsumer = 'sub_notify_consumer_' . bin2hex(random_bytes(3));
        $evtConsumer = 'evt_notify_consumer_' . bin2hex(random_bytes(3));
        $procConsumer = journey_process_verified_stripe_event($conn, [
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
        ], [
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN(
            'non-journey no notify',
            count($mailLog) === $mailBeforeNon
            && (($procConsumer['admin_trial_notify']['result'] ?? '') === 'skipped'
                || ($procConsumer['detail'] ?? '') === 'ignored_non_journey_price'
                || ($procConsumer['result'] ?? '') === 'processed'),
            json_encode($procConsumer)
        );
        $cntConsumer = (int) $conn->query(
            "SELECT COUNT(*) c FROM user_product_subscriptions WHERE stripe_subscription_id='{$subConsumer}'"
        )->fetch_assoc()['c'];
        expectN('non-journey no entitlement row', $cntConsumer === 0);

        // 5) Abandoned / non-subscription checkout → no notification
        $mailBeforeAbandon = count($mailLog);
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
        ], [
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('payment-mode checkout ignored', ($procPay['result'] ?? '') === 'ignored');
        expectN('abandoned checkout no email', count($mailLog) === $mailBeforeAbandon);

        // 6) Journey-looking event but unknown price → no notification
        $mailBeforeUnknown = count($mailLog);
        $subUnknown = 'sub_notify_unknown_' . bin2hex(random_bytes(3));
        $evtUnknown = 'evt_notify_unknown_' . bin2hex(random_bytes(3));
        $procUnknown = journey_process_verified_stripe_event($conn, [
            'id' => $evtUnknown,
            'type' => 'customer.subscription.created',
            'created' => $now,
            'data' => [
                'object' => makeTrialSub([
                    'id' => $subUnknown,
                    'items' => [
                        'data' => [
                            ['price' => ['id' => 'price_unknown_xyz', 'product' => 'prod_x']],
                        ],
                    ],
                    'metadata' => ['user_id' => (string) $uid, 'product' => 'journey'],
                ]),
            ],
        ], [
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('unknown price no email', count($mailLog) === $mailBeforeUnknown, json_encode($procUnknown));

        // 7) Email delivery failure does not reverse Premium
        $subFailMail = 'sub_notify_mailfail_' . bin2hex(random_bytes(3));
        $evtFailMail = 'evt_notify_mailfail_' . bin2hex(random_bytes(3));
        $mailLog = [];
        $procFailMail = journey_process_verified_stripe_event($conn, [
            'id' => $evtFailMail,
            'type' => 'customer.subscription.created',
            'created' => $now,
            'data' => [
                'object' => makeTrialSub([
                    'id' => $subFailMail,
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ], [
            'now' => $now,
            'send_email' => $failMail,
            'recipient' => $recipient,
        ]);
        expectN('mail failure webhook still processed', ($procFailMail['result'] ?? '') === 'processed');
        expectN(
            'mail failure reported',
            ($procFailMail['admin_trial_notify']['result'] ?? '') === 'failed',
            json_encode($procFailMail['admin_trial_notify'] ?? null)
        );
        $entFail = $conn->query(
            "SELECT entitlement_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subFailMail}'"
        )->fetch_assoc();
        expectN('mail failure keeps trialing entitlement', ($entFail['entitlement_status'] ?? '') === 'trialing');
        $delStatus = $conn->query(
            "SELECT delivery_status, delivery_error FROM journey_admin_trial_notifications WHERE stripe_subscription_id='{$subFailMail}'"
        )->fetch_assoc();
        expectN(
            'mail failure logged on ledger',
            ($delStatus['delivery_status'] ?? '') === 'failed'
            && ($delStatus['delivery_error'] ?? '') === 'credits_exceeded',
            json_encode($delStatus)
        );

        // 8) Active (non-trial) Journey subscription → no trial notification
        $mailLog = [];
        $subActive = 'sub_notify_active_' . bin2hex(random_bytes(3));
        $evtActive = 'evt_notify_active_' . bin2hex(random_bytes(3));
        $procActive = journey_process_verified_stripe_event($conn, [
            'id' => $evtActive,
            'type' => 'customer.subscription.created',
            'created' => $now,
            'data' => [
                'object' => makeTrialSub([
                    'id' => $subActive,
                    'status' => 'active',
                    'trial_start' => null,
                    'trial_end' => null,
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ], [
            'now' => $now,
            'send_email' => $mockMail,
            'recipient' => $recipient,
        ]);
        expectN('active sub processed', ($procActive['result'] ?? '') === 'processed');
        expectN(
            'active sub no trial notify',
            count($mailLog) === 0
            && ($procActive['admin_trial_notify']['result'] ?? '') === 'skipped',
            json_encode($procActive['admin_trial_notify'] ?? null)
        );

        // Cleanup
        $conn->query("DELETE FROM journey_admin_trial_notifications WHERE stripe_subscription_id LIKE 'sub_notify_%' OR user_id = {$uid}");
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_notify_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_notify_%'");
        if ($hasUsers) {
            // Leave user row (may be shared); only delete if we created a disposable marker email.
            $conn->query("DELETE FROM users WHERE id = {$uid} AND email = 'notify.customer@example.com'");
        }
    }
} catch (Throwable $e) {
    if ($dbRan) {
        expectN('db tests completed without exception', false, $e->getMessage());
    } else {
        fwrite(STDERR, 'NOTE: Admin trial notify DB tests skipped: ' . $e->getMessage() . "\n");
    }
}

if (!$dbRan) {
    fwrite(STDERR, "NOTE: Admin trial notify DB tests skipped (no local mysqli).\n");
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
