<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();

// Serve jp-business mini-site when using jp-business subdomain (clean URLs: /npv-irr/, /breakeven-profit/)
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'jp-business.ronbelisle.com') {
    $uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
    $uri = rtrim($uri, '/');
    if ($uri === '/npv-irr' || $uri === '') {
        if ($uri === '/npv-irr') {
            require __DIR__ . '/jp-business.ronbelisle.com/npv-irr/index.php';
        } else {
            require __DIR__ . '/jp-business.ronbelisle.com/index.php';
        }
        exit;
    }
    if ($uri === '/breakeven-profit') {
        require __DIR__ . '/jp-business.ronbelisle.com/breakeven-profit/index.php';
        exit;
    }
    require __DIR__ . '/jp-business.ronbelisle.com/index.php';
    exit;
}

// Serve business calculators landing when using business.ronbelisle.com (root path only)
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'business.ronbelisle.com') {
    $uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
    $uri = rtrim($uri, '/');
    if ($uri === '' || $uri === '/') {
        require __DIR__ . '/business.ronbelisle.com/index.php';
        exit;
    }
    // For other paths (e.g., /npv-irr/), fall through so the stub directories handle them.
}

require_once 'includes/db_config.php';
require_once __DIR__ . '/includes/has_premium_access.php';
$premium_pricing_blurb = get_premium_pricing_blurb();
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

// Hide site header when embedded in calcforadvisors.com demos (white-label preview)
$hide_site_header = isset($_GET['embed'])
    || (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'calcforadvisors.com') !== false);
$is_calculator_directory = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'calculators.php';

// SEO: homepage
require_once __DIR__ . '/includes/seo_public_url.php';
$seo_title = $is_calculator_directory ? "Individual Retirement Planning Calculators" : "Retirement Plan Builder & Financial Calculators";
$seo_description = $is_calculator_directory
    ? "Explore focused retirement planning calculators for Social Security, spending, taxes, RMDs, debt, and portfolio decisions."
    : "Build a year-by-year retirement plan with a free Retirement Plan Builder. Calculator Premium adds Monte Carlo stress testing, PDF reports, and AI explanations of your specific results.";
$seo_url = rb_seo_public_url();
$seo_site_name = "Ron Belisle Financial Calculators";
$seo_og_image = rb_seo_site_base_url() . '/images/og-default.jpg';
$seo_og_image_alt = 'Ron Belisle — Retirement planning calculators and AI insights';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("includes/analytics.php"); ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
  <title><?php echo htmlspecialchars($seo_title); ?></title>
  <link rel="canonical" href="<?php echo htmlspecialchars($seo_url); ?>">
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo htmlspecialchars($seo_url); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
  <meta property="og:site_name" content="<?php echo htmlspecialchars($seo_site_name); ?>">
  <meta property="og:locale" content="en_US">
  <meta property="og:image" content="<?php echo htmlspecialchars($seo_og_image); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($seo_og_image_alt); ?>">
  <!-- Twitter / X -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?php echo htmlspecialchars($seo_url); ?>">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_og_image); ?>">
  <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($seo_og_image_alt); ?>">
  <?php include __DIR__ . '/includes/json-ld-home.php'; ?>
  <style>
    :root{
      --max: 1120px;
      --bg: #f5f7fb;
      --paper: #ffffff;
      --paper-soft: #f8fafc;
      --text: #0f172a;
      --muted: #526071;
      --border: #d9e1ec;
      --accent: #1d4ed8;
      --accent-strong: #173f8a;
      --success: #047857;
      --ink-soft: #334155;
      --shadow: 0 14px 34px rgba(15,23,42,.10);
      --radius: 12px;
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{
      margin:0;
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
      color:var(--text);
      background:linear-gradient(180deg,#f8fafc 0%,var(--bg) 48%,#eef2f7 100%);
      line-height:1.5;
    }
    .wrap{max-width:var(--max);margin:0 auto;padding:22px 20px 46px}
    a{color:var(--accent)}
    a:focus-visible,button:focus-visible{outline:3px solid rgba(29,78,216,.28);outline-offset:3px}
    .topbar{
      display:block;
      padding:0;
      margin-bottom:16px;
      border:1px solid var(--border);
      background:var(--paper);
      border-radius:18px;
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    .brand{
      display:grid;
      grid-template-columns:minmax(0,1fr) 310px;
      gap:28px;
      align-items:stretch;
      min-width:0;
      flex:1;
    }
    .brand-text{min-width:0;padding:34px 34px 30px}
    .mark{
      width:48px;height:48px;border-radius:12px;border:1px solid rgba(29,78,216,.16);
      background:#eff6ff;
      display:grid;place-items:center;font-weight:850;letter-spacing:.01em;color:var(--accent);font-size:17px;
      margin-bottom:18px;
    }
    a.mark{text-decoration:none}
    .brand-title{
      font-size:clamp(32px,5vw,54px);
      font-weight:850;
      letter-spacing:0;
      margin:0;
      color:var(--text);
      line-height:1.02;
      max-width:780px;
    }
    .brand-tagline{
      font-size:17px;
      color:var(--muted);
      margin:16px 0 0;
      line-height:1.62;
      max-width:760px;
    }
    .hero-trust{
      display:flex;
      flex-wrap:wrap;
      gap:8px 16px;
      margin:16px 0 0;
      padding:0;
      list-style:none;
      color:var(--ink-soft);
      font-size:13px;
      font-weight:700;
    }
    .hero-trust li::before{
      content:"\2713";
      margin-right:6px;
      color:var(--success);
      font-weight:900;
    }
    .hero-primary-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}
    .hero-actions{
      display:flex;
      flex-direction:column;
      gap:8px;
      align-items:stretch;
      justify-content:center;
      align-self:center;
      margin-right:26px;
      padding:16px;
      background:#f8fafc;
      border-left:1px solid var(--border);
      border:1px solid var(--border);
      border-radius:14px;
      min-width:220px;
      max-width:250px;
    }
    .hero-actions::before{
      content:"Access";
      display:block;
      margin:0 0 2px;
      color:#64748b;
      font-size:11px;
      font-weight:850;
      letter-spacing:.08em;
      line-height:1.2;
      text-transform:uppercase;
    }
    .hero-welcome{
      font-size:13px;
      color:var(--muted);
      margin:0 0 2px;
      line-height:1.35;
    }
    .hero-welcome strong{color:var(--text)}
    .hero-action-label{
      margin:2px 0 0;
      color:#334155;
      font-size:13px;
      font-weight:800;
      line-height:1.3;
    }
    .hero-action-note{
      margin:0;
      color:#64748b;
      font-size:12px;
      line-height:1.35;
    }
    .hero-premium-path{
      margin:0;
      padding:0 0 12px;
      border-bottom:1px solid #e2e8f0;
    }
    .hero-premium-path .hero-btn{
      width:100%;
      margin-top:8px;
    }
    .hero-login-path{
      margin-top:2px;
    }
    .hero-login-path .hero-btn{
      width:100%;
      margin-top:7px;
    }
    .hero-btn,.btn{
      min-height:44px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding:10px 16px;
      border-radius:10px;
      font-size:14px;
      font-weight:760;
      text-decoration:none;
      line-height:1.2;
      border:1px solid transparent;
      transition:background .15s ease,border-color .15s ease,color .15s ease,transform .15s ease;
    }
    .hero-btn:hover,.btn:hover{transform:translateY(-1px)}
    .hero-actions .hero-btn{
      min-height:38px;
      padding:8px 12px;
      border-radius:9px;
      font-size:13px;
      font-weight:750;
      box-shadow:none;
    }
    .hero-actions .hero-btn-premium{
      color:#fff;
      background:var(--success);
      border-color:var(--success);
    }
    .hero-actions .hero-btn-premium:hover{
      color:#fff;
      background:#065f46;
      border-color:#065f46;
    }
    .hero-btn-primary,.btn-primary{
      color:#fff;
      background:var(--accent);
      border-color:var(--accent);
      box-shadow:0 8px 18px rgba(29,78,216,.18);
    }
    .hero-btn-primary:hover,.btn-primary:hover{background:var(--accent-strong);border-color:var(--accent-strong)}
    .hero-btn-secondary,.btn-secondary{
      color:var(--text);
      background:#fff;
      border-color:var(--border);
    }
    .hero-btn-secondary:hover,.btn-secondary:hover{border-color:#b8c5d6;background:#f8fafc}
    .hero-btn-premium{
      color:#fff;
      background:var(--success);
      border-color:var(--success);
    }
    .hero-btn-premium:hover{background:#065f46}
    .premium-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:42px;
      padding:9px 12px;
      border-radius:10px;
      background:#ecfdf5;
      color:#065f46;
      font-weight:800;
      border:1px solid #bbf7d0;
    }
    .site-top-nav{
      display:flex;
      gap:6px;
      flex-wrap:wrap;
      align-items:center;
      margin:0 0 18px;
      padding:6px;
      border:1px solid var(--border);
      background:rgba(255,255,255,.76);
      border-radius:999px;
      width:max-content;
      max-width:100%;
    }
    .site-top-nav a{
      display:inline-flex;
      align-items:center;
      min-height:38px;
      padding:8px 14px;
      border-radius:999px;
      text-decoration:none;
      font-size:14px;
      font-weight:700;
      color:var(--muted);
      transition:background .15s ease,color .15s ease;
    }
    .site-top-nav a:hover:not(.active){background:#f1f5f9;color:var(--text)}
    .site-top-nav a.active{background:var(--text);color:#fff}
    .premium-banner {
      background:#0f2f5f;
      border-radius:14px;
      padding:20px 22px;
      margin:0 0 18px;
      color:white;
      box-shadow:0 10px 26px rgba(15,47,95,.18);
      border:1px solid rgba(255,255,255,.12);
    }
    .premium-banner-content{display:flex;align-items:center;justify-content:space-between;gap:18px}
    .premium-banner-text{flex:1}
    .premium-banner h2{margin:0 0 6px;font-size:20px;font-weight:820;letter-spacing:0}
    .premium-banner p{margin:0;opacity:.95;font-size:14px;line-height:1.5}
    .premium-banner-pricing{margin:9px 0 12px;font-size:14px;font-weight:650;opacity:.95}
    .premium-banner-pricing a{color:#fff;text-decoration:underline}
    .premium-banner-features{display:flex;gap:10px 16px;margin-top:11px;flex-wrap:wrap}
    .premium-feature-item{display:flex;align-items:center;gap:6px;font-size:13px;opacity:.95}
    .premium-feature-item::before{content:"✓";font-weight:bold;color:#86efac}
    .premium-banner-cta{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:42px;
      background:#fff;
      color:#0f2f5f;
      padding:10px 18px;
      border-radius:10px;
      text-decoration:none;
      font-weight:800;
      font-size:14px;
      white-space:nowrap;
    }
    .premium-banner.member{background:#065f46}
    .premium-banner.member .premium-banner-cta{background:rgba(255,255,255,.14);color:white;border:1px solid rgba(255,255,255,.42)}
    .planning-shell{
      margin-top:20px;
      padding:22px;
      background:rgba(255,255,255,.78);
      border:1px solid var(--border);
      border-radius:18px;
      box-shadow:0 8px 24px rgba(15,23,42,.06);
    }
    .section-kicker{
      margin:0 0 6px;
      font-size:12px;
      font-weight:850;
      color:var(--accent);
      letter-spacing:.10em;
      text-transform:uppercase;
    }
    .section-title{margin:0;font-size:26px;line-height:1.15;letter-spacing:0}
    .section-copy{margin:8px 0 0;color:var(--muted);font-size:15px;max-width:760px}
    .calculator-tab-shell{
      margin:18px 0 20px;
      padding:6px;
      background:#eef2f7;
      border:1px solid #d8e1ed;
      border-radius:14px;
    }
    .calculator-tab-bar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;background:transparent;border:none;padding:0;margin:0}
    .calculator-tab{
      min-height:46px;
      border:none;
      background:transparent;
      padding:10px 12px;
      border-radius:10px;
      font-size:13px;
      font-weight:760;
      color:#475569;
      cursor:pointer;
      line-height:1.25;
      text-align:center;
      transition:background .15s ease,color .15s ease,box-shadow .15s ease;
    }
    .calculator-tab:hover:not(.active){color:var(--text);background:rgba(255,255,255,.62)}
    .calculator-tab.active{background:#fff;color:var(--text);box-shadow:0 1px 4px rgba(15,23,42,.10)}
    .tab-panel[hidden]{display:none !important}
    .primary-tool{
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:18px;
      align-items:center;
      padding:22px;
      border:1px solid #cbd8ea;
      border-radius:14px;
      background:#fff;
      box-shadow:0 10px 24px rgba(15,23,42,.08);
      margin-bottom:18px;
    }
    .primary-tool h3,.tool-card h3,.tool-row h3{margin:0;color:var(--text);letter-spacing:0}
    .primary-tool h3{font-size:24px;line-height:1.15}
    .primary-tool p{margin:8px 0 0;color:var(--muted);font-size:15px;line-height:1.55;max-width:820px}
    .popular-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:16px 0 20px}
    .tool-card{
      min-height:132px;
      padding:14px;
      border:1px solid var(--border);
      border-radius:12px;
      background:#fff;
      display:flex;
      flex-direction:column;
      gap:8px;
    }
    .tool-card h3{font-size:15px;line-height:1.24}
    .tool-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.38;flex:1}
    .goal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:14px}
    .goal-section{
      border:1px solid var(--border);
      border-radius:14px;
      background:#fff;
      overflow:hidden;
    }
    .goal-section.full{grid-column:1 / -1}
    .goal-heading{
      display:flex;
      gap:10px;
      align-items:center;
      padding:13px 14px;
      border-bottom:1px solid #e5ebf3;
      background:#f8fafc;
    }
    .goal-icon{
      width:30px;
      height:30px;
      border-radius:8px;
      display:grid;
      place-items:center;
      background:#e0ecff;
      color:var(--accent);
      font-weight:900;
      flex:0 0 auto;
    }
    .goal-heading h2,.goal-heading h3{margin:0;font-size:16px;line-height:1.25}
    .goal-heading p{margin:2px 0 0;color:var(--muted);font-size:12.5px;line-height:1.35}
    .tool-list{display:grid}
    .tool-row{
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:12px;
      align-items:center;
      padding:12px 14px;
      border-top:1px solid #edf1f6;
      text-decoration:none;
      color:inherit;
    }
    .tool-list .tool-row:first-child{border-top:0}
    .tool-row:hover{background:#f8fafc}
    .tool-row h3{font-size:14.5px;line-height:1.25}
    .tool-row p{margin:4px 0 0;color:var(--muted);font-size:13px;line-height:1.35}
    .tool-action,.btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:36px;
      padding:8px 10px;
      color:var(--accent);
      font-weight:800;
      font-size:13px;
      text-decoration:none;
      white-space:nowrap;
    }
    .tool-action::after,.btn::after{content:"→";margin-left:5px}
    .btn{border:0;background:transparent;border-radius:8px}
    .btn-primary,.primary-tool .btn{
      min-height:44px;
      padding:10px 16px;
      border-radius:10px;
      background:var(--accent);
      color:#fff;
      border:1px solid var(--accent);
      box-shadow:0 8px 18px rgba(29,78,216,.16);
    }
    .primary-tool .btn::after{color:inherit}
    .btn-secondary{border:1px solid var(--border);background:#fff;color:var(--text)}
    .feature-badge{
      display:inline-block;
      margin-left:8px;
      padding:3px 8px;
      border-radius:999px;
      font-size:10px;
      font-weight:850;
      letter-spacing:.05em;
      text-transform:uppercase;
      vertical-align:middle;
      color:#075985;
      background:#e0f2fe;
    }
    .card{display:block}
    .section,.section-heading,.hint,.tier,.tier-title,.tier-hint,.grid,.grid.tight{margin:0;padding:0}
    .card.coming-soon .btn{background:#e2e8f0;color:#64748b;border-color:#cbd5e1;cursor:not-allowed;pointer-events:none}
    .card.coming-soon .coming-badge{
      display:inline-block;
      background:#f59e0b;
      color:white;
      padding:4px 10px;
      border-radius:12px;
      font-size:11px;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.05em;
      margin-left:6px;
    }
    .integrated-note{
      margin-top:20px;
      padding:16px 18px;
      border:1px solid var(--border);
      border-radius:14px;
      background:#fff;
    }
    .integrated-note p{margin:0;color:var(--muted);font-size:14px;line-height:1.5}
    .integrated-note p:first-child{color:var(--text);font-weight:800;margin-bottom:4px}
    .integrated-note a{font-weight:800}
    .advisor-callout{
      margin:20px 0 0;
      padding:18px 20px;
      border:1px solid #d8e1ed;
      border-radius:14px;
      background:#f8fafc;
      display:flex;
      align-items:center;
      justify-content:space-between;
      flex-wrap:wrap;
      gap:18px;
    }
    .advisor-callout h2{
      margin:0;
      font-size:18px;
      line-height:1.25;
      letter-spacing:0;
    }
    .advisor-callout p{
      margin:5px 0 0;
      color:var(--muted);
      font-size:14px;
      line-height:1.45;
      max-width:720px;
    }
    .advisor-callout .btn{
      min-height:40px;
      padding:9px 14px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--text);
      border-radius:10px;
      flex:0 0 auto;
    }
    hr.footer-sep{border:0;border-top:1px solid rgba(15,23,42,.12);margin:22px 0 14px}
    .site-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:13px;padding-bottom:10px}
    .footer-left{margin:0}
    .footer-right{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;text-align:right}
    .donate-button{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:999px;border:1px solid rgba(15,23,42,.14);background:rgba(15,23,42,.03);color:var(--text);text-decoration:none;font-weight:700;line-height:1;white-space:nowrap}
    .donate-button:hover{background:rgba(15,23,42,.06)}
    @media (max-width: 920px){
      .brand{grid-template-columns:1fr}
      .hero-actions{border-left:0;border-top:1px solid var(--border);display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-self:stretch;margin:0;max-width:none}
      .hero-welcome{grid-column:1 / -1}
      .popular-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
      .goal-grid{grid-template-columns:1fr}
    }
    @media (max-width: 640px){
      .wrap{padding:14px 12px 34px}
      .brand-text{padding:24px 20px 22px}
      .mark{margin-bottom:14px}
      .brand-title{font-size:34px}
      .brand-tagline{font-size:16px}
      .hero-actions{grid-template-columns:1fr;padding:18px}
      .site-top-nav{width:100%;border-radius:14px}
      .site-top-nav a{flex:1 1 100%;justify-content:center}
      .premium-banner-content{flex-direction:column;align-items:flex-start}
      .premium-banner-cta{width:100%}
      .planning-shell{padding:16px;border-radius:14px}
      .calculator-tab-bar{grid-template-columns:1fr}
      .primary-tool{grid-template-columns:1fr;padding:18px}
      .popular-grid{grid-template-columns:1fr}
      .tool-row{grid-template-columns:1fr;gap:6px}
      .tool-action{justify-content:flex-start;padding-left:0}
      .footer-right{width:100%;justify-content:flex-start;text-align:left}
    }
    @media (max-width: 767px){
      .wrap{padding:10px 10px 28px}
      .topbar{
        margin-bottom:10px;
        border-radius:12px;
        box-shadow:0 6px 16px rgba(15,23,42,.07);
      }
      .brand{gap:0}
      .brand-text{padding:18px 16px 16px}
      .mark{
        width:38px;
        height:38px;
        border-radius:10px;
        margin-bottom:10px;
        font-size:14px;
      }
      .brand-title{
        font-size:30px;
        line-height:1.08;
      }
      .brand-tagline{
        margin-top:9px;
        font-size:14.5px;
        line-height:1.42;
      }
      .hero-trust{
        gap:5px 12px;
        margin-top:12px;
        font-size:12.5px;
      }
      .hero-primary-actions{
        margin-top:14px;
        gap:8px;
      }
      .hero-primary-actions .hero-btn{
        width:100%;
      }
      .hero-primary-actions .hero-btn-secondary{
        min-height:38px;
        padding:8px 12px;
        font-size:13px;
        box-shadow:none;
      }
      .hero-actions{
        grid-template-columns:1fr;
        gap:6px;
        padding:12px;
        border-radius:10px;
      }
      .hero-actions::before{
        margin-bottom:0;
        font-size:10px;
      }
      .hero-action-label{
        font-size:12.5px;
      }
      .hero-action-note{
        font-size:11.5px;
        line-height:1.3;
      }
      .hero-premium-path{
        padding-bottom:9px;
      }
      .hero-premium-path .hero-btn,
      .hero-login-path .hero-btn{
        min-height:38px;
        margin-top:6px;
      }
      .site-top-nav{
        gap:4px;
        margin-bottom:12px;
        padding:4px;
        border-radius:10px;
      }
      .site-top-nav a{
        min-height:34px;
        padding:6px 10px;
        font-size:13px;
      }
      .premium-banner{
        margin-bottom:12px;
        padding:14px;
        border-radius:10px;
        box-shadow:0 6px 16px rgba(15,47,95,.14);
      }
      .premium-banner h2{
        font-size:17px;
      }
      .premium-banner p,
      .premium-banner-pricing,
      .premium-feature-item{
        font-size:13px;
      }
      .premium-banner-features{
        gap:7px 12px;
        margin-top:9px;
      }
      .premium-banner-cta{
        min-height:38px;
        padding:8px 12px;
      }
      .planning-shell{
        margin-top:12px;
        padding:12px;
        border-radius:12px;
        box-shadow:0 4px 12px rgba(15,23,42,.05);
      }
      .section-kicker{
        margin-bottom:4px;
        font-size:10.5px;
      }
      .section-title{
        font-size:22px;
        line-height:1.18;
      }
      .section-copy{
        margin-top:5px;
        font-size:13.5px;
        line-height:1.4;
      }
      .calculator-tab-shell{
        margin:12px 0 14px;
        padding:4px;
        border-radius:10px;
      }
      .calculator-tab{
        min-height:38px;
        padding:8px 10px;
        border-radius:8px;
        font-size:12.5px;
      }
      .primary-tool{
        padding:14px;
        border-radius:10px;
        margin-bottom:12px;
        box-shadow:0 5px 14px rgba(15,23,42,.07);
      }
      .primary-tool h3{
        font-size:20px;
      }
      .primary-tool p{
        margin-top:5px;
        font-size:13.5px;
        line-height:1.42;
      }
      .primary-tool .btn{
        min-height:40px;
        padding:8px 12px;
      }
      .popular-grid{
        gap:8px;
        margin:10px 0 14px;
      }
      .tool-card{
        min-height:0;
        padding:12px;
        border-radius:10px;
        gap:6px;
      }
      .tool-card h3{
        font-size:14px;
      }
      .tool-card p{
        font-size:12.5px;
        line-height:1.34;
      }
      .goal-grid{
        gap:10px;
        margin-top:10px;
      }
      .goal-section{
        border-radius:10px;
      }
      .goal-heading{
        gap:8px;
        padding:10px 12px;
      }
      .goal-icon{
        width:26px;
        height:26px;
        border-radius:7px;
        font-size:12px;
      }
      .goal-heading h2,
      .goal-heading h3{
        font-size:15px;
      }
      .goal-heading p{
        font-size:12px;
        line-height:1.28;
      }
      .tool-row{
        padding:10px 12px;
        gap:4px;
      }
      .tool-row h3{
        font-size:14px;
      }
      .tool-row p{
        margin-top:3px;
        font-size:12.5px;
        line-height:1.32;
      }
      .tool-action,
      .btn{
        min-height:34px;
        padding:6px 0;
        font-size:12.5px;
      }
      .integrated-note{
        margin-top:12px;
        padding:12px;
        border-radius:10px;
      }
      .integrated-note p{
        font-size:13px;
        line-height:1.4;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">

    <?php if (!$hide_site_header): ?>
    <div class="topbar" role="banner">
      <div class="brand">
        <div class="brand-text">
          <a class="mark" href="/about-me.php" aria-label="About Ron Belisle">RB</a>
          <h1 class="brand-title">See how your savings, Social Security, and spending fit together.</h1>
          <p class="brand-tagline">Build a free retirement plan snapshot in about 10 minutes. Enter your numbers once to see your projected retirement income, portfolio withdrawals, RMDs, and estimated federal taxes year by year.</p>
          <ul class="hero-trust" aria-label="Retirement plan builder benefits">
            <li>Free to use</li>
            <li>No account required</li>
            <li>Your starting point for retirement decisions</li>
          </ul>
          <div class="hero-primary-actions">
            <a href="retirement-plan/" class="hero-btn hero-btn-primary" data-rb-event="retirement_plan_start" data-rb-param-placement="homepage_hero">Build My Free Retirement Plan</a>
            <a href="calculators.php" class="hero-btn hero-btn-secondary" data-rb-event="calculator_explore_click" data-rb-param-placement="homepage_hero">Explore Individual Calculators</a>
          </div>
        </div>
        <div class="hero-actions">
          <?php if ($isLoggedIn): ?>
            <p class="hero-action-label">Existing member</p>
            <p class="hero-welcome">Welcome back, <strong><?php echo htmlspecialchars($userName); ?></strong></p>
            <a href="auth/logout.php" class="hero-btn hero-btn-secondary">Log Out</a>
            <?php if (!$is_premium): ?>
              <div class="hero-premium-path">
                <p class="hero-action-label">Calculator Premium</p>
                <p class="hero-action-note">Learn about the subscription and start a 7-day free trial.</p>
                <a href="premium.html" class="hero-btn hero-btn-premium" data-rb-event="premium_trial_click" data-rb-param-placement="homepage_member_hero">Try Calculator Premium Free for 7 Days</a>
              </div>
            <?php else: ?>
              <span class="premium-badge">✨ Calculator Premium Member</span>
            <?php endif; ?>
          <?php else: ?>
            <div class="hero-premium-path">
              <p class="hero-action-label">Calculator Premium</p>
              <p class="hero-action-note">Start with the free plan above, then add saved scenarios, reports, and advanced projections when you need them.</p>
              <a href="premium.html" class="hero-btn hero-btn-secondary" data-rb-event="premium_details_click" data-rb-param-placement="homepage_hero">See Calculator Premium</a>
            </div>
            <div class="hero-login-path">
              <p class="hero-action-label">Already have a Calculator Premium account?</p>
              <a href="auth/login.php" class="hero-btn hero-btn-secondary">Log In</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <nav class="site-top-nav" aria-label="Site sections">
      <a href="<?php echo $is_calculator_directory ? '/calculators.php' : '/'; ?>" class="active" aria-current="page">Calculators</a>
      <a href="https://journey.ronbelisle.com/" aria-label="Guided retirement planning, separate site and pricing" data-rb-event="journey_promotion_click" data-rb-param-placement="navigation">Guided Retirement Planning (separate site)</a>
    </nav>
    <?php endif; ?>

    <?php if ($is_premium): ?>
      <!-- Premium Member Banner -->
      <div class="premium-banner member">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>✓ Calculator Premium Active</h2>
            <p>You have full access to Calculator Premium features across the site.</p>
          </div>
          <a href="account.php" class="premium-banner-cta">Manage Account</a>
        </div>
      </div>
    <?php elseif ($isLoggedIn): ?>
      <!-- Upgrade Prompt for Logged-In Free Users -->
      <div class="premium-banner">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>Unlock Calculator Premium</h2>
            <p>Save and compare scenarios, export PDF and CSV reports, AI-generated plain-language explanations of your specific results, and advanced projections.</p>
            <p class="premium-banner-pricing"><?php echo htmlspecialchars($premium_pricing_blurb); ?> <a href="premium.html#pricing">See pricing</a></p>
            <div class="premium-banner-features">
              <div class="premium-feature-item">Save & Compare Scenarios</div>
              <div class="premium-feature-item">PDF Reports</div>
              <div class="premium-feature-item">AI Explain</div>
              <div class="premium-feature-item">Advanced Projections</div>
            </div>
          </div>
            <a href="premium.html" class="premium-banner-cta" data-rb-event="premium_trial_click" data-rb-param-placement="homepage_upgrade_banner">Try Calculator Premium Free for 7 Days</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($is_calculator_directory): ?>
    <main class="planning-shell" id="planning-tools">
      <?php if (!$hide_site_header): ?>
        <p class="section-kicker">Planning workspace</p>
        <?php if ($is_calculator_directory): ?>
          <h2 class="section-title">Choose a focused retirement question</h2>
          <p class="section-copy">Start with the Retirement Plan Builder if you want one connected view of your retirement. Use these focused tools when you already know the specific decision you want to explore—Social Security, spending, taxes, RMDs, portfolio risk, debt, or estate planning.</p>
        <?php else: ?>
          <h2 class="section-title">Explore individual planning decisions</h2>
          <p class="section-copy">Already know the question you want to answer? Choose a focused calculator for Social Security, spending, taxes, RMDs, portfolio risk, debt, and other financial planning topics.</p>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (!$hide_site_header): ?>
      <div class="calculator-tab-shell">
        <div class="calculator-tab-bar" role="tablist" aria-label="Calculator categories">
          <button type="button" class="calculator-tab active" role="tab" id="tab-retirement" data-tab="retirement" aria-selected="true" aria-controls="tab-panel-retirement">In or Near Retirement (Boomers &amp; Gen X)</button>
          <button type="button" class="calculator-tab" role="tab" id="tab-foundation" data-tab="foundation" aria-selected="false" aria-controls="tab-panel-foundation">Building or Strengthening Foundation (Millennials &amp; Gen Z)</button>
        </div>
      </div>
      <?php endif; ?>

      <div id="tab-panel-retirement" class="tab-panel active" data-tab="retirement" role="tabpanel" aria-labelledby="tab-retirement"<?php if ($hide_site_header): ?> style="display:block"<?php endif; ?>>
        <section class="primary-tool" aria-label="Retirement app links">
          <div>
            <h3>Retirement Plan Builder</h3>
            <p>Enter your numbers once and see how savings, Social Security, spending, RMDs, and estimated federal taxes fit together year by year. Calculator Premium adds a Monte Carlo stress test on the same plan, plus PDF export and AI explanations of your specific results.</p>
          </div>
          <a class="btn btn-primary" href="retirement-plan/">Open</a>
        </section>

        <section aria-label="Popular Planning Tools">
          <p class="section-kicker">Planning shortcuts</p>
          <div class="popular-grid">
            <a class="tool-card" href="social-security-claiming-analyzer/"><h3>Social Security Claiming Analyzer</h3><p>Compare claiming ages and see how lifetime Social Security benefits change over time.</p><span class="tool-action">Open</span></a>
            <a class="tool-card" href="retirement-spending-checkup/"><h3>Retirement Spending &amp; On-Track Checkup</h3><p>Estimate a retirement budget from your current spending, factor in guaranteed income, and see whether your savings look on track using a simple withdrawal-rate rule of thumb.</p><span class="tool-action">Open</span></a>
            <a class="tool-card" href="roth-conv/"><h3>Roth Conversion Calculator</h3><p>Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds.</p><span class="tool-action">Open</span></a>
            <a class="tool-card" href="plan-success/"><h3>Plan Success (Monte Carlo)</h3><p>Standalone stress test: portfolio, annual withdrawal, years, return, and volatility. Best for quick experiments; most users should start with Retirement Plan Builder instead.</p><span class="tool-action">Open</span></a>
          </div>
        </section>

        <div class="goal-grid">
          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">SS</div><div><h2>Social Security</h2><p>Decide when to claim, how couples should coordinate, and what income Social Security will actually cover.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="social-security-claiming-analyzer/"><span><h3>Social Security Claiming Analyzer</h3><p>Compare claiming ages and see how lifetime Social Security benefits change over time.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="ss-early-exit/"><span><h3>Early Exit Social Security Impact</h3><p>See how stopping work earlier than planned can lower the Social Security benefit your SSA statement assumes.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="ss-survivor-impact/"><span><h3>Social Security Survivor Impact Calculator</h3><p>Should the lower earner delay to age 70? Not always. Compare claiming strategies for both spouses and see how survivor benefits, longevity, and COLAs can dramatically change the strategy that produces the highest lifetime household income.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="ss-gap/"><span><h3>Social Security + Spending Gap Calculator</h3><p>See how Social Security reduces the portfolio you need by identifying your real retirement spending gap.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">$</div><div><h2>Retirement Income and Spending</h2><p>Turn income sources, spending needs, and timing choices into a clearer retirement paycheck strategy.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="retirement-spending-checkup/"><span><h3>Retirement Spending &amp; On-Track Checkup</h3><p>Estimate a retirement budget from your current spending, factor in guaranteed income, and see whether your savings look on track using a simple withdrawal-rate rule of thumb.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="retirement-timeline/"><span><h3>Retirement Timeline &amp; Checklist</h3><p>Turn your target retirement date into a simple, phased checklist of tasks—from early prep to your last day at work and first year in retirement.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="pension-vs-lump-sum/"><span><h3>Pension vs. Lump Sum</h3><p>See how many years it takes for the pension to “pay back” the lump sum and how your life expectancy affects the choice.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="future-value-app/"><span><h3>Future Value Calculator</h3><p>Calculate present value, future value, annuities, and required payments to reach your financial goals.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="required-vs-desired/"><span><h3>Required vs. Desired Spending Calculator</h3><p>Separate essential expenses from discretionary spending to calculate the minimum portfolio needed for security and the ideal portfolio for your full retirement lifestyle.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">TX</div><div><h2>Taxes and RMDs</h2><p>Plan around taxable withdrawals, Roth decisions, and required distributions before they surprise you.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="roth-conv/"><span><h3>Roth Conversion Calculator</h3><p>Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="rmd-impact/"><span><h3>RMD Impact</h3><p>Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">%</div><div><h2>Portfolio and Risk</h2><p>Pressure-test whether your plan can handle market uncertainty, debt, survivor needs, and withdrawal choices.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="plan-success/"><span><h3>Plan Success (Monte Carlo)</h3><p>Standalone stress test: portfolio, annual withdrawal, years, return, and volatility. Best for quick experiments; most users should start with Retirement Plan Builder instead.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="survivor-gap/"><span><h3>Survivor Gap Calculator</h3><p>Compare single-life vs joint-life annuity payouts and see how life insurance could fill the gap for your surviving spouse.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="debt-payoff/"><span><h3>Debt Payoff Calculator</h3><p>Pay down debt before retirement—compare avalanche vs snowball, see payoff timelines, and how extra payments save interest.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">LG</div><div><h2>Estate and Legacy</h2><p>Think through how retirement account decisions may affect heirs, taxes, and long-term family outcomes.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="/estate-planning/"><span><h3>Estate &amp; Legacy Planning Suite</h3><p>Model inherited IRA taxes under the 10-year rule, compare Roth conversion strategies across generations, and explore SECURE Act planning tools.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">↔</div><div><h2>Comparisons and Specialist Tools</h2><p>Evaluate focused trade-offs when fees, advice models, or investment choices could change the outcome.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="swr-fee-impact/"><span><h3>Safe Withdrawal Rate &amp; Fee Impact</h3><p>See how AUM and fund fees change the 4% rule — spending power and ending wealth — without assuming fees cut the SWR dollar-for-dollar.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="managed-vs-vanguard/"><span><h3>Managed Portfolio vs Vanguard Index Fund</h3><p>See the true cost of advisor fees - including opportunity cost - compared to low-cost Vanguard index funds.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="vanguard-pas-vs-target-date/"><span><h3>Vanguard Personal Advisor vs Target Date Funds</h3><p>Compare the cost of Vanguard PAS (0.30%) with a self-managed blend of Target Date funds. Allocate conservative, moderate, and aggressive.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>
        </div>
      </div>

      <div id="tab-panel-foundation" class="tab-panel" data-tab="foundation" role="tabpanel" aria-labelledby="tab-foundation"<?php echo $hide_site_header ? '' : ' hidden'; ?>>
        <div class="goal-grid">
          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">FD</div><div><h2>Foundational Planning</h2><p>Build a stronger base by improving cash reserves, reducing debt, and deciding where extra dollars should go.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="emergency-fund/"><span><h3>Emergency Fund Builder</h3><p>Set a target (e.g. 3–6 months of expenses) and see how long it takes to get there at your savings rate.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="debt-payoff/"><span><h3>Debt Payoff Calculator</h3><p>Compare avalanche vs snowball, see payoff timelines, and see how extra payments shorten your journey and save interest.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="debt-vs-saving/"><span><h3>Debt vs Saving: Which First?</h3><p>Compare putting extra cash toward high-interest debt versus investing it for retirement and see which leaves you with higher net worth over time.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">GO</div><div><h2>Big Goals</h2><p>Map large financial milestones so student loans, housing, and savings goals fit into the bigger plan.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="student-loan-payoff/"><span><h3>Student Loan Payoff</h3><p>Model extra payments, refinancing, and payoff timelines so you can choose a strategy that fits.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="down-payment/"><span><h3>Down Payment / House Savings</h3><p>See how much to save each month to reach your down payment goal and when you'll get there.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>

          <section class="goal-section full">
            <div class="goal-heading"><div class="goal-icon" aria-hidden="true">RT</div><div><h2>Retirement Growth</h2><p>Estimate where you are headed and see how savings, time, compounding, and trade-offs can improve the path.</p></div></div>
            <div class="tool-list">
              <a class="tool-row" href="401k-on-track/"><span><h3>401(k) / IRA On Track?</h3><p>See if your current balance and contributions put you on track for retirement by your target age.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="nest-egg-target/"><span><h3>How Much Do I Need? Nest Egg Target</h3><p>Get a rule-of-thumb target for how much to have saved by retirement. Enter the income you want, subtract Social Security and pensions, and see the nest egg you’re aiming for.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="compound-interest/"><span><h3>The Power of Compound Interest</h3><p>Play with starting amount, monthly contributions, years, and return to see how compounding drives long-term growth.</p></span><span class="tool-action">Open</span></a>
              <a class="tool-row" href="trade-off-explorer/"><span><h3>Retirement Trade-Off Explorer</h3><p>See how retiring later, saving more each year, or spending less (or adding part-time income) changes whether you look on track for your retirement income goal.</p></span><span class="tool-action">Open</span></a>
            </div>
          </section>
        </div>
      </div>
    </main>
    <?php else: ?>
    <section class="planning-shell" id="planning-tools" aria-labelledby="planning-tools-title">
      <p class="section-kicker">Planning workspace</p>
      <h2 class="section-title" id="planning-tools-title">Start with one clear retirement plan</h2>
      <p class="section-copy">The Retirement Plan Builder brings your savings, Social Security, spending, RMDs, and estimated taxes into one year-by-year snapshot. If you already know the specific question you want to answer, browse the focused calculators.</p>
      <a class="btn btn-primary" href="calculators.php">Browse Individual Calculators</a>
    </section>
    <?php endif; ?>

    <?php if (!$hide_site_header): ?>
    <section class="advisor-callout" aria-labelledby="advisor-callout-title">
      <div>
        <h2 id="advisor-callout-title">For Financial Advisors</h2>
        <p>These retirement planning calculators are also available with advisor branding and additional tools designed for financial professionals.</p>
      </div>
      <a class="btn" href="https://calcforadvisors.com/">Visit CalcForAdvisors.com &rarr;</a>
    </section>
    <?php endif; ?>

    <?php if (!$hide_site_header): ?>
    <script>
    (function () {
      var tabBar = document.querySelector('.calculator-tab-bar');
      if (!tabBar) return;

      var tabs = tabBar.querySelectorAll('.calculator-tab');
      var panels = document.querySelectorAll('.tab-panel');

      function tabFromHash() {
        var hash = (location.hash || '').toLowerCase();
        if (hash === '#foundation' || hash === '#early-career') return 'foundation';
        if (hash === '#retirement') return 'retirement';
        return 'retirement';
      }

      function switchTab(tabId) {
        tabs.forEach(function (tab) {
          var active = tab.dataset.tab === tabId;
          tab.classList.toggle('active', active);
          tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
          var active = panel.dataset.tab === tabId;
          panel.hidden = !active;
          panel.classList.toggle('active', active);
        });
      }

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var tabId = tab.dataset.tab;
          switchTab(tabId);
          if (tabId === 'retirement') {
            history.replaceState(null, '', location.pathname + location.search);
          } else {
            history.replaceState(null, '', location.pathname + location.search + '#' + tabId);
          }
        });
      });

      window.addEventListener('hashchange', function () {
        switchTab(tabFromHash());
      });

      switchTab(tabFromHash());
    })();
    </script>
    <?php endif; ?>

    <?php if (!$isLoggedIn && !$hide_site_header): ?>
      <!-- Premium Promotion for Non-Logged-In Users (after they've seen the calculators) -->
      <div class="premium-banner" id="premium">
        <div class="premium-banner-content">
          <div class="premium-banner-text">
            <h2>Calculator Premium Features</h2>
            <p>All calculators above are free to use. Calculator Premium adds the following features:</p>
            <p class="premium-banner-pricing"><?php echo htmlspecialchars($premium_pricing_blurb); ?> <a href="premium.html#pricing">See pricing</a></p>
            <div class="premium-banner-features">
              <div class="premium-feature-item">Save and compare scenarios</div>
              <div class="premium-feature-item">Export PDF and CSV reports</div>
              <div class="premium-feature-item">AI-generated plain-language explanations of your specific results</div>
              <div class="premium-feature-item">Advanced projections (10–20 year)</div>
              <div class="premium-feature-item">Ad-free experience</div>
            </div>
          </div>
          <a href="premium.html" class="premium-banner-cta">Learn More</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$hide_site_header) include __DIR__ . '/includes/footer.php'; ?>

  </div>
</body>
</html>
