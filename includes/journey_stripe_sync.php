<?php
/**
 * Journey Premium Stripe webhook sync (Milestone 2).
 *
 * Authoritative entitlement updates for product_key = 'journey'.
 * Does not write users.subscription_status or calcforadvisors_* tables.
 */

if (defined('JOURNEY_STRIPE_SYNC_LOADED')) {
    return;
}
define('JOURNEY_STRIPE_SYNC_LOADED', 1);

require_once __DIR__ . '/journey_entitlement.php';

/**
 * Classify a Price ID into a product bucket for routing.
 * Journey recognition uses configured Journey Price IDs only.
 */
function journey_classify_price_id(?string $priceId): string
{
    if ($priceId === null || $priceId === '') {
        return 'unknown';
    }
    if (journey_is_journey_price_id($priceId)) {
        return 'journey';
    }
    $consumerMonthly = defined('STRIPE_PRICE_MONTHLY') ? (string) STRIPE_PRICE_MONTHLY : '';
    $consumerAnnual = defined('STRIPE_PRICE_ANNUAL') ? (string) STRIPE_PRICE_ANNUAL : '';
    if (($consumerMonthly !== '' && hash_equals($consumerMonthly, $priceId)) ||
        ($consumerAnnual !== '' && hash_equals($consumerAnnual, $priceId))) {
        return 'consumer';
    }
    $cfaMonthly = defined('CALCFORADVISORS_PRICE_MONTHLY') ? (string) CALCFORADVISORS_PRICE_MONTHLY : '';
    $cfaAnnual = defined('CALCFORADVISORS_PRICE_ANNUAL') ? (string) CALCFORADVISORS_PRICE_ANNUAL : '';
    if (($cfaMonthly !== '' && hash_equals($cfaMonthly, $priceId)) ||
        ($cfaAnnual !== '' && hash_equals($cfaAnnual, $priceId))) {
        return 'cfa';
    }
    return 'unknown';
}

/**
 * Extract primary Price ID from a Stripe Subscription-like object/array.
 *
 * @param object|array $subscription
 */
function journey_subscription_price_id($subscription): string
{
    $arr = journey_stripe_object_to_array($subscription);
    $items = $arr['items']['data'] ?? null;
    if (is_array($items) && isset($items[0])) {
        $price = $items[0]['price'] ?? null;
        if (is_array($price) && !empty($price['id'])) {
            return (string) $price['id'];
        }
        if (is_string($price) && $price !== '') {
            return $price;
        }
    }
    if (!empty($arr['plan']['id'])) {
        return (string) $arr['plan']['id'];
    }
    return (string) ($arr['stripe_price_id'] ?? $arr['price_id'] ?? '');
}

/**
 * @param object|array $obj
 * @return array<string,mixed>
 */
function journey_stripe_object_to_array($obj): array
{
    if (is_array($obj)) {
        return $obj;
    }
    if (is_object($obj)) {
        if (method_exists($obj, 'toArray')) {
            /** @var array<string,mixed> $a */
            $a = $obj->toArray();
            return $a;
        }
        $json = json_encode($obj);
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }
    return [];
}

/**
 * Resolve verified user_id for a Journey subscription, or null if unknown.
 *
 * @param object|array $subscription
 */
function journey_resolve_user_id_for_subscription(
    mysqli $conn,
    $subscription,
    ?int $userIdHint = null
): ?int {
    if ($userIdHint !== null && $userIdHint > 0) {
        return $userIdHint;
    }

    $arr = journey_stripe_object_to_array($subscription);
    $metaUser = $arr['metadata']['user_id'] ?? null;
    if (is_numeric($metaUser) && (int) $metaUser > 0) {
        return (int) $metaUser;
    }

    $subId = (string) ($arr['id'] ?? '');
    if ($subId !== '') {
        $stmt = $conn->prepare(
            'SELECT user_id FROM user_product_subscriptions WHERE stripe_subscription_id = ? LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $subId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && (int) $row['user_id'] > 0) {
                return (int) $row['user_id'];
            }
        }
    }

    return null;
}

/**
 * Upsert Journey row from a verified Stripe Subscription object/array.
 *
 * @param object|array $subscription
 * @return array{ok:bool,reason:string,entitlement_status?:string,access_allowed?:bool,product?:string}
 */
function journey_sync_subscription_row(
    mysqli $conn,
    $subscription,
    ?int $userIdHint = null,
    ?int $eventCreated = null,
    ?int $nowTs = null
): array {
    $arr = journey_stripe_object_to_array($subscription);
    $subId = (string) ($arr['id'] ?? '');
    if ($subId === '' || strpos($subId, 'sub_') !== 0) {
        return ['ok' => false, 'reason' => 'missing_subscription_id'];
    }

    $priceId = journey_subscription_price_id($arr);
    $product = journey_classify_price_id($priceId);
    if ($product !== 'journey') {
        return ['ok' => true, 'reason' => 'ignored_non_journey_price', 'product' => $product];
    }

    $userId = journey_resolve_user_id_for_subscription($conn, $arr, $userIdHint);
    if ($userId === null) {
        return ['ok' => false, 'reason' => 'missing_user_association'];
    }

    $stripeStatus = (string) ($arr['status'] ?? '');
    $cancelAtPeriodEnd = !empty($arr['cancel_at_period_end']);
    $periodStart = journey_parse_time_value($arr['current_period_start'] ?? null);
    $periodEnd = journey_parse_time_value($arr['current_period_end'] ?? null);
    $trialStart = journey_parse_time_value($arr['trial_start'] ?? null);
    $trialEnd = journey_parse_time_value($arr['trial_end'] ?? null);
    $canceledAt = journey_parse_time_value($arr['canceled_at'] ?? null);
    $endedAt = journey_parse_time_value($arr['ended_at'] ?? null);
    $customerId = $arr['customer'] ?? null;
    if (is_array($customerId)) {
        $customerId = $customerId['id'] ?? null;
    }
    $customerId = is_string($customerId) ? $customerId : '';

    $latestInvoice = $arr['latest_invoice'] ?? null;
    if (is_array($latestInvoice)) {
        $latestInvoice = $latestInvoice['id'] ?? null;
    }
    $latestInvoice = is_string($latestInvoice) ? $latestInvoice : null;

    $productId = '';
    $items = $arr['items']['data'] ?? [];
    if (is_array($items) && isset($items[0]['price']['product'])) {
        $p = $items[0]['price']['product'];
        $productId = is_string($p) ? $p : (string) (is_array($p) ? ($p['id'] ?? '') : '');
    }
    if ($productId === '') {
        $productId = journey_stripe_product_id();
    }

    $entitlementStatus = journey_normalize_entitlement_status(
        $stripeStatus,
        $cancelAtPeriodEnd,
        $periodEnd,
        $nowTs
    );
    $accessAllowed = journey_entitlement_allows_premium_access($entitlementStatus);

    $periodStartSql = $periodStart !== null ? gmdate('Y-m-d H:i:s', $periodStart) : null;
    $periodEndSql = $periodEnd !== null ? gmdate('Y-m-d H:i:s', $periodEnd) : null;
    $trialStartSql = $trialStart !== null ? gmdate('Y-m-d H:i:s', $trialStart) : null;
    $trialEndSql = $trialEnd !== null ? gmdate('Y-m-d H:i:s', $trialEnd) : null;
    $canceledSql = $canceledAt !== null ? gmdate('Y-m-d H:i:s', $canceledAt) : null;
    $endedSql = $endedAt !== null ? gmdate('Y-m-d H:i:s', $endedAt) : null;
    if ($entitlementStatus === 'canceled' || $entitlementStatus === 'expired') {
        if ($endedSql === null) {
            $endedSql = gmdate('Y-m-d H:i:s', $nowTs ?? time());
        }
    }

    $cancelFlag = $cancelAtPeriodEnd ? 1 : 0;
    $productKey = JOURNEY_PRODUCT_KEY;
    $eventCreatedVal = $eventCreated;

    $esc = static function (?string $v) use ($conn): string {
        if ($v === null) {
            return 'NULL';
        }
        return "'" . $conn->real_escape_string($v) . "'";
    };
    $eventSql = $eventCreatedVal === null ? 'NULL' : (string) (int) $eventCreatedVal;
    $invoiceSql = $latestInvoice === null ? 'NULL' : ("'" . $conn->real_escape_string($latestInvoice) . "'");

    $insert = sprintf(
        "INSERT INTO user_product_subscriptions (
            user_id, product_key, stripe_customer_id, stripe_subscription_id,
            stripe_price_id, stripe_product_id, stripe_status, entitlement_status,
            trial_start, trial_end, current_period_start, current_period_end,
            cancel_at_period_end, canceled_at, ended_at, latest_invoice_id,
            last_stripe_event_created
        ) VALUES (
            %d, '%s', '%s', '%s',
            '%s', '%s', '%s', '%s',
            %s, %s, %s, %s,
            %d, %s, %s, %s,
            %s
        )
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            product_key = VALUES(product_key),
            stripe_customer_id = VALUES(stripe_customer_id),
            stripe_price_id = VALUES(stripe_price_id),
            stripe_product_id = VALUES(stripe_product_id),
            stripe_status = VALUES(stripe_status),
            entitlement_status = VALUES(entitlement_status),
            trial_start = VALUES(trial_start),
            trial_end = VALUES(trial_end),
            current_period_start = VALUES(current_period_start),
            current_period_end = VALUES(current_period_end),
            cancel_at_period_end = VALUES(cancel_at_period_end),
            canceled_at = VALUES(canceled_at),
            ended_at = VALUES(ended_at),
            latest_invoice_id = VALUES(latest_invoice_id),
            last_stripe_event_created = VALUES(last_stripe_event_created),
            updated_at = CURRENT_TIMESTAMP",
        $userId,
        $conn->real_escape_string($productKey),
        $conn->real_escape_string($customerId),
        $conn->real_escape_string($subId),
        $conn->real_escape_string($priceId),
        $conn->real_escape_string($productId),
        $conn->real_escape_string($stripeStatus),
        $conn->real_escape_string($entitlementStatus),
        $esc($trialStartSql),
        $esc($trialEndSql),
        $esc($periodStartSql),
        $esc($periodEndSql),
        $cancelFlag,
        $esc($canceledSql),
        $esc($endedSql),
        $invoiceSql,
        $eventSql
    );

    try {
        if (!$conn->query($insert)) {
            return ['ok' => false, 'reason' => 'upsert_failed'];
        }
    } catch (Throwable $e) {
        error_log('journey_sync_subscription_row: upsert exception');
        return ['ok' => false, 'reason' => 'upsert_exception'];
    }

    return [
        'ok' => true,
        'reason' => 'synced',
        'entitlement_status' => $entitlementStatus,
        'access_allowed' => $accessAllowed,
        'product' => 'journey',
    ];
}

/**
 * Process a verified Stripe event for Journey entitlement.
 *
 * @param object|array $event Stripe Event or fixture array with type/data/id/created/livemode
 * @param array{
 *   retrieve_subscription?: callable(string): (object|array|null),
 *   retrieve_checkout_session?: callable(string): (object|array|null),
 *   now?: int
 * } $options
 * @return array{http_status:int,result:string,detail:string}
 */
function journey_process_verified_stripe_event(mysqli $conn, $event, array $options = []): array
{
    $arr = journey_stripe_object_to_array($event);
    $eventId = (string) ($arr['id'] ?? '');
    $type = (string) ($arr['type'] ?? '');
    $created = isset($arr['created']) ? (int) $arr['created'] : null;
    $livemode = array_key_exists('livemode', $arr) ? (bool) $arr['livemode'] : null;
    $now = isset($options['now']) ? (int) $options['now'] : time();

    if ($eventId === '' || $type === '') {
        return ['http_status' => 400, 'result' => 'invalid_event', 'detail' => 'missing_id_or_type'];
    }

    $claim = journey_webhook_event_claim($conn, $eventId, $type, $created, $livemode);
    if ($claim === 'already_processed' || $claim === 'in_progress') {
        return ['http_status' => 200, 'result' => $claim, 'detail' => 'idempotent_skip'];
    }
    if ($claim === 'error') {
        return ['http_status' => 500, 'result' => 'claim_error', 'detail' => 'claim_failed'];
    }
    // claimed | reclaimed → process

    journey_webhook_event_mark($conn, $eventId, 'processing');

    $retrieveSub = $options['retrieve_subscription'] ?? null;
    $retrieveSession = $options['retrieve_checkout_session'] ?? null;

    try {
        $object = $arr['data']['object'] ?? null;
        if (!is_array($object) && !is_object($object)) {
            journey_webhook_event_mark($conn, $eventId, 'failed', 'missing_data_object');
            return ['http_status' => 400, 'result' => 'failed', 'detail' => 'missing_data_object'];
        }

        $syncResult = null;
        $userHint = null;

        switch ($type) {
            case 'checkout.session.completed':
                $session = journey_stripe_object_to_array($object);
                if (($session['mode'] ?? '') !== 'subscription') {
                    journey_webhook_event_mark($conn, $eventId, 'processed');
                    return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'non_subscription_checkout'];
                }
                if (!empty($session['client_reference_id']) && is_numeric($session['client_reference_id'])) {
                    $userHint = (int) $session['client_reference_id'];
                } elseif (!empty($session['metadata']['user_id']) && is_numeric($session['metadata']['user_id'])) {
                    $userHint = (int) $session['metadata']['user_id'];
                }
                $subId = $session['subscription'] ?? null;
                if (is_array($subId)) {
                    $subId = $subId['id'] ?? null;
                }
                if (!is_string($subId) || $subId === '') {
                    // May need expand via retrieve session
                    if (is_callable($retrieveSession) && !empty($session['id'])) {
                        $full = $retrieveSession((string) $session['id']);
                        $fullArr = journey_stripe_object_to_array($full);
                        $subId = $fullArr['subscription'] ?? null;
                        if (is_array($subId)) {
                            $subId = $subId['id'] ?? null;
                        }
                    }
                }
                if (!is_string($subId) || $subId === '') {
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'checkout_missing_subscription');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'checkout_missing_subscription'];
                }
                $subscription = is_callable($retrieveSub) ? $retrieveSub($subId) : null;
                if ($subscription === null) {
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'subscription_retrieve_failed');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'subscription_retrieve_failed'];
                }
                $conn->begin_transaction();
                try {
                    $syncResult = journey_sync_subscription_row($conn, $subscription, $userHint, $created, $now);
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') === 'missing_user_association') {
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', 'missing_user_association');
                        return ['http_status' => 200, 'result' => 'unresolved', 'detail' => 'missing_user_association'];
                    }
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') !== 'ignored_non_journey_price') {
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', substr((string) ($syncResult['reason'] ?? 'sync_failed'), 0, 200));
                        return ['http_status' => 500, 'result' => 'failed', 'detail' => (string) ($syncResult['reason'] ?? 'sync_failed')];
                    }
                    journey_webhook_event_mark($conn, $eventId, 'processed');
                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'tx_exception');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'tx_exception'];
                }
                return [
                    'http_status' => 200,
                    'result' => 'processed',
                    'detail' => (string) ($syncResult['reason'] ?? 'synced'),
                ];

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
            case 'customer.subscription.paused':
            case 'customer.subscription.resumed':
                $subscription = $object;
                // Prefer live retrieve when callable (authoritative), else use event object.
                $subId = (string) (journey_stripe_object_to_array($object)['id'] ?? '');
                if (is_callable($retrieveSub) && $subId !== '' && $type !== 'customer.subscription.deleted') {
                    $fetched = $retrieveSub($subId);
                    if ($fetched !== null) {
                        $subscription = $fetched;
                    }
                }
                if (!empty(journey_stripe_object_to_array($subscription)['metadata']['user_id']) &&
                    is_numeric(journey_stripe_object_to_array($subscription)['metadata']['user_id'])) {
                    $userHint = (int) journey_stripe_object_to_array($subscription)['metadata']['user_id'];
                }
                $conn->begin_transaction();
                try {
                    $syncResult = journey_sync_subscription_row($conn, $subscription, $userHint, $created, $now);
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') === 'missing_user_association') {
                        // If price is not journey, ignore; if journey, unresolved.
                        $priceId = journey_subscription_price_id($subscription);
                        if (journey_classify_price_id($priceId) !== 'journey') {
                            journey_webhook_event_mark($conn, $eventId, 'processed');
                            $conn->commit();
                            return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'non_journey'];
                        }
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', 'missing_user_association');
                        return ['http_status' => 200, 'result' => 'unresolved', 'detail' => 'missing_user_association'];
                    }
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') !== 'ignored_non_journey_price') {
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', substr((string) ($syncResult['reason'] ?? 'sync_failed'), 0, 200));
                        return ['http_status' => 500, 'result' => 'failed', 'detail' => (string) ($syncResult['reason'] ?? 'sync_failed')];
                    }
                    journey_webhook_event_mark($conn, $eventId, 'processed');
                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'tx_exception');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'tx_exception'];
                }
                return [
                    'http_status' => 200,
                    'result' => 'processed',
                    'detail' => (string) ($syncResult['reason'] ?? 'synced'),
                ];

            case 'invoice.paid':
            case 'invoice.payment_failed':
            case 'invoice.payment_action_required':
                $invoice = journey_stripe_object_to_array($object);
                $subId = $invoice['subscription'] ?? null;
                if (is_array($subId)) {
                    $subId = $subId['id'] ?? null;
                }
                if (!is_string($subId) || $subId === '') {
                    journey_webhook_event_mark($conn, $eventId, 'processed');
                    return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'invoice_without_subscription'];
                }
                $subscription = is_callable($retrieveSub) ? $retrieveSub($subId) : null;
                if ($subscription === null) {
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'subscription_retrieve_failed');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'subscription_retrieve_failed'];
                }
                $conn->begin_transaction();
                try {
                    $syncResult = journey_sync_subscription_row($conn, $subscription, null, $created, $now);
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') === 'missing_user_association') {
                        $priceId = journey_subscription_price_id($subscription);
                        if (journey_classify_price_id($priceId) !== 'journey') {
                            journey_webhook_event_mark($conn, $eventId, 'processed');
                            $conn->commit();
                            return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'non_journey'];
                        }
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', 'missing_user_association');
                        return ['http_status' => 200, 'result' => 'unresolved', 'detail' => 'missing_user_association'];
                    }
                    if (!$syncResult['ok'] && ($syncResult['reason'] ?? '') !== 'ignored_non_journey_price') {
                        $conn->rollback();
                        journey_webhook_event_mark($conn, $eventId, 'failed', substr((string) ($syncResult['reason'] ?? 'sync_failed'), 0, 200));
                        return ['http_status' => 500, 'result' => 'failed', 'detail' => (string) ($syncResult['reason'] ?? 'sync_failed')];
                    }
                    journey_webhook_event_mark($conn, $eventId, 'processed');
                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    journey_webhook_event_mark($conn, $eventId, 'failed', 'tx_exception');
                    return ['http_status' => 500, 'result' => 'failed', 'detail' => 'tx_exception'];
                }
                return [
                    'http_status' => 200,
                    'result' => 'processed',
                    'detail' => (string) ($syncResult['reason'] ?? 'synced'),
                ];

            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':
                // Deferred for card Checkout; acknowledge without entitlement changes.
                journey_webhook_event_mark($conn, $eventId, 'processed');
                return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'async_checkout_deferred'];

            default:
                journey_webhook_event_mark($conn, $eventId, 'processed');
                return ['http_status' => 200, 'result' => 'ignored', 'detail' => 'unhandled_event_type'];
        }
    } catch (Throwable $e) {
        error_log('journey_process_verified_stripe_event: unexpected failure type=' . $type);
        journey_webhook_event_mark($conn, $eventId, 'failed', 'unexpected_exception');
        return ['http_status' => 500, 'result' => 'failed', 'detail' => 'unexpected_exception'];
    }
}
