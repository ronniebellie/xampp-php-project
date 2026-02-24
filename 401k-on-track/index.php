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
    $sub = null;
    $stmt->bind_result($sub);
    $user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
    $stmt->close();
    $isPremium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>401(k) / IRA On Track? Calculator</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>401(k) / IRA On Track?</h1>
      <p class="sub">See if your current balance and contributions put you on track for retirement by your target age.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Are you on track?</h2>
      <p>Rules of thumb (e.g. "save 15%" or "have 10× salary by 67") are starting points, but your target depends on the income you want in retirement. This calculator projects your 401(k) and IRA growth to your chosen retirement age and compares it to a target balance—either one you set or one derived from a desired retirement income and withdrawal rate (e.g. 4% rule).</p>
    </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
  <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
  <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
    <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
    <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
    <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;">⚖️ Compare</button>
    <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;">📄 PDF</button>
    <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;">📊 CSV</button>
    <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
  </div>
  <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568;">Save / Load / Compare / PDF / CSV</p>
</div>
<?php endif; ?>

    <form id="onTrackForm">
      <h3>Your situation</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Current age</label>
          <input type="number" id="currentAge" min="18" max="80" step="1" value="40" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div>
          <label for="retirementAge" style="display: block; margin-bottom: 5px; font-weight: 600;">Target retirement age</label>
          <input type="number" id="retirementAge" min="22" max="100" step="1" value="65" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">When you plan to stop contributing and start drawing</small>
        </div>
        <div>
          <label for="currentBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Current 401(k) + IRA balance ($)</label>
          <input type="number" id="currentBalance" min="0" step="1000" value="150000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div>
          <label for="annualContribution" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual contribution ($)</label>
          <input type="number" id="annualContribution" min="0" step="500" value="12000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Total you add per year (employer match included)</small>
        </div>
        <div>
          <label for="expectedReturn" style="display: block; margin-bottom: 5px; font-weight: 600;">Expected annual return (%)</label>
          <input type="number" id="expectedReturn" min="0" max="20" step="0.5" value="6" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Long-term average; many use 5–7% real return</small>
        </div>
        <div>
          <label for="targetBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">Target balance at retirement ($)</label>
          <input type="number" id="targetBalance" min="0" step="50000" value="1000000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Or set from income below</small>
        </div>
      </div>
      <h3 style="margin-top: 24px;">Optional: set target from desired income</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="desiredIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">Desired annual income in retirement ($)</label>
          <input type="number" id="desiredIncome" min="0" step="5000" value="0" placeholder="e.g. 60000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
        </div>
        <div>
          <label for="withdrawalRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Withdrawal rate (%)</label>
          <input type="number" id="withdrawalRate" min="1" max="10" step="0.25" value="4" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">4% is a common rule of thumb</small>
        </div>
        <div style="align-self: end;">
          <button type="button" id="setTargetFromIncome" class="btn-secondary" style="padding: 10px 16px;">Set target from income</button>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Am I on track?</button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your projection</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Years to retirement</div>
          <div id="resultYears" style="font-size: 24px; font-weight: 800; color: #1e3a8a;"></div>
        </div>
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Projected balance</div>
          <div id="resultProjected" style="font-size: 24px; font-weight: 800; color: #14532d;"></div>
        </div>
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Target balance</div>
          <div id="resultTarget" style="font-size: 24px; font-weight: 800; color: #78350f;"></div>
        </div>
        <div id="resultOnTrackCard" style="border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; font-weight: 600;" id="resultOnTrackLabel">On track?</div>
          <div id="resultOnTrack" style="font-size: 22px; font-weight: 800;"></div>
        </div>
      </div>
      <div id="progressMessage" style="margin: 16px 0; padding: 12px; border-radius: 8px;"></div>
      <div class="chart-section" style="margin: 24px 0;">
        <h3>Balance growth to retirement</h3>
        <div class="chart-wrapper" style="height: 360px;">
          <canvas id="growthChart"></canvas>
        </div>
      </div>
      <div class="table-section" style="margin: 24px 0;">
        <h3>Year-by-year projection</h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Year</th>
                <th>Age</th>
                <th>Balance (start)</th>
                <th>Contribution</th>
                <th>Growth</th>
                <th>Balance (end)</th>
              </tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
      </div>
      <?php $share_title = '401(k) On Track Calculator'; $share_text = 'Check out the 401(k) On Track calculator at ronbelisle.com — see if you\'re on track for retirement.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
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
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>
