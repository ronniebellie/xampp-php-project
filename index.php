<?php
session_start();
require_once 'includes/db_config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';

// Check premium status if logged in
$is_premium = false;
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sub = null;
    $stmt->bind_result($sub);
    $user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
    $stmt->close();
    $is_premium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("includes/analytics.php"); ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ron's Homepage</title>
  <style>
    :root{
      --max: 980px;
      --bg: #f6f7fb;
      --paper: #ffffff;
      --text: #0f172a;
      --muted: rgba(15,23,42,.68);
      --border: rgba(15,23,42,.14);
      --accent: #1d4ed8;
      --shadow: 0 14px 34px rgba(15,23,42,.14);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
      color:var(--text);
      background:
        linear-gradient(180deg,#f9fafb 0%,var(--bg) 55%,#f3f4f6 100%),
        repeating-linear-gradient(0deg,rgba(15,23,42,.03) 0 1px,transparent 1px 34px),
        repeating-linear-gradient(90deg,rgba(15,23,42,.02) 0 1px,transparent 1px 34px);
    }
    .wrap{max-width:var(--max);margin:0 auto;padding:24px 18px 44px}
    .topbar{
      display:flex;align-items:center;justify-content:space-between;gap:16px;
      padding:28px 32px;margin-bottom:20px;
      border:2px solid var(--border);
      background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      border-radius:var(--radius);
      box-shadow:0 4px 20px rgba(15,23,42,.08), 0 0 0 1px rgba(29,78,216,.06);
    }
    .brand{display:flex;align-items:center;gap:16px;min-width:0;flex:1}
    .mark{
      width:56px;height:56px;border-radius:16px;border:2px solid rgba(29,78,216,.2);
      background:linear-gradient(135deg,rgba(29,78,216,.15),rgba(29,78,216,.06));
      display:grid;place-items:center;font-weight:850;letter-spacing:-.02em;color:var(--accent);flex:0 0 auto;font-size:20px;
    }
    .brand-text{flex:1;min-width:0}
    .brand-title{
      font-size:24px;font-weight:850;letter-spacing:-.01em;margin:0;color:var(--accent);line-height:1.25;
    }
    .brand-tagline{
      font-size:15px;color:var(--muted);margin:8px 0 0;line-height:1.5;max-width:560px;
    }
    .brand-subtitle{
      font-size:14px;color:var(--muted);margin:10px 0 0;
    }
    .brand-subtitle a{
      color:var(--accent);text-decoration:none;font-weight:600;
    }
    .brand-subtitle a:hover{text-decoration:underline}
    
    /* Premium Banner */
    .premium-banner {
      background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 20px;
      color: white;
      box-shadow: 0 8px 24px rgba(44, 82, 130, 0.25);
      border: 1px solid rgba(255,255,255,0.1);
    }
    .premium-banner-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .premium-banner-text {
      flex: 1;
    }
    .premium-banner h2 {
      margin: 0 0 8px 0;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }
    .premium-banner p {
      margin: 0;
      opacity: 0.95;
      font-size: 15px;
      line-height: 1.5;
    }
    .premium-banner-features {
      display: flex;
      gap: 20px;
      margin-top: 12px;
      flex-wrap: wrap;
    }
    .premium-feature-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      opacity: 0.95;
    }
    .premium-feature-item::before {
      content: "✓";
      font-weight: bold;
      color: #48bb78;
    }
    .premium-banner-cta {
      display: inline-block;
      background: white;
      color: #2c5282;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .premium-banner-cta:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    .premium-banner.member {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }
    .premium-banner.member .premium-banner-cta {
      background: rgba(255,255,255,0.2);
      color: white;
      border: 2px solid white;
    }
    
    /* Mobile tweaks for header & premium banner */
    @media (max-width: 640px) {
      .wrap {
        padding: 18px 14px 32px;
      }
      .topbar {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px 18px;
        gap: 12px;
      }
      .brand {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      .mark {
        display: none;
      }
      .brand-title {
        font-size: 20px;
      }
      .brand-tagline {
        font-size: 14px;
        max-width: none;
      }
      .premium-banner {
        padding: 20px 18px;
      }
      .premium-banner-content {
        flex-direction: column;
        align-items: flex-start;
      }
      .premium-banner-cta {
        width: 100%;
        text-align: center;
        justify-content: center;
        display: inline-flex;
      }
    }
    
    .section{
      margin-top:18px;display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;
      padding:6px 4px 0;
    }
    h2{margin:0;font-size:18px;letter-spacing:-.01em}
    .hint{margin:0;font-size:13px;color:var(--muted)}
    .grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:14px;margin-top:14px}
    @media (min-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .card{
      border:1px solid rgba(15,23,42,.12);
      background:var(--paper);
      box-shadow:0 10px 22px rgba(15,23,42,.08);
      border-radius:var(--radius);
      padding:16px;
      display:flex;flex-direction:column;gap:10px;min-height:160px;position:relative;
      transition:transform 120ms ease, background 120ms ease;
    }
    .card:hover{transform:translateY(-2px);background:#fff}
    .card h3{margin:0;font-size:16px;letter-spacing:-.01em}
    .card p{margin:0;color:var(--muted);flex:1}
    .card::after{
      content:"";position:absolute;top:14px;right:14px;width:78px;height:26px;opacity:.22;
      background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='156' height='52' viewBox='0 0 156 52'><path d='M4 40 C20 36, 26 46, 38 38 S62 20, 76 26 S98 42, 112 30 S132 10, 152 14' fill='none' stroke='%231d4ed8' stroke-width='4' stroke-linecap='round'/></svg>");
      background-size:cover;background-repeat:no-repeat;pointer-events:none;
    }
    .section-heading {
      font-size: 20px;
      font-weight: 800;
      color: var(--text);
      margin: 32px 0 8px 0;
      letter-spacing: -0.01em;
    }
    .section-heading:first-of-type { margin-top: 0; }
    .section-divider {
      margin: 48px 0 0;
      padding: 32px 0 0;
      border-top: 3px solid var(--border);
      position: relative;
    }
    .section-divider::before {
      content: "";
      position: absolute;
      top: -3px;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--accent) 0%, transparent 100%);
      opacity: 0.35;
      border-radius: 2px;
    }
    .card.coming-soon .btn {
      background: #e2e8f0;
      color: #64748b;
      border-color: #cbd5e1;
      cursor: not-allowed;
      pointer-events: none;
    }

    .card.coming-soon .coming-badge {
      display: inline-block;
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-left: 6px;
    }
    .btn{
      display:inline-block;text-decoration:none;font-weight:750;font-size:14px;
      padding:10px 14px;border-radius:14px;border:1px solid rgba(29,78,216,.28);
      background:rgba(29,78,216,.10);color:var(--accent);
    }
    .btn:hover{background:rgba(29,78,216,.14)}

    /* Subscription buttons */
    .subscribe-btn {
      color: #059669;
      font-weight: 700;
    }
    .subscribe-btn:hover {
      text-decoration: underline;
    }
    .premium-badge {
      color: #d97706;
      font-weight: 700;
    }

    hr.footer-sep {
      border: 0;
      border-top: 1px solid rgba(15,23,42,.12);
      margin: 22px 0 14px;
    }

    .site-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      color: var(--muted);
      font-size: 13px;
      padding-bottom: 10px;
    }

    .footer-left { margin: 0; }

    .footer-right {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
      text-align: right;
    }

    .donate-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 8px 14px;
      border-radius: 999px;
      border: 1px solid rgba(15,23,42,.14);
      background: rgba(15,23,42,.03);
      color: var(--text);
      text-decoration: none;
      font-weight: 700;
      line-height: 1;
      white-space: nowrap;
    }

    .donate-button:hover {
      background: rgba(15,23,42,.06);
    }

    @media (max-width: 720px) {
      .topbar{flex-direction:column;align-items:flex-start;padding:20px}
      .brand-title{font-size:20px}
      .brand-tagline{font-size:14px}
      .section-divider{margin-top:36px;padding-top:24px}
      .premium-banner-content {
        flex-direction: column;
        align-items: flex-start;
      }
      .premium-banner-cta {
        width: 100%;
        text-align: center;
      }
      .footer-right {
        width: 100%;
        justify-content: flex-start;
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">

    <div class="topbar" role="banner">
      <div class="brand">
        <div class="mark" aria-hidden="true">RB</div>
        <div class="brand-text">
          <h1 class="brand-title">Free web apps for sound financial planning</h1>
          <p class="brand-tagline">Free calculators for retirement planning (Boomers and Gen X) and for building a solid financial foundation (Millennials and Gen Z)</p>
          <?php if ($isLoggedIn): ?>
            <p class="brand-subtitle">
              Welcome back, <strong><?php echo htmlspecialchars($userName); ?></strong>! 
              <a href="auth/logout.php">Log out</a>
              <?php if (!$is_premium): ?>
                | <a href="subscribe.php" class="subscribe-btn">Upgrade to Premium</a>
              <?php else: ?>
                | <span class="premium-badge">✨ Premium Member</span>
              <?php endif; ?>
            </p>
          <?php else: ?>
            <p class="brand-subtitle">
              All calculators are free to use—no account needed. <a href="auth/login.php">Log in</a> or <a href="auth/register.php">Sign up</a> for premium features: save scenarios, export PDFs, and compare results across all tools.
            </p>
          <?php endif; ?>
          <hr style="margin: 14px 0 10px 0; border: 0; border-top: 1px solid var(--border);" />
          <p class="brand-subtitle">For retirement advisors &amp; planners: we offer these calculators with your own branding — <a href="https://calcforadvisors.com" target="_blank" rel="noopener">Click here to learn more</a>.</p>
        </div>
      </div>
    </div>

    <?php if ($is_premium): ?>
      <!-- Premium Member Banner -->
      <div class="premium-banner member">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>✨ You're a Premium Member!</h2>
            <p>Enjoy unlimited scenario saving and comparing, PDF and CSV exports, and advanced projections across all calculators.</p>
          </div>
          <a href="account.php" class="premium-banner-cta">Manage Account</a>
        </div>
      </div>
    <?php elseif ($isLoggedIn): ?>
      <!-- Upgrade Prompt for Logged-In Free Users -->
      <div class="premium-banner">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>Unlock Premium Features</h2>
            <p>Save and compare scenarios, export PDF and CSV reports, and access advanced projections.</p>
            <div class="premium-banner-features">
              <div class="premium-feature-item">Save & Compare Scenarios</div>
              <div class="premium-feature-item">PDF Reports</div>
              <div class="premium-feature-item">Advanced Projections</div>
            </div>
          </div>
          <a href="premium.html" class="premium-banner-cta">Try Free for 7 Days</a>
        </div>
      </div>
    <?php endif; ?>

    <h2 class="section-heading" id="retirement">For folks in or near retirement (Boomers and Gen X)</h2>
    <p class="hint">RMDs, Social Security, Roth conversions, and retirement income planning.</p>
    <main class="grid" aria-label="Retirement app links">
      <section class="card">
        <h3>RMD Impact</h3>
        <p>Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time.</p>
        <a class="btn" href="rmd-impact/">Open</a>
      </section>

      <section class="card">
        <h3>Retirement Spending &amp; On-Track Checkup</h3>
        <p>Estimate a retirement budget from your current spending, factor in guaranteed income, and see whether your savings look on track using a simple withdrawal-rate rule of thumb.</p>
        <a class="btn" href="retirement-spending-checkup/">Open</a>
      </section>

      <section class="card">
        <h3>Future Value Calculator</h3>
        <p>Calculate present value, future value, annuities, and required payments to reach your financial goals.</p>
        <a class="btn" href="future-value-app/">Open</a>
      </section>

      <section class="card">
        <h3>Social Security Claiming Analyzer</h3>
        <p>Compare claiming ages and see how lifetime Social Security benefits change over time.</p>
        <a class="btn" href="social-security-claiming-analyzer/">Open</a>
      </section>

      <section class="card">
        <h3>Social Security + Spending Gap Calculator</h3>
        <p>See how Social Security reduces the portfolio you need by identifying your real retirement spending gap.</p>
        <a class="btn" href="ss-gap/">Open</a>
      </section>

      <section class="card">
        <h3>Roth Conversion Calculator</h3>
        <p>Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds.</p>
        <a class="btn" href="roth-conv/">Open</a>
      </section>

      <section class="card">
        <h3>Required vs. Desired Spending Calculator</h3>
        <p>Separate essential expenses from discretionary spending to calculate the minimum portfolio needed for security and the ideal portfolio for your full retirement lifestyle.</p>
        <a class="btn" href="required-vs-desired/">Open</a>
      </section>

      <section class="card">
        <h3>Managed Portfolio vs Vanguard Index Fund</h3>
        <p>See the true cost of advisor fees - including opportunity cost - compared to low-cost Vanguard index funds.</p>
        <a class="btn" href="managed-vs-vanguard/">Open</a>
      </section>

      <section class="card">
        <h3>Survivor Gap Calculator</h3>
        <p>Compare single-life vs joint-life annuity payouts and see how life insurance could fill the gap for your surviving spouse.</p>
        <a class="btn" href="survivor-gap/">Open</a>
      </section>

      <section class="card">
        <h3>Debt Payoff Calculator</h3>
        <p>Pay down debt before retirement—compare avalanche vs snowball, see payoff timelines, and how extra payments save interest.</p>
        <a class="btn" href="debt-payoff/">Open</a>
      </section>

      <section class="card">
        <h3>Pension vs. Lump Sum</h3>
        <p>See how many years it takes for the pension to “pay back” the lump sum and how your life expectancy affects the choice.</p>
        <a class="btn" href="pension-vs-lump-sum/">Open</a>
      </section>

      <section class="card">
        <h3>Retirement Timeline &amp; Checklist</h3>
        <p>Turn your target retirement date into a simple, phased checklist of tasks—from early prep to your last day at work and first year in retirement.</p>
        <a class="btn" href="retirement-timeline/">Open</a>
      </section>

      <section class="card">
        <h3>Plan Success (Monte Carlo)</h3>
        <p>See the probability of your portfolio lasting through retirement using random market return simulations.</p>
        <a class="btn" href="plan-success/">Open</a>
      </section>
    </main>

    <div class="section-divider">
    <h2 class="section-heading" id="early-career">For folks building or strengthening their foundation (Millennials and Gen Z)</h2>
    <p class="hint">Debt payoff, emergency fund, down payment, and getting on track for retirement—whether you're just starting or catching up.</p>
    <main class="grid" aria-label="Early career app links">
      <section class="card">
        <h3>Debt Payoff Calculator</h3>
        <p>Compare avalanche vs snowball, see payoff timelines, and see how extra payments shorten your journey and save interest.</p>
        <a class="btn" href="debt-payoff/">Open</a>
      </section>

      <section class="card">
        <h3>Debt vs Saving: Which First?</h3>
        <p>Compare putting extra cash toward high-interest debt versus investing it for retirement and see which leaves you with higher net worth over time.</p>
        <a class="btn" href="debt-vs-saving/">Open</a>
      </section>

      <section class="card">
        <h3>Emergency Fund Builder</h3>
        <p>Set a target (e.g. 3–6 months of expenses) and see how long it takes to get there at your savings rate.</p>
        <a class="btn" href="emergency-fund/">Open</a>
      </section>

      <section class="card">
        <h3>Down Payment / House Savings</h3>
        <p>See how much to save each month to reach your down payment goal and when you'll get there.</p>
        <a class="btn" href="down-payment/">Open</a>
      </section>

      <section class="card">
        <h3>Student Loan Payoff</h3>
        <p>Model extra payments, refinancing, and payoff timelines so you can choose a strategy that fits.</p>
        <a class="btn" href="student-loan-payoff/">Open</a>
      </section>

      <section class="card">
        <h3>Retirement Trade-Off Explorer</h3>
        <p>See how retiring later, saving more each year, or spending less (or adding part-time income) changes whether you look on track for your retirement income goal.</p>
        <a class="btn" href="trade-off-explorer/">Open</a>
      </section>

      <section class="card">
        <h3>How Much Do I Need? Nest Egg Target</h3>
        <p>Get a rule-of-thumb target for how much to have saved by retirement. Enter the income you want, subtract Social Security and pensions, and see the nest egg you’re aiming for.</p>
        <a class="btn" href="nest-egg-target/">Open</a>
      </section>

      <section class="card">
        <h3>401(k) / IRA On Track?</h3>
        <p>See if your current balance and contributions put you on track for retirement by your target age.</p>
        <a class="btn" href="401k-on-track/">Open</a>
      </section>
    </main>
    </div>

    <?php if (!$isLoggedIn): ?>
      <!-- Premium Promotion for Non-Logged-In Users (after they've seen the calculators) -->
      <div class="premium-banner">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>Professional Planning Tools, Now with Premium Features</h2>
            <p>All calculators above are free to use. Upgrade to Premium to save and compare scenarios, export PDF and CSV reports, and access advanced projections.</p>
            <div class="premium-banner-features">
              <div class="premium-feature-item">Save Unlimited Scenarios</div>
              <div class="premium-feature-item">Export PDF Reports</div>
              <div class="premium-feature-item">10-20 Year Projections</div>
              <div class="premium-feature-item">Ad-Free Experience</div>
            </div>
          </div>
          <a href="premium.html" class="premium-banner-cta">Learn More</a>
        </div>
      </div>
    <?php endif; ?>

    <?php include __DIR__ . '/includes/footer.php'; ?>

  </div>
</body>
</html>