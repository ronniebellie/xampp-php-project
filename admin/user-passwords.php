<?php
/**
 * Private administrator tool for resetting consumer account passwords.
 *
 * Passwords are entered only by the administrator, stored as one-way hashes,
 * and never displayed or written to logs.
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
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($userId === false || $userId === null) {
            $error = 'Choose an account.';
        } elseif (strlen($password) < 12) {
            $error = 'Use a password with at least 12 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $userId);
            $stmt->execute();
            $updated = $stmt->affected_rows;
            $stmt->close();

            if ($updated === 1) {
                error_log('admin: reset consumer password for user_id=' . (int) $userId);
                $message = 'Password reset. Give the account holder the new password through your existing support channel.';
            } else {
                $error = 'That account was not found.';
            }
        }
    }
}

$users = [];
$result = $conn->query(
    'SELECT id, email, full_name, subscription_status, last_login, created_at
     FROM users ORDER BY created_at DESC LIMIT 250'
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Consumer Passwords — Ron Belisle</title>
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
        select, input { width: 100%; max-width: 520px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
        button { margin-top: 18px; padding: 11px 16px; border: 0; border-radius: 8px; background: #1d4ed8; color: #fff; font-weight: 700; cursor: pointer; }
        .warning { color: #64748b; font-size: .9rem; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-nav"><a href="/admin/">← Administrator home</a></div>
    <div class="admin-card">
        <h1>Consumer account passwords</h1>
        <p class="lede">Reset a customer password when they cannot use email recovery. Passwords are stored as one-way hashes and are never shown or logged.</p>

        <?php if ($message): ?><div class="notice success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($users === []): ?>
            <p class="warning">No consumer accounts were found.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Last login</th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($user['full_name'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $user['subscription_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($user['last_login'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="">
                <?php echo rb_csrf_field(); ?>
                <label for="user_id">Account</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Choose an account</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int) $user['id']; ?>">
                            <?php echo htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="password">Temporary password</label>
                <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <label for="password_confirm">Confirm temporary password</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required>
                <button type="submit">Reset account password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
