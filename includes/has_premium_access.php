<?php
/**
 * Unified Premium access check for ronbelisle.com calculators.
 * Returns true if user has Premium features (save, export, AI explain, extended projections).
 *
 * Sources:
 * 1. verified consumer subscriptions or explicit unlinked manual grants
 * 2. calcforadvisors paid subscribers (plan = monthly or annual) via bridge session
 *
 * Requires: session_start() already called, db_config available.
 */
if (!defined('HAS_PREMIUM_ACCESS_LOADED')) {
    define('HAS_PREMIUM_ACCESS_LOADED', 1);

    function rb_current_advisor_premium(): bool {
        global $conn;
        require_once __DIR__ . '/calcforadvisors_entitlement.php';
        $id=(int)($_SESSION['calcforadvisors_subscriber_id']??0);
        if($id<=0)return false;
        try { return cfa_has_advisor_premium_entitlement($conn,$id); }
        catch(Throwable $e){error_log('Advisor entitlement unavailable');return false;}
    }

    function has_premium_access() {
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/db_config.php';
        }

        require_once __DIR__ . '/consumer_subscription.php';
        if (!empty($_SESSION['user_id']) && rb_consumer_has_premium($conn,(int)$_SESSION['user_id'])) return true;

        // 2. calcforadvisors paid subscriber (set by bridge)
        return rb_current_advisor_premium();
    }

    /** Short pricing line for upsell banners (matches premium.html). */
    function get_premium_pricing_blurb() {
        return '7-day free trial, then $3/month or $30/year.';
    }

    /**
     * Returns the URL for Premium upsell.
     * For calcforadvisors free users → calcforadvisors.com pricing.
     * Otherwise → premium features & pricing page.
     */
    function get_premium_upsell_url($isLoggedIn) {
        if (!empty($_SESSION['calcforadvisors_plan']) && $_SESSION['calcforadvisors_plan'] === 'free') {
            return 'https://calcforadvisors.com/index.html#pricing';
        }
        return '/premium.html';
    }

    /**
     * Get scenario owner for save/load/delete. Returns ['type'=>'user','id'=>N] or ['type'=>'cfa','id'=>N] or null.
     */
    function get_scenario_owner() {
        if (isset($_SESSION['user_id'])) {
            global $conn;
            if (!isset($conn)) require_once __DIR__ . '/db_config.php';
            require_once __DIR__ . '/consumer_subscription.php';
            if(rb_consumer_has_premium($conn,(int)$_SESSION['user_id'])) return ['type'=>'user','id'=>(int)$_SESSION['user_id']];
        }
        if (rb_current_advisor_premium()) {
            return ['type' => 'cfa', 'id' => (int) $_SESSION['calcforadvisors_subscriber_id']];
        }
        return null;
    }
}
