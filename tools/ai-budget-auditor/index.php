<?php
session_start();
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
$premiumUpsellUrl = get_premium_upsell_url($isLoggedIn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Connect your YNAB budget for a free category snapshot with true overspending highlights. Premium adds GPT-4o budget analysis and follow-up questions.">
  <title>AI Budget Auditor - Ron Belisle</title>
  <link rel="stylesheet" href="../../css/styles.css">
  <style>
    .config-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 18px;
      margin-bottom: 8px;
    }
    .field label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      font-size: 14px;
    }
    .field input[type="password"],
    .field input[type="text"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      font-size: 14px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .field small {
      display: block;
      margin-top: 6px;
      color: #6b7280;
      font-size: 12px;
      line-height: 1.45;
    }
    .actions-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 24px;
    }
    .btn-primary {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      background: #1d4ed8;
      color: #fff;
      font-weight: 700;
      font-size: 15px;
      cursor: pointer;
    }
    .btn-primary:hover:not(:disabled) { background: #1e40af; }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-ai {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      background: #0d9488;
      color: #fff;
      font-weight: 700;
      font-size: 15px;
      cursor: pointer;
    }
    .btn-ai:hover:not(:disabled) { background: #0f766e; }
    .btn-ai:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-secondary {
      padding: 12px 20px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background: #fff;
      color: #374151;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
    }
    .btn-secondary:hover { background: #f9fafb; }
    #statusMessage {
      margin-top: 16px;
      font-size: 14px;
      color: #4b5563;
      min-height: 1.4em;
    }
    #statusMessage.error { color: #b91c1c; font-weight: 600; }
    #previewSection { display: none; margin-top: 32px; }
    .overspend { color: #b91c1c; font-weight: 600; }
    .tier-badge {
      display: inline-block;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #1e40af;
      background: #dbeafe;
      border: 1px solid #93c5fd;
      border-radius: 999px;
      padding: 4px 10px;
      margin-bottom: 10px;
    }
    .setup-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 22px 24px;
      margin-bottom: 28px;
    }
    .setup-card h3 {
      margin: 0 0 10px;
      font-size: 1.15rem;
      color: #1f2937;
    }
    .setup-card > p {
      margin: 0 0 18px;
      color: #4b5563;
      line-height: 1.55;
    }
    .setup-steps {
      margin: 0;
      padding-left: 22px;
      color: #374151;
      line-height: 1.6;
    }
    .setup-steps li { margin-bottom: 14px; }
    .setup-steps li:last-child { margin-bottom: 0; }
    .setup-steps a {
      color: #1d4ed8;
      font-weight: 600;
      text-decoration: none;
    }
    .setup-steps a:hover { text-decoration: underline; }
    .setup-note {
      display: block;
      margin-top: 8px;
      padding: 10px 12px;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-left: 3px solid #f59e0b;
      border-radius: 8px;
      font-size: 13px;
      color: #92400e;
      line-height: 1.5;
    }
    .snapshot-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      margin: 0 0 22px;
    }
    .snapshot-card {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 16px;
    }
    .snapshot-card.alert {
      background: #fef2f2;
      border-color: #fecaca;
    }
    .snapshot-card.ok {
      background: #f0fdf4;
      border-color: #86efac;
    }
    .snapshot-card-label {
      font-size: 12px;
      font-weight: 700;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 6px;
    }
    .snapshot-card-value {
      font-size: 22px;
      font-weight: 800;
      color: #111827;
    }
    .snapshot-card.alert .snapshot-card-value { color: #b91c1c; }
    .snapshot-card.ok .snapshot-card-value { color: #166534; }
    .premium-upsell-auditor {
      margin: 0 0 22px;
      padding: 24px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      border-radius: 12px;
      text-align: center;
    }
    .premium-upsell-auditor h4 {
      margin: 0 0 10px;
      font-size: 1.15rem;
      color: #fff;
    }
    .premium-upsell-auditor p {
      margin: 0 0 16px;
      opacity: 0.95;
      line-height: 1.55;
    }
    .premium-upsell-auditor a {
      display: inline-block;
      background: #fff;
      color: #667eea;
      padding: 12px 28px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
    }
    .premium-ai-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
      margin: 0 0 22px;
    }
    .premium-tag {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #92400e;
      background: #fef3c7;
      border: 1px solid #fcd34d;
      border-radius: 999px;
      padding: 2px 8px;
      margin-left: 6px;
      vertical-align: middle;
    }
  </style>
</head>
<body>
  <?php include(__DIR__ . '/../../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <span class="tier-badge">Free snapshot · Premium AI</span>
      <h1>AI Budget Auditor</h1>
      <p class="sub">Connect YNAB for a free category snapshot with true overspending highlights (Available &lt; $0). Premium unlocks GPT-4o narrative analysis and follow-up questions — included in your $3/mo subscription.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How it works</h2>
      <p><strong>Free:</strong> Paste your YNAB token, load your current-month categories, and review the snapshot table. Overspending is highlighted only when <strong>Available</strong> goes negative — credit card activity is handled with true YNAB logic.</p>
      <p style="margin-top: 10px;"><strong>Premium:</strong> Add your OpenAI key and click <strong>Get AI Analysis</strong> for a plain-English audit plus follow-up questions. Your keys stay in this browser’s <code>localStorage</code>; YNAB is fetched directly and AI requests go through <code>/api/ynab_proxy.php</code>.</p>
    </div>

    <section aria-label="API configuration">
      <div class="setup-card">
        <h3>🚀 Getting Started (60-Second Setup)</h3>
        <p>Your YNAB token stays securely in this browser’s <code>localStorage</code>. Nothing is saved on the server.</p>
        <ol class="setup-steps">
          <li>
            <strong>YNAB Personal Access Token</strong> — Open
            <a href="https://app.ynab.com/settings/developer" target="_blank" rel="noopener noreferrer">app.ynab.com/settings/developer</a>,
            click <strong>New Token</strong>, copy the token, and paste it below.
            <span class="setup-note"><strong>Note:</strong> If you normally use Google or Apple Sign-In to access YNAB, you will be prompted to quickly set a local account password first to unlock the “New Token” button.</span>
          </li>
          <li>
            <strong>YNAB Budget ID</strong> — Leave this set to <code>last-used</code> to automatically fetch your most recently active budget. Only change it if you want a specific budget UUID.
          </li>
          <?php if ($isPremium): ?>
          <li>
            <strong>OpenAI API Key</strong> <span class="premium-tag">Premium</span> — Create a key at
            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com/api-keys</a>
            for GPT-4o analysis and follow-ups.
          </li>
          <?php else: ?>
          <li>
            <strong>AI Analysis</strong> — Upgrade to <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Premium</a> to unlock GPT-4o budget analysis and follow-up questions (7-day free trial).
          </li>
          <?php endif; ?>
        </ol>
      </div>

      <h3>Configuration</h3>
      <div class="config-grid">
        <div class="field">
          <label for="ynabToken">YNAB Personal Access Token</label>
          <input type="password" id="ynabToken" autocomplete="off" spellcheck="false" placeholder="Paste YNAB token">
          <small>Generate a token at <a href="https://app.ynab.com/settings/developer" target="_blank" rel="noopener noreferrer" style="color: #1d4ed8;">app.ynab.com/settings/developer</a>.</small>
        </div>
        <?php if ($isPremium): ?>
        <div class="field" id="openaiField">
          <label for="openaiKey">OpenAI API Key <span class="premium-tag">Premium</span></label>
          <input type="password" id="openaiKey" autocomplete="off" spellcheck="false" placeholder="sk-…">
          <small>Sent to <code>/api/ynab_proxy.php</code> for GPT-4o analysis only. Not stored on the server.</small>
        </div>
        <?php endif; ?>
        <div class="field">
          <label for="budgetId">YNAB Budget ID</label>
          <input type="text" id="budgetId" autocomplete="off" spellcheck="false" placeholder="last-used">
          <small>Default <code>last-used</code> works for most setups. Or paste a budget UUID.</small>
        </div>
      </div>

      <div class="actions-row">
        <button type="button" id="runSnapshotBtn" class="btn-primary">Load Category Snapshot</button>
        <?php if ($isPremium): ?>
        <button type="button" id="runAiBtn" class="btn-ai" disabled>Get AI Analysis</button>
        <?php endif; ?>
        <button type="button" id="clearKeysBtn" class="btn-secondary">Clear saved keys</button>
      </div>
      <div id="statusMessage" role="status" aria-live="polite"></div>
    </section>

    <section id="previewSection" aria-label="Category preview">
      <h3 id="previewTitle">Category snapshot (current month)</h3>
      <p style="color: #4b5563; margin-top: 0;">Amounts are for the current budget month (UTC). Red <strong>Available</strong> values indicate true YNAB overspending.</p>

      <div id="snapshotSummary" class="snapshot-summary" aria-live="polite"></div>

      <?php if (!$isPremium): ?>
      <div id="premiumUpsellBanner" class="premium-upsell-auditor" style="display: none;">
        <h4>🔒 Unlock AI Budget Analysis</h4>
        <p>Your snapshot is ready. Upgrade to Premium for GPT-4o narrative insights, a 3-bullet action plan, and follow-up questions — plus all retirement calculators, save/compare, and PDF export. 7-day free trial, then $3/mo.</p>
        <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>">Start 7-day free trial</a>
      </div>
      <?php else: ?>
      <div id="premiumAiRow" class="premium-ai-row" style="display: none;">
        <button type="button" id="runAiBtnInline" class="btn-ai">🤖 Get AI Analysis</button>
        <span style="color: #4b5563; font-size: 14px;">GPT-4o review with follow-up questions</span>
      </div>
      <?php endif; ?>

      <div class="table-wrapper">
        <table class="data-table" id="categoryTable">
          <thead>
            <tr>
              <th>Group</th>
              <th>Category</th>
              <th>Budgeted</th>
              <th>Activity</th>
              <th>Available</th>
            </tr>
          </thead>
          <tbody id="categoryTableBody"></tbody>
        </table>
      </div>
    </section>
  </div>

  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="app.js"></script>
</body>
</html>
