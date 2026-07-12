<?php
/**
 * Premium: export budget transactions as CSV for the selected month.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/budget_helpers.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

$userId = budget_require_user_id();
if (!$userId) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Log in to export.';
    exit;
}

if (!has_premium_access()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Premium subscription required to export.';
    exit;
}

if (!budget_tables_exist($conn)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Budget tables are missing.';
    exit;
}

$monthStart = budget_month_start($_GET['month'] ?? '');
$monthEnd = budget_month_end($monthStart);
$monthLabel = substr($monthStart, 0, 7);

$stmt = $conn->prepare(
    'SELECT t.txn_date, t.payee, t.memo, t.amount, a.name AS account_name, c.name AS category_name
     FROM budget_transactions t
     LEFT JOIN budget_accounts a ON a.id = t.account_id
     LEFT JOIN budget_categories c ON c.id = t.category_id
     WHERE t.user_id = ? AND t.txn_date BETWEEN ? AND ?
     ORDER BY t.txn_date ASC, t.id ASC'
);
$stmt->bind_param('iss', $userId, $monthStart, $monthEnd);
$stmt->execute();
$res = $stmt->get_result();

$filename = 'budget-transactions-' . $monthLabel . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Payee', 'Memo', 'Account', 'Category', 'Outflow', 'Inflow']);

while ($row = $res->fetch_assoc()) {
    $amount = (float) $row['amount'];
    $outflow = $amount < 0 ? number_format(abs($amount), 2, '.', '') : '';
    $inflow = $amount > 0 ? number_format($amount, 2, '.', '') : '';
    fputcsv($out, [
        $row['txn_date'],
        $row['payee'],
        $row['memo'],
        $row['account_name'],
        $row['category_name'],
        $outflow,
        $inflow,
    ]);
}

fclose($out);
$stmt->close();
exit;
