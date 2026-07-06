<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/password_reset.php';

$error = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$email = false;
$showForm = false;

if (!rb_password_reset_configured()) {
    $error = 'Password reset is not configured. Please contact support.';
} elseif ($token === '') {
    $error = 'Invalid or missing link. Please request a new reset link.';
} else {
    $email = rb_password_reset_verify_token($token);
    if ($email === false) {
        $error = 'This link is invalid or has expired. Please request a new reset link.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
            $showForm = true;
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
            $showForm = true;
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
            $stmt->bind_param('ss', $hash, $email);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                header('Location: login.php?msg=password_reset');
                exit;
            }
            $stmt->close();
            $error = 'Could not update password. Please request a new reset link.';
            $showForm = true;
        }
    } else {
        $showForm = true;
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
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
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
                <a href="forgot-password.php">Request a new reset link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
