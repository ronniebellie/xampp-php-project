<?php
/**
 * Budget app JSON API (v1): month snapshot, add transaction, set category target.
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/budget_helpers.php';

$userId = budget_require_user_id();
if (!$userId) {
    budget_json_error('Log in to use the budget app.', 401);
}

if (!budget_tables_exist($conn)) {
    budget_json_error(
        'Budget tables are missing. Run sql/create_budget_tables.sql in your database, then reload.',
        503
    );
}

ensure_budget_setup($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $monthStart = budget_month_start($_GET['month'] ?? '');
    $monthEnd = budget_month_end($monthStart);

    $accounts = [];
    $stmt = $conn->prepare(
        'SELECT id, name, account_type, cleared_balance FROM budget_accounts WHERE user_id = ? ORDER BY sort_order, id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $accounts[] = $row;
    }
    $stmt->close();

    $groups = [];
    $stmt = $conn->prepare(
        'SELECT id, name FROM budget_category_groups WHERE user_id = ? ORDER BY sort_order, id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $groups[(int) $row['id']] = ['id' => (int) $row['id'], 'name' => $row['name'], 'categories' => []];
    }
    $stmt->close();

    $categories = [];
    $stmt = $conn->prepare(
        'SELECT id, group_id, name FROM budget_categories WHERE user_id = ? AND is_hidden = 0 ORDER BY sort_order, id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cat = [
            'id' => (int) $row['id'],
            'group_id' => $row['group_id'] ? (int) $row['group_id'] : null,
            'name' => $row['name'],
        ];
        $categories[] = $cat;
        if ($cat['group_id'] && isset($groups[$cat['group_id']])) {
            $groups[$cat['group_id']]['categories'][] = $cat;
        }
    }
    $stmt->close();

    $transactions = [];
    $stmt = $conn->prepare(
        'SELECT t.id, t.account_id, t.category_id, t.txn_date, t.payee, t.memo, t.amount, c.name AS category_name
         FROM budget_transactions t
         LEFT JOIN budget_categories c ON c.id = t.category_id
         WHERE t.user_id = ? AND t.txn_date BETWEEN ? AND ?
         ORDER BY t.txn_date DESC, t.id DESC'
    );
    $stmt->bind_param('iss', $userId, $monthStart, $monthEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $transactions[] = [
            'id' => (int) $row['id'],
            'account_id' => (int) $row['account_id'],
            'category_id' => $row['category_id'] ? (int) $row['category_id'] : null,
            'category_name' => $row['category_name'],
            'txn_date' => $row['txn_date'],
            'payee' => $row['payee'],
            'memo' => $row['memo'],
            'amount' => (float) $row['amount'],
        ];
    }
    $stmt->close();

    $plan = [];
    $stmt = $conn->prepare(
        'SELECT c.id, c.name, g.name AS group_name,
                COALESCE(t.target_amount, 0) AS target_amount,
                COALESCE(SUM(tx.amount), 0) AS activity
         FROM budget_categories c
         LEFT JOIN budget_category_groups g ON g.id = c.group_id
         LEFT JOIN budget_monthly_targets t
           ON t.category_id = c.id AND t.user_id = ? AND t.month_date = ?
         LEFT JOIN budget_transactions tx
           ON tx.category_id = c.id AND tx.user_id = ? AND tx.txn_date BETWEEN ? AND ?
         WHERE c.user_id = ? AND c.is_hidden = 0
         GROUP BY c.id, c.name, g.name, t.target_amount
         ORDER BY g.sort_order, c.sort_order, c.id'
    );
    $stmt->bind_param('isissi', $userId, $monthStart, $userId, $monthStart, $monthEnd, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $target = (float) $row['target_amount'];
        $activity = (float) $row['activity'];
        $plan[] = [
            'category_id' => (int) $row['id'],
            'category_name' => $row['name'],
            'group_name' => $row['group_name'],
            'target' => $target,
            'activity' => $activity,
            'available' => $target + $activity,
        ];
    }
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'month' => substr($monthStart, 0, 7),
        'accounts' => $accounts,
        'groups' => array_values($groups),
        'categories' => $categories,
        'transactions' => $transactions,
        'plan' => $plan,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    budget_json_error('Method not allowed.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    budget_json_error('Invalid JSON body.');
}

$action = $data['action'] ?? '';

if ($action === 'add_transaction') {
    $accountId = (int) ($data['account_id'] ?? 0);
    $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : 0;
    $txnDate = trim($data['txn_date'] ?? '');
    $payee = trim($data['payee'] ?? '');
    $memo = trim($data['memo'] ?? '');
    $flow = $data['flow'] ?? 'expense';
    $amountRaw = $data['amount'] ?? null;

    if (!$accountId || !$categoryId || $payee === '' || $amountRaw === null || $amountRaw === '') {
        budget_json_error('Account, category, payee, and amount are required.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txnDate)) {
        budget_json_error('Date must be YYYY-MM-DD.');
    }

    $amount = round((float) $amountRaw, 2);
    if ($amount <= 0) {
        budget_json_error('Amount must be greater than zero.');
    }
    if ($flow === 'expense') {
        $amount = -$amount;
    } elseif ($flow !== 'income') {
        budget_json_error('Flow must be expense or income.');
    }

    $stmt = $conn->prepare('SELECT id FROM budget_accounts WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $accountId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        budget_json_error('Invalid account.');
    }
    $stmt->close();

    $stmt = $conn->prepare('SELECT id FROM budget_categories WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $categoryId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        budget_json_error('Invalid category.');
    }
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO budget_transactions (user_id, account_id, category_id, txn_date, payee, memo, amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iiisssd', $userId, $accountId, $categoryId, $txnDate, $payee, $memo, $amount);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not save transaction.');
    }
    $newId = (int) $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'transaction_id' => $newId]);
    exit;
}

if ($action === 'set_target') {
    $categoryId = (int) ($data['category_id'] ?? 0);
    $monthStart = budget_month_start($data['month'] ?? '');
    $target = round((float) ($data['target'] ?? 0), 2);

    if (!$categoryId) {
        budget_json_error('Category is required.');
    }
    if ($target < 0) {
        budget_json_error('Target cannot be negative.');
    }

    $stmt = $conn->prepare('SELECT id FROM budget_categories WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $categoryId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        budget_json_error('Invalid category.');
    }
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO budget_monthly_targets (user_id, category_id, month_date, target_amount)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->bind_param('iisd', $userId, $categoryId, $monthStart, $target);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not save target.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

budget_json_error('Unknown action.');
