<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
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
    <meta name="description" content="Compare Vanguard Personal Advisor Services (PAS) fees with a self-managed mix of Vanguard Target Date funds. Compare direct fees and their projected portfolio effect.">
    <title>Vanguard Personal Advisor vs Target Date Funds</title>
    <?php $og_title = $ld_name = 'Vanguard Personal Advisor vs Target Date Funds'; $og_description = $ld_description = 'Compare Vanguard PAS fees with a self-managed mix of Vanguard Target Date funds. Compare direct fees and their projected portfolio effect.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="styles.css?v=5">
</head>
<body>
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Vanguard Personal Advisor vs Target Date Funds</h1>
            <p class="subtitle">Compare the cost of Vanguard PAS (advisory fee plus fund expenses) with a self-managed three-bucket Target Date strategy</p>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e2e8f0;">
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>How This Works</h2>
            <p>Compare the same portfolio return under two cost structures. PAS costs include an editable advisory fee plus the weighted expense ratio of its underlying funds. The self-managed portfolio incurs only its Target Date fund expense. Defaults are planning assumptions; the 0.08% PAS fund expense is generic, not your verified actual expense. Enter the rates for your own holdings. <a href="https://investor.vanguard.com/advice/personal-financial-advisor" target="_blank" rel="noopener">Vanguard explains that investment expense ratios are separate from its advisory fee.</a></p>
            <p>Figures are nominal dollars. Each modeled year applies growth first, charges fees once on the grown balance, then funds the scheduled withdrawal from remaining assets. This annual approximation does not model monthly January cash-flow timing. Ending balances exclude money already withdrawn; compare the income totals as well. Withdrawals begin in the selected calendar year.</p>
            <p style="margin-top: 12px;">You can allocate your self-managed portfolio across <strong>conservative</strong> (Income / 2020), <strong>moderate</strong> (2025–2035), and <strong>aggressive</strong> (2040–2070) Target Date funds. The selected Target Date expense ratio is applied once to each bucket; no advisory fee is added.</p>
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
                    <label for="portfolioValue">Current Portfolio Value ($)</label>
                    <input type="number" id="portfolioValue" value="1000000" min="0" step="any">
                    <span class="help-text">Enter your exact portfolio value (e.g. 1,325,000 &rarr; 1325000).</span>
                </div>

                <div class="input-group">
                    <label for="pasFee">PAS Advisory Fee (%)</label>
                    <input type="number" id="pasFee" value="0.30" min="0" max="1" step="any">
                    <span class="help-text">Annual Vanguard Personal Advisor advisory fee. Investment expense ratios are modeled separately below.</span>
                </div>

                <div class="input-group">
                    <label for="pasFundExpense">PAS Underlying Fund Expense (%)</label>
                    <input type="number" id="pasFundExpense" value="0.08" min="0" max="100" step="any">
                    <span class="help-text">Weighted average expense ratio of the funds held inside the PAS-managed portfolio. This cost is separate from the PAS advisory fee. The 0.08% default is an editable generic planning assumption, not your verified actual PAS fund expense.</span>
                </div>
                <p class="help-text" style="margin-bottom:20px;"><strong>PAS Modeled All-In Annual Cost: <span id="pasAllInCost">0.38%</span></strong><br>Advisory fee plus underlying fund expense, based on the entered assumptions; not a universal Vanguard fee.</p>
                <p id="pasCostMigration" class="help-text" role="status" hidden></p>
                <div class="input-group">
                    <label for="targetDateFee">Self-Managed Target Date Fund Expense (%)</label>
                    <input type="number" id="targetDateFee" aria-label="Self-Managed Target Date Fund Expense (%)" value="0.08" min="0" max="0.5" step="any">
                    <span class="help-text">Weighted average expense ratio of the Target Date funds in the self-managed three-bucket strategy. No advisory fee is added.</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Investment Timeline (Years)</span>
                        <span class="value" id="yearsLabel">20 yrs</span>
                    </div>
                    <input type="range" id="years" aria-label="Years to model" value="20" min="1" max="50" step="1">
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Expected Annual Return (Before Fees) (%)</span>
                        <span class="value" id="returnRateLabel">6%</span>
                    </div>
                    <input type="range" id="returnRate" aria-label="Expected annual return, percent" value="6" min="0" max="20" step="0.25">
                    <span class="help-text">The same assumed gross return is applied to both alternatives so the comparison isolates the effect of fees. This calculator does not assume that either strategy will outperform the other.</span>
                </div>

                <div class="input-group">
                    <label for="timelineStartYear">Timeline start year</label>
                    <input type="number" id="timelineStartYear" value="<?php echo (int)date('Y'); ?>" min="2000" max="2100" step="any">
                    <span class="help-text">The real-world year when the projection begins: year 1 in the results is this year, year 2 is the next calendar year, and so on.</span>
                </div>
                <input type="hidden" id="withdrawalModel" value="dollar">
                <div id="dollarWithdrawals">
                    <div class="input-group"><label for="annualWithdrawal">Starting Annual Withdrawal ($)</label><input type="number" id="annualWithdrawal" value="60000" min="0" max="10000000000" step="any"><span class="help-text">$60,000 per year is approximately $5,000 per month. The same dollar requirement applies to PAS and the Three-Bucket portfolio.</span></div>
                    <div class="input-group"><label for="inflation">Annual Withdrawal Inflation Adjustment (%)</label><input type="number" id="inflation" value="2.5" min="-10" max="20" step="any"><span class="help-text">Spending increases each year after the selected withdrawal start year: $60,000, $61,500, then $63,037.50 at 2.5%. If the timeline begins later, the schedule already includes inflation since that start year.</span></div>
                </div>
                <div id="legacyWithdrawals" hidden>
                    <p class="help-text"><strong>Legacy percentage-withdrawal scenario.</strong> The original calculations are retained. Switch to dollar withdrawals for a common spending plan.</p>
                    <button id="useDollarWithdrawals" type="button">Use dollar withdrawals</button>
                <div class="input-group">
                    <div class="slider-label">
                        <span>Annual Withdrawal (% of portfolio)</span>
                        <span class="value" id="withdrawalPctLabel">4.2%</span>
                    </div>
                    <input type="range" id="withdrawalPct" aria-label="Annual portfolio withdrawal, percent" value="4.2" min="0" max="10" step="0.1">
                    <span class="help-text">Each year, withdrawals equal this percentage of that alternative’s current total balance after growth, before fees are deducted. For example, 4.5% of $1,060,000 is $47,700. Fees also use that same pre-withdrawal balance. Set to 0 for no withdrawals; this is not a fixed percentage of the original portfolio.</span>
                </div>
                </div>
                <div class="input-group">
                    <label for="withdrawalsStartYear">Withdrawals start year</label>
                    <input type="number" id="withdrawalsStartYear" value="2027" min="2000" max="2100" step="any">
                    <span class="help-text">Calendar year when scheduled withdrawals begin. No withdrawals occur before this year.</span>
                </div>

                <h3 style="margin: 24px 0 12px; font-size: 18px; color: #334155;">Self-Managed Allocation (Target Date funds)</h3>
                <p style="margin-bottom: 16px; color: #64748b; font-size: 14px;">Allocate what share of your portfolio would go into conservative, moderate, and aggressive Target Date funds. Percentages are normalized to 100%; the final percentages shown below are used in the calculation.</p>

                <p class="help-text"><strong>Three-Bucket Strategy:</strong> Retirement withdrawals are taken from the Conservative bucket first, then Moderate, then Aggressive. This allows longer-term investments more time to remain invested. Buckets are not automatically replenished or rebalanced. All three use the same expected return and fund expense. Sequencing organizes withdrawals; it does not assume that the buckets earn different returns or increase total investment performance.</p>
                <div class="input-group">
                    <div class="slider-label">
                        <span>Conservative (Income / 2020)</span>
                        <span class="value" id="pctConservativeLabel">33.3%</span>
                    </div>
                    <input type="range" id="pctConservative" aria-label="Conservative allocation, percent" value="20" min="0" max="100" step="5">
                    <span class="help-text">VTINX, VTWNX</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Moderate (2025–2035)</span>
                        <span class="value" id="pctModerateLabel">33.3%</span>
                    </div>
                    <input type="range" id="pctModerate" aria-label="Moderate allocation, percent" value="20" min="0" max="100" step="5">
                    <span class="help-text">VTTVX, VTHRX, VTTHX</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Aggressive (2040–2070)</span>
                        <span class="value" id="pctAggressiveLabel">33.3%</span>
                    </div>
                    <input type="range" id="pctAggressive" aria-label="Aggressive allocation, percent" value="20" min="0" max="100" step="5">
                    <span class="help-text">VFORX, VTIVX, VFIFX, VFFVX, VTTSX, VLXVX, VSVNX</span>
                </div>

                <div id="allocationSum" class="allocation-sum" style="margin-top: 8px; font-size: 13px; color: #64748b;"></div>

                <button id="calculateBtn" class="calculate-btn" type="button">Calculate True Cost</button>
            </div>

            <div id="results" class="results-section" style="display: none; min-width: 0;">
                <h2>Vanguard PAS vs Self-Managed Three-Bucket</h2>

                <div class="opportunity-cost-banner">
                    <div class="fee-headlines"><div>PAS Total Costs<strong id="headlinePasFees"></strong></div><div>Three-Bucket Fund Expenses<strong id="headlineTargetFees"></strong></div></div>
                    <div class="cost-label">Additional Cost of Vanguard PAS<br><small>Direct costs over <span id="resultYears"></span> years</small></div>
                    <div class="cost-amount" id="opportunityCost">$0</div>
                    <div class="cost-average">≈ <span id="avgAnnualCost">$0</span> additional direct costs per year on average</div>
                    <div class="cost-explanation">PAS total modeled costs minus Three-Bucket fund expenses. A negative difference means PAS costs less under the entered assumptions.</div>
                </div>

                <div class="comparison-section">
                    <h3 class="comparison-section-title">Cost Comparison</h3>
                    <div class="comparison-table">
                        <div class="comparison-header">
                            <div class="col-label"></div>
                            <div class="col-managed">Vanguard PAS<br><span class="fee-label" id="pasFeeResultLabel"></span></div>
                            <div class="col-vanguard">Three-Bucket<br><span class="fee-label">Fund expenses</span></div>
                            <div class="col-difference">Difference</div>
                        </div>

                        <div class="comparison-row"><div class="row-label">Year 1 Advisory Fees</div><div class="col-managed" id="pasYear1Advisory"></div><div class="col-vanguard">$0</div><div class="col-difference">—</div></div>
                        <div class="comparison-row"><div class="row-label">Year 1 Fund Expenses</div><div class="col-managed" id="pasYear1Fund"></div><div class="col-vanguard" id="targetYear1Fund"></div><div class="col-difference">—</div></div>
                        <div class="comparison-row">
                            <div class="row-label">Year 1 Total Cost</div>
                            <div class="col-managed" id="pasYear1Fee"></div>
                            <div class="col-vanguard" id="targetYear1Fee"></div>
                            <div class="col-difference negative" id="year1FeeDiff"></div>
                        </div>

                        <div class="comparison-row"><div class="row-label">Cumulative Advisory Fees</div><div class="col-managed" id="pasCumulativeAdvisory"></div><div class="col-vanguard">$0</div><div class="col-difference">—</div></div>
                        <div class="comparison-row"><div class="row-label">Cumulative Fund Expenses</div><div class="col-managed" id="pasCumulativeFund"></div><div class="col-vanguard" id="targetCumulativeFund"></div><div class="col-difference">—</div></div>
                        <div class="comparison-row">
                            <div class="row-label">Total Costs / Fund Expenses</div>
                            <div class="col-managed" id="pasTotalFees"></div>
                            <div class="col-vanguard" id="targetTotalFees"></div>
                            <div class="col-difference negative" id="totalFeesDiff"></div>
                        </div>
                    </div>
                </div>

                <div class="comparison-section">
                    <h3 class="comparison-section-title">Portfolio Effect of Those Costs</h3>
                    <div class="comparison-table">
                        <div class="comparison-header">
                            <div class="col-label"></div>
                            <div class="col-managed">Vanguard PAS<br><span class="fee-label">Advisory + fund expenses</span></div>
                            <div class="col-vanguard">Three-Bucket<br><span class="fee-label">Fund expenses</span></div>
                            <div class="col-difference">Difference</div>
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


                    </div>
                </div>

                <div class="portfolio-decomposition">
                    <p class="help-text">Projected ending portfolio difference = direct fee difference + compounding / withdrawal effects. The ending difference is not itself a fee. If either portfolio cannot fund spending, different withdrawals also affect the ending difference.</p>
                    <div class="cost-breakdown" id="costBreakdown">
                        <span class="cb-part"><span class="cb-num" id="breakdownFees">$0</span><span class="cb-desc">direct fee difference</span></span>
                        <span class="cb-op">+</span>
                        <span class="cb-part"><span class="cb-num" id="breakdownGrowth">$0</span><span class="cb-desc">growth / withdrawal effects</span></span>
                        <span class="cb-op">=</span>
                        <span class="cb-part cb-total"><span class="cb-num" id="breakdownTotal">$0</span><span class="cb-desc">ending portfolio difference</span></span>
                    </div>
                    <div class="cost-explanation">Signed ending-balance difference: Target Date minus PAS. Negative values favor PAS under these assumptions.</div>
                </div>
                <div class="comparison-section">
                    <h3 class="comparison-section-title">Retirement Withdrawals</h3>
                    <div class="comparison-table"><div class="comparison-header"><div></div><div>PAS</div><div>Three-Bucket</div><div>Difference</div></div>                        <div class="comparison-row" id="incomeRow">
                            <div class="row-label">Total Withdrawals Paid</div>
                            <div class="col-managed" id="pasTotalIncome"></div>
                            <div class="col-vanguard" id="targetTotalIncome"></div>
                            <div class="col-difference neutral" id="totalIncomeDiff"></div>
                        </div></div>
                    <p class="help-text" id="withdrawalMethodResult"></p>
                    <p><strong>PAS:</strong> <span id="pasWithdrawalStatus"></span></p>
                    <p><strong>Three-Bucket:</strong> <span id="targetWithdrawalStatus"></span></p>
                </div>
                <div class="comparison-section">
                    <h3 class="comparison-section-title">Your Three Buckets</h3>
                    <p class="help-text">Starting balances, midpoint and ending balances after fees and withdrawals. Depletion refers to a zero balance, not a balance rounded to $0. Displayed amounts are rounded independently; totals use full precision.</p>
                    <div style="overflow-x: auto;" tabindex="0" role="region" aria-label="Bucket balances">
                        <table class="bucket-table" style="width: 100%; text-align: left; border-spacing: 12px;">
                            <thead><tr><th scope="col">Bucket</th><th scope="col">Starting balance</th><th scope="col" id="bucketMidYear">Midpoint</th><th scope="col" id="bucketEndYear">Ending balance</th><th scope="col">Depletion</th></tr></thead>
                            <tbody id="bucketSummary"></tbody>
                        </table>
                    </div>
                </div>
                <div class="chart-container">
                    <h3>Three-Bucket Balances Over Time</h3>
                    <div style="position: relative; height: 300px;"><canvas id="bucketChart" role="img" aria-label="Conservative, Moderate and Aggressive bucket balances over time"></canvas></div>
                </div>

                <div class="chart-container">
                    <h3>Portfolio Growth Over Time</h3>
                    <canvas id="growthChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Cumulative Fees Paid Over Time</h3><p class="help-text">PAS total costs include advisory fees plus underlying fund expenses; self-managed costs include fund expenses only.</p>
                    <canvas id="feesChart"></canvas>
                </div>

                <div class="insights-section">
                    <h3>Key Insights</h3>
                    <div class="insight-box">
                        <div class="insight-icon">💰</div>
                        <div class="insight-content">
                            <strong>Direct Costs:</strong> Additional modeled PAS costs: <span id="insightDirectFees"></span> over <span id="insightYears"></span> years.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">📈</div>
                        <div class="insight-content">
                            <strong>Other balance effects:</strong> <span id="insightLostGrowth"></span> remains after the fee difference. It includes compounding and differences in portfolio withdrawals.
                        </div>
                    </div>
                    <div class="insight-box">
                        <div class="insight-icon">🎯</div>
                        <div class="insight-content">
                            <strong>Your starting bucket allocation:</strong> <span id="insightAllocation"></span> (conservative / moderate / aggressive).
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
    <script src="../js/explain-results-modal.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js?v=12"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
