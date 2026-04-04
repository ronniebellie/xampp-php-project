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
  <meta name="description" content="Explore the power of compound interest. Adjust starting amount, return, years, and monthly contributions to see how your money can grow.">
  <title>The Power of Compound Interest</title>
  <?php
    $og_title = $ld_name = 'The Power of Compound Interest';
    $og_description = $ld_description = 'Explore the power of compound interest. Adjust starting amount, return, years, and monthly contributions to see how your money can grow.';
    include(__DIR__ . '/../includes/og-twitter-meta.php');
    include(__DIR__ . '/../includes/json-ld-softwareapp.php');
  ?>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .slider-row {
      margin-bottom: 18px;
    }
    .slider-label {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 4px;
      font-weight: 600;
      font-size: 14px;
    }
    .slider-label span.value {
      font-weight: 500;
      color: #4b5563;
      font-size: 13px;
    }
    input[type="range"] {
      width: 100%;
      margin: 0;
      -webkit-appearance: none;
      background: transparent;
    }
    input[type="range"]::-webkit-slider-runnable-track {
      height: 6px;
      background: #e5e7eb;
      border-radius: 999px;
    }
    input[type="range"]::-moz-range-track {
      height: 6px;
      background: #e5e7eb;
      border-radius: 999px;
    }
    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      background: #1d4ed8;
      border: 2px solid #ffffff;
      box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.5), 0 6px 12px rgba(15, 23, 42, 0.15);
      margin-top: -6px;
    }
    input[type="range"]::-moz-range-thumb {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      background: #1d4ed8;
      border: 2px solid #ffffff;
      box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.5), 0 6px 12px rgba(15, 23, 42, 0.15);
    }
    .summary-pill-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      margin: 20px 0 10px;
    }
    .summary-pill {
      padding: 14px 16px;
      border-radius: 12px;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
    }
    .summary-pill .label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #6b7280;
      margin-bottom: 4px;
      font-weight: 600;
    }
    .summary-pill .value {
      font-size: 22px;
      font-weight: 800;
      color: #111827;
    }
    .summary-pill.interest .value {
      color: #15803d;
    }
    .summary-pill.final .value {
      color: #1d4ed8;
    }
    .summary-pill.invested .value {
      color: #92400e;
    }
  </style>
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>The Power of Compound Interest</h1>
      <p class="sub">Drag the sliders to see how your starting amount, time in the market, return, and monthly contributions change your long‑term results.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 26px;">
      <h2>See how growth accelerates over time</h2>
      <p>Compound interest means you earn returns not just on what you put in, but also on the growth that’s already there. Over long periods, this creates a curve that bends upward—especially when you combine time with steady monthly contributions.</p>
    </div>

    <section aria-label="Compound interest sliders">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
        <div class="slider-row">
          <div class="slider-label">
            <span>Initial investment</span>
            <span class="value" id="initialLabel"></span>
          </div>
          <input type="range" id="initial" min="0" max="100000" step="1000" value="10000">
        </div>
        <div class="slider-row">
          <div class="slider-label">
            <span>Annual return</span>
            <span class="value" id="returnLabel"></span>
          </div>
          <input type="range" id="rate" min="0" max="15" step="0.25" value="7">
        </div>
        <div class="slider-row">
          <div class="slider-label">
            <span>Years</span>
            <span class="value" id="yearsLabel"></span>
          </div>
          <input type="range" id="years" min="1" max="40" step="1" value="30">
        </div>
        <div class="slider-row">
          <div class="slider-label">
            <span>Monthly contributions</span>
            <span class="value" id="monthlyLabel"></span>
          </div>
          <input type="range" id="monthly" min="0" max="3000" step="50" value="0">
        </div>
      </div>
    </section>

    <div id="compoundResults" style="display: none;">
      <section aria-label="Summary of results">
        <div class="summary-pill-grid">
          <div class="summary-pill final">
            <div class="label">Final balance</div>
            <div class="value" id="finalBalance"></div>
          </div>
          <div class="summary-pill invested">
            <div class="label">Total invested</div>
            <div class="value" id="totalInvested"></div>
          </div>
          <div class="summary-pill interest">
            <div class="label">Interest earned</div>
            <div class="value" id="interestEarned"></div>
          </div>
        </div>
      </section>

      <div class="chart-section" style="margin-top: 26px;">
        <h3>Growth of your money over time</h3>
        <div class="chart-wrapper" style="height: 320px;">
          <canvas id="compoundChart"></canvas>
        </div>
      </div>
    </div>

    <?php
      $share_title = 'The Power of Compound Interest';
      $share_text = 'Check out this compound interest explorer at ronbelisle.com — see how time and contributions grow your money.';
      include(__DIR__ . '/../includes/share-results-block.php');
    ?>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Unlock Premium Features';
      $premium_upsell_text = 'Upgrade to Premium to save and compare compound interest scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your specific results.';
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

