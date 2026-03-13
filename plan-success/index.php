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
  <meta name="description" content="Monte Carlo retirement calculator. See the probability your portfolio lasts through retirement with thousands of market simulations.">
  <title>Plan Success (Monte Carlo) | Ron Belisle Financial Calculators</title>
  <?php $og_title = $ld_name = 'Plan Success (Monte Carlo)'; $og_description = $ld_description = 'Monte Carlo retirement calculator. See the probability your portfolio lasts through retirement with thousands of market simulations.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
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
      <h1>Plan Success (Monte Carlo)</h1>
      <p class="sub">See the probability your portfolio lasts through retirement using random market return simulations.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How It Works</h2>
      <p>A <strong>Monte Carlo simulation</strong> runs hundreds or thousands of possible market paths. Each path uses random annual returns (based on your expected return and volatility). Your portfolio is drawn down by your chosen withdrawal each year. The <strong>success rate</strong> is the percentage of those paths where you still have money left at the end. No simulation can predict the real future—this tool shows you the odds under your assumptions.</p>
      <p style="margin-top: 12px;"><strong>Tip:</strong> Use a conservative expected return (e.g. 5–6%) and include volatility (e.g. 10–15% standard deviation) so the success rate reflects sequence-of-returns risk.</p>
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

    <div id="planForm">
      <h3>Your Plan</h3>
      <div id="validationError" role="alert" style="display: none; margin-bottom: 15px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #b91c1c; font-size: 14px;"></div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div class="slider-row">
          <div class="slider-label"><span>Starting Portfolio</span><span class="value" id="portfolioLabel"></span></div>
          <input type="range" id="portfolio" min="50000" max="5000000" step="50000" value="1000000">
          <small style="color: #666;">Today’s portfolio value</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Annual Withdrawal</span><span class="value" id="withdrawalLabel"></span></div>
          <input type="range" id="withdrawal" min="10000" max="500000" step="5000" value="40000">
          <small style="color: #666;">First-year withdrawal. Optionally grow with inflation below.</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Inflation Rate for Withdrawals (%)</span><span class="value" id="inflationRateLabel"></span></div>
          <input type="range" id="inflationRate" min="0" max="10" step="0.1" value="0">
          <small style="color: #666;">0–10%. Set to 0 for flat withdrawals. Typical U.S. ~3%.</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Years to Model</span><span class="value" id="yearsLabel"></span></div>
          <input type="range" id="years" min="5" max="50" step="1" value="30">
          <small style="color: #666;">e.g. 30 for a 30-year retirement</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Expected Annual Return (%)</span><span class="value" id="expectedReturnLabel"></span></div>
          <input type="range" id="expectedReturn" min="0" max="20" step="0.25" value="6">
          <small style="color: #666;">Long-term average return assumption</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Volatility / Std Dev (%)</span><span class="value" id="volatilityLabel"></span></div>
          <input type="range" id="volatility" min="0" max="50" step="0.5" value="12">
          <small style="color: #666;">Typical stock portfolio: ~10–15%</small>
        </div>
        <div class="slider-row">
          <div class="slider-label"><span>Number of Simulations</span><span class="value" id="simulationsLabel"></span></div>
          <input type="range" id="simulations" min="100" max="10000" step="100" value="1000">
          <small style="color: #666;">More = smoother result, slower run</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="button" id="runMonteCarloBtn" class="button" style="font-size: 1.1em; padding: 12px 30px;">Run Monte Carlo</button>
      </div>
    </div>

    <div id="results" class="results-container" style="display: none;">
      <h2>Monte Carlo Results</h2>

      <div class="info-box info-box-blue" id="summaryBox" style="margin-bottom: 25px;"></div>

      <div class="chart-section">
        <h3>Ending Portfolio Distribution</h3>
        <p style="color: #666; margin-bottom: 10px;">Vertical axis = ending portfolio (dollar outcome). Horizontal axis = how many simulations landed in that range. Negative = ran out of money.</p>
        <div class="chart-wrapper" style="height: 320px;">
          <canvas id="distributionChart"></canvas>
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
        <p style="margin: 0; color: #78350f; line-height: 1.6;">This tool is for education only. Past performance and Monte Carlo results do not guarantee future outcomes. Returns and volatility are uncertain. Consider consulting a financial professional.</p>
      </div>
      <?php $share_title = 'Plan Success (Monte Carlo) Calculator'; $share_text = 'Check out the Plan Success Monte Carlo calculator at ronbelisle.com — see the probability your plan will last through retirement.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDF and CSV, and get AI-generated plain-language explanations of your specific results.'; include(__DIR__ . '/../includes/premium-upsell-banner.php'); ?>
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
