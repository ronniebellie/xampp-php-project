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
$stmt = $conn->prepare("SELECT email, subscription_status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sub = null;
$email = null;
$created_at = null;
$stmt->bind_result($email, $sub, $created_at);
$user = $stmt->fetch() ? ['email' => $email, 'subscription_status' => $sub, 'created_at' => $created_at] : null;
$stmt->close();

if (!$user) {
    header('Location: auth/login.php');
    exit;
}

$is_premium = ($user['subscription_status'] === 'premium');
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - Ron Belisle</title>
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
    <?php include('includes/premium-banner-include.php'); ?>
    
    <div class="wrap">
        <div class="account-container">
            <div class="account-header">
                <h1>Account Management</h1>
                <p style="color: #64748b; margin: 0;">Welcome back, <?php echo htmlspecialchars($userName); ?>!</p>
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
                    <p style="color: #059669; margin-top: 15px;">
                        <strong>You have full access to all premium features:</strong><br>
                        • Save and compare unlimited scenarios<br>
                        • Export PDF and CSV reports<br>
                        • Access advanced projections<br>
                        • Ad-free experience
                    </p>
                <?php else: ?>
                    <p style="color: #64748b; margin-top: 15px;">
                        Upgrade to Premium to unlock scenario saving, PDF exports, and advanced projections.
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
