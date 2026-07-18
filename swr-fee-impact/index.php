<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="See how AUM and fund fees change the 4% rule. Estimate fee-adjusted spending power and ending wealth — fees do not cut the safe withdrawal rate dollar-for-dollar.">
  <title>Safe Withdrawal Rate &amp; Fee Impact</title>
  <?php
    $og_title = $ld_name = 'Safe Withdrawal Rate & Fee Impact';
    $og_description = $ld_description = 'See how AUM and fund fees change the 4% rule. Estimate fee-adjusted spending power and ending wealth — fees do not cut the safe withdrawal rate dollar-for-dollar.';
    include(__DIR__ . '/../includes/og-twitter-meta.php');
    include(__DIR__ . '/../includes/json-ld-softwareapp.php');
  ?>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

    <header>
      <h1>Safe Withdrawal Rate &amp; Fee Impact</h1>
      <p class="sub">
        The 4% rule is a <em>gross</em> portfolio withdrawal idea. Assets under management (AUM) fees come out of the account, so they change
        both spending power and ending wealth — but <strong>not</strong> by simply subtracting the fee from 4%
        (for example, 4% − 1% is not 3%).
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Research-style estimates (for example, work popularized by Michael Kitces) suggest a 1% investment expense
        might reduce a 4% safe withdrawal rate toward about <strong>3.6%</strong>, not 3%, because fees shrink when
        the portfolio shrinks in bad markets. This calculator shows that spending impact — and the much larger effect
        fees can have on ending wealth when markets are kinder.
      </p>
      <p style="margin-top: 8px;">
        Use <strong>Simple model</strong> for a constant-return estimate, or <strong>Sequence scenarios</strong> for
        three teaching paths (tough / typical / favorable). This is not a full historical backtest like
        <a href="https://ficalc.app/" target="_blank" rel="noopener noreferrer">FiCalc</a>; it focuses on the fee question.
      </p>
    </div>

    <form id="swrFeeForm">
      <h3>Your portfolio &amp; fees</h3>
      <div id="validationError" role="alert" style="display: none; margin-bottom: 15px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #b91c1c; font-size: 14px;"></div>

      <div class="input-grid">
        <div>
          <label for="portfolioValue" style="display: block; margin-bottom: 5px; font-weight: 600;">Portfolio value ($)</label>
          <input type="number" id="portfolioValue" value="100000" min="1000" step="any" inputmode="numeric"
                 style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
          <small style="color: #666;">Default matches common “smaller balance” examples; enter your own amount.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Baseline SWR (before AUM)</span>
            <span class="value" id="baselineSwrLabel"></span>
          </div>
          <input type="range" id="baselineSwr" min="2.5" max="5.5" step="0.1" value="4.0">
          <small style="color: #666;">Common rule of thumb: 4%.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>AUM / advisor fee</span>
            <span class="value" id="aumFeeLabel"></span>
          </div>
          <input type="range" id="aumFee" min="0" max="2.5" step="0.05" value="1.0">
          <small style="color: #666;">Typical advice: ~1%. Smaller balances may face higher % or minimum fees.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Fund expense ratio</span>
            <span class="value" id="fundErLabel"></span>
          </div>
          <input type="range" id="fundEr" min="0" max="1.5" step="0.01" value="0.05">
          <small style="color: #666;">Low-cost index funds are often ~0.03–0.10%; active funds can be much higher.</small>
        </div>

        <div class="slider-row">
          <div class="slider-label">
            <span>Retirement length</span>
            <span class="value" id="yearsLabel"></span>
          </div>
          <input type="range" id="years" min="20" max="40" step="1" value="30">
        </div>
      </div>

      <h3 style="margin-top: 8px;">Calculation mode</h3>
      <div class="mode-toggle">
        <label>
          <input type="radio" name="engineMode" value="simple" checked>
          <span><strong>Simple model</strong> — constant real return; clear fee-adjusted SWR estimate</span>
        </label>
        <label>
          <input type="radio" name="engineMode" value="sequence">
          <span><strong>Sequence scenarios</strong> — tough / typical / favorable teaching paths</span>
        </label>
      </div>

      <div id="simpleExtras" class="input-grid" style="margin-top: 16px;">
        <div class="slider-row">
          <div class="slider-label">
            <span>Expected real return (before fees)</span>
            <span class="value" id="realReturnLabel"></span>
          </div>
          <input type="range" id="realReturn" min="2" max="7" step="0.25" value="4.5">
          <small style="color: #666;">Used only in Simple mode. Spending stays flat in real terms.</small>
        </div>
      </div>

      <div id="scenarioBlock" hidden style="margin-top: 16px;">
        <h3>Retirement start scenario</h3>
        <div class="scenario-toggle" id="scenarioToggle"></div>
        <p id="scenarioHelp" style="font-size: 13px; color: #4b5563; margin-top: 8px;"></p>
      </div>

      <div style="text-align: center; margin: 28px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          Calculate fee impact
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>What you can spend</h2>
      <div class="swr-hero-grid">
        <div class="swr-hero-card">
          <div class="label">Gross year-1 withdrawal</div>
          <div class="value" id="grossSpend"></div>
          <div class="hint" id="grossSpendHint"></div>
        </div>
        <div class="swr-hero-card warn">
          <div class="label">Fee-adjusted spending power</div>
          <div class="value" id="netSpend"></div>
          <div class="hint" id="netSpendHint"></div>
        </div>
        <div class="swr-hero-card warn">
          <div class="label">Implied spendable SWR</div>
          <div class="value" id="impliedSwr"></div>
          <div class="hint" id="impliedSwrHint"></div>
        </div>
      </div>
      <p id="swrNarrative" style="font-size: 15px; color: #374151; line-height: 1.55; margin: 12px 0 24px;"></p>

      <h2>Ending wealth (same spending path)</h2>
      <p style="font-size: 14px; color: #4b5563; margin-bottom: 8px;">
        Both sides use your <strong>baseline</strong> withdrawal rate (inflation-adjusted). Only fees differ.
      </p>
      <div class="comparison-mini">
        <div class="col">
          <h4>Low fund fees only</h4>
          <div class="big" id="endNoAum"></div>
          <div class="sub" id="endNoAumSub"></div>
        </div>
        <div class="col">
          <h4>With your AUM fee</h4>
          <div class="big" id="endWithAum"></div>
          <div class="sub" id="endWithAumSub"></div>
        </div>
        <div class="col">
          <h4>Opportunity cost</h4>
          <div class="big" id="endGap"></div>
          <div class="sub" id="endGapSub"></div>
        </div>
      </div>

      <div class="chart-container">
        <h3>Portfolio value over time</h3>
        <div class="chart-wrap">
          <canvas id="portfolioChart" role="img" aria-label="Portfolio value with and without AUM fees"></canvas>
        </div>
      </div>

      <div class="info-box-blue" style="margin: 20px 0;">
        <h3 style="margin-top: 0;">Advice vs AUM pricing</h3>
        <p id="adviceNote">
          A good advisor relationship can be valuable. The open question is whether you need to pay a percentage of
          your wealth every year for decades — many people use hourly, flat-fee, or advice-only models instead.
          Compare long-run fee drag in
          <a href="../managed-vs-vanguard/">Managed vs Vanguard</a>.
          For a nest-egg target at a chosen withdrawal rate, see
          <a href="../nest-egg-target/">Nest Egg Target</a>.
        </p>
      </div>

      <div class="info-box-blue" style="margin: 20px 0; background: #f8fafc; border-color: #cbd5e1;">
        <h3 style="margin-top: 0;">Assumptions &amp; limits</h3>
        <p id="assumptionsText" style="margin: 0; font-size: 14px; color: #475569;"></p>
      </div>

      <?php if ($isPremium): ?>
      <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
        <button type="button" id="explainResultsBtnInResults" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">🤖 Explain my results</button>
        <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534; line-height: 1.5;">Get AI-generated plain-language explanations of your specific results.</p>
      </div>
      <?php endif; ?>

      <?php
        $share_title = 'Safe Withdrawal Rate & Fee Impact';
        $share_text  = 'See how AUM fees change the 4% rule — spending power and ending wealth — at ronbelisle.com.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Unlock Premium Features';
      $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios, export PDFs and CSVs, get AI-generated plain-language explanations of your specific results, and more across all calculators.';
      include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/lib/url-prefill.js"></script>
  <script src="../js/share-results.js"></script>
  <script src="../js/explain-results-modal.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script src="scenario-paths.js"></script>
  <script src="calculator.js"></script>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
