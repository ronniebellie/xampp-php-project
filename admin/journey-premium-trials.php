<?php
/**
 * Recent Journey Premium Trials — private administrator view.
 * Marks currently listed unviewed trials as viewed when opened.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_admin_trials.php';

rb_require_admin($conn);

$limit = JOURNEY_ADMIN_TRIALS_DEFAULT_LIMIT;
$trials = journey_admin_list_recent_trials($conn, $limit);

// Mark currently displayed unseen records as viewed (after listing snapshot for labels).
$toMark = [];
foreach ($trials as $t) {
    if (!empty($t['is_new']) && !empty($t['stripe_subscription_id'])) {
        $toMark[] = (string) $t['stripe_subscription_id'];
    }
}
if ($toMark !== []) {
    journey_admin_mark_trials_viewed($conn, $toMark);
}

$summary = journey_admin_trial_summary($conn);
$remainingNew = journey_admin_count_unviewed_trials($conn);
$pageTitle = 'Recent Journey Premium Trials';
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
        .admin-nav a {
            color: #1d4ed8; text-decoration: none; font-weight: 600; font-size: 0.95rem;
        }
        .admin-nav a.is-active { color: #0f172a; text-decoration: underline; }
        .admin-summary {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px; margin-bottom: 22px;
        }
        .admin-stat {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;
        }
        .admin-stat .n { display: block; font-size: 1.4rem; font-weight: 700; color: #0f172a; }
        .admin-stat .l { display: block; font-size: 0.8rem; color: #64748b; margin-top: 2px; }
        .admin-table-wrap { overflow-x: auto; }
        table.admin-table {
            width: 100%; border-collapse: collapse; font-size: 0.92rem;
        }
        table.admin-table th, table.admin-table td {
            text-align: left; padding: 10px 12px; border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.admin-table th {
            font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;
            color: #64748b; font-weight: 700;
        }
        .pill {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 0.78rem; font-weight: 700;
        }
        .pill-new { background: #dbeafe; color: #1d4ed8; }
        .pill-viewed { background: #f1f5f9; color: #64748b; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8rem; }
        .empty { color: #64748b; padding: 18px 0; }
        .footnote { margin-top: 16px; color: #94a3b8; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php echo journey_admin_nav_html($conn, 'trials'); ?>
    <div class="admin-card">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="lede">
            Confirmed Journey Premium trial activations from Stripe webhook sync.
            Opening this page marks the listed new trials as viewed.
            <?php if ($remainingNew > 0): ?>
                <strong><?php echo (int) $remainingNew; ?> still unviewed</strong> outside this page’s recent window.
            <?php endif; ?>
        </p>

        <div class="admin-summary">
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['last_7_days']; ?></span>
                <span class="l">Started last 7 days</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['last_30_days']; ?></span>
                <span class="l">Started last 30 days</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['currently_trialing']; ?></span>
                <span class="l">Currently trialing</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['converted_to_active']; ?></span>
                <span class="l">Converted to active</span>
            </div>
        </div>

        <div class="admin-table-wrap">
            <?php if ($trials === []): ?>
                <p class="empty">No Journey Premium trials recorded yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Trial started</th>
                            <th>Trial ends</th>
                            <th>Status</th>
                            <th>Stripe subscription</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($trials as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['full_name'] !== '' ? $t['full_name'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['email'] !== '' ? $t['email'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $t['trial_start_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $t['trial_end_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $t['status_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="mono"><?php echo htmlspecialchars((string) $t['stripe_subscription_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if (!empty($t['is_new'])): ?>
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
        <p class="footnote">Showing up to <?php echo (int) $limit; ?> most recent Journey Premium trials (newest first).</p>
    </div>
</div>
</body>
</html>
