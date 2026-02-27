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
  <title>Debt vs Saving: Which First?</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>Debt vs Saving: Which First?</h1>
      <p class="sub">
        You have extra money each month. Should you throw it at debt, or invest it for retirement?
        This tool compares both paths side‑by‑side over a time horizon.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Enter one representative debt (balance, rate, and minimum payment), an amount you could put toward that debt
        or invest each month, and an expected investment return and time horizon. The calculator runs two simple
        scenarios:
      </p>
      <ul style="margin-top: 8px; padding-left: 22px; line-height: 1.7;">
        <li><strong>Invest Extra</strong> – you pay only the minimum on the debt and invest the extra each month.</li>
        <li><strong>Pay Debt First</strong> – you use the extra to pay down the debt faster; once it’s gone, you invest what you had been paying.</li>
      </ul>
      <p style="margin-top: 8px;">
        At the end of the horizon, we compare approximate net worth (investments minus any remaining debt) under each
        strategy to show which one wins on these assumptions.
      </p>
    </div>

    <form id="debtVsSavingForm">
      <h3>Your situation</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="debtBalance" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Debt balance ($)
          </label>
          <input
            type="number"
            id="debtBalance"
            min="0"
            step="100"
            value="20000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Use your highest‑interest or most representative debt.</small>
        </div>

        <div>
          <label for="debtRate" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Debt interest rate (% per year)
          </label>
          <input
            type="number"
            id="debtRate"
            min="0"
            max="40"
            step="0.25"
            value="18"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Credit cards are often 15–25%.</small>
        </div>

        <div>
          <label for="minPayment" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Minimum monthly payment ($)
          </label>
          <input
            type="number"
            id="minPayment"
            min="0"
            step="25"
            value="400"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Roughly what you must pay toward this debt each month.</small>
        </div>

        <div>
          <label for="extraPerMonth" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Extra you could put toward debt or investing ($/month)
          </label>
          <input
            type="number"
            id="extraPerMonth"
            min="0"
            step="25"
            value="300"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">This is the lever we’ll send either to debt or to investments.</small>
        </div>

        <div>
          <label for="investReturn" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Expected investment return (% per year)
          </label>
          <input
            type="number"
            id="investReturn"
            min="0"
            max="20"
            step="0.5"
            value="7"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Long‑term stock‑heavy portfolios are often modeled at 6–8%.</small>
        </div>

        <div>
          <label for="horizonYears" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Time horizon (years)
          </label>
          <input
            type="number"
            id="horizonYears"
            min="1"
            max="40"
            step="1"
            value="10"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">How long you’ll keep this plan going.</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          Compare strategies
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Debt vs saving comparison</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Invest Extra</div>
          <div id="investExtraHeadline" style="font-size: 18px; font-weight: 700; color: #1e3a8a; margin-top: 4px;"></div>
          <div id="investExtraDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Pay Debt First</div>
          <div id="payDebtHeadline" style="font-size: 18px; font-weight: 700; color: #14532d; margin-top: 4px;"></div>
          <div id="payDebtDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>

        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Which wins (on these assumptions)?</div>
          <div id="winnerHeadline" style="font-size: 18px; font-weight: 700; color: #78350f; margin-top: 4px;"></div>
          <div id="winnerDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>
      </div>

      <div class="info-box-blue" style="margin-top: 12px;">
        <h3 style="margin-top: 0;">How to read this</h3>
        <p id="explanationText">
          Results will appear here after you compare strategies. This is a simplified model with steady payments and
          returns—it’s meant to give directional guidance, not precise forecasts.
        </p>
      </div>

      <?php
        $share_title = 'Debt vs Saving: Which First?';
        $share_text  = 'Try this Debt vs Saving comparison at ronbelisle.com — see whether it might make more sense to pay debt down first or invest extra money for retirement.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Save and compare payoff vs investing plans';
      $premium_upsell_text = 'Upgrade to Premium to save multiple debt vs saving scenarios, export PDFs and CSVs, and compare them with your other calculators.';
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

  <script src="../js/share-results.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="calculator.js"></script>
</body>
</html>

