<?php
/**
 * Password setup by email is intentionally disabled because this site does not
 * use a transactional email provider.
 */
require_once __DIR__ . '/includes/init.php';
$error = 'Password setup by email is not available. Please contact support for assistance.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/analytics.php'; ?>
    <?php include __DIR__ . '/includes/social-metadata.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Password - calcforadvisors.com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 420px;
            width: 100%;
        }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { font-size: 22px; color: #1e293b; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 14px; }
        input[type="email"] {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
        }
        input:focus { outline: none; border-color: #2c5282; }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .message { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .home-link { display: block; text-align: center; margin-bottom: 18px; color: #2c5282; text-decoration: none; font-size: 14px; }
        .footer-links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .footer-links a { color: #2c5282; text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="index.html" class="home-link">← Back to calcforadvisors.com</a>
        <div class="logo">
            <h1>Set Up Your Password</h1>
        </div>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <div class="footer-links" style="margin-top: 18px;">
            <a href="login.php">Back to login</a>
        </div>
    </div>
</body>
</html>
