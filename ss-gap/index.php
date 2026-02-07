<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Security + Spending Gap Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Social Security + Spending Gap Calculator</h1>
            <p class="sub">See how Social Security reduces the portfolio you need by identifying your real retirement spending gap</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Your Spending Gap</h2>
            <p>Many retirees overestimate how much they need to save because they forget that Social Security will cover a significant portion of their spending. This calculator shows your actual "spending gap" - the difference between what you want to spend and what Social Security provides - and calculates the portfolio size needed to fill that gap using sustainable withdrawal rates.</p>
        </div>

        <form id="gapForm">
            <h3>Your Retirement Income & Expenses</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="targetSpending" style="display: block; margin-bottom: 5px; font-weight: 600;">Target Monthly Spending ($)</label>
                    <input type="number" id="targetSpending" step="100" value="8000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Your desired monthly retirement budget</small>
                </div>
                <div>
                    <label for="ssIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Social Security Monthly Income ($)</label>
                    <input type="number" id="ssIncome" step="100" value="3500" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Combined household Social Security benefits</small>
                </div>
                <div>
                    <label for="otherIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Other Monthly Income ($)</label>
                    <input type="number" id="otherIncome" step="100" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Pension, rental income, part-time work, etc.</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Withdrawal Rate Assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="withdrawalRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Starting Withdrawal Rate (%)</label>
                    <input type="number" id="withdrawalRate" step="0.1" min="0" max="10" value="4.0" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Common range: 3.5% - 5.0%</small>
                </div>
                <div>
                    <label for="filingStatus" style="display: block; margin-bottom: 5px; font-weight: 600;">Household Type</label>
                    <select id="filingStatus" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <option value="single">Single</option>
                        <option value="married" selected>Married</option>
                    </select>
                    <small style="color: #666;">For context in results</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate Gap</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Spending Gap Analysis</h2>
            
            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Portfolio Needed at Different Withdrawal Rates</h3>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="withdrawalChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <div class="table-section">
                <h3>Withdrawal Rate Comparison</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Withdrawal Rate</th>
                                <th>Portfolio Needed</th>
                                <th>Annual Withdrawal</th>
                                <th>Monthly Withdrawal</th>
                                <th>Success Rate*</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
                <p style="font-size: 0.9em; color: #666; margin-top: 10px;">*Historical success rate over 30 years based on historical data (approximate)</p>
            </div>
        </div>

        <footer class="site-footer">
            <span class="donate-text">If these tools are useful, please consider supporting future development.</span>
            <a href="#" class="donate-btn">
                <span class="donate-dot"></span>
                Donate
            </a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="calculator.js"></script>
</body>
</html>
