<?php
/**
 * Shared account-page helpers (entitlement display + password change).
 */

declare(strict_types=1);

if (defined('RB_ACCOUNT_HELPERS_LOADED')) {
    return;
}
define('RB_ACCOUNT_HELPERS_LOADED', 1);

require_once __DIR__ . '/journey_checkout.php';
require_once __DIR__ . '/journey_plan_store.php';

/**
 * Journey Premium status for account UI — same authority as Journey chrome/status API.
 *
 * @return array{
 *   hasAccess:bool,
 *   entitlementStatus:string,
 *   label:string,
 *   detail:string,
 *   hadSubscription:bool,
 *   cloudPlanExists:bool,
 *   actionLabel:string,
 *   actionUrl:string,
 *   secondaryActionLabel:?string,
 *   secondaryActionUrl:?string
 * }
 */
function rb_account_journey_status(mysqli $conn, int $userId): array
{
    $hasAccess = has_journey_premium_access($conn, $userId);
    $entitlementStatus = 'none';
    $hadSubscription = false;
    $cloudPlanExists = journey_plan_has_cloud_row($conn, $userId);

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
            $hadSubscription = true;
            $entitlementStatus = trim((string) ($row['entitlement_status'] ?? ''));
            if ($entitlementStatus === '') {
                $entitlementStatus = strtolower(trim((string) ($row['stripe_status'] ?? 'none')));
            }
        }
    }
    if ($entitlementStatus === '') {
        $entitlementStatus = 'none';
    }

    $actionLabel = 'Continue Free Journey';
    $actionUrl = 'https://journey.ronbelisle.com/';
    $secondaryActionLabel = 'Start Journey Premium Trial';
    $secondaryActionUrl = '/premium/journey.php';

    if ($hasAccess) {
        if ($entitlementStatus === 'trialing') {
            $label = '30-day trial active';
            $detail = 'Your Journey Premium trial is active. Your plan can save to your account so you can continue across browsers, devices, and over time.';
        } elseif ($entitlementStatus === 'canceled_grace') {
            $label = 'Active through period end';
            $detail = 'Your Journey Premium access remains active through the end of the current billing period, including cloud saving.';
        } else {
            $label = 'Active';
            $detail = 'Your Journey Premium access is active. Your plan can save to your account so you can continue across browsers, devices, and over time.';
        }
        $actionLabel = 'Open Journey Premium';
        $actionUrl = 'https://journey.ronbelisle.com/';
        $secondaryActionLabel = null;
        $secondaryActionUrl = null;
    } elseif ($hadSubscription || $cloudPlanExists) {
        $label = $cloudPlanExists ? 'Saved Journey available' : 'Journey Premium inactive';
        $detail = $cloudPlanExists
            ? 'Your saved Journey is still available to review. Restore Journey Premium when you are ready to update it in the cloud again.'
            : 'You can continue the free Journey anytime. Restore Journey Premium when you want cloud saving again.';
        $actionLabel = 'Continue Free Journey';
        $actionUrl = 'https://journey.ronbelisle.com/';
        $secondaryActionLabel = 'Restore Journey Premium';
        $secondaryActionUrl = '/premium/journey.php';
    } else {
        $label = 'Free Journey';
        $detail = 'All six Journey phases are free to use. Journey Premium adds cloud saving so you can continue your plan across browsers, devices, and over time.';
    }

    return [
        'hasAccess' => $hasAccess,
        'entitlementStatus' => $entitlementStatus,
        'label' => $label,
        'detail' => $detail,
        'hadSubscription' => $hadSubscription,
        'cloudPlanExists' => $cloudPlanExists,
        'actionLabel' => $actionLabel,
        'actionUrl' => $actionUrl,
        'secondaryActionLabel' => $secondaryActionLabel,
        'secondaryActionUrl' => $secondaryActionUrl,
    ];
}

/**
 * Validate a logged-in password change request.
 *
 * @return array{ok:bool,error?:string}
 */
function rb_account_validate_password_change(
    string $currentPassword,
    string $newPassword,
    string $confirmPassword,
    string $currentHash
): array {
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        return ['ok' => false, 'error' => 'All password fields are required.'];
    }
    if (!password_verify($currentPassword, $currentHash)) {
        return ['ok' => false, 'error' => 'Current password is incorrect.'];
    }
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'New password must be at least 8 characters.'];
    }
    if ($newPassword !== $confirmPassword) {
        return ['ok' => false, 'error' => 'New password and confirmation do not match.'];
    }
    if (password_verify($newPassword, $currentHash)) {
        return ['ok' => false, 'error' => 'New password must be different from your current password.'];
    }
    return ['ok' => true];
}
