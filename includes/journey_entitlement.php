<?php
/**
 * Journey Premium entitlement domain helpers (Milestone 1).
 *
 * Pure mapping + config recognition. Does not gate public Journey phases.
 * Does not write users.subscription_status or calcforadvisors tables.
 *
 * Authoritative long-term source of truth for access will be
 * user_product_subscriptions rows synced by webhooks (Milestone 2+),
 * not checkout success.php alone.
 */

if (defined('JOURNEY_ENTITLEMENT_LOADED')) {
    return;
}
define('JOURNEY_ENTITLEMENT_LOADED', 1);

require_once __DIR__ . '/stripe_config.php';

/** Product key for Retirement Planning Journey Premium. */
const JOURNEY_PRODUCT_KEY = 'journey';

/**
 * Optional in-process Price ID overrides for tests (never used for production config).
 * @var array{monthly?:string,annual?:string}|null
 */
$GLOBALS['_journey_price_id_overrides'] = $GLOBALS['_journey_price_id_overrides'] ?? null;

/**
 * @param array{monthly?:string,annual?:string}|null $overrides
 */
function journey_price_id_overrides_set(?array $overrides): void
{
    $GLOBALS['_journey_price_id_overrides'] = $overrides;
}

/**
 * Return configured Journey monthly Price ID, or empty string if unset.
 */
function journey_stripe_monthly_price_id(): string
{
    $overrides = $GLOBALS['_journey_price_id_overrides'] ?? null;
    if (is_array($overrides) && !empty($overrides['monthly'])) {
        return (string) $overrides['monthly'];
    }
    return defined('JOURNEY_STRIPE_MONTHLY_PRICE_ID') ? (string) JOURNEY_STRIPE_MONTHLY_PRICE_ID : '';
}

/**
 * Return configured Journey annual Price ID, or empty string if unset (optional).
 */
function journey_stripe_annual_price_id(): string
{
    $overrides = $GLOBALS['_journey_price_id_overrides'] ?? null;
    if (is_array($overrides) && array_key_exists('annual', $overrides)) {
        return (string) ($overrides['annual'] ?? '');
    }
    return defined('JOURNEY_STRIPE_ANNUAL_PRICE_ID') ? (string) JOURNEY_STRIPE_ANNUAL_PRICE_ID : '';
}

/**
 * Return configured Journey Product ID, or empty string if unset (optional).
 */
function journey_stripe_product_id(): string
{
    return defined('JOURNEY_STRIPE_PRODUCT_ID') ? (string) JOURNEY_STRIPE_PRODUCT_ID : '';
}

/**
 * True when monthly Price ID is present and looks like a Stripe Price id.
 * Required before future Checkout may create a Journey subscription Session.
 */
function journey_stripe_checkout_config_ready(): bool
{
    $monthly = journey_stripe_monthly_price_id();
    if ($monthly === '' || strpos($monthly, 'price_') !== 0) {
        return false;
    }
    if (in_array($monthly, ['price_xxx', 'price_replace_me', 'price_placeholder'], true)) {
        return false;
    }
    return true;
}

/**
 * Exact Price-ID recognition for Journey (primary routing key).
 * Metadata must not be used as the sole identifier.
 */
function journey_is_journey_price_id(?string $priceId): bool
{
    if ($priceId === null || $priceId === '') {
        return false;
    }
    $monthly = journey_stripe_monthly_price_id();
    $annual = journey_stripe_annual_price_id();
    if ($monthly !== '' && hash_equals($monthly, $priceId)) {
        return true;
    }
    if ($annual !== '' && hash_equals($annual, $priceId)) {
        return true;
    }
    return false;
}

/**
 * Map a Price ID to a product_key when recognized; null otherwise.
 */
function journey_product_key_for_price_id(?string $priceId): ?string
{
    if (journey_is_journey_price_id($priceId)) {
        return JOURNEY_PRODUCT_KEY;
    }
    return null;
}

/**
 * Normalize Stripe subscription status + period flags into an app entitlement_status.
 *
 * @param string      $stripeStatus           Raw Stripe subscription.status
 * @param bool        $cancelAtPeriodEnd      cancel_at_period_end
 * @param int|null    $currentPeriodEndTs     Unix timestamp (UTC)
 * @param int|null    $nowTs                  Override "now" for tests
 */
function journey_normalize_entitlement_status(
    string $stripeStatus,
    bool $cancelAtPeriodEnd = false,
    ?int $currentPeriodEndTs = null,
    ?int $nowTs = null
): string {
    $now = $nowTs ?? time();
    $status = strtolower(trim($stripeStatus));

    if ($status === 'trialing') {
        if ($cancelAtPeriodEnd && $currentPeriodEndTs !== null && $currentPeriodEndTs <= $now) {
            return 'expired';
        }
        return $cancelAtPeriodEnd ? 'canceled_grace' : 'trialing';
    }

    if ($status === 'active') {
        if ($cancelAtPeriodEnd) {
            if ($currentPeriodEndTs !== null && $currentPeriodEndTs <= $now) {
                return 'expired';
            }
            return 'canceled_grace';
        }
        return 'active';
    }

    if ($status === 'past_due') {
        return 'past_due';
    }
    if ($status === 'unpaid') {
        return 'unpaid';
    }
    if ($status === 'incomplete') {
        return 'incomplete';
    }
    if ($status === 'incomplete_expired') {
        return 'incomplete_expired';
    }
    if ($status === 'paused') {
        return 'paused';
    }
    if ($status === 'canceled') {
        if ($currentPeriodEndTs !== null && $currentPeriodEndTs > $now) {
            // Stripe may still report canceled while period access remains in some edge cases;
            // prefer period end when provided.
            return 'canceled_grace';
        }
        return 'canceled';
    }

    return 'none';
}

/**
 * Whether future Premium capabilities should be allowed for this entitlement_status.
 * Free Journey phases are always available separately and are not gated here.
 */
function journey_entitlement_allows_premium_access(string $entitlementStatus): bool
{
    return in_array($entitlementStatus, ['trialing', 'active', 'canceled_grace'], true);
}

/**
 * Evaluate a Stripe-like subscription snapshot into a Journey entitlement result.
 *
 * Expected keys (subset ok): stripe_status|status, cancel_at_period_end,
 * current_period_end (unix int or ISO8601 string), trial_end, price_id, product_key.
 *
 * @param array<string,mixed> $subscription
 * @return array{
 *   productKey: string,
 *   stripeStatus: string,
 *   entitlementStatus: string,
 *   accessAllowed: bool,
 *   accessThrough: string|null,
 *   reason: string,
 *   priceId: string,
 *   isJourneyPrice: bool
 * }
 */
function journey_evaluate_subscription_entitlement(array $subscription, ?int $nowTs = null): array
{
    $now = $nowTs ?? time();
    $stripeStatus = (string) ($subscription['stripe_status'] ?? $subscription['status'] ?? '');
    $cancelAtPeriodEnd = !empty($subscription['cancel_at_period_end']);
    $priceId = (string) ($subscription['stripe_price_id'] ?? $subscription['price_id'] ?? '');
    $productKey = (string) ($subscription['product_key'] ?? JOURNEY_PRODUCT_KEY);

    $periodEndTs = journey_parse_time_value($subscription['current_period_end'] ?? null);
    $trialEndTs = journey_parse_time_value($subscription['trial_end'] ?? $subscription['trial_ends_at'] ?? null);

    $entitlementStatus = journey_normalize_entitlement_status(
        $stripeStatus,
        $cancelAtPeriodEnd,
        $periodEndTs,
        $now
    );

    $accessAllowed = journey_entitlement_allows_premium_access($entitlementStatus);
    $accessThroughTs = null;
    if ($accessAllowed) {
        if ($entitlementStatus === 'trialing' && $trialEndTs !== null) {
            $accessThroughTs = $trialEndTs;
        }
        if ($periodEndTs !== null) {
            $accessThroughTs = $periodEndTs;
        }
    }

    $reason = $entitlementStatus;
    if ($entitlementStatus === 'canceled_grace') {
        $reason = 'canceled_at_period_end_within_access_window';
    } elseif ($entitlementStatus === 'past_due') {
        $reason = 'past_due_premium_actions_unavailable';
    }

    $isJourneyPrice = journey_is_journey_price_id($priceId);

    return [
        'productKey' => $productKey !== '' ? $productKey : JOURNEY_PRODUCT_KEY,
        'stripeStatus' => strtolower($stripeStatus),
        'entitlementStatus' => $entitlementStatus,
        'accessAllowed' => $accessAllowed,
        'accessThrough' => $accessThroughTs !== null ? gmdate('c', $accessThroughTs) : null,
        'reason' => $reason,
        'priceId' => $priceId,
        'isJourneyPrice' => $isJourneyPrice,
    ];
}

/**
 * @param mixed $value Unix timestamp, numeric string, or ISO8601 datetime
 */
function journey_parse_time_value($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (is_float($value)) {
        return $value > 0 ? (int) $value : null;
    }
    if (is_numeric($value)) {
        $n = (int) $value;
        return $n > 0 ? $n : null;
    }
    if (is_string($value)) {
        $ts = strtotime($value);
        return ($ts !== false && $ts > 0) ? $ts : null;
    }
    return null;
}

/**
 * Insert / reclaim a webhook event row for idempotency.
 *
 * Returns:
 * - claimed            — new row; caller should process
 * - reclaimed          — prior failed row reset to processing; caller should process
 * - already_processed  — successfully handled before; no-op
 * - in_progress        — another worker holds received/processing; no-op (HTTP 200)
 * - duplicate          — alias of already_processed/in_progress for older callers
 * - error              — unexpected DB failure
 *
 * Safe under MYSQLI_REPORT_STRICT (duplicate key does not escape as an uncaught throw).
 *
 * @return string claimed|reclaimed|already_processed|in_progress|duplicate|error
 */
function journey_webhook_event_claim(
    mysqli $conn,
    string $stripeEventId,
    string $eventType,
    ?int $stripeCreatedAt = null,
    ?bool $livemode = null
): string {
    if ($stripeEventId === '') {
        return 'error';
    }

    $livemodeSql = $livemode === null ? 'NULL' : ($livemode ? '1' : '0');
    $createdSql = $stripeCreatedAt === null ? 'NULL' : (string) (int) $stripeCreatedAt;
    $eventIdEsc = $conn->real_escape_string($stripeEventId);
    $typeEsc = $conn->real_escape_string($eventType);

    $sql = "INSERT INTO stripe_webhook_events
            (stripe_event_id, event_type, stripe_created_at, livemode, processing_status, attempts)
            VALUES ('{$eventIdEsc}', '{$typeEsc}', {$createdSql}, {$livemodeSql}, 'received', 0)";

    try {
        if ($conn->query($sql)) {
            return 'claimed';
        }
        $errno = (int) $conn->errno;
    } catch (Throwable $e) {
        // mysqli_sql_exception on duplicate under MYSQLI_REPORT_STRICT / ERROR
        $errno = (int) ($e->getCode() > 0 ? $e->getCode() : $conn->errno);
        $msg = $e->getMessage();
        if ($errno !== 1062 && stripos($msg, 'Duplicate') === false) {
            error_log('journey_webhook_event_claim: unexpected DB exception');
            return 'error';
        }
        $errno = 1062;
    }

    if ($errno !== 1062) {
        error_log('journey_webhook_event_claim: insert failed errno=' . $errno);
        return 'error';
    }

    // Duplicate stripe_event_id — inspect prior state for reclaim / skip.
    try {
        $stmt = $conn->prepare(
            'SELECT processing_status FROM stripe_webhook_events WHERE stripe_event_id = ? LIMIT 1'
        );
        if (!$stmt) {
            return 'error';
        }
        $stmt->bind_param('s', $stripeEventId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    } catch (Throwable $e) {
        error_log('journey_webhook_event_claim: select after duplicate failed');
        return 'error';
    }

    if (!$row) {
        return 'error';
    }

    $status = (string) ($row['processing_status'] ?? '');
    if ($status === 'processed') {
        return 'already_processed';
    }
    if ($status === 'failed') {
        try {
            $upd = $conn->prepare(
                "UPDATE stripe_webhook_events
                 SET processing_status = 'processing', last_error = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE stripe_event_id = ? AND processing_status = 'failed'"
            );
            if (!$upd) {
                return 'error';
            }
            $upd->bind_param('s', $stripeEventId);
            $upd->execute();
            $affected = $upd->affected_rows;
            $upd->close();
            return $affected > 0 ? 'reclaimed' : 'in_progress';
        } catch (Throwable $e) {
            error_log('journey_webhook_event_claim: reclaim failed');
            return 'error';
        }
    }

    // received / processing / unknown — treat as in-flight
    return 'in_progress';
}

/**
 * Mark a previously claimed webhook event as processed/failed.
 */
function journey_webhook_event_mark(
    mysqli $conn,
    string $stripeEventId,
    string $processingStatus,
    ?string $lastError = null
): bool {
    $allowed = ['processing', 'processed', 'failed', 'received'];
    if (!in_array($processingStatus, $allowed, true)) {
        return false;
    }

    if ($processingStatus === 'processed') {
        $stmt = $conn->prepare(
            'UPDATE stripe_webhook_events
             SET processing_status = ?, last_error = NULL, processed_at = CURRENT_TIMESTAMP,
                 attempts = attempts + 1, updated_at = CURRENT_TIMESTAMP
             WHERE stripe_event_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $processingStatus, $stripeEventId);
    } elseif ($lastError === null) {
        $stmt = $conn->prepare(
            'UPDATE stripe_webhook_events
             SET processing_status = ?, last_error = NULL, attempts = attempts + 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE stripe_event_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $processingStatus, $stripeEventId);
    } else {
        $stmt = $conn->prepare(
            'UPDATE stripe_webhook_events
             SET processing_status = ?, last_error = ?, attempts = attempts + 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE stripe_event_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sss', $processingStatus, $lastError, $stripeEventId);
    }

    try {
        $ok = $stmt->execute();
    } catch (Throwable $e) {
        $stmt->close();
        return false;
    }
    $stmt->close();
    return $ok;
}
