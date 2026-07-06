(function () {
  'use strict';

  var api = window.BUDGET_API;
  var monthPicker = document.getElementById('monthPicker');
  var loadStatus = document.getElementById('loadStatus');
  var state = { accounts: [], categories: [], groups: [], allCategories: [], transactions: [] };

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

  function setStatusEl(id, msg, isError) {
    var el = $(id);
    if (!el) return;
    el.textContent = msg || '';
    el.className = 'status' + (isError ? ' error' : '');
  }

  function currentMonth() {
    return monthPicker && monthPicker.value ? monthPicker.value : new Date().toISOString().slice(0, 7);
  }

  function shiftMonth(delta) {
    if (!monthPicker) return;
    var parts = currentMonth().split('-');
    var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1 + delta, 1);
    monthPicker.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    loadMonth();
  }

  function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-tab') === tab);
    });
    document.querySelectorAll('.panel').forEach(function (p) {
      p.classList.remove('active');
    });
    var panel = $('panel-' + tab);
    if (panel) panel.classList.add('active');
  }

  function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        switchTab(btn.getAttribute('data-tab'));
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

  function accountOptionLabel(account) {
    var typeLabel = account.account_type_label || account.account_type || '';
    return account.name + (typeLabel ? ' (' + typeLabel + ')' : '');
  }

  function fillAccountSelect(select, accounts) {
    if (!select) return;
    select.innerHTML = '';
    accounts.forEach(function (account) {
      var opt = document.createElement('option');
      opt.value = account.id;
      opt.textContent = accountOptionLabel(account);
      select.appendChild(opt);
    });
  }

  function apiPost(body) {
    return fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function accountTypeOptions(selected) {
    var types = [
      ['checking', 'Checking'],
      ['savings', 'Savings'],
      ['cash', 'Cash'],
      ['credit', 'Credit card'],
    ];
    return types.map(function (pair) {
      var sel = pair[0] === selected ? ' selected' : '';
      return '<option value="' + pair[0] + '"' + sel + '>' + pair[1] + '</option>';
    }).join('');
  }

  function groupOptions(selectedId) {
    return state.groups.map(function (g) {
      var sel = g.id === selectedId ? ' selected' : '';
      return '<option value="' + g.id + '"' + sel + '>' + escapeHtml(g.name) + '</option>';
    }).join('');
  }

  function renderAccounts(accounts) {
    var tbody = document.querySelector('#accountsTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    accounts.forEach(function (account) {
      var tr = document.createElement('tr');
      var canDelete = accounts.length > 1 && account.transaction_count === 0;
      tr.innerHTML =
        '<td><input type="text" class="account-name-input" data-account-id="' + account.id +
          '" value="' + escapeHtml(account.name) + '" maxlength="128" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;"></td>' +
        '<td><select class="account-type-input" data-account-id="' + account.id + '" style="padding:8px;border:1px solid #e5e7eb;border-radius:6px;">' +
          accountTypeOptions(account.account_type) +
        '</select></td>' +
        '<td class="num">' + account.transaction_count + '</td>' +
        '<td><button type="button" class="btn-secondary btn-danger account-delete-btn" data-account-id="' + account.id +
          '" ' + (canDelete ? '' : 'disabled') + '>Delete</button></td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.account-name-input').forEach(function (input) {
      input.addEventListener('change', function () {
        saveAccount(parseInt(input.getAttribute('data-account-id'), 10));
      });
    });
    tbody.querySelectorAll('.account-type-input').forEach(function (select) {
      select.addEventListener('change', function () {
        saveAccount(parseInt(select.getAttribute('data-account-id'), 10));
      });
    });
    tbody.querySelectorAll('.account-delete-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        deleteAccount(parseInt(btn.getAttribute('data-account-id'), 10));
      });
    });
  }

  function saveAccount(accountId) {
    var nameInput = document.querySelector('.account-name-input[data-account-id="' + accountId + '"]');
    var typeSelect = document.querySelector('.account-type-input[data-account-id="' + accountId + '"]');
    if (!nameInput || !typeSelect) return;
    var name = nameInput.value.trim();
    if (!name) {
      alert('Account name cannot be empty.');
      return loadMonth();
    }
    apiPost({ action: 'update_account', account_id: accountId, name: name, account_type: typeSelect.value })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Update failed');
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); return loadMonth(); });
  }

  function deleteAccount(accountId) {
    if (!confirm('Delete this account? This cannot be undone.')) return;
    apiPost({ action: 'delete_account', account_id: accountId })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Delete failed');
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); });
  }

  function renderCategories(allCategories) {
    var tbody = document.querySelector('#categoriesTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    allCategories.forEach(function (cat) {
      var tr = document.createElement('tr');
      if (cat.is_hidden) tr.style.opacity = '0.65';
      tr.innerHTML =
        '<td><select class="cat-group-input" data-category-id="' + cat.id + '" style="padding:8px;border:1px solid #e5e7eb;border-radius:6px;">' +
          groupOptions(cat.group_id) +
        '</select></td>' +
        '<td><input type="text" class="cat-name-input" data-category-id="' + cat.id +
          '" value="' + escapeHtml(cat.name) + '" maxlength="128" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;"></td>' +
        '<td class="num">' + cat.transaction_count + '</td>' +
        '<td>' + (cat.is_hidden ? 'Hidden' : 'Active') + '</td>' +
        '<td><button type="button" class="btn-secondary cat-hide-btn" data-category-id="' + cat.id +
          '" data-hidden="' + (cat.is_hidden ? '0' : '1') + '">' + (cat.is_hidden ? 'Show' : 'Hide') + '</button></td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.cat-name-input, .cat-group-input').forEach(function (el) {
      el.addEventListener('change', function () {
        saveCategory(parseInt(el.getAttribute('data-category-id'), 10));
      });
    });
    tbody.querySelectorAll('.cat-hide-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        toggleCategoryHidden(parseInt(btn.getAttribute('data-category-id'), 10), btn.getAttribute('data-hidden') === '1');
      });
    });
  }

  function saveCategory(categoryId) {
    var nameInput = document.querySelector('.cat-name-input[data-category-id="' + categoryId + '"]');
    var groupSelect = document.querySelector('.cat-group-input[data-category-id="' + categoryId + '"]');
    if (!nameInput || !groupSelect) return;
    var name = nameInput.value.trim();
    if (!name) {
      alert('Category name cannot be empty.');
      return loadMonth();
    }
    apiPost({
      action: 'update_category',
      category_id: categoryId,
      name: name,
      group_id: parseInt(groupSelect.value, 10),
    })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Update failed');
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); return loadMonth(); });
  }

  function toggleCategoryHidden(categoryId, hidden) {
    apiPost({ action: 'set_category_hidden', category_id: categoryId, hidden: hidden })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Update failed');
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); });
  }

  function resetTxnForm() {
    $('txnEditId').value = '';
    $('txnFormTitle').textContent = 'Add transaction';
    $('txnSubmitBtn').textContent = 'Save transaction';
    $('txnCancelBtn').style.display = 'none';
    $('txnPayee').value = '';
    $('txnMemo').value = '';
    $('txnAmount').value = '';
    var expense = document.querySelector('input[name="flow"][value="expense"]');
    if (expense) expense.checked = true;
    if (!$('txnDate').value) {
      $('txnDate').value = new Date().toISOString().slice(0, 10);
    }
    setFormStatus('');
  }

  function startEditTransaction(t) {
    switchTab('add');
    $('txnEditId').value = String(t.id);
    $('txnFormTitle').textContent = 'Edit transaction';
    $('txnSubmitBtn').textContent = 'Update transaction';
    $('txnCancelBtn').style.display = 'inline-block';
    $('txnDate').value = t.txn_date;
    $('txnAccount').value = String(t.account_id);

    var catSelect = $('txnCategory');
    var catId = String(t.category_id);
    if (catSelect && !catSelect.querySelector('option[value="' + catId + '"]')) {
      var cat = state.allCategories.find(function (c) { return String(c.id) === catId; });
      if (cat) {
        var opt = document.createElement('option');
        opt.value = catId;
        opt.textContent = cat.name + ' (hidden)';
        catSelect.appendChild(opt);
      }
    }
    if (catSelect) catSelect.value = catId;

    $('txnPayee').value = t.payee;
    $('txnMemo').value = t.memo || '';
    $('txnAmount').value = money(Math.abs(t.amount));
    var flow = t.amount >= 0 ? 'income' : 'expense';
    var flowEl = document.querySelector('input[name="flow"][value="' + flow + '"]');
    if (flowEl) flowEl.checked = true;
    setFormStatus('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function deleteTransaction(id) {
    if (!confirm('Delete this transaction?')) return;
    apiPost({ action: 'delete_transaction', transaction_id: id })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Delete failed');
        if ($('txnEditId').value === String(id)) resetTxnForm();
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); });
  }

  function renderRegister(transactions) {
    var tbody = document.querySelector('#registerTable tbody');
    var empty = $('registerEmpty');
    if (!tbody) return;
    tbody.innerHTML = '';
    state.transactions = transactions;

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
        '<td>' + escapeHtml(t.account_name || '—') + '</td>' +
        '<td>' + escapeHtml(t.category_name || '—') + '</td>' +
        '<td class="num neg">' + outflow + '</td>' +
        '<td class="num pos">' + inflow + '</td>' +
        '<td><button type="button" class="btn-secondary txn-edit-btn" data-txn-id="' + t.id + '">Edit</button> ' +
        '<button type="button" class="btn-secondary btn-danger txn-delete-btn" data-txn-id="' + t.id + '">Delete</button></td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.txn-edit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-txn-id'), 10);
        var t = state.transactions.find(function (x) { return x.id === id; });
        if (t) startEditTransaction(t);
      });
    });
    tbody.querySelectorAll('.txn-delete-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        deleteTransaction(parseInt(btn.getAttribute('data-txn-id'), 10));
      });
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

  function loadMonth() {
    setLoadStatus('Loading…');
    return fetch(api + '?month=' + encodeURIComponent(currentMonth()), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Load failed');
        state.accounts = data.accounts;
        state.categories = data.categories;
        state.groups = data.groups;
        state.allCategories = data.all_categories || [];
        fillAccountSelect($('txnAccount'), data.accounts);
        fillSelect($('txnCategory'), data.categories, 'id', 'name');
        fillSelect($('newCategoryGroup'), data.groups, 'id', 'name');
        renderRegister(data.transactions);
        renderPlan(data.plan);
        renderAccounts(data.accounts);
        renderCategories(state.allCategories);
        document.dispatchEvent(new CustomEvent('budget:loaded', {
          detail: { accounts: data.accounts, categories: data.categories },
        }));
        setLoadStatus('');
      })
      .catch(function (err) {
        setLoadStatus(err.message, true);
      });
  }

  function saveTarget(categoryId, target) {
    apiPost({ action: 'set_target', category_id: categoryId, month: currentMonth(), target: target })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Save failed');
        return loadMonth();
      })
      .catch(function (err) { alert(err.message); });
  }

  function bindAccountForm() {
    var form = $('accountForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      setStatusEl('accountFormStatus', 'Saving…');
      apiPost({ action: 'add_account', name: $('newAccountName').value.trim(), account_type: $('newAccountType').value })
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || 'Add failed');
          $('newAccountName').value = '';
          $('newAccountType').value = 'checking';
          setStatusEl('accountFormStatus', 'Account added.');
          return loadMonth();
        })
        .catch(function (err) { setStatusEl('accountFormStatus', err.message, true); });
    });
  }

  function bindCategoryForms() {
    var groupForm = $('groupForm');
    if (groupForm) {
      groupForm.addEventListener('submit', function (e) {
        e.preventDefault();
        setStatusEl('groupFormStatus', 'Saving…');
        apiPost({ action: 'add_category_group', name: $('newGroupName').value.trim() })
          .then(function (data) {
            if (!data.ok) throw new Error(data.error || 'Add failed');
            $('newGroupName').value = '';
            setStatusEl('groupFormStatus', 'Group added.');
            return loadMonth();
          })
          .catch(function (err) { setStatusEl('groupFormStatus', err.message, true); });
      });
    }

    var categoryForm = $('categoryForm');
    if (categoryForm) {
      categoryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        setStatusEl('categoryFormStatus', 'Saving…');
        apiPost({
          action: 'add_category',
          name: $('newCategoryName').value.trim(),
          group_id: parseInt($('newCategoryGroup').value, 10),
        })
          .then(function (data) {
            if (!data.ok) throw new Error(data.error || 'Add failed');
            $('newCategoryName').value = '';
            setStatusEl('categoryFormStatus', 'Category added.');
            return loadMonth();
          })
          .catch(function (err) { setStatusEl('categoryFormStatus', err.message, true); });
      });
    }
  }

  function bindForm() {
    var form = $('txnForm');
    if (!form) return;

    if ($('txnDate') && !$('txnDate').value) {
      $('txnDate').value = new Date().toISOString().slice(0, 10);
    }

    $('txnCancelBtn').addEventListener('click', resetTxnForm);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var flowEl = form.querySelector('input[name="flow"]:checked');
      var editId = $('txnEditId').value;
      var payload = {
        account_id: parseInt($('txnAccount').value, 10),
        category_id: parseInt($('txnCategory').value, 10),
        txn_date: $('txnDate').value,
        payee: $('txnPayee').value.trim(),
        memo: $('txnMemo').value.trim(),
        amount: $('txnAmount').value,
        flow: flowEl ? flowEl.value : 'expense',
      };

      if (editId) {
        payload.action = 'update_transaction';
        payload.transaction_id = parseInt(editId, 10);
      } else {
        payload.action = 'add_transaction';
      }

      setFormStatus('Saving…');
      apiPost(payload)
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || 'Save failed');
          resetTxnForm();
          setFormStatus(editId ? 'Updated.' : 'Saved.');
          return loadMonth();
        })
        .catch(function (err) { setFormStatus(err.message, true); });
    });
  }

  function bindExport() {
    var btn = $('exportCsvBtn');
    if (!btn || !window.BUDGET_EXPORT_API) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      window.location.href = window.BUDGET_EXPORT_API + '?month=' + encodeURIComponent(currentMonth());
    });
  }

  function init() {
    if (!monthPicker) return;
    monthPicker.value = new Date().toISOString().slice(0, 7);
    monthPicker.addEventListener('change', loadMonth);
    $('monthPrev').addEventListener('click', function () { shiftMonth(-1); });
    $('monthNext').addEventListener('click', function () { shiftMonth(1); });
    initTabs();
    bindForm();
    bindAccountForm();
    bindCategoryForms();
    bindExport();
    window.budgetReloadMonth = loadMonth;
    loadMonth();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
