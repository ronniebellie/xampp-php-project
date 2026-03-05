document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('nestEggForm');
  const resultsEl = document.getElementById('results');
  const directWrap = document.getElementById('directIncomeWrap');
  const estimateWrap = document.getElementById('estimateWrap');
  const resultTarget = document.getElementById('resultTarget');
  const resultExplanation = document.getElementById('resultExplanation');
  const currentSavingsCard = document.getElementById('currentSavingsCard');
  const resultCurrentPct = document.getElementById('resultCurrentPct');
  const resultCurrentDetail = document.getElementById('resultCurrentDetail');

  function fmtCurrency(value) {
    if (!isFinite(value)) return '—';
    return value.toLocaleString(undefined, {
      style: 'currency',
      currency: 'USD',
      maximumFractionDigits: 0
    });
  }

  function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
  }

  // Toggle direct vs estimate inputs
  const incomeMethodRadios = form.querySelectorAll('input[name="incomeMethod"]');
  incomeMethodRadios.forEach(radio => {
    radio.addEventListener('change', () => {
      const useEstimate = form.querySelector('input[name="incomeMethod"]:checked').value === 'estimate';
      directWrap.style.display = useEstimate ? 'none' : 'grid';
      estimateWrap.style.display = useEstimate ? 'grid' : 'none';
    });
  });
  // Initial state
  estimateWrap.style.display = 'none';

  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const method = form.querySelector('input[name="incomeMethod"]:checked').value;
    let desiredAnnual = 0;

    if (method === 'direct') {
      desiredAnnual = Number(document.getElementById('desiredAnnualIncome').value || 0);
    } else {
      const monthly = Number(document.getElementById('currentMonthlySpending').value || 0);
      const pct = Number(document.getElementById('retirementSpendingPct').value || 80) / 100;
      desiredAnnual = monthly * 12 * pct;
    }

    const guaranteedAnnual = Number(document.getElementById('guaranteedAnnualIncome').value || 0);
    let withdrawalRatePct = Number(document.getElementById('withdrawalRate').value || 4);
    const currentSavings = Number(document.getElementById('currentSavings').value || 0);

    const errors = [];
    if (desiredAnnual <= 0) errors.push('desired retirement income (or current spending to estimate it)');
    if (withdrawalRatePct <= 0) errors.push('withdrawal rate');

    if (errors.length) {
      alert('Please enter: ' + errors.join(', ') + '.');
      return;
    }

    withdrawalRatePct = clamp(withdrawalRatePct, 0.5, 15);
    const withdrawalRate = withdrawalRatePct / 100;

    let neededFromPortfolio = desiredAnnual - guaranteedAnnual;
    if (neededFromPortfolio < 0) neededFromPortfolio = 0;

    const targetNestEgg = neededFromPortfolio > 0 && withdrawalRate > 0
      ? neededFromPortfolio / withdrawalRate
      : 0;

    resultTarget.textContent = targetNestEgg > 0 ? fmtCurrency(targetNestEgg) : '—';
    if (targetNestEgg > 0) {
      resultExplanation.textContent =
        'You want ' + fmtCurrency(desiredAnnual) + '/year in retirement. ' +
        (guaranteedAnnual > 0 ? fmtCurrency(guaranteedAnnual) + ' from guaranteed income leaves ' : '') +
        fmtCurrency(neededFromPortfolio) + '/year from your portfolio. At ' + withdrawalRatePct.toFixed(2) + '%, that implies a nest egg of about ' + fmtCurrency(targetNestEgg) + '.';
    } else if (desiredAnnual > 0 && guaranteedAnnual >= desiredAnnual) {
      resultExplanation.textContent = 'Your guaranteed income (' + fmtCurrency(guaranteedAnnual) + '/year) already covers your desired retirement income. No portfolio target needed for this rule of thumb.';
    } else {
      resultExplanation.textContent = 'Adjust your income or withdrawal rate to see a target.';
    }

    if (currentSavings > 0 && targetNestEgg > 0) {
      const pct = (currentSavings / targetNestEgg) * 100;
      currentSavingsCard.style.display = 'block';
      resultCurrentPct.textContent = pct.toFixed(0) + '% of target';
      resultCurrentDetail.textContent =
        'You have ' + fmtCurrency(currentSavings) + ' saved. Your target is ' + fmtCurrency(targetNestEgg) + '.';
    } else {
      currentSavingsCard.style.display = 'none';
    }

    const summary = 'Nest Egg Target. Desired annual income ' + fmtCurrency(desiredAnnual) + '. Guaranteed income ' + fmtCurrency(guaranteedAnnual) + '. Needed from portfolio ' + fmtCurrency(neededFromPortfolio) + '/year. Withdrawal rate ' + withdrawalRatePct.toFixed(2) + '%. Target nest egg: ' + fmtCurrency(targetNestEgg) + '.' + (currentSavings > 0 && targetNestEgg > 0 ? ' Current savings ' + fmtCurrency(currentSavings) + ' (' + ((currentSavings / targetNestEgg) * 100).toFixed(0) + '% of target).' : '');
    window.lastNestEggResult = { summary };

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  const explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

function escapeHtml(s) {
  const div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function explainResults() {
  const r = window.lastNestEggResult;
  if (!r || !r.summary) {
    alert('Please run the calculation first to see results.');
    return;
  }
  const btn = document.getElementById('explainResultsBtnInResults');
  const origText = btn ? btn.textContent : '';
  if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }
  const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
  fetch(explainUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ calculator_type: 'nest-egg-target', results_summary: r.summary })
  })
  .then(res => res.text())
  .then(text => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Server returned an unexpected response. Try logging out and back in.'); }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation);
  })
  .catch(err => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

function showExplainModal(explanation) {
  let overlay = document.getElementById('explainResultsModalOverlay');
  if (overlay) overlay.remove();
  overlay = document.createElement('div');
  overlay.id = 'explainResultsModalOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
  overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
  const box = document.createElement('div');
  box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:560px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
  box.addEventListener('click', function(e) { e.stopPropagation(); });
  box.innerHTML = '<div style="padding:24px 24px 16px;">' +
    '<h2 style="margin:0 0 16px 0;font-size:1.25rem;color:#1f2937;">🤖 AI Explanation</h2>' +
    '<div style="color:#374151;line-height:1.7;white-space:pre-wrap;overflow-y:auto;max-height:50vh;">' + escapeHtml(explanation) + '</div>' +
    '</div>' +
    '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
    '<p style="margin:0 0 12px 0;font-size:12px;color:#6b7280;">This is an AI-generated explanation for educational purposes. Not financial or legal advice.</p>' +
    '<button type="button" id="explainModalCloseBtn" style="padding:10px 24px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Close</button>' +
    '</div>';
  overlay.appendChild(box);
  document.body.appendChild(overlay);
  document.getElementById('explainModalCloseBtn').addEventListener('click', function() { overlay.remove(); });
}
