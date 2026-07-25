<?php
/**
 * Journey Premium Milestone 2 — webhook sync tests.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone2.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_stripe_sync.php';

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

journey_price_id_overrides_set([
    'monthly' => 'price_journey_monthly_fixture',
    'annual' => 'price_journey_annual_fixture',
]);

// Ensure consumer/CFA constants exist as non-journey fixtures for classification tests.
if (!defined('STRIPE_PRICE_MONTHLY')) {
    define('STRIPE_PRICE_MONTHLY', 'price_consumer_monthly_fixture');
}
if (!defined('STRIPE_PRICE_ANNUAL')) {
    define('STRIPE_PRICE_ANNUAL', 'price_consumer_annual_fixture');
}
if (!defined('CALCFORADVISORS_PRICE_MONTHLY')) {
    define('CALCFORADVISORS_PRICE_MONTHLY', 'price_cfa_monthly_fixture');
}
if (!defined('CALCFORADVISORS_PRICE_ANNUAL')) {
    define('CALCFORADVISORS_PRICE_ANNUAL', 'price_cfa_annual_fixture');
}

expect2('journey monthly routes', journey_classify_price_id('price_journey_monthly_fixture') === 'journey');
expect2('journey annual routes', journey_classify_price_id('price_journey_annual_fixture') === 'journey');
expect2('consumer rejected for journey', journey_classify_price_id('price_consumer_monthly_fixture') === 'consumer');
expect2('cfa rejected for journey', journey_classify_price_id('price_cfa_monthly_fixture') === 'cfa');
expect2('unknown price', journey_classify_price_id('price_unknown_xyz') === 'unknown');
expect2('metadata alone cannot classify', journey_classify_price_id(null) === 'unknown');

$now = 1_700_000_000;
$future = $now + 86400 * 14;
$past = $now - 100;

function makeSub(array $over): array
{
    $base = [
        'id' => 'sub_fixture_1',
        'object' => 'subscription',
        'status' => 'active',
        'cancel_at_period_end' => false,
        'current_period_start' => $GLOBALS['now'] - 100,
        'current_period_end' => $GLOBALS['future'],
        'trial_start' => null,
        'trial_end' => null,
        'canceled_at' => null,
        'ended_at' => null,
        'customer' => 'cus_fixture_1',
        'latest_invoice' => 'in_fixture_1',
        'metadata' => ['user_id' => '42', 'product' => 'journey'],
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
    return array_replace_recursive($base, $over);
}

$eval = journey_evaluate_subscription_entitlement(makeSub(['status' => 'trialing', 'trial_end' => $future]), $now);
expect2('trialing grants', $eval['accessAllowed'] === true);

$eval = journey_evaluate_subscription_entitlement(makeSub(['status' => 'active']), $now);
expect2('active grants', $eval['accessAllowed'] === true);

$eval = journey_evaluate_subscription_entitlement(makeSub([
    'status' => 'active',
    'cancel_at_period_end' => true,
    'current_period_end' => $future,
]), $now);
expect2('cancel grace grants', $eval['accessAllowed'] === true && $eval['entitlementStatus'] === 'canceled_grace');

$eval = journey_evaluate_subscription_entitlement(makeSub([
    'status' => 'canceled',
    'current_period_end' => $past,
]), $now);
expect2('canceled expired denies', $eval['accessAllowed'] === false);

foreach (['past_due', 'unpaid', 'incomplete', 'incomplete_expired', 'paused'] as $st) {
    $eval = journey_evaluate_subscription_entitlement(makeSub(['status' => $st]), $now);
    expect2($st . ' denies', $eval['accessAllowed'] === false);
}

$dbRan = false;
try {
    require_once $root . '/includes/db_config.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbRan = true;
        $up = file_get_contents($root . '/sql/migrations/20260725_001_journey_premium_m1_up.sql');
        if (is_string($up)) {
            if ($conn->multi_query($up)) {
                do {
                    if ($r = $conn->store_result()) {
                        $r->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            }
        }

        // Strict mysqli duplicate claim
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $evtStrict = 'evt_m2_strict_' . bin2hex(random_bytes(4));
        $c1 = journey_webhook_event_claim($conn, $evtStrict, 'customer.subscription.updated', $now, false);
        expect2('strict claim first', $c1 === 'claimed', $c1);
        $c2 = journey_webhook_event_claim($conn, $evtStrict, 'customer.subscription.updated', $now, false);
        expect2(
            'strict claim duplicate no throw',
            in_array($c2, ['already_processed', 'in_progress', 'duplicate'], true),
            $c2
        );
        journey_webhook_event_mark($conn, $evtStrict, 'processed');
        $c3 = journey_webhook_event_claim($conn, $evtStrict, 'customer.subscription.updated', $now, false);
        expect2('strict already_processed', $c3 === 'already_processed', $c3);

        // Failed reclaim
        $evtFail = 'evt_m2_fail_' . bin2hex(random_bytes(4));
        journey_webhook_event_claim($conn, $evtFail, 'invoice.paid', $now, false);
        journey_webhook_event_mark($conn, $evtFail, 'failed', 'synthetic');
        $reclaim = journey_webhook_event_claim($conn, $evtFail, 'invoice.paid', $now, false);
        expect2('failed event reclaimed', $reclaim === 'reclaimed', $reclaim);

        mysqli_report(MYSQLI_REPORT_OFF);

        $uid = 920001;
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_m2_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_m2_%'");

        $retrieveSub = static function (string $id) use ($now, $future) {
            return makeSub([
                'id' => $id,
                'status' => 'trialing',
                'trial_end' => $future,
                'current_period_end' => $future,
                'metadata' => ['user_id' => '920001', 'product' => 'journey'],
            ]);
        };

        // Unknown price does not grant
        $unknownSub = makeSub([
            'id' => 'sub_m2_unknown',
            'items' => ['data' => [['price' => ['id' => 'price_unknown_xyz', 'product' => 'prod_x']]]],
            'metadata' => ['user_id' => '920001', 'product' => 'journey'],
        ]);
        $syncUnknown = journey_sync_subscription_row($conn, $unknownSub, $uid, $now, $now);
        expect2('unknown price ignored', ($syncUnknown['reason'] ?? '') === 'ignored_non_journey_price');
        $cnt = (int) $conn->query("SELECT COUNT(*) c FROM user_product_subscriptions WHERE stripe_subscription_id='sub_m2_unknown'")->fetch_assoc()['c'];
        expect2('unknown price no row', $cnt === 0);

        // Consumer / CFA prices do not create journey rows
        foreach ([
            ['sub_m2_consumer', 'price_consumer_monthly_fixture'],
            ['sub_m2_cfa', 'price_cfa_monthly_fixture'],
        ] as $pair) {
            $s = makeSub([
                'id' => $pair[0],
                'items' => ['data' => [['price' => ['id' => $pair[1], 'product' => 'prod_x']]]],
                'metadata' => ['user_id' => (string) $uid],
            ]);
            $r = journey_sync_subscription_row($conn, $s, $uid, $now, $now);
            expect2($pair[0] . ' ignored', ($r['reason'] ?? '') === 'ignored_non_journey_price');
        }

        // Journey sync + upsert
        $subId = 'sub_m2_journey_' . bin2hex(random_bytes(3));
        $r1 = journey_sync_subscription_row($conn, makeSub([
            'id' => $subId,
            'status' => 'trialing',
            'trial_end' => $future,
            'metadata' => ['user_id' => (string) $uid],
        ]), $uid, $now, $now);
        expect2('journey sync ok', ($r1['ok'] ?? false) && ($r1['entitlement_status'] ?? '') === 'trialing', json_encode($r1));

        $r2 = journey_sync_subscription_row($conn, makeSub([
            'id' => $subId,
            'status' => 'active',
            'metadata' => ['user_id' => (string) $uid],
        ]), $uid, $now + 1, $now);
        expect2('upsert not duplicate', ($r2['ok'] ?? false) && ($r2['entitlement_status'] ?? '') === 'active');
        $rows = (int) $conn->query("SELECT COUNT(*) c FROM user_product_subscriptions WHERE stripe_subscription_id='{$subId}'")->fetch_assoc()['c'];
        expect2('single row after upsert', $rows === 1);

        // Missing user association
        $rMiss = journey_sync_subscription_row($conn, makeSub([
            'id' => 'sub_m2_nouser',
            'metadata' => [],
        ]), null, $now, $now);
        expect2('missing user fails', ($rMiss['ok'] ?? true) === false && ($rMiss['reason'] ?? '') === 'missing_user_association');

        // Event processing: subscription.updated
        $evtUp = 'evt_m2_upd_' . bin2hex(random_bytes(3));
        $eventUp = [
            'id' => $evtUp,
            'type' => 'customer.subscription.updated',
            'created' => $now,
            'livemode' => false,
            'data' => [
                'object' => makeSub([
                    'id' => $subId,
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                    'current_period_end' => $future,
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ];
        $proc = journey_process_verified_stripe_event($conn, $eventUp, [
            'retrieve_subscription' => static function (string $id) use ($subId, $uid, $future) {
                return makeSub([
                    'id' => $subId,
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                    'current_period_end' => $future,
                    'metadata' => ['user_id' => (string) $uid],
                ]);
            },
            'now' => $now,
        ]);
        expect2('subscription.updated processed', ($proc['http_status'] ?? 0) === 200 && ($proc['result'] ?? '') === 'processed', json_encode($proc));
        $status = $conn->query("SELECT entitlement_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subId}'")->fetch_assoc();
        expect2('grace entitlement stored', ($status['entitlement_status'] ?? '') === 'canceled_grace');

        $evtRow = $conn->query("SELECT processing_status, processed_at, attempts FROM stripe_webhook_events WHERE stripe_event_id='{$evtUp}'")->fetch_assoc();
        expect2('processed_at set', ($evtRow['processing_status'] ?? '') === 'processed' && !empty($evtRow['processed_at']));

        // Duplicate event
        $procDup = journey_process_verified_stripe_event($conn, $eventUp, [
            'retrieve_subscription' => $retrieveSub,
            'now' => $now,
        ]);
        expect2('duplicate event 200', ($procDup['http_status'] ?? 0) === 200, json_encode($procDup));

        // subscription.deleted
        $evtDel = 'evt_m2_del_' . bin2hex(random_bytes(3));
        $procDel = journey_process_verified_stripe_event($conn, [
            'id' => $evtDel,
            'type' => 'customer.subscription.deleted',
            'created' => $now,
            'livemode' => false,
            'data' => [
                'object' => makeSub([
                    'id' => $subId,
                    'status' => 'canceled',
                    'current_period_end' => $past,
                    'canceled_at' => $now,
                    'ended_at' => $now,
                    'metadata' => ['user_id' => (string) $uid],
                ]),
            ],
        ], ['now' => $now]);
        expect2('deleted processed', ($procDel['result'] ?? '') === 'processed', json_encode($procDel));
        $afterDel = $conn->query("SELECT entitlement_status, stripe_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subId}'")->fetch_assoc();
        expect2(
            'deleted keeps history without access',
            in_array($afterDel['entitlement_status'] ?? '', ['canceled', 'expired'], true),
            json_encode($afterDel)
        );
        $stillThere = (int) $conn->query("SELECT COUNT(*) c FROM user_product_subscriptions WHERE stripe_subscription_id='{$subId}'")->fetch_assoc()['c'];
        expect2('history row retained', $stillThere === 1);

        // invoice.paid / payment_failed use retrieved subscription
        $evtPaid = 'evt_m2_paid_' . bin2hex(random_bytes(3));
        $procPaid = journey_process_verified_stripe_event($conn, [
            'id' => $evtPaid,
            'type' => 'invoice.paid',
            'created' => $now,
            'data' => ['object' => ['id' => 'in_x', 'subscription' => $subId]],
        ], [
            'retrieve_subscription' => static function (string $id) use ($subId, $uid, $future) {
                return makeSub([
                    'id' => $subId,
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'current_period_end' => $future,
                    'metadata' => ['user_id' => (string) $uid],
                ]);
            },
            'now' => $now,
        ]);
        expect2('invoice.paid sync', ($procPaid['result'] ?? '') === 'processed', json_encode($procPaid));

        $evtFailPay = 'evt_m2_pf_' . bin2hex(random_bytes(3));
        $procFailPay = journey_process_verified_stripe_event($conn, [
            'id' => $evtFailPay,
            'type' => 'invoice.payment_failed',
            'created' => $now,
            'data' => ['object' => ['id' => 'in_y', 'subscription' => $subId]],
        ], [
            'retrieve_subscription' => static function (string $id) use ($subId, $uid, $future) {
                // Stripe still reports past_due — mapping denies access, but uses object state.
                return makeSub([
                    'id' => $subId,
                    'status' => 'past_due',
                    'current_period_end' => $future,
                    'metadata' => ['user_id' => (string) $uid],
                ]);
            },
            'now' => $now,
        ]);
        expect2('invoice.payment_failed uses sub state', ($procFailPay['result'] ?? '') === 'processed');
        $pd = $conn->query("SELECT entitlement_status FROM user_product_subscriptions WHERE stripe_subscription_id='{$subId}'")->fetch_assoc();
        expect2('past_due stored', ($pd['entitlement_status'] ?? '') === 'past_due');

        // checkout.session.completed
        $subCheckout = 'sub_m2_co_' . bin2hex(random_bytes(3));
        $evtCo = 'evt_m2_co_' . bin2hex(random_bytes(3));
        $procCo = journey_process_verified_stripe_event($conn, [
            'id' => $evtCo,
            'type' => 'checkout.session.completed',
            'created' => $now,
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'mode' => 'subscription',
                    'client_reference_id' => (string) $uid,
                    'subscription' => $subCheckout,
                    'metadata' => ['product' => 'journey'],
                ],
            ],
        ], [
            'retrieve_subscription' => static function (string $id) use ($subCheckout, $uid, $future) {
                return makeSub([
                    'id' => $subCheckout,
                    'status' => 'trialing',
                    'trial_end' => $future,
                    'metadata' => ['user_id' => (string) $uid],
                ]);
            },
            'now' => $now,
        ]);
        expect2('checkout.session.completed', ($procCo['result'] ?? '') === 'processed', json_encode($procCo));

        // Missing user on journey checkout
        $evtNoUser = 'evt_m2_nouser_' . bin2hex(random_bytes(3));
        $procNoUser = journey_process_verified_stripe_event($conn, [
            'id' => $evtNoUser,
            'type' => 'checkout.session.completed',
            'created' => $now,
            'data' => [
                'object' => [
                    'id' => 'cs_test_2',
                    'mode' => 'subscription',
                    'subscription' => 'sub_m2_orphan',
                ],
            ],
        ], [
            'retrieve_subscription' => static function (string $id) use ($future) {
                return makeSub([
                    'id' => $id,
                    'status' => 'trialing',
                    'trial_end' => $future,
                    'metadata' => [],
                ]);
            },
            'now' => $now,
        ]);
        expect2(
            'missing user unresolved no grant',
            ($procNoUser['result'] ?? '') === 'unresolved',
            json_encode($procNoUser)
        );
        $orphan = (int) $conn->query("SELECT COUNT(*) c FROM user_product_subscriptions WHERE stripe_subscription_id='sub_m2_orphan'")->fetch_assoc()['c'];
        expect2('orphan not inserted', $orphan === 0);

        // Failed event diagnostic + retry
        $evtRetry = 'evt_m2_retry_' . bin2hex(random_bytes(3));
        $bad = journey_process_verified_stripe_event($conn, [
            'id' => $evtRetry,
            'type' => 'invoice.paid',
            'created' => $now,
            'data' => ['object' => ['id' => 'in_z', 'subscription' => 'sub_missing_retrieve']],
        ], [
            'retrieve_subscription' => static function (string $id) {
                return null;
            },
            'now' => $now,
        ]);
        expect2('failed retrieve records failure', ($bad['result'] ?? '') === 'failed');
        $failRow = $conn->query("SELECT processing_status, last_error FROM stripe_webhook_events WHERE stripe_event_id='{$evtRetry}'")->fetch_assoc();
        expect2('failed diagnostic stored', ($failRow['processing_status'] ?? '') === 'failed' && !empty($failRow['last_error']));

        // Cleanup all m2 fixtures
        $conn->query("DELETE FROM user_product_subscriptions WHERE user_id = {$uid} OR stripe_subscription_id LIKE 'sub_m2_%'");
        $conn->query("DELETE FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_m2_%'");
        $left = (int) $conn->query("SELECT COUNT(*) c FROM user_product_subscriptions WHERE user_id={$uid} OR stripe_subscription_id LIKE 'sub_m2_%'")->fetch_assoc()['c']
            + (int) $conn->query("SELECT COUNT(*) c FROM stripe_webhook_events WHERE stripe_event_id LIKE 'evt_m2_%'")->fetch_assoc()['c'];
        expect2('temp records removed', $left === 0);
    }
} catch (Throwable $e) {
    if ($dbRan) {
        expect2('db tests completed without exception', false, $e->getMessage());
    } else {
        fwrite(STDERR, 'NOTE: Milestone 2 DB tests skipped: ' . $e->getMessage() . "\n");
    }
}

if (!$dbRan) {
    fwrite(STDERR, "NOTE: Milestone 2 DB tests skipped (no local mysqli).\n");
}

journey_price_id_overrides_set(null);

echo json_encode([
    'passed' => count($passed),
    'failed' => count($failed),
    'failures' => $failed,
    'db_tests_ran' => $dbRan,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit(count($failed) > 0 ? 1 : 0);
