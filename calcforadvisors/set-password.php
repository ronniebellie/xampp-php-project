<?php
/**
 * Set password via token from email (calcforadvisors).
 */
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';

$error = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!defined('CALCFORADVISORS_AUTH_SECRET') || CALCFORADVISORS_AUTH_SECRET === 'replace-with-random-secret-32chars') {
    $error = 'Password setup is not configured. Please contact support.';
} elseif (empty($token)) {
    $error = 'Invalid or missing link. Please request a new setup link from the login page.';
} else {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        $error = 'Invalid link. Please request a new setup link.';
    } else {
        list($encEmail, $encExpiry, $sig) = $parts;
        $payload = $encEmail . '.' . $encExpiry;
        $expected = hash_hmac('sha256', $payload, CALCFORADVISORS_AUTH_SECRET);

        if (!hash_equals($expected, $sig)) {
            $error = 'Invalid link. Please request a new setup link.';
        } else {
            $email = base64_decode($encEmail, true);
            $expiry = (int) base64_decode($encExpiry, true);

            if ($email === false || $expiry < time()) {
                $error = 'This link has expired. Please request a new setup link.';
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $password = $_POST['password'] ?? '';
                    $confirm = $_POST['password_confirm'] ?? '';

                    if (strlen($password) < 8) {
                        $error = 'Password must be at least 8 characters.';
                    } elseif ($password !== $confirm) {
                        $error = 'Passwords do not match.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE calcforadvisors_subscribers SET password_hash = ? WHERE email = ? AND status = ?');
                        $status = 'active';
                        $stmt->bind_param('sss', $hash, $email, $status);
                        $stmt->execute();

                        if ($stmt->affected_rows > 0) {
                            $stmt->close();
                            $conn->close();
                            header('Location: login.php?msg=password_set');
                            exit;
                        }
                        $stmt->close();
                        $error = 'Could not update password. Please request a new link.';
                    }
                }
            }
        }
    }
}

$showForm = empty($error) || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - calcforadvisors.com</title>
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
        input[type="password"] {
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
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .home-link { display: block; text-align: center; margin-bottom: 18px; color: #2c5282; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="index.html" class="home-link">← Back to calcforadvisors.com</a>
        <div class="logo">
            <h1>Create Your Password</h1>
        </div>
        <?php if ($error && !$showForm): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <p style="text-align: center;"><a href="request-set-password.php">Request a new link</a></p>
        <?php elseif ($showForm): ?>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
                <button type="submit" class="btn">Set Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
