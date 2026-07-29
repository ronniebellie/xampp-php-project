<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/password_reset.php';
require_once __DIR__ . '/../includes/send_email.php';

$message = '';
$error = '';

if (!rb_password_reset_configured()) {
    $error = 'Password reset is not configured. Please contact support.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT id, email FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();

            $token = rb_password_reset_create_token($user['email']);
            $url = rb_auth_base_url() . '/auth/reset-password.php?token=' . urlencode($token);

            $subject = 'Reset your Ron Belisle account password';
            $body = "Hi,\n\n"
                . "We received a request to reset the password for your ronbelisle.com account.\n\n"
                . "Reset your password (link expires in 24 hours):\n\n"
                . $url . "\n\n"
                . "If you didn't request this, you can ignore this email. Your password won't change.\n\n"
                . "— Ron Belisle Financial Calculators\n";

            if (send_email_smtp($user['email'], $subject, $body)) {
                $message = 'If that email is in our system, we sent a reset link. Check your inbox and spam folder.';
            } else {
                $mailError = function_exists('rb_send_email_last_error') ? rb_send_email_last_error() : null;
                if ($mailError === 'credits_exceeded' || $mailError === 'auth_failed' || $mailError === 'config_incomplete' || $mailError === 'config_missing') {
                    $error = 'Password reset email cannot be sent right now because email delivery is unavailable. Please contact support at ronbelisle@gmail.com and we will help you reset your password.';
                } else {
                    $error = 'We could not send the email. Please try again later or contact support at ronbelisle@gmail.com.';
                }
                error_log('forgot-password: send failed for user_id=' . (int) $user['id'] . ' err=' . ($mailError ?? 'unknown'));
            }
        } else {
            $stmt->close();
            $message = 'If that email is in our system, we sent a reset link. Check your inbox and spam folder.';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ron Belisle</title>
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
        input[type="email"] {
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
        .message { background: #d1fae5; color: #065f46; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .home-link { display: block; text-align: center; margin-bottom: 20px; color: #3b82f6; text-decoration: none; font-size: 14px; }
        .footer-links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .footer-links a { color: #3b82f6; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="../" class="home-link">← Back to Home</a>
        <div class="logo">
            <h1>Forgot Password</h1>
            <p>We'll email you a link to reset your password</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <div class="footer-links"><a href="login.php">Back to log in</a></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
            <div class="footer-links">
                Remember your password? <a href="login.php">Log in</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
