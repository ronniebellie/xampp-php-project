<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-3NB2DLYQFZ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-3NB2DLYQFZ');

  // Lightweight event helper: window.rbTrack('event_name', { param: value })
  window.rbTrack = function (name, params) {
    try { gtag('event', name, params || {}); } catch (e) {}
  };

  // Declarative click tracking: add data-rb-event="name" to any element.
  // Extra params via data-rb-param-<key>="value" (e.g. data-rb-param-location="hero").
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
</script>
