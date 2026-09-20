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
    <meta name="description" content="Compare Vanguard Personal Advisor Services (PAS) fees with a self-managed mix of Vanguard Target Date funds. See your opportunity cost.">
    <title>Vanguard Personal Advisor vs Target Date Funds</title>
    <?php $og_title = $ld_name = 'Vanguard Personal Advisor vs Target Date Funds'; $og_description = $ld_description = 'Compare Vanguard PAS fees with a self-managed mix of Vanguard Target Date funds. See your opportunity cost.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="styles.css?v=4">
</head>
<body>
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Vanguard Personal Advisor vs Target Date Funds</h1>
            <p class="subtitle">Compare the cost of Vanguard PAS (0.30%) with a self-managed three-bucket Target Date strategy</p>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e2e8f0;">
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>How This Works</h2>
            <p><strong>Simple Projection:</strong> Compare the same portfolio return under two editable annual fee assumptions. Enter the <strong>total cost of advice and underlying funds</strong> for PAS, and the total fund cost for your Target Date alternative. The starting values (0.30% and 0.08%) are illustrative assumptions, not verified current price quotes. Check your actual fees. Allocation percentages set three separate starting balances; each uses the entered gross return.</p>
            <p>Figures are nominal dollars. In Simple Projection, each modeled year applies growth first, then fees and any withdrawal to that grown balance. Ending balances exclude money already withdrawn; compare the income totals as well. Withdrawals begin in the selected calendar year.</p>
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
                    <label for="analysisMode">Analysis mode</label>
                    <select id="analysisMode" style="width:100%;padding:12px;font:inherit;"><option value="simple">Simple Projection</option><option value="stress">Retirement Stress Test</option></select>
                    <span class="help-text">Simple Projection keeps the existing deterministic fee comparison. Retirement Stress Test explores many hypothetical market sequences with different return assumptions.</span>
                </div>

                <div class="input-group">
                    <label for="portfolioValue">Current Portfolio Value ($)</label>
                    <input type="number" id="portfolioValue" value="1000000" min="0" step="any">
                    <span class="help-text">Enter your exact portfolio value (e.g. 1,325,000 &rarr; 1325000).</span>
                </div>

                <div class="input-group">
                    <label for="pasFee">PAS total annual fee assumption (%)</label>
                    <input type="number" id="pasFee" value="0.30" min="0" max="1" step="any">
                    <span class="help-text">Include advice and underlying fund expenses.</span>
                </div>

                <div class="input-group">
                    <label>Target Date Fund Blend Expense (%)</label>
                    <input type="number" id="targetDateFee" aria-label="Target date fund annual fee, percent" value="0.08" min="0" max="0.5" step="any">
                    <span class="help-text">Enter your actual fund expense ratio. It applies once to each bucket, with no additional advisory charge.</span>
                </div>

                <div class="input-group">
                    <div class="slider-label">
                        <span>Investment Timeline (Years)</span>
                        <span class="value" id="yearsLabel">20 yrs</span>
                    </div>
                    <input type="range" id="years" aria-label="Years to model" value="20" min="1" max="50" step="1">
                </div>

                <div class="input-group" data-simple-only>
                    <div class="slider-label">
                        <span>Expected Annual Return (Before Fees) (%)</span>
                        <span class="value" id="returnRateLabel">6%</span>
                    </div>
                    <input type="range" id="returnRate" aria-label="Expected annual return, percent" value="6" min="0" max="20" step="0.25">
                    <span class="help-text">Use a conservative assumption (e.g. 5–7%)</span>
                </div>

                <div class="input-group">
                    <label for="timelineStartYear">Timeline start year</label>
                    <input type="number" id="timelineStartYear" value="<?php echo (int)date('Y'); ?>" min="2000" max="2100" step="any">
                    <span class="help-text">The real-world year when the simulation begins: year 1 in the results is this year, year 2 is the next calendar year, and so on.</span>
                </div>
                <div class="input-group" data-simple-only>
                    <div class="slider-label">
                        <span>Annual Withdrawal (% of portfolio)</span>
                        <span class="value" id="withdrawalPctLabel">4.2%</span>
                    </div>
                    <input type="range" id="withdrawalPct" aria-label="Annual portfolio withdrawal, percent" value="4.2" min="0" max="10" step="0.1">
                    <span class="help-text">Each year, withdrawals equal this percentage of that alternative’s current total balance after growth, before fees are deducted. For example, 4.5% of $1,060,000 is $47,700. Fees also use that same pre-withdrawal balance. Set to 0 for no withdrawals; this is not a fixed percentage of the original portfolio.</span>
                </div>
                <div class="input-group">
                    <label for="withdrawalsStartYear">Withdrawals start year</label>
                    <input type="number" id="withdrawalsStartYear" value="2027" min="2000" max="2100" step="any">
                    <span class="help-text">Calendar year when withdrawals begin: the selected percentage in Simple Projection, or the starting dollar amount in Retirement Stress Test.</span>
                </div>

                <div id="stressInputs" hidden>
                    <h3>Retirement Stress Test assumptions</h3>
                    <p class="help-text">Generic planning assumptions, not Vanguard forecasts or historical guarantees. PAS assumptions are independent editable inputs; no outperformance is implied.</p>
                    <p class="help-text">Expected Return is the assumed long-term arithmetic average annual return before fees. Volatility describes how much yearly returns may vary around that average; higher volatility means a wider range of possible gains and losses.</p>
                    <div class="input-group"><label for="annualWithdrawal">Starting annual withdrawal ($)</label><input type="number" id="annualWithdrawal" value="60000" min="0" max="10000000000" step="any"></div>
                    <p class="help-text">$60,000 is approximately $5,000 per month. Both strategies face the same dollar requirement, starting in the selected withdrawal year; inflation increases it only after withdrawals begin. If that year is before the timeline, spending starts at the entered amount in the first modeled year.</p>
                    <div class="input-group"><label for="inflation">Inflation adjustment (%)</label><input type="number" id="inflation" value="2.5" min="-10" max="20" step="any"></div>
                    <div class="input-group"><label for="conservativeReturn">Conservative expected annual return (%)</label><input type="number" id="conservativeReturn" value="4" min="-100" max="100" step="any"></div>
                    <div class="input-group"><label for="conservativeVolatility">Conservative annual volatility (%)</label><input type="number" id="conservativeVolatility" value="6" min="0" max="100" step="any"></div>
                    <div class="input-group"><label for="moderateReturn">Moderate expected annual return (%)</label><input type="number" id="moderateReturn" value="5.5" min="-100" max="100" step="any"></div>
                    <div class="input-group"><label for="moderateVolatility">Moderate annual volatility (%)</label><input type="number" id="moderateVolatility" value="10" min="0" max="100" step="any"></div>
                    <div class="input-group"><label for="aggressiveReturn">Aggressive expected annual return (%)</label><input type="number" id="aggressiveReturn" value="7" min="-100" max="100" step="any"></div>
                    <div class="input-group"><label for="aggressiveVolatility">Aggressive annual volatility (%)</label><input type="number" id="aggressiveVolatility" value="16" min="0" max="100" step="any"></div>
                    <div class="input-group"><label for="pasReturn">PAS expected annual return (%)</label><input type="number" id="pasReturn" value="6" min="-100" max="100" step="any"></div>
                    <div class="input-group"><label for="pasVolatility">PAS annual volatility (%)</label><input type="number" id="pasVolatility" value="11" min="0" max="100" step="any"></div>
                    <div class="input-group"><label for="simulations">Number of simulations</label><select id="simulations" style="width:100%;padding:12px;font:inherit;"><option value="1000">1,000</option><option value="5000">5,000</option><option value="10000" selected>10,000</option></select></div>
                    <div class="input-group"><label for="simulationSeed">Simulation seed</label><input type="number" id="simulationSeed" min="0" max="4294967295" step="1"><span class="help-text">The same seed and inputs reproduce the results. Save/Load preserves this seed. Run New Simulation generates another seed.</span></div>
                    <details><summary>Stress Test methodology and correlations</summary>
                        <p class="help-text">Annual arithmetic returns follow a normal model, floored at −100%. Correlated shocks use Cholesky decomposition of a validated positive definite matrix; years are independent. Extreme assumptions and the loss floor can alter the modeled average, volatility and correlations. This simplified model omits fat tails, changing correlations, taxes, glide-path changes and market regimes.</p>
                        <p class="help-text">Fixed correlations: Conservative/Moderate 0.65; Conservative/Aggressive 0.40; Moderate/Aggressive 0.80; PAS/Conservative 0.60; PAS/Moderate 0.85; PAS/Aggressive 0.90.</p>
                        <p class="help-text">Stress Test timing: apply returns, fund the same inflation-adjusted withdrawal, then charge the entered annual expense on each remaining balance. PAS includes the entered total advisory/fund cost once. No bucket replenishment or rebalancing. Simple Projection retains its original pre-withdrawal fee base.</p>
                    </details>
                </div>
                <h3 style="margin: 24px 0 12px; font-size: 18px; color: #334155;">Self-Managed Allocation (Target Date funds)</h3>
                <p style="margin-bottom: 16px; color: #64748b; font-size: 14px;">Allocate what share of your portfolio would go into conservative, moderate, and aggressive Target Date funds. Percentages are normalized to 100%; the final percentages shown below are used in the calculation.</p>

                <p class="help-text"><strong>Three-Bucket Strategy:</strong> Retirement withdrawals are taken from the Conservative bucket first, then Moderate, then Aggressive. This allows longer-term investments more time to remain invested. Buckets are not automatically replenished or rebalanced. In Simple Projection all three use the same expected return and expense, so sequencing alone does not change total value. Retirement Stress Test uses separate return and volatility assumptions.</p>
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

                <p id="stressStatus" role="status" aria-live="polite"></p>
                <button id="newSimulationBtn" type="button" class="btn-secondary" hidden>Run New Simulation</button>
                <button id="calculateBtn" class="calculate-btn" type="button">Calculate True Cost</button>
            </div>

            <div id="results" class="results-section" style="display: none; min-width: 0;">
                <div id="simpleProjectionResults">
                <h2>Simple Projection: PAS vs Self-Managed Target Date</h2>

                <div class="opportunity-cost-banner">
                    <div class="cost-label">Total Opportunity Cost Over <span id="resultYears"></span> Years:</div>
                    <div class="cost-amount" id="opportunityCost">$0</div>
                    <div class="cost-average">≈ <span id="avgAnnualCost">$0</span> per year on average</div>
                    <div class="cost-breakdown" id="costBreakdown">
                        <span class="cb-part"><span class="cb-num" id="breakdownFees">$0</span><span class="cb-desc">direct fee difference</span></span>
                        <span class="cb-op">+</span>
                        <span class="cb-part"><span class="cb-num" id="breakdownGrowth">$0</span><span class="cb-desc">growth / withdrawal effects</span></span>
                        <span class="cb-op">=</span>
                        <span class="cb-part cb-total"><span class="cb-num" id="breakdownTotal">$0</span><span class="cb-desc">total opportunity cost</span></span>
                    </div>
                    <div class="cost-explanation">Signed ending-balance difference: Target Date minus PAS. Negative values favor PAS under these assumptions.</div>
                </div>

                <div class="comparison-section">
                    <h3 class="comparison-section-title">Fees &amp; Costs</h3>
                    <div class="comparison-table">
                        <div class="comparison-header">
                            <div class="col-label"></div>
                            <div class="col-managed">Vanguard PAS<br><span class="fee-label" id="pasFeeResultLabel"></span></div>
                            <div class="col-vanguard">Three-Bucket Total<br><span class="fee-label">Selected fund fee</span></div>
                            <div class="col-difference">You're Giving Up</div>
                        </div>

                        <div class="comparison-row">
                            <div class="row-label">Year 1 Fee</div>
                            <div class="col-managed" id="pasYear1Fee"></div>
                            <div class="col-vanguard" id="targetYear1Fee"></div>
                            <div class="col-difference negative" id="year1FeeDiff"></div>
                        </div>

                        <div class="comparison-row">
                            <div class="row-label">Total Fees Paid</div>
                            <div class="col-managed" id="pasTotalFees"></div>
                            <div class="col-vanguard" id="targetTotalFees"></div>
                            <div class="col-difference negative" id="totalFeesDiff"></div>
                        </div>
                    </div>
                </div>

                <div class="comparison-section">
                    <h3 class="comparison-section-title">Portfolio &amp; Income</h3>
                    <div class="comparison-table">
                        <div class="comparison-header">
                            <div class="col-label"></div>
                            <div class="col-managed">Vanguard PAS<br><span class="fee-label">Selected PAS fee</span></div>
                            <div class="col-vanguard">Three-Bucket Total<br><span class="fee-label">Selected fund fee</span></div>
                            <div class="col-difference">You're Giving Up</div>
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

                        <div class="comparison-row" id="incomeRow">
                            <div class="row-label">Retirement Income Taken</div>
                            <div class="col-managed" id="pasTotalIncome"></div>
                            <div class="col-vanguard" id="targetTotalIncome"></div>
                            <div class="col-difference neutral" id="totalIncomeDiff"></div>
                        </div>
                    </div>
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

                </div>
                <section id="stressResults" hidden aria-labelledby="stressHeading">
                    <h2 id="stressHeading">Retirement Stress Test</h2>
                    <p><strong>Monte Carlo results are hypothetical planning illustrations based on the assumptions entered. They are not predictions or guarantees of future investment performance.</strong></p>
                    <p class="help-text">Expected returns, volatility, inflation, correlations and fees materially affect these illustrations. Compare income delivered, survival and the range of ending balances together; no single metric establishes a better strategy.</p>
                    <p id="stressRunDetails"></p>
                    <div style="overflow-x:auto;" role="region" aria-label="Stress Test comparison" tabindex="0"><table class="stress-table"><thead><tr><th>Metric</th><th>Vanguard PAS</th><th>Three-Bucket</th></tr></thead><tbody id="stressComparison"></tbody></table></div>
                    <p class="help-text">Survival means all scheduled withdrawals were funded, including an exactly funded final withdrawal with a $0 balance. Failure is the first spending shortfall, not merely a low balance. Failure-year statistics include failed simulations only; the early failure year is their 10th percentile, rather than a single extreme outlier. With no scheduled spending, all paths meet the spending requirement.</p>
                    <h3>Three-Bucket behavior</h3><div id="stressBucketDetails"></div>
                    <p class="help-text">Depletion years include only simulations where a funded bucket depleted; the accompanying percentage shows how often that happened. Buckets empty at the start are labeled separately.</p>
                    <div class="chart-container"><h3>Ending Balance Percentiles</h3><div class="stress-chart"><canvas id="stressEndingChart" role="img" aria-label="PAS and Three-Bucket ending balance percentiles"></canvas></div></div>
                    <div class="chart-container"><h3>Portfolio Survival Over Time</h3><div class="stress-chart"><canvas id="stressSurvivalChart" role="img" aria-label="Percentage of paths funding all spending to each year"></canvas></div></div>
                    <div class="chart-container"><h3>Median Bucket Balances Over Time</h3><div class="stress-chart"><canvas id="stressBucketChart" role="img" aria-label="Pointwise median Conservative Moderate and Aggressive balances"></canvas></div><p class="help-text">Each point is that bucket’s median across all paths, including depleted balances. These are not one representative simulation; individual bucket medians need not sum to the median total portfolio.</p></div>
                    <p>Sequence-of-returns risk means that poor market returns early in retirement can be more damaging than the same poor returns occurring later, because withdrawals may force investments to be sold while values are depressed.</p>
                    <p>The three-bucket strategy attempts to reduce this risk by spending conservative assets first while allowing longer-term assets more time to remain invested. It does not eliminate sequence risk.</p>
                </section>
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
    <script src="stress-engine.js?v=1"></script>
    <script src="calculator.js?v=9"></script>
    <script src="stress-ui.js?v=1"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
