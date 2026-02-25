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
  <title>Pension vs. Lump Sum Calculator</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>Pension vs. Lump Sum Calculator</h1>
      <p class="sub">See how many years it takes for the pension to “pay back” the lump sum, and how your life expectancy affects the choice.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Understanding the Choice</h2>
      <p>Many employers offer a choice: <strong>take a monthly pension for life</strong> or <strong>take a lump sum</strong> and invest or spend it. This calculator compares the two by showing how many years of pension payments it takes to match what the lump sum would grow to if invested at an assumed rate. If you live past that “break-even” age, the pension typically comes out ahead; if you don’t, the lump sum (or what’s left of it) may be worth more to you or your heirs.</p>
      <p style="margin-top: 12px;"><strong>Tip:</strong> Use your plan’s official lump sum and monthly pension numbers. The growth rate should reflect how you’d invest the lump sum (e.g. 4–6% for a balanced portfolio).</p>
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

    <form id="pensionForm">
      <h3>Your Numbers</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div>
          <label for="monthlyPension" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly Pension ($)</label>
          <input type="number" id="monthlyPension" min="0" step="100" value="2500" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Before tax; single-life or joint-life amount</small>
        </div>
        <div>
          <label for="lumpSum" style="display: block; margin-bottom: 5px; font-weight: 600;">Lump Sum Offered ($)</label>
          <input type="number" id="lumpSum" min="0" step="1000" value="500000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">One-time amount if you give up the pension</small>
        </div>
        <div>
          <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Your Current Age</label>
          <input type="number" id="currentAge" min="50" max="95" value="65" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Age when pension or lump sum starts</small>
        </div>
        <div>
          <label for="growthRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Assumed Growth Rate on Lump Sum (%)</label>
          <input type="number" id="growthRate" min="0" max="15" step="0.25" value="5" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Annual return if you invest the lump sum</small>
        </div>
        <div>
          <label for="lifeExpectancy" style="display: block; margin-bottom: 5px; font-weight: 600;">Plan To Age (Life Expectancy)</label>
          <input type="number" id="lifeExpectancy" min="70" max="120" value="90" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Used to show total received by end of plan</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Compare Pension vs. Lump Sum</button>
      </div>
    </form>

    <div id="results" class="results-container" style="display: none;">
      <h2>Pension vs. Lump Sum Comparison</h2>

      <div class="info-box info-box-blue" id="summaryBox" style="margin-bottom: 25px;"></div>

      <div class="chart-section">
        <h3>Pension vs. Lump Sum Over Time</h3>
        <p style="color: #666; margin-bottom: 10px;">Cumulative pension received vs. what the lump sum would grow to if invested.</p>
        <div class="chart-wrapper" style="height: 340px;">
          <canvas id="comparisonChart"></canvas>
        </div>
      </div>

      <div class="table-section">
        <h3>Year-by-Year Comparison</h3>
        <div class="table-wrapper">
          <table class="data-table" id="resultsTable">
            <thead>
              <tr>
                <th>Year</th>
                <th>Age</th>
                <th>Cumulative Pension Received</th>
                <th>Lump Sum If Invested (FV)</th>
              </tr>
            </thead>
            <tbody id="resultsBody"></tbody>
          </table>
        </div>
      </div>

      <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
        <h3 style="color: #92400e; margin-top: 0;">Disclaimer</h3>
        <p style="margin: 0; color: #78350f; line-height: 1.6;">This tool is for educational purposes only and does not constitute financial or tax advice. Pension and lump sum amounts vary by plan and assumptions. Investment returns are uncertain. Consider inflation, taxes, and your health and family situation. Consult a qualified professional before making this decision.</p>
      </div>
      <?php $share_title = 'Pension vs. Lump Sum Calculator'; $share_text = 'Check out the Pension vs. Lump Sum Calculator at ronbelisle.com — compare pension income to lump sum growth over time.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
    $premium_upsell_headline = 'Unlock Premium Features';
    $premium_upsell_text = 'Upgrade to Premium to save and compare pension vs. lump sum scenarios and export PDF and CSV reports.';
    include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
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
