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
  <title>Student Loan Payoff Calculator</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    @media (max-width: 700px) {
      .loan-row { grid-template-columns: 1fr 1fr !important; }
      .loan-row label { font-size: 12px !important; }
    }
  </style>
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>Student Loan Payoff Calculator</h1>
      <p class="sub">Model extra payments and payoff timelines. Compare avalanche vs snowball to choose a strategy that fits.</p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>Strategies and refinancing</h2>
      <p><strong>Avalanche</strong> — Put extra money toward the loan with the highest interest rate first; you pay less total interest. <strong>Snowball</strong> — Put extra toward the smallest balance first to pay off individual loans sooner. This tool shows payoff order, total interest, and a month-by-month view. If you're considering <strong>refinancing</strong>, run your current loans here first, then change rates or add a single "Refinanced" loan to compare.</p>
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

    <form id="loanForm">
      <h3>Your student loans</h3>
      <p style="color: #64748b; margin-bottom: 15px;">Enter up to 5 loans. Leave balance at 0 to skip a row.</p>
      <div id="loanRows">
        <div class="loan-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 12px;">
          <div>
            <label for="name1" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Name</label>
            <input type="text" id="name1" placeholder="e.g. Federal Sub" value="Federal Loan 1" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          </div>
          <div>
            <label for="balance1" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Balance ($)</label>
            <input type="number" id="balance1" min="0" step="1" value="25000" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          </div>
          <div>
            <label for="apr1" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">APR (%)</label>
            <input type="number" id="apr1" min="0" max="50" step="0.1" value="5.5" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          </div>
          <div>
            <label for="min1" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Min payment ($)</label>
            <input type="number" id="min1" min="0" step="1" value="280" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
          </div>
          <div></div>
        </div>
        <div class="loan-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 12px;">
          <div><label for="name2" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Name</label><input type="text" id="name2" placeholder="e.g. Federal Unsub" value="Federal Loan 2" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="balance2" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Balance ($)</label><input type="number" id="balance2" min="0" step="1" value="18000" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="apr2" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">APR (%)</label><input type="number" id="apr2" min="0" max="50" step="0.1" value="4.2" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="min2" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Min payment ($)</label><input type="number" id="min2" min="0" step="1" value="180" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div></div>
        </div>
        <div class="loan-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 12px;">
          <div><label for="name3" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Name</label><input type="text" id="name3" placeholder="e.g. Private" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="balance3" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Balance ($)</label><input type="number" id="balance3" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="apr3" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">APR (%)</label><input type="number" id="apr3" min="0" max="50" step="0.1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="min3" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Min payment ($)</label><input type="number" id="min3" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div></div>
        </div>
        <div class="loan-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 12px;">
          <div><label for="name4" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Name</label><input type="text" id="name4" placeholder="Loan 4" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="balance4" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Balance ($)</label><input type="number" id="balance4" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="apr4" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">APR (%)</label><input type="number" id="apr4" min="0" max="50" step="0.1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="min4" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Min payment ($)</label><input type="number" id="min4" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div></div>
        </div>
        <div class="loan-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 12px;">
          <div><label for="name5" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Name</label><input type="text" id="name5" placeholder="Loan 5" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="balance5" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Balance ($)</label><input type="number" id="balance5" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="apr5" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">APR (%)</label><input type="number" id="apr5" min="0" max="50" step="0.1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div><label for="min5" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">Min payment ($)</label><input type="number" id="min5" min="0" step="1" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;"></div>
          <div></div>
        </div>
      </div>

      <h3 style="margin-top: 28px;">Strategy & extra payment</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div>
          <label for="strategy" style="display: block; margin-bottom: 5px; font-weight: 600;">Strategy</label>
          <select id="strategy" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
            <option value="avalanche">Avalanche (highest APR first)</option>
            <option value="snowball">Snowball (smallest balance first)</option>
          </select>
        </div>
        <div>
          <label for="extra" style="display: block; margin-bottom: 5px; font-weight: 600;">Extra monthly payment ($)</label>
          <input type="number" id="extra" min="0" step="10" value="100" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Applied to your target loan each month</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate Payoff</button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your payoff plan</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Debt-free in</div>
          <div id="resultMonths" style="font-size: 24px; font-weight: 800; color: #14532d;"></div>
        </div>
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Total interest</div>
          <div id="resultInterest" style="font-size: 24px; font-weight: 800; color: #78350f;"></div>
        </div>
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Total paid</div>
          <div id="resultTotal" style="font-size: 24px; font-weight: 800; color: #1e3a8a;"></div>
        </div>
      </div>
      <div class="chart-section" style="margin: 24px 0;">
        <h3>Balance over time</h3>
        <div class="chart-wrapper" style="height: 360px;">
          <canvas id="balanceChart"></canvas>
        </div>
      </div>
      <div class="chart-section" style="margin: 24px 0;">
        <h3>Payoff order</h3>
        <div id="payoffOrder" style="margin-top: 8px;"></div>
      </div>
      <div class="table-section" style="margin: 24px 0;">
        <h3>Month-by-month (first 24 months)</h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Target loan</th>
                <th>Payment</th>
                <th>Interest</th>
                <th>Remaining balance</th>
              </tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
      </div>
      <?php $share_title = 'Student Loan Payoff Calculator'; $share_text = 'Check out the Student Loan Payoff calculator at ronbelisle.com — plan your path to payoff.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>
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
