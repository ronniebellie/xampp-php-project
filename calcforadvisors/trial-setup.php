<?php
/**
 * Trial setup for free subscribers: firm name, logo URL.
 * Generates shareable trial page link (30-day white-label trial).
 */
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/auth_helpers.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';

// One-time token login (Safari Private mode: cookies may be blocked on redirect)
$token = trim($_GET['token'] ?? '');
if (!empty($token)) {
    $stmt = $conn->prepare('SELECT id, email, plan, status FROM calcforadvisors_subscribers WHERE trial_login_token = ? AND trial_login_token_expires > NOW() AND status = ?');
    $status = 'active';
    $stmt->bind_param('ss', $token, $status);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $stmt->close();
        $clear = $conn->prepare('UPDATE calcforadvisors_subscribers SET trial_login_token = NULL, trial_login_token_expires = NULL WHERE id = ?');
        $clear->bind_param('i', $row['id']);
        $clear->execute();
        $clear->close();
        $_SESSION['calcforadvisors_subscriber_id'] = (int) $row['id'];
        $_SESSION['calcforadvisors_subscriber_email'] = $row['email'];
        $_SESSION['calcforadvisors_subscriber_plan'] = $row['plan'];
        $_SESSION['calcforadvisors_subscriber_status'] = $row['status'];
        // Don't redirect - render page in same request (Safari Private may block cookie on redirect)
    } else {
        $stmt->close();
    }
}
if (empty($_SESSION['calcforadvisors_subscriber_id'])) {
    calcforadvisors_require_login();
}

$sub = calcforadvisors_get_subscriber();
if ($sub['plan'] !== 'free') {
    header('Location: account.php');
    exit;
}

// Fetch trial data from DB (created_at for window; firm_name, logo_url, banner_url, trial_slug for form/trial URL)
$firmName = '';
$logoUrl = '';
$bannerUrl = '';
$trialSlug = '';
$createdAt = null;
$stmt = $conn->prepare('SELECT firm_name, logo_url, banner_url, trial_slug, created_at FROM calcforadvisors_subscribers WHERE id = ?');
$stmt->bind_param('i', $sub['id']);
$stmt->execute();
$stmt->bind_result($firmName, $logoUrl, $bannerUrl, $trialSlug, $createdAt);
$stmt->fetch();
$stmt->close();

$created = $createdAt ? strtotime($createdAt) : time();
$trialEnds = $created + (30 * 86400);
$trialExpired = time() > $trialEnds;

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$trialExpired) {
    $firmName = trim($_POST['firm_name'] ?? '');
    $logoUrl = trim($_POST['logo_url'] ?? '');
    $bannerUrl = trim($_POST['banner_url'] ?? '');
    $customSlug = strtolower(trim(preg_replace('/[^a-z0-9-]/', '', $_POST['custom_slug'] ?? '')));

    if (empty($firmName)) {
        $error = 'Firm name is required.';
    } elseif (strlen($firmName) > 255) {
        $error = 'Firm name is too long.';
    } elseif (!empty($logoUrl) && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid logo URL.';
    } elseif (!empty($bannerUrl) && !filter_var($bannerUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid banner URL.';
    } elseif (!empty($customSlug) && (strlen($customSlug) < 3 || strlen($customSlug) > 32)) {
        $error = 'Custom URL slug must be 3–32 characters.';
    } elseif (!empty($customSlug) && !preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]{1,2}$/', $customSlug)) {
        $error = 'Use only lowercase letters, numbers, and hyphens.';
    } else {
        $stmt = $conn->prepare('SELECT trial_slug FROM calcforadvisors_subscribers WHERE id = ?');
        $stmt->bind_param('i', $sub['id']);
        $stmt->execute();
        $stmt->bind_result($existingSlug);
        $stmt->fetch();
        $stmt->close();

        $slug = $existingSlug;
        if (!empty($customSlug)) {
            // Check if custom slug is taken by another subscriber
            $stmt = $conn->prepare('SELECT id FROM calcforadvisors_subscribers WHERE trial_slug = ? AND id != ?');
            $stmt->bind_param('si', $customSlug, $sub['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                $error = 'That URL slug is already in use. Try another.';
            } else {
                $stmt->close();
                $slug = $customSlug;
            }
        }
        if (empty($error)) {
            if (empty($slug)) {
                $slug = bin2hex(random_bytes(8));
            }
            $stmt = $conn->prepare('UPDATE calcforadvisors_subscribers SET firm_name = ?, logo_url = ?, banner_url = ?, trial_slug = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $firmName, $logoUrl, $bannerUrl, $slug, $sub['id']);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header('Location: account.php?msg=trial_updated');
                exit;
            }
            $stmt->close();
            $error = 'Could not save. Please try again.';
        }
    }
}

$baseUrl = defined('CALCFORADVISORS_BASE_URL') ? CALCFORADVISORS_BASE_URL : 'https://calcforadvisors.com';
$trialUrl = $trialSlug ? $baseUrl . '/trial.php?s=' . $trialSlug : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Trial - calcforadvisors.com</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; min-height: 100vh; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; }
        .card { background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h2 { font-size: 18px; color: #334155; margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="url"] { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; margin-bottom: 16px; }
        input:focus { outline: none; border-color: #2c5282; }
        .btn { display: inline-block; background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 15px; }
        .btn:hover { opacity: 0.95; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .trial-url { background: #f1f5f9; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 14px; word-break: break-all; }
        .trial-url a { color: #2c5282; font-weight: 600; }
        .back { display: inline-block; margin-bottom: 16px; color: #2c5282; text-decoration: none; font-size: 14px; }
        .back:hover { text-decoration: underline; }
        .expired { color: #dc2626; font-weight: 600; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="account.php" class="back">← Back to account</a>
        <div class="card">
            <h2>30-Day White-Label Trial</h2>
            <?php if ($msg === 'welcome'): ?>
                <p style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">Welcome! Add your firm details below to get your shareable trial page.</p>
            <?php endif; ?>
            <?php if ($trialExpired): ?>
                <p class="expired">Your trial has ended. Upgrade to a paid plan for ongoing white-label access.</p>
                <a href="index.html#pricing" class="btn">Upgrade to paid</a>
            <?php else: ?>
                <p style="color: #64748b; margin-bottom: 20px;">Add your firm name and logo. You'll get a shareable link to your branded calculator page for 30 days.</p>
                <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST">
                    <label for="firm_name">Firm name *</label>
                    <input type="text" id="firm_name" name="firm_name" required maxlength="255" value="<?php echo htmlspecialchars($firmName ?? ''); ?>" placeholder="e.g. Smith Retirement Planning">
                    <label for="logo_url">Logo URL (optional)</label>
                    <input type="url" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($logoUrl ?? ''); ?>" placeholder="https://yoursite.com/logo.png">
                    <label for="banner_url">Header/banner image URL (optional)</label>
                    <input type="url" id="banner_url" name="banner_url" value="<?php echo htmlspecialchars($bannerUrl ?? ''); ?>" placeholder="https://yoursite.com/banner.jpg">
                    <label for="custom_slug">Custom URL slug (optional)</label>
                    <input type="text" id="custom_slug" name="custom_slug" value="<?php echo htmlspecialchars($trialSlug ?? ''); ?>" placeholder="e.g. smith-retirement" pattern="[a-zA-Z0-9-]+" maxlength="32" style="text-transform: lowercase;">
                    <p style="font-size: 13px; color: #64748b; margin-top: -8px; margin-bottom: 16px;">Letters, numbers, hyphens only. Your link: calcforadvisors.com/trial.php?s=<em>your-slug</em></p>
                    <button type="submit" class="btn">Save & get trial link</button>
                </form>
                <?php if ($trialUrl): ?>
                    <p style="margin-top: 20px; font-weight: 600;">Your trial page:</p>
                    <div class="trial-url"><a href="<?php echo htmlspecialchars($trialUrl); ?>" target="_blank"><?php echo htmlspecialchars($trialUrl); ?></a></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
