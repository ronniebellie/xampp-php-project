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

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

