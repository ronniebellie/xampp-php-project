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
    <?php include('../includes/analytics.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Compare managed portfolio fees to Vanguard index funds. See how advisory fees and expense ratios add up over time.">
    <title>Managed Portfolio vs Vanguard Index Fund</title>
    <?php $og_title = $ld_name = 'Managed Portfolio vs Vanguard Index Fund'; $og_description = $ld_description = 'Compare managed portfolio fees to Vanguard index funds. See how advisory fees and expense ratios add up over time.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Managed Portfolio vs Vanguard Index Fund</h1>
            <p class="subtitle">See the true cost of advisor fees - including opportunity cost</p>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e2e8f0;">
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Vanguard Index Funds</h2>
            <p>A Vanguard index fund is an investment fund that matches the performance of the entire stock market (like the S&P 500) rather than trying to beat it. Because it doesn't require expensive managers picking stocks, index funds have very low fees—typically around 0.04% compared to 1% for managed portfolios.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
        <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with charts and comparison (PDF)">📄 Download PDF</button>
        <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year data for Excel or spreadsheets">📊 Export CSV</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data. <strong>Explain</strong> — AI explains your results in plain language.
    </p>
</div>
<?php endif; ?>

        <div class="calculator-wrapper">
            <!-- Input Section -->
            <div class="input-section">
                <h2>Your Portfolio Details</h2>
                
                <div class="input-group">
                    <div class="slider-label">
                        <span>Current Portfolio Value</span>
                        <span class="value" id="portfolioValueLabel"></span>
                    </div>
                    <input type="range" id="portfolioValue" value="500000" min="50000" max="5000000" step="10000">
                </div>

                <div class="input-group">
                    <label for="advisorFee">Advisor Fee (%)</label>
                    <input type="range" id="advisorFee" value="1.0" min="0" max="5" step="0.05">
                    <div class="slider-label" style="margin-top: 4px; justify-content: flex-end;">
                        <span class="value" id="advisorFeeLabel" style="font-weight: 500; color: #4b5563;"></span>
                    </div>
                    <span class="help-text">Typical managed portfolio fee: 1.0%</span>
                </div>

                <div class="input-group">
    <label for="vanguardFee">Vanguard Index Fund Expense Ratio (%)</label>
    <input type="number" id="vanguardFee" value="0.04" min="0" max="1" step="0.01" readonly>
    <span class="help-text">Typical Vanguard index fund expense ratio is about 0.04%</span>
</div>

                <div class="input-group">
                    <label for="years">Investment Timeline (Years)</label>
                    <input type="range" id="years" value="20" min="1" max="50" step="1">
                    <div class="slider-label" style="margin-top: 4px; justify-content: flex-end;">
                        <span class="value" id="yearsLabel" style="font-weight: 500; color: #4b5563;"></span>
                    </div>
                </div>

                <div class="input-group">
                    <label for="returnRate">Expected Annual Return (Before Fees) (%)</label>
                    <input type="range" id="returnRate" value="8.0" min="0" max="20" step="0.25">
                    <div class="slider-label" style="margin-top: 4px; justify-content: flex-end;">
                        <span class="value" id="returnRateLabel" style="font-weight: 500; color: #4b5563;"></span>
                    </div>
                    <span class="help-text">Historical S&P 500 average: ~10% (we use conservative 8%)</span>
                </div>

                <button id="calculateBtn" class="calculate-btn" type="button">Calculate True Cost</button>
            </div>

            <!-- Results Section -->
            <div id="results" class="results-section" style="display: none;">
                <h2>The True Cost of Your Advisor Fee</h2>
                
                <!-- Opportunity Cost Banner -->
                <div class="opportunity-cost-banner">
                    <div class="cost-label">Total Opportunity Cost Over <span id="resultYears"></span> Years:</div>
                    <div class="cost-amount" id="opportunityCost">$0</div>
<div class="cost-explanation">This is the amount of money you are losing by not having your money in a Vanguard index fund</div>                </div>

                <!-- Comparison Table -->
                <div class="comparison-table">
                    <div class="comparison-header">
                        <div class="col-label"></div>
                        <div class="col-managed">Managed Portfolio<br><span class="fee-label" id="managedFeeLabel"></span></div>
                        <div class="col-vanguard">Vanguard VTSAX<br><span class="fee-label">0.04% fee</span></div>
                        <div class="col-difference">You're Losing</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="row-label">Year 1 Fee</div>
                        <div class="col-managed" id="managedYear1Fee"></div>
                        <div class="col-vanguard" id="vanguardYear1Fee"></div>
                        <div class="col-difference negative" id="year1FeeDiff"></div>
                    </div>

                    <div class="comparison-row">
                        <div class="row-label">Year <span id="midYearLabel"></span> Portfolio</div>
                        <div class="col-managed" id="managedMidValue"></div>
                        <div class="col-vanguard" id="vanguardMidValue"></div>
                        <div class="col-difference negative" id="midValueDiff"></div>
                    </div>

                    <div class="comparison-row highlight">
                        <div class="row-label">Year <span id="finalYearLabel"></span> Portfolio</div>
                        <div class="col-managed" id="managedFinalValue"></div>
                        <div class="col-vanguard" id="vanguardFinalValue"></div>
                        <div class="col-difference negative large" id="finalValueDiff"></div>
                    </div>

                    <div class="comparison-row">
                        <div class="row-label">Total Fees Paid</div>
                        <div class="col-managed" id="managedTotalFees"></div>
                        <div class="col-vanguard" id="vanguardTotalFees"></div>
                        <div class="col-difference negative" id="totalFeesDiff"></div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="chart-container">
                    <h3>Portfolio Growth Over Time</h3>
                    <canvas id="growthChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Cumulative Fees Paid Over Time</h3>
                    <canvas id="feesChart"></canvas>
                </div>

                <!-- Key Insights -->
                <div class="insights-section">
                    <h3>Key Insights</h3>
                    <div class="insight-box">
                        <div class="insight-icon">💰</div>
                        <div class="insight-content">
                            <strong>Direct Fees:</strong> You'll pay <span id="insightDirectFees"></span> more in advisor fees over <span id="insightYears"></span> years.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">📈</div>
                        <div class="insight-content">
                            <strong>Lost Growth:</strong> Those fee dollars would have grown to <span id="insightLostGrowth"></span> in Vanguard VTSAX.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">🎯</div>
                        <div class="insight-content">
                            <strong>What Your Advisor Must Deliver:</strong> To justify their fee, your advisor must beat Vanguard's return by <span id="insightBeatBy"></span> annually. <em>Most don't.</em>
                        </div>
                    </div>
                </div>

                <?php if ($isPremium): ?>
                <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                    <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                    <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
                </div>
                <?php endif; ?>

                <?php $share_title = 'Managed vs. Vanguard Calculator'; $share_text = 'Check out the Managed vs. Vanguard calculator at ronbelisle.com — see the true cost of advisor fees.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
            </div>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Unlock Premium Features';
        $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDF and CSV reports, get AI-generated plain-language explanations of your specific results, and access your fee comparison across devices.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
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