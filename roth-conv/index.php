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
            <p>Converting traditional IRA funds to a Roth IRA means paying taxes now on the converted amount, but all future growth and withdrawals will be tax-free. This calculator helps you analyze whether converting makes sense by comparing the tax cost today versus the tax savings in retirement, while considering Required Minimum Distributions (RMDs), Medicare IRMAA surcharges (2-year lookback), the 3.8% Net Investment Income Tax (NIIT), and the opportunity cost of paying taxes early (discount rate).</p>
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
                <div id="spouseAgeWrap" style="display: none;">
                    <label for="spouseAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Spouse Age</label>
                    <input type="number" id="spouseAge" value="" min="18" max="120" style="width: 100%;">
                    <small style="color: #666;">Used to compute the extra 65+ standard deduction for joint returns.</small>
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
                    <input type="number" id="annualPortfolioWithdrawalRate" value="0" min="0" max="20" step="0.01" style="width: 100%;">
                    <small style="color: #666;">Percent of your total portfolio (Traditional + Roth) you plan to withdraw each year for spending. Traditional withdrawals are taxable; Roth is tax‑free. RMDs still happen separately.</small>
                </div>
                <div>
                    <label for="withdrawalMode" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawal Mode</label>
                    <select id="withdrawalMode" style="width: 100%;">
                        <option value="rate" selected>Use a fixed withdrawal %</option>
                        <option value="target_after_tax">Solve withdrawals for target after‑tax spending</option>
                    </select>
                    <small style="color: #666;">If you choose “target after‑tax,” the calculator will increase withdrawals as needed to cover conversion/RMD taxes while maintaining your spending target.</small>
                </div>
                <div id="targetSpendingWrap" style="display: none;">
                    <label for="targetAfterTaxSpending" style="display: block; margin-bottom: 5px; font-weight: 600;">Target Annual Spending (after tax) ($)</label>
                    <input type="number" id="targetAfterTaxSpending" value="110000" min="0" step="1000" style="width: 100%;">
                    <small style="color: #666;">Target annual spending after federal income taxes. The model will solve for the portfolio withdrawals needed to hit this target.</small>
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

            <h3 style="margin-top: 30px;">Medicare IRMAA</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" id="includeIrmaa" checked>
                        Include Medicare IRMAA surcharges
                    </label>
                    <small style="color: #666; display: block; margin-top: 6px;">Adds Part B &amp; Part D income-related premium surcharges to all-in tax cost. Uses a 2-year income lookback (same as Social Security / Medicare).</small>
                </div>
                <div>
                    <label for="medicareStartAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Medicare Enrollment Age</label>
                    <input type="number" id="medicareStartAge" value="65" min="62" max="70" required style="width: 100%;">
                    <small style="color: #666;">Age when you (and spouse, if married) enroll in Medicare Part B. IRMAA applies once enrolled.</small>
                </div>
                <div>
                    <label for="taxExemptInterest" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Tax-Exempt Interest ($)</label>
                    <input type="number" id="taxExemptInterest" value="0" min="0" step="100" style="width: 100%;">
                    <small style="color: #666;">Municipal bond interest (Form 1040 line 2a). Added to gross income for MAGI / IRMAA thresholds.</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Net Investment Income Tax (NIIT)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" id="includeNiit" checked>
                        Include NIIT (3.8% surtax)
                    </label>
                    <small style="color: #666; display: block; margin-top: 6px;">Applies when MAGI exceeds $200k (single/HOH), $250k (MFJ), or $125k (MFS). Tax is 3.8% on the lesser of net investment income or MAGI above the threshold.</small>
                </div>
                <div>
                    <label for="investmentIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Investment Income ($)</label>
                    <input type="number" id="investmentIncome" value="15000" min="0" step="500" style="width: 100%;">
                    <small style="color: #666;">Dividends, taxable interest, capital gains, and other net investment income (not wages, pensions, or RMDs).</small>
                </div>
                <div>
                    <label for="retirementInvestmentIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Retirement Investment Income ($) — Optional</label>
                    <input type="number" id="retirementInvestmentIncome" value="" min="0" step="500" placeholder="Same as above if blank" style="width: 100%;">
                    <small style="color: #666;">Investment income after retirement if different from pre-retirement. Leave blank to use the same amount every year.</small>
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
                <div>
                    <label for="discountRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Tax Discount Rate (%) — Optional</label>
                    <input type="number" id="discountRate" value="0" min="0" max="15" step="0.1" style="width: 100%;">
                    <small style="color: #666;">Opportunity cost of paying taxes now vs. later. Tax dollars paid today could stay invested (e.g., in an index fund). A higher rate makes future tax savings worth less in today&rsquo;s dollars. Try 3&ndash;7% to match expected portfolio returns. Leave at 0 for nominal (undiscounted) totals only.</small>
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
    <script src="../js/explain-results-modal.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/calculator.js')); ?>"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>