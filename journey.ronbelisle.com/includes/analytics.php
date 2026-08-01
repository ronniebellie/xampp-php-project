<?php
/**
 * Journey GA4 include — uses the shared ronbelisle.com Measurement ID.
 * Prefer the shared file so the tag stays a single source of truth.
 */
$rbSharedAnalytics = dirname(__DIR__, 2) . '/includes/analytics.php';
if (is_readable($rbSharedAnalytics)) {
    include $rbSharedAnalytics;
    return;
}
?>
<!-- Google tag (gtag.js) — Journey fallback -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-3NB2DLYQFZ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  if (!window.__rbGtagConfigured) {
    window.__rbGtagConfigured = true;
    gtag('js', new Date());
    gtag('config', 'G-3NB2DLYQFZ', {
      cookie_domain: 'ronbelisle.com',
      send_page_view: true
    });
  }
  window.rbTrack = window.rbTrack || function (name, params) {
    try { gtag('event', name, params || {}); } catch (e) {}
  };
</script>
