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
    <meta name="description" content="Compare Vanguard Personal Advisor Services (PAS) fees with a self-managed mix of Vanguard Target Date funds. See your opportunity cost.">
    <title>Vanguard Personal Advisor vs Target Date Funds</title>
    <?php $og_title = $ld_name = 'Vanguard Personal Advisor vs Target Date Funds'; $og_description = $ld_description = 'Compare Vanguard PAS fees with a self-managed mix of Vanguard Target Date funds. See your opportunity cost.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Vanguard Personal Advisor vs Target Date Funds</h1>
            <p class="subtitle">Compare the cost of Vanguard PAS (0.30%) with a self-managed blend of Target Date funds</p>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e2e8f0;">
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>How This Works</h2>
            <p><strong>Vanguard Personal Advisor Services (PAS)</strong> charges 0.30% of your portfolio per year for ongoing advice and management. You can instead self-manage using Vanguard’s <strong>Target Retirement</strong> funds (e.g. VTINX, VTWNX, VTTVX … VTTSX, VLXVX, VSVNX), which have expense ratios around 0.08%. This tool compares the two: same expected return, different fees—so you see the opportunity cost of staying with PAS.</p>
            <p style="margin-top: 12px;">You can allocate your self-managed portfolio across <strong>conservative</strong> (Income / 2020), <strong>moderate</strong> (2025–2035), and <strong>aggressive</strong> (2040–2070) Target Date funds. The math uses a blended expense ratio of about 0.08% for the self-managed side.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with charts (PDF)">📄 Download PDF</button>
        <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year data for Excel or spreadsheets">📊 Export CSV</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data. <strong>Explain</strong> — AI explains your results in plain language.
    </p>
</div>
<?php endif; ?>

        <div class="calculator-wrapper">
            <div class="input-section">
                <h2>Your Portfolio &amp; Assumptions</h2>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Current Portfolio Value</span>
                        <span class="value" id="portfolioValueLabel">$1,000,000</span>
                    </div>
                    <input type="range" id="portfolioValue" value="1000000" min="50000" max="5000000" step="25000">
                </div>

                <div class="input-group">
                    <label for="pasFee">Vanguard PAS Fee (%)</label>
                    <input type="number" id="pasFee" value="0.30" min="0" max="1" step="0.01" readonly>
                    <span class="help-text">Vanguard Personal Advisor Services: 0.30%</span>
                </div>

                <div class="input-group">
                    <label>Target Date Fund Blend Expense (%)</label>
                    <input type="number" id="targetDateFee" value="0.08" min="0" max="0.5" step="0.01" readonly>
                    <span class="help-text">Typical Vanguard Target Retirement fund expense ratio ~0.08%</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Investment Timeline (Years)</span>
                        <span class="value" id="yearsLabel">20 yrs</span>
                    </div>
                    <input type="range" id="years" value="20" min="1" max="50" step="1">
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Expected Annual Return (Before Fees) (%)</span>
                        <span class="value" id="returnRateLabel">6%</span>
                    </div>
                    <input type="range" id="returnRate" value="6" min="0" max="20" step="0.25">
                    <span class="help-text">Use a conservative assumption (e.g. 5–7%)</span>
                </div>

                <div class="input-group">
                    <label for="timelineStartYear">Timeline start year</label>
                    <input type="number" id="timelineStartYear" value="<?php echo (int)date('Y'); ?>" min="2000" max="2100" step="1">
                    <span class="help-text">The real-world year when the simulation begins: year 1 in the results is this year, year 2 is the next calendar year, and so on.</span>
                </div>
                <div class="input-group">
                    <div class="slider-label">
                        <span>Annual Withdrawal (% of portfolio)</span>
                        <span class="value" id="withdrawalPctLabel">0%</span>
                    </div>
                    <input type="range" id="withdrawalPct" value="0" min="0" max="10" step="0.1">
                    <span class="help-text">Set to 0 for no withdrawal. Many retirees use ~4% (e.g. 4.2% starting in a given year).</span>
                </div>
                <div class="input-group">
                    <label for="withdrawalsStartYear">Withdrawals start year</label>
                    <input type="number" id="withdrawalsStartYear" value="<?php echo (int)date('Y'); ?>" min="2000" max="2100" step="1">
                    <span class="help-text">Calendar year when you begin taking the annual withdrawal % above. Example: 2027 if you won&rsquo;t withdraw until then.</span>
                </div>

                <h3 style="margin: 24px 0 12px; font-size: 18px; color: #334155;">Self-Managed Allocation (Target Date funds)</h3>
                <p style="margin-bottom: 16px; color: #64748b; font-size: 14px;">Allocate what share of your portfolio would go into conservative, moderate, and aggressive Target Date funds. Percentages are normalized to 100%.</p>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Conservative (Income / 2020)</span>
                        <span class="value" id="pctConservativeLabel">40%</span>
                    </div>
                    <input type="range" id="pctConservative" value="40" min="0" max="100" step="5">
                    <span class="help-text">VTINX, VTWNX</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Moderate (2025–2035)</span>
                        <span class="value" id="pctModerateLabel">40%</span>
                    </div>
                    <input type="range" id="pctModerate" value="40" min="0" max="100" step="5">
                    <span class="help-text">VTTVX, VTHRX, VTTHX</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Aggressive (2040–2070)</span>
                        <span class="value" id="pctAggressiveLabel">20%</span>
                    </div>
                    <input type="range" id="pctAggressive" value="20" min="0" max="100" step="5">
                    <span class="help-text">VFORX, VTIVX, VFIFX, VFFVX, VTTSX, VLXVX, VSVNX</span>
                </div>

                <div id="allocationSum" class="allocation-sum" style="margin-top: 8px; font-size: 13px; color: #64748b;"></div>

                <button id="calculateBtn" class="calculate-btn" type="button">Calculate True Cost</button>
            </div>

            <div id="results" class="results-section" style="display: none;">
                <h2>The Cost of PAS vs Self-Managed Target Date</h2>

                <div class="opportunity-cost-banner">
                    <div class="cost-label">Total Opportunity Cost Over <span id="resultYears"></span> Years:</div>
                    <div class="cost-amount" id="opportunityCost">$0</div>
                    <div class="cost-explanation">Extra amount you could have by self-managing with Target Date funds instead of PAS</div>
                </div>

                <div class="comparison-table">
                    <div class="comparison-header">
                        <div class="col-label"></div>
                        <div class="col-managed">Vanguard PAS<br><span class="fee-label" id="pasFeeResultLabel"></span></div>
                        <div class="col-vanguard">Target Date Blend<br><span class="fee-label">~0.08% fee</span></div>
                        <div class="col-difference">You're Giving Up</div>
                    </div>

                    <div class="comparison-row">
                        <div class="row-label">Year 1 Fee</div>
                        <div class="col-managed" id="pasYear1Fee"></div>
                        <div class="col-vanguard" id="targetYear1Fee"></div>
                        <div class="col-difference negative" id="year1FeeDiff"></div>
                    </div>

                    <div class="comparison-row">
                        <div class="row-label">Year <span id="midYearLabel"></span> Portfolio</div>
                        <div class="col-managed" id="pasMidValue"></div>
                        <div class="col-vanguard" id="targetMidValue"></div>
                        <div class="col-difference negative" id="midValueDiff"></div>
                    </div>

                    <div class="comparison-row highlight">
                        <div class="row-label">Year <span id="finalYearLabel"></span> Portfolio</div>
                        <div class="col-managed" id="pasFinalValue"></div>
                        <div class="col-vanguard" id="targetFinalValue"></div>
                        <div class="col-difference negative large" id="finalValueDiff"></div>
                    </div>

                    <div class="comparison-row">
                        <div class="row-label">Total Fees Paid</div>
                        <div class="col-managed" id="pasTotalFees"></div>
                        <div class="col-vanguard" id="targetTotalFees"></div>
                        <div class="col-difference negative" id="totalFeesDiff"></div>
                    </div>
                </div>

                <div class="chart-container">
                    <h3>Portfolio Growth Over Time</h3>
                    <canvas id="growthChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Cumulative Fees Paid Over Time</h3>
                    <canvas id="feesChart"></canvas>
                </div>

                <div class="insights-section">
                    <h3>Key Insights</h3>
                    <div class="insight-box">
                        <div class="insight-icon">💰</div>
                        <div class="insight-content">
                            <strong>Direct Fees:</strong> You'll pay <span id="insightDirectFees"></span> more with PAS over <span id="insightYears"></span> years.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">📈</div>
                        <div class="insight-content">
                            <strong>Lost Growth:</strong> Those fee dollars would have grown to <span id="insightLostGrowth"></span> in the Target Date blend.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">🎯</div>
                        <div class="insight-content">
                            <strong>Your self-managed blend:</strong> <span id="insightAllocation"></span> (conservative / moderate / aggressive).
                        </div>
                    </div>
                </div>

                <?php if ($isPremium): ?>
                <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                    <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                    <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
                </div>
                <?php endif; ?>

                <?php $share_title = 'Vanguard PAS vs Target Date Funds'; $share_text = 'Compare Vanguard Personal Advisor Services with self-managed Target Date funds at ronbelisle.com'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
            </div>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Unlock Premium Features';
        $premium_upsell_text = 'Upgrade to Premium to save and load scenarios, export PDF and CSV reports, and get AI-generated plain-language explanations of your results.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js?v=3"></script>
</body>
</html>
