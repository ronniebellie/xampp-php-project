<?php
/**
 * Free sign-up for calcforadvisors (no Stripe).
 * Creates subscriber with plan='free', no Stripe IDs.
 */
ob_start();
// Ensure session cookie works across redirect (Safari/compatibility)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id, password_hash, stripe_customer_id FROM calcforadvisors_subscribers WHERE email = ? AND status = ?');
        $status = 'active';
        $stmt->bind_param('ss', $email, $status);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows >= 1) {
            $existing = $result->fetch_assoc();
            $stmt->close();
            if (!empty($existing['password_hash'])) {
                $error = 'An account with this email already exists. <a href="login.php">Log in</a> instead.';
            } else {
                $error = 'This email is already registered. <a href="request-set-password.php">Request a password setup link</a> to get started.';
            }
        } else {
            $stmt->close();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $plan = 'free';

            $stmt = $conn->prepare('INSERT INTO calcforadvisors_subscribers (email, plan, status, stripe_customer_id, stripe_subscription_id, password_hash) VALUES (?, ?, ?, NULL, NULL, ?)');
            $stmt->bind_param('ssss', $email, $plan, $status, $hash);

            if ($stmt->execute()) {
                $newId = (int) $conn->insert_id;
                $stmt->close();

                // One-time token for redirect (works in Safari Private mode when cookies are blocked)
                $token = bin2hex(random_bytes(24));
                $expires = date('Y-m-d H:i:s', time() + 300); // 5 min
                $upd = $conn->prepare('UPDATE calcforadvisors_subscribers SET trial_login_token = ?, trial_login_token_expires = ? WHERE id = ?');
                $upd->bind_param('ssi', $token, $expires, $newId);
                $upd->execute();
                $upd->close();
                $conn->close();

                $_SESSION['calcforadvisors_subscriber_id'] = $newId;
                $_SESSION['calcforadvisors_subscriber_email'] = $email;
                $_SESSION['calcforadvisors_subscriber_plan'] = 'free';
                $_SESSION['calcforadvisors_subscriber_status'] = 'active';

                ob_end_clean();
                header('Location: trial-setup.php?token=' . urlencode($token) . '&msg=welcome');
                exit;
            } else {
                error_log('calcforadvisors register-free INSERT failed: ' . $stmt->error . ' (errno: ' . $stmt->errno . ')');
                $stmt->close();
                $error = 'Could not create account. Please try again or contact support.';
            }
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
    <title>Try Free for 30 Days - calcforadvisors.com</title>
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
        .logo h1 { font-size: 22px; color: #1e293b; margin-bottom: 6px; }
        .logo p { color: #64748b; font-size: 14px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 14px; }
        input[type="email"], input[type="password"] {
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
        .btn:hover { opacity: 0.95; }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .error a { color: #dc2626; text-decoration: underline; }
        .footer-links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .footer-links a { color: #2c5282; text-decoration: none; font-weight: 600; }
        .home-link { display: block; text-align: center; margin-bottom: 18px; color: #2c5282; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="index.html" class="home-link">← Back to calcforadvisors.com</a>
        <div class="logo">
            <h1>Try Free for 30 Days</h1>
            <p>Your logo and firm name on a shareable page with 14 retirement calculators. Core tools without <a href="https://ronbelisle.com/premium.html" target="_blank" rel="noopener">Premium</a> features. No credit card required.</p>
        </div>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>
        <div class="footer-links">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>
