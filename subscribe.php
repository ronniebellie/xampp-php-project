<?php
session_start();
require_once 'includes/db_config.php';
require_once 'includes/stripe_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, subscription_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If already premium, redirect to homepage
if ($user['subscription_status'] === 'premium') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe to Premium - Ron Belisle Financial Planning</title>
    <link rel="stylesheet" href="css/shared-styles.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .subscription-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .pricing-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: transform 0.2s, border-color 0.2s;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            border-color: #2c5282;
        }
        
        .pricing-card.popular {
            border-color: #2c5282;
            position: relative;
        }
        
        .popular-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            background: #2c5282;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .plan-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 10px;
        }
        
        .plan-price {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
        }
        
        .plan-price sup {
            font-size: 0.5em;
            vertical-align: top;
        }
        
        .plan-period {
            color: #666;
            font-size: 0.9em;
        }
        
        .plan-savings {
            color: #059669;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 30px 0;
            text-align: left;
        }
        
        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .plan-features li:before {
            content: "✓ ";
            color: #059669;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .subscribe-btn {
            width: 100%;
            padding: 15px;
            background: #2c5282;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .subscribe-btn:hover {
            background: #1e3a5f;
        }
        
        .money-back {
            margin-top: 20px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 6px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="subscription-container">
        <h1>Upgrade to Premium</h1>
        <p>Get unlimited access to all retirement calculators and planning tools</p>
        
        <div class="pricing-cards">
            <!-- Monthly Plan -->
            <div class="pricing-card">
                <div class="plan-name">Monthly</div>
                <div class="plan-price"><sup>$</sup>6<span class="plan-period">/month</span></div>
                
                <ul class="plan-features">
                    <li>All premium calculators</li>
                    <li>Unlimited calculations</li>
                    <li>Save your scenarios</li>
                    <li>Priority support</li>
                    <li>Cancel anytime</li>
                </ul>
                
                <button class="subscribe-btn" onclick="checkout('monthly')">
                    Subscribe Monthly
                </button>
            </div>
            
            <!-- Annual Plan (Popular) -->
            <div class="pricing-card popular">
                <div class="popular-badge">BEST VALUE</div>
                <div class="plan-name">Annual</div>
                <div class="plan-price"><sup>$</sup>60<span class="plan-period">/year</span></div>
                <div class="plan-savings">Save $12/year!</div>
                
                <ul class="plan-features">
                    <li>All premium calculators</li>
                    <li>Unlimited calculations</li>
                    <li>Save your scenarios</li>
                    <li>Priority support</li>
                    <li>Cancel anytime</li>
                </ul>
                
                <button class="subscribe-btn" onclick="checkout('annual')">
                    Subscribe Annually
                </button>
            </div>
        </div>
        
        <div class="money-back">
            <strong>💯 30-Day Money Back Guarantee</strong><br>
            Not satisfied? Get a full refund within 30 days, no questions asked.
        </div>
    </div>

    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
        
        function checkout(plan) {
            const priceId = plan === 'monthly' 
                ? '<?php echo STRIPE_PRICE_MONTHLY; ?>'
                : '<?php echo STRIPE_PRICE_ANNUAL; ?>';
            
            // Redirect to checkout page
            window.location.href = 'checkout.php?plan=' + plan + '&price_id=' + priceId;
        }
    </script>
</body>
</html>