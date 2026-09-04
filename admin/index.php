<?php
/**
 * Private administrator home.
 * Not linked from public navigation.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_admin_trials.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_feedback.php';

rb_require_admin($conn);

$newCount = journey_admin_count_unviewed_signups($conn);
$feedbackNew = journey_feedback_count_new($conn);
$pageTitle = 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — Ron Belisle</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        body { background: #f1f5f9; color: #1e293b; }
        .admin-wrap { max-width: 960px; margin: 32px auto; padding: 0 16px 48px; }
        .admin-card {
            background: #fff; border-radius: 16px; padding: 28px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .admin-card h1 { margin: 0 0 8px; font-size: 1.6rem; }
        .admin-card p { color: #64748b; margin: 0 0 20px; line-height: 1.5; }
        .admin-nav { display: flex; flex-wrap: wrap; gap: 12px 18px; margin-bottom: 22px; }
        .admin-nav a {
            color: #1d4ed8; text-decoration: none; font-weight: 600; font-size: 0.95rem;
        }
        .admin-nav a.is-active { color: #0f172a; text-decoration: underline; }
        .admin-list { list-style: none; padding: 0; margin: 0; }
        .admin-list li { margin: 0 0 12px; }
        .admin-list a {
            display: inline-block; padding: 12px 14px; border: 1px solid #e2e8f0;
            border-radius: 10px; text-decoration: none; color: #0f172a; font-weight: 600;
        }
        .admin-list a:hover { border-color: #93c5fd; background: #f8fafc; }
        .admin-badge {
            display: inline-block; margin-left: 8px; padding: 2px 8px;
            border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 0.8rem;
        }
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php echo journey_admin_nav_html($conn, 'home'); ?>
    <div class="admin-card">
        <h1>Administrator</h1>
        <p>Private tools for site operations. Not available to customers.</p>
        <ul class="admin-list">
            <li>
                <a href="/admin/journey-premium-trials.php">
                    Recent Signups
                    <?php if ($newCount > 0): ?>
                        <span class="admin-badge"><?php echo (int) $newCount; ?> new</span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="/admin/journey-feedback.php">
                    Journey Feedback
                    <?php if ($feedbackNew > 0): ?>
                        <span class="admin-badge"><?php echo (int) $feedbackNew; ?> new</span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="/admin/calcforadvisors-passwords.php">
                    CalcForAdvisors Password Provisioning
                </a>
            </li>
            <li>
                <a href="/admin/user-passwords.php">
                    Consumer Account Password Reset
                </a>
            </li>
        </ul>
    </div>
</div>
</body>
</html>
