/**
 * Premium AI Explain modal with follow-up questions.
 * Include before calculator.js on pages that call showExplainModal().
 *
 *   showExplainModal(explanation, { calculatorType: 'ss-gap', resultsSummary: summary });
 */
(function (global) {
  'use strict';

  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s == null ? '' : String(s);
    return div.innerHTML;
  }

  function apiUrl() {
    return (global.location.origin || '') + '/api/explain_results.php';
  }

  var state = {
    overlay: null,
    calculatorType: 'calculator',
    resultsSummary: '',
    conversation: []
  };

  function getThreadEl() {
    return state.overlay ? state.overlay.querySelector('#explainModalThread') : null;
  }

  function appendThreadMessage(role, text) {
    var thread = getThreadEl();
    if (!thread) return;
    var block = document.createElement('div');
    block.style.cssText = 'margin-top:14px;padding-top:14px;border-top:1px solid #e5e7eb;';
    var label = role === 'user' ? 'You asked' : 'AI';
    var labelColor = role === 'user' ? '#1d4ed8' : '#0f766e';
    block.innerHTML =
      '<div style="font-size:12px;font-weight:700;color:' + labelColor + ';margin-bottom:6px;">' + label + '</div>' +
      '<p style="margin:0;color:#374151;line-height:1.7;white-space:pre-wrap;">' + escapeHtml(text) + '</p>';
    thread.appendChild(block);
    block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function setFollowUpBusy(busy) {
    if (!state.overlay) return;
    var input = state.overlay.querySelector('#explainFollowUpInput');
    var btn = state.overlay.querySelector('#explainFollowUpBtn');
    if (input) input.disabled = busy;
    if (btn) {
      btn.disabled = busy;
      btn.textContent = busy ? 'Sending…' : 'Ask';
    }
  }

  function sendFollowUp() {
    if (!state.overlay) return;
    var input = state.overlay.querySelector('#explainFollowUpInput');
    if (!input) return;
    var question = (input.value || '').trim();
    if (!question) return;
    if (!state.resultsSummary) {
      alert('Follow-up is unavailable because the original results context was not saved. Close and click Explain my results again.');
      return;
    }

    appendThreadMessage('user', question);
    input.value = '';
    setFollowUpBusy(true);

    fetch(apiUrl(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        calculator_type: state.calculatorType,
        results_summary: state.resultsSummary,
        follow_up_question: question,
        conversation: state.conversation
      })
    })
      .then(function (res) { return res.text(); })
      .then(function (text) {
        setFollowUpBusy(false);
        var data;
        try { data = JSON.parse(text); } catch (e) {
          throw new Error('Server returned an unexpected response. Try logging out and back in.');
        }
        if (data.error) throw new Error(data.error);
        var answer = data.explanation || '';
        state.conversation.push({ role: 'user', content: question });
        state.conversation.push({ role: 'assistant', content: answer });
        appendThreadMessage('assistant', answer);
      })
      .catch(function (err) {
        setFollowUpBusy(false);
        alert('Follow-up: ' + err.message);
      });
  }

  function bindFollowUpHandlers() {
    if (!state.overlay) return;
    var btn = state.overlay.querySelector('#explainFollowUpBtn');
    var input = state.overlay.querySelector('#explainFollowUpInput');
    if (btn) btn.addEventListener('click', sendFollowUp);
    if (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendFollowUp();
        }
      });
    }
    var closeBtn = state.overlay.querySelector('#explainModalCloseBtn');
    if (closeBtn) closeBtn.addEventListener('click', function () { state.overlay.remove(); });
  }

  function open(explanation, options) {
    options = options || {};
    state.calculatorType = options.calculatorType || options.calculator_type || 'calculator';
    state.resultsSummary = options.resultsSummary || options.results_summary || '';
    state.conversation = [{ role: 'assistant', content: explanation }];

    if (state.overlay) state.overlay.remove();

    var overlay = document.createElement('div');
    overlay.id = 'explainResultsModalOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
    overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.remove(); });

    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:560px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
    box.addEventListener('click', function (e) { e.stopPropagation(); });

    box.innerHTML =
      '<div style="padding:24px 24px 16px;overflow-y:auto;flex:1;">' +
        '<h2 style="margin:0 0 16px 0;font-size:1.25rem;color:#1f2937;">🤖 AI Explanation</h2>' +
        '<div id="explainModalMain" style="color:#374151;line-height:1.7;white-space:pre-wrap;">' + escapeHtml(explanation) + '</div>' +
        '<div id="explainModalThread"></div>' +
      '</div>' +
      '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
        '<label for="explainFollowUpInput" style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Ask a follow-up question about these results</label>' +
        '<textarea id="explainFollowUpInput" rows="2" placeholder="For example: What would change if I lowered my withdrawal rate?" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;resize:vertical;min-height:52px;"></textarea>' +
        '<div style="display:flex;gap:10px;align-items:center;margin-top:10px;flex-wrap:wrap;">' +
          '<button type="button" id="explainFollowUpBtn" style="padding:10px 20px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Ask</button>' +
          '<button type="button" id="explainModalCloseBtn" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;cursor:pointer;font-weight:600;">Close</button>' +
        '</div>' +
        '<p style="margin:12px 0 0 0;font-size:12px;color:#6b7280;">AI-generated for educational purposes only. Not financial or legal advice.</p>' +
      '</div>';

    overlay.appendChild(box);
    global.document.body.appendChild(overlay);
    state.overlay = overlay;
    bindFollowUpHandlers();
  }

  global.ExplainResultsModal = { open: open };
  global.showExplainModal = function (explanation, options) {
    open(explanation, options);
  };
})(window);
