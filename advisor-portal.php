<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/calcforadvisors_portal.php';

header('X-Robots-Tag: noindex, nofollow', true);
$slug = (string) ($_GET['portal_slug'] ?? '');
$loader = static function (string $validSlug): ?array {
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        require __DIR__ . '/includes/db_config.php';
        return isset($conn) && $conn instanceof mysqli ? cfa_load_public_portal($conn, $validSlug) : null;
    } catch (Throwable $e) {
        error_log('CalcForAdvisors portal database unavailable: ' . $e->getMessage());
        return null;
    }
};

// Explicit local-only fixture for non-production visual review; never active on a public host.
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocal = $host === 'localhost' || str_starts_with($host, 'localhost:') || $host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:');
if ($isLocal && $slug === 'demo-advisor') {
    $loader = static fn(string $validSlug): array => [
        'id'=>0,'portal_slug'=>$validSlug,'firm_name'=>'Northgate Retirement Planning','advisor_name'=>'Jordan Morgan, CFP®',
        'public_email'=>'hello@example.com','phone'=>'(555) 014-2026','website_url'=>'https://example.com',
        'disclosure_text'=>'These tools are provided for educational purposes and are not a recommendation or guarantee of financial results.',
        'plan'=>'monthly','status'=>'active','stripe_subscription_status'=>'active','logo_url'=>'','banner_url'=>'',
    ];
}
$portal = cfa_resolve_public_portal($slug, $loader);
if ($portal['state'] === 'invalid') http_response_code(404);
if ($portal['state'] === 'unavailable') http_response_code(403);
$profile = $portal['profile'];
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$groups = rb_advisor_calculators_grouped();
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title><?= $portal['state']==='available' ? $e(($profile['firm_name'] ?: 'Advisor').' Calculators') : 'Advisor Portal Unavailable' ?></title>
<link rel="stylesheet" href="/css/advisor-portal.css"></head><body class="cfa-page">
<?php if ($portal['state'] !== 'available'): ?>
<main class="cfa-shell"><section class="cfa-notice"><p class="cfa-eyebrow">CalcForAdvisors</p><h1><?= $portal['state']==='invalid' ? 'Advisor portal not found' : 'This advisor portal is currently unavailable.' ?></h1><p><?= $portal['state']==='invalid' ? 'Check the address and try again.' : 'Please contact your advisor if you need assistance.' ?></p></section></main>
<?php else: ?>
<header class="cfa-hero"><?php if ($profile['banner_url']): ?><img class="cfa-banner" src="<?= $e($profile['banner_url']) ?>" alt=""><?php endif; ?><div class="cfa-shell cfa-identity">
<?php if ($profile['logo_url']): ?><img class="cfa-logo" src="<?= $e($profile['logo_url']) ?>" alt="<?= $e($profile['firm_name']) ?> logo"><?php endif; ?><div><p class="cfa-eyebrow">Retirement planning resources</p><h1><?= $e($profile['firm_name'] ?: 'Your Advisor') ?></h1>
<?php if ($profile['advisor_name']): ?><p class="cfa-advisor-name"><?= $e($profile['advisor_name']) ?></p><?php endif; ?><div class="cfa-contact">
<?php if ($profile['public_email']): ?><a href="mailto:<?= $e($profile['public_email']) ?>"><?= $e($profile['public_email']) ?></a><?php endif; ?>
<?php if ($profile['phone']): ?><a href="tel:<?= $e(preg_replace('/[^0-9+]/','',$profile['phone'])) ?>"><?= $e($profile['phone']) ?></a><?php endif; ?>
<?php if ($profile['website_url']): ?><a href="<?= $e($profile['website_url']) ?>" target="_blank" rel="noopener noreferrer">Visit website</a><?php endif; ?></div></div></div></header>
<main class="cfa-shell cfa-main"><section class="cfa-intro"><h2>Planning calculators to explore together</h2><p>Use these educational tools to model common retirement decisions and prepare questions for your next conversation.</p></section>
<?php $number=0; foreach ($groups as $category=>$calculators): $number++; ?><section class="cfa-category"><div class="cfa-category-heading"><span><?= $number ?></span><h2><?= $e(RB_CALCULATOR_ADVISOR_CATEGORIES[$category]) ?></h2></div><div class="cfa-grid">
<?php foreach ($calculators as $calculator): ?><article class="cfa-card"><h3><?= $e($calculator['name']) ?></h3><p><?= $e($calculator['description']) ?></p><?php $badges=cfa_calculator_badges($calculator); if ($badges): ?><div class="cfa-badges"><?php foreach ($badges as $badge): ?><span class="cfa-badge"><?= $e($badge) ?></span><?php endforeach; ?></div><?php endif; ?><a class="cfa-open" href="<?= $e(cfa_portal_path($profile['portal_slug']).'/calculator/'.rawurlencode($calculator['id'])) ?>">Open calculator <span aria-hidden="true">→</span></a></article><?php endforeach; ?>
</div></section><?php endforeach; ?>
<?php if ($profile['disclosure_text']): ?><section class="cfa-disclosure" aria-label="Advisor disclosure"><?= $e($profile['disclosure_text']) ?></section><?php endif; ?></main>
<footer class="cfa-footer"><div class="cfa-shell"><p><strong>Powered by RonBelisle.com</strong></p><p>For educational and informational purposes only. Results are estimates, not financial, tax, investment, or legal advice.</p></div></footer>
<?php endif; ?></body></html>
