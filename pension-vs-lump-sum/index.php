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
    <meta name="description" content="Compare pension vs lump sum. See break-even age and how many years of payments match the lump sum if invested.">
    <title>Pension vs. Lump Sum Calculator</title>
    <?php $og_title = $ld_name = 'Pension vs. Lump Sum Calculator'; $og_description = $ld_description = 'Compare pension vs lump sum. See break-even age and how many years of payments match the lump sum if invested.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
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
    <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

    <header>
      <h1>Pension vs. Lump Sum Calculator</h1>
      <p class="sub">See how many years it takes for the pension to “pay back” the lump sum, and how your life expectancy affects the choice.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Understanding the Choice</h2>
      <p>Many employers offer a choice: <strong>take a monthly pension for life</strong> or <strong>take a lump sum</strong> and invest or spend it. This calculator compares the two by showing how many years of pension payments it takes to match what the lump sum would grow to if invested at an assumed rate. If you live past that “break-even” age, the pension typically comes out ahead; if you don’t, the lump sum (or what’s left of it) may be worth more to you or your heirs.</p>
      <p style="margin-top: 12px;"><strong>Tip:</strong> Use your plan’s official lump sum and monthly pension numbers. The growth rate should reflect how you’d invest the lump sum (e.g. 4–6% for a balanced portfolio).</p>
    </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
  <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
  <p style="margin: 0 0 12px 0; font-size: 14px; color: #22543d;">Save / Load — Store and recall scenarios. <strong>Explain</strong> — AI explains your results in plain language.</p>
  <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
    <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
    <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
    <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
  </div>
</div>
<?php endif; ?>

    <section aria-label="Pension vs lump sum inputs">
      <h3>Your Numbers</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
        <div class="slider-row">
          <div class="slider-label"><span>Monthly Pension ($)</span><span class="value" id="monthlyPensionLabel"></span></div>
          <input type="range" id="monthlyPension" min="500" max="5000" step="100" value="2500">
          <small style="color: #666;">Before tax; single-life or joint-life amount</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Lump Sum Offered ($)</span><span class="value" id="lumpSumLabel"></span></div>
          <input type="range" id="lumpSum" min="100000" max="1000000" step="10000" value="500000">
          <small style="color: #666;">One-time amount if you give up the pension</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Your Current Age</span><span class="value" id="currentAgeLabel"></span></div>
          <input type="range" id="currentAge" min="50" max="95" step="1" value="65">
          <small style="color: #666;">Age when pension or lump sum starts</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Assumed Growth Rate on Lump Sum (%)</span><span class="value" id="growthRateLabel"></span></div>
          <input type="range" id="growthRate" min="0" max="15" step="0.25" value="5">
          <small style="color: #666;">Annual return if you invest the lump sum</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Plan To Age (Life Expectancy)</span><span class="value" id="lifeExpectancyLabel"></span></div>
          <input type="range" id="lifeExpectancy" min="70" max="105" step="1" value="90">
          <small style="color: #666;">Used to show total received by end of plan</small>
        </div>
      </div>
    </section>

    <div id="results" class="results-container">
      <h2>Pension vs. Lump Sum Comparison</h2>

      <div class="info-box info-box-blue" id="summaryBox" style="margin-bottom: 25px;"></div>

      <div class="chart-section">
        <h3>Pension vs. Lump Sum Over Time</h3>
        <p style="color: #666; margin-bottom: 10px;">Cumulative pension received vs. what the lump sum would grow to if invested.</p>
        <div class="chart-wrapper" style="height: 340px;">
          <canvas id="comparisonChart"></canvas>
        </div>
      </div>

      <div class="table-section">
        <h3>Year-by-Year Comparison</h3>
        <div class="table-wrapper">
          <table class="data-table" id="resultsTable">
            <thead>
              <tr>
                <th>Year</th>
                <th>Age</th>
                <th>Cumulative Pension Received</th>
                <th>Lump Sum If Invested (FV)</th>
              </tr>
            </thead>
            <tbody id="resultsBody"></tbody>
          </table>
        </div>
      </div>

      <?php if ($isPremium): ?>
      <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
        <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
        <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
      </div>
      <?php endif; ?>

      <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
        <h3 style="color: #92400e; margin-top: 0;">Disclaimer</h3>
        <p style="margin: 0; color: #78350f; line-height: 1.6;">This tool is for educational purposes only and does not constitute financial or tax advice. Pension and lump sum amounts vary by plan and assumptions. Investment returns are uncertain. Consider inflation, taxes, and your health and family situation. Consult a qualified professional before making this decision.</p>
      </div>
      <?php $share_title = 'Pension vs. Lump Sum Calculator'; $share_text = 'Check out the Pension vs. Lump Sum Calculator at ronbelisle.com — compare pension income to lump sum growth over time.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
    $premium_upsell_headline = 'Unlock Premium Features';
    $premium_upsell_text = 'Upgrade to Premium to save and compare pension vs. lump sum scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your specific results.';
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
  <script>
  const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
  </script>
  <script src="calculator.js"></script>
</body>
</html>
