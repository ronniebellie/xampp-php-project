<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth_flow_helpers.php';

$error = '';
$success = '';

rb_auth_capture_trial_intent_from_request();
rb_auth_capture_return_from_request();
$journeyTrialIntent = rb_auth_is_journey_trial_intent();
$trialIntent = rb_auth_is_trial_intent();

$prefillEmail = '';
if (!empty($_SESSION['trial_signup_email'])) {
    $prefillEmail = (string) $_SESSION['trial_signup_email'];
    unset($_SESSION['trial_signup_email']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $prefillEmail = (string) $_POST['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = htmlspecialchars($_POST['full_name']);
    
    // Validation
    if (empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists ($conn from includes/db_config.php)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password and insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $password_hash, $full_name);
            
            if ($stmt->execute()) {
                $user_id = (int) $conn->insert_id;
                $stmt->close();

                $user = [
                    'id' => $user_id,
                    'email' => $email,
                    'full_name' => $full_name,
                    'subscription_status' => 'free',
                ];
                rb_auth_login_user($user, false);

                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                $conn->close();

                rb_auth_redirect_after_auth();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }

        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Ron Belisle Financial Calculators</title>
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
        
        input[type="text"],
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
        
        .success {
            background: #d1fae5;
            color: #059669;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="../" class="home-link">← Back to Home</a>
        
        <div class="logo">
            <?php if ($journeyTrialIntent): ?>
            <h1>Create Your Account</h1>
            <p>Create your free account, then start your 30-day Journey Premium trial.</p>
            <?php elseif ($trialIntent): ?>
            <h1>Start Your 7-Day Free Premium Trial</h1>
            <p>Create your free account to continue. Next you'll pick a plan — your trial starts before any charge.</p>
            <?php else: ?>
            <h1>Create Your Account</h1>
            <p>Sign up for premium features</p>
            <?php endif; ?>
        </div>

        <?php if ($trialIntent): ?>
            <div class="trial-callout">
                You'll add a payment method at checkout so access can continue after the trial, but you won't be charged for 7 days. Cancel anytime.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($prefillEmail); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small style="color: #64748b; font-size: 12px;">Minimum 8 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <?php if ($journeyTrialIntent): ?>
            <p class="journey-trial-expect" style="color:#475569;font-size:0.92em;line-height:1.45;margin:0 0 12px;">
                After creating your account, you’ll continue to secure Stripe Checkout. You will not be charged today.
            </p>
            <?php endif; ?>
            <button type="submit" class="btn"><?php echo ($trialIntent || $journeyTrialIntent) ? 'Create Account &amp; Continue' : 'Create Account'; ?></button>
        </form>
        
        <div class="footer-links">
            <?php if ($trialIntent || $journeyTrialIntent): ?>
            Already have an account? <a href="login.php<?php echo rb_auth_companion_query(); ?>">Log in here</a>
            <?php else: ?>
            Already have an account? <a href="login.php<?php echo rb_auth_companion_query(); ?>">Log in</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>