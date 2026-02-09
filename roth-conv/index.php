<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roth Conversion Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Roth Conversion Calculator</h1>
            <p class="sub">Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Roth Conversions</h2>
            <p>Converting traditional IRA funds to a Roth IRA means paying taxes now on the converted amount, but all future growth and withdrawals will be tax-free. This calculator helps you analyze whether converting makes sense by comparing the tax cost today versus the tax savings in retirement, while considering Required Minimum Distributions (RMDs) and Medicare IRMAA surcharges.</p>
        </div>

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
    <label for="currentIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Annual Gross Income ($)</label>
    <input type="number" id="currentIncome" value="80000" min="0" step="1000" required style="width: 100%;">
    <small style="color: #666;">Wages, pensions, etc. (before standard deduction)</small>
</div>
                <div>
                    <label for="retirementIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Retirement Income ($)</label>
                    <input type="number" id="retirementIncome" value="40000" min="0" step="1000" required style="width: 100%;">
                    <small style="color: #666;">Annual income excluding RMDs</small>
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
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Conversion Analysis</h2>
            <div id="resultsContent"></div>
        </div>

        <footer class="site-footer">
            <span class="donate-text">If these tools are useful, please consider supporting future development.</span>
            <a href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" class="donate-btn">
                <span class="donate-dot"></span>
                Donate
            </a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="calculator.js"></script>
</body>
</html>