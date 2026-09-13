/* Shared dialogs: Escape, contained keyboard focus, background isolation, restore. */
(function (global) {
  'use strict';
  global.rbAccessibleDialog = function (box, overlay, close) {
    var opener = document.activeElement;
    box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true');
    box.setAttribute('tabindex', '-1');
    var heading = box.querySelector('h2');
    if (heading) box.setAttribute('aria-label', heading.textContent);
    var siblings = Array.from(document.body.children).filter(function (el) { return el !== overlay && !el.contains(overlay); });
    var prior = siblings.map(function (el) { return el.inert; });
    siblings.forEach(function (el) { el.inert = true; });
    function key(e) {
      if (e.key === 'Escape') { e.preventDefault(); close(); return; }
      if (e.key !== 'Tab') return;
      var controls = Array.from(box.querySelectorAll('button,select,textarea,input,a[href],[tabindex="0"]')).filter(function (el) { return !el.disabled && el.getClientRects().length; });
      var first = controls[0], last = controls[controls.length - 1];
      if (!first) { e.preventDefault(); box.focus(); }
      else if (e.shiftKey && (document.activeElement === first || document.activeElement === box)) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && (document.activeElement === last || document.activeElement === box)) { e.preventDefault(); first.focus(); }
    }
    box.addEventListener('keydown', key); box.focus();
    return function () {
      box.removeEventListener('keydown', key);
      siblings.forEach(function (el, i) { el.inert = prior[i]; });
      if (opener && opener.isConnected) opener.focus();
    };
  };
})(window);
