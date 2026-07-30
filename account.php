<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/account_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$password_message = '';
$password_error = '';

// Handle change-password before loading display fields.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!rb_csrf_validate($_POST['csrf_token'] ?? null)) {
        $password_error = 'Your session expired. Please try again.';
    } else {
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $hashRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $currentHash = is_array($hashRow) ? (string) ($hashRow['password_hash'] ?? '') : '';

        $validated = rb_account_validate_password_change(
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? ''),
            $currentHash
        );

        if (empty($validated['ok'])) {
            $password_error = (string) ($validated['error'] ?? 'Could not update password.');
        } else {
            $newHash = password_hash((string) $_POST['new_password'], PASSWORD_DEFAULT);
            $upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->bind_param('si', $newHash, $user_id);
            if ($upd->execute() && $upd->affected_rows >= 0) {
                $password_message = 'Your password has been updated.';
            } else {
                $password_error = 'Could not update password. Please try again.';
            }
            $upd->close();
        }
    }
}

$stmt = $conn->prepare('SELECT email, subscription_status, created_at, stripe_subscription_id FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$sub = null;
$email = null;
$created_at = null;
$stripe_sub_id = null;
$stmt->bind_result($email, $sub, $created_at, $stripe_sub_id);
$user = $stmt->fetch()
    ? [
        'email' => $email,
        'subscription_status' => $sub,
        'created_at' => $created_at,
        'stripe_subscription_id' => $stripe_sub_id,
    ]
    : null;
$stmt->close();

if (!$user) {
    header('Location: auth/login.php');
    exit;
}

// Calculator Premium (legacy site-wide premium) — users.subscription_status.
$is_calculator_premium = ($user['subscription_status'] === 'premium');
// Journey Premium — authoritative product entitlement (same as Journey chrome).
$journeyStatus = rb_account_journey_status($conn, $user_id);
$is_journey_premium = !empty($journeyStatus['hasAccess']);

// Banner helpers used by shared includes.
$is_premium = $is_calculator_premium;
$isPremium = $is_calculator_premium;
$isLoggedIn = true;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your Ron Belisle account, Journey Premium status, and password.">
    <title>My Account - Ron Belisle</title>
    <?php $og_title = 'My Account - Ron Belisle'; $og_description = 'Manage your Ron Belisle account, Journey Premium status, and password.'; include(__DIR__ . '/includes/og-twitter-meta.php'); ?>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .account-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .account-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .account-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #1e293b;
        }
        .account-section {
            margin-bottom: 30px;
        }
        .account-section h2 {
            font-size: 20px;
            color: #334155;
            margin-bottom: 15px;
        }
        .account-section-muted {
            margin-top: 8px;
            margin-bottom: 24px;
            padding-top: 22px;
            border-top: 1px solid #e2e8f0;
        }
        .account-section-muted h2 {
            font-size: 15px;
            font-weight: 600;
            color: #64748b;
            margin: 0 0 12px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .other-product {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px 16px;
        }
        .other-product-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #475569;
        }
        .other-product-detail {
            margin: 4px 0 0;
            font-size: 13px;
            line-height: 1.45;
            color: #64748b;
            flex: 1 1 100%;
        }
        .other-product-link {
            font-size: 14px;
            font-weight: 600;
            color: #1d4ed8;
            text-decoration: none;
            white-space: nowrap;
        }
        .other-product-link:hover {
            text-decoration: underline;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            color: #1e293b;
            text-align: right;
        }
        .premium-badge {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
        }
        .status-muted {
            color: #64748b;
        }
        .status-detail {
            margin: 12px 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1d4ed8;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        .btn:hover {
            background: #1e40af;
        }
        .btn-secondary {
            background: #64748b;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .form-group input[type="password"] {
            width: 100%;
            max-width: 420px;
            padding: 11px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .message-ok {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .message-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .password-hint {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php if ($is_calculator_premium && $is_journey_premium): ?>
    <div class="premium-banner premium-active" style="background: linear-gradient(135deg, #2563eb 0%, #059669 100%); color: white; padding: 20px; text-align: center; margin-bottom: 30px; border-radius: 8px;">
        <h3 style="margin: 0 0 10px 0; font-size: 24px;">✓ Premium products active</h3>
        <p style="margin: 0; opacity: 0.95;">Calculator Premium and Journey Premium are both active on this account.</p>
    </div>
    <?php elseif ($is_journey_premium): ?>
    <div class="premium-banner premium-active" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 20px; text-align: center; margin-bottom: 30px; border-radius: 8px;">
        <h3 style="margin: 0 0 10px 0; font-size: 24px;">✓ Journey Premium active</h3>
        <p style="margin: 0; opacity: 0.95;">Your Retirement Planning Journey Premium access is active.</p>
    </div>
    <?php elseif ($is_calculator_premium): ?>
    <div class="premium-banner premium-active" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 20px; text-align: center; margin-bottom: 30px; border-radius: 8px;">
        <h3 style="margin: 0 0 10px 0; font-size: 24px;">✓ Calculator Premium active</h3>
        <p style="margin: 0; opacity: 0.95;">You have full access to premium calculator features. Journey Premium is a separate product.</p>
    </div>
    <?php endif; ?>

    <div class="wrap">
        <div class="account-container">
            <div class="account-header">
                <h1>My Account</h1>
                <p style="color: #64748b; margin: 0;">Welcome back, <?php echo htmlspecialchars($userName); ?>!</p>
                <?php
                $msg = $_GET['msg'] ?? '';
                if ($msg === 'no_stripe'): ?>
                    <p style="color: #b45309; margin-top: 10px; font-size: 14px;">Your subscription was granted separately. Contact support if you need to make changes.</p>
                <?php elseif ($msg === 'error'): ?>
                    <p style="color: #dc2626; margin-top: 10px; font-size: 14px;">Could not open subscription management. Please try again or contact support.</p>
                    <?php
                    $err = $_SESSION['billing_portal_error'] ?? '';
                    unset($_SESSION['billing_portal_error']);
                    if ($err): ?>
                    <p style="color: #92400e; margin-top: 8px; font-size: 13px; background: #fef3c7; padding: 10px; border-radius: 6px;">Details: <?php echo htmlspecialchars($err); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="account-section">
                <h2>Account Information</h2>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>

            <div class="account-section">
                <h2>Journey Premium</h2>
                <p class="status-detail" style="margin-top:0;">Optional cloud saving and ongoing workspace for the Retirement Planning Journey.</p>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <?php if ($is_journey_premium): ?>
                            <span class="premium-badge"><?php echo htmlspecialchars($journeyStatus['label']); ?></span>
                        <?php else: ?>
                            <span class="status-muted"><?php echo htmlspecialchars($journeyStatus['label']); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <p class="status-detail"><?php echo htmlspecialchars($journeyStatus['detail']); ?></p>
                <p style="margin-top: 15px;">
                    <a href="<?php echo htmlspecialchars($journeyStatus['actionUrl']); ?>" class="btn"<?php echo $is_journey_premium ? ' style="background:#059669;"' : ''; ?>>
                        <?php echo htmlspecialchars($journeyStatus['actionLabel']); ?>
                    </a>
                    <?php if (!empty($journeyStatus['secondaryActionLabel']) && !empty($journeyStatus['secondaryActionUrl'])): ?>
                        <a href="<?php echo htmlspecialchars($journeyStatus['secondaryActionUrl']); ?>" class="btn btn-secondary" style="margin-left: 10px;">
                            <?php echo htmlspecialchars($journeyStatus['secondaryActionLabel']); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($is_calculator_premium): ?>
            <div class="account-section">
                <h2>Calculator Premium</h2>
                <p class="status-detail" style="margin-top:0;">Scenario saving, exports, and advanced calculator features on ronbelisle.com. Separate from Journey Premium.</p>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="premium-badge">Active</span>
                    </span>
                </div>
                <p style="color: #334155; margin-top: 15px;"><strong>You have full access to premium calculator features:</strong></p>
                <ul style="color: #475569; line-height: 1.8; margin: 10px 0 20px 0;">
                    <li><strong>Save and compare unlimited scenarios</strong> — Store your calculator inputs and results, recall them later, and compare two scenarios side by side.</li>
                    <li><strong>Export PDF and CSV reports</strong> — Download professional PDF summaries or spreadsheet data for your records or advisors.</li>
                    <li><strong>AI-generated plain-language explanations</strong> — After running any comparison, click "Explain my results" for a clear, educational breakdown of your specific numbers.</li>
                    <li><strong>Advanced projections</strong> — See full year-by-year projections (e.g., ages 73–100) instead of limited previews.</li>
                    <li><strong>Ad-free experience</strong> — Use all tools without promotional interruptions.</li>
                </ul>
                <?php if (!empty($user['stripe_subscription_id'])): ?>
                <p style="margin-top: 15px;">
                    <a href="billing_portal.php" class="btn" style="background: #059669;">Manage Calculator Premium subscription</a>
                    <span style="font-size: 13px; color: #64748b; margin-left: 8px;">Cancel, update payment method, or view invoices</span>
                </p>
                <?php else: ?>
                <p style="margin-top: 15px; padding: 14px; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <strong>Your Calculator Premium subscription was set up manually.</strong> To cancel or make changes, please <a href="mailto:ronbelisle@gmail.com?subject=Subscription%20change%20request">contact support</a>.
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="account-section" id="change-password">
                <h2>Change Password</h2>
                <?php if ($password_message): ?>
                    <div class="message-ok"><?php echo htmlspecialchars($password_message); ?></div>
                <?php endif; ?>
                <?php if ($password_error): ?>
                    <div class="message-error"><?php echo htmlspecialchars($password_error); ?></div>
                <?php endif; ?>
                <p class="password-hint">Use at least 8 characters. You’ll stay signed in after updating.</p>
                <form method="POST" action="account.php#change-password" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">
                    <?php echo rb_csrf_field(); ?>
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn">Update Password</button>
                </form>
            </div>

            <?php if (!$is_calculator_premium): ?>
            <div class="account-section account-section-muted">
                <h2>Other Products</h2>
                <div class="other-product">
                    <p class="other-product-title">Calculator Premium</p>
                    <a class="other-product-link" href="premium.html">Learn More</a>
                    <p class="other-product-detail">Advanced planning features for the retirement calculators.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="account-section">
                <h2>Actions</h2>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
                <a href="auth/logout.php" class="btn btn-secondary" style="margin-left: 10px;">Log Out</a>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
