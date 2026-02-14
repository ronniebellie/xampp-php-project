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
    <title>RMD Impact Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>RMD Impact Calculator</h1>
            <p class="sub">Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding RMDs</h2>
            <p>Required Minimum Distributions (RMDs) force you to withdraw a percentage of your tax-deferred retirement accounts starting at age 73. While they can feel intimidating, for most retirees with modest account balances, RMDs don't create a significant tax burden. This calculator helps you understand your specific situation and whether RMD planning strategies make sense for you.</p>
        </div>

       

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;">📄 Download PDF</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
</div>
<?php endif; ?>

        <form id="rmdForm">
            <h3>Your Current Situation</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Current Age</label>
                    <input type="number" id="currentAge" min="50" max="100" value="60" required style="width: 100%;">
                    <small style="color: #666;">Enter your age today</small>
                </div>
                <div>
                    <label for="accountBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Tax-Deferred Account Balance (as of 12/31 last year) ($)</label>
                    <input type="number" id="accountBalance" min="0" step="1000" value="150000" required style="width: 100%;">
                    <small style="color: #666;">Traditional IRA, 401(k), etc. - exclude Roth accounts</small>
                </div>
                <div>
                    <label for="growthRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Growth Rate (%)</label>
                    <input type="number" id="growthRate" min="0" max="20" step="0.1" value="7" required style="width: 100%;">
                    <small style="color: #666;">Typical range: 5-8% for diversified portfolios</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="spouseBeneficiary" style="display: block; margin-bottom: 5px; font-weight: 600;">Is your spouse the sole beneficiary?</label>
                    <select id="spouseBeneficiary" onchange="toggleSpouseAge()" style="width: 100%;">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                    <small style="color: #666;">Used to determine which IRS life expectancy table applies</small>
                </div>
                <div id="spouseAgeGroup" style="display: none;">
                    <label for="spouseAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Spouse's Current Age</label>
                    <input type="number" id="spouseAge" min="18" max="100" value="55" style="width: 100%;">
                    <small style="color: #666;">Only needed if spouse is more than 10 years younger</small>
                </div>
            </div>

            <h3 style="margin-top: 30px;">Other Retirement Income</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="socialSecurity" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Social Security Benefits ($)</label>
                    <input type="number" id="socialSecurity" min="0" step="1000" value="30000" style="width: 100%;">
                    <small style="color: #666;">Expected annual amount (0 if not yet claiming)</small>
                </div>
                <div>
                    <label for="pension" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Pension Income ($)</label>
                    <input type="number" id="pension" min="0" step="1000" value="0" style="width: 100%;">
                    <small style="color: #666;">Include any pension or annuity income</small>
                </div>
                <div>
                    <label for="otherIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Other Taxable Income ($)</label>
                    <input type="number" id="otherIncome" min="0" step="1000" value="0" style="width: 100%;">
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
                <h3>Account Balance and RMD Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="rmdChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <div class="table-section">
                <h3>Year-by-Year Breakdown</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Age</th>
                                <th>Account Balance</th>
                                <th>RMD Amount</th>
                                <th>Total Income</th>
                                <th>Est. Tax Bracket</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>
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
    </script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
</script>
<script src="calculator.js"></script>
</body>
</html>