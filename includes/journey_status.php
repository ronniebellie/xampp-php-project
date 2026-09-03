<?php
/**
 * Journey Premium account-status payload builder (Milestone 5 / R1 — P2).
 * Used by premium/journey-status.php for Journey account chrome and Phase 6 CTAs.
 */

declare(strict_types=1);

if (defined('RB_JOURNEY_STATUS_LOADED')) {
    return;
}
define('RB_JOURNEY_STATUS_LOADED', 1);

require_once __DIR__ . '/journey_checkout.php';
require_once __DIR__ . '/journey_plan_store.php';

const JOURNEY_STATUS_HOME_URL = 'https://journey.ronbelisle.com/';
const JOURNEY_STATUS_CHECKOUT_URL = 'https://ronbelisle.com/premium/journey.php';
const JOURNEY_STATUS_LOGIN_BASE = 'https://ronbelisle.com/auth/login.php';
const JOURNEY_STATUS_LOGOUT_BASE = 'https://ronbelisle.com/auth/logout.php';
const JOURNEY_STATUS_ACCOUNT_URL = 'https://ronbelisle.com/account.php';

/**
 * First token of a display name (not a formal legal name parser).
 */
function journey_status_first_name(string $fullName): string
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
    if ($fullName === '') {
        return '';
    }
    $parts = explode(' ', $fullName);
    return (string) $parts[0];
}

/**
 * Lightweight cloud-plan timestamp without loading the full JSON payload.
 *
 * @return array{exists:bool,planSavedAt:?string}
 */
function journey_status_cloud_plan_meta(mysqli $conn, int $userId): array
{
    if ($userId <= 0 || !journey_plan_tables_ready($conn) || !journey_cloud_save_enabled()) {
        return ['exists' => false, 'planSavedAt' => null];
    }

    $stmt = $conn->prepare(
        'SELECT server_updated_at FROM journey_plans WHERE user_id = ? LIMIT 1'
    );
    if (!$stmt) {
        return ['exists' => false, 'planSavedAt' => null];
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($row) || empty($row['server_updated_at'])) {
        return ['exists' => false, 'planSavedAt' => null];
    }

    $ts = strtotime((string) $row['server_updated_at'] . ' UTC');
    if ($ts === false) {
        $ts = time();
    }

    return [
        'exists' => true,
        'planSavedAt' => gmdate('c', $ts),
    ];
}

/**
 * Build the JSON-serializable Journey status object for the current session.
 *
 * @return array<string,mixed>
 */
function journey_status_build_response(mysqli $conn): array
{
    $homeReturn = JOURNEY_STATUS_HOME_URL;
    $loginUrl = JOURNEY_STATUS_LOGIN_BASE . '?return=' . rawurlencode($homeReturn);
    $logoutUrl = JOURNEY_STATUS_LOGOUT_BASE;

    $authenticated = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    $base = [
        'authenticated' => false,
        'userId' => null,
        'userName' => null,
        'firstName' => null,
        'userEmail' => null,
        'hasAccess' => false,
        'accessMode' => 'anonymous',
        'subscriptionStatus' => 'none',
        'entitlementStatus' => 'none',
        'canCloudRead' => false,
        'canCloudWrite' => false,
        'cloudPlanExists' => false,
        'planSavedAt' => null,
        'cta' => 'start_trial',
        'checkoutUrl' => JOURNEY_STATUS_CHECKOUT_URL,
        'loginUrl' => $loginUrl,
        'logoutUrl' => $logoutUrl,
        'logoutCsrfToken' => null,
        'workspaceUrl' => JOURNEY_STATUS_HOME_URL,
        'accountUrl' => JOURNEY_STATUS_ACCOUNT_URL,
        'trialDays' => JOURNEY_CHECKOUT_TRIAL_DAYS,
    ];

    if (!$authenticated) {
        return $base;
    }

    $userId = (int) $_SESSION['user_id'];
    $userName = trim((string) ($_SESSION['user_name'] ?? ''));
    $userEmail = trim((string) ($_SESSION['user_email'] ?? ''));
    $firstName = journey_status_first_name($userName);
    if ($firstName === '' && $userEmail !== '') {
        $firstName = explode('@', $userEmail)[0] ?: '';
    }

    $hasAccess = has_journey_premium_access($conn, $userId);
    $entitlementStatus = 'none';
    $hadJourneySubscription = false;

    $sql = "SELECT entitlement_status, stripe_status
            FROM user_product_subscriptions
            WHERE user_id = ? AND product_key = ?
            ORDER BY updated_at DESC, id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $product = JOURNEY_PRODUCT_KEY;
        $stmt->bind_param('is', $userId, $product);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (is_array($row)) {
            $hadJourneySubscription = true;
            $entitlementStatus = trim((string) ($row['entitlement_status'] ?? ''));
            if ($entitlementStatus === '') {
                $entitlementStatus = strtolower(trim((string) ($row['stripe_status'] ?? 'none')));
            }
        }
    }

    if ($entitlementStatus === '') {
        $entitlementStatus = 'none';
    }

    $cloudMeta = journey_status_cloud_plan_meta($conn, $userId);
    $cloudPlanExists = !empty($cloudMeta['exists']);
    $cloudEnabled = journey_cloud_save_enabled();
    $canCloudWrite = $cloudEnabled && journey_plan_can_write($conn, $userId);
    $canCloudRead = $cloudEnabled && journey_plan_can_read($conn, $userId);

    if ($canCloudWrite) {
        $accessMode = 'premium';
    } elseif ($canCloudRead && $cloudPlanExists) {
        $accessMode = 'readonly';
    } else {
        $accessMode = 'free';
    }

    if ($hasAccess) {
        $cta = 'open_workspace';
    } elseif ($hadJourneySubscription) {
        $cta = 'subscribe';
    } else {
        $cta = 'start_trial';
    }

    return [
        'authenticated' => true,
        'userId' => $userId,
        'userName' => $userName !== '' ? $userName : null,
        'firstName' => $firstName !== '' ? $firstName : null,
        'userEmail' => $userEmail !== '' ? $userEmail : null,
        'hasAccess' => $hasAccess,
        'accessMode' => $accessMode,
        'subscriptionStatus' => $entitlementStatus,
        'entitlementStatus' => $entitlementStatus,
        'canCloudRead' => $canCloudRead,
        'canCloudWrite' => $canCloudWrite,
        'cloudPlanExists' => $cloudPlanExists,
        'planSavedAt' => $cloudMeta['planSavedAt'],
        'cta' => $cta,
        'checkoutUrl' => JOURNEY_STATUS_CHECKOUT_URL,
        'loginUrl' => $loginUrl,
        'logoutUrl' => $logoutUrl,
        'logoutCsrfToken' => rb_csrf_token(),
        'workspaceUrl' => JOURNEY_STATUS_HOME_URL,
        'accountUrl' => JOURNEY_STATUS_ACCOUNT_URL,
        'trialDays' => JOURNEY_CHECKOUT_TRIAL_DAYS,
    ];
}
