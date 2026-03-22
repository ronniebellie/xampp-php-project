<?php
/**
 * Open Graph and Twitter Card meta tags for share previews.
 * Set $og_title and $og_description before including.
 * Optional: $og_image (full URL, e.g. https://ronbelisle.com/images/og-calc.png), $og_site_name
 */
require_once __DIR__ . '/seo_public_url.php';
if (empty($og_title)) $og_title = '';
if (empty($og_description)) $og_description = '';
if (empty($og_url)) {
    $og_url = rb_seo_public_url();
}
$og_site_name = $og_site_name ?? 'Ron Belisle Financial Calculators';

if ($og_title !== ''):
?>
  <link rel="canonical" href="<?php echo htmlspecialchars($og_url); ?>">
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>">
  <meta property="og:site_name" content="<?php echo htmlspecialchars($og_site_name); ?>">
  <meta property="og:locale" content="en_US">
  <?php if (!empty($og_image)): ?>
  <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
  <?php endif; ?>
  <!-- Twitter -->
  <meta name="twitter:card" content="<?php echo !empty($og_image) ? 'summary_large_image' : 'summary'; ?>">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($og_description); ?>">
  <?php if (!empty($og_image)): ?>
  <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
  <?php endif; ?>
<?php endif; ?>
