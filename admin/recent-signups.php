<?php
/**
 * Recent Signups — private administrator view.
 * Marks currently listed unviewed signups as viewed when opened.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_admin_trials.php';

rb_require_admin($conn);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $source = isset($_POST['source']) && is_string($_POST['source']) ? $_POST['source'] : '';
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!rb_csrf_validate(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    if ($recordId === false || !journey_admin_delete_signup($conn, $source, (int) $recordId)) {
        $_SESSION['recent_signups_error'] = 'Signup could not be deleted.';
    } else {
        $_SESSION['recent_signups_success'] = 'Signup deleted.';
    }
    header('Location: /admin/recent-signups.php', true, 303);
    exit;
}

$successMessage = isset($_SESSION['recent_signups_success'])
    ? (string) $_SESSION['recent_signups_success']
    : '';
$errorMessage = isset($_SESSION['recent_signups_error'])
    ? (string) $_SESSION['recent_signups_error']
    : '';
unset($_SESSION['recent_signups_success'], $_SESSION['recent_signups_error']);

$limit = JOURNEY_ADMIN_TRIALS_DEFAULT_LIMIT;
$signups = journey_admin_list_recent_signups($conn, $limit);

// Mark currently displayed unseen records as viewed (after listing snapshot for labels).
$toMark = [];
foreach ($signups as $signup) {
    if (empty($signup['is_new'])) {
        continue;
    }
    if (($signup['source_key'] ?? '') === 'journey' && !empty($signup['stripe_subscription_id'])) {
        $toMark[] = (string) $signup['stripe_subscription_id'];
    }
}
if ($toMark !== []) {
    journey_admin_mark_trials_viewed($conn, $toMark);
}
$calculatorToMark = [];
foreach ($signups as $signup) {
    if (!empty($signup['is_new']) && in_array(
        $signup['source_key'] ?? '',
        [JOURNEY_ADMIN_SIGNUP_SOURCE_RON, JOURNEY_ADMIN_SIGNUP_SOURCE_CFA],
        true
    )) {
        $calculatorToMark[] = [
            'source' => (string) $signup['source_key'],
            'record_id' => (int) $signup['record_id'],
        ];
    }
}
journey_admin_mark_calculator_signups_viewed($conn, $calculatorToMark);

$summary = journey_admin_signup_summary($conn);
$remainingNew = journey_admin_count_unviewed_signups($conn);
$pageTitle = 'Recent Signups';
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
        .sort-button {
            appearance: none; border: 0; background: transparent; color: inherit;
            cursor: pointer; font: inherit; font-weight: inherit; letter-spacing: inherit;
            padding: 0 18px 0 0; position: relative; text-align: left; text-transform: inherit;
        }
        .sort-button::after { content: '↕'; opacity: 0.45; position: absolute; right: 0; }
        th[aria-sort="ascending"] .sort-button::after { content: '↑'; opacity: 1; }
        th[aria-sort="descending"] .sort-button::after { content: '↓'; opacity: 1; }
        .sort-button:focus-visible { outline: 2px solid #2563eb; outline-offset: 3px; border-radius: 2px; }
        .pill {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 0.78rem; font-weight: 700;
        }
        .pill-new { background: #dbeafe; color: #1d4ed8; }
        .pill-viewed { background: #f1f5f9; color: #64748b; }
        .detail { display: block; margin-top: 3px; color: #64748b; font-size: 0.78rem; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .empty { color: #64748b; padding: 18px 0; }
        .footnote { margin-top: 16px; color: #94a3b8; font-size: 0.85rem; }
        .notice { border-radius: 8px; margin: 0 0 18px; padding: 10px 12px; font-size: 0.9rem; }
        .notice-success { background: #dcfce7; color: #166534; }
        .notice-error { background: #fee2e2; color: #991b1b; }
        .delete-form { margin: 0; }
        .delete-button {
            appearance: none; background: #fff; border: 1px solid #dc2626; border-radius: 6px;
            color: #b91c1c; cursor: pointer; font: inherit; font-size: 0.78rem;
            font-weight: 700; padding: 4px 8px;
        }
        .delete-button:hover { background: #fef2f2; }
        .delete-button:focus-visible { outline: 2px solid #dc2626; outline-offset: 2px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <?php echo journey_admin_nav_html($conn, 'trials'); ?>
    <div class="admin-card">
        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="lede">
            Recent account and trial signups across Ron Belisle Calculators, Journey Premium, and CalcForAdvisors.
            Opening this page marks the listed new signups as viewed.
            <?php if ($remainingNew > 0): ?>
                <strong><?php echo (int) $remainingNew; ?> still unviewed</strong> outside this page’s recent window.
            <?php endif; ?>
        </p>

        <?php if ($successMessage !== ''): ?>
            <p class="notice notice-success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif ($errorMessage !== ''): ?>
            <p class="notice notice-error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="admin-summary">
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['last_7_days']; ?></span>
                <span class="l">Signups last 7 days</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['last_30_days']; ?></span>
                <span class="l">Signups last 30 days</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['journey_trials']; ?></span>
                <span class="l">Journey Premium trials</span>
            </div>
            <div class="admin-stat">
                <span class="n"><?php echo (int) $summary['calculator_signups']; ?></span>
                <span class="l">Calculator signups</span>
            </div>
        </div>

        <div class="admin-table-wrap">
            <?php if ($signups === []): ?>
                <p class="empty">No signups recorded yet.</p>
            <?php else: ?>
                <table class="admin-table" id="signups-table">
                    <thead>
                        <tr>
                            <th scope="col"><button class="sort-button" type="button" data-sort-column="0" data-sort-type="text">Name</button></th>
                            <th scope="col"><button class="sort-button" type="button" data-sort-column="1" data-sort-type="text">Email</button></th>
                            <th scope="col" aria-sort="descending"><button class="sort-button" type="button" data-sort-column="2" data-sort-type="date">Signup date/time</button></th>
                            <th scope="col"><button class="sort-button" type="button" data-sort-column="3" data-sort-type="text">Source</button></th>
                            <th scope="col"><button class="sort-button" type="button" data-sort-column="4" data-sort-type="text">Status</button></th>
                            <th scope="col"><button class="sort-button" type="button" data-sort-column="5" data-sort-type="text">Review status</button></th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($signups as $signup): ?>
                        <tr>
                            <td data-sort-value="<?php echo htmlspecialchars(strtolower((string) $signup['full_name']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($signup['full_name'] !== '' ? $signup['full_name'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-sort-value="<?php echo htmlspecialchars(strtolower((string) $signup['email']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($signup['email'] !== '' ? $signup['email'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-sort-value="<?php echo htmlspecialchars((string) $signup['signup_at'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $signup['signup_at_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-sort-value="<?php echo htmlspecialchars(strtolower((string) $signup['source_label']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $signup['source_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-sort-value="<?php echo htmlspecialchars(strtolower((string) $signup['status_label']), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string) $signup['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (($signup['source_key'] ?? '') === 'journey'): ?>
                                    <span class="detail">Trial ends <?php echo htmlspecialchars((string) $signup['trial_end_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="detail mono"><?php echo htmlspecialchars((string) $signup['stripe_subscription_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-value="<?php echo !empty($signup['is_new']) ? 'new' : 'viewed'; ?>">
                                <?php if (!empty($signup['is_new'])): ?>
                                    <span class="pill pill-new">New</span>
                                <?php else: ?>
                                    <span class="pill pill-viewed">Viewed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form class="delete-form" method="POST" action="/admin/recent-signups.php" onsubmit="return window.confirm('Delete this signup permanently?');">
                                    <?php echo rb_csrf_field(); ?>
                                    <input type="hidden" name="source" value="<?php echo htmlspecialchars((string) $signup['source_key'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="record_id" value="<?php echo (int) $signup['record_id']; ?>">
                                    <button class="delete-button" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <p class="footnote">Showing up to <?php echo (int) $limit; ?> most recent signups across all sources (newest first).</p>
    </div>
</div>
<script>
(function () {
    var table = document.getElementById('signups-table');
    if (!table || !table.tBodies.length) return;
    var headers = Array.prototype.slice.call(table.querySelectorAll('.sort-button'));
    var tbody = table.tBodies[0];

    headers.forEach(function (button) {
        button.addEventListener('click', function () {
            var column = Number(button.getAttribute('data-sort-column'));
            var header = button.closest('th');
            var wasAscending = header.getAttribute('aria-sort') === 'ascending';
            var direction = wasAscending ? 'descending' : 'ascending';
            var multiplier = direction === 'ascending' ? 1 : -1;
            var rows = Array.prototype.slice.call(tbody.rows);

            headers.forEach(function (other) {
                other.closest('th').removeAttribute('aria-sort');
            });
            header.setAttribute('aria-sort', direction);

            rows.sort(function (a, b) {
                var aValue = a.cells[column].getAttribute('data-sort-value') || '';
                var bValue = b.cells[column].getAttribute('data-sort-value') || '';
                return aValue.localeCompare(bValue, undefined, { numeric: true, sensitivity: 'base' }) * multiplier;
            });
            rows.forEach(function (row) { tbody.appendChild(row); });
        });
    });
}());
</script>
</body>
</html>
