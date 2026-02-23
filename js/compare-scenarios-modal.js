/**
 * Shared "Compare scenarios" modal for premium calculators.
 * Usage: CompareScenariosModal.open(apiBase, calculatorType, onCompare, { maxScenarios: 3 }).
 * onCompare(scenarios) receives an array of 2 or 3 scenario objects { id, name, data, created_at, updated_at }.
 */
(function (global) {
  'use strict';

  function open(apiBase, calculatorType, onCompare, options) {
    options = options || {};
    var maxScenarios = Math.min(3, Math.max(2, options.maxScenarios || 2));
    var overlay = document.getElementById('compareScenariosModalOverlay');
    if (overlay) {
      overlay.remove();
    }

    overlay = document.createElement('div');
    overlay.id = 'compareScenariosModalOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:440px;width:100%;padding:24px;';
    box.addEventListener('click', function (e) { e.stopPropagation(); });

    box.innerHTML =
      '<h2 style="margin:0 0 20px 0;font-size:1.35rem;color:#1f2937;">Compare scenarios</h2>' +
      '<p style="color:#6b7280;font-size:0.9rem;margin:0 0 16px 0;">Choose 2 or 3 saved scenarios to compare side-by-side.</p>' +
      '<div style="margin-bottom:12px;"><label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.9rem;">Scenario A</label><select id="compareSelectA" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"></select></div>' +
      '<div style="margin-bottom:12px;"><label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.9rem;">Scenario B</label><select id="compareSelectB" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"></select></div>' +
      '<div id="compareSelectCRow" style="margin-bottom:16px;display:none;"><label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.9rem;">Scenario C</label><select id="compareSelectC" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"></select></div>' +
      '<button type="button" id="compareAddThirdBtn" style="background:none;border:none;color:#3182ce;cursor:pointer;font-size:0.9rem;padding:0 0 16px 0;text-decoration:underline;">+ Add third scenario</button>' +
      '<div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">' +
      '<button type="button" id="compareCancelBtn" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">Cancel</button>' +
      '<button type="button" id="compareDoCompareBtn" style="padding:10px 20px;border:none;border-radius:8px;background:#f59e0b;color:#fff;cursor:pointer;font-weight:600;">Compare</button>' +
      '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    var selectA = document.getElementById('compareSelectA');
    var selectB = document.getElementById('compareSelectB');
    var selectCRow = document.getElementById('compareSelectCRow');
    var selectC = document.getElementById('compareSelectC');
    var addThirdBtn = document.getElementById('compareAddThirdBtn');

    function closeModal() {
      if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }

    function getSelectedScenarios() {
      var list = [];
      var idA = selectA.value;
      var idB = selectB.value;
      if (idA && idB && idA !== idB) {
        list.push(scenariosById[idA], scenariosById[idB]);
        if (maxScenarios >= 3 && selectCRow.style.display !== 'none' && selectC.value && selectC.value !== idA && selectC.value !== idB) {
          list.push(scenariosById[String(selectC.value)]);
        }
      }
      return list;
    }

    var scenariosById = {};
    var scenarios = [];

    var url = (apiBase.replace(/\/?$/, '') + '/api/load_scenarios.php?calculator_type=' + encodeURIComponent(calculatorType)).replace(/\/+/, '/');
    if (url.indexOf('/') === 0) url = window.location.origin + url;

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success) {
          alert(data.error || 'Could not load scenarios');
          closeModal();
          return;
        }
        scenarios = data.scenarios || [];
        if (scenarios.length < 2) {
          alert('You need at least 2 saved scenarios to compare. Save more scenarios first!');
          closeModal();
          return;
        }
        scenarios.forEach(function (s) {
          scenariosById[String(s.id)] = s;
        });
        var opt = '<option value="">— Select —</option>' + scenarios.map(function (s) {
          return '<option value="' + s.id + '">' + escapeHtml(s.name) + '</option>';
        }).join('');
        selectA.innerHTML = opt;
        selectB.innerHTML = opt;
        selectC.innerHTML = opt;
        if (maxScenarios >= 3) {
          addThirdBtn.style.display = 'block';
        } else {
          addThirdBtn.style.display = 'none';
        }
      })
      .catch(function () {
        alert('Failed to load scenarios.');
        closeModal();
      });

    addThirdBtn.addEventListener('click', function () {
      selectCRow.style.display = 'block';
      addThirdBtn.style.display = 'none';
    });

    document.getElementById('compareCancelBtn').addEventListener('click', closeModal);

    document.getElementById('compareDoCompareBtn').addEventListener('click', function () {
      var selected = getSelectedScenarios();
      if (selected.length < 2) {
        alert('Please select two different scenarios (Scenario A and Scenario B).');
        return;
      }
      closeModal();
      onCompare(selected);
    });

    function escapeHtml(s) {
      var div = document.createElement('div');
      div.textContent = s;
      return div.innerHTML;
    }
  }

  global.CompareScenariosModal = { open: open };
})(typeof window !== 'undefined' ? window : this);
