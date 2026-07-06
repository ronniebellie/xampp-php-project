(function () {
  'use strict';

  var api = window.BUDGET_API;
  var isPremium = !!window.BUDGET_IS_PREMIUM;
  var parsedRows = [];
  var csvHeaders = [];
  var csvRawRows = [];
  var headerRowIndex = 0;

  function $(id) {
    return document.getElementById(id);
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function money(n) {
    var x = Number(n);
    if (!isFinite(x)) return '0';
    return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function setImportStatus(msg, isError) {
    var el = $('importStatus');
    if (!el) return;
    el.textContent = msg || '';
    el.className = 'status' + (isError ? ' error' : '');
  }

  function parseCsv(text) {
    text = text.replace(/^\uFEFF/, '');
    var rows = [];
    var row = [];
    var field = '';
    var i = 0;
    var inQuotes = false;

    while (i < text.length) {
      var ch = text[i];
      if (inQuotes) {
        if (ch === '"') {
          if (text[i + 1] === '"') {
            field += '"';
            i += 2;
            continue;
          }
          inQuotes = false;
          i++;
          continue;
        }
        field += ch;
        i++;
        continue;
      }
      if (ch === '"') {
        inQuotes = true;
        i++;
        continue;
      }
      if (ch === ',') {
        row.push(field);
        field = '';
        i++;
        continue;
      }
      if (ch === '\r') {
        i++;
        continue;
      }
      if (ch === '\n') {
        row.push(field);
        rows.push(row);
        row = [];
        field = '';
        i++;
        continue;
      }
      field += ch;
      i++;
    }
    if (field.length || row.length) {
      row.push(field);
      rows.push(row);
    }
    return rows;
  }

  function findFidelityHeader(rows) {
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      if (!row || !row.length) continue;
      var first = (row[0] || '').trim();
      var hasAmount = row.some(function (c) {
        return (c || '').trim().indexOf('Amount') === 0;
      });
      if (first === 'Run Date' && hasAmount) return i;
    }
    return -1;
  }

  function findGenericHeader(rows) {
    for (var i = 0; i < Math.min(rows.length, 20); i++) {
      var row = rows[i];
      if (!row || row.filter(function (c) { return (c || '').trim(); }).length < 2) continue;
      return i;
    }
    return 0;
  }

  function fillColumnSelect(select, headers, selected) {
    if (!select) return;
    select.innerHTML = '<option value="">— skip —</option>';
    headers.forEach(function (h, idx) {
      var opt = document.createElement('option');
      opt.value = String(idx);
      opt.textContent = h || ('Column ' + (idx + 1));
      if (String(idx) === String(selected)) opt.selected = true;
      select.appendChild(opt);
    });
  }

  function headerIndexByName(name) {
    var target = name.toLowerCase();
    for (var i = 0; i < csvHeaders.length; i++) {
      if ((csvHeaders[i] || '').trim().toLowerCase() === target) return String(i);
    }
    for (var j = 0; j < csvHeaders.length; j++) {
      var h = (csvHeaders[j] || '').trim().toLowerCase();
      if (h.indexOf(target) !== -1) return String(j);
    }
    return '';
  }

  function amountColumnIndex() {
    for (var i = 0; i < csvHeaders.length; i++) {
      if ((csvHeaders[i] || '').trim().indexOf('Amount') === 0) return String(i);
    }
    return '';
  }

  function applyPreset(preset) {
    if (!csvHeaders.length) return;
    fillColumnSelect($('mapDate'), csvHeaders, '');
    fillColumnSelect($('mapPayee'), csvHeaders, '');
    fillColumnSelect($('mapMemo'), csvHeaders, '');
    fillColumnSelect($('mapAmount'), csvHeaders, '');
    fillColumnSelect($('mapOutflow'), csvHeaders, '');
    fillColumnSelect($('mapInflow'), csvHeaders, '');

    if (preset === 'fidelity') {
      $('mapDate').value = headerIndexByName('Run Date');
      $('mapPayee').value = headerIndexByName('Action');
      $('mapMemo').value = headerIndexByName('Description');
      $('mapAmount').value = amountColumnIndex();
      if ($('importAmountMode')) $('importAmountMode').value = 'signed';
      if ($('importStripCash')) $('importStripCash').checked = true;
    } else {
      var outIdx = headerIndexByName('outflow');
      var inIdx = headerIndexByName('inflow');
      var amtIdx = headerIndexByName('amount');
      $('mapDate').value = headerIndexByName('date');
      $('mapPayee').value = headerIndexByName('payee');
      $('mapMemo').value = headerIndexByName('memo');
      if (outIdx && inIdx) {
        $('mapOutflow').value = outIdx;
        $('mapInflow').value = inIdx;
        if ($('importAmountMode')) $('importAmountMode').value = 'split';
      } else {
        $('mapAmount').value = amtIdx;
        if ($('importAmountMode')) $('importAmountMode').value = 'signed';
      }
      if ($('importStripCash')) $('importStripCash').checked = false;
    }
    rebuildPreview();
  }

  function parseDate(value) {
    value = (value || '').trim();
    if (!value) return null;
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    var m = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
    if (m) {
      var month = parseInt(m[1], 10);
      var day = parseInt(m[2], 10);
      var year = parseInt(m[3], 10);
      if (year < 100) year += year >= 70 ? 1900 : 2000;
      if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
        return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
      }
    }
    return null;
  }

  function parseAmount(value) {
    value = (value || '').trim().replace(/,/g, '').replace(/\$/g, '');
    if (!value) return null;
    var n = parseFloat(value);
    return isFinite(n) ? Math.round(n * 100) / 100 : null;
  }

  function cell(row, idx) {
    if (idx === '' || idx === null || idx === undefined) return '';
    var i = parseInt(idx, 10);
    return i >= 0 && i < row.length ? (row[i] || '').trim() : '';
  }

  function resolveAmount(row, mode, amountIdx, outIdx, inIdx) {
    if (mode === 'split') {
      var outVal = parseAmount(cell(row, outIdx));
      var inVal = parseAmount(cell(row, inIdx));
      if (inVal && inVal > 0) return inVal;
      if (outVal && outVal > 0) return -outVal;
      if (outVal && outVal < 0) return outVal;
      if (inVal && inVal < 0) return inVal;
      return null;
    }
    return parseAmount(cell(row, amountIdx));
  }

  function buildTransactions() {
    var dateIdx = $('mapDate') ? $('mapDate').value : '';
    var payeeIdx = $('mapPayee') ? $('mapPayee').value : '';
    var memoIdx = $('mapMemo') ? $('mapMemo').value : '';
    var amountIdx = $('mapAmount') ? $('mapAmount').value : '';
    var outIdx = $('mapOutflow') ? $('mapOutflow').value : '';
    var inIdx = $('mapInflow') ? $('mapInflow').value : '';
    var mode = $('importAmountMode') ? $('importAmountMode').value : 'signed';
    var stripCash = $('importStripCash') && $('importStripCash').checked;
    var cashSuffix = /\s*\(Cash\)\s*$/i;
    var fidelityMode = $('importPreset') && $('importPreset').value === 'fidelity';
    var rows = [];
    var dataStart = headerRowIndex + 1;

    for (var r = dataStart; r < csvRawRows.length; r++) {
      var row = csvRawRows[r];
      if (!row || !row.some(function (c) { return (c || '').trim(); })) continue;

      var txnDate = parseDate(cell(row, dateIdx));
      if (!txnDate) {
        if (fidelityMode && rows.length > 0) break;
        continue;
      }

      var amount = resolveAmount(row, mode, amountIdx, outIdx, inIdx);
      if (amount === null || amount === 0) continue;

      var payee = cell(row, payeeIdx);
      if (!payee) continue;
      if (stripCash) payee = payee.replace(cashSuffix, '').trim();
      if (!payee) continue;

      var memo = cell(row, memoIdx);
      if (memo.toLowerCase() === 'no description') memo = '';

      rows.push({
        txn_date: txnDate,
        payee: payee,
        memo: memo,
        amount: amount,
      });
    }
    return rows;
  }

  function rebuildPreview() {
    parsedRows = buildTransactions();
    var tbody = document.querySelector('#importPreviewTable tbody');
    var summary = $('importPreviewSummary');
    var btn = $('importSubmitBtn');
    if (!tbody) return;

    tbody.innerHTML = '';
    var show = parsedRows.slice(0, 25);
    show.forEach(function (t) {
      var tr = document.createElement('tr');
      var outflow = t.amount < 0 ? money(Math.abs(t.amount)) : '';
      var inflow = t.amount > 0 ? money(t.amount) : '';
      tr.innerHTML =
        '<td>' + escapeHtml(t.txn_date) + '</td>' +
        '<td>' + escapeHtml(t.payee) + '</td>' +
        '<td>' + escapeHtml(t.memo) + '</td>' +
        '<td class="num neg">' + outflow + '</td>' +
        '<td class="num pos">' + inflow + '</td>';
      tbody.appendChild(tr);
    });

    if (summary) {
      var extra = parsedRows.length > 25 ? ' (showing first 25)' : '';
      summary.textContent = parsedRows.length
        ? parsedRows.length + ' transaction' + (parsedRows.length === 1 ? '' : 's') + ' ready to import' + extra + '.'
        : 'No valid rows found with current column mapping.';
    }
    if (btn) btn.disabled = parsedRows.length === 0;
    setImportStatus('');
  }

  function showMappingUI(show) {
    ['importMapping', 'importDefaults', 'importPreview'].forEach(function (id) {
      var el = $(id);
      if (el) el.style.display = show ? 'block' : 'none';
    });
  }

  function fillImportSelects(accounts, categories) {
    var acc = $('importAccount');
    var cat = $('importCategory');
    if (acc) {
      acc.innerHTML = '';
      (accounts || []).forEach(function (a) {
        var opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = a.name;
        acc.appendChild(opt);
      });
    }
    if (cat) {
      cat.innerHTML = '';
      (categories || []).forEach(function (c) {
        var opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        cat.appendChild(opt);
      });
    }
  }

  function handleFile(file) {
    if (!isPremium || !file) return;
    setImportStatus('Reading file…');
    var reader = new FileReader();
    reader.onload = function () {
      csvRawRows = parseCsv(String(reader.result || ''));
      var fidelityIdx = findFidelityHeader(csvRawRows);
      if (fidelityIdx >= 0) {
        headerRowIndex = fidelityIdx;
        if ($('importPreset')) $('importPreset').value = 'fidelity';
      } else {
        headerRowIndex = findGenericHeader(csvRawRows);
        if ($('importPreset')) $('importPreset').value = 'generic';
      }
      csvHeaders = (csvRawRows[headerRowIndex] || []).map(function (h) {
        return (h || '').trim();
      });
      applyPreset($('importPreset') ? $('importPreset').value : 'generic');
      showMappingUI(true);
      setImportStatus('');
    };
    reader.onerror = function () {
      setImportStatus('Could not read file.', true);
    };
    reader.readAsText(file);
  }

  function submitImport() {
    if (!isPremium || !parsedRows.length) return;
    var accountId = parseInt($('importAccount').value, 10);
    var categoryId = parseInt($('importCategory').value, 10);
    if (!accountId || !categoryId) {
      setImportStatus('Choose an account and category.', true);
      return;
    }
    if (parsedRows.length > 500) {
      setImportStatus('Import limited to 500 rows. Split the file or remove extra rows.', true);
      return;
    }

    setImportStatus('Importing…');
    $('importSubmitBtn').disabled = true;

    fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'import_transactions',
        account_id: accountId,
        category_id: categoryId,
        transactions: parsedRows,
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Import failed');
        setImportStatus('Imported ' + data.imported + ' transaction' + (data.imported === 1 ? '' : 's') +
          (data.skipped ? ' (' + data.skipped + ' skipped).' : '.'));
        $('importFile').value = '';
        parsedRows = [];
        showMappingUI(false);
        if (window.budgetReloadMonth) window.budgetReloadMonth();
      })
      .catch(function (err) {
        setImportStatus(err.message, true);
      })
      .finally(function () {
        if ($('importSubmitBtn')) $('importSubmitBtn').disabled = parsedRows.length === 0;
      });
  }

  function bindImport() {
    if (!isPremium) return;

    var fileInput = $('importFile');
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) handleFile(fileInput.files[0]);
      });
    }

    ['mapDate', 'mapPayee', 'mapMemo', 'mapAmount', 'mapOutflow', 'mapInflow', 'importAmountMode'].forEach(function (id) {
      var el = $(id);
      if (el) el.addEventListener('change', rebuildPreview);
    });

    var stripCash = $('importStripCash');
    if (stripCash) stripCash.addEventListener('change', rebuildPreview);

    var preset = $('importPreset');
    if (preset) {
      preset.addEventListener('change', function () {
        applyPreset(preset.value);
      });
    }

    var form = $('importForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitImport();
      });
    }

    document.addEventListener('budget:loaded', function (e) {
      var detail = e.detail || {};
      fillImportSelects(detail.accounts, detail.categories);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindImport);
  } else {
    bindImport();
  }
})();
