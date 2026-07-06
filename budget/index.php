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
    .btn-secondary {
      padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px;
      background: #fff; font-weight: 600; cursor: pointer; font-size: 13px;
    }
    .btn-secondary:hover { background: #f9fafb; }
    .btn-danger { color: #b91c1c; border-color: #fecaca; }
    .btn-danger:disabled { opacity: 0.45; cursor: not-allowed; }
    .accounts-add { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
    .hint { color: #6b7280; font-size: 13px; margin: 0 0 12px; line-height: 1.5; }
    .premium-note {
      background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
      padding: 12px 14px; font-size: 13px; color: #1e3a8a; margin-bottom: 16px; line-height: 1.5;
    }
    .import-step { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
    .import-step:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .mapping-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px; margin-top: 10px;
    }
    .register-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <?php include __DIR__ . '/../includes/back-link-include.php'; ?>

    <header>
      <h1>Manual Budget <span class="pill">Beta</span></h1>
      <p class="sub">Enter transactions yourself — no bank linking. Unlimited accounts and manual entry are free. <strong>Premium</strong> adds bank CSV import and data export.</p>
    </header>

    <?php if (!$tablesReady): ?>
      <div class="setup-box">
        <strong>One-time setup:</strong> run <code>sql/create_budget_tables.sql</code> in your database
        (e.g. phpMyAdmin → database <code>ronbelisle_premium</code> → Import/SQL), then reload this page.
      </div>
    <?php else: ?>

    <div class="month-row">
      <button type="button" class="btn-secondary" id="monthPrev" aria-label="Previous month">←</button>
      <label for="monthPicker"><strong>Month</strong></label>
      <input type="month" id="monthPicker">
      <button type="button" class="btn-secondary" id="monthNext" aria-label="Next month">→</button>
      <span id="loadStatus" style="color:#6b7280;font-size:14px;"></span>
    </div>

    <div class="tabs">
      <button type="button" class="tab-btn active" data-tab="add">Add transaction</button>
      <button type="button" class="tab-btn" data-tab="register">Register</button>
      <button type="button" class="tab-btn" data-tab="plan">Monthly plan</button>
      <button type="button" class="tab-btn" data-tab="accounts">Accounts</button>
      <button type="button" class="tab-btn" data-tab="categories">Categories</button>
      <button type="button" class="tab-btn" data-tab="import">Import</button>
    </div>

    <section id="panel-add" class="panel active card">
      <h2 id="txnFormTitle" style="margin-top:0;">Add transaction</h2>
      <form id="txnForm">
        <input type="hidden" id="txnEditId" value="">
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
        <button type="submit" class="btn-primary" id="txnSubmitBtn">Save transaction</button>
        <button type="button" class="btn-secondary" id="txnCancelBtn" style="display:none;margin-left:8px;">Cancel edit</button>
        <div id="formStatus" class="status"></div>
      </form>
    </section>

    <section id="panel-register" class="panel card">
      <div class="register-actions">
        <h2 style="margin:0;">Register</h2>
        <?php if ($isPremium): ?>
        <a href="#" class="btn-secondary" id="exportCsvBtn">Export CSV</a>
        <?php else: ?>
        <span class="hint" style="margin:0;">CSV export is a <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Premium</a> feature.</span>
        <?php endif; ?>
      </div>
      <div style="overflow-x:auto;">
        <table class="data" id="registerTable">
          <thead>
            <tr>
              <th>Date</th>
              <th>Payee</th>
              <th>Account</th>
              <th>Category</th>
              <th class="num">Outflow</th>
              <th class="num">Inflow</th>
              <th>Actions</th>
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

    <section id="panel-accounts" class="panel card">
      <h2 style="margin-top:0;">Accounts</h2>
      <p class="hint">Name each account and pick a type. Add checking, savings, cash, and credit cards — all included free.</p>
      <?php if (!$isPremium): ?>
      <div class="premium-note">
        <strong>Premium:</strong> import transactions from your bank’s CSV export and export your budget data.
        <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Learn about Premium</a>
      </div>
      <?php endif; ?>

      <div class="accounts-add">
        <h3 style="margin:0 0 12px;font-size:16px;">Add account</h3>
        <form id="accountForm">
          <div class="form-grid">
            <div class="field">
              <label for="newAccountName">Name</label>
              <input type="text" id="newAccountName" maxlength="128" required placeholder="e.g. Fidelity CMA">
            </div>
            <div class="field">
              <label for="newAccountType">Type</label>
              <select id="newAccountType" required>
                <option value="checking">Checking</option>
                <option value="savings">Savings</option>
                <option value="cash">Cash</option>
                <option value="credit">Credit card</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn-primary">Add account</button>
          <div id="accountFormStatus" class="status"></div>
        </form>
      </div>

      <div style="overflow-x:auto;">
        <table class="data" id="accountsTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th class="num">Transactions</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="panel-categories" class="panel card">
      <h2 style="margin-top:0;">Categories</h2>
      <p class="hint">Rename categories, add new ones, or hide categories you no longer use. Hidden categories stay in history but won’t appear in new transactions.</p>

      <div class="accounts-add">
        <h3 style="margin:0 0 12px;font-size:16px;">Add category group</h3>
        <form id="groupForm" style="margin-bottom:16px;">
          <div class="form-grid">
            <div class="field">
              <label for="newGroupName">Group name</label>
              <input type="text" id="newGroupName" maxlength="128" required placeholder="e.g. Monthly Bills">
            </div>
          </div>
          <button type="submit" class="btn-secondary">Add group</button>
          <div id="groupFormStatus" class="status"></div>
        </form>

        <h3 style="margin:0 0 12px;font-size:16px;">Add category</h3>
        <form id="categoryForm">
          <div class="form-grid">
            <div class="field">
              <label for="newCategoryName">Category name</label>
              <input type="text" id="newCategoryName" maxlength="128" required placeholder="e.g. Groceries">
            </div>
            <div class="field">
              <label for="newCategoryGroup">Group</label>
              <select id="newCategoryGroup" required></select>
            </div>
          </div>
          <button type="submit" class="btn-primary">Add category</button>
          <div id="categoryFormStatus" class="status"></div>
        </form>
      </div>

      <div style="overflow-x:auto;">
        <table class="data" id="categoriesTable">
          <thead>
            <tr>
              <th>Group</th>
              <th>Category</th>
              <th class="num">Transactions</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="panel-import" class="panel card">
      <h2 style="margin-top:0;">Import from CSV</h2>
      <?php if (!$isPremium): ?>
      <div class="premium-note">
        <strong>Premium feature:</strong> upload a CSV export from your bank, map columns, preview, and import up to 500 transactions at a time.
        <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Upgrade to Premium</a>
      </div>
      <?php else: ?>
      <p class="hint">Export a transaction CSV from your bank (most banks offer this under account activity or statements). We never connect to your bank — you upload the file yourself.</p>

      <form id="importForm">
        <div class="import-step">
          <h3 style="margin:0 0 8px;font-size:15px;">1. Choose file</h3>
          <input type="file" id="importFile" accept=".csv,text/csv" required>
        </div>

        <div id="importMapping" class="import-step" style="display:none;">
          <h3 style="margin:0 0 8px;font-size:15px;">2. Map columns</h3>
          <div class="field" style="max-width:280px;margin-bottom:10px;">
            <label for="importPreset">Format preset</label>
            <select id="importPreset">
              <option value="generic">Generic CSV</option>
              <option value="fidelity">Brokerage / cash management (Run Date, Action, Amount)</option>
            </select>
          </div>
          <div class="mapping-grid">
            <div class="field">
              <label for="mapDate">Date</label>
              <select id="mapDate"></select>
            </div>
            <div class="field">
              <label for="mapPayee">Payee</label>
              <select id="mapPayee"></select>
            </div>
            <div class="field">
              <label for="mapMemo">Memo (optional)</label>
              <select id="mapMemo"></select>
            </div>
            <div class="field">
              <label for="importAmountMode">Amount style</label>
              <select id="importAmountMode">
                <option value="signed">Single signed column (+ inflow, − outflow)</option>
                <option value="split">Separate outflow / inflow columns</option>
              </select>
            </div>
            <div class="field">
              <label for="mapAmount">Amount column</label>
              <select id="mapAmount"></select>
            </div>
            <div class="field">
              <label for="mapOutflow">Outflow column</label>
              <select id="mapOutflow"></select>
            </div>
            <div class="field">
              <label for="mapInflow">Inflow column</label>
              <select id="mapInflow"></select>
            </div>
          </div>
          <label style="display:block;margin-top:10px;font-size:13px;">
            <input type="checkbox" id="importStripCash"> Strip “(Cash)” suffix from payee names
          </label>
        </div>

        <div id="importDefaults" class="import-step" style="display:none;">
          <h3 style="margin:0 0 8px;font-size:15px;">3. Defaults for imported rows</h3>
          <p class="hint">All rows in this import use the same account and category. You can re-categorize individual transactions afterward in the register.</p>
          <div class="form-grid">
            <div class="field">
              <label for="importAccount">Account</label>
              <select id="importAccount" required></select>
            </div>
            <div class="field">
              <label for="importCategory">Category</label>
              <select id="importCategory" required></select>
            </div>
          </div>
        </div>

        <div id="importPreview" class="import-step" style="display:none;">
          <h3 style="margin:0 0 8px;font-size:15px;">4. Preview</h3>
          <p id="importPreviewSummary" class="hint"></p>
          <div style="overflow-x:auto;">
            <table class="data" id="importPreviewTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Payee</th>
                  <th>Memo</th>
                  <th class="num">Outflow</th>
                  <th class="num">Inflow</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <button type="submit" class="btn-primary" id="importSubmitBtn" style="margin-top:14px;" disabled>Import transactions</button>
        </div>

        <div id="importStatus" class="status"></div>
      </form>
      <?php endif; ?>
    </section>

    <?php endif; ?>

    <footer class="site-footer" style="margin-top:32px;">
      <p><a href="/">Ron Belisle</a> · Manual-first budgeting · <?php if (!$isPremium): ?><a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Premium</a><?php else: ?>Premium member<?php endif; ?></p>
    </footer>
  </div>

  <?php if ($tablesReady): ?>
  <script>
    window.BUDGET_API = '/api/budget.php';
    window.BUDGET_EXPORT_API = '/api/budget_export.php';
    window.BUDGET_IS_PREMIUM = <?php echo $isPremium ? 'true' : 'false'; ?>;
  </script>
  <script src="app.js?v=3"></script>
  <?php if ($isPremium): ?>
  <script src="import.js?v=1"></script>
  <?php endif; ?>
  <?php endif; ?>
</body>
</html>
