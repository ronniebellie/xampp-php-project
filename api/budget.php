<?php
/**
 * Budget app JSON API (v1): month snapshot, add transaction, set category target.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/budget_helpers.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

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
        'SELECT a.id, a.name, a.account_type, a.cleared_balance,
                (SELECT COUNT(*) FROM budget_transactions t WHERE t.account_id = a.id) AS transaction_count
         FROM budget_accounts a
         WHERE a.user_id = ?
         ORDER BY a.sort_order, a.id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $accounts[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'account_type' => $row['account_type'],
            'account_type_label' => budget_account_type_label($row['account_type']),
            'cleared_balance' => (float) $row['cleared_balance'],
            'transaction_count' => (int) $row['transaction_count'],
        ];
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
    $allCategories = [];
    $stmt = $conn->prepare(
        'SELECT c.id, c.group_id, c.name, c.is_hidden, g.name AS group_name,
                (SELECT COUNT(*) FROM budget_transactions t WHERE t.category_id = c.id) AS transaction_count
         FROM budget_categories c
         LEFT JOIN budget_category_groups g ON g.id = c.group_id
         WHERE c.user_id = ?
         ORDER BY g.sort_order, c.sort_order, c.id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cat = [
            'id' => (int) $row['id'],
            'group_id' => $row['group_id'] ? (int) $row['group_id'] : null,
            'group_name' => $row['group_name'],
            'name' => $row['name'],
            'is_hidden' => (int) $row['is_hidden'] === 1,
            'transaction_count' => (int) $row['transaction_count'],
        ];
        $allCategories[] = $cat;
        if (!$cat['is_hidden']) {
            $categories[] = [
                'id' => $cat['id'],
                'group_id' => $cat['group_id'],
                'name' => $cat['name'],
            ];
            if ($cat['group_id'] && isset($groups[$cat['group_id']])) {
                $groups[$cat['group_id']]['categories'][] = [
                    'id' => $cat['id'],
                    'group_id' => $cat['group_id'],
                    'name' => $cat['name'],
                ];
            }
        }
    }
    $stmt->close();

    $transactions = [];
    $stmt = $conn->prepare(
        'SELECT t.id, t.account_id, t.category_id, t.txn_date, t.payee, t.memo, t.amount,
                c.name AS category_name, a.name AS account_name
         FROM budget_transactions t
         LEFT JOIN budget_categories c ON c.id = t.category_id
         LEFT JOIN budget_accounts a ON a.id = t.account_id
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
            'account_name' => $row['account_name'],
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
        'all_categories' => $allCategories,
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
    $fields = budget_parse_transaction_input($data);

    if (!budget_assert_user_account($conn, $userId, $fields['account_id'])) {
        budget_json_error('Invalid account.');
    }
    if (!budget_assert_user_category($conn, $userId, $fields['category_id'])) {
        budget_json_error('Invalid category.');
    }

    $stmt = $conn->prepare(
        'INSERT INTO budget_transactions (user_id, account_id, category_id, txn_date, payee, memo, amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'iiisssd',
        $userId,
        $fields['account_id'],
        $fields['category_id'],
        $fields['txn_date'],
        $fields['payee'],
        $fields['memo'],
        $fields['amount']
    );
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not save transaction.');
    }
    $newId = (int) $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'transaction_id' => $newId]);
    exit;
}

if ($action === 'update_transaction') {
    $transactionId = (int) ($data['transaction_id'] ?? 0);
    if (!$transactionId || !budget_assert_user_transaction($conn, $userId, $transactionId)) {
        budget_json_error('Invalid transaction.');
    }

    $fields = budget_parse_transaction_input($data);

    if (!budget_assert_user_account($conn, $userId, $fields['account_id'])) {
        budget_json_error('Invalid account.');
    }
    if (!budget_assert_user_category($conn, $userId, $fields['category_id'])) {
        budget_json_error('Invalid category.');
    }

    $stmt = $conn->prepare(
        'UPDATE budget_transactions
         SET account_id = ?, category_id = ?, txn_date = ?, payee = ?, memo = ?, amount = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param(
        'iisssdii',
        $fields['account_id'],
        $fields['category_id'],
        $fields['txn_date'],
        $fields['payee'],
        $fields['memo'],
        $fields['amount'],
        $transactionId,
        $userId
    );
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not update transaction.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete_transaction') {
    $transactionId = (int) ($data['transaction_id'] ?? 0);
    if (!$transactionId || !budget_assert_user_transaction($conn, $userId, $transactionId)) {
        budget_json_error('Invalid transaction.');
    }

    $stmt = $conn->prepare('DELETE FROM budget_transactions WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $transactionId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not delete transaction.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
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

if ($action === 'import_transactions') {
    if (!has_premium_access()) {
        budget_json_error('Premium subscription required for CSV import.', 403);
    }

    $accountId = (int) ($data['account_id'] ?? 0);
    $categoryId = (int) ($data['category_id'] ?? 0);
    $rows = $data['transactions'] ?? [];

    if (!$accountId || !$categoryId) {
        budget_json_error('Account and category are required for import.');
    }
    if (!is_array($rows) || count($rows) === 0) {
        budget_json_error('No transactions to import.');
    }
    if (count($rows) > 500) {
        budget_json_error('Import limited to 500 transactions at a time.');
    }

    if (!budget_assert_user_account($conn, $userId, $accountId)) {
        budget_json_error('Invalid account.');
    }
    if (!budget_assert_user_category($conn, $userId, $categoryId)) {
        budget_json_error('Invalid category.');
    }

    $stmt = $conn->prepare(
        'INSERT INTO budget_transactions (user_id, account_id, category_id, txn_date, payee, memo, amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $imported = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            $skipped++;
            continue;
        }
        $txnDate = trim($row['txn_date'] ?? '');
        $payee = trim($row['payee'] ?? '');
        $memo = trim($row['memo'] ?? '');
        $amount = isset($row['amount']) ? round((float) $row['amount'], 2) : null;

        if ($payee === '' || $amount === null || $amount == 0.0) {
            $skipped++;
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txnDate)) {
            $skipped++;
            continue;
        }
        if (strlen($payee) > 255) {
            $payee = substr($payee, 0, 255);
        }
        if (strlen($memo) > 512) {
            $memo = substr($memo, 0, 512);
        }

        $stmt->bind_param('iiisssd', $userId, $accountId, $categoryId, $txnDate, $payee, $memo, $amount);
        if ($stmt->execute()) {
            $imported++;
        } else {
            $skipped++;
        }
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'imported' => $imported, 'skipped' => $skipped]);
    exit;
}

if ($action === 'add_category_group') {
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        budget_json_error('Group name is required.');
    }
    if (strlen($name) > 128) {
        budget_json_error('Group name is too long.');
    }

    $sort = 0;
    $stmt = $conn->prepare(
        'INSERT INTO budget_category_groups (user_id, name, sort_order) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('isi', $userId, $name, $sort);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not add category group.');
    }
    $newId = (int) $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'group_id' => $newId]);
    exit;
}

if ($action === 'add_category') {
    $name = trim($data['name'] ?? '');
    $groupId = (int) ($data['group_id'] ?? 0);

    if ($name === '' || !$groupId) {
        budget_json_error('Category name and group are required.');
    }
    if (strlen($name) > 128) {
        budget_json_error('Category name is too long.');
    }

    $stmt = $conn->prepare('SELECT id FROM budget_category_groups WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $groupId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        budget_json_error('Invalid category group.');
    }
    $stmt->close();

    $sort = 0;
    $stmt = $conn->prepare(
        'INSERT INTO budget_categories (user_id, group_id, name, sort_order) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('iisi', $userId, $groupId, $name, $sort);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not add category.');
    }
    $newId = (int) $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'category_id' => $newId]);
    exit;
}

if ($action === 'update_category') {
    $categoryId = (int) ($data['category_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $groupId = (int) ($data['group_id'] ?? 0);

    if (!$categoryId || $name === '' || !$groupId) {
        budget_json_error('Category, name, and group are required.');
    }
    if (!budget_assert_user_category($conn, $userId, $categoryId)) {
        budget_json_error('Invalid category.');
    }

    $stmt = $conn->prepare('SELECT id FROM budget_category_groups WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $groupId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        budget_json_error('Invalid category group.');
    }
    $stmt->close();

    $stmt = $conn->prepare(
        'UPDATE budget_categories SET name = ?, group_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param('siii', $name, $groupId, $categoryId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not update category.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'set_category_hidden') {
    $categoryId = (int) ($data['category_id'] ?? 0);
    $hidden = !empty($data['hidden']);

    if (!$categoryId || !budget_assert_user_category($conn, $userId, $categoryId)) {
        budget_json_error('Invalid category.');
    }

    $hiddenInt = $hidden ? 1 : 0;
    $stmt = $conn->prepare(
        'UPDATE budget_categories SET is_hidden = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param('iii', $hiddenInt, $categoryId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not update category.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'add_account') {
    $name = trim($data['name'] ?? '');
    $type = trim($data['account_type'] ?? 'checking');

    if ($name === '') {
        budget_json_error('Account name is required.');
    }
    if (strlen($name) > 128) {
        budget_json_error('Account name is too long.');
    }
    if (!budget_valid_account_type($type)) {
        budget_json_error('Invalid account type.');
    }

    $sort = 0;
    $stmt = $conn->prepare(
        'INSERT INTO budget_accounts (user_id, name, account_type, cleared_balance, sort_order)
         VALUES (?, ?, ?, 0, ?)'
    );
    $stmt->bind_param('issi', $userId, $name, $type, $sort);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not add account.');
    }
    $newId = (int) $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'account_id' => $newId]);
    exit;
}

if ($action === 'update_account') {
    $accountId = (int) ($data['account_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $type = trim($data['account_type'] ?? '');

    if (!$accountId || $name === '') {
        budget_json_error('Account and name are required.');
    }
    if (strlen($name) > 128) {
        budget_json_error('Account name is too long.');
    }
    if (!budget_valid_account_type($type)) {
        budget_json_error('Invalid account type.');
    }
    if (!budget_assert_user_account($conn, $userId, $accountId)) {
        budget_json_error('Invalid account.');
    }

    $stmt = $conn->prepare(
        'UPDATE budget_accounts SET name = ?, account_type = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param('ssii', $name, $type, $accountId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not update account.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete_account') {
    $accountId = (int) ($data['account_id'] ?? 0);
    if (!$accountId) {
        budget_json_error('Account is required.');
    }
    if (!budget_assert_user_account($conn, $userId, $accountId)) {
        budget_json_error('Invalid account.');
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM budget_accounts WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $countRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ((int) ($countRow['c'] ?? 0) <= 1) {
        budget_json_error('You must keep at least one account.');
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM budget_transactions WHERE account_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $accountId, $userId);
    $stmt->execute();
    $txnRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ((int) ($txnRow['c'] ?? 0) > 0) {
        budget_json_error('Remove or reassign transactions before deleting this account.');
    }

    $stmt = $conn->prepare('DELETE FROM budget_accounts WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $accountId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        budget_json_error('Could not delete account.');
    }
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

budget_json_error('Unknown action.');
