<?php
/**
 * Request a "set password" link for calcforadvisors subscribers.
 * Sends an email with a signed token link (no DB storage).
 */
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';
require_once CALCFORADVISORS_INCLUDES . '/send_email.php';

$message = '';
$error = '';

if (!defined('CALCFORADVISORS_AUTH_SECRET') || CALCFORADVISORS_AUTH_SECRET === 'replace-with-random-secret-32chars') {
    $error = 'Password setup is not configured. Please contact support.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $conn->prepare('SELECT id, email, status FROM calcforadvisors_subscribers WHERE email = ? AND status = ?');
        $status = 'active';
        $stmt->bind_param('ss', $email, $status);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows >= 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            error_log('request-set-password: found user ' . $user['email']);

            $expiry = time() + (60 * 60 * 24); // 24 hours
            $payload = base64_encode($user['email']) . '.' . base64_encode((string)$expiry);
            $sig = hash_hmac('sha256', $payload, CALCFORADVISORS_AUTH_SECRET);
            $token = $payload . '.' . $sig;

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'calcforadvisors.com';
            $base = $scheme . '://' . $host . dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $base = rtrim($base, '/');
            $url = $base . '/set-password.php?token=' . urlencode($token);

            $subject = 'Set up your calcforadvisors.com account';
            $body = "Hi,\n\nClick the link below to set your password and access your calcforadvisors account:\n\n$url\n\nThis link expires in 24 hours. If you didn't request this, you can ignore this email.\n\n— calcforadvisors.com";

            $sendOk = send_email_smtp($user['email'], $subject, $body);
            error_log('request-set-password: send_result=' . ($sendOk ? 'ok' : 'fail') . ' for ' . $user['email']);
            if ($sendOk) {
                $message = 'If that email is in our system, we\'ve sent you a link to set your password. Check your inbox (and spam folder).';
            } else {
                $error = 'We couldn\'t send the email. Please contact support@calcforadvisors.com.';
            }
        } else {
            $stmt->close();
            error_log('request-set-password: user not found or inactive, email=' . $email);
            // Don't reveal whether the email exists
            $message = 'If that email is in our system, we\'ve sent you a link to set your password. Check your inbox (and spam folder).';
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
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <div class="footer-links">
                <a href="login.php">Back to login</a>
            </div>
        <?php elseif ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn">Send Setup Link</button>
            </form>
        <?php else: ?>
            <p style="margin-bottom: 20px; color: #64748b; font-size: 14px;">Enter the email you used when subscribing. We'll send you a link to set your password.</p>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn">Send Setup Link</button>
            </form>
            <div class="footer-links" style="margin-top: 18px;">
                Already have a password? <a href="login.php">Log in</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
