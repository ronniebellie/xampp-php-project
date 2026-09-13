<?php
/**
 * JSON-LD SoftwareApplication structured data for calculator pages.
 * Set $ld_name and $ld_description before including.
 */
require_once __DIR__ . '/seo_public_url.php';
$ld_url = rb_seo_public_url();
$site = rb_seo_site_base_url();
require_once __DIR__ . '/calculator_catalog.php';
foreach (rb_calculator_catalog() as $calculator) {
    if ($calculator['active'] && $calculator['route'] === parse_url($ld_url, PHP_URL_PATH)) {
        $ld_name = $calculator['name'];
        $ld_description = $calculator['description'];
        break;
    }
}
if (empty($ld_name) || empty($ld_description)) return;

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $ld_name,
    'description' => $ld_description,
    'url' => $ld_url,
    'applicationCategory' => 'FinanceApplication',
    'operatingSystem' => 'Web',
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'USD',
    ],
    'isAccessibleForFree' => true,
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Ron Belisle Financial Calculators',
        'url' => $site,
    ],
];
?>
  <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
