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
    <title>Social Security + Spending Gap Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>
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

            <div class="chart-section">
                <h3>Annual Withdrawal Amounts by Rate</h3>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="annualWithdrawalChart"></canvas>
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
            <a href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" class="donate-btn">
                <span class="donate-dot"></span>
                Donate
            </a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js"></script>
</body>
</html>