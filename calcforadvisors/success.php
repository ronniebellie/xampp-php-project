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

$trialActive = false;

try {
    $session = \Stripe\Checkout\Session::retrieve(
        $session_id,
        ['expand' => ['subscription']]
    );
    if ($session->status !== 'complete') {
        throw new Exception('Checkout was not completed.');
    }
    if ($session->mode === 'subscription') {
        // Free trial: Stripe often leaves payment_status unpaid until the first charge
        if (!in_array($session->payment_status, ['paid', 'unpaid'], true)) {
            throw new Exception('Subscription could not be confirmed.');
        }
        $sub = $session->subscription;
        if (is_object($sub) && isset($sub->status) && $sub->status === 'trialing') {
            $trialActive = true;
        }
    } elseif ($session->payment_status !== 'paid') {
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
            <p>Your calcforadvisors subscription is now active.</p>
            <?php if (!empty($trialActive)): ?>
            <p style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;color:#166534;">
                You’re in a <strong>30-day free trial</strong>. Your payment method is on file; you won’t be charged until the trial ends. You can cancel anytime from the billing portal.
            </p>
            <?php endif; ?>
            <p>Set up your account to access your subscriber dashboard, manage billing, and get your white-label calculators.</p>
            <a href="request-set-password.php" class="cta">Set up your account</a>
            <p style="margin-top: 16px; font-size: 14px;"><a href="index.html" style="color: #2c5282;">Return to calcforadvisors.com</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
