(function () {
  'use strict';

  var api = window.BUDGET_API;
  var monthPicker = document.getElementById('monthPicker');
  var loadStatus = document.getElementById('loadStatus');
  var state = { accounts: [], categories: [] };

  function $(id) {
    return document.getElementById(id);
  }

  function money(n) {
    var x = Number(n);
    if (!isFinite(x)) return '0';
    return x.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function setLoadStatus(msg, isError) {
    if (!loadStatus) return;
    loadStatus.textContent = msg || '';
    loadStatus.style.color = isError ? '#b91c1c' : '#6b7280';
  }

  function setFormStatus(msg, isError) {
    var el = $('formStatus');
    if (!el) return;
    el.textContent = msg || '';
    el.className = 'status' + (isError ? ' error' : '');
  }

  function currentMonth() {
    return monthPicker && monthPicker.value ? monthPicker.value : new Date().toISOString().slice(0, 7);
  }

  function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('active'); });
        btn.classList.add('active');
        var tab = btn.getAttribute('data-tab');
        var panel = $('panel-' + tab);
        if (panel) panel.classList.add('active');
      });
    });
  }

  function fillSelect(select, items, valueKey, labelKey) {
    if (!select) return;
    select.innerHTML = '';
    items.forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item[valueKey];
      opt.textContent = item[labelKey];
      select.appendChild(opt);
    });
  }

  function renderRegister(transactions) {
    var tbody = document.querySelector('#registerTable tbody');
    var empty = $('registerEmpty');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!transactions.length) {
      if (empty) empty.style.display = 'block';
      return;
    }
    if (empty) empty.style.display = 'none';
    transactions.forEach(function (t) {
      var tr = document.createElement('tr');
      var outflow = t.amount < 0 ? money(Math.abs(t.amount)) : '';
      var inflow = t.amount > 0 ? money(t.amount) : '';
      tr.innerHTML =
        '<td>' + escapeHtml(t.txn_date) + '</td>' +
        '<td>' + escapeHtml(t.payee) + '</td>' +
        '<td>' + escapeHtml(t.category_name || '—') + '</td>' +
        '<td class="num neg">' + outflow + '</td>' +
        '<td class="num pos">' + inflow + '</td>';
      tbody.appendChild(tr);
    });
  }

  function renderPlan(plan) {
    var tbody = document.querySelector('#planTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    plan.forEach(function (row) {
      var tr = document.createElement('tr');
      var availClass = row.available < 0 ? 'neg warn' : 'num';
      tr.innerHTML =
        '<td>' + escapeHtml((row.group_name ? row.group_name + ': ' : '') + row.category_name) + '</td>' +
        '<td class="num"><input type="number" class="target-input" min="0" step="0.01" data-category-id="' +
          row.category_id + '" value="' + money(row.target) + '"></td>' +
        '<td class="num ' + (row.activity < 0 ? 'neg' : row.activity > 0 ? 'pos' : '') + '">' + money(row.activity) + '</td>' +
        '<td class="' + availClass + '">' + money(row.available) + '</td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.target-input').forEach(function (input) {
      input.addEventListener('change', function () {
        saveTarget(parseInt(input.getAttribute('data-category-id'), 10), parseFloat(input.value) || 0);
      });
    });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function loadMonth() {
    setLoadStatus('Loading…');
    return fetch(api + '?month=' + encodeURIComponent(currentMonth()), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Load failed');
        state.accounts = data.accounts;
        state.categories = data.categories;
        fillSelect($('txnAccount'), data.accounts, 'id', 'name');
        fillSelect($('txnCategory'), data.categories, 'id', 'name');
        renderRegister(data.transactions);
        renderPlan(data.plan);
        setLoadStatus('');
      })
      .catch(function (err) {
        setLoadStatus(err.message, true);
      });
  }

  function saveTarget(categoryId, target) {
    fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'set_target',
        category_id: categoryId,
        month: currentMonth(),
        target: target,
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Save failed');
        return loadMonth();
      })
      .catch(function (err) {
        alert(err.message);
      });
  }

  function bindForm() {
    var form = $('txnForm');
    if (!form) return;

    var dateInput = $('txnDate');
    if (dateInput && !dateInput.value) {
      dateInput.value = new Date().toISOString().slice(0, 10);
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var flowEl = form.querySelector('input[name="flow"]:checked');
      setFormStatus('Saving…');
      fetch(api, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'add_transaction',
          account_id: parseInt($('txnAccount').value, 10),
          category_id: parseInt($('txnCategory').value, 10),
          txn_date: $('txnDate').value,
          payee: $('txnPayee').value.trim(),
          memo: $('txnMemo').value.trim(),
          amount: $('txnAmount').value,
          flow: flowEl ? flowEl.value : 'expense',
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || 'Save failed');
          $('txnPayee').value = '';
          $('txnMemo').value = '';
          $('txnAmount').value = '';
          setFormStatus('Saved.');
          return loadMonth();
        })
        .catch(function (err) {
          setFormStatus(err.message, true);
        });
    });
  }

  function init() {
    if (!monthPicker) return;
    monthPicker.value = new Date().toISOString().slice(0, 7);
    monthPicker.addEventListener('change', loadMonth);
    initTabs();
    bindForm();
    loadMonth();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
