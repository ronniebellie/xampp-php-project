<?php
/**
 * JSON-LD SoftwareApplication structured data for calculator pages.
 * Set $ld_name and $ld_description before including.
 */
if (empty($ld_name) || empty($ld_description)) return;

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ronbelisle.com';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$ld_url = $proto . '://' . $host . $uri;

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
