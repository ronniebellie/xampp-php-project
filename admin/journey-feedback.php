<?php
/**
 * Journey Feedback — private administrator list.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_admin_trials.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_feedback.php';

rb_require_admin($conn);

$items = journey_feedback_list_recent($conn, JOURNEY_FEEDBACK_LIST_LIMIT);
$pageTitle = 'Journey Feedback';

function admin_fmt_dt(?string $v): string
{
    if ($v === null || trim($v) === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($v, new DateTimeZone('UTC'));
        return $dt->setTimezone(new DateTimeZone('America/Chicago'))->format('M j, Y g:i A T');
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
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — Ron Belisle</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        body { background: #f1f5f9; color: #1e293b; }
        .admin-wrap { max-width: 1100px; margin: 32px auto; padding: 0 16px 48px; }
        .admin-card {
            background: #fff; border-radius: 16px; padding: 28px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .admin-card h1 { margin: 0 0 8px; font-size: 1.6rem; }
        .admin-card .lede { color: #64748b; margin: 0 0 22px; line-height: 1.5; }
        .admin-nav { display: flex; flex-wrap: wrap; gap: 12px 18px; margin-bottom: 22px; }
        .admin-nav a { color: #1d4ed8; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .admin-nav a.is-active { color: #0f172a; text-decoration: underline; }
        .admin-table-wrap { overflow-x: auto; }
        table.admin-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        table.admin-table th, table.admin-table td {
            text-align: left; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top;
        }
        table.admin-table th {
            font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;
            color: #64748b; font-weight: 700;
        }
        table.admin-table a { color: #1d4ed8; text-decoration: none; font-weight: 600; }
        table.admin-table a:hover { text-decoration: underline; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .pill-new { background: #dbeafe; color: #1d4ed8; }
        .pill-viewed { background: #f1f5f9; color: #64748b; }
        .empty { color: #64748b; padding: 18px 0; }
        .footnote { margin-top: 16px; color: #94a3b8; font-size: 0.85rem; }
        .muted { color: #64748b; word-break: break-all; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php echo journey_admin_nav_html($conn, 'feedback'); ?>
    <div class="admin-card">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="lede">Product feedback from Journey users. Opening an item marks it viewed.</p>

        <div class="admin-table-wrap">
            <?php if ($items === []): ?>
                <p class="empty">No feedback submissions yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Page</th>
                            <th>Summary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <a href="/admin/journey-feedback-view.php?id=<?php echo (int) $item['id']; ?>">
                                    <?php echo htmlspecialchars(admin_fmt_dt($item['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $userLabel = $item['full_name'] !== '' ? $item['full_name'] : '—';
                                if (!empty($item['user_id'])) {
                                    $userLabel .= ' (#' . (int) $item['user_id'] . ')';
                                }
                                echo htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['email'] !== '' ? $item['email'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($item['page_url'] !== '' ? $item['page_url'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $item['summary'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if (!empty($item['is_new'])): ?>
                                    <span class="pill pill-new">New</span>
                                <?php else: ?>
                                    <span class="pill pill-viewed">Viewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <p class="footnote">Showing up to <?php echo (int) JOURNEY_FEEDBACK_LIST_LIMIT; ?> most recent submissions.</p>
    </div>
</div>
</body>
</html>
