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

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});
