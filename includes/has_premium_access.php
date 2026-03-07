<?php
/**
 * Unified Premium access check for ronbelisle.com calculators.
 * Returns true if user has Premium features (save, export, AI explain, extended projections).
 *
 * Sources:
 * 1. ronbelisle.com users with subscription_status = 'premium'
 * 2. calcforadvisors paid subscribers (plan = monthly or annual) via bridge session
 *
 * Requires: session_start() already called, db_config available.
 */
if (!defined('HAS_PREMIUM_ACCESS_LOADED')) {
    define('HAS_PREMIUM_ACCESS_LOADED', 1);

    function has_premium_access() {
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/db_config.php';
        }

        // 1. ronbelisle.com Premium user
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $sub = null;
            $stmt->bind_result($sub);
            if ($stmt->fetch() && $sub === 'premium') {
                $stmt->close();
                return true;
            }
            $stmt->close();
        }

        // 2. calcforadvisors paid subscriber (set by bridge)
        if (!empty($_SESSION['calcforadvisors_subscriber_id']) && !empty($_SESSION['calcforadvisors_plan'])) {
            $plan = $_SESSION['calcforadvisors_plan'];
            if (in_array($plan, ['monthly', 'annual'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the URL for Premium upsell (subscribe or upgrade).
     * For calcforadvisors free users → calcforadvisors.com pricing.
     * Otherwise → ronbelisle subscribe or auth/register.
     */
    function get_premium_upsell_url($isLoggedIn) {
        if (!empty($_SESSION['calcforadvisors_plan']) && $_SESSION['calcforadvisors_plan'] === 'free') {
            return 'https://calcforadvisors.com/index.html#pricing';
        }
        return $isLoggedIn ? '/subscribe.php' : '/auth/register.php';
    }

    /**
     * Get scenario owner for save/load/delete. Returns ['type'=>'user','id'=>N] or ['type'=>'cfa','id'=>N] or null.
     */
    function get_scenario_owner() {
        if (isset($_SESSION['user_id'])) {
            global $conn;
            if (!isset($conn)) require_once __DIR__ . '/db_config.php';
            $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $sub = null;
            $stmt->bind_result($sub);
            if ($stmt->fetch() && $sub === 'premium') {
                $stmt->close();
                return ['type' => 'user', 'id' => (int) $_SESSION['user_id']];
            }
            $stmt->close();
        }
        if (!empty($_SESSION['calcforadvisors_subscriber_id']) && in_array($_SESSION['calcforadvisors_plan'] ?? '', ['monthly', 'annual'], true)) {
            return ['type' => 'cfa', 'id' => (int) $_SESSION['calcforadvisors_subscriber_id']];
        }
        return null;
    }
}
