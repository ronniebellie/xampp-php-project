<?php
/**
 * JSON-LD SoftwareApplication structured data for calculator pages.
 * Set $ld_name and $ld_description before including.
 */
if (empty($ld_name) || empty($ld_description)) return;

require_once __DIR__ . '/seo_public_url.php';
$ld_url = rb_seo_public_url();

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $ld_name,
    'description' => $ld_description,
    'url' => $ld_url,
    'applicationCategory' => 'FinanceApplication',
    'operatingSystem' => 'Web',
];
?>
  <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES); ?></script>
