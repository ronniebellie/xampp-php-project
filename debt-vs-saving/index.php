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
  <meta name="description" content="Debt vs saving: which comes first? Compare paying off debt vs building emergency fund with different interest rate scenarios.">
  <title>Debt vs Saving: Which First?</title>
  <?php $og_title = $ld_name = 'Debt vs Saving: Which First?'; $og_description = $ld_description = 'Debt vs saving: which comes first? Compare paying off debt vs building emergency fund with different interest rate scenarios.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
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
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>Debt vs Saving: Which First?</h1>
      <p class="sub">
        You have extra money each month. Should you throw it at debt, or invest it for retirement?
        This tool compares both paths side‑by‑side over a time horizon.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Enter one representative debt (balance, rate, and minimum payment), an amount you could put toward that debt
        or invest each month, and an expected investment return and time horizon. The calculator runs two simple
        scenarios:
      </p>
      <ul style="margin-top: 8px; padding-left: 22px; line-height: 1.7;">
        <li><strong>Invest Extra</strong> – you pay only the minimum on the debt and invest the extra each month.</li>
        <li><strong>Pay Debt First</strong> – you use the extra to pay down the debt faster; once it’s gone, you invest what you had been paying.</li>
      </ul>
      <p style="margin-top: 8px;">
        At the end of the horizon, we compare approximate net worth (investments minus any remaining debt) under each
        strategy to show which one wins on these assumptions.
      </p>
    </div>

    <form id="debtVsSavingForm">
      <h3>Your situation</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div class="slider-row">
          <div class="slider-label">
            <span>Debt balance</span>
            <span class="value" id="debtBalanceLabel"></span>
          </div>
          <input
            type="range"
            id="debtBalance"
            min="0"
            max="50000"
            step="500"
            value="20000"
          >
          <small style="color: #666;">Use your highest‑interest or most representative debt.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Debt interest rate (% per year)</span>
            <span class="value" id="debtRateLabel"></span>
          </div>
          <input
            type="range"
            id="debtRate"
            min="0"
            max="40"
            step="0.25"
            value="18"
          >
          <small style="color: #666;">Credit cards are often 15–25%.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Minimum monthly payment</span>
            <span class="value" id="minPaymentLabel"></span>
          </div>
          <input
            type="range"
            id="minPayment"
            min="0"
            max="2000"
            step="25"
            value="400"
          >
          <small style="color: #666;">Roughly what you must pay toward this debt each month.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Extra you could put toward debt or investing ($/month)</span>
            <span class="value" id="extraPerMonthLabel"></span>
          </div>
          <input
            type="range"
            id="extraPerMonth"
            min="0"
            max="2000"
            step="25"
            value="300"
          >
          <small style="color: #666;">This is the lever we’ll send either to debt or to investments.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Expected investment return (% per year)</span>
            <span class="value" id="investReturnLabel"></span>
          </div>
          <input
            type="range"
            id="investReturn"
            min="0"
            max="20"
            step="0.5"
            value="7"
          >
          <small style="color: #666;">Long‑term stock‑heavy portfolios are often modeled at 6–8%.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Time horizon (years)</span>
            <span class="value" id="horizonYearsLabel"></span>
          </div>
          <input
            type="range"
            id="horizonYears"
            min="1"
            max="40"
            step="1"
            value="10"
          >
          <small style="color: #666;">How long you’ll keep this plan going.</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          Compare strategies
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Debt vs saving comparison</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Invest Extra</div>
          <div id="investExtraHeadline" style="font-size: 18px; font-weight: 700; color: #1e3a8a; margin-top: 4px;"></div>
          <div id="investExtraDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Pay Debt First</div>
          <div id="payDebtHeadline" style="font-size: 18px; font-weight: 700; color: #14532d; margin-top: 4px;"></div>
          <div id="payDebtDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>

        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Which wins (on these assumptions)?</div>
          <div id="winnerHeadline" style="font-size: 18px; font-weight: 700; color: #78350f; margin-top: 4px;"></div>
          <div id="winnerDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>
      </div>

      <div class="info-box-blue" style="margin-top: 12px;">
        <h3 style="margin-top: 0;">How to read this</h3>
        <p id="explanationText">
          Results will appear here after you compare strategies. This is a simplified model with steady payments and
          returns—it’s meant to give directional guidance, not precise forecasts.
        </p>
      </div>

      <?php
        $share_title = 'Debt vs Saving: Which First?';
        $share_text  = 'Try this Debt vs Saving comparison at ronbelisle.com — see whether it might make more sense to pay debt down first or invest extra money for retirement.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Save and compare payoff vs investing plans';
      $premium_upsell_text = 'Upgrade to Premium to save multiple debt vs saving scenarios, export PDFs and CSVs, get AI-generated plain-language explanations of your specific results, and compare them with your other calculators.';
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

  <script src="../js/share-results.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>

