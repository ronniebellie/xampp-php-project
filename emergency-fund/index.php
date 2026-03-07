<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Build your emergency fund. Calculate how many months of expenses to save and how long to reach your target.">
  <title>Emergency Fund Builder</title>
  <?php $og_title = $ld_name = 'Emergency Fund Builder'; $og_description = $ld_description = 'Build your emergency fund. Calculate how many months of expenses to save and how long to reach your target.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>Emergency Fund Builder</h1>
      <p class="sub">Set a target (e.g. 3–6 months of expenses) and see how long it takes to get there at your savings rate.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Why an emergency fund?</h2>
      <p>An emergency fund covers unexpected expenses (car repair, job loss, medical bill) without going into debt. Many advisors suggest <strong>3–6 months of essential expenses</strong> in a separate savings account. This tool shows your target amount and how many months it will take to reach it based on your current savings and monthly contribution.</p>
    </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
  <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
  <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
    <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
    <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
    <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;">⚖️ Compare</button>
    <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;">📄 PDF</button>
    <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;">📊 CSV</button>
    <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
  </div>
  <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568;">Save / Load / Compare / PDF / CSV</p>
</div>
<?php endif; ?>

    <form id="efForm">
      <h3>Your situation</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="monthlyExpenses" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly essential expenses ($)</label>
          <input type="number" id="monthlyExpenses" min="100" step="50" value="4000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Rent, utilities, food, insurance, minimum debt payments</small>
        </div>
        <div>
          <label for="targetMonths" style="display: block; margin-bottom: 5px; font-weight: 600;">Target (months of expenses)</label>
          <select id="targetMonths" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
            <option value="3">3 months</option>
            <option value="4">4 months</option>
            <option value="5">5 months</option>
            <option value="6" selected>6 months</option>
          </select>
          <small style="color: #666;">Common goal: 3–6 months</small>
        </div>
        <div>
          <label for="currentSavings" style="display: block; margin-bottom: 5px; font-weight: 600;">Current emergency savings ($)</label>
          <input type="number" id="currentSavings" min="0" step="100" value="2000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div>
          <label for="monthlyContribution" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly contribution ($)</label>
          <input type="number" id="monthlyContribution" min="0" step="25" value="400" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Amount you can add each month</small>
        </div>
        <div>
          <label for="interestRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Savings interest rate (% per year)</label>
          <input type="number" id="interestRate" min="0" max="20" step="0.1" value="4.5" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">e.g. high-yield savings ~4–5%</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your emergency fund plan</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Target amount</div>
          <div id="resultTarget" style="font-size: 24px; font-weight: 800; color: #14532d;"></div>
        </div>
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Months to goal</div>
          <div id="resultMonths" style="font-size: 24px; font-weight: 800; color: #1e3a8a;"></div>
        </div>
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Goal reached by</div>
          <div id="resultDate" style="font-size: 20px; font-weight: 800; color: #78350f;"></div>
        </div>
      </div>
      <div id="progressMessage" style="margin: 16px 0; padding: 12px; background: #f0fdf4; border-radius: 8px; color: #166534;"></div>
      <div class="chart-section" style="margin: 24px 0;">
        <h3>Savings over time</h3>
        <div class="chart-wrapper" style="height: 360px;">
          <canvas id="savingsChart"></canvas>
        </div>
      </div>
      <div class="chart-section" style="margin: 24px 0;">
        <h3>Progress to goal (%)</h3>
        <div class="chart-wrapper" style="height: 280px;">
          <canvas id="progressChart"></canvas>
        </div>
      </div>
      <div class="table-section" style="margin: 24px 0;">
        <h3>Month-by-month (first 24 months)</h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Balance</th>
                <th>Interest</th>
                <th>Contribution</th>
                <th>% of goal</th>
              </tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
      </div>
      <?php $share_title = 'Emergency Fund Calculator'; $share_text = 'Check out the Emergency Fund calculator at ronbelisle.com — plan how long to build your emergency fund.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
    $premium_upsell_headline = 'Unlock Premium Features';
    $premium_upsell_text = 'Upgrade to Premium to save and compare emergency fund scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your specific results.';
    include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
    <footer class="site-footer">
      <span class="donate-text">If these tools are useful, please consider supporting future development.</span>
      <a href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" class="donate-btn">
        <span class="donate-dot"></span>
        Donate
      </a>
    </footer>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/share-results.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>
