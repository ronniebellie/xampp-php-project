<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth_flow_helpers.php';

$error = '';
$success = '';

rb_auth_capture_trial_intent_from_request();
rb_auth_capture_return_from_request();
$trialIntent = rb_auth_is_trial_intent();

if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success = 'Your password was updated. You can log in now.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $stmt = $conn->prepare("SELECT id, email, password_hash, full_name, subscription_status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                rb_auth_login_user($user, $remember);

                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();

                rb_auth_redirect_after_auth();
            } elseif ($trialIntent) {
                $error = 'Incorrect password. Try again or use Forgot password below.';
            } else {
                $error = 'Invalid email or password';
            }
        } elseif ($trialIntent) {
            rb_auth_redirect_to_trial_signup($email);
        } else {
            $error = 'Invalid email or password';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Ron Belisle Financial Calculators</title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/analytics.php'; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .logo p {
            color: #64748b;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
        }
        
        .remember-me label {
            margin-bottom: 0;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
        }
        
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
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error-help {
            margin: -12px 0 20px;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }

        .error-help a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .error-help a:hover {
            text-decoration: underline;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .forgot-link {
            display: block;
            text-align: right;
            margin-top: 8px;
            font-size: 13px;
        }

        .forgot-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link a:hover {
            text-decoration: underline;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }
        
        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }

        .footer-links-secondary {
            margin-top: 12px;
            font-size: 13px;
            color: #64748b;
        }
        
        .home-link {
            display: block;
            text-align: center;
            margin-bottom: 20px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        
        .home-link:hover {
            text-decoration: underline;
        }

        .trial-callout {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.55;
        }

        .trial-callout strong {
            display: block;
            margin-bottom: 4px;
            color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="../" class="home-link">← Back to Home</a>
        
        <div class="logo">
            <?php if ($trialIntent): ?>
            <h1>Log In to Continue</h1>
            <p>Use your existing account email and password to continue to billing.</p>
            <?php else: ?>
            <h1>Welcome Back</h1>
            <p>Log in to access premium features</p>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form id="login-form" method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="forgot-link"><a href="forgot-password.php">Forgot password?</a></div>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1"<?php echo (!empty($_POST['remember']) ? ' checked' : ''); ?>>
                <label for="remember">Stay logged in</label>
            </div>
            
            <button type="submit" class="btn">Log In</button>
        </form>
        
        <div class="footer-links">
            <?php if ($trialIntent): ?>
            <a href="register.php<?php echo rb_auth_companion_query(); ?>">Sign up to start your trial</a>
            <?php else: ?>
            Don't have an account? <a href="register.php<?php echo rb_auth_companion_query(); ?>">Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>