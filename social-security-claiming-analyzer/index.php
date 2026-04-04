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
    <meta name="description" content="Compare Social Security claiming strategies. See how filing at 62 vs 70 affects lifetime benefits and break-even ages.">
    <title>Social Security Claiming Analyzer</title>
    <?php $og_title = $ld_name = 'Social Security Claiming Analyzer'; $og_description = $ld_description = 'Compare Social Security claiming strategies. See how filing at 62 vs 70 affects lifetime benefits and break-even ages.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Social Security Claiming Analyzer</h1>
            <p class="sub">Compare different Social Security claiming ages and visualize how your lifetime benefits change based on when you start collecting</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Social Security Claiming Decisions</h2>
            <p>You can claim Social Security retirement benefits as early as age 62 or as late as age 70. Claiming early means smaller monthly checks but more total payments. Claiming later means larger monthly checks but fewer total payments. This calculator helps you understand the trade-offs and find the "break-even" age where total lifetime benefits become equal.</p>
        </div>

<?php if ($isPremium): ?>
        <div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
                <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
                <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
                <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with charts and year-by-year table (PDF)">📄 Download PDF</button>
                <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year benefit data for Excel or spreadsheets">📊 Export CSV</button>
                <button type="button" id="downloadSummaryBtn" class="btn-primary" style="background: #805ad5; color: white;" title="One-page PDF: FRA, monthly benefits, break-even ages">📋 Claiming Summary</button>
                <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
            </div>
            <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
                <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data. <strong>Summary</strong> — One-page claiming decision guide. <strong>Explain</strong> — AI explains your results in plain language.
            </p>
        </div>
<?php endif; ?>

        <form id="ssForm">
            <h3>Your Information</h3>
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
                            <option value="<?php echo $y; ?>"<?php echo $y === 1960 ? ' selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" id="birthDate" value="1960-01-15" required>
                    <small style="color: #666;">Used to calculate your Full Retirement Age (FRA). Pick month, day, and year—no scrolling.</small>
                </div>
                <div>
                    <label for="monthlyPIA" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly Benefit at Full Retirement Age ($)</label>
                    <input type="number" id="monthlyPIA" min="0" step="1" value="3000" required style="width: 100%;">
                    <small style="color: #666;">From your Social Security statement (before Medicare)</small>
                </div>
                <div>
                    <label for="lifeExpectancy" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Life Expectancy (Age)</label>
                    <input type="number" id="lifeExpectancy" min="62" max="100" value="85" required style="width: 100%;">
                    <small style="color: #666;">Average is 78 for men, 82 for women</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Claiming Scenarios to Compare</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="claimAgeA" style="display: block; margin-bottom: 5px; font-weight: 600;">Scenario A: Claim at Age</label>
                    <input type="number" id="claimAgeA" min="62" max="70" value="62" required style="width: 100%;">
                    <small style="color: #666;">Earliest: 62</small>
                </div>
                <div>
                    <label for="claimAgeB" style="display: block; margin-bottom: 5px; font-weight: 600;">Scenario B: Claim at Age</label>
                    <input type="number" id="claimAgeB" min="62" max="70" value="67" required style="width: 100%;">
                    <small style="color: #666;">Your FRA (typically 66-67)</small>
                </div>
                <div>
                    <label for="claimAgeC" style="display: block; margin-bottom: 5px; font-weight: 600;">Scenario C: Claim at Age</label>
                    <input type="number" id="claimAgeC" min="62" max="70" value="70" required style="width: 100%;">
                    <small style="color: #666;">Latest: 70 (max benefits)</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="colaRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual COLA Increase (%)</label>
                    <input type="number" id="colaRate" min="0" max="10" step="0.1" value="2.5" required style="width: 100%;">
                    <small style="color: #666;">30-year average is ~2.6%</small>
                </div>
                <div>
                    <label for="discountRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Discount Rate (%) - Optional</label>
                    <input type="number" id="discountRate" min="0" max="10" step="0.1" value="0" style="width: 100%;">
                    <small style="color: #666;">0 = nominal dollars, 3-4% = present value</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Compare Scenarios</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Social Security Comparison</h2>
            
            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Cumulative Lifetime Benefits</h3>
                <div class="chart-wrapper">
                    <canvas id="lifetimeBenefitsChart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3>Monthly Benefit Amount by Claiming Age</h3>
                <div class="chart-wrapper" style="height: 300px;">
                    <canvas id="monthlyBenefitsChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <?php if ($isPremium): ?>
            <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
                <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
            </div>
            <?php endif; ?>

            <div class="table-section">
                <h3>Year-by-Year Comparison</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr id="tableHeader"></tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>
            <?php $share_title = 'Social Security Claiming Analyzer'; $share_text = 'Check out the Social Security Claiming Analyzer at ronbelisle.com — compare claiming ages and see lifetime benefits.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Save & Compare Claiming Strategies';
        $premium_upsell_text = 'Upgrade to Premium to save scenarios, compare claiming strategies, and get AI-generated plain-language explanations of your specific results.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script>
    (function() {
        var month = document.getElementById('birthDateMonth');
        var day = document.getElementById('birthDateDay');
        var year = document.getElementById('birthDateYear');
        var hidden = document.getElementById('birthDate');
        function daysInMonth(m, y) {
            return new Date(parseInt(y, 10), parseInt(m, 10), 0).getDate();
        }
        function syncBirthDate() {
            var m = month.value;
            var y = year.value;
            var maxDay = daysInMonth(m, y);
            var d = Math.min(parseInt(day.value, 10), maxDay);
            if (parseInt(day.value, 10) > maxDay) day.value = d;
            hidden.value = y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
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
    <script src="calculator.js"></script>
</body>
</html>