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
  <title>How Much Do I Need? Nest Egg Target</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>How Much Do I Need? Nest Egg Target</h1>
      <p class="sub">
        Get a rule-of-thumb target for how much to have saved by retirement. Enter the income you want in retirement,
        subtract guaranteed income (Social Security, pensions), and use a withdrawal rate (e.g. 4%) to see the nest egg you’re aiming for.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Many planners use the <strong>4% rule</strong>: in year one of retirement you withdraw 4% of your portfolio;
        in later years you adjust for inflation. So: <em>nest egg ≈ annual income from portfolio ÷ 4%</em>.
        This calculator does that math for you. You can change the withdrawal rate (e.g. 3% for a more conservative target).
      </p>
      <p style="margin-top: 8px;">
        Once you have a target, use the <strong>401(k) / IRA On Track?</strong> calculator to see if your current savings and contributions are on pace to reach it.
      </p>
    </div>

    <form id="nestEggForm">
      <h3>Your retirement income picture</h3>

      <div style="margin-bottom: 20px;">
        <fieldset style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px;">
          <legend style="font-weight: 600;">How do you want to set your retirement income?</legend>
          <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <input type="radio" name="incomeMethod" value="direct" checked style="width: 18px; height: 18px;">
            I have a number in mind (desired annual income in retirement)
          </label>
          <label style="display: flex; align-items: center; gap: 8px;">
            <input type="radio" name="incomeMethod" value="estimate" style="width: 18px; height: 18px;">
            Estimate from my current spending
          </label>
        </fieldset>
      </div>

      <div id="directIncomeWrap" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="desiredAnnualIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Desired annual income in retirement ($)
          </label>
          <input
            type="number"
            id="desiredAnnualIncome"
            min="0"
            step="1000"
            value="60000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Total income you want per year in retirement (before or after tax—use one consistently).</small>
        </div>
      </div>

      <div id="estimateWrap" style="display: none; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="currentMonthlySpending" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Current monthly spending ($)
          </label>
          <input
            type="number"
            id="currentMonthlySpending"
            min="0"
            step="50"
            value="5000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">What you spend now (or expect to spend before retirement).</small>
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
          <small style="color: #666;">Many people use 70–80%.</small>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="guaranteedAnnualIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Expected annual guaranteed income in retirement ($)
          </label>
          <input
            type="number"
            id="guaranteedAnnualIncome"
            min="0"
            step="500"
            value="24000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Social Security, pensions, annuities, rental income you expect to continue.</small>
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
          <small style="color: #666;">4% is a common rule of thumb; use a lower % for a more conservative target.</small>
        </div>
        <div>
          <label for="currentSavings" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Optional: Current retirement savings ($)
          </label>
          <input
            type="number"
            id="currentSavings"
            min="0"
            step="1000"
            value=""
            placeholder="e.g. 150000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">If you enter this, we’ll show how close you are to your target and link to the 401(k) On Track calculator.</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          What’s my target?
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your nest egg target</h2>
      <div class="summary-grid" style="margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Target nest egg</div>
          <div id="resultTarget" style="font-size: 28px; font-weight: 800; color: #1e3a8a;"></div>
          <div id="resultExplanation" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>
        <div id="currentSavingsCard" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Where you are now</div>
          <div id="resultCurrentPct" style="font-size: 22px; font-weight: 800; color: #14532d;"></div>
          <div id="resultCurrentDetail" style="font-size: 13px; color: #4b5563; margin-top: 4px;"></div>
          <p style="margin: 12px 0 0 0;">
            <a href="../401k-on-track/" class="btn" style="display: inline-block; margin-top: 8px;">See if I’m on track → 401(k) / IRA On Track?</a>
          </p>
        </div>
      </div>

      <div class="info-box-blue" style="margin-top: 16px;">
        <h3 style="margin-top: 0;">Next step</h3>
        <p id="nextStepText">
          Use the <a href="../401k-on-track/">401(k) / IRA On Track?</a> calculator to see if your current balance and contributions will get you to this target by your chosen retirement age.
        </p>
      </div>

      <?php
        $share_title = 'How Much Do I Need? Nest Egg Target';
        $share_text  = 'Check out the Nest Egg Target calculator at ronbelisle.com — get a rule-of-thumb number for how much to save for retirement.';
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
