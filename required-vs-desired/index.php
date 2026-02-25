<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isPremium = false;
if ($isLoggedIn) {
    require_once '../includes/db_config.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $isPremium = ($user['subscription_status'] === 'premium');
}
// Don't close PHP yet - keep variables in scope
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Required vs. Desired Spending Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Required vs. Desired Spending Calculator</h1>
            <p class="sub">Separate essential needs from discretionary wants to determine your true retirement portfolio needs</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>How This Calculator Helps</h2>
            <p>Understanding the difference between <strong>required</strong> (essential) and <strong>desired</strong> (discretionary) spending helps you plan for a secure retirement. Required expenses are non-negotiable (housing, food, healthcare), while desired expenses enhance your lifestyle (travel, hobbies, dining out). This calculator shows you two scenarios: the minimum portfolio for essentials only, and the ideal portfolio for your full desired lifestyle.</p>
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
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data.
    </p>
</div>
<?php endif; ?>

        <form id="calculatorForm">
            <h3>Your Retirement Spending</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="required-annual" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Required Expenses (Today's Dollars) ($)</label>
                    <input type="number" id="required-annual" value="48000" min="0" step="1000" style="width: 100%;">
                    <small style="color: #666;">Essentials: housing, food, utilities, insurance, healthcare, property taxes</small>
                </div>
                <div>
                    <label for="desired-annual" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Desired Expenses (Today's Dollars) ($)</label>
                    <input type="number" id="desired-annual" value="24000" min="0" step="1000" style="width: 100%;">
                    <small style="color: #666;">Discretionary: travel, hobbies, entertainment, dining out, gifts</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="ss-income" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Social Security Income ($)</label>
                    <input type="number" id="ss-income" value="36000" min="0" step="1000" style="width: 100%;">
                    <small style="color: #666;">Combined annual Social Security benefits (you + spouse)</small>
                </div>
                <div>
                    <label for="current-age" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Age</label>
                    <input type="number" id="current-age" value="65" min="50" max="100" style="width: 100%;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="life-expectancy" style="display: block; margin-bottom: 5px; font-weight: 600;">Life Expectancy</label>
                    <input type="number" id="life-expectancy" value="95" min="60" max="120" style="width: 100%;">
                    <small style="color: #666;">Plan to this age for a secure retirement</small>
                </div>
                <div>
                    <label for="inflation-rate" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Inflation Rate (%)</label>
                    <input type="number" id="inflation-rate" value="3.0" min="0" max="10" step="0.1" style="width: 100%;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="withdrawal-rate" style="display: block; margin-bottom: 5px; font-weight: 600;">Portfolio Withdrawal Rate (%)</label>
                    <input type="number" id="withdrawal-rate" value="4.0" min="2" max="6" step="0.5" style="width: 100%;">
                    <small style="color: #666;">Typical range: 3-5% (4% is traditional rule of thumb)</small>
                </div>
                <div>
                    <label for="portfolio-return" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Portfolio Return (%)</label>
                    <input type="number" id="portfolio-return" value="6.0" min="0" max="15" step="0.5" style="width: 100%;">
                    <small style="color: #666;">Conservative: 5-6%, Moderate: 6-8%, Aggressive: 8-10%</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="button" class="button" onclick="calculate()" style="font-size: 1.1em; padding: 12px 30px;">Calculate Portfolio Needs</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Results</h2>

            <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; opacity: 0.9; font-weight: 600;">Essential Needs Only</h3>
                    <div id="essential-portfolio" style="font-size: 36px; font-weight: 800; margin: 10px 0; line-height: 1;">$0</div>
                    <div id="essential-gap" style="font-size: 14px; opacity: 0.85; margin-top: 8px;">Annual gap: $0</div>
                    <div id="essential-years" style="font-size: 14px; opacity: 0.85; margin-top: 4px;">0 years of retirement</div>
                </div>

                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; opacity: 0.9; font-weight: 600;">Full Desired Lifestyle</h3>
                    <div id="full-portfolio" style="font-size: 36px; font-weight: 800; margin: 10px 0; line-height: 1;">$0</div>
                    <div id="full-gap" style="font-size: 14px; opacity: 0.85; margin-top: 8px;">Annual gap: $0</div>
                    <div id="full-years" style="font-size: 14px; opacity: 0.85; margin-top: 4px;">0 years of retirement</div>
                </div>
            </div>

            <div class="table-section">
                <h3>Portfolio Needed at Different Withdrawal Rates</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Withdrawal Rate</th>
                                <th>Essential Needs Portfolio</th>
                                <th>Full Lifestyle Portfolio</th>
                                <th>Difference</th>
                            </tr>
                        </thead>
                        <tbody id="comparison-tbody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-section">
                <h3>Portfolio Balance Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="balance-chart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3>Annual Withdrawals Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="withdrawal-chart"></canvas>
                </div>
            </div>

            <div class="table-section">
                <h3>Year-by-Year Projection</h3>
                <p style="color: #666; margin-bottom: 20px;">See how your portfolio and expenses change over time with inflation.</p>
                
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Age</th>
                                <th>Required</th>
                                <th>Desired</th>
                                <th>Total</th>
                                <th>SS Income</th>
                                <th>Withdrawal</th>
                                <th>Essential Balance</th>
                                <th>Full Balance</th>
                            </tr>
                        </thead>
                        <tbody id="projection-tbody-free">
                        </tbody>
                    </table>
                </div>
            </div>
            <?php $share_title = 'Required vs. Desired Portfolio Calculator'; $share_text = 'Check out the Required vs. Desired calculator at ronbelisle.com — see how much portfolio you need for essential needs vs. full lifestyle.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php include(__DIR__ . '/../includes/premium-upsell-banner.php'); ?>
        <footer class="site-footer">
            <span class="donate-text">If these tools are useful, please consider supporting future development.</span>
            <a href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" class="donate-btn">
                <span class="donate-dot"></span>
                Donate
            </a>
        </footer>
        <?php endif; ?>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../js/share-results.js"></script>
    <script>
        const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
        const RVD_API_BASE = (function() {
            const path = window.location.pathname;
            const match = path.match(/^(.*\/)required-vs-desired\/?/);
            const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
            return window.location.origin + basePath;
        })();
        let balanceChart = null;
        let withdrawalChart = null;

        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        function calculate() {
            // Get input values
            const requiredAnnual = parseFloat(document.getElementById('required-annual').value);
            const desiredAnnual = parseFloat(document.getElementById('desired-annual').value);
            const ssIncome = parseFloat(document.getElementById('ss-income').value);
            const currentAge = parseInt(document.getElementById('current-age').value);
            const lifeExpectancy = parseInt(document.getElementById('life-expectancy').value);
            const inflationRate = parseFloat(document.getElementById('inflation-rate').value) / 100;
            const withdrawalRate = parseFloat(document.getElementById('withdrawal-rate').value) / 100;
            const portfolioReturn = parseFloat(document.getElementById('portfolio-return').value) / 100;

            const years = lifeExpectancy - currentAge;
            const totalAnnual = requiredAnnual + desiredAnnual;

            // Calculate annual gaps (first year)
            const essentialGap = Math.max(0, requiredAnnual - ssIncome);
            const fullGap = Math.max(0, totalAnnual - ssIncome);

            // Calculate portfolio needed
            const essentialPortfolio = essentialGap / withdrawalRate;
            const fullPortfolio = fullGap / withdrawalRate;

            // Update summary cards
            document.getElementById('essential-portfolio').textContent = formatCurrency(essentialPortfolio);
            document.getElementById('essential-gap').textContent = `Annual gap: ${formatCurrency(essentialGap)}`;
            document.getElementById('essential-years').textContent = `${years} years of retirement`;

            document.getElementById('full-portfolio').textContent = formatCurrency(fullPortfolio);
            document.getElementById('full-gap').textContent = `Annual gap: ${formatCurrency(fullGap)}`;
            document.getElementById('full-years').textContent = `${years} years of retirement`;

            // Generate comparison table
            const withdrawalRates = [0.03, 0.035, 0.04, 0.045, 0.05];
            const comparisonTbody = document.getElementById('comparison-tbody');
            comparisonTbody.innerHTML = '';

            withdrawalRates.forEach(rate => {
                const essPort = essentialGap / rate;
                const fullPort = fullGap / rate;
                const diff = fullPort - essPort;

                const row = `
                    <tr>
                        <td>${(rate * 100).toFixed(1)}%</td>
                        <td>${formatCurrency(essPort)}</td>
                        <td>${formatCurrency(fullPort)}</td>
                        <td>${formatCurrency(diff)}</td>
                    </tr>
                `;
                comparisonTbody.innerHTML += row;
            });

            // Generate year-by-year projection (first 5 years for free)
            generateProjection(requiredAnnual, desiredAnnual, ssIncome, currentAge, years, 
                              inflationRate, portfolioReturn, essentialPortfolio, fullPortfolio);

            // Generate charts
            generateChart(requiredAnnual, desiredAnnual, ssIncome, currentAge, years,
                         inflationRate, portfolioReturn, essentialPortfolio, fullPortfolio);
            generateWithdrawalChart(requiredAnnual, desiredAnnual, ssIncome, currentAge, years, inflationRate);

            // Show results
            document.getElementById('results').style.display = 'block';
            document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function generateProjection(requiredAnnual, desiredAnnual, ssIncome, currentAge, years,
                                    inflationRate, portfolioReturn, essentialPortfolio, fullPortfolio) {
            const tbody = document.getElementById('projection-tbody-free');
            tbody.innerHTML = '';

            let essBalance = essentialPortfolio;
            let fullBalance = fullPortfolio;

            const isPremium = (typeof isPremiumUser !== 'undefined' && isPremiumUser);
            const freeYears = Math.min(5, years);
            const rowsToShow = isPremium ? years : freeYears;

            for (let i = 0; i < rowsToShow; i++) {
                const year = i + 1;
                const age = currentAge + i;
                
                // Inflate expenses
                const inflatedRequired = requiredAnnual * Math.pow(1 + inflationRate, i);
                const inflatedDesired = desiredAnnual * Math.pow(1 + inflationRate, i);
                const totalExpenses = inflatedRequired + inflatedDesired;
                
                // Calculate withdrawals
                const essWithdrawal = Math.max(0, inflatedRequired - ssIncome);
                const fullWithdrawal = Math.max(0, totalExpenses - ssIncome);
                
                // Update balances (beginning of year balance)
                const essBalanceStart = essBalance;
                const fullBalanceStart = fullBalance;
                
                // Apply withdrawal and growth
                essBalance = (essBalance - essWithdrawal) * (1 + portfolioReturn);
                fullBalance = (fullBalance - fullWithdrawal) * (1 + portfolioReturn);
                
                const row = `
                    <tr>
                        <td>Year ${year}</td>
                        <td>${age}</td>
                        <td>${formatCurrency(inflatedRequired)}</td>
                        <td>${formatCurrency(inflatedDesired)}</td>
                        <td>${formatCurrency(totalExpenses)}</td>
                        <td>${formatCurrency(ssIncome)}</td>
                        <td>${formatCurrency(fullWithdrawal)}</td>
                        <td>${formatCurrency(essBalanceStart)}</td>
                        <td>${formatCurrency(fullBalanceStart)}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            }

            // For non-premium users, add a blurred preview hinting at the full projection
            if (!isPremium && years > freeYears) {
                tbody.innerHTML += `
                    <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                        <td>Year 6</td>
                        <td>${currentAge + 5}</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XXX,XXX</td>
                        <td>$XXX,XXX</td>
                    </tr>
                    <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                        <td>Year 7</td>
                        <td>${currentAge + 6}</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XXX,XXX</td>
                        <td>$XXX,XXX</td>
                    </tr>
                    <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                        <td>Year 8</td>
                        <td>${currentAge + 7}</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XX,XXX</td>
                        <td>$XXX,XXX</td>
                        <td>$XXX,XXX</td>
                    </tr>
                `;
            }
        }

        function generateChart(requiredAnnual, desiredAnnual, ssIncome, currentAge, years,
                              inflationRate, portfolioReturn, essentialPortfolio, fullPortfolio) {
            const labels = [];
            const essentialData = [];
            const fullData = [];

            let essBalance = essentialPortfolio;
            let fullBalance = fullPortfolio;

            for (let i = 0; i <= years; i++) {
                labels.push(currentAge + i);
                essentialData.push(essBalance);
                fullData.push(fullBalance);

                if (i < years) {
                    const inflatedRequired = requiredAnnual * Math.pow(1 + inflationRate, i);
                    const inflatedTotal = (requiredAnnual + desiredAnnual) * Math.pow(1 + inflationRate, i);
                    
                    const essWithdrawal = Math.max(0, inflatedRequired - ssIncome);
                    const fullWithdrawal = Math.max(0, inflatedTotal - ssIncome);
                    
                    essBalance = (essBalance - essWithdrawal) * (1 + portfolioReturn);
                    fullBalance = (fullBalance - fullWithdrawal) * (1 + portfolioReturn);
                }
            }

            const ctx = document.getElementById('balance-chart');
            
            if (balanceChart) {
                balanceChart.destroy();
            }

            balanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Essential Needs Only',
                            data: essentialData,
                            borderColor: '#f5576c',
                            backgroundColor: 'rgba(245, 87, 108, 0.1)',
                            borderWidth: 3,
                            tension: 0.4
                        },
                        {
                            label: 'Full Desired Lifestyle',
                            data: fullData,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Portfolio Balance Over Time',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Age'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Portfolio Balance'
                            },
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        function generateWithdrawalChart(requiredAnnual, desiredAnnual, ssIncome, currentAge, years, inflationRate) {
            const labels = [];
            const essentialWithdrawals = [];
            const fullWithdrawals = [];
            const requiredExpenses = [];
            const desiredExpenses = [];

            for (let i = 0; i <= years; i++) {
                const age = currentAge + i;
                labels.push(age);
                
                const inflatedRequired = requiredAnnual * Math.pow(1 + inflationRate, i);
                const inflatedDesired = desiredAnnual * Math.pow(1 + inflationRate, i);
                const totalExpenses = inflatedRequired + inflatedDesired;
                
                const essWithdrawal = Math.max(0, inflatedRequired - ssIncome);
                const fullWithdrawal = Math.max(0, totalExpenses - ssIncome);
                
                essentialWithdrawals.push(essWithdrawal);
                fullWithdrawals.push(fullWithdrawal);
                requiredExpenses.push(inflatedRequired);
                desiredExpenses.push(inflatedDesired);
            }

            const ctx = document.getElementById('withdrawal-chart');
            
            if (withdrawalChart) {
                withdrawalChart.destroy();
            }

            withdrawalChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Required Expenses',
                            data: requiredExpenses,
                            borderColor: '#f5576c',
                            backgroundColor: 'rgba(245, 87, 108, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Essential Withdrawals (Required - SS)',
                            data: essentialWithdrawals,
                            borderColor: '#e53e3e',
                            backgroundColor: 'rgba(229, 62, 62, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Total Expenses (Required + Desired)',
                            data: requiredExpenses.map((r, i) => r + desiredExpenses[i]),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.05)',
                            borderWidth: 2,
                            tension: 0.4,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Full Withdrawals (Total - SS)',
                            data: fullWithdrawals,
                            borderColor: '#4c51bf',
                            backgroundColor: 'rgba(76, 81, 191, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Annual Spending and Withdrawals Over Time',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Age'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Annual Amount'
                            },
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Premium Save/Load/Compare/PDF/CSV
        document.addEventListener('DOMContentLoaded', function() {
            const saveBtn = document.getElementById('saveScenarioBtn');
            const loadBtn = document.getElementById('loadScenarioBtn');
            const compareBtn = document.getElementById('compareScenariosBtn');
            const pdfBtn = document.getElementById('downloadPdfBtn');
            const csvBtn = document.getElementById('downloadCsvBtn');
            if (saveBtn) saveBtn.addEventListener('click', saveScenario);
            if (loadBtn) loadBtn.addEventListener('click', loadScenario);
            if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
            if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
            if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
        });

        function saveScenario() {
            const scenarioName = prompt('Enter a name for this scenario:', 'My Spending Plan');
            if (!scenarioName) return;
            
            const formData = {
                requiredAnnual: document.getElementById('required-annual')?.value,
                desiredAnnual: document.getElementById('desired-annual')?.value,
                ssIncome: document.getElementById('ss-income')?.value,
                currentAge: document.getElementById('current-age')?.value,
                lifeExpectancy: document.getElementById('life-expectancy')?.value,
                inflationRate: document.getElementById('inflation-rate')?.value,
                withdrawalRate: document.getElementById('withdrawal-rate')?.value,
                portfolioReturn: document.getElementById('portfolio-return')?.value
            };
            
            fetch(RVD_API_BASE + 'api/save_scenario.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    calculator_type: 'required-vs-desired',
                    scenario_name: scenarioName,
                    scenario_data: formData
                })
            })
            .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text: text })))
            .then(({ ok, status, text }) => {
                let data;
                try { data = JSON.parse(text); } catch (_) { throw new Error(text || 'Server error'); }
                if (!ok) throw new Error(data.error || 'Save failed');
                return data;
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('saveStatus').textContent = '✓ Saved!';
                    setTimeout(() => { document.getElementById('saveStatus').textContent = ''; }, 3000);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => alert('Save scenario failed: ' + err.message));
        }

        function loadScenario() {
            fetch(RVD_API_BASE + 'api/load_scenarios.php?calculator_type=required-vs-desired')
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                if (data.scenarios.length === 0) {
                    alert('No saved scenarios yet. Save your first one!');
                    return;
                }
                
                let message = 'Select a scenario to load (or type "d" + number to delete):\n\n';
                data.scenarios.forEach((s, i) => {
                    message += `${i + 1}. ${s.name} (saved ${new Date(s.updated_at).toLocaleDateString()})\n`;
                });
                message += '\nExamples: Enter "1" to load, "d1" to delete';
                
                const choice = prompt(message + '\n\nEnter number or d+number:');
                if (!choice) return;
                
                if (choice.toLowerCase().startsWith('d')) {
                    const index = parseInt(choice.substring(1)) - 1;
                    if (index >= 0 && index < data.scenarios.length) {
                        const scenario = data.scenarios[index];
                        if (confirm(`Delete "${scenario.name}"? This cannot be undone.`)) {
                            fetch(RVD_API_BASE + 'api/delete_scenario.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ scenario_id: scenario.id })
                            })
                            .then(res => res.json())
                            .then(result => {
                                if (result.success) {
                                    alert('Scenario deleted!');
                                } else {
                                    alert('Error: ' + result.error);
                                }
                            });
                        }
                    }
                } else {
                    const index = parseInt(choice) - 1;
                    if (index >= 0 && index < data.scenarios.length) {
                        const scenario = data.scenarios[index];
                        if (scenario.data.requiredAnnual) document.getElementById('required-annual').value = scenario.data.requiredAnnual;
                        if (scenario.data.desiredAnnual) document.getElementById('desired-annual').value = scenario.data.desiredAnnual;
                        if (scenario.data.ssIncome) document.getElementById('ss-income').value = scenario.data.ssIncome;
                        if (scenario.data.currentAge) document.getElementById('current-age').value = scenario.data.currentAge;
                        if (scenario.data.lifeExpectancy) document.getElementById('life-expectancy').value = scenario.data.lifeExpectancy;
                        if (scenario.data.inflationRate) document.getElementById('inflation-rate').value = scenario.data.inflationRate;
                        if (scenario.data.withdrawalRate) document.getElementById('withdrawal-rate').value = scenario.data.withdrawalRate;
                        if (scenario.data.portfolioReturn) document.getElementById('portfolio-return').value = scenario.data.portfolioReturn;
                        alert('Scenario loaded! Click Calculate to see results.');
                    }
                }
            });
        }

        function compareScenarios() {
            fetch(RVD_API_BASE + 'api/load_scenarios.php?calculator_type=required-vs-desired')
            .then(res => res.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + data.error); return; }
                if (data.scenarios.length < 2) {
                    alert('You need at least 2 saved scenarios to compare. Save more first!');
                    return;
                }
                alert('Compare feature: Load scenarios individually to see their results side-by-side.');
            })
            .catch(() => alert('Failed to load scenarios.'));
        }

        function downloadPDF() {
            alert('PDF download: Please run a calculation first, then use the PDF button.');
        }

        function downloadCSV() {
            alert('CSV export: Please run a calculation first, then use the CSV button.');
        }
    </script>
</body>
</html>