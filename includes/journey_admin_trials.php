<?php
/**
 * Admin dashboard helpers for Recent Journey Premium Trials.
 *
 * Authoritative list source: user_product_subscriptions (product_key = journey)
 * with trial_start set, joined to users. Viewed state from
 * journey_admin_trial_notifications.viewed_at.
 */

if (defined('JOURNEY_ADMIN_TRIALS_LOADED')) {
    return;
}
define('JOURNEY_ADMIN_TRIALS_LOADED', 1);

require_once __DIR__ . '/journey_entitlement.php';

const JOURNEY_ADMIN_TRIALS_DEFAULT_LIMIT = 50;

/**
 * Human-readable status for admin table.
 */
function journey_admin_trial_status_label(string $entitlementStatus, ?string $stripeStatus = null): string
{
    $status = strtolower(trim($entitlementStatus));
    switch ($status) {
        case 'trialing':
            return 'Trial active';
        case 'active':
            return 'Active';
        case 'canceled_grace':
            return 'Canceled';
        case 'canceled':
            return 'Canceled';
        case 'expired':
            return 'Trial ended';
        case 'past_due':
            return 'Past due';
        case 'unpaid':
            return 'Past due';
        case 'none':
            // Fall back to raw Stripe status when entitlement is empty.
            $stripe = strtolower(trim((string) $stripeStatus));
            if ($stripe === 'trialing') {
                return 'Trial active';
            }
            if ($stripe === 'active') {
                return 'Active';
            }
            return 'Unknown';
        default:
            if ($status !== '') {
                return ucwords(str_replace('_', ' ', $status));
            }
            return 'Unknown';
    }
}

/**
 * Format a DATETIME/date string for admin display (America/Chicago).
 */
function journey_admin_format_db_datetime(?string $dbValue, bool $dateOnly = false): string
{
    if ($dbValue === null || trim($dbValue) === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($dbValue, new DateTimeZone('UTC'));
        $dt = $dt->setTimezone(new DateTimeZone('America/Chicago'));
    } catch (Throwable $e) {
        return '—';
    }
    return $dateOnly ? $dt->format('M j, Y') : $dt->format('M j, Y g:i A T');
}

/**
 * @return list<array<string,mixed>>
 */
function journey_admin_list_recent_trials(mysqli $conn, int $limit = JOURNEY_ADMIN_TRIALS_DEFAULT_LIMIT): array
{
    $limit = max(1, min(100, $limit));
    $productKey = JOURNEY_PRODUCT_KEY;
    $sql = "SELECT
                ups.id AS subscription_row_id,
                ups.user_id,
                ups.stripe_subscription_id,
                ups.stripe_customer_id,
                ups.stripe_status,
                ups.entitlement_status,
                ups.trial_start,
                ups.trial_end,
                ups.cancel_at_period_end,
                ups.created_at AS subscription_created_at,
                u.full_name,
                u.email,
                n.viewed_at,
                n.delivery_status AS ledger_status
            FROM user_product_subscriptions ups
            LEFT JOIN users u ON u.id = ups.user_id
            LEFT JOIN journey_admin_trial_notifications n
                ON n.stripe_subscription_id = ups.stripe_subscription_id
            WHERE ups.product_key = ?
              AND ups.trial_start IS NOT NULL
            ORDER BY ups.trial_start DESC, ups.id DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('si', $productKey, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $viewedAt = $row['viewed_at'] ?? null;
        $isNew = ($viewedAt === null || $viewedAt === '');
        $rows[] = [
            'subscription_row_id' => (int) ($row['subscription_row_id'] ?? 0),
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'stripe_subscription_id' => (string) ($row['stripe_subscription_id'] ?? ''),
            'stripe_customer_id' => (string) ($row['stripe_customer_id'] ?? ''),
            'stripe_status' => (string) ($row['stripe_status'] ?? ''),
            'entitlement_status' => (string) ($row['entitlement_status'] ?? ''),
            'status_label' => journey_admin_trial_status_label(
                (string) ($row['entitlement_status'] ?? ''),
                (string) ($row['stripe_status'] ?? '')
            ),
            'trial_start' => $row['trial_start'] ?? null,
            'trial_end' => $row['trial_end'] ?? null,
            'trial_start_label' => journey_admin_format_db_datetime(
                isset($row['trial_start']) ? (string) $row['trial_start'] : null
            ),
            'trial_end_label' => journey_admin_format_db_datetime(
                isset($row['trial_end']) ? (string) $row['trial_end'] : null,
                true
            ),
            'full_name' => trim((string) ($row['full_name'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'viewed_at' => $viewedAt,
            'is_new' => $isNew,
            'review_label' => $isNew ? 'New' : 'Viewed',
        ];
    }
    $stmt->close();
    return $rows;
}

/**
 * Count Journey Premium trials that have not been reviewed in the admin UI.
 */
function journey_admin_count_unviewed_trials(mysqli $conn): int
{
    $productKey = JOURNEY_PRODUCT_KEY;
    $sql = "SELECT COUNT(*) AS c
            FROM user_product_subscriptions ups
            LEFT JOIN journey_admin_trial_notifications n
                ON n.stripe_subscription_id = ups.stripe_subscription_id
            WHERE ups.product_key = ?
              AND ups.trial_start IS NOT NULL
              AND (n.id IS NULL OR n.viewed_at IS NULL)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $productKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['c'] ?? 0);
}

/**
 * Mark the given subscription IDs as viewed (idempotent).
 *
 * @param list<string> $stripeSubscriptionIds
 * @return int Number of rows newly marked viewed
 */
function journey_admin_mark_trials_viewed(mysqli $conn, array $stripeSubscriptionIds): int
{
    $marked = 0;
    $productKey = JOURNEY_PRODUCT_KEY;

    foreach ($stripeSubscriptionIds as $subId) {
        $subId = trim((string) $subId);
        if ($subId === '' || strpos($subId, 'sub_') !== 0) {
            continue;
        }

        $uid = 0;
        $lookup = $conn->prepare(
            'SELECT user_id FROM user_product_subscriptions
             WHERE stripe_subscription_id = ? AND product_key = ? LIMIT 1'
        );
        if ($lookup) {
            $lookup->bind_param('ss', $subId, $productKey);
            $lookup->execute();
            $r = $lookup->get_result()->fetch_assoc();
            $lookup->close();
            if (is_array($r) && isset($r['user_id'])) {
                $uid = (int) $r['user_id'];
            }
        }

        if ($uid > 0) {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, user_id, delivery_status, viewed_at)
                 VALUES (?, ?, \'recorded\', UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE
                    viewed_at = COALESCE(viewed_at, VALUES(viewed_at)),
                    user_id = COALESCE(VALUES(user_id), user_id),
                    delivery_status = IF(
                        delivery_status IN (\'sending\', \'failed\'),
                        \'recorded\',
                        delivery_status
                    )'
            );
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('si', $subId, $uid);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, user_id, delivery_status, viewed_at)
                 VALUES (?, NULL, \'recorded\', UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE
                    viewed_at = COALESCE(viewed_at, VALUES(viewed_at)),
                    delivery_status = IF(
                        delivery_status IN (\'sending\', \'failed\'),
                        \'recorded\',
                        delivery_status
                    )'
            );
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $subId);
        }

        if ($stmt->execute()) {
            // MySQL: insert => 1, update changing values => 2, no-op update => 0
            if ($stmt->affected_rows > 0) {
                $marked++;
            }
        }
        $stmt->close();
    }
    return $marked;
}

/**
 * Compact summary figures for the admin trials page.
 *
 * @return array{last_7_days:int,last_30_days:int,currently_trialing:int,converted_to_active:int}
 */
function journey_admin_trial_summary(mysqli $conn): array
{
    $productKey = JOURNEY_PRODUCT_KEY;
    $out = [
        'last_7_days' => 0,
        'last_30_days' => 0,
        'currently_trialing' => 0,
        'converted_to_active' => 0,
    ];
    $sql = "SELECT
                SUM(CASE WHEN trial_start >= (UTC_TIMESTAMP() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS last_7_days,
                SUM(CASE WHEN trial_start >= (UTC_TIMESTAMP() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS last_30_days,
                SUM(CASE WHEN entitlement_status = 'trialing' THEN 1 ELSE 0 END) AS currently_trialing,
                SUM(CASE WHEN entitlement_status = 'active' THEN 1 ELSE 0 END) AS converted_to_active
            FROM user_product_subscriptions
            WHERE product_key = ?
              AND trial_start IS NOT NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $out;
    }
    $stmt->bind_param('s', $productKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (is_array($row)) {
        $out['last_7_days'] = (int) ($row['last_7_days'] ?? 0);
        $out['last_30_days'] = (int) ($row['last_30_days'] ?? 0);
        $out['currently_trialing'] = (int) ($row['currently_trialing'] ?? 0);
        $out['converted_to_active'] = (int) ($row['converted_to_active'] ?? 0);
    }
    return $out;
}

/**
 * Render shared admin chrome nav. Escape handled by caller for dynamic bits.
 */
function journey_admin_nav_html(mysqli $conn, string $active = ''): string
{
    if (!function_exists('journey_feedback_count_new')) {
        require_once __DIR__ . '/journey_feedback.php';
    }

    $newCount = journey_admin_count_unviewed_trials($conn);
    $trialsLabel = 'Journey Premium Trials';
    if ($newCount > 0) {
        $trialsLabel .= ' — ' . (int) $newCount . ' new';
    }

    $feedbackNew = journey_feedback_count_new($conn);
    $feedbackLabel = 'Journey Feedback';
    if ($feedbackNew > 0) {
        $feedbackLabel .= ' — ' . (int) $feedbackNew . ' new';
    }

    $link = static function (string $href, string $label, string $key) use ($active): string {
        $class = $key === $active ? ' class="is-active"' : '';
        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $class . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    };

    return '<nav class="admin-nav" aria-label="Administrator">'
        . $link('/admin/', 'Admin home', 'home')
        . $link('/admin/journey-premium-trials.php', $trialsLabel, 'trials')
        . $link('/admin/journey-feedback.php', $feedbackLabel, 'feedback')
        . $link('/account.php', 'My account', 'account')
        . '</nav>';
}
