<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isPremium = false;
if ($isLoggedIn) {
    require_once '../includes/db_config.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sub = null;
    $stmt->bind_result($sub);
    $user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
    $stmt->close();
    $isPremium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Down Payment / House Savings Calculator</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>Down Payment / House Savings</h1>
      <p class="sub">See how much to save each month to reach your down payment goal and when you'll get there.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Why plan your down payment?</h2>
      <p>A larger down payment can lower your monthly payment, help you avoid PMI (private mortgage insurance), and improve your chances of approval. Many buyers aim for <strong>10–20%</strong> of the purchase price—or more for better rates. This tool projects how long it will take to reach your target based on your current savings and monthly contribution.</p>
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

    <form id="dpForm">
      <h3>Your down payment goal</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="targetAmount" style="display: block; margin-bottom: 5px; font-weight: 600;">Target down payment ($)</label>
          <input type="number" id="targetAmount" min="1000" step="1000" value="60000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Amount you want to save for a down payment</small>
        </div>
        <div>
          <label for="housePrice" style="display: block; margin-bottom: 5px; font-weight: 600;">Optional: House price ($)</label>
          <input type="number" id="housePrice" min="0" step="5000" value="0" placeholder="e.g. 300000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Leave 0 to use target amount only</small>
        </div>
        <div>
          <label for="downPct" style="display: block; margin-bottom: 5px; font-weight: 600;">Down payment %</label>
          <input type="number" id="downPct" min="0" max="100" step="1" value="20" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">If house price set, target = price × this %</small>
        </div>
        <div>
          <label for="currentSavings" style="display: block; margin-bottom: 5px; font-weight: 600;">Current savings ($)</label>
          <input type="number" id="currentSavings" min="0" step="500" value="10000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div>
          <label for="monthlyContribution" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly contribution ($)</label>
          <input type="number" id="monthlyContribution" min="0" step="25" value="800" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
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
      <h2>Your down payment plan</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Target down payment</div>
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
    </div>

    <?php if (!$isPremium): ?>
    <?php include('../includes/premium-upsell-banner.php'); ?>
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
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>
