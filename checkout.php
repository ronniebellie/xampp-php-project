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

// Get plan details from URL
$plan = $_GET['plan'] ?? 'monthly';
$price_id = $_GET['price_id'] ?? STRIPE_PRICE_MONTHLY;

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Create Stripe Checkout Session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'http://localhost/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/subscribe.php?canceled=true',
        'customer_email' => $user['email'],
        'client_reference_id' => $user_id,
        'metadata' => [
            'user_id' => $user_id,
            'plan' => $plan
        ]
    ]);
    
    // Redirect to Stripe Checkout
    header('Location: ' . $checkout_session->url);
    exit;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>