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
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Estimate how stopping work early can lower your Social Security benefit versus the SSA statement that assumes you keep earning.">
    <title>Early Exit Social Security Impact</title>
    <?php
    $og_title = $ld_name = 'Early Exit Social Security Impact';
    $og_description = $ld_description = 'Estimate how stopping work early can lower your Social Security benefit versus the SSA statement that assumes you keep earning.';
    include(__DIR__ . '/../includes/og-twitter-meta.php');
    include(__DIR__ . '/../includes/json-ld-softwareapp.php');
    ?>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
      .mode-toggle {
        display: inline-flex;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 24px;
      }
      .mode-toggle button {
        border: 0;
        background: #f8fafc;
        color: #334155;
        padding: 10px 18px;
        font-weight: 600;
        cursor: pointer;
      }
      .mode-toggle button.active {
        background: #1d4ed8;
        color: #fff;
      }
      .advanced-only { display: none; }
      body.mode-advanced .advanced-only { display: block; }
      .earnings-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px;
        margin-bottom: 8px;
        align-items: end;
      }
      details.how-estimated {
        margin: 24px 0;
        padding: 16px 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
      }
      details.how-estimated summary {
        cursor: pointer;
        font-weight: 600;
        color: #1e293b;
      }
      .deep-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
      }
      .deep-links a {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95em;
      }
      .deep-links a:hover { background: #dbeafe; }
      .summary-sub {
        margin-top: 8px;
        font-size: 0.85em;
        opacity: 0.9;
        line-height: 1.35;
      }
    </style>
</head>
<body>

    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Early Exit Social Security Impact</h1>
            <p class="sub">See how stopping work before you planned can lower the Social Security benefit your statement assumes</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Why early exit can shrink your benefit</h2>
            <p>Many SSA estimates assume you keep earning near your current salary until your planned claiming age. If you retire or are laid off earlier, fewer high-earning years may enter your highest 35 years of indexed earnings — so your actual benefit can be lower even if you claim at the same age.</p>
            <p style="margin-top: 12px;">This calculator estimates that reduction. It is educational, not an official SSA projection. Related tools:
                <a href="../social-security-claiming-analyzer/">Claiming Analyzer</a> ·
                <a href="../ss-gap/">Spending Gap</a> ·
                <a href="../ss-survivor-impact/">Survivor Impact</a>
            </p>
        </div>

<?php if ($isPremium): ?>
        <div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
                <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
                <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
                <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with chart and scenario table (PDF)">📄 Download PDF</button>
                <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Scenario comparison data for Excel">📊 Export CSV</button>
                <button type="button" id="downloadSummaryBtn" class="btn-primary" style="background: #805ad5; color: white;" title="One-page PDF: reduction, nest egg, takeaway">📋 Impact Summary</button>
                <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
            </div>
            <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
                <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios.
                <strong>Compare</strong> — Side-by-side of two saved runs.
                <strong>PDF</strong> — Full report with chart.
                <strong>CSV</strong> — Spreadsheet data.
                <strong>Summary</strong> — One-page impact guide.
                <strong>Explain</strong> — AI explains your results after you calculate.
            </p>
        </div>
<?php endif; ?>

        <div class="mode-toggle" role="group" aria-label="Estimate mode">
            <button type="button" id="modeQuickBtn" class="active">Quick Estimate</button>
            <button type="button" id="modeAdvancedBtn">Advanced</button>
        </div>

        <form id="earlyExitForm">
            <input type="hidden" id="mode" value="quick">

            <h3>Your timeline</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="birthDateMonth" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Birth Date</label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <select id="birthDateMonth" style="flex: 1; min-width: 80px; padding: 8px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>"<?php echo $m === 1 ? ' selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="birthDateDay" style="flex: 1; min-width: 60px; padding: 8px;">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo $d; ?>"<?php echo $d === 15 ? ' selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="birthDateYear" style="flex: 1; min-width: 80px; padding: 8px;">
                            <?php for ($y = (int)date('Y'); $y >= 1920; $y--): ?>
                            <option value="<?php echo $y; ?>"<?php echo $y === 1965 ? ' selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" id="birthDate" value="1965-01-15" required>
                    <small style="color: #666;">Used for Full Retirement Age and eligibility-year bend points. Current age: <span id="currentAgeLabel">—</span></small>
                </div>
                <div>
                    <label for="plannedRetirementAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Planned retirement age</label>
                    <input type="number" id="plannedRetirementAge" min="50" max="75" value="67" required style="width: 100%;">
                    <small style="color: #666;">When you originally expected to stop working</small>
                </div>
                <div>
                    <label for="actualStopAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Actual stop age</label>
                    <input type="number" id="actualStopAge" min="50" max="75" value="62" required style="width: 100%;">
                    <small style="color: #666;">When work actually stops (retire / layoff)</small>
                </div>
                <div>
                    <label for="claimingAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Claiming age</label>
                    <input type="number" id="claimingAge" min="62" max="70" value="67" required style="width: 100%;">
                    <small style="color: #666;">Held constant across scenarios (62–70)</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Earnings &amp; SSA statement</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="currentAnnualEarnings" style="display: block; margin-bottom: 5px; font-weight: 600;">Current annual earnings ($)</label>
                    <input type="number" id="currentAnnualEarnings" min="0" step="any" value="95000" required style="width: 100%;">
                    <small style="color: #666;">Gross earnings; model caps at Social Security taxable maximum</small>
                </div>
                <div>
                    <label for="earningsGrowthRatePct" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected earnings growth (%)</label>
                    <input type="number" id="earningsGrowthRatePct" min="0" max="10" step="any" value="2" style="width: 100%;">
                    <small style="color: #666;">Optional; applied from now until each stop age</small>
                </div>
                <div>
                    <label for="ssaBenefitMonthly" style="display: block; margin-bottom: 5px; font-weight: 600;">SSA estimated benefit ($/month)</label>
                    <input type="number" id="ssaBenefitMonthly" min="0" step="any" value="2800" style="width: 100%;">
                    <small style="color: #666;">Optional but recommended — from your SSA statement</small>
                </div>
                <div>
                    <label for="ssaBenefitBasis" style="display: block; margin-bottom: 5px; font-weight: 600;">SSA amount represents</label>
                    <select id="ssaBenefitBasis" style="width: 100%; padding: 8px;">
                        <option value="fra" selected>Benefit at Full Retirement Age</option>
                        <option value="claimingAge">Benefit at my claiming age above</option>
                    </select>
                    <small style="color: #666;">FRA is preferred for calibration</small>
                </div>
            </div>

            <div class="advanced-only">
                <h3 style="margin-top: 30px;">Advanced earnings detail</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label for="yearsAlreadyWorked" style="display: block; margin-bottom: 5px; font-weight: 600;">Years already worked</label>
                        <input type="number" id="yearsAlreadyWorked" min="0" max="50" value="35" style="width: 100%;">
                        <small style="color: #666;">Years with Social Security–covered earnings so far</small>
                    </div>
                    <div>
                        <label for="historicalEarningsRatio" style="display: block; margin-bottom: 5px; font-weight: 600;">Historical earnings ratio (%)</label>
                        <input type="number" id="historicalEarningsRatio" min="20" max="100" value="65" style="width: 100%;">
                        <small style="color: #666;">Past top-35 years as % of today’s earnings (default 65%)</small>
                    </div>
                    <div>
                        <label for="postStopAnnualEarnings" style="display: block; margin-bottom: 5px; font-weight: 600;">Earnings after stop age ($/year)</label>
                        <input type="number" id="postStopAnnualEarnings" min="0" step="any" value="0" style="width: 100%;">
                        <small style="color: #666;">Part-time / consulting until claiming (optional)</small>
                    </div>
                </div>

                <h4 style="margin: 10px 0 8px;">Optional recent annual earnings</h4>
                <p style="color: #666; font-size: 0.95em; margin: 0 0 12px;">Enter recent years to improve the estimate. Leave blank to use the historical ratio only.</p>
                <div id="pastEarningsList"></div>
                <button type="button" id="addEarningsYearBtn" class="btn-secondary" style="margin-bottom: 20px;">+ Add year</button>
            </div>

            <h3 style="margin-top: 30px;">Assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="lifeExpectancy" style="display: block; margin-bottom: 5px; font-weight: 600;">Life expectancy (age)</label>
                    <input type="number" id="lifeExpectancy" min="62" max="100" value="85" required style="width: 100%;">
                    <small style="color: #666;">For lifetime benefit comparison</small>
                </div>
                <div>
                    <label for="colaRatePct" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual COLA (%)</label>
                    <input type="number" id="colaRatePct" min="0" max="10" step="any" value="2.5" required style="width: 100%;">
                    <small style="color: #666;">Long-run average is roughly 2.5–2.6%</small>
                </div>
                <div>
                    <label for="withdrawalRatePct" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawal rate for nest egg (%)</label>
                    <input type="number" id="withdrawalRatePct" min="2" max="6" step="any" value="4" required style="width: 100%;">
                    <small style="color: #666;">Used to translate the monthly cut into extra savings needed</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Estimate Impact</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Early-Exit Impact</h2>

            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Monthly Benefit by Work-Stop Scenario</h3>
                <p style="color: #666; margin-top: -8px; margin-bottom: 12px;">Same claiming age; only the age you stop working changes.</p>
                <div class="chart-wrapper" style="height: 320px;">
                    <canvas id="scenarioBarChart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3>Where the Reduction Comes From</h3>
                <div class="chart-wrapper" style="height: 280px;">
                    <canvas id="waterfallChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <?php if ($isPremium): ?>
            <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
            </div>
            <?php endif; ?>

            <details class="how-estimated">
                <summary>How we estimated this</summary>
                <div id="howEstimated" style="margin-top: 12px; color: #475569; line-height: 1.55;"></div>
            </details>

            <div class="table-section">
                <h3>Scenario comparison</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Stop age</th>
                                <th>Est. PIA at FRA</th>
                                <th>Benefit at claim</th>
                                <th>vs Plan ($/mo)</th>
                                <th>Extra nest egg</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>

            <?php
            $share_title = 'Early Exit Social Security Impact';
            $share_text = 'Check out the Early Exit Social Security Impact calculator at ronbelisle.com — see how stopping work early can lower your SSA estimate.';
            include(__DIR__ . '/../includes/share-results-block.php');
            ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Save, Compare & Export Early-Exit Scenarios';
        $premium_upsell_text = 'Upgrade to Premium to save scenarios, compare runs, download PDF/CSV reports, and get AI-generated plain-language explanations of your specific results.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script>
      window.isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script src="../js/explain-results-modal.js"></script>
    <script>
    (function() {
        var month = document.getElementById('birthDateMonth');
        var day = document.getElementById('birthDateDay');
        var year = document.getElementById('birthDateYear');
        var hidden = document.getElementById('birthDate');
        function daysInMonth(m, y) {
            return new Date(parseInt(y, 10), parseInt(m, 10), 0).getDate();
        }
        function ageFromBirth(yyyyMmDd) {
            var parts = String(yyyyMmDd).substr(0, 10).split('-');
            if (parts.length !== 3) return null;
            var b = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            var today = new Date();
            var age = today.getFullYear() - b.getFullYear();
            var m = today.getMonth() - b.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
            return age;
        }
        function syncBirthDate() {
            var m = month.value;
            var y = year.value;
            var maxDay = daysInMonth(m, y);
            var d = Math.min(parseInt(day.value, 10), maxDay);
            if (parseInt(day.value, 10) > maxDay) day.value = d;
            hidden.value = y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            var age = ageFromBirth(hidden.value);
            var label = document.getElementById('currentAgeLabel');
            if (label) label.textContent = age !== null ? age : '—';
            if (typeof window.onBirthDateSynced === 'function') window.onBirthDateSynced(hidden.value, age);
        }
        if (month && day && year && hidden) {
            month.addEventListener('change', syncBirthDate);
            day.addEventListener('change', syncBirthDate);
            year.addEventListener('change', syncBirthDate);
            syncBirthDate();
        }
        window.setBirthDateFromString = function(yyyyMmDd) {
            if (!yyyyMmDd || !month || !day || !year) return;
            var parts = String(yyyyMmDd).substr(0, 10).split('-');
            if (parts.length !== 3) return;
            var y = parseInt(parts[0], 10), m = parseInt(parts[1], 10), d = parseInt(parts[2], 10);
            if (!y || !m || !d) return;
            year.value = y;
            month.value = m;
            var maxDay = new Date(y, m, 0).getDate();
            day.value = Math.min(d, maxDay);
            syncBirthDate();
        };
    })();
    </script>
    <script src="../js/lib/url-prefill.js"></script>
    <script src="calculator.js"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
