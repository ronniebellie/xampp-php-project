<?php
/**
 * XML Sitemap for search engines.
 * Access at /sitemap.php or configure server to serve as sitemap.xml
 */
header('Content-Type: application/xml; charset=utf-8');

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ronbelisle.com';
$base = $proto . '://' . $host;

$today = date('Y-m-d');

$urls = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/401k-on-track/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/nest-egg-target/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/survivor-gap/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/retirement-spending-checkup/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/social-security-claiming-analyzer/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/roth-conv/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/rmd-impact/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/managed-vs-vanguard/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/vanguard-pas-vs-target-date/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/retirement-timeline/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/pension-vs-lump-sum/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/plan-success/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/required-vs-desired/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/estate-planning/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/estate-planning/inherited-ira-impact/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/future-value-app/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/ss-gap/', 'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/emergency-fund/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/debt-payoff/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/debt-vs-saving/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/down-payment/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/student-loan-payoff/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/trade-off-explorer/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/present-value/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/present-value-annuity/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/future-value-annuity/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/loan-payment/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/growing-annuity/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/interest-rate/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/time-value-of-money/number-of-periods/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/about-me.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/about.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/premium.html', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/subscribe.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/disclaimer.php', 'priority' => '0.5', 'changefreq' => 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    $loc = $base . $u['loc'];
    $priority = $u['priority'] ?? '0.5';
    $changefreq = $u['changefreq'] ?? 'monthly';
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
