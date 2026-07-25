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
 * Return configured Journey monthly Price ID, or empty string if unset.
 */
function journey_stripe_monthly_price_id(): string
{
    return defined('JOURNEY_STRIPE_MONTHLY_PRICE_ID') ? (string) JOURNEY_STRIPE_MONTHLY_PRICE_ID : '';
}

/**
 * Return configured Journey annual Price ID, or empty string if unset (optional).
 */
function journey_stripe_annual_price_id(): string
{
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
 * Insert a webhook event row for idempotency. Returns:
 * - 'claimed' when this caller should process the event
 * - 'duplicate' when stripe_event_id already exists
 * - 'error' on failure
 *
 * Milestone 1 foundation only; full product handlers arrive in Milestone 2.
 *
 * @param mysqli $conn
 * @return string claimed|duplicate|error
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

    $status = 'received';
    $livemodeSql = $livemode === null ? 'NULL' : ($livemode ? '1' : '0');
    $createdSql = $stripeCreatedAt === null ? 'NULL' : (string) (int) $stripeCreatedAt;
    $eventIdEsc = $conn->real_escape_string($stripeEventId);
    $typeEsc = $conn->real_escape_string($eventType);
    $statusEsc = $conn->real_escape_string($status);

    $sql = "INSERT INTO stripe_webhook_events
            (stripe_event_id, event_type, stripe_created_at, livemode, processing_status, attempts)
            VALUES ('{$eventIdEsc}', '{$typeEsc}', {$createdSql}, {$livemodeSql}, '{$statusEsc}', 0)";

    if ($conn->query($sql)) {
        return 'claimed';
    }
    if ((int) $conn->errno === 1062) {
        return 'duplicate';
    }
    return 'error';
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

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
