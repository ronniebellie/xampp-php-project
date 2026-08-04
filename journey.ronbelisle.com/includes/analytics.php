<?php
/**
 * Journey GA4 include — dedicated "Journey Retirement Planning" property.
 * Measurement ID: G-8PMXKZ60L4
 *
 * Intentionally does NOT load the shared ronbelisle.com analytics tag so
 * Journey traffic reports only to this property.
 */
?>
<!-- Google tag (gtag.js) — Journey Retirement Planning -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-8PMXKZ60L4"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  if (!window.__journeyGtagConfigured) {
    window.__journeyGtagConfigured = true;
    gtag('js', new Date());
    gtag('config', 'G-8PMXKZ60L4', {
      cookie_domain: 'ronbelisle.com',
      send_page_view: true
    });
  }
  window.rbTrack = window.rbTrack || function (name, params) {
    try {
      if (typeof gtag !== 'function' || !name) return;
      gtag('event', name, params || {});
    } catch (e) {}
  };
</script>
