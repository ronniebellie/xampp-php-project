<?php
/**
 * Journey Premium — review-only plan selection (Milestone 3).
 * Not linked from public Journey pages yet. noindex while under review.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_flow_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_checkout.php';

$planPrefill = strtolower(trim((string) ($_GET['plan'] ?? '')));
if (!in_array($planPrefill, ['monthly', 'annual'], true)) {
    $planPrefill = '';
}

if (!isset($_SESSION['user_id'])) {
    $return = '/premium/journey.php';
    if ($planPrefill !== '') {
        $return .= '?plan=' . rawurlencode($planPrefill);
    }
    rb_auth_redirect_to_login($return, 'journey_trial');
}

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT email, full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    rb_auth_redirect_to_login('/premium/journey.php', 'journey_trial');
}

$alreadyEntitled = has_journey_premium_access($conn, $userId);
$canceled = isset($_GET['canceled']);
$configReady = journey_stripe_checkout_config_ready();
$error = '';
if (isset($_GET['error'])) {
    $map = [
        'invalid_plan' => 'Please choose Monthly or Annual.',
        'csrf' => 'Your session expired. Please try again.',
        'catalog' => 'Journey Premium is not available right now. Please try again later.',
        'stripe' => 'Checkout could not be started. Please try again.',
    ];
    $key = (string) $_GET['error'];
    $error = $map[$key] ?? 'Something went wrong. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Journey Premium — Choose a plan (review)</title>
    <link rel="stylesheet" href="/css/shared-styles.css">
    <style>
        .jp-wrap { max-width: 820px; margin: 40px auto; padding: 20px; }
        .jp-banner {
            background: #fff7ed; border: 1px solid #fdba74; color: #9a3412;
            padding: 12px 14px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95em;
        }
        .jp-trial {
            background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46;
            padding: 16px 18px; border-radius: 8px; margin: 18px 0 24px; line-height: 1.5;
        }
        .jp-trial-heading {
            display: block;
            font-size: 1.05em;
            font-weight: 700;
            color: #047857;
            margin: 0 0 8px;
        }
        .jp-trial-body {
            margin: 0;
            color: #065f46;
        }
        .jp-error {
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
            padding: 12px 14px; border-radius: 8px; margin-bottom: 16px;
        }
        .jp-cancel {
            background: #f8fafc; border: 1px solid #cbd5e1; color: #334155;
            padding: 12px 14px; border-radius: 8px; margin-bottom: 16px;
        }
        .jp-plans { display: grid; gap: 16px; margin: 20px 0 28px; }
        @media (min-width: 700px) { .jp-plans { grid-template-columns: 1fr 1fr; } }
        .jp-card {
            display: block; border: 2px solid #cbd5e1; border-radius: 10px; padding: 18px 18px 16px;
            background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s;
        }
        .jp-card:hover { border-color: #2c5282; }
        .jp-card:has(input:checked), .jp-card.is-selected {
            border-color: #2c5282; box-shadow: 0 0 0 1px #2c5282;
        }
        .jp-card input { position: absolute; opacity: 0; pointer-events: none; }
        .jp-name { font-size: 1.25em; font-weight: 700; color: #2c5282; }
        .jp-price { font-size: 2em; font-weight: 700; margin: 10px 0 6px; color: #111827; }
        .jp-price span { font-size: .45em; font-weight: 600; color: #64748b; }
        .jp-save { color: #047857; font-weight: 600; margin: 8px 0; }
        .jp-note { color: #475569; font-size: .95em; line-height: 1.45; }
        .jp-submit {
            width: 100%; max-width: 420px; display: block; margin: 0 auto;
            padding: 14px 18px; background: #2c5282; color: #fff; border: 0; border-radius: 8px;
            font-size: 1.05em; font-weight: 700; cursor: pointer;
        }
        .jp-submit:hover { background: #1e3a5f; }
        .jp-submit:disabled { background: #94a3b8; cursor: not-allowed; }
        .jp-fine { margin-top: 22px; color: #475569; font-size: .92em; line-height: 1.55; }
        .jp-fine ul { padding-left: 1.2em; }
        h1 { color: #1e3a5f; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="jp-wrap">
    <div class="jp-banner"><strong>Review only.</strong> This page is not linked from the public Journey yet.</div>

    <h1>Retirement Planning Journey Premium</h1>
    <p>Save your progress, revisit decisions, compare alternatives, and keep your retirement plan current. All six phases remain free to complete.</p>

    <?php if ($canceled): ?>
        <div class="jp-cancel">
            No subscription was started. You can choose a plan below when you are ready.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="jp-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($alreadyEntitled): ?>
        <div class="jp-trial">
            <strong>You already have Journey Premium access.</strong>
            Checkout is not needed right now. Manage billing from your account when Portal support is available.
        </div>
    <?php elseif (!$configReady): ?>
        <div class="jp-error">Journey Premium checkout is not configured on this server yet.</div>
    <?php else: ?>
        <div class="jp-trial">
            <strong class="jp-trial-heading">30-day free trial</strong>
            <p class="jp-trial-body">A payment method is required, but you will not be charged today. Cancel before the trial ends if you decide not to continue.</p>
        </div>

        <form method="post" action="/premium/journey-checkout.php" id="journey-plan-form">
            <?php echo rb_csrf_field(); ?>
            <div class="jp-plans" role="radiogroup" aria-label="Choose a Journey Premium plan">
                <label class="jp-card<?php echo $planPrefill === 'monthly' ? ' is-selected' : ''; ?>">
                    <input type="radio" name="plan" value="monthly" <?php echo $planPrefill === 'monthly' ? 'checked' : ''; ?> required>
                    <div class="jp-name">Monthly</div>
                    <div class="jp-price">$4<span> / month after trial</span></div>
                    <p class="jp-note">After the trial, $4 per month until canceled.</p>
                </label>
                <label class="jp-card<?php echo $planPrefill === 'annual' ? ' is-selected' : ''; ?>">
                    <input type="radio" name="plan" value="annual" <?php echo $planPrefill === 'annual' ? 'checked' : ''; ?> required>
                    <div class="jp-name">Annual</div>
                    <div class="jp-price">$40<span> / year after trial</span></div>
                    <p class="jp-save">Saves $8 compared with paying monthly for 12 months.</p>
                    <p class="jp-note">After the trial, $40 per year until canceled.</p>
                </label>
            </div>

            <button type="submit" class="jp-submit">Continue to secure checkout</button>
        </form>

        <div class="jp-fine">
            <ul>
                <li>30-day free trial</li>
                <li>A payment method is required</li>
                <li>You will not be charged today</li>
                <li>Cancel before the trial ends if you decide not to continue</li>
            </ul>
            <p>Signed in as <?php echo htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8'); ?>.</p>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var form = document.getElementById('journey-plan-form');
    if (!form) return;
    var cards = form.querySelectorAll('.jp-card');
    function sync() {
        cards.forEach(function (card) {
            var input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-selected', !!(input && input.checked));
        });
    }
    cards.forEach(function (card) {
        card.addEventListener('click', sync);
        var input = card.querySelector('input');
        if (input) input.addEventListener('change', sync);
    });
    sync();
})();
</script>
</body>
</html>
