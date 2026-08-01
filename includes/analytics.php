<?php
/**
 * Shared Google Analytics 4 tag for ronbelisle.com and journey.ronbelisle.com.
 * Measurement ID: G-3NB2DLYQFZ
 *
 * Loads once per page. cookie_domain is set to the parent domain so sessions
 * can continue across the Journey subdomain without a second GA4 property.
 */
?>
<!-- Google tag (gtag.js) -->
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

  // Lightweight event helper: window.rbTrack('event_name', { param: value })
  // Never pass names, emails, account IDs, or financial values in params.
  window.rbTrack = function (name, params) {
    try {
      if (typeof gtag !== 'function' || !name) return;
      gtag('event', name, params || {});
    } catch (e) {}
  };

  // Declarative click tracking: add data-rb-event="name" to any element.
  // Extra params via data-rb-param-<key>="value" (e.g. data-rb-param-placement="homepage_hero").
  if (!window.__rbTrackClickBound) {
    window.__rbTrackClickBound = true;
    document.addEventListener('click', function (e) {
      var t = e.target && e.target.closest ? e.target.closest('[data-rb-event]') : null;
      if (!t) return;
      var params = {};
      for (var i = 0; i < t.attributes.length; i++) {
        var a = t.attributes[i];
        if (a.name.indexOf('data-rb-param-') === 0) {
          params[a.name.slice('data-rb-param-'.length)] = a.value;
        }
      }
      window.rbTrack(t.getAttribute('data-rb-event'), params);
    }, true);
  }
</script>
