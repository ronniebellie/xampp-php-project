<?php
/**
 * calcforadvisors subscriber dashboard.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/auth_helpers.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';

calcforadvisors_require_login();
$sub = calcforadvisors_get_subscriber();

$msg = $_GET['msg'] ?? '';
$billingError = $_SESSION['billing_portal_error'] ?? null;
if ($billingError) {
    unset($_SESSION['billing_portal_error']);
}

$trialSlug = '';
$trialUrl = '';
$trialExpired = false;
if ($sub['plan'] === 'free') {
    $stmt = $conn->prepare('SELECT trial_slug, created_at FROM calcforadvisors_subscribers WHERE id = ?');
    $stmt->bind_param('i', $sub['id']);
    $stmt->execute();
    $stmt->bind_result($trialSlug, $createdAt);
    $stmt->fetch();
    $stmt->close();
    $baseUrl = defined('CALCFORADVISORS_BASE_URL') ? CALCFORADVISORS_BASE_URL : 'https://calcforadvisors.com';
    $trialUrl = $trialSlug ? $baseUrl . '/trial.php?s=' . $trialSlug : '';
    $created = $createdAt ? strtotime($createdAt) : time();
    $trialExpired = time() > ($created + (30 * 86400));
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - calcforadvisors.com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 640px; margin: 0 auto; }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        h1 { font-size: 24px; color: #1e293b; }
        .nav a {
            color: #2c5282;
            text-decoration: none;
            font-weight: 600;
            margin-left: 16px;
        }
        .nav a:hover { text-decoration: underline; }
        .card {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .card h2 { font-size: 18px; color: #334155; margin-bottom: 16px; }
        .card p { color: #64748b; font-size: 15px; line-height: 1.6; }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 12px;
        }
        .btn:hover { opacity: 0.95; }
        .status { color: #059669; font-weight: 600; }
        .status.canceled { color: #dc2626; }
        .message { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>My Account</h1>
            <nav class="nav">
                <a href="index.html">calcforadvisors.com</a>
                <a href="logout.php">Log out</a>
            </nav>
        </header>

        <?php if ($msg === 'welcome'): ?>
            <div class="message">Welcome! Your 30-day trial has started.</div>
        <?php endif; ?>
        <?php if ($msg === 'no_billing'): ?>
            <div class="message">Billing management is for paid subscribers. <a href="index.html#pricing">Upgrade</a> to manage your subscription.</div>
        <?php endif; ?>
        <?php if ($msg === 'trial_updated'): ?>
            <div class="message">Your trial page has been updated.</div>
        <?php endif; ?>
        <?php if ($billingError): ?>
            <div class="error">Billing error: <?php echo htmlspecialchars($billingError); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Subscription</h2>
            <p>
                <strong>Email:</strong> <?php echo htmlspecialchars($sub['email']); ?><br>
                <strong>Plan:</strong> <?php echo htmlspecialchars($sub['plan']); ?><br>
                <strong>Status:</strong> <span class="status <?php echo $sub['status'] === 'canceled' ? 'canceled' : ''; ?>"><?php echo htmlspecialchars($sub['status']); ?></span>
            </p>
            <?php if ($sub['status'] === 'active' && $sub['plan'] !== 'free'): ?>
                <a href="billing-portal.php" class="btn">Manage subscription & billing</a>
            <?php elseif ($sub['plan'] === 'free'): ?>
                <a href="index.html#pricing" class="btn">Upgrade to paid</a>
            <?php endif; ?>
        </div>

        <?php if ($sub['plan'] === 'free'): ?>
        <div class="card">
            <h2>30-Day White-Label Trial</h2>
            <p>Add your firm name and logo to get a shareable branded page with links to 14 retirement calculators. Valid for 30 days from sign-up.</p>
            <?php if ($trialExpired): ?>
                <p style="color: #dc2626; font-weight: 600;">Your trial has ended. Upgrade for ongoing white-label access.</p>
                <a href="index.html#pricing" class="btn">Upgrade to paid</a>
            <?php else: ?>
                <a href="trial-setup.php" class="btn"><?php echo $trialSlug ? 'Manage trial page' : 'Set up trial page'; ?></a>
                <?php if ($trialUrl): ?>
                    <p style="margin-top: 16px; font-size: 14px;">Your trial page: <a href="<?php echo htmlspecialchars($trialUrl); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($trialUrl); ?></a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Resources</h2>
            <p>Access calculators at ronbelisle.com. Trial accounts get core tools only; paid plans include save, export, AI explain, and extended projections.</p>
            <a href="get-calc-bridge-token.php" class="btn">Access calculators</a>
            <a href="index.html#samples" class="btn" style="margin-left: 8px; background: transparent; color: #2c5282; border: 2px solid #2c5282;">View demos</a>
        </div>
    </div>
</body>
</html>
