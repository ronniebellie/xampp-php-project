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
  <meta name="description" content="Save for a down payment. Calculate monthly savings needed, timeline to reach your target, and impact of interest on your house fund.">
  <title>Down Payment / House Savings Calculator</title>
  <?php $og_title = $ld_name = 'Down Payment / House Savings Calculator'; $og_description = $ld_description = 'Save for a down payment. Calculate monthly savings needed, timeline to reach your target, and impact of interest on your house fund.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .slider-row { margin-bottom: 18px; }
    .slider-label { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; font-weight: 600; font-size: 14px; }
    .slider-label span.value { font-weight: 500; color: #4b5563; font-size: 13px; }
    input[type="range"] { width: 100%; margin: 0; -webkit-appearance: none; background: transparent; }
    input[type="range"]::-webkit-slider-runnable-track { height: 6px; background: #e5e7eb; border-radius: 999px; }
    input[type="range"]::-moz-range-track { height: 6px; background: #e5e7eb; border-radius: 999px; }
    input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 999px; background: #1d4ed8; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(37,99,235,.5), 0 6px 12px rgba(15,23,42,.15); margin-top: -6px; }
    input[type="range"]::-moz-range-thumb { width: 18px; height: 18px; border-radius: 999px; background: #1d4ed8; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(37,99,235,.5), 0 6px 12px rgba(15,23,42,.15); }
  </style>
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

    <section aria-label="Down payment inputs">
      <h3>Your down payment goal</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
        <div class="slider-row">
          <div class="slider-label"><span>House price (optional)</span><span class="value" id="housePriceLabel"></span></div>
          <input type="range" id="housePrice" min="0" max="600000" step="10000" value="0">
          <small style="color: #666;">Set to 0 to use target amount below; otherwise target = price × down %</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Down payment %</span><span class="value" id="downPctLabel"></span></div>
          <input type="range" id="downPct" min="5" max="30" step="1" value="20">
          <small style="color: #666;">Used when house price &gt; 0</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Target down payment</span><span class="value" id="targetAmountLabel"></span></div>
          <input type="range" id="targetAmount" min="10000" max="200000" step="1000" value="60000">
          <small style="color: #666;">Used when house price is 0; otherwise computed from price × down %</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Current savings</span><span class="value" id="currentSavingsLabel"></span></div>
          <input type="range" id="currentSavings" min="0" max="100000" step="1000" value="10000">
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Monthly contribution</span><span class="value" id="monthlyContributionLabel"></span></div>
          <input type="range" id="monthlyContribution" min="0" max="3000" step="50" value="800">
          <small style="color: #666;">Amount you can add each month</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Savings interest rate (% per year)</span><span class="value" id="interestRateLabel"></span></div>
          <input type="range" id="interestRate" min="0" max="10" step="0.1" value="4.5">
          <small style="color: #666;">e.g. high-yield savings ~4–5%</small>
        </div>
      </div>
    </section>

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
      <?php $share_title = 'Down Payment Calculator'; $share_text = 'Check out the Down Payment calculator at ronbelisle.com — plan your path to a down payment.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
    $premium_upsell_headline = 'Unlock Premium Features';
    $premium_upsell_text = 'Upgrade to Premium to save and compare down payment scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your specific results.';
    include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/share-results.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>
