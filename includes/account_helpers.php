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

/**
 * Journey Premium status for account UI — same authority as Journey chrome/status API.
 *
 * @return array{
 *   hasAccess:bool,
 *   entitlementStatus:string,
 *   label:string,
 *   detail:string,
 *   hadSubscription:bool
 * }
 */
function rb_account_journey_status(mysqli $conn, int $userId): array
{
    $hasAccess = has_journey_premium_access($conn, $userId);
    $entitlementStatus = 'none';
    $hadSubscription = false;

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

    if ($hasAccess) {
        if ($entitlementStatus === 'trialing') {
            $label = 'Journey Premium (trial)';
            $detail = 'Your Journey Premium trial is active. Cloud Journey saving and cross-device continuity are available.';
        } elseif ($entitlementStatus === 'canceled_grace') {
            $label = 'Journey Premium (access through period end)';
            $detail = 'Your Journey Premium access remains active through the end of the current billing period.';
        } else {
            $label = 'Journey Premium';
            $detail = 'Your Journey Premium access is active.';
        }
    } elseif ($hadSubscription) {
        $label = 'Journey Premium inactive';
        $detail = 'A prior Journey Premium subscription exists, but access is not currently active. Cloud updates require restoring Journey Premium.';
    } else {
        $label = 'Not enrolled';
        $detail = 'Journey Premium is optional. The six free Journey phases remain available; cloud Journey saving requires Journey Premium.';
    }

    return [
        'hasAccess' => $hasAccess,
        'entitlementStatus' => $entitlementStatus,
        'label' => $label,
        'detail' => $detail,
        'hadSubscription' => $hadSubscription,
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
