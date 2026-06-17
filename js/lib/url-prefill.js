/**
 * Build calculator URLs with query params and apply them on page load.
 */
(function (global) {
  'use strict';

  function setFieldValue(id, value) {
    var el = document.getElementById(id);
    if (!el || value === null || value === undefined || value === '') return false;
    if (el.type === 'checkbox') {
      el.checked = value === true || value === 'true' || value === '1' || value === 'yes';
      return true;
    }
    el.value = value;
    return true;
  }

  /**
   * @param {Object.<string, string|function>} mapping param name -> field id or setter(value, params)
   * @param {object} options { required: string[], formId, autoSubmit, afterApply(params) }
   */
  function applyFromUrl(mapping, options) {
    options = options || {};
    var params = new URLSearchParams(window.location.search || '');
    if (!params.toString()) return false;

    var required = options.required || [];
    for (var i = 0; i < required.length; i++) {
      if (!params.has(required[i])) return false;
    }

    var applied = false;
    Object.keys(mapping).forEach(function (paramKey) {
      if (!params.has(paramKey)) return;
      var target = mapping[paramKey];
      var val = params.get(paramKey);
      if (typeof target === 'function') {
        target(val, params);
        applied = true;
      } else if (setFieldValue(target, val)) {
        applied = true;
      }
    });

    if (applied && options.afterApply) options.afterApply(params);

    if (applied && options.autoSubmit) {
      if (options.formId) {
        var form = document.getElementById(options.formId);
        if (form) {
          form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
      } else if (typeof options.autoRun === 'function') {
        options.autoRun();
      }
    }

    return applied;
  }

  function buildUrl(basePath, params) {
    var p = new URLSearchParams();
    Object.keys(params).forEach(function (k) {
      var v = params[k];
      if (v !== null && v !== undefined && v !== '') {
        p.set(k, String(v));
      }
    });
    var qs = p.toString();
    return basePath + (qs ? '?' + qs : '');
  }

  global.RBUrlPrefill = {
    setFieldValue: setFieldValue,
    applyFromUrl: applyFromUrl,
    buildUrl: buildUrl
  };
})(typeof window !== 'undefined' ? window : this);
