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
            <?php if ($sub['status'] === 'active'): ?>
                <a href="billing-portal.php" class="btn">Manage subscription & billing</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Resources</h2>
            <p>Access your white-label calculators and demos from the main site.</p>
            <a href="index.html#demos" class="btn">View demos</a>
        </div>
    </div>
</body>
</html>
