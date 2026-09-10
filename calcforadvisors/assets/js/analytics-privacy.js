/* Shared by all three sites. Fail closed before loading any third-party analytics. */
(function (window, document) {
  'use strict';
  if (window.rbAnalyticsInit) return;
  function cleanUrl(value) {
    try { var url = new URL(value); return url.origin + url.pathname; } catch (_) { return ''; }
  }
  function sensitiveContext() {
    try {
      var url = new URL(window.location.href);
      var ref = document.referrer ? new URL(document.referrer) : null;
      return !!(url.search || url.hash || (ref && (ref.search || ref.hash)) ||
        /\/(?:set-password|reset-password|trial-setup|calcforadvisors-bridge)\.php$/i.test(url.pathname));
    } catch (_) { return true; }
  }
  window.rbAnalyticsInit = function (id, cookieDomain) {
    if (window.__rbAnalyticsInitialized) return;
    window.__rbAnalyticsInitialized = true;
    window.dataLayer = window.dataLayer || [];
    var blocked = sensitiveContext();
    // Preserve the API for existing events, but never queue events on sensitive pages.
    function enqueue() { window.dataLayer.push(arguments); }
    window.gtag = function (command, name, params) {
      if (blocked || sensitiveContext()) return;
      if (command === 'event' || command === 'config') {
        params = Object.assign({}, params || {}, {
          page_location: cleanUrl(window.location.href),
          page_referrer: cleanUrl(document.referrer)
        });
      }
      enqueue.apply(null, arguments.length > 2 || command === 'config' || command === 'event'
        ? [command, name, params] : [command, name]);
    };
    window.rbTrack = function (name, params) { if (name) window.gtag('event', name, params); };
    if (blocked) return;
    window.gtag('js', new Date());
    var config = { send_page_view: true };
    if (cookieDomain) config.cookie_domain = cookieDomain;
    window.gtag('config', id, config);
    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(script);
  };
  // Static advisor pages pass configuration on this local script element.
  var current = document.currentScript;
  if (current && current.dataset.gaId) window.rbAnalyticsInit(current.dataset.gaId);
})(window, document);
