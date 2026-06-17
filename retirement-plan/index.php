<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
$defaultBirthYear = (int) date('Y') - 62;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Build a year-by-year retirement plan in one place. Enter your numbers once and see whether your savings, Social Security, and spending hold together over time.">
  <title>Retirement Plan Builder</title>
  <?php
    $og_title = $ld_name = 'Retirement Plan Builder';
    $og_description = $ld_description = 'Build a year-by-year retirement plan in one place. Enter your numbers once and see whether your savings, Social Security, and spending hold together over time.';
    include(__DIR__ . '/../includes/og-twitter-meta.php');
    include(__DIR__ . '/../includes/json-ld-softwareapp.php');
  ?>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 18px;
      margin-bottom: 22px;
    }
    .form-grid label.field-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
    }
    .form-grid input, .form-grid select {
      width: 100%;
      padding: 10px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
    }
    .form-grid small {
      display: block;
      margin-top: 4px;
      color: #666;
      line-height: 1.4;
    }
    .status-card {
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
    }
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      margin: 18px 0 8px;
    }
    .metric-box {
      background: #fff;
      border: 1px solid rgba(0,0,0,0.06);
      border-radius: 10px;
      padding: 14px;
    }
    .metric-box .label { font-size: 13px; color: #4b5563; margin-bottom: 4px; }
    .metric-box .value { font-size: 22px; font-weight: 800; color: #111827; }
    .chart-wrap { height: 320px; margin: 20px 0; }
    .deep-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 24px; }
    .deep-links a {
      display: block;
      padding: 14px;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      text-decoration: none;
      color: #1d4ed8;
      font-weight: 600;
      background: #f8fafc;
    }
    .deep-links a:hover { background: #eff6ff; }
    .mc-chart-wrap { height: 280px; margin-top: 16px; }
    .mc-locked {
      filter: blur(5px);
      user-select: none;
      pointer-events: none;
      opacity: 0.65;
    }
    .mc-success-rate {
      font-size: 42px;
      font-weight: 800;
      line-height: 1;
      margin: 8px 0;
    }
  </style>
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

    <header>
      <h1>Retirement Plan Builder</h1>
      <p class="sub">
        Enter your numbers once and see how savings, Social Security, and retirement spending fit together year by year.
        Free users get a snapshot and key milestones; Premium unlocks the full timeline, save/compare, and export.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        This planner combines logic from several of our calculators — growth projections, Social Security claiming adjustments,
        spending-gap math, <strong>required minimum distributions (RMDs)</strong>, and a <strong>simplified federal tax estimate</strong> — into one consistent timeline.
      </p>
      <p style="margin-top: 8px;">
        Use it as your starting point. Premium adds a <strong>Monte Carlo stress test</strong> on the same plan.
        Open the specialized calculators below for Roth conversions and deeper tax modeling.
      </p>
    </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
  <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
  <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
    <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;">Save Scenario</button>
    <button type="button" id="loadScenarioBtn" class="btn-secondary">Load Scenario</button>
    <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;">⚖️ Compare Scenarios</button>
    <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;">📊 Export CSV</button>
    <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;">📄 Download PDF</button>
    <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
  </div>
  <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
    <strong>PDF</strong> — Full plan report with chart, Monte Carlo (if run), and year-by-year table.
  </p>
</div>
<?php endif; ?>

    <form id="planForm">
      <h3>About you</h3>
      <div class="form-grid">
        <div>
          <label class="field-label" for="birthYear">Birth year</label>
          <input type="number" id="birthYear" min="1920" max="<?php echo date('Y'); ?>" value="<?php echo $defaultBirthYear; ?>" required>
          <small id="fraHint">Used to estimate your age today and Social Security Full Retirement Age.</small>
        </div>
        <div id="retirementAgeWrap">
          <label class="field-label" for="retirementAge">Planned retirement age</label>
          <input type="number" id="retirementAge" min="18" max="100" value="65" required>
          <small>When you expect to stop working and start drawing from savings.</small>
        </div>
      </div>
      <div style="margin-bottom: 22px;">
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:14px;">
          <input type="checkbox" id="alreadyRetired"> I'm already retired
        </label>
      </div>

      <h3>Money today</h3>
      <div class="form-grid">
        <div>
          <label class="field-label" for="balance">Retirement savings today ($)</label>
          <input type="number" id="balance" min="0" step="1000" value="850000" required>
          <small>401(k), IRA, brokerage — one combined total for this snapshot.</small>
        </div>
        <div>
          <label class="field-label" for="portfolioWithdrawalStartAge">Portfolio withdrawals for spending begin at age</label>
          <input type="number" id="portfolioWithdrawalStartAge" min="18" max="100" step="1" value="">
          <small>When you'll start drawing from savings to cover spending (after Social Security). RMDs still apply when required. For a calendar date (e.g. Jan 2027), use the age you'll be that year.</small>
        </div>
        <div id="annualContributionWrap">
          <label class="field-label" for="annualContribution">Annual contributions until retirement ($)</label>
          <input type="number" id="annualContribution" min="0" step="500" value="12000">
        </div>
        <div id="returnPreRetirementWrap">
          <label class="field-label" for="returnPreRetirement">Expected return before retirement (%)</label>
          <input type="number" id="returnPreRetirement" min="0" max="15" step="0.1" value="6">
          <small>Only used if you are still saving and growing your portfolio before retirement.</small>
        </div>
      </div>

      <h3>Spending in retirement</h3>
      <p style="color:#4b5563;margin:0 0 14px;font-size:14px;line-height:1.5;">
        What you expect to <strong>spend</strong> each year — household expenses, not your income.
      </p>
      <div style="margin-bottom: 12px;">
        <label style="margin-right: 16px;"><input type="radio" name="spendingMethod" value="estimate" checked> Estimate from current spending</label>
        <label><input type="radio" name="spendingMethod" value="direct"> Enter annual spending directly</label>
      </div>

      <div id="estimateSpendingWrap" class="form-grid">
        <div>
          <label class="field-label" for="currentMonthlySpending">Household monthly spending ($)</label>
          <input type="number" id="currentMonthlySpending" min="0" step="50" value="6000">
          <small>What your household spends now (or expects to spend in retirement) — not gross income.</small>
        </div>
        <div>
          <label class="field-label" for="retirementSpendingPct">Retirement spending (% of current)</label>
          <input type="number" id="retirementSpendingPct" min="40" max="120" value="80">
          <small>Many planners use 70–80% while still working. Already retired? This is set to 100%.</small>
        </div>
      </div>

      <div id="directSpendingWrap" class="form-grid" style="display:none;">
        <div>
          <label class="field-label" for="annualSpendingDirect">Annual household spending in retirement ($)</label>
          <input type="number" id="annualSpendingDirect" min="0" step="1000" value="72000">
          <small>Total yearly expenses for your household — not income.</small>
        </div>
      </div>

      <h3 style="margin-top: 28px;">Guaranteed income in retirement</h3>
      <p style="color:#4b5563;margin:0 0 14px;font-size:14px;line-height:1.5;">
        Steady income from Social Security, pensions, and similar sources. Each person's Social Security is entered separately.
      </p>

      <h4 style="margin: 0 0 12px; font-size: 15px; color: #374151;">Your Social Security</h4>
      <div style="margin-bottom: 12px;">
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:14px;">
          <input type="checkbox" id="ssAlreadyReceiving"> I'm already receiving my benefit
        </label>
      </div>
      <div id="ssEstimateWrap" class="form-grid">
        <div>
          <label class="field-label" for="ssPiaMonthly">Your benefit at Full Retirement Age ($/month)</label>
          <input type="number" id="ssPiaMonthly" min="0" step="50" value="2800">
          <small>From <em>your</em> SSA statement (PIA at FRA) — for future claiming estimates.</small>
        </div>
        <div>
          <label class="field-label" for="ssClaimAge">You plan to claim at age</label>
          <select id="ssClaimAge">
            <?php for ($a = 62; $a <= 70; $a++): ?>
              <option value="<?php echo $a; ?>"<?php echo $a === 67 ? ' selected' : ''; ?>><?php echo $a; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div id="ssReceivingWrap" class="form-grid" style="display:none;">
        <div>
          <label class="field-label" for="ssCurrentMonthly">Your current monthly benefit ($)</label>
          <input type="number" id="ssCurrentMonthly" min="0" step="0.01" value="0">
          <small>Gross amount from your current SSA deposit (before Medicare is deducted).</small>
        </div>
      </div>

      <h4 style="margin: 18px 0 12px; font-size: 15px; color: #374151;">Spouse's Social Security <span style="font-weight: 400; color: #6b7280;">(optional)</span></h4>
      <div style="margin-bottom: 12px;">
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:14px;">
          <input type="checkbox" id="spouseSsAlreadyReceiving"> Spouse is already receiving their benefit
        </label>
      </div>
      <div id="spouseSsEstimateWrap" class="form-grid">
        <div>
          <label class="field-label" for="spouseSsMonthly">Spouse's benefit at Full Retirement Age ($/month)</label>
          <input type="number" id="spouseSsMonthly" min="0" step="50" value="0">
          <small>From <em>spouse's</em> SSA statement. Leave at 0 if not applicable.</small>
        </div>
        <div>
          <label class="field-label" for="spouseSsClaimAge">Spouse plans to claim at age</label>
          <select id="spouseSsClaimAge">
            <?php for ($a = 62; $a <= 70; $a++): ?>
              <option value="<?php echo $a; ?>"<?php echo $a === 67 ? ' selected' : ''; ?>><?php echo $a; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div id="spouseSsReceivingWrap" class="form-grid" style="display:none;">
        <div>
          <label class="field-label" for="spouseSsCurrentMonthly">Spouse's current monthly benefit ($)</label>
          <input type="number" id="spouseSsCurrentMonthly" min="0" step="0.01" value="0">
          <small>Gross amount from spouse's current SSA deposit.</small>
        </div>
      </div>
      <div class="form-grid">
        <div>
          <label class="field-label" for="otherGuaranteedAnnual">Other guaranteed income ($/year)</label>
          <input type="number" id="otherGuaranteedAnnual" min="0" step="1000" value="0">
          <small>Pension, annuity, rental, etc. — do not include Social Security here.</small>
        </div>
      </div>

      <details class="advanced" open>
        <summary>Tax &amp; RMD assumptions</summary>
        <div class="form-grid" style="margin-top: 14px;">
          <div>
            <label class="field-label" for="filingStatus">Tax filing status</label>
            <select id="filingStatus">
              <option value="married" selected>Married filing jointly</option>
              <option value="single">Single</option>
              <option value="hoh">Head of household</option>
            </select>
          </div>
          <div>
            <label class="field-label" for="taxDeferredPct">Tax-deferred share of portfolio (%)</label>
            <input type="number" id="taxDeferredPct" min="0" max="100" step="1" value="85">
            <small>Portion subject to RMDs (Traditional IRA, 401(k), etc.). Roth/brokerage is the remainder.</small>
          </div>
          <div>
            <label class="field-label" for="spouseBeneficiary">Spouse is sole IRA beneficiary?</label>
            <select id="spouseBeneficiary" onchange="toggleSpouseAgeField()">
              <option value="no">No</option>
              <option value="yes">Yes</option>
            </select>
            <small>If yes and spouse is 10+ years younger, a lower RMD divisor may apply.</small>
          </div>
          <div id="spouseAgeGroup" style="display:none;">
            <label class="field-label" for="spouseAge">Spouse's current age</label>
            <input type="number" id="spouseAge" min="18" max="100" value="56">
          </div>
        </div>
      </details>

      <details class="advanced">
        <summary>Other advanced assumptions</summary>
        <div class="form-grid" style="margin-top: 14px;">
          <div>
            <label class="field-label" for="withdrawalRate">Withdrawal rate for on-track check (%)</label>
            <input type="number" id="withdrawalRate" min="0.5" max="10" step="0.1" value="4">
          </div>
          <div>
            <label class="field-label" for="returnRetirement">Expected return in retirement (%)</label>
            <input type="number" id="returnRetirement" min="0" max="12" step="0.1" value="5">
          </div>
          <div>
            <label class="field-label" for="inflation">Inflation on spending (%)</label>
            <input type="number" id="inflation" min="0" max="8" step="0.1" value="2.5">
          </div>
          <div>
            <label class="field-label" for="colaRate">Social Security COLA (%)</label>
            <input type="number" id="colaRate" min="0" max="8" step="0.1" value="2.5">
          </div>
          <div>
            <label class="field-label" for="volatility">Return volatility for stress test (%)</label>
            <input type="number" id="volatility" min="0" max="50" step="0.5" value="12">
            <small>Standard deviation of annual returns (Premium Monte Carlo). Typical: 10–15%.</small>
          </div>
          <div>
            <label class="field-label" for="simulations">Monte Carlo simulations</label>
            <input type="number" id="simulations" min="100" max="5000" step="100" value="1000">
            <small>Premium stress test runs (100–5,000).</small>
          </div>
        </div>
      </details>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Build my plan</button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your retirement plan snapshot</h2>

      <div id="statusCard" class="status-card">
        <div id="statusHeadline" style="font-size: 22px; font-weight: 800; margin-bottom: 8px;"></div>
        <div id="statusDetail" style="color: #374151; line-height: 1.55;"></div>
        <div class="metrics-grid">
          <div class="metric-box">
            <div class="label" id="metricProjectedLabel">Projected at retirement</div>
            <div class="value" id="metricProjected">—</div>
          </div>
          <div class="metric-box">
            <div class="label">Rule-of-thumb target</div>
            <div class="value" id="metricTarget">—</div>
          </div>
          <div class="metric-box">
            <div class="label">Income (when plan is fully running)</div>
            <div class="value" id="metricIncome">—</div>
          </div>
          <div class="metric-box">
            <div class="label">Lifetime est. federal tax (retirement years)</div>
            <div class="value" id="metricLifetimeTax">—</div>
          </div>
        </div>
        <p id="portfolioWithdrawalNote" style="margin: 8px 0 0; font-size: 14px; color: #4b5563; display: none;"></p>
        <p id="rmdNote" style="margin: 8px 0 0; font-size: 14px; color: #4b5563;"></p>
        <p id="depletedNote" style="margin: 12px 0 0; font-size: 14px; color: #4b5563;"></p>
      </div>

      <div class="chart-section">
        <h3>Portfolio balance over time</h3>
        <div class="chart-wrap">
          <canvas id="planChart"></canvas>
        </div>
      </div>

      <div class="table-section">
        <h3>Key milestones</h3>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Age</th>
                <th>Portfolio</th>
                <th>Withdrawal</th>
                <th>Social Security (household)</th>
                <th>Other income</th>
                <th>RMD</th>
                <th>Est. federal tax</th>
                <th>Total income</th>
              </tr>
            </thead>
            <tbody id="milestoneBody"></tbody>
          </table>
        </div>
      </div>

      <div class="table-section">
        <h3>Year-by-year timeline</h3>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Age</th>
                <th>Portfolio</th>
                <th>Withdrawal</th>
                <th>Social Security (household)</th>
                <th>Other income</th>
                <th>RMD</th>
                <th>Est. federal tax</th>
                <th>Total income</th>
              </tr>
            </thead>
            <tbody id="fullTableBody"></tbody>
          </table>
        </div>
      </div>

      <div class="chart-section" id="monteCarloSection">
        <h3>Monte Carlo stress test <?php if ($isPremium): ?><span style="font-size:14px;color:#0d9488;font-weight:600;">Premium</span><?php endif; ?></h3>
        <p style="color:#4b5563;margin-bottom:12px;">
          Thousands of random market scenarios test whether your plan lasts through age 90,
          using the same spending, Social Security, and RMD rules as your timeline above.
        </p>
        <div id="mcResultsWrap">
          <div id="mcPremiumResults" style="<?php echo $isPremium ? '' : 'display:none;'; ?>">
            <div class="mc-success-rate" id="mcSuccessRate">—</div>
            <p id="mcSummaryText" style="color:#374151;line-height:1.6;margin:0;"></p>
            <div class="mc-chart-wrap">
              <canvas id="mcDistributionChart"></canvas>
            </div>
          </div>
          <?php if (!$isPremium): ?>
          <div id="mcFreeUpsell" style="padding:24px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12px;color:#fff;text-align:center;">
            <p style="margin:0 0 8px;font-size:28px;font-weight:800;" class="mc-locked">72.4%</p>
            <p style="margin:0 0 16px;opacity:0.95;">See the probability your plan lasts through age 90 under thousands of market scenarios.</p>
            <a href="/premium.html" style="display:inline-block;background:#fff;color:#667eea;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;">Upgrade to Premium</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($isPremium): ?>
      <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
        <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
        <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific plan.</p>
      </div>
      <?php endif; ?>

      <div class="info-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 24px 0; border-radius: 8px;">
        <h3 style="color: #92400e; margin-top: 0;">Educational model only</h3>
        <p style="margin: 0; color: #78350f; line-height: 1.6;">
          Federal tax estimates are simplified (standard deduction, 50% of Social Security treated as taxable, no state tax, IRMAA, or NIIT).
          RMDs apply to the tax-deferred portion of your portfolio only. Monte Carlo uses random annual returns (not a forecast of actual markets).
          This is educational — not tax or financial advice.
        </p>
      </div>

      <h3>Dig deeper with specialized tools</h3>
      <p style="color:#4b5563;margin:0 0 12px;font-size:14px;">These open in a new tab with your plan numbers pre-filled.</p>
      <div class="deep-links">
        <a id="deepLinkSs" href="../social-security-claiming-analyzer/" target="_blank" rel="noopener">Social Security Claiming Analyzer →</a>
        <a id="deepLinkPlanSuccess" href="../plan-success/" target="_blank" rel="noopener">Plan Success (Monte Carlo) →</a>
        <a id="deepLinkRoth" href="../roth-conv/" target="_blank" rel="noopener">Roth Conversion Calculator →</a>
        <a id="deepLinkRmd" href="../rmd-impact/" target="_blank" rel="noopener">RMD Impact →</a>
        <a id="deepLinkSpending" href="../retirement-spending-checkup/" target="_blank" rel="noopener">Spending &amp; On-Track Checkup →</a>
        <a id="deepLinkSsGap" href="../ss-gap/" target="_blank" rel="noopener">Social Security + Spending Gap →</a>
        <a id="deepLinkNestEgg" href="../nest-egg-target/" target="_blank" rel="noopener">Nest Egg Target →</a>
      </div>

      <?php
        $share_title = 'Retirement Plan Builder';
        $share_text = 'Check out the Retirement Plan Builder at ronbelisle.com — one timeline for savings, Social Security, and retirement spending.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'See Your Complete Retirement Timeline';
      $premium_upsell_text = 'Upgrade to Premium for the full year-by-year table through age 90, Monte Carlo stress test, PDF report, save and compare scenarios, CSV export, and AI explanations of your plan.';
      include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/lib/finance-core.js"></script>
  <script src="../js/lib/rmd-tax-core.js"></script>
  <script src="../js/lib/url-prefill.js"></script>
  <script src="deep-links.js"></script>
  <script src="plan-engine.js"></script>
  <script src="monte-carlo-engine.js"></script>
  <script src="../js/share-results.js"></script>
  <script src="../js/explain-results-modal.js"></script>
  <script src="../js/compare-scenarios-modal.js"></script>
  <script>
    function toggleSpouseAgeField() {
      var sel = document.getElementById('spouseBeneficiary');
      var grp = document.getElementById('spouseAgeGroup');
      if (grp && sel) grp.style.display = sel.value === 'yes' ? 'block' : 'none';
    }
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
  </script>
  <script src="calculator.js"></script>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
