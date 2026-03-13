<?php
/**
 * Public white-label trial page for free subscribers.
 * URL: trial.php?s=SLUG — shows logo, firm name, and fixed calculator subset.
 */
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';

$slug = trim($_GET['s'] ?? '');
$firmName = '';
$logoUrl = '';
$bannerUrl = '';
$trialExpired = false;
$notFound = false;

if (empty($slug)) {
    $notFound = true;
} else {
    $stmt = $conn->prepare('SELECT firm_name, logo_url, banner_url, created_at, plan FROM calcforadvisors_subscribers WHERE trial_slug = ?');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $stmt->bind_result($firmName, $logoUrl, $bannerUrl, $createdAt, $plan);
    if (!$stmt->fetch() || $plan !== 'free') {
        $notFound = true;
    } else {
        $created = $createdAt ? strtotime($createdAt) : time();
        $trialEnds = $created + (30 * 86400);
        $trialExpired = time() > $trialEnds;
    }
    $stmt->close();
}
$conn->close();

$host = $_SERVER['HTTP_HOST'] ?? '';
$calcBase = (strpos($host, 'localhost') !== false) ? 'http://localhost' : 'https://ronbelisle.com';

$calculators = [
    ['title' => 'RMD Impact', 'url' => $calcBase . '/rmd-impact/', 'desc' => 'Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time.'],
    ['title' => 'Estate & Legacy Planning Suite', 'url' => $calcBase . '/estate-planning/', 'desc' => 'Model inherited IRA taxes under the 10-year rule, compare Roth conversion strategies, and explore SECURE Act planning tools.'],
    ['title' => 'Managed Portfolio vs Vanguard', 'url' => $calcBase . '/managed-vs-vanguard/', 'desc' => 'See the true cost of advisor fees—including opportunity cost—compared to low-cost Vanguard index funds.'],
    ['title' => 'Vanguard Personal Advisor vs Target Date Funds', 'url' => $calcBase . '/vanguard-pas-vs-target-date/', 'desc' => 'Compare Vanguard PAS (0.30%) with a self-managed blend of Target Date funds.'],
    ['title' => 'Social Security Claiming Analyzer', 'url' => $calcBase . '/social-security-claiming-analyzer/', 'desc' => 'Compare claiming ages and see how lifetime Social Security benefits change over time.'],
    ['title' => 'Retirement Timeline & Checklist', 'url' => $calcBase . '/retirement-timeline/', 'desc' => 'Turn your target retirement date into a simple, phased checklist of tasks.'],
    ['title' => 'Roth Conversion Calculator', 'url' => $calcBase . '/roth-conv/', 'desc' => 'Analyze the benefits of converting traditional IRA funds to Roth, considering taxes, RMDs, and Medicare IRMAA.'],
    ['title' => 'Required vs. Desired Spending', 'url' => $calcBase . '/required-vs-desired/', 'desc' => 'Separate essential expenses from discretionary spending to calculate minimum vs ideal portfolio.'],
    ['title' => 'Retirement Spending Checkup', 'url' => $calcBase . '/retirement-spending-checkup/', 'desc' => 'Estimate a retirement budget and see whether your savings look on track.'],
    ['title' => 'Future Value Calculator', 'url' => $calcBase . '/future-value-app/', 'desc' => 'Calculate present value, future value, annuities, and required payments.'],
    ['title' => 'Social Security + Spending Gap', 'url' => $calcBase . '/ss-gap/', 'desc' => 'See how Social Security reduces the portfolio you need by identifying your real retirement spending gap.'],
    ['title' => 'Survivor Gap Calculator', 'url' => $calcBase . '/survivor-gap/', 'desc' => 'Compare single-life vs joint-life annuity payouts and how life insurance could fill the gap.'],
    ['title' => 'Debt Payoff Calculator', 'url' => $calcBase . '/debt-payoff/', 'desc' => 'Pay down debt before retirement—compare avalanche vs snowball and payoff timelines.'],
    ['title' => 'Pension vs. Lump Sum', 'url' => $calcBase . '/pension-vs-lump-sum/', 'desc' => 'See how many years it takes for the pension to pay back the lump sum.'],
    ['title' => 'Plan Success (Monte Carlo)', 'url' => $calcBase . '/plan-success/', 'desc' => 'See the probability of your portfolio lasting through retirement using random market simulations.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $notFound || $trialExpired ? 'Trial' : htmlspecialchars($firmName); ?> - calcforadvisors.com</title>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; color: #1e293b; line-height: 1.6; -webkit-font-smoothing: antialiased; }
    .hero { width: 100%; background: #0b4f80; line-height: 0; font-size: 0; }
    .hero img { display: block; width: 100%; height: auto; vertical-align: top; }
    .hero.without-banner { padding: 32px 24px; line-height: 1.4; font-size: 1rem; text-align: center; }
    .hero.without-banner.no-logo { padding: 40px 24px; }
    .hero.without-banner img.logo { display: inline-block; max-height: 80px; max-width: 280px; width: auto; height: auto; }
    .hero.without-banner h1 { font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 16px; }
    .hero.without-banner h1.only { margin-top: 0; }
    .hero-mobile-text { display: none; }
    @media (max-width: 540px) {
      .hero { line-height: 1.4; font-size: 1rem; }
      .hero.with-banner img.banner { display: none !important; }
      .hero.without-banner img.logo { display: none !important; }
      .hero.without-banner h1 { display: none !important; }
      .hero-mobile-text { display: block !important; padding: 32px 24px 36px; text-align: left; }
      .hero-mobile-text .hero-title { font-size: 1.75rem; font-weight: 700; color: #fff; margin-bottom: 8px; line-height: 1.25; }
      .hero-mobile-text .hero-tagline { font-size: 1rem; color: rgba(255,255,255,0.95); font-weight: 500; line-height: 1.4; }
    }
    .skip-back { position: fixed; top: 16px; left: 20px; z-index: 20; display: inline-flex; align-items: center; padding: 10px 16px; background: rgba(0,0,0,.35); color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; backdrop-filter: blur(8px); }
    .skip-back:hover { background: rgba(0,0,0,.5); color: #fff; }
    .back-to-list { display: inline-flex; align-items: center; padding: 8px 14px; background: #0c4a6e; color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 8px; margin-bottom: 16px; }
    .back-to-list:hover { background: #0b3d5c; color: #fff; }
    @media (max-width: 540px) {
      .skip-back { position: static; display: block; width: 100%; text-align: center; border-radius: 0; padding: 12px 16px; }
    }
    .intro { max-width: 960px; margin: 0 auto; padding: 24px 24px 28px; }
    .intro-box { background: #ffffff; border-radius: 10px; padding: 28px 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); font-size: 1.05rem; color: #334155; line-height: 1.75; }
    .intro-box a { color: #0369a1; text-decoration: none; font-weight: 600; }
    .intro-box a:hover { text-decoration: underline; }
    .calculators-section { max-width: 960px; margin: 0 auto; padding: 0 24px 40px; }
    .calculators-section h2 { font-size: 1.55rem; font-weight: 700; color: #0c4a6e; margin-bottom: 20px; }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @media (max-width: 860px) { .grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px) { .grid { grid-template-columns: 1fr; } }
    .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 22px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: box-shadow 0.2s ease, transform 0.2s ease; cursor: pointer; }
    .card:hover { box-shadow: 0 4px 14px rgba(3,105,161,0.12); transform: translateY(-2px); }
    .card h3 { font-size: 1rem; font-weight: 700; color: #0c4a6e; margin-bottom: 8px; line-height: 1.35; }
    .card p { font-size: 0.9rem; color: #475569; line-height: 1.6; flex: 1; margin-bottom: 12px; }
    .card .click-link { font-size: 0.9rem; font-weight: 600; color: #0369a1; }
    .demo-note { margin-top: 28px; font-size: 0.82rem; color: #94a3b8; text-align: center; line-height: 1.55; max-width: 700px; margin-left: auto; margin-right: auto; }
    .iframe-container { display: none; max-width: 960px; margin: 0 auto; padding: 0 24px 40px; }
    .iframe-container.active { display: block; }
    .iframe-container .calculator-frame { width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; min-height: 600px; background: #fff; }
    .calculators-list { display: block; }
    .calculators-list.hidden { display: none; }
    .error-box { max-width: 520px; margin: 40px auto; padding: 28px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center; }
    .error-box h2 { font-size: 1.25rem; color: #334155; margin-bottom: 12px; }
    .error-box p { color: #64748b; margin-bottom: 16px; }
    .error-box a { color: #0369a1; font-weight: 600; }
    footer { text-align: center; padding: 24px 16px 28px; font-size: 0.8rem; color: #94a3b8; }
  </style>
</head>
<body>

<?php if ($notFound): ?>
  <div class="error-box">
    <h2>Trial link not found</h2>
    <p>This trial link is invalid or has been removed.</p>
    <a href="index.html">Go to calcforadvisors.com</a>
  </div>
<?php elseif ($trialExpired): ?>
  <div class="error-box">
    <h2>Trial has ended</h2>
    <p><?php echo htmlspecialchars($firmName ?: 'This'); ?>’s 30-day white-label trial has expired. Upgrade to a paid plan for ongoing access.</p>
    <a href="index.html#pricing">View pricing</a>
  </div>
<?php else: ?>

  <a href="index.html" class="skip-back">← Back to calcforadvisors.com</a>

  <div class="hero <?php echo !empty($bannerUrl) ? 'with-banner' : 'without-banner ' . (empty($logoUrl) ? 'no-logo' : ''); ?>">
    <div class="hero-mobile-text">
      <span class="hero-title"><?php echo htmlspecialchars($firmName); ?></span>
      <span class="hero-tagline">Retirement and planning calculators</span>
    </div>
    <?php if (!empty($bannerUrl)): ?>
      <img src="<?php echo htmlspecialchars($bannerUrl); ?>" alt="<?php echo htmlspecialchars($firmName); ?>" class="banner">
    <?php else: ?>
      <?php if (!empty($logoUrl)): ?>
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($firmName); ?>" class="logo">
      <?php endif; ?>
      <h1 class="<?php echo empty($logoUrl) ? 'only' : ''; ?>"><?php echo htmlspecialchars($firmName); ?></h1>
    <?php endif; ?>
  </div>

  <section class="intro">
    <div class="intro-box">
      Retirement and planning calculators for folks in or near retirement—RMDs, Social Security, Roth conversions, estate & legacy planning, and more. All tools are free on <a href="<?php echo htmlspecialchars($calcBase); ?>" target="_blank" rel="noopener">ronbelisle.com</a>. No account required.
    </div>
  </section>

  <section class="calculators-section calculators-list" id="calculators-list">
    <h2>Retirement calculators</h2>
    <div class="grid">
      <?php foreach ($calculators as $c): ?>
        <div class="card" data-url="<?php echo htmlspecialchars($c['url']); ?>" data-title="<?php echo htmlspecialchars($c['title']); ?>" role="button" tabindex="0">
          <h3><?php echo htmlspecialchars($c['title']); ?></h3>
          <p><?php echo htmlspecialchars($c['desc']); ?></p>
          <span class="click-link">Click here</span>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="demo-note">Click a calculator to open it below. Your firm's branding stays visible at the top.</p>
  </section>

  <section class="iframe-container" id="iframe-container">
    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
      <a href="#" class="back-to-list" id="back-to-list">← Back to calculator list</a>
      <a href="#" id="open-in-new-tab" target="_blank" rel="noopener" style="font-size: 14px; color: #0369a1; font-weight: 600;">Open in new tab</a>
    </div>
    <iframe id="calculator-frame" class="calculator-frame" title="Calculator"></iframe>
  </section>

  <footer>
    Calculators powered by <a href="https://ronbelisle.com" style="color:#94a3b8;">ronbelisle.com</a> · 30-day trial from <a href="index.html" style="color:#94a3b8;">calcforadvisors.com</a>
  </footer>

  <script>
    (function() {
      var cards = document.querySelectorAll('.card[data-url]');
      var list = document.getElementById('calculators-list');
      var container = document.getElementById('iframe-container');
      var frame = document.getElementById('calculator-frame');
      var backBtn = document.getElementById('back-to-list');

      var openInNewTab = document.getElementById('open-in-new-tab');

      function openCalculator(url, title) {
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        var embedUrl = url + sep + 'embed=1&return_url=' + encodeURIComponent(window.location.href);
        frame.src = embedUrl;
        frame.title = title;
        openInNewTab.href = url;
        openInNewTab.textContent = 'Open ' + title + ' in new tab';
        list.classList.add('hidden');
        container.classList.add('active');
        frame.focus();
        window.scrollTo(0, 0);
      }

      function closeCalculator() {
        frame.src = 'about:blank';
        list.classList.remove('hidden');
        container.classList.remove('active');
        backBtn.blur();
      }

      cards.forEach(function(card) {
        card.addEventListener('click', function() {
          openCalculator(card.dataset.url, card.dataset.title);
        });
        card.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openCalculator(card.dataset.url, card.dataset.title);
          }
        });
      });

      backBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeCalculator();
      });
    })();
  </script>

<?php endif; ?>

</body>
</html>
