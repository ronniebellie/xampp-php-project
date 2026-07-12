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
    <meta name="description" content="See how required minimum distributions (RMDs) affect your retirement income and taxes. Model RMD schedules and Roth conversions.">
    <title>RMD Impact Calculator</title>
    <?php $og_title = $ld_name = 'RMD Impact Calculator'; $og_description = $ld_description = 'See how required minimum distributions (RMDs) affect your retirement income and taxes. Model RMD schedules and Roth conversions.'; include(__DIR__ . '/../includes/og-twitter-meta.php'); include(__DIR__ . '/../includes/json-ld-softwareapp.php'); ?>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>RMD Impact Calculator</h1>
            <p class="sub">Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding RMDs</h2>
            <p>Required Minimum Distributions (RMDs) force you to withdraw a percentage of your tax-deferred retirement accounts starting at age 73. Many retirees also take planned withdrawals from their IRA or 401(k) long before RMDs begin to fund living expenses — this calculator lets you model those withdrawals and see how they affect your future RMDs and tax brackets. Once RMDs start, any traditional withdrawal you already take counts toward satisfying the RMD for that year.</p>
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
        <button type="button" id="downloadCalendarBtn" class="btn-primary" style="background: #805ad5; color: white;" title="One-page PDF of RMD due dates (next 10–15 years)">📅 RMD Calendar</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with chart. <strong>CSV</strong> — Spreadsheet data. <strong>Calendar</strong> — One-page RMD due dates. <strong>Explain</strong> — AI explains your results in plain language.
    </p>
</div>
<?php endif; ?>

        <form id="rmdForm">
            <h3>Your Current Situation</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Current Age</label>
                    <input type="number" id="currentAge" min="50" max="100" value="68" required style="width: 100%;">
                    <small style="color: #666;">Enter your age today</small>
                </div>
                <div>
                    <label for="accountBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Tax-Deferred Account Balance (as of 12/31 last year) ($)</label>
                    <input type="number" id="accountBalance" min="0" step="any" value="1100000" required style="width: 100%;">
                    <small style="color: #666;">Traditional IRA, 401(k), etc. - exclude Roth accounts</small>
                </div>
                <div>
                    <label for="growthRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Growth Rate (%)</label>
                    <input type="number" id="growthRate" min="0" max="20" step="any" value="7" required style="width: 100%;">
                    <small style="color: #666;">Typical range: 5-8% for diversified portfolios</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="spouseBeneficiary" style="display: block; margin-bottom: 5px; font-weight: 600;">Is your spouse the sole beneficiary?</label>
                    <select id="spouseBeneficiary" onchange="toggleSpouseAge()" style="width: 100%;">
                        <option value="no">No</option>
                        <option value="yes" selected>Yes</option>
                    </select>
                    <small style="color: #666;">Used to determine which IRS life expectancy table applies</small>
                </div>
                <div id="spouseAgeGroup" style="display: block;">
                    <label for="spouseAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Spouse's Current Age</label>
                    <input type="number" id="spouseAge" min="18" max="100" value="68" style="width: 100%;">
                    <small style="color: #666;">Only needed if spouse is more than 10 years younger</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Planned Portfolio Withdrawals <span style="font-weight: 400; font-size: 0.85em; color: #666;">(optional)</span></h3>
            <p style="color: #555; font-size: 0.95em; margin: 0 0 15px 0; line-height: 1.5;">If you are already withdrawing from retirement accounts to cover living expenses, enter those withdrawals here. Withdrawals from a traditional IRA/401(k) reduce your tax-deferred balance and lower future RMDs. After age 73, traditional withdrawals count toward your RMD — only the shortfall (if any) is added on top.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label for="enableWithdrawals" style="display: block; margin-bottom: 5px; font-weight: 600;">Include planned withdrawals?</label>
                    <select id="enableWithdrawals" onchange="toggleWithdrawalFields()" style="width: 100%;">
                        <option value="no" selected>No — account untouched until RMDs</option>
                        <option value="yes">Yes — I take regular withdrawals</option>
                    </select>
                </div>
            </div>

            <div id="withdrawalFieldsGroup" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="withdrawalAmount" style="display: block; margin-bottom: 5px; font-weight: 600;">Planned Annual Withdrawal ($)</label>
                        <input type="number" id="withdrawalAmount" min="0" step="any" value="80000" style="width: 100%;">
                        <small style="color: #666;">Total from all sources combined</small>
                    </div>
                    <div>
                        <label for="withdrawalEndAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawals continue until age</label>
                        <input type="number" id="withdrawalEndAge" min="50" max="100" value="100" style="width: 100%;">
                        <small style="color: #666;">Leave at 100 to model ongoing withdrawals</small>
                    </div>
                </div>

                <?php $planYear = (int)date('Y'); $defaultStartYear = $planYear + 1; ?>
                <input type="hidden" id="planStartYear" value="<?php echo $planYear; ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 10px;">
                    <div>
                        <label for="withdrawalStartMode" style="display: block; margin-bottom: 5px; font-weight: 600;">When do withdrawals begin?</label>
                        <select id="withdrawalStartMode" onchange="toggleWithdrawalStartMode()" style="width: 100%;">
                            <option value="age">At a specific age</option>
                            <option value="date" selected>On a calendar date</option>
                        </select>
                    </div>
                </div>

                <div id="withdrawalStartAgeGroup" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="withdrawalStartAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Begin at age</label>
                            <input type="number" id="withdrawalStartAge" min="50" max="100" value="68" style="width: 100%;">
                            <small style="color: #666;">Usually your current age if already withdrawing</small>
                        </div>
                    </div>
                </div>

                <div id="withdrawalStartDateGroup" style="display: block;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="withdrawalStartMonth" style="display: block; margin-bottom: 5px; font-weight: 600;">Begin in month</label>
                            <select id="withdrawalStartMonth" style="width: 100%;">
                                <option value="1" selected>January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div>
                            <label for="withdrawalStartYear" style="display: block; margin-bottom: 5px; font-weight: 600;">Begin in year</label>
                            <input type="number" id="withdrawalStartYear" min="<?php echo $planYear; ?>" max="2100" step="1" value="<?php echo $defaultStartYear; ?>" style="width: 100%;">
                            <small style="color: #666;">e.g. January <?php echo $defaultStartYear; ?> if starting next year</small>
                        </div>
                    </div>
                    <p id="withdrawalStartDateHint" style="margin: 0 0 15px 0; font-size: 0.9em; color: #555; line-height: 1.4;"></p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="withdrawalSource" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawal source</label>
                        <select id="withdrawalSource" onchange="toggleWithdrawalSource()" style="width: 100%;">
                            <option value="traditional" selected>Traditional IRA / 401(k)</option>
                            <option value="roth">Roth IRA / Roth 401(k)</option>
                            <option value="taxable">Taxable brokerage account</option>
                            <option value="combination">Combination (split across accounts)</option>
                        </select>
                        <small style="color: #666;">Only traditional withdrawals reduce future RMDs</small>
                    </div>
                    <div>
                        <label for="withdrawalInflation" style="display: block; margin-bottom: 5px; font-weight: 600;">Increase withdrawals for inflation?</label>
                        <select id="withdrawalInflation" onchange="toggleInflationRate()" style="width: 100%;">
                            <option value="no" selected>No — fixed dollar amount</option>
                            <option value="yes">Yes — grow with inflation</option>
                        </select>
                    </div>
                    <div id="withdrawalInflationRateGroup" style="display: none;">
                        <label for="withdrawalInflationRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual inflation rate (%)</label>
                        <input type="number" id="withdrawalInflationRate" min="0" max="10" step="any" value="2.5" style="width: 100%;">
                    </div>
                </div>

                <div id="rothBalanceGroup" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="rothBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Roth IRA / Roth 401(k) Balance ($)</label>
                            <input type="number" id="rothBalance" min="0" step="any" value="0" style="width: 100%;">
                            <small style="color: #666;">As of 12/31 last year — Roth withdrawals are tax-free</small>
                        </div>
                    </div>
                </div>

                <div id="taxableBalanceGroup" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="taxableBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Taxable Brokerage Balance ($)</label>
                            <input type="number" id="taxableBalance" min="0" step="any" value="0" style="width: 100%;">
                            <small style="color: #666;">As of 12/31 last year — capital gains taxed separately</small>
                        </div>
                    </div>
                </div>

                <div id="combinationPctGroup" style="display: none; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <p style="margin: 0 0 12px 0; font-weight: 600; font-size: 0.95em;">Split across accounts (must total 100%)</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                        <div>
                            <label for="pctTraditional" style="display: block; margin-bottom: 5px; font-weight: 600;">Traditional (%)</label>
                            <input type="number" id="pctTraditional" min="0" max="100" step="any" value="70" style="width: 100%;">
                        </div>
                        <div>
                            <label for="pctRoth" style="display: block; margin-bottom: 5px; font-weight: 600;">Roth (%)</label>
                            <input type="number" id="pctRoth" min="0" max="100" step="any" value="20" style="width: 100%;">
                        </div>
                        <div>
                            <label for="pctTaxable" style="display: block; margin-bottom: 5px; font-weight: 600;">Taxable (%)</label>
                            <input type="number" id="pctTaxable" min="0" max="100" step="any" value="10" style="width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Other Retirement Income</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="socialSecurity" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Social Security Benefits ($)</label>
                    <input type="number" id="socialSecurity" min="0" step="any" value="55000" style="width: 100%;">
                    <small style="color: #666;">Expected annual amount (0 if not yet claiming)</small>
                </div>
                <div>
                    <label for="pension" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Pension Income ($)</label>
                    <input type="number" id="pension" min="0" step="any" value="0" style="width: 100%;">
                    <small style="color: #666;">Include any pension or annuity income</small>
                </div>
                <div>
                    <label for="otherIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Other Taxable Income ($)</label>
                    <input type="number" id="otherIncome" min="0" step="any" value="0" style="width: 100%;">
                    <small style="color: #666;">Rental income, part-time work, etc.</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Tax Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="filingStatus" style="display: block; margin-bottom: 5px; font-weight: 600;">Tax Filing Status</label>
                    <select id="filingStatus" style="width: 100%;">
                        <option value="single">Single</option>
                        <option value="married" selected>Married Filing Jointly</option>
                        <option value="hoh">Head of Household</option>
                    </select>
                </div>
                <div>
                    <label for="standardDeduction" style="display: block; margin-bottom: 5px; font-weight: 600;">Use Standard Deduction?</label>
                    <select id="standardDeduction" style="width: 100%;">
                        <option value="yes" selected>Yes</option>
                        <option value="no">No (I itemize)</option>
                    </select>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate RMD Impact</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your RMD Projection</h2>
            
            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Traditional Balance, RMDs, and Withdrawals Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="rmdChart"></canvas>
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
                <h3>Year-by-Year Breakdown</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Age</th>
                                <th>Traditional Balance</th>
                                <th>Withdrawals</th>
                                <th>RMD Amount</th>
                                <th>Total Income</th>
                                <th>Est. Tax Bracket</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>

            <?php $share_title = 'RMD Impact Calculator'; $share_text = 'Check out the RMD Impact Calculator at ronbelisle.com — see how RMDs interact with your taxes and income.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDF and CSV, and get AI-generated plain-language explanations of your specific results.'; include(__DIR__ . '/../includes/premium-upsell-banner.php'); ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script src="../js/explain-results-modal.js"></script>
    <script>
    function toggleSpouseAge() {
        const spouseBeneficiary = document.getElementById('spouseBeneficiary').value;
        const spouseAgeGroup = document.getElementById('spouseAgeGroup');
        if (spouseBeneficiary === 'yes') {
            spouseAgeGroup.style.display = 'block';
        } else {
            spouseAgeGroup.style.display = 'none';
        }
    }

    function toggleWithdrawalFields() {
        const enabled = document.getElementById('enableWithdrawals').value === 'yes';
        document.getElementById('withdrawalFieldsGroup').style.display = enabled ? 'block' : 'none';
        if (enabled) {
            toggleWithdrawalStartMode();
            toggleWithdrawalSource();
            toggleInflationRate();
            syncWithdrawalStartAge();
            updateWithdrawalStartDateHint();
        }
    }

    function toggleWithdrawalStartMode() {
        const mode = document.getElementById('withdrawalStartMode').value;
        document.getElementById('withdrawalStartAgeGroup').style.display = mode === 'age' ? 'block' : 'none';
        document.getElementById('withdrawalStartDateGroup').style.display = mode === 'date' ? 'block' : 'none';
        updateWithdrawalStartDateHint();
    }

    function updateWithdrawalStartDateHint() {
        const hint = document.getElementById('withdrawalStartDateHint');
        const modeEl = document.getElementById('withdrawalStartMode');
        if (!hint || !modeEl || modeEl.value !== 'date') return;

        const currentAge = parseInt(document.getElementById('currentAge').value, 10);
        const planYear = parseInt(document.getElementById('planStartYear').value, 10);
        const startYear = parseInt(document.getElementById('withdrawalStartYear').value, 10);
        const monthSelect = document.getElementById('withdrawalStartMonth');
        const monthLabel = monthSelect ? monthSelect.options[monthSelect.selectedIndex].text : 'January';

        if (!currentAge || !planYear || !startYear) return;

        const effectiveAge = currentAge + (startYear - planYear);
        hint.textContent = monthLabel + ' ' + startYear + ' corresponds to about age ' + effectiveAge +
            ' in this projection (one row per age year, starting from your current age in ' + planYear + ').';
    }

    function toggleWithdrawalSource() {
        const source = document.getElementById('withdrawalSource').value;
        const showRoth = source === 'roth' || source === 'combination';
        const showTaxable = source === 'taxable' || source === 'combination';
        document.getElementById('rothBalanceGroup').style.display = showRoth ? 'block' : 'none';
        document.getElementById('taxableBalanceGroup').style.display = showTaxable ? 'block' : 'none';
        document.getElementById('combinationPctGroup').style.display = source === 'combination' ? 'block' : 'none';
    }

    function toggleInflationRate() {
        const yes = document.getElementById('withdrawalInflation').value === 'yes';
        document.getElementById('withdrawalInflationRateGroup').style.display = yes ? 'block' : 'none';
    }

    function syncWithdrawalStartAge() {
        const currentAgeEl = document.getElementById('currentAge');
        const startEl = document.getElementById('withdrawalStartAge');
        if (currentAgeEl && startEl && !startEl.dataset.userEdited) {
            startEl.value = currentAgeEl.value;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const startEl = document.getElementById('withdrawalStartAge');
        const currentAgeEl = document.getElementById('currentAge');
        const startYearEl = document.getElementById('withdrawalStartYear');
        const startMonthEl = document.getElementById('withdrawalStartMonth');
        if (startEl) {
            startEl.addEventListener('input', function() { startEl.dataset.userEdited = '1'; });
        }
        if (currentAgeEl) {
            currentAgeEl.addEventListener('change', function() {
                syncWithdrawalStartAge();
                updateWithdrawalStartDateHint();
            });
        }
        if (startYearEl) startYearEl.addEventListener('input', updateWithdrawalStartDateHint);
        if (startMonthEl) startMonthEl.addEventListener('change', updateWithdrawalStartDateHint);
        updateWithdrawalStartDateHint();
    });
    </script>
    <script src="../js/compare-scenarios-modal.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
</script>
<script src="calculator.js"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>