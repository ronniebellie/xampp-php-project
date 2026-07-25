<?php
/**
 * Journey Premium Checkout success — confirmation only (Milestone 3).
 * Does NOT write users.subscription_status or grant entitlement.
 * Entitlement comes from the Milestone 2 webhook → user_product_subscriptions.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/stripe_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_flow_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_checkout.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    rb_auth_redirect_to_login('/premium/journey.php', 'journey_trial');
}

$userId = (int) $_SESSION['user_id'];
$sessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';
$attempt = isset($_GET['attempt']) ? max(0, (int) $_GET['attempt']) : 0;

$error = null;
$checkoutOk = false;
$entitled = false;
$plan = '';

if ($sessionId === '' || strpos($sessionId, 'cs_') !== 0) {
    $error = 'missing_session';
} else {
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $checkout = \Stripe\Checkout\Session::retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        $metaProduct = (string) ($checkout->metadata['product'] ?? $checkout->metadata['product_key'] ?? '');
        $ref = (string) ($checkout->client_reference_id ?? '');
        $metaUser = (string) ($checkout->metadata['user_id'] ?? '');
        $plan = (string) ($checkout->metadata['plan'] ?? '');

        if ($checkout->mode !== 'subscription') {
            throw new RuntimeException('not_subscription');
        }
        if ($metaProduct !== JOURNEY_PRODUCT_KEY) {
            throw new RuntimeException('not_journey_session');
        }
        if ((string) $userId !== $ref && (string) $userId !== $metaUser) {
            throw new RuntimeException('user_mismatch');
        }
        if ($checkout->status !== 'complete') {
            throw new RuntimeException('not_complete');
        }

        $checkoutOk = true;
        // Intentionally do NOT update users.subscription_status or insert entitlement rows here.
        $entitled = has_journey_premium_access($conn, $userId);
    } catch (Throwable $e) {
        error_log('journey-success: ' . $e->getMessage());
        $error = 'lookup_failed';
    }
}

$finishing = $checkoutOk && !$entitled && $attempt < 8;
$refreshUrl = '/premium/journey-success.php?session_id=' . rawurlencode($sessionId) . '&attempt=' . ($attempt + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Journey Premium — Checkout confirmation</title>
    <?php if ($finishing): ?>
        <meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/shared-styles.css">
    <style>
        .js-wrap { max-width: 640px; margin: 60px auto; padding: 28px 24px; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,.08); text-align: center; }
        h1 { color: #1e3a5f; margin-bottom: 12px; }
        .js-ok { color: #047857; }
        .js-wait { color: #92400e; }
        .js-err { color: #991b1b; }
        p { color: #334155; line-height: 1.55; }
        a.btn {
            display: inline-block; margin-top: 18px; padding: 12px 18px; background: #2c5282; color: #fff;
            text-decoration: none; border-radius: 8px; font-weight: 600;
        }
        a.btn-secondary { background: #64748b; margin-left: 8px; }
    </style>
</head>
<body>
<div class="js-wrap">
    <?php if ($error !== null): ?>
        <h1 class="js-err">We could not confirm this checkout</h1>
        <p>No Journey Premium access was granted from this page. If you completed payment, please wait a moment and check again, or return to the plan page.</p>
        <a class="btn" href="/premium/journey.php">Return to plan selection</a>
    <?php elseif ($entitled): ?>
        <h1 class="js-ok">Your Journey Premium trial is ready</h1>
        <p>Stripe finished processing your subscription. You will not be charged today. Your 30-day free trial is active<?php echo $plan !== '' ? ' on the ' . htmlspecialchars($plan, ENT_QUOTES, 'UTF-8') . ' plan' : ''; ?>.</p>
        <p>Cancel before the trial ends to avoid being charged.</p>
        <a class="btn" href="https://journey.ronbelisle.com/">Continue to the Journey</a>
        <a class="btn btn-secondary" href="/premium/journey.php">Back to plan page</a>
    <?php else: ?>
        <h1 class="js-wait">Finishing setup</h1>
        <p>Thanks — Stripe is processing your subscription. Journey Premium access is confirmed by our secure webhook, not this page.</p>
        <p>This usually takes a few seconds.<?php echo $finishing ? ' Checking again…' : ''; ?></p>
        <?php if (!$finishing): ?>
            <p>If access is still not ready, wait a minute and retry, or return to the plan page. Do not start a second checkout unless you are sure the first one did not complete.</p>
        <?php endif; ?>
        <a class="btn" href="<?php echo htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8'); ?>">Check again</a>
        <a class="btn btn-secondary" href="/premium/journey.php">Return to plan selection</a>
    <?php endif; ?>
</div>
</body>
</html>
