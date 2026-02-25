<?php
session_start();
require_once 'includes/db_config.php';
require_once 'includes/stripe_config.php';
require_once 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get session ID from URL
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    header('Location: index.php');
    exit;
}

// Base URL for links in email (same domain as current request)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ronbelisle.com';
$base_url = $scheme . '://' . $host;

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$error_message = null;

try {
    // Retrieve the checkout session
    $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
    
    $user_id = $checkout_session->client_reference_id;
    $subscription_id = $checkout_session->subscription;
    
    // Get current user info (to detect first-time upgrade and to email them)
    $stmt = $conn->prepare("SELECT email, full_name, subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $was_already_premium = ($user['subscription_status'] === 'premium');
    
    // Update user subscription status in database
    $stmt = $conn->prepare("UPDATE users SET subscription_status = 'premium', stripe_subscription_id = ? WHERE id = ?");
    $stmt->bind_param("si", $subscription_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Send welcome email only on first successful subscription (not on page refresh)
    if (!$was_already_premium) {
        $to = $user['email'];
        $first_name = !empty($user['full_name']) ? trim(explode(' ', $user['full_name'])[0]) : 'there';
        $subject = "Thanks for subscribing – Ron Belisle Financial Calculators";
        
        $message = "Hi " . $first_name . ",\n\n";
        $message .= "Thank you for subscribing to Premium at Ron Belisle Financial Calculators. Your subscription is now active.\n\n";
        $message .= "You now have full access to:\n";
        $message .= "• Save and compare scenarios across all calculators\n";
        $message .= "• Export PDF and CSV reports\n";
        $message .= "• Advanced projections (e.g., 10–20 year outlooks)\n";
        $message .= "• All premium retirement and planning tools on the site\n\n";
        $message .= "Get started:\n";
        $message .= "• Visit the site: " . $base_url . "\n";
        $message .= "• Manage your account: " . $base_url . "/account.php\n";
        $message .= "• Review your premium benefits and how to cancel: " . $base_url . "/premium.html\n\n";
        $message .= "If you have any questions, just reply to this email.\n\n";
        $message .= "Ron Belisle\n";
        $message .= "Ron Belisle Financial Calculators\n";
        $message .= $base_url . "\n";
        
        $headers = [
            'From: Ron Belisle Financial Calculators <noreply@ronbelisle.com>',
            'Reply-To: ronbelisle@ronbelisle.com',
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8',
        ];
        @mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Premium! - Ron Belisle Financial Planning</title>
    <link rel="stylesheet" href="css/shared-styles.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 4em;
            color: #059669;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c5282;
            margin-bottom: 20px;
        }
        
        .success-message {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .premium-features {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f0f9ff;
            border-radius: 6px;
        }
        
        .premium-features h3 {
            color: #2c5282;
            margin-bottom: 15px;
        }
        
        .premium-features ul {
            list-style: none;
            padding: 0;
        }
        
        .premium-features li {
            padding: 8px 0;
        }
        
        .premium-features li:before {
            content: "✓ ";
            color: #059669;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: #2c5282;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: bold;
            margin-top: 20px;
            transition: background 0.2s;
        }
        
        .cta-button:hover {
            background: #1e3a5f;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <?php if (isset($error_message)): ?>
            <div class="success-icon">⚠️</div>
            <h1>Something Went Wrong</h1>
            <p class="success-message"><?php echo htmlspecialchars($error_message); ?></p>
            <a href="subscribe.php" class="cta-button">Try Again</a>
        <?php else: ?>
            <div class="success-icon">🎉</div>
            <h1>Welcome to Premium!</h1>
            <p class="success-message">
                Your subscription is now active. You have full access to all premium retirement calculators and planning tools.
            </p>
            
            <div class="premium-features">
                <h3>You now have access to:</h3>
                <ul>
                    <li>Roth Conversion Calculator</li>
                    <li>Social Security Analyzer</li>
                    <li>Retirement Income Planner</li>
                    <li>Tax-Efficient Withdrawal Strategy</li>
                    <li>All future premium tools</li>
                </ul>
            </div>
            
            <a href="index.php" class="cta-button">Start Planning</a>
        <?php endif; ?>
    </div>
</body>
</html>