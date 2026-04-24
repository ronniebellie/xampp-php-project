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
  <meta name="description" content="Bridge the gap between retirement and Social Security. See how much to save to cover spending until benefits start.">
  <title>Social Security + Spending Gap Calculator</title>
  <?php $og_title = $ld_name = 'Social Security + Spending Gap Calculator'; $og_description = $ld_description = 'Bridge the gap between retirement and Social Security. See how much to save to cover spending until benefits start.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
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

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Social Security + Spending Gap Calculator</h1>
            <p class="sub">See how Social Security reduces the portfolio you need by identifying your real retirement spending gap</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Your Spending Gap</h2>
            <p>Many retirees overestimate how much they need to save because they forget that Social Security will cover a significant portion of their spending. This calculator shows your actual "spending gap" - the difference between what you want to spend and what Social Security provides - and calculates the portfolio size needed to fill that gap using sustainable withdrawal rates.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
        <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with charts (PDF)">📄 Download PDF</button>
        <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year data for Excel or spreadsheets">📊 Export CSV</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data. <strong>Explain</strong> — AI explains your results in plain language.
    </p>
</div>
<?php endif; ?>

        <form id="gapForm">
            <h3>Your Retirement Income & Expenses</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div class="slider-row">
                    <div class="slider-label"><span>Target Monthly Spending</span><span class="value" id="targetSpendingLabel"></span></div>
                    <input type="range" id="targetSpending" min="3000" max="15000" step="100" value="8000">
                    <small style="color: #666;">Your desired monthly retirement budget</small>
                </div>
                <div class="slider-row">
                    <div class="slider-label"><span>Social Security Monthly Income</span><span class="value" id="ssIncomeLabel"></span></div>
                    <input type="range" id="ssIncome" min="0" max="6000" step="100" value="3500">
                    <small style="color: #666;">Combined household Social Security benefits</small>
                </div>
                <div class="slider-row">
                    <div class="slider-label"><span>Other Monthly Income</span><span class="value" id="otherIncomeLabel"></span></div>
                    <input type="range" id="otherIncome" min="0" max="4000" step="100" value="0">
                    <small style="color: #666;">Pension, rental income, part-time work, etc.</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Withdrawal Rate Assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div class="slider-row">
                    <div class="slider-label"><span>Starting Withdrawal Rate</span><span class="value" id="withdrawalRateLabel"></span></div>
                    <input type="range" id="withdrawalRate" min="2.5" max="6.0" step="0.1" value="4.0">
                    <small style="color: #666;">Common range: 3.5% - 5.0%</small>
                </div>
                <div>
                    <label for="filingStatus" style="display: block; margin-bottom: 5px; font-weight: 600;">Household Type</label>
                    <select id="filingStatus" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <option value="single">Single</option>
                        <option value="married" selected>Married</option>
                    </select>
                    <small style="color: #666;">For context in results</small>
                </div>
            </div>

            <div style="margin: 18px 0 8px 0;">
                <small style="color: #666;">Note: This calculator updates instantly as you move the sliders.</small>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Spending Gap Analysis</h2>
            
            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Portfolio Needed at Different Withdrawal Rates</h3>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="withdrawalChart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3>Annual Withdrawal Amounts by Rate</h3>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="annualWithdrawalChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <div class="table-section">
                <h3>Withdrawal Rate Comparison</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Withdrawal Rate</th>
                                <th>Portfolio Needed</th>
                                <th>Annual Withdrawal</th>
                                <th>Monthly Withdrawal</th>
                                <th>Success Rate*</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
                <p style="font-size: 0.9em; color: #666; margin-top: 10px;">*Historical success rate over 30 years based on historical data (approximate)</p>
            </div>

            <?php if ($isPremium): ?>
            <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
            </div>
            <?php endif; ?>
            <?php $share_title = 'Spending Gap (SS Gap) Calculator'; $share_text = 'Check out the Spending Gap calculator at ronbelisle.com — see how much portfolio you need to fill the gap between Social Security and expenses.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Unlock Premium Features';
        $premium_upsell_text = 'Upgrade to Premium to save and compare Social Security gap scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your specific results.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script src="../js/compare-scenarios-modal.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js"></script>
</body>
</html>