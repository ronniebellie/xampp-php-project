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
  <title>Retirement Trade-Off Explorer</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>Retirement Trade-Off Explorer</h1>
      <p class="sub">
        Explore how three big levers—retiring later, saving more, or spending less (or adding part-time income)—
        change whether you look on track for your retirement income goal.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Start with a simple baseline: your age, current savings, annual contributions, expected return, and the income
        you’d like in retirement (along with Social Security or pension income). We estimate the portfolio you might
        need using a withdrawal-rate rule of thumb (like 4%) and compare it to what you’re projected to have by your
        target retirement age.
      </p>
      <p style="margin-top: 8px;">
        Then we show side‑by‑side how three levers can close any gap:
        <strong>retiring later</strong>, <strong>saving more each year</strong>, and
        <strong>spending less / adding part‑time income</strong>. Each scenario shows the projected annual
        income shortfall (or surplus) relative to your target.
      </p>
    </div>

    <form id="tradeoffForm">
      <h3>Your baseline plan</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="currentAge" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Current age
          </label>
          <input
            type="number"
            id="currentAge"
            min="18"
            max="80"
            step="1"
            value="40"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
        </div>

        <div>
          <label for="retirementAge" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Target retirement age
          </label>
          <input
            type="number"
            id="retirementAge"
            min="22"
            max="80"
            step="1"
            value="65"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Baseline age when you stop contributing and start withdrawals.</small>
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
            value="150000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">401(k), IRA, and other long-term investments earmarked for retirement.</small>
        </div>

        <div>
          <label for="annualContribution" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Annual retirement savings ($/year)
          </label>
          <input
            type="number"
            id="annualContribution"
            min="0"
            step="500"
            value="12000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Total you add each year, including employer match.</small>
        </div>

        <div>
          <label for="expectedReturn" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Expected annual return (% before retirement)
          </label>
          <input
            type="number"
            id="expectedReturn"
            min="0"
            max="20"
            step="0.5"
            value="6"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Long‑term average; many use 5–7% after inflation.</small>
        </div>
      </div>

      <h3>Your retirement income goal</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="desiredIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Desired annual income in retirement ($)
          </label>
          <input
            type="number"
            id="desiredIncome"
            min="0"
            step="5000"
            value="80000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Before or after tax—pick one and stay consistent.</small>
        </div>

        <div>
          <label for="guaranteedIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Expected annual guaranteed income ($)
          </label>
          <input
            type="number"
            id="guaranteedIncome"
            min="0"
            step="500"
            value="30000"
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
          <small style="color: #666;">4% is a common rule of thumb; lower is more conservative.</small>
        </div>
      </div>

      <h3>Trade‑off settings</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="retireLater2" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Retire later: second scenario (+ years)
          </label>
          <input
            type="number"
            id="retireLater2"
            min="1"
            max="15"
            step="1"
            value="2"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Baseline X vs X + this many years.</small>
        </div>

        <div>
          <label for="retireLater3" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Retire later: third scenario (+ years)
          </label>
          <input
            type="number"
            id="retireLater3"
            min="2"
            max="20"
            step="1"
            value="5"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">E.g. X + 5 years.</small>
        </div>

        <div>
          <label for="extraSavings" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Save more: extra annual savings ($)
          </label>
          <input
            type="number"
            id="extraSavings"
            min="0"
            step="500"
            value="3000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">How much more you might invest each year.</small>
        </div>

        <div>
          <label for="spendingCutPct" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Spend less: cut retirement budget by (%)
          </label>
          <input
            type="number"
            id="spendingCutPct"
            min="0"
            max="50"
            step="1"
            value="10"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">For example, 10% or 15% less than your current target.</small>
        </div>

        <div>
          <label for="partTimeIncome" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Part‑time work or side income ($/year)
          </label>
          <input
            type="number"
            id="partTimeIncome"
            min="0"
            step="1000"
            value="10000"
            style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;"
          >
          <small style="color: #666;">Optional extra income during early retirement years.</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          Explore my trade‑offs
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your baseline vs trade‑offs</h2>
      <div class="summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 20px 0;">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #1e40af; font-weight: 600;">Baseline plan</div>
          <div id="baselineHeadline" style="font-size: 18px; font-weight: 700; color: #1e3a8a; margin-top: 4px;"></div>
          <div id="baselineDetail" style="font-size: 13px; color: #4b5563; margin-top: 6px;"></div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #166534; font-weight: 600;">Retire later (X vs X+2 vs X+5)</div>
          <div id="retireLaterSummary" style="font-size: 13px; color: #14532d; margin-top: 6px; line-height: 1.7;"></div>
        </div>

        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #92400e; font-weight: 600;">Save more each year</div>
          <div id="saveMoreSummary" style="font-size: 13px; color: #78350f; margin-top: 6px; line-height: 1.7;"></div>
        </div>

        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px;">
          <div style="font-size: 13px; color: #991b1b; font-weight: 600;">Spend less / add income</div>
          <div id="spendLessSummary" style="font-size: 13px; color: #7f1d1d; margin-top: 6px; line-height: 1.7;"></div>
        </div>
      </div>

      <div class="info-box-blue" style="margin-top: 12px;">
        <h3 style="margin-top: 0;">How to read these results</h3>
        <p id="explanationText">
          Results will appear here after you run the explorer. This is a rule‑of‑thumb view using simple growth
          assumptions—not a full financial plan.
        </p>
      </div>

      <?php
        $share_title = 'Retirement Trade-Off Explorer';
        $share_text  = 'Try this Retirement Trade-Off Explorer at ronbelisle.com — see how retiring later, saving more, or spending less change your retirement outlook.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Save and compare retirement trade-offs';
      $premium_upsell_text = 'Upgrade to Premium to save multiple trade-off scenarios, export PDFs and CSVs, and compare them across calculators.';
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

