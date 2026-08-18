<?php
/**
 * Private administrator tool for provisioning CalcForAdvisors passwords.
 *
 * This replaces the former email-based setup link for paid subscribers.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/csrf.php';

rb_require_admin($conn);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rb_csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } else {
        $subscriberId = filter_var($_POST['subscriber_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($subscriberId === false || $subscriberId === null) {
            $error = 'Choose a subscriber.';
        } elseif (strlen($password) < 12) {
            $error = 'Use a password with at least 12 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                'UPDATE calcforadvisors_subscribers SET password_hash = ? WHERE id = ? AND status = ?'
            );
            $status = 'active';
            $stmt->bind_param('sis', $hash, $subscriberId, $status);
            $stmt->execute();
            $updated = $stmt->affected_rows;
            $stmt->close();

            if ($updated === 1) {
                error_log('admin: provisioned CalcForAdvisors password for subscriber_id=' . (int) $subscriberId);
                $message = 'Password set. Give the subscriber the password through your existing support channel.';
            } else {
                $error = 'That active subscriber was not found or already has the requested state.';
            }
        }
    }
}

$subscribers = [];
$result = $conn->query(
    "SELECT id, email, plan, status, created_at FROM calcforadvisors_subscribers
     WHERE status = 'active' AND (password_hash IS NULL OR password_hash = '')
     ORDER BY created_at DESC LIMIT 100"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }
    $result->free();
}

function cfa_admin_fmt_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Chicago'))
            ->format('M j, Y g:i A T');
    } catch (Throwable $e) {
        return '—';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CalcForAdvisors Passwords — Ron Belisle</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        body { background: #f1f5f9; color: #1e293b; }
        .admin-wrap { max-width: 960px; margin: 32px auto; padding: 0 16px 48px; }
        .admin-card { background: #fff; border-radius: 16px; padding: 28px 30px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        .lede { color: #64748b; line-height: 1.5; }
        .admin-nav { margin-bottom: 22px; }
        .admin-nav a { color: #1d4ed8; font-weight: 600; text-decoration: none; }
        .notice { padding: 12px 14px; border-radius: 8px; margin: 18px 0; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0 28px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e2e8f0; }
        th { color: #64748b; font-size: .8rem; text-transform: uppercase; }
        label { display: block; margin: 14px 0 6px; font-weight: 600; }
        select, input { width: 100%; max-width: 480px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
        button { margin-top: 18px; padding: 11px 16px; border: 0; border-radius: 8px; background: #1d4ed8; color: #fff; font-weight: 700; cursor: pointer; }
        .warning { color: #64748b; font-size: .9rem; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-nav"><a href="/admin/">← Administrator home</a></div>
    <div class="admin-card">
        <h1>CalcForAdvisors passwords</h1>
        <p class="lede">Provision an active subscriber who cannot receive an email setup link. Passwords are stored as one-way hashes and are never logged.</p>

        <?php if ($message): ?><div class="notice success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($subscribers === []): ?>
            <p class="warning">No active subscribers currently need password provisioning.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Email</th><th>Plan</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $subscriber['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $subscriber['plan'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(cfa_admin_fmt_date($subscriber['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="">
                <?php echo rb_csrf_field(); ?>
                <label for="subscriber_id">Subscriber</label>
                <select id="subscriber_id" name="subscriber_id" required>
                    <option value="">Choose a subscriber</option>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <option value="<?php echo (int) $subscriber['id']; ?>">
                            <?php echo htmlspecialchars((string) $subscriber['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="password">Temporary password</label>
                <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <label for="password_confirm">Confirm temporary password</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required>
                <button type="submit">Set subscriber password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
