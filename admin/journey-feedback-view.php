<?php
/**
 * Journey Feedback detail — marks submission viewed on open.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_admin_trials.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_feedback.php';

rb_require_admin($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = journey_feedback_get($conn, $id);
if ($item === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Not found</title></head><body>'
        . '<p>Feedback not found.</p><p><a href="/admin/journey-feedback.php">Back to Journey Feedback</a></p>'
        . '</body></html>';
    exit;
}

journey_feedback_mark_viewed($conn, $id);
$item = journey_feedback_get($conn, $id) ?? $item;

function admin_fmt_dt_view(?string $v): string
{
    if ($v === null || trim($v) === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($v, new DateTimeZone('UTC'));
        return $dt->setTimezone(new DateTimeZone('America/Chicago'))->format('F j, Y \a\t g:i A T');
    } catch (Throwable $e) {
        return '—';
    }
}

$pageTitle = 'Feedback #' . (int) $item['id'];
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
        .admin-wrap { max-width: 860px; margin: 32px auto; padding: 0 16px 48px; }
        .admin-card {
            background: #fff; border-radius: 16px; padding: 28px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .admin-card h1 { margin: 0 0 8px; font-size: 1.6rem; }
        .admin-nav { display: flex; flex-wrap: wrap; gap: 12px 18px; margin-bottom: 22px; }
        .admin-nav a { color: #1d4ed8; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .admin-nav a.is-active { color: #0f172a; text-decoration: underline; }
        .meta { color: #64748b; margin: 0 0 22px; font-size: 0.92rem; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .pill-viewed { background: #f1f5f9; color: #64748b; }
        .block { margin: 0 0 18px; }
        .block h2 { margin: 0 0 6px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b; }
        .block p, .block pre {
            margin: 0; white-space: pre-wrap; word-break: break-word;
            color: #0f172a; line-height: 1.5; font: inherit;
        }
        .tech { margin-top: 28px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
        .tech dl { margin: 0; display: grid; grid-template-columns: 160px 1fr; gap: 8px 12px; font-size: 0.9rem; }
        .tech dt { color: #64748b; }
        .tech dd { margin: 0; word-break: break-word; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php echo journey_admin_nav_html($conn, 'feedback'); ?>
    <div class="admin-card">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="meta">
            <?php echo htmlspecialchars(admin_fmt_dt_view($item['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
            · <span class="pill pill-viewed">Viewed</span>
            · <a href="/admin/journey-feedback.php">Back to list</a>
        </p>

        <div class="block">
            <h2>What were you trying to do?</h2>
            <p><?php echo htmlspecialchars((string) $item['trying_to_do'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="block">
            <h2>What happened instead?</h2>
            <p><?php echo htmlspecialchars((string) $item['what_happened'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="tech">
            <dl>
                <dt>User</dt>
                <dd>
                    <?php
                    $name = $item['full_name'] !== '' ? $item['full_name'] : '—';
                    if (!empty($item['user_id'])) {
                        $name .= ' (#' . (int) $item['user_id'] . ')';
                    }
                    echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    ?>
                </dd>
                <dt>Email</dt>
                <dd><?php echo htmlspecialchars($item['email'] !== '' ? $item['email'] : '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Page</dt>
                <dd><?php echo htmlspecialchars($item['page_url'] !== '' ? $item['page_url'] : '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Journey phase</dt>
                <dd><?php echo htmlspecialchars($item['journey_phase'] !== '' ? $item['journey_phase'] : '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Signed in</dt>
                <dd><?php echo !empty($item['is_signed_in']) ? 'Yes' : 'No'; ?></dd>
                <dt>Premium</dt>
                <dd><?php echo !empty($item['is_premium']) ? 'Yes' : 'No'; ?></dd>
                <dt>User agent</dt>
                <dd><?php echo htmlspecialchars($item['user_agent'] !== '' ? $item['user_agent'] : '—', ENT_QUOTES, 'UTF-8'); ?></dd>
            </dl>
        </div>
    </div>
</div>
</body>
</html>
