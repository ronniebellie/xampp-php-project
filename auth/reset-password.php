<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/password_reset.php';
$secret = rb_password_reset_secret();

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
$error = '';
$showForm = false;
$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? '');
$token = is_string($token) ? $token : '';
$email = rb_password_token_email($token);
if ($email === null) {
    $error = 'This link is invalid or has expired. Please contact support.';
} else {
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($accountId, $oldHash);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || !rb_password_token_verify($token, $oldHash, 'consumer-password', $secret)) {
        $error = 'This link is invalid or has expired. Please contact support.';
    } else {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';
            if (!is_string($csrf) || !rb_csrf_validate($csrf)) {
                http_response_code(403);
                $error = 'The form expired. Please reload the link and try again.';
            } elseif (!is_string($password) || !is_string($confirm) || strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if (rb_password_token_redeem($conn, 'users', (int) $accountId, $oldHash, $hash)) {
                    rb_csrf_rotate();
                    header('Location: login.php?msg=password_reset');
                    exit;
                }
                $showForm = false;
                $error = 'This link is no longer valid. Please contact support.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Ron Belisle</title>
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
            max-width: 450px;
            width: 100%;
        }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { font-size: 24px; color: #1e293b; margin-bottom: 8px; }
        .logo p { color: #64748b; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 8px; font-size: 14px; }
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
        }
        input:focus { outline: none; border-color: #3b82f6; }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .error { background: #fee2e2; color: #dc2626; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .home-link { display: block; text-align: center; margin-bottom: 20px; color: #3b82f6; text-decoration: none; font-size: 14px; }
        .footer-links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .footer-links a { color: #3b82f6; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="login.php" class="home-link">← Back to Log In</a>
        <div class="logo">
            <h1>Reset Password</h1>
            <p>Choose a new password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <form method="POST" action="reset-password.php">
                <?php echo rb_csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        <?php else: ?>
            <div class="footer-links">
                Password reset by email is unavailable. Please contact support.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
