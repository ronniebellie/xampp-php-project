<?php
session_start();
require_once 'includes/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, subscription_status, created_at, stripe_subscription_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sub = null;
$email = null;
$created_at = null;
$stripe_sub_id = null;
$stmt->bind_result($email, $sub, $created_at, $stripe_sub_id);
$user = $stmt->fetch() ? ['email' => $email, 'subscription_status' => $sub, 'created_at' => $created_at, 'stripe_subscription_id' => $stripe_sub_id] : null;
$stmt->close();

if (!$user) {
    header('Location: auth/login.php');
    exit;
}

$is_premium = ($user['subscription_status'] === 'premium');
$isPremium = $is_premium;  // banner include expects $isPremium
$isLoggedIn = true;       // banner include may check this
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your Ron Belisle account. View subscription, saved scenarios, and premium status.">
    <title>Account Management - Ron Belisle</title>
    <?php $og_title = 'Account Management - Ron Belisle'; $og_description = 'Manage your Ron Belisle account. View subscription, saved scenarios, and premium status.'; include(__DIR__ . '/includes/og-twitter-meta.php'); ?>
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
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            color: #1e293b;
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1d4ed8;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <?php if ($is_premium): ?>
    <div class="premium-banner premium-active" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 20px; text-align: center; margin-bottom: 30px; border-radius: 8px;">
        <h3 style="margin: 0 0 10px 0; font-size: 24px;">✓ Premium Active</h3>
        <p style="margin: 0; opacity: 0.95;">You have full access to all Premium features across the site.</p>
    </div>
    <?php else: ?>
    <?php include('includes/premium-banner-include.php'); ?>
    <?php endif; ?>
    
    <div class="wrap">
        <div class="account-container">
            <div class="account-header">
                <h1>Account Management</h1>
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
                <h2>Subscription Status</h2>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <?php if ($is_premium): ?>
                            <span class="premium-badge">✨ Premium Member</span>
                        <?php else: ?>
                            <span style="color: #64748b;">Free Account</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($is_premium): ?>
                    <p style="color: #334155; margin-top: 15px;"><strong>You have full access to all premium features:</strong></p>
                    <ul style="color: #475569; line-height: 1.8; margin: 10px 0 20px 0;">
                        <li><strong>Save and compare unlimited scenarios</strong> — Store your calculator inputs and results, recall them later, and compare two scenarios side by side.</li>
                        <li><strong>Export PDF and CSV reports</strong> — Download professional PDF summaries or spreadsheet data for your records or advisors.</li>
                        <li><strong>AI-generated plain-language explanations</strong> — After running any comparison, click "Explain my results" for a clear, educational breakdown of your specific numbers.</li>
                        <li><strong>Advanced projections</strong> — See full year-by-year projections (e.g., ages 73–100) instead of limited previews.</li>
                        <li><strong>Ad-free experience</strong> — Use all tools without promotional interruptions.</li>
                    </ul>
                    <?php if (!empty($user['stripe_subscription_id'])): ?>
                    <p style="margin-top: 15px;">
                        <a href="billing_portal.php" class="btn" style="background: #059669;">Manage subscription</a>
                        <span style="font-size: 13px; color: #64748b; margin-left: 8px;">Cancel, update payment method, or view invoices</span>
                    </p>
                    <?php else: ?>
                    <p style="margin-top: 15px; padding: 14px; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                        <strong>Your subscription was set up manually.</strong> To cancel or make changes, please <a href="mailto:ronbelisle@gmail.com?subject=Subscription%20change%20request">contact support</a>.
                    </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: #64748b; margin-top: 15px;">
                        Upgrade to Premium to unlock scenario saving, PDF exports, AI-generated plain-language explanations of your specific results, and advanced projections.
                    </p>
                    <a href="subscribe.php" class="btn">Upgrade to Premium</a>
                <?php endif; ?>
            </div>

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
