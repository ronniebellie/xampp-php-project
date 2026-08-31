<?php
/**
 * CalcForAdvisors 2.0 entitlement and portal-slug foundation.
 *
 * This module is deliberately separate from consumer Calculator Premium and
 * Journey Premium. It can be used by the legacy bridge now and by shared-session
 * authentication later, but Phase 2 does not change either production flow.
 */

declare(strict_types=1);

if (defined('CFA_ENTITLEMENT_FOUNDATION_LOADED')) {
    return;
}
define('CFA_ENTITLEMENT_FOUNDATION_LOADED', 1);

const CFA_PAST_DUE_GRACE_DAYS = 7;
const CFA_LEGACY_TRIAL_DAYS = 30;
const CFA_PORTAL_SLUG_MIN_LENGTH = 3;
const CFA_PORTAL_SLUG_MAX_LENGTH = 48;

const CFA_PORTAL_RESERVED_SLUGS = [
    'account', 'admin', 'api', 'assets', 'billing', 'calculator',
    'checkout', 'login', 'logout', 'p', 'portal', 'pricing',
    'register', 'robots', 'sitemap', 'stripe', 'success', 'support',
    'trial', 'www',
];

function cfa_parse_utc_datetime($value): ?DateTimeImmutable
{
    if ($value instanceof DateTimeImmutable) {
        return $value->setTimezone(new DateTimeZone('UTC'));
    }
    if ($value instanceof DateTimeInterface) {
        return (new DateTimeImmutable($value->format(DateTimeInterface::ATOM)))->setTimezone(new DateTimeZone('UTC'));
    }
    $text = trim((string) $value);
    if ($text === '' || $text === '0000-00-00 00:00:00') {
        return null;
    }
    try {
        return (new DateTimeImmutable($text, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'));
    } catch (Throwable $e) {
        return null;
    }
}

function cfa_datetime_string(?DateTimeImmutable $value): ?string
{
    return $value ? $value->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null;
}

function cfa_normalize_portal_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? '';
    $value = preg_replace('/-+/', '-', $value) ?? '';
    return trim($value, '-');
}

function cfa_portal_slug_is_reserved(string $slug): bool
{
    return in_array(strtolower($slug), CFA_PORTAL_RESERVED_SLUGS, true);
}

/** @return array{ok:bool,slug:string,error:?string} */
function cfa_validate_portal_slug(string $value): array
{
    $slug = cfa_normalize_portal_slug($value);
    $length = strlen($slug);
    if ($length < CFA_PORTAL_SLUG_MIN_LENGTH || $length > CFA_PORTAL_SLUG_MAX_LENGTH) {
        return ['ok' => false, 'slug' => $slug, 'error' => 'length'];
    }
    if (!preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])$/', $slug)) {
        return ['ok' => false, 'slug' => $slug, 'error' => 'format'];
    }
    if (cfa_portal_slug_is_reserved($slug)) {
        return ['ok' => false, 'slug' => $slug, 'error' => 'reserved'];
    }
    return ['ok' => true, 'slug' => $slug, 'error' => null];
}

/**
 * Pure duplicate check used by migrations/tests before a database constraint is
 * applied. Values may be strings or rows containing id and portal_slug.
 */
function cfa_portal_slug_is_unique(string $slug, array $existing, ?int $excludeSubscriberId = null): bool
{
    $needle = strtolower($slug);
    foreach ($existing as $item) {
        if (is_array($item)) {
            if ($excludeSubscriberId !== null && (int) ($item['id'] ?? 0) === $excludeSubscriberId) {
                continue;
            }
            $candidate = (string) ($item['portal_slug'] ?? '');
        } else {
            $candidate = (string) $item;
        }
        if ($candidate !== '' && strtolower($candidate) === $needle) {
            return false;
        }
    }
    return true;
}

function cfa_portal_slug_available(mysqli $conn, string $slug, ?int $excludeSubscriberId = null): bool
{
    $validation = cfa_validate_portal_slug($slug);
    if (!$validation['ok']) {
        return false;
    }
    if ($excludeSubscriberId !== null) {
        $stmt = $conn->prepare('SELECT id FROM calcforadvisors_subscribers WHERE portal_slug = ? AND id != ? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('si', $validation['slug'], $excludeSubscriberId);
    } else {
        $stmt = $conn->prepare('SELECT id FROM calcforadvisors_subscribers WHERE portal_slug = ? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $validation['slug']);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $result = $stmt->get_result();
    $available = !$result || $result->num_rows === 0;
    $stmt->close();
    return $available;
}

/**
 * Evaluate one authoritative subscriber row without consulting a browser
 * session. `$now` is injectable so lifecycle rules remain deterministic.
 *
 * @return array{
 *   state:string,
 *   has_access:bool,
 *   has_premium:bool,
 *   portal_available:bool,
 *   in_grace:bool,
 *   access_until:?string,
 *   reason:string
 * }
 */
function cfa_evaluate_advisor_entitlement(array $subscriber, ?DateTimeImmutable $now = null): array
{
    $utc = new DateTimeZone('UTC');
    $now = ($now ?? new DateTimeImmutable('now', $utc))->setTimezone($utc);
    $plan = strtolower(trim((string) ($subscriber['plan'] ?? '')));
    $legacyStatus = strtolower(trim((string) ($subscriber['status'] ?? '')));
    $stripeStatus = strtolower(trim((string) ($subscriber['stripe_subscription_status'] ?? '')));
    $trialEnds = cfa_parse_utc_datetime($subscriber['trial_ends_at'] ?? null);
    $accessEnds = cfa_parse_utc_datetime($subscriber['access_ends_at'] ?? null);
    $pastDueStarted = cfa_parse_utc_datetime($subscriber['past_due_started_at'] ?? null);

    $result = static function (
        string $state,
        bool $hasAccess,
        bool $hasPremium,
        bool $inGrace,
        ?DateTimeImmutable $until,
        string $reason
    ): array {
        return [
            'state' => $state,
            'has_access' => $hasAccess,
            'has_premium' => $hasPremium,
            'portal_available' => $hasAccess,
            'in_grace' => $inGrace,
            'access_until' => cfa_datetime_string($until),
            'reason' => $reason,
        ];
    };

    // Preserve legacy no-card trials, but do not upgrade them to calculator
    // Premium. If no explicit end was migrated, retain the existing 30-day rule.
    if ($plan === 'free') {
        if ($trialEnds === null) {
            $created = cfa_parse_utc_datetime($subscriber['created_at'] ?? null);
            $trialEnds = $created ? $created->modify('+' . CFA_LEGACY_TRIAL_DAYS . ' days') : null;
        }
        if ($trialEnds !== null && $now < $trialEnds) {
            return $result('legacy_trialing', true, false, false, $trialEnds, 'legacy_free_trial_active');
        }
        return $result('legacy_trial_expired', false, false, false, $trialEnds, 'legacy_free_trial_expired');
    }

    if ($stripeStatus === 'trialing') {
        if ($trialEnds === null) {
            return $result('trial_invalid', false, false, false, null, 'stripe_trial_end_missing');
        }
        if ($trialEnds !== null && $now >= $trialEnds) {
            return $result('trial_expired', false, false, false, $trialEnds, 'stripe_trial_end_reached');
        }
        return $result('trialing', true, true, false, $trialEnds, 'stripe_trial_active');
    }

    if ($stripeStatus === 'active') {
        return $result('active', true, true, false, $accessEnds, 'stripe_subscription_active');
    }

    if ($stripeStatus === 'past_due' || $legacyStatus === 'past_due') {
        $graceEnds = $pastDueStarted ? $pastDueStarted->modify('+' . CFA_PAST_DUE_GRACE_DAYS . ' days') : null;
        if ($graceEnds !== null && $now < $graceEnds) {
            return $result('past_due_grace', true, true, true, $graceEnds, 'past_due_within_grace');
        }
        return $result('past_due_expired', false, false, false, $graceEnds, $pastDueStarted ? 'past_due_grace_expired' : 'past_due_start_unknown');
    }

    if (in_array($stripeStatus, ['canceled', 'cancelled'], true) || in_array($legacyStatus, ['canceled', 'cancelled'], true)) {
        if ($accessEnds !== null && $now < $accessEnds) {
            return $result('canceled_paid_through', true, true, false, $accessEnds, 'canceled_with_remaining_access');
        }
        return $result('canceled_expired', false, false, false, $accessEnds, 'canceled_access_expired');
    }

    // Backward compatibility for existing paid records before Stripe status is
    // backfilled. The row is still database-authoritative; no session plan is
    // trusted. Later webhook work should populate stripe_subscription_status.
    if ($stripeStatus === '' && in_array($plan, ['monthly', 'annual'], true) && $legacyStatus === 'active') {
        return $result('legacy_active', true, true, false, $accessEnds, 'legacy_paid_record_active');
    }

    return $result('inactive', false, false, false, $accessEnds, 'no_qualifying_subscription');
}

/** @return array<string,mixed>|null */
function cfa_load_advisor_subscriber_for_entitlement(mysqli $conn, int $subscriberId): ?array
{
    if ($subscriberId <= 0) return null;
    $sql = 'SELECT id, email, plan, status, created_at,
                   stripe_customer_id, stripe_subscription_id,
                   stripe_subscription_status, trial_ends_at, access_ends_at,
                   trial_used_at, past_due_started_at, portal_slug
              FROM calcforadvisors_subscribers WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $subscriberId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return is_array($row) ? $row : null;
}

/** @return array<string,mixed> */
function cfa_advisor_entitlement(mysqli $conn, int $subscriberId, ?DateTimeImmutable $now = null): array
{
    $subscriber = cfa_load_advisor_subscriber_for_entitlement($conn, $subscriberId);
    if ($subscriber === null) {
        return cfa_evaluate_advisor_entitlement([], $now);
    }
    return cfa_evaluate_advisor_entitlement($subscriber, $now);
}

function cfa_has_advisor_premium_entitlement(mysqli $conn, int $subscriberId, ?DateTimeImmutable $now = null): bool
{
    return cfa_advisor_entitlement($conn, $subscriberId, $now)['has_premium'] === true;
}
