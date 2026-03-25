<?php
/**
 * Homepage JSON-LD: Organization + WebSite (for rich results / knowledge panel hints).
 * Expects $seo_description set by index.php when included from the homepage.
 */
require_once __DIR__ . '/seo_public_url.php';

$base = rb_seo_site_base_url();
$home_description = !empty($seo_description) ? $seo_description : 'Free retirement and financial calculators and planning tools.';

$graph = [
    [
        '@type' => 'Organization',
        '@id' => $base . '/#organization',
        'name' => 'Ron Belisle Financial Calculators',
        'url' => $base . '/',
    ],
    [
        '@type' => 'WebSite',
        '@id' => $base . '/#website',
        'url' => $base . '/',
        'name' => 'Ron Belisle Financial Calculators',
        'description' => $home_description,
        'publisher' => ['@id' => $base . '/#organization'],
        'inLanguage' => 'en-US',
    ],
];

$payload = [
    '@context' => 'https://schema.org',
    '@graph' => $graph,
];
?>
  <script type="application/ld+json"><?php echo json_encode($payload, JSON_UNESCAPED_SLASHES); ?></script>
