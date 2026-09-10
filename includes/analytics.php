<?php /* Shared, privacy-gated GA4 initialization. */ ?>
<script>
<?php readfile(__DIR__ . '/../calcforadvisors/assets/js/analytics-privacy.js'); ?>
window.rbAnalyticsInit('G-3NB2DLYQFZ', 'ronbelisle.com');
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
