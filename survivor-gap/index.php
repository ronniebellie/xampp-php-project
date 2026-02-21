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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survivor Gap Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Survivor Gap Calculator</h1>
            <p class="sub">Compare single-life vs joint-life annuity payouts and see how life insurance could fill the gap for your surviving spouse</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding the Annuity Choice</h2>
            <p>If you have a pension or retirement account (e.g., TIAA-CREF, state or public retirement systems, teacher plans) that can be converted to an annuity, you often face a choice: <strong>single-life</strong> (higher monthly benefit, ends when you die) or <strong>joint-life</strong> (lower monthly benefit, continues to your survivor). The joint-life option typically reduces your monthly payment by 14–18% because it must fund payments for two lives. This calculator shows you the dollar cost of that reduction over your expected retirement years—and how life insurance could help fill the gap for your survivor with tax-free benefits.</p>
            <p style="margin-top: 12px;"><strong>Tip:</strong> Get your exact single-life and joint-life amounts from your plan provider (TIAA, state retirement system, etc.) or their online estimator, then enter them here.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios for later comparison.
    </p>
</div>
<?php endif; ?>

        <form id="survivorGapForm">
            <h3>Your Annuity Options</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div>
                    <label for="singleLifeMonthly" style="display: block; margin-bottom: 5px; font-weight: 600;">Single-Life Monthly Benefit ($)</label>
                    <input type="number" id="singleLifeMonthly" min="0" step="100" value="4000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Higher amount; benefit ends when you die</small>
                </div>
                <div>
                    <label for="jointLifeMonthly" style="display: block; margin-bottom: 5px; font-weight: 600;">Joint-Life Monthly Benefit ($)</label>
                    <input type="number" id="jointLifeMonthly" min="0" step="100" value="3400" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Lower amount; survivor continues to receive benefit</small>
                </div>
                <div>
                    <label for="yearsInRetirement" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Years in Retirement</label>
                    <input type="number" id="yearsInRetirement" min="1" max="40" value="18" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <small style="color: #666;">Typical life expectancy beyond retirement: 17–19 years</small>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate Survivor Gap</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Your Survivor Gap Analysis</h2>

            <div class="summary-grid" id="summaryCards"></div>

            <div class="chart-section">
                <h3>Single-Life vs Joint-Life Monthly Benefit</h3>
                <div class="chart-wrapper" style="height: 300px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3>Cumulative Survivor Gap Over Time</h3>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="cumulativeGapChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
                <h3 style="color: #92400e; margin-top: 0;">Disclaimer</h3>
                <p style="margin: 0; color: #78350f; line-height: 1.6;">This tool is for educational purposes only and does not constitute financial, tax, or insurance advice. Annuity payout amounts vary by provider and depend on your age, your spouse's age, and current rates. Life insurance costs depend on age, health, and policy type. Consult a qualified professional before making decisions about annuities or life insurance.</p>
            </div>
        </div>

        <?php if (!$isPremium): ?>
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
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js"></script>
</body>
</html>
