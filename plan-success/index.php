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
  <title>Plan Success (Monte Carlo) | Ron Belisle Financial Calculators</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>Plan Success (Monte Carlo)</h1>
      <p class="sub">See the probability your portfolio lasts through retirement using random market return simulations.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How It Works</h2>
      <p>A <strong>Monte Carlo simulation</strong> runs hundreds or thousands of possible market paths. Each path uses random annual returns (based on your expected return and volatility). Your portfolio is drawn down by your chosen withdrawal each year. The <strong>success rate</strong> is the percentage of those paths where you still have money left at the end. No simulation can predict the real future—this tool shows you the odds under your assumptions.</p>
      <p style="margin-top: 12px;"><strong>Tip:</strong> Use a conservative expected return (e.g. 5–6%) and include volatility (e.g. 10–15% standard deviation) so the success rate reflects sequence-of-returns risk.</p>
    </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
  <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
  <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
    <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
    <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
    <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
  </div>
</div>
<?php endif; ?>

    <form id="planForm">
      <h3>Your Plan</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div>
          <label for="portfolio" style="display: block; margin-bottom: 5px; font-weight: 600;">Starting Portfolio ($)</label>
          <input type="number" id="portfolio" min="0" step="10000" value="1000000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Today’s portfolio value</small>
        </div>
        <div>
          <label for="withdrawal" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Withdrawal ($)</label>
          <input type="number" id="withdrawal" min="0" step="1000" value="40000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">First-year withdrawal amount. You can optionally grow this with inflation below.</small>
        </div>
        <div>
          <label for="inflationRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Inflation Rate for Withdrawals (%)</label>
          <input type="number" id="inflationRate" min="0" max="10" step="0.1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Set to 0 for flat withdrawals. Average U.S. inflation over the last few decades has been around 3% per year.</small>
        </div>
        <div>
          <label for="years" style="display: block; margin-bottom: 5px; font-weight: 600;">Years to Model</label>
          <input type="number" id="years" min="5" max="50" value="30" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">e.g. 30 for a 30-year retirement</small>
        </div>
        <div>
          <label for="expectedReturn" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected Annual Return (%)</label>
          <input type="number" id="expectedReturn" min="0" max="20" step="0.25" value="6" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Long-term average return assumption</small>
        </div>
        <div>
          <label for="volatility" style="display: block; margin-bottom: 5px; font-weight: 600;">Volatility / Std Dev (%)</label>
          <input type="number" id="volatility" min="0" max="50" step="0.5" value="12" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Typical stock portfolio: ~10–15%</small>
        </div>
        <div>
          <label for="simulations" style="display: block; margin-bottom: 5px; font-weight: 600;">Number of Simulations</label>
          <input type="number" id="simulations" min="100" max="10000" step="100" value="1000" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">More = smoother result, slower run</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Run Monte Carlo</button>
      </div>
    </form>

    <div id="results" class="results-container" style="display: none;">
      <h2>Monte Carlo Results</h2>

      <div class="info-box info-box-blue" id="summaryBox" style="margin-bottom: 25px;"></div>

      <div class="chart-section">
        <h3>Ending Portfolio Distribution</h3>
        <p style="color: #666; margin-bottom: 10px;">How often each ending balance occurred across simulations (negative = ran out of money).</p>
        <div class="chart-wrapper" style="height: 320px;">
          <canvas id="distributionChart"></canvas>
        </div>
      </div>

      <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
        <h3 style="color: #92400e; margin-top: 0;">Disclaimer</h3>
        <p style="margin: 0; color: #78350f; line-height: 1.6;">This tool is for education only. Past performance and Monte Carlo results do not guarantee future outcomes. Returns and volatility are uncertain. Consider consulting a financial professional.</p>
      </div>
      <?php $share_title = 'Plan Success (Monte Carlo) Calculator'; $share_text = 'Check out the Plan Success Monte Carlo calculator at ronbelisle.com — see the probability your plan will last through retirement.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
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
  </script>
  <script src="calculator.js"></script>
</body>
</html>
