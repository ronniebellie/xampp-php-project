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
  <title>Retirement Spending &amp; On-Track Checkup</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>Retirement Spending &amp; On-Track Checkup</h1>
      <p class="sub">
        Estimate what you might spend in retirement, apply a simple rule-of-thumb (like the 4% rule),
        and see whether your current savings put you close to “on track” for that spending level.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        This calculator uses two common planning shortcuts:
        many retirees spend about <strong>80% of their pre-retirement budget</strong>, and a sustainable
        withdrawal rate is often approximated by the <strong>4% rule</strong>.
        You can adjust both to fit your situation.
      </p>
      <p style="margin-top: 8px;">
        Enter your current monthly spending (or current retirement budget if you’re already retired),
        tweak your expected retirement spending percentage, add any
        guaranteed income (Social Security, pensions, annuities), and we’ll estimate the portfolio size
        you might want at retirement and how your current savings compare.
      </p>
    </div>

    <form id="spendingForm">
      <h3>Your current spending &amp; savings</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="currentMonthlySpending" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Current monthly living expenses or retirement budget ($)
          </label>
          <input
            type="number"
            id="currentMonthlySpending"
            min="0"
            step="50"
            value="5000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">
            If you’re still working, use your current monthly budget. If you’re already retired, use your
            current retirement spending.
          </small>
        </div>

        <div>
          <label style="display: block; margin-bottom: 5px; font-weight: 600;">
            Retirement stage
          </label>
          <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #374151;">
            <input type="checkbox" id="alreadyRetired" style="width: 16px; height: 16px;">
            I’m already retired (use 100% of my current spending)
          </label>
          <small style="display: block; margin-top: 4px; color: #666;">
            When checked, this treats today’s spending as your retirement budget and locks the percentage below to 100%.
          </small>
        </div>

        <div>
          <label for="retirementSpendingPct" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Retirement spending as % of current
          </label>
          <input
            type="number"
            id="retirementSpendingPct"
            min="40"
            max="120"
            step="1"
            value="80"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">
            If you’re still working, many people use 70–80%. If you’re already retired and using today’s budget, this is typically 100%.
          </small>
        </div>

        <div>
          <label for="guaranteedMonthlyIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Expected guaranteed retirement income ($/month)
          </label>
          <input
            type="number"
            id="guaranteedMonthlyIncome"
            min="0"
            step="50"
            value="3000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">
            Social Security, pensions, annuities, rental income you expect to continue.
          </small>
        </div>

        <div>
          <label for="currentSavings" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Current retirement savings ($)
          </label>
          <input
            type="number"
            id="currentSavings"
            min="0"
            step="1000"
            value="750000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">
            401(k), IRA, brokerage, and other long-term investments earmarked for retirement.
          </small>
        </div>

        <div>
          <label for="withdrawalRate" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Withdrawal rate (% per year)
          </label>
          <input
            type="number"
            id="withdrawalRate"
            min="2"
            max="8"
            step="0.25"
            value="4"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">
            4% is a common starting point; more conservative plans use a lower number.
          </small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          See if I'm on track
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your retirement spending picture</h2>
      <div class="summary-grid" style="margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Target retirement budget (per year)</div>
          <div id="resultAnnualBudget" style="font-size: 24px; font-weight: 800; color: #1e3a8a;"></div>
          <div style="font-size: 13px; color: #4b5563; margin-top: 4px;">
            Based on your current spending and retirement %.
          </div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Covered by guaranteed income (per year)</div>
          <div id="resultAnnualGuaranteed" style="font-size: 24px; font-weight: 800; color: #14532d;"></div>
          <div style="font-size: 13px; color: #4b5563; margin-top: 4px;">
            Social Security, pensions, annuities, rentals.
          </div>
        </div>

        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Needed from portfolio (per year)</div>
          <div id="resultAnnualFromPortfolio" style="font-size: 24px; font-weight: 800; color: #78350f;"></div>
          <div style="font-size: 13px; color: #4b5563; margin-top: 4px;">
            Retirement budget minus guaranteed income.
          </div>
        </div>

        <div id="onTrackCard" style="border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; font-weight: 600;" id="onTrackLabel">On track?</div>
          <div id="onTrackStatus" style="font-size: 22px; font-weight: 800;"></div>
          <div id="onTrackDetail" style="font-size: 13px; margin-top: 4px;"></div>
        </div>
      </div>

      <div class="info-box-blue" id="explanationBox" style="margin-top: 10px;">
        <h3 style="margin-top: 0;">How to read this</h3>
        <p id="explanationText">
          Results will appear here after you run a checkup. This is a rule-of-thumb view only and doesn't
          replace detailed financial planning.
        </p>
      </div>

      <?php
        $share_title = 'Retirement Spending & On-Track Checkup';
        $share_text  = 'Check out this Retirement Spending & On-Track Checkup at ronbelisle.com — estimate whether your savings support your retirement budget.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Unlock Premium Features';
      $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDFs and CSVs, and more across all calculators.';
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

