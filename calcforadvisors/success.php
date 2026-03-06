<?php
/**
 * Post-checkout success page for calcforadvisors.
 * Verifies the Stripe session and displays a thank-you message.
 */
$root = dirname(__DIR__);
$includes = file_exists($root . '/includes/stripe_config.php') ? $root . '/includes' : $root . '/html/includes';
$vendor = file_exists($root . '/vendor/autoload.php') ? $root . '/vendor' : $root . '/html/vendor';
require_once $includes . '/stripe_config.php';
require_once $vendor . '/autoload.php';

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    header('Location: index.html');
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$error = null;
$plan = null;

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    if ($session->payment_status !== 'paid') {
        throw new Exception('Payment was not completed.');
    }
    $plan = $session->metadata->plan ?? 'subscription';
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - calcforadvisors.com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 520px;
            background: white;
            padding: 48px 40px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            text-align: center;
        }
        .icon { font-size: 4em; margin-bottom: 20px; }
        h1 { color: #2c5282; margin-bottom: 16px; font-size: 1.75rem; }
        p { color: #4a5568; margin-bottom: 24px; }
        .cta {
            display: inline-block;
            background: #2c5282;
            color: white;
            padding: 14px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .cta:hover { background: #1e3a5f; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="icon">⚠️</div>
            <h1>Something went wrong</h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="index.html#pricing" class="cta">Back to pricing</a>
        <?php else: ?>
            <div class="icon">✓</div>
            <h1>Thank you for subscribing</h1>
            <p>Your calcforadvisors subscription is now active. We'll be in touch shortly to set up your branded calculator suite.</p>
            <a href="index.html" class="cta">Return to calcforadvisors.com</a>
        <?php endif; ?>
    </div>
</body>
</html>
