<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
// Don't close PHP yet - keep variables in scope
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Model Roth conversions from traditional IRA. See tax cost, RMD impact, break-even age, and lifetime tax savings.">
    <title>Roth Conversion Calculator</title>
    <?php $og_title = $ld_name = 'Roth Conversion Calculator'; $og_description = $ld_description = 'Model Roth conversions from traditional IRA. See tax cost, RMD impact, break-even age, and lifetime tax savings.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Roth Conversion Calculator</h1>
            <p class="sub">Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Roth Conversions</h2>
            <p>Converting traditional IRA funds to a Roth IRA means paying taxes now on the converted amount, but all future growth and withdrawals will be tax-free. This calculator helps you analyze whether converting makes sense by comparing the tax cost today versus the tax savings in retirement, while considering Required Minimum Distributions (RMDs) and Medicare IRMAA surcharges.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
        <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with chart and year-by-year table (PDF)">📄 Download PDF</button>
        <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year data for Excel or spreadsheets">📊 Export CSV</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with chart. <strong>CSV</strong> — Spreadsheet data. <strong>Explain</strong> — AI explains your results in plain language.
    </p>
</div>
<?php endif; ?>

        <form id="rothForm">
            <h3>Personal Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Age</label>
                    <input type="number" id="currentAge" value="60" min="18" max="100" required style="width: 100%;">
                </div>
                <div>
                    <label for="retirementAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Retirement Age (leave blank if already retired)</label>
                    <input type="number" id="retirementAge" value="" placeholder="Optional" style="width: 100%;">
                    <small style="color: #666;">Leave blank if you're already retired</small>
                </div>
                <div>
                    <label for="lifeExpectancy" style="display: block; margin-bottom: 5px; font-weight: 600;">Life Expectancy</label>
                    <input type="number" id="lifeExpectancy" value="90" min="60" max="120" required style="width: 100%;">
                </div>
                <div>
                    <label for="filingStatus" style="display: block; margin-bottom: 5px; font-weight: 600;">Filing Status</label>
                    <select id="filingStatus" required style="width: 100%;">
                        <option value="single">Single</option>
                        <option value="married" selected>Married Filing Jointly</option>
                        <option value="married_separate">Married Filing Separately</option>
                        <option value="head">Head of Household</option>
                    </select>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Financial Situation</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="traditionalIRA" style="display: block; margin-bottom: 5px; font-weight: 600;">Traditional IRA/401(k) Balance ($)</label>
                    <input type="number" id="traditionalIRA" value="500000" min="0" step="1000" required style="width: 100%;">
                    <small style="color: #666;">Current balance in traditional retirement accounts</small>
                </div>
                <div>
                    <label for="rothIRA" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Roth IRA Balance ($)</label>
                    <input type="number" id="rothIRA" value="50000" min="0" step="1000" required style="width: 100%;">
                    <small style="color: #666;">Current balance in Roth accounts</small>
                </div>
                <div>
    <label for="currentIncome" id="currentIncomeLabel" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Annual Gross Income ($)</label>
    <input type="number" id="currentIncome" value="80000" min="0" step="1000" required style="width: 100%;">
    <small id="currentIncomeHelp" style="color: #666;">Wages, pensions, etc. (before standard deduction)</small>
</div>
                <div>
                    <label for="retirementIncome" id="retirementIncomeLabel" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Retirement Income ($)</label>
                    <input type="number" id="retirementIncome" value="40000" min="0" step="1000" required style="width: 100%;">
                    <small id="retirementIncomeHelp" style="color: #666;">Annual income excluding RMDs</small>
                </div>
            </div>

            <h3 style="margin-top: 18px;">Spending from your portfolio (optional)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="annualPortfolioWithdrawalRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Portfolio Withdrawal (%)</label>
                    <input type="number" id="annualPortfolioWithdrawalRate" value="0" min="0" max="20" step="0.1" style="width: 100%;">
                    <small style="color: #666;">Percent of your total portfolio (Traditional + Roth) you plan to withdraw each year for spending. Traditional withdrawals are taxable; Roth is tax‑free. RMDs still happen separately.</small>
                </div>
                <div>
                    <label for="withdrawalOrder" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawal Order</label>
                    <select id="withdrawalOrder" style="width: 100%;">
                        <option value="traditional_then_roth" selected>Traditional first, then Roth</option>
                        <option value="roth_then_traditional">Roth first, then Traditional</option>
                    </select>
                    <small style="color: #666;">Traditional withdrawals are taxed as income; Roth withdrawals are tax‑free.</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Conversion Strategy</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="conversionAmount" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Conversion Amount ($)</label>
                    <input type="number" id="conversionAmount" value="50000" min="0" step="1000" required style="width: 100%;">
                    <small style="color: #666;">Amount to convert each year</small>
                </div>
                <div>
                    <label for="conversionYears" style="display: block; margin-bottom: 5px; font-weight: 600;">Number of Years to Convert</label>
                    <input type="number" id="conversionYears" value="5" min="1" max="30" required style="width: 100%;">
                    <small style="color: #666;">How many years to spread conversions</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="returnRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Investment Return (%)</label>
                    <input type="number" id="returnRate" value="7" min="0" max="20" step="0.1" required style="width: 100%;">
                    <small style="color: #666;">Expected portfolio growth rate</small>
                </div>
                <div>
                    <label for="inflationRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Inflation Rate (%)</label>
                    <input type="number" id="inflationRate" value="2.5" min="0" max="10" step="0.1" required style="width: 100%;">
                    <small style="color: #666;">For adjusting brackets over time</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Conversion Analysis</h2>
            <div id="resultsContent"></div>
            <?php if ($isPremium): ?>
            <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
            </div>
            <?php endif; ?>
            <?php $share_title = 'Roth Conversion Calculator'; $share_text = 'Check out the Roth Conversion calculator at ronbelisle.com — analyze when and how much to convert.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Unlock Premium Features';
        $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDF and CSV reports, get AI-generated plain-language explanations of your specific results, and access your conversion analysis across devices.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/calculator.js')); ?>"></script>
</body>
</html>