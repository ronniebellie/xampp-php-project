<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Security Claiming Analyzer</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Social Security Claiming Analyzer</h1>
            <p class="sub">Compare different Social Security claiming ages and visualize how your lifetime benefits change based on when you start collecting</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Social Security Claiming Decisions</h2>
            <p>You can claim Social Security retirement benefits as early as age 62 or as late as age 70. Claiming early means smaller monthly checks but more total payments. Claiming later means larger monthly checks but fewer total payments. This calculator helps you understand the trade-offs and find the "break-even" age where total lifetime benefits become equal.</p>
        </div>

        <form id="ssForm">
            <h3>Your Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="birthDate" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Birth Date</label>
                    <input type="date" id="birthDate" required style="width: 100%;">
                    <small style="color: #666;">Used to calculate your Full Retirement Age (FRA)</small>
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
