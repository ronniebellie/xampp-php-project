(function () {
  'use strict';

  var STORAGE_KEYS = {
    ynabToken: 'aiBudgetAuditor_ynabToken',
    openaiKey: 'aiBudgetAuditor_openaiKey',
    budgetId: 'aiBudgetAuditor_budgetId'
  };

  var isPremium = typeof isPremiumUser !== 'undefined' && isPremiumUser;

  var state = {
    modalOverlay: null,
    budgetSummary: '',
    budgetName: '',
    openaiKey: '',
    conversation: [],
    snapshotReady: false
  };

  var els = {};

  function $(id) {
    return document.getElementById(id);
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
  }

  function formatMoney(milliunits) {
    var dollars = Number(milliunits || 0) / 1000;
    return dollars.toLocaleString('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function currentMonthLabel() {
    var now = new Date();
    return now.toLocaleString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' });
  }

  function setStatus(message, isError) {
    els.statusMessage.textContent = message || '';
    els.statusMessage.classList.toggle('error', !!isError);
  }

  function loadConfig() {
    els.ynabToken.value = localStorage.getItem(STORAGE_KEYS.ynabToken) || '';
    if (els.openaiKey) {
      els.openaiKey.value = localStorage.getItem(STORAGE_KEYS.openaiKey) || '';
    }
    els.budgetId.value = localStorage.getItem(STORAGE_KEYS.budgetId) || 'last-used';
  }

  function saveConfig() {
    localStorage.setItem(STORAGE_KEYS.ynabToken, els.ynabToken.value.trim());
    if (els.openaiKey) {
      localStorage.setItem(STORAGE_KEYS.openaiKey, els.openaiKey.value.trim());
    }
    localStorage.setItem(STORAGE_KEYS.budgetId, (els.budgetId.value.trim() || 'last-used'));
  }

  function clearConfig() {
    Object.keys(STORAGE_KEYS).forEach(function (key) {
      localStorage.removeItem(STORAGE_KEYS[key]);
    });
    els.ynabToken.value = '';
    if (els.openaiKey) els.openaiKey.value = '';
    els.budgetId.value = 'last-used';
    resetSnapshot();
    setStatus('Saved keys cleared from this browser.');
  }

  function resetSnapshot() {
    state.budgetSummary = '';
    state.budgetName = '';
    state.snapshotReady = false;
    els.previewSection.style.display = 'none';
    els.categoryTableBody.innerHTML = '';
    if (els.snapshotSummary) els.snapshotSummary.innerHTML = '';
    if (els.premiumUpsellBanner) els.premiumUpsellBanner.style.display = 'none';
    if (els.premiumAiRow) els.premiumAiRow.style.display = 'none';
    if (els.runAiBtn) els.runAiBtn.disabled = true;
  }

  function setSnapshotBusy(busy) {
    els.runSnapshotBtn.disabled = busy;
    els.runSnapshotBtn.textContent = busy ? 'Loading…' : 'Load Category Snapshot';
  }

  function setAiBusy(busy) {
    var label = busy ? 'Analyzing…' : '🤖 Get AI Analysis';
    if (els.runAiBtn) {
      els.runAiBtn.disabled = busy || !state.snapshotReady;
      els.runAiBtn.textContent = busy ? 'Analyzing…' : 'Get AI Analysis';
    }
    if (els.runAiBtnInline) {
      els.runAiBtnInline.disabled = busy;
      els.runAiBtnInline.textContent = busy ? 'Analyzing…' : label;
    }
  }

  async function fetchJson(url, options) {
    var response = await fetch(url, options);
    var text = await response.text();
    var data;
    try {
      data = text ? JSON.parse(text) : {};
    } catch (err) {
      throw new Error('Unexpected response from ' + url);
    }
    if (!response.ok) {
      var detail = typeof data.error === 'string'
        ? data.error
        : (data.error && data.error.detail) || (data.error && data.error.message) || data.message;
      throw new Error(detail || ('Request failed (' + response.status + ')'));
    }
    return data;
  }

  async function fetchBudgetMeta(token, budgetId) {
    var url = 'https://api.youneedabudget.com/v1/budgets/' + encodeURIComponent(budgetId);
    var json = await fetchJson(url, {
      headers: { Authorization: 'Bearer ' + token }
    });
    var budget = json.data && json.data.budget;
    return {
      id: budget && budget.id,
      name: (budget && budget.name) || budgetId
    };
  }

  async function fetchYnabCategories(token, budgetId) {
    var url = 'https://api.youneedabudget.com/v1/budgets/' + encodeURIComponent(budgetId) + '/categories';
    var json = await fetchJson(url, {
      headers: { Authorization: 'Bearer ' + token }
    });
    return (json.data && json.data.category_groups) || [];
  }

  function isCreditCardCategory(groupName) {
    return /credit card payments?/i.test(groupName || '');
  }

  function flattenCategories(categoryGroups) {
    var rows = [];
    categoryGroups.forEach(function (group) {
      if (!group || group.deleted || group.hidden) return;
      (group.categories || []).forEach(function (cat) {
        if (!cat || cat.deleted || cat.hidden) return;
        rows.push({
          groupName: group.name,
          name: cat.name,
          budgeted: cat.budgeted || 0,
          activity: cat.activity || 0,
          balance: cat.balance || 0,
          isCreditCard: isCreditCardCategory(group.name)
        });
      });
    });
    return rows;
  }

  function formatCategorySummary(budgetName, rows) {
    var lines = [
      'YNAB Budget Audit — ' + currentMonthLabel(),
      'Budget: ' + budgetName,
      '',
      'YNAB interpretation rules for this export:',
      '- Available is the ONLY overspending signal. Available < $0.00 = overspent. Negative Activity alone is NOT overspending.',
      '- Credit Card Payments categories: negative Activity is normal card usage. If Available >= $0, the card workflow is healthy — do not flag as overspent.',
      '- Regular categories: overspending means Available went negative (e.g. Legal & Tax Prep with negative Available).',
      '',
      'All amounts in USD. Activity = this month\'s spending/inflows. Available = budgeted + rollovers + activity.',
      ''
    ];

    var currentGroup = null;
    rows.forEach(function (row) {
      if (row.groupName !== currentGroup) {
        currentGroup = row.groupName;
        lines.push('[' + currentGroup + ']');
      }
      var overspent = row.balance < 0;
      var suffix = '';
      if (overspent) {
        suffix = ' (OVERSPENT — Available < $0)';
      } else if (row.isCreditCard && row.activity < 0) {
        suffix = ' (Credit card: negative Activity is normal; Available covers balance — not overspent)';
      }
      lines.push(
        '- ' + row.name +
        ': Budgeted ' + formatMoney(row.budgeted) +
        ' | Activity ' + formatMoney(row.activity) +
        ' | Available ' + formatMoney(row.balance) +
        suffix
      );
    });

    var overspentRows = rows.filter(function (r) { return r.balance < 0; });
    var totalBudgeted = rows.reduce(function (sum, r) { return sum + r.budgeted; }, 0);
    var totalActivity = rows.reduce(function (sum, r) { return sum + r.activity; }, 0);

    lines.push('');
    lines.push('Summary totals');
    lines.push('- Total budgeted: ' + formatMoney(totalBudgeted));
    lines.push('- Total activity: ' + formatMoney(totalActivity));
    lines.push('- Categories overspent (Available < $0 only): ' + overspentRows.length);
    if (overspentRows.length) {
      lines.push('- Overspent category names: ' + overspentRows.map(function (r) { return r.name; }).join(', '));
    }

    return lines.join('\n');
  }

  function renderSnapshotSummary(rows, budgetName) {
    if (!els.snapshotSummary) return;

    var overspentRows = rows.filter(function (r) { return r.balance < 0; });
    var totalBudgeted = rows.reduce(function (sum, r) { return sum + r.budgeted; }, 0);
    var totalActivity = rows.reduce(function (sum, r) { return sum + r.activity; }, 0);
    var overspentClass = overspentRows.length ? 'alert' : 'ok';
    var overspentText = overspentRows.length
      ? overspentRows.length + (overspentRows.length === 1 ? ' category' : ' categories')
      : 'None';

    els.snapshotSummary.innerHTML =
      '<div class="snapshot-card">' +
        '<div class="snapshot-card-label">Budget</div>' +
        '<div class="snapshot-card-value" style="font-size:18px;">' + escapeHtml(budgetName) + '</div>' +
      '</div>' +
      '<div class="snapshot-card">' +
        '<div class="snapshot-card-label">Total budgeted</div>' +
        '<div class="snapshot-card-value">' + escapeHtml(formatMoney(totalBudgeted)) + '</div>' +
      '</div>' +
      '<div class="snapshot-card">' +
        '<div class="snapshot-card-label">Total activity</div>' +
        '<div class="snapshot-card-value">' + escapeHtml(formatMoney(totalActivity)) + '</div>' +
      '</div>' +
      '<div class="snapshot-card ' + overspentClass + '">' +
        '<div class="snapshot-card-label">Overspent (Available &lt; $0)</div>' +
        '<div class="snapshot-card-value">' + escapeHtml(overspentText) + '</div>' +
      '</div>';

    if (els.previewTitle) {
      els.previewTitle.textContent = 'Category snapshot — ' + budgetName + ' (' + currentMonthLabel() + ')';
    }
  }

  function renderPreview(rows) {
    els.categoryTableBody.innerHTML = rows.map(function (row) {
      var overspent = row.balance < 0;
      return (
        '<tr' + (overspent ? ' style="background:#fef2f2;"' : '') + '>' +
          '<td>' + escapeHtml(row.groupName) + '</td>' +
          '<td>' + escapeHtml(row.name) + '</td>' +
          '<td>' + escapeHtml(formatMoney(row.budgeted)) + '</td>' +
          '<td>' + escapeHtml(formatMoney(row.activity)) + '</td>' +
          '<td class="' + (overspent ? 'overspend' : '') + '">' + escapeHtml(formatMoney(row.balance)) + '</td>' +
        '</tr>'
      );
    }).join('');
    els.previewSection.style.display = rows.length ? 'block' : 'none';
  }

  function showPostSnapshotUi() {
    if (els.premiumUpsellBanner) {
      els.premiumUpsellBanner.style.display = 'block';
    }
    if (els.premiumAiRow) {
      els.premiumAiRow.style.display = 'flex';
    }
    if (els.runAiBtn) {
      els.runAiBtn.disabled = false;
    }
  }

  function proxyUrl() {
    return (window.location.origin || '') + '/api/ynab_proxy.php';
  }

  async function fetchOpenAiAnalysis(summary, apiKey, options) {
    options = options || {};
    var body = { budget_summary: summary };
    if (options.followUpQuestion) {
      body.follow_up_question = options.followUpQuestion;
      body.conversation = options.conversation || [];
    }

    var json = await fetchJson(proxyUrl(), {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + apiKey
      },
      body: JSON.stringify(body)
    });

    if (!json.analysis) {
      throw new Error('OpenAI returned an empty analysis.');
    }
    return json.analysis.trim();
  }

  function renderMarkdownish(text) {
    var html = escapeHtml(text);
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
    if (html.indexOf('<li>') !== -1) {
      html = html.replace(/(<li>[\s\S]*?<\/li>)/g, function (block) {
        return '<ul style="margin:12px 0;padding-left:22px;line-height:1.65;">' + block + '</ul>';
      });
    }
    html = html.replace(/\n\n/g, '</p><p style="margin:0 0 14px 0;">');
    return '<p style="margin:0 0 14px 0;">' + html + '</p>';
  }

  function closeModal() {
    document.removeEventListener('keydown', onModalKeydown);
    if (state.modalOverlay) {
      state.modalOverlay.remove();
      state.modalOverlay = null;
    }
  }

  function getModalThreadEl() {
    return state.modalOverlay ? state.modalOverlay.querySelector('#aiBudgetAuditThread') : null;
  }

  function appendThreadMessage(role, text) {
    var thread = getModalThreadEl();
    if (!thread) return;
    var block = document.createElement('div');
    block.style.cssText = 'margin-top:14px;padding-top:14px;border-top:1px solid #e5e7eb;';
    var label = role === 'user' ? 'You asked' : 'AI';
    var labelColor = role === 'user' ? '#1d4ed8' : '#0f766e';
    block.innerHTML =
      '<div style="font-size:12px;font-weight:700;color:' + labelColor + ';margin-bottom:6px;">' + label + '</div>' +
      '<div style="margin:0;color:#374151;line-height:1.7;font-size:15px;">' + renderMarkdownish(text) + '</div>';
    thread.appendChild(block);
    block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function setFollowUpBusy(busy) {
    if (!state.modalOverlay) return;
    var input = state.modalOverlay.querySelector('#aiBudgetFollowUpInput');
    var btn = state.modalOverlay.querySelector('#aiBudgetFollowUpBtn');
    if (input) input.disabled = busy;
    if (btn) {
      btn.disabled = busy;
      btn.textContent = busy ? 'Sending…' : 'Ask';
    }
  }

  async function sendFollowUp() {
    if (!state.modalOverlay) return;
    var input = state.modalOverlay.querySelector('#aiBudgetFollowUpInput');
    if (!input) return;
    var question = (input.value || '').trim();
    if (!question) return;
    if (!state.budgetSummary) {
      alert('Follow-up is unavailable because the budget context was not saved. Close and load the snapshot again.');
      return;
    }
    if (!state.openaiKey) {
      alert('Follow-up is unavailable because your OpenAI key is missing.');
      return;
    }

    appendThreadMessage('user', question);
    input.value = '';
    setFollowUpBusy(true);

    try {
      var answer = await fetchOpenAiAnalysis(state.budgetSummary, state.openaiKey, {
        followUpQuestion: question,
        conversation: state.conversation
      });
      state.conversation.push({ role: 'user', content: question });
      state.conversation.push({ role: 'assistant', content: answer });
      appendThreadMessage('assistant', answer);
    } catch (err) {
      alert('Follow-up: ' + (err && err.message ? err.message : String(err)));
    } finally {
      setFollowUpBusy(false);
    }
  }

  function bindFollowUpHandlers() {
    if (!state.modalOverlay) return;
    var btn = state.modalOverlay.querySelector('#aiBudgetFollowUpBtn');
    var input = state.modalOverlay.querySelector('#aiBudgetFollowUpInput');
    if (btn) btn.addEventListener('click', sendFollowUp);
    if (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendFollowUp();
        }
      });
    }
    state.modalOverlay.querySelector('#aiBudgetAuditCloseBtn').addEventListener('click', closeModal);
  }

  function showAuditModal(analysisText) {
    closeModal();

    state.conversation = [{ role: 'assistant', content: analysisText }];

    var overlay = document.createElement('div');
    overlay.id = 'aiBudgetAuditModalOverlay';
    overlay.style.cssText =
      'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });

    var box = document.createElement('div');
    box.style.cssText =
      'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:620px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
    box.addEventListener('click', function (e) { e.stopPropagation(); });

    box.innerHTML =
      '<div style="padding:24px 24px 16px;overflow-y:auto;flex:1;">' +
        '<h2 style="margin:0 0 8px 0;font-size:1.25rem;color:#1f2937;">📊 AI Budget Audit</h2>' +
        '<p style="margin:0 0 18px 0;font-size:13px;color:#6b7280;">' + escapeHtml(state.budgetName) + ' · ' + escapeHtml(currentMonthLabel()) + '</p>' +
        '<div id="aiBudgetAuditBody" style="color:#374151;line-height:1.7;font-size:15px;">' + renderMarkdownish(analysisText) + '</div>' +
        '<div id="aiBudgetAuditThread"></div>' +
      '</div>' +
      '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
        '<label for="aiBudgetFollowUpInput" style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Ask a follow-up question about this budget</label>' +
        '<textarea id="aiBudgetFollowUpInput" rows="2" placeholder="For example: Should I move money from Next car purchase to cover Legal &amp; Tax Prep?" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;resize:vertical;min-height:52px;"></textarea>' +
        '<div style="display:flex;gap:10px;align-items:center;margin-top:10px;flex-wrap:wrap;">' +
          '<button type="button" id="aiBudgetFollowUpBtn" style="padding:10px 20px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Ask</button>' +
          '<button type="button" id="aiBudgetAuditCloseBtn" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;cursor:pointer;font-weight:600;">Close</button>' +
        '</div>' +
        '<p style="margin:12px 0 0 0;font-size:12px;color:#6b7280;">AI-generated for personal review only. Not financial advice.</p>' +
      '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    state.modalOverlay = overlay;

    bindFollowUpHandlers();
    document.addEventListener('keydown', onModalKeydown);
  }

  function onModalKeydown(e) {
    if (e.key === 'Escape') closeModal();
  }

  async function loadSnapshot() {
    saveConfig();

    var token = els.ynabToken.value.trim();
    var budgetId = els.budgetId.value.trim() || 'last-used';

    if (!token) {
      setStatus('Please paste your YNAB Personal Access Token.', true);
      els.ynabToken.focus();
      return;
    }

    setSnapshotBusy(true);
    setStatus('Fetching categories from YNAB…');

    try {
      var budgetMeta = await fetchBudgetMeta(token, budgetId);
      var categoryGroups = await fetchYnabCategories(token, budgetId);
      var rows = flattenCategories(categoryGroups);

      if (!rows.length) {
        throw new Error('No visible categories returned for this budget.');
      }

      state.budgetName = budgetMeta.name;
      state.budgetSummary = formatCategorySummary(budgetMeta.name, rows);
      state.snapshotReady = true;

      renderSnapshotSummary(rows, budgetMeta.name);
      renderPreview(rows);
      showPostSnapshotUi();

      setStatus('Category snapshot loaded.' + (isPremium ? ' Click Get AI Analysis when ready.' : ''));
    } catch (err) {
      setStatus(err && err.message ? err.message : String(err), true);
    } finally {
      setSnapshotBusy(false);
    }
  }

  async function runAiAnalysis() {
    if (!isPremium) return;

    saveConfig();

    if (!state.snapshotReady || !state.budgetSummary) {
      setStatus('Load a category snapshot first.', true);
      return;
    }

    var openaiKey = els.openaiKey ? els.openaiKey.value.trim() : '';
    if (!openaiKey) {
      setStatus('Please paste your OpenAI API Key for AI analysis.', true);
      if (els.openaiKey) els.openaiKey.focus();
      return;
    }

    state.openaiKey = openaiKey;
    setAiBusy(true);
    setStatus('Sending summary to GPT-4o for analysis…');

    try {
      var analysis = await fetchOpenAiAnalysis(state.budgetSummary, openaiKey);
      setStatus('AI analysis complete.');
      showAuditModal(analysis);
    } catch (err) {
      setStatus(err && err.message ? err.message : String(err), true);
    } finally {
      setAiBusy(false);
    }
  }

  function bindEvents() {
    ['ynabToken', 'budgetId'].forEach(function (id) {
      var el = $(id);
      if (el) {
        el.addEventListener('change', saveConfig);
        el.addEventListener('blur', saveConfig);
      }
    });
    if (els.openaiKey) {
      els.openaiKey.addEventListener('change', saveConfig);
      els.openaiKey.addEventListener('blur', saveConfig);
    }
    els.runSnapshotBtn.addEventListener('click', loadSnapshot);
    els.clearKeysBtn.addEventListener('click', clearConfig);
    if (els.runAiBtn) els.runAiBtn.addEventListener('click', runAiAnalysis);
    if (els.runAiBtnInline) els.runAiBtnInline.addEventListener('click', runAiAnalysis);
  }

  function init() {
    els.ynabToken = $('ynabToken');
    els.openaiKey = $('openaiKey');
    els.budgetId = $('budgetId');
    els.runSnapshotBtn = $('runSnapshotBtn');
    els.runAiBtn = $('runAiBtn');
    els.runAiBtnInline = $('runAiBtnInline');
    els.clearKeysBtn = $('clearKeysBtn');
    els.statusMessage = $('statusMessage');
    els.previewSection = $('previewSection');
    els.previewTitle = $('previewTitle');
    els.categoryTableBody = $('categoryTableBody');
    els.snapshotSummary = $('snapshotSummary');
    els.premiumUpsellBanner = $('premiumUpsellBanner');
    els.premiumAiRow = $('premiumAiRow');

    loadConfig();
    bindEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
