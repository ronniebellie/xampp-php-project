<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/budget_helpers.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$isPremium = has_premium_access();
$premiumUpsellUrl = get_premium_upsell_url($isLoggedIn);
$tablesReady = $isLoggedIn && budget_tables_exist($conn);

if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = '/budget/';
    header('Location: /auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manual-first budget planner. Enter transactions yourself — no bank linking required.">
  <title>Manual Budget — Ron Belisle</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .tabs { display: flex; gap: 8px; flex-wrap: wrap; margin: 20px 0 16px; }
    .tab-btn {
      padding: 10px 16px; border: 1px solid #d1d5db; background: #fff;
      border-radius: 8px; font-weight: 600; cursor: pointer;
    }
    .tab-btn.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
    .panel { display: none; }
    .panel.active { display: block; }
    .form-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 14px; margin-bottom: 14px;
    }
    .field label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
    .field input, .field select {
      width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;
    }
    .flow-row { display: flex; gap: 16px; align-items: center; margin: 8px 0 14px; }
    .btn-primary {
      padding: 12px 20px; border: none; border-radius: 8px; background: #1d4ed8;
      color: #fff; font-weight: 700; cursor: pointer;
    }
    .btn-primary:hover { background: #1e40af; }
    .month-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 14px; }
    table.data th, table.data td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
    table.data th { color: #4b5563; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .neg { color: #b91c1c; }
    .pos { color: #047857; }
    .warn { color: #b45309; font-weight: 600; }
    .setup-box {
      background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 16px; margin: 16px 0;
    }
    .status { min-height: 1.2em; margin-top: 10px; font-size: 14px; color: #047857; font-weight: 600; }
    .status.error { color: #b91c1c; }
    .pill {
      display: inline-block; background: #ecfdf5; color: #065f46; font-size: 12px;
      font-weight: 700; padding: 4px 10px; border-radius: 999px; margin-left: 8px;
    }
    .target-input { width: 90px; text-align: right; }
  </style>
</head>
<body>
  <div class="wrap">
    <?php include __DIR__ . '/../includes/back-link-include.php'; ?>

    <header>
      <h1>Manual Budget <span class="pill">Beta</span></h1>
      <p class="sub">Enter transactions yourself — no bank linking. Review every dollar as you go. File import and extra accounts will come later under Premium.</p>
    </header>

    <?php if (!$tablesReady): ?>
      <div class="setup-box">
        <strong>One-time setup:</strong> run <code>sql/create_budget_tables.sql</code> in your database
        (e.g. phpMyAdmin → database <code>ronbelisle_premium</code> → Import/SQL), then reload this page.
      </div>
    <?php else: ?>

    <div class="month-row">
      <label for="monthPicker"><strong>Month</strong></label>
      <input type="month" id="monthPicker">
      <span id="loadStatus" style="color:#6b7280;font-size:14px;"></span>
    </div>

    <div class="tabs">
      <button type="button" class="tab-btn active" data-tab="add">Add transaction</button>
      <button type="button" class="tab-btn" data-tab="register">Register</button>
      <button type="button" class="tab-btn" data-tab="plan">Monthly plan</button>
    </div>

    <section id="panel-add" class="panel active card">
      <h2 style="margin-top:0;">Add transaction</h2>
      <form id="txnForm">
        <div class="form-grid">
          <div class="field">
            <label for="txnDate">Date</label>
            <input type="date" id="txnDate" required>
          </div>
          <div class="field">
            <label for="txnAccount">Account</label>
            <select id="txnAccount" required></select>
          </div>
          <div class="field">
            <label for="txnCategory">Category</label>
            <select id="txnCategory" required></select>
          </div>
          <div class="field">
            <label for="txnAmount">Amount</label>
            <input type="number" id="txnAmount" min="0.01" step="0.01" required placeholder="0.00">
          </div>
        </div>
        <div class="field">
          <label for="txnPayee">Payee</label>
          <input type="text" id="txnPayee" required maxlength="255" placeholder="Store or payer name">
        </div>
        <div class="field" style="margin-top:12px;">
          <label for="txnMemo">Memo (optional)</label>
          <input type="text" id="txnMemo" maxlength="512">
        </div>
        <div class="flow-row">
          <label><input type="radio" name="flow" value="expense" checked> Expense (outflow)</label>
          <label><input type="radio" name="flow" value="income"> Income (inflow)</label>
        </div>
        <button type="submit" class="btn-primary">Save transaction</button>
        <div id="formStatus" class="status"></div>
      </form>
    </section>

    <section id="panel-register" class="panel card">
      <h2 style="margin-top:0;">Register</h2>
      <div style="overflow-x:auto;">
        <table class="data" id="registerTable">
          <thead>
            <tr>
              <th>Date</th>
              <th>Payee</th>
              <th>Category</th>
              <th class="num">Outflow</th>
              <th class="num">Inflow</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <p id="registerEmpty" style="color:#6b7280;display:none;">No transactions this month yet.</p>
    </section>

    <section id="panel-plan" class="panel card">
      <h2 style="margin-top:0;">Monthly plan</h2>
      <p style="color:#4b5563;font-size:14px;margin-top:0;">
        Set a target per category. <strong>Activity</strong> is spending (negative) or income (positive).
        <strong>Available</strong> = target + activity — negative means overspent.
      </p>
      <div style="overflow-x:auto;">
        <table class="data" id="planTable">
          <thead>
            <tr>
              <th>Category</th>
              <th class="num">Target</th>
              <th class="num">Activity</th>
              <th class="num">Available</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <?php endif; ?>

    <footer class="site-footer" style="margin-top:32px;">
      <p><a href="/">Ron Belisle</a> · Manual-first budgeting · <?php if (!$isPremium): ?><a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Premium</a><?php else: ?>Premium member<?php endif; ?></p>
    </footer>
  </div>

  <?php if ($tablesReady): ?>
  <script>
    window.BUDGET_API = '/api/budget.php';
  </script>
  <script src="app.js"></script>
  <?php endif; ?>
</body>
</html>
