<?php
/**
 * Open Graph and Twitter Card meta tags for share previews.
 * Set $og_title and $og_description before including.
 * Optional: $og_image (absolute URL), $og_image_alt, $og_site_name
 *
 * Default share image: /images/og-default.png (1200×630). Add that file to the repo / server.
 */
require_once __DIR__ . '/seo_public_url.php';
if (empty($og_title)) $og_title = '';
if (empty($og_description)) $og_description = '';
if (empty($og_url)) {
    $og_url = rb_seo_public_url();
}
$og_site_name = $og_site_name ?? 'Ron Belisle Financial Calculators';

if (empty($og_image)) {
    $og_image = rb_seo_site_base_url() . '/images/og-default.png';
}

// Facebook recommends width/height for link previews (matches og-default.png).
$og_image_width = isset($og_image_width) ? (int) $og_image_width : 1200;
$og_image_height = isset($og_image_height) ? (int) $og_image_height : 630;
$og_image_type = $og_image_type ?? 'image/png';

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
  <meta property="og:image:width" content="<?php echo (int) $og_image_width; ?>">
  <meta property="og:image:height" content="<?php echo (int) $og_image_height; ?>">
  <meta property="og:image:type" content="<?php echo htmlspecialchars($og_image_type); ?>">
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($og_image_alt ?? $og_title); ?>">
  <?php endif; ?>
  <!-- Twitter -->
  <meta name="twitter:card" content="<?php echo !empty($og_image) ? 'summary_large_image' : 'summary'; ?>">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($og_description); ?>">
  <?php if (!empty($og_image)): ?>
  <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
  <?php endif; ?>
<?php endif; ?>
