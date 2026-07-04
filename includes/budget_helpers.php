<?php
/**
 * Manual-first budget app helpers (v1).
 * Requires logged-in ronbelisle.com user (users.id).
 */

if (!defined('BUDGET_HELPERS_LOADED')) {
    define('BUDGET_HELPERS_LOADED', 1);

    function budget_require_user_id() {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return (int) $_SESSION['user_id'];
    }

    function budget_tables_exist(mysqli $conn) {
        $result = $conn->query("SHOW TABLES LIKE 'budget_accounts'");
        return $result && $result->num_rows > 0;
    }

    function budget_month_start($monthParam) {
        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            return $monthParam . '-01';
        }
        return date('Y-m-01');
    }

    function budget_month_end($monthStart) {
        return date('Y-m-t', strtotime($monthStart));
    }

    function ensure_budget_setup(mysqli $conn, $userId) {
        $stmt = $conn->prepare('SELECT id FROM budget_accounts WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return;
        }
        $stmt->close();

        $accountName = 'Checking';
        $stmt = $conn->prepare(
            'INSERT INTO budget_accounts (user_id, name, account_type, cleared_balance, sort_order) VALUES (?, ?, ?, 0, 0)'
        );
        $type = 'checking';
        $stmt->bind_param('iss', $userId, $accountName, $type);
        $stmt->execute();
        $stmt->close();

        $groups = [
            'Everyday' => ['Groceries', 'Dining Out', 'Gas & Auto', 'Miscellaneous'],
            'Bills' => ['Utilities', 'Phone & Internet', 'Insurance', 'Subscriptions'],
            'Goals' => ['Giving', 'Savings'],
            'Income' => ['Paycheck', 'Other Income'],
        ];

        $groupOrder = 0;
        foreach ($groups as $groupName => $categories) {
            $stmt = $conn->prepare(
                'INSERT INTO budget_category_groups (user_id, name, sort_order) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('isi', $userId, $groupName, $groupOrder);
            $stmt->execute();
            $groupId = (int) $conn->insert_id;
            $stmt->close();

            $catOrder = 0;
            foreach ($categories as $catName) {
                $stmt = $conn->prepare(
                    'INSERT INTO budget_categories (user_id, group_id, name, sort_order) VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('iisi', $userId, $groupId, $catName, $catOrder);
                $stmt->execute();
                $stmt->close();
                $catOrder++;
            }
            $groupOrder++;
        }
    }

    function budget_json_error($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }
}
