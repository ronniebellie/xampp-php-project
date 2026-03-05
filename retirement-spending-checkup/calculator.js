document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('spendingForm');
  const resultsEl = document.getElementById('results');

  const elAnnualBudget = document.getElementById('resultAnnualBudget');
  const elAnnualGuaranteed = document.getElementById('resultAnnualGuaranteed');
  const elAnnualFromPortfolio = document.getElementById('resultAnnualFromPortfolio');

  const onTrackCard = document.getElementById('onTrackCard');
  const onTrackLabel = document.getElementById('onTrackLabel');
  const onTrackStatus = document.getElementById('onTrackStatus');
  const onTrackDetail = document.getElementById('onTrackDetail');

  const explanationText = document.getElementById('explanationText');

  const retirementPctInput = document.getElementById('retirementSpendingPct');
  const alreadyRetiredCheckbox = document.getElementById('alreadyRetired');

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

  if (!form) return;

  if (alreadyRetiredCheckbox && retirementPctInput) {
    const syncRetiredState = () => {
      if (alreadyRetiredCheckbox.checked) {
        retirementPctInput.value = 100;
        retirementPctInput.disabled = true;
        retirementPctInput.style.backgroundColor = '#f9fafb';
      } else {
        retirementPctInput.disabled = false;
        retirementPctInput.style.backgroundColor = '';
        if (!retirementPctInput.value) {
          retirementPctInput.value = 80;
        }
      }
    };
    alreadyRetiredCheckbox.addEventListener('change', syncRetiredState);
    syncRetiredState();
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const monthlyNow = Number(document.getElementById('currentMonthlySpending').value || 0);
    const retirePctRaw = Number(document.getElementById('retirementSpendingPct').value || 0);
    const guaranteedMonthly = Number(document.getElementById('guaranteedMonthlyIncome').value || 0);
    const currentSavings = Number(document.getElementById('currentSavings').value || 0);
    let withdrawalRatePct = Number(document.getElementById('withdrawalRate').value || 0);

    const errors = [];
    if (monthlyNow <= 0) errors.push('Current monthly living expenses');
    if (retirePctRaw <= 0) errors.push('Retirement spending %');
    if (withdrawalRatePct <= 0) errors.push('Withdrawal rate %');

    if (errors.length) {
      alert('Please enter a positive value for: ' + errors.join(', ') + '.');
      return;
    }

    const retirePct = retirePctRaw / 100;
    withdrawalRatePct = clamp(withdrawalRatePct, 0.5, 15);
    const withdrawalRate = withdrawalRatePct / 100;

    const annualBudget = monthlyNow * 12 * retirePct;
    const annualGuaranteed = guaranteedMonthly * 12;

    let annualFromPortfolio = annualBudget - annualGuaranteed;
    if (annualFromPortfolio < 0) annualFromPortfolio = 0;

    const requiredNestEgg = annualFromPortfolio > 0 && withdrawalRate > 0
      ? annualFromPortfolio / withdrawalRate
      : 0;

    elAnnualBudget.textContent = fmtCurrency(annualBudget);
    elAnnualGuaranteed.textContent = fmtCurrency(annualGuaranteed);
    elAnnualFromPortfolio.textContent = fmtCurrency(annualFromPortfolio);

    let statusText = '';
    let detailText = '';
    let background = '#f3f4f6';
    let borderColor = '#e5e7eb';
    let labelColor = '#374151';
    let statusColor = '#111827';

    if (annualBudget === 0) {
      statusText = 'Need more info';
      detailText = 'Enter your current spending so we can estimate a retirement budget.';
    } else if (annualFromPortfolio === 0) {
      statusText = 'Strong position';
      detailText = 'Your guaranteed income already covers the target retirement budget. Your portfolio can be reserved for extras, flexibility, and legacy goals.';
      background = '#ecfdf3';
      borderColor = '#4ade80';
      labelColor = '#166534';
      statusColor = '#166534';
    } else if (requiredNestEgg === 0) {
      statusText = 'Need more info';
      detailText = 'Adjust your withdrawal rate or income assumptions to get a clearer picture.';
    } else {
      const ratio = currentSavings / requiredNestEgg;
      const shortfall = requiredNestEgg - currentSavings;
      const cushion = currentSavings - requiredNestEgg;

      if (!isFinite(ratio)) {
        statusText = 'Need more info';
        detailText = 'Check your numbers—especially withdrawal rate and required income.';
      } else if (ratio >= 1.1) {
        const cushionPct = (ratio - 1) * 100;
        statusText = 'On track (with cushion)';
        detailText =
          'Using a ' +
          withdrawalRatePct.toFixed(2) +
          '% withdrawal rate, your current savings are about ' +
          cushionPct.toFixed(0) +
          '% above this rule-of-thumb target. You may be able to support this budget, assuming markets cooperate.';
        background = '#ecfdf3';
        borderColor = '#4ade80';
        labelColor = '#166534';
        statusColor = '#166534';
      } else if (ratio >= 0.8) {
        const behindPct = (1 - ratio) * 100;
        statusText = 'Close to on track';
        detailText =
          'You are within about ' +
          behindPct.toFixed(0) +
          '% of the rule-of-thumb target nest egg (' +
          fmtCurrency(requiredNestEgg) +
          '). Small changes—saving a bit more, working slightly longer, or trimming the budget—could close the gap.';
        background = '#eff6ff';
        borderColor = '#93c5fd';
        labelColor = '#1d4ed8';
        statusColor = '#1d4ed8';
      } else {
        statusText = 'Behind this rule of thumb';
        detailText =
          'To fully support this retirement budget at a ' +
          withdrawalRatePct.toFixed(2) +
          '% withdrawal rate, the rule-of-thumb target nest egg is about ' +
          fmtCurrency(requiredNestEgg) +
          '. That is roughly ' +
          fmtCurrency(shortfall) +
          ' more than your current savings. Consider adjusting your spending, retirement age, savings rate, or withdrawal assumptions.';
        background = '#fef2f2';
        borderColor = '#fecaca';
        labelColor = '#b91c1c';
        statusColor = '#b91c1c';
      }
    }

    onTrackCard.style.background = background;
    onTrackCard.style.border = '1px solid ' + borderColor;
    onTrackLabel.style.color = labelColor;
    onTrackStatus.style.color = statusColor;

    onTrackStatus.textContent = statusText;
    onTrackDetail.textContent = detailText;

    const explanation = [];
    explanation.push(
      'This checkup uses your current monthly spending, an adjustable retirement spending percentage, and a withdrawal rate (defaulting to 4%) to estimate a target portfolio size.'
    );
    explanation.push(
      'If your guaranteed income covers most of the budget, you rely less on your portfolio. If not, the calculator estimates how large a nest egg might be needed using that withdrawal rate.'
    );
    explanation.push(
      'Being “on track” here means your current savings meet or exceed that rule-of-thumb target. Real planning should also consider taxes, longevity, investment risk, and changing spending over time.'
    );
    explanationText.textContent = explanation.join(' ');

    const summary = 'Retirement Spending Checkup. Target retirement budget ' + fmtCurrency(annualBudget) + '/year. Guaranteed income ' + fmtCurrency(annualGuaranteed) + '/year. Needed from portfolio ' + fmtCurrency(annualFromPortfolio) + '/year. Withdrawal rate ' + withdrawalRatePct.toFixed(2) + '%. Required nest egg ' + fmtCurrency(requiredNestEgg) + '. Current savings ' + fmtCurrency(currentSavings) + '. Status: ' + statusText + '.';
    window.lastSpendingCheckupResult = { summary };

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
  const r = window.lastSpendingCheckupResult;
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
    body: JSON.stringify({ calculator_type: 'retirement-spending-checkup', results_summary: r.summary })
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

