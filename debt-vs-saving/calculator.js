document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('debtVsSavingForm');
  const resultsEl = document.getElementById('results');

  if (!form) return;

  const investExtraHeadline = document.getElementById('investExtraHeadline');
  const investExtraDetail = document.getElementById('investExtraDetail');
  const payDebtHeadline = document.getElementById('payDebtHeadline');
  const payDebtDetail = document.getElementById('payDebtDetail');
  const winnerHeadline = document.getElementById('winnerHeadline');
  const winnerDetail = document.getElementById('winnerDetail');
  const explanationText = document.getElementById('explanationText');

  function fmtCurrency(value) {
    if (!isFinite(value)) return '—';
    return value.toLocaleString(undefined, {
      style: 'currency',
      currency: 'USD',
      maximumFractionDigits: 0
    });
  }

  function simulateInvestExtra(...args) { return RBNumerical.debtSaving(...args, false); }
  function simulatePayDebtFirst(...args) { return RBNumerical.debtSaving(...args, true); }

  function updateDebtVsSaving(showAlerts) {
    try {
    const debtBalance = Number(document.getElementById('debtBalance').value || 0);
    let debtRate = Number(document.getElementById('debtRate').value || 0);
    const minPayment = Number(document.getElementById('minPayment').value || 0);
    const extraPerMonth = Number(document.getElementById('extraPerMonth').value || 0);
    let investReturn = Number(document.getElementById('investReturn').value || 0);
    const horizonYears = Number(document.getElementById('horizonYears').value || 0);

    const debtBalanceLabel = document.getElementById('debtBalanceLabel');
    if (debtBalanceLabel) debtBalanceLabel.textContent = fmtCurrency(debtBalance);
    const debtRateLabel = document.getElementById('debtRateLabel');
    if (debtRateLabel) debtRateLabel.textContent = debtRate.toFixed(2).replace(/\.00$/, '') + '%';
    const minPaymentLabel = document.getElementById('minPaymentLabel');
    if (minPaymentLabel) minPaymentLabel.textContent = fmtCurrency(minPayment) + '/mo';
    const extraPerMonthLabel = document.getElementById('extraPerMonthLabel');
    if (extraPerMonthLabel) extraPerMonthLabel.textContent = fmtCurrency(extraPerMonth) + '/mo';
    const investReturnLabel = document.getElementById('investReturnLabel');
    if (investReturnLabel) investReturnLabel.textContent = investReturn.toFixed(2).replace(/\.00$/, '') + '%';
    const horizonYearsLabel = document.getElementById('horizonYearsLabel');
    if (horizonYearsLabel) horizonYearsLabel.textContent = horizonYears.toFixed(0) + ' yrs';

    const errors = [];
    if (![debtBalance,debtRate,minPayment,extraPerMonth,investReturn,horizonYears].every(Number.isFinite)) errors.push('finite numeric inputs');
    if (debtRate < 0 || debtRate > 60 || investReturn < -100 || investReturn > 25) errors.push('supported rates: debt 0–60%, savings -100–25% nominal annual');
    if (horizonYears > 1000 || Math.abs(horizonYears*12-Math.round(horizonYears*12)) > 1e-8) errors.push('at most 12000 whole monthly periods');
    if (debtBalance <= 0) errors.push('debt balance');
    if (minPayment <= 0) errors.push('minimum payment');
    if (extraPerMonth < 0) errors.push('extra amount (must be zero or positive)');
    if (horizonYears <= 0) errors.push('time horizon');

    if (errors.length) {
      resultsEl.style.display = 'none';
      if (showAlerts) alert('Please check: ' + errors.join(', ') + '.');
      return;
    }


    const investExtra = simulateInvestExtra(
      debtBalance,
      debtRate,
      minPayment,
      extraPerMonth,
      investReturn,
      horizonYears
    );

    const payDebtFirst = simulatePayDebtFirst(
      debtBalance,
      debtRate,
      minPayment,
      extraPerMonth,
      investReturn,
      horizonYears
    );

    const netWorthInvestExtra = investExtra.invest - investExtra.debt;
    const netWorthPayDebt = payDebtFirst.invest - payDebtFirst.debt;

    investExtraHeadline.textContent =
      fmtCurrency(investExtra.invest) + ' invested, ' +
      (investExtra.debt > 0 ? fmtCurrency(investExtra.debt) + ' debt remaining' : 'no debt remaining');

    investExtraDetail.textContent =
      'Approximate net worth after ' +
      horizonYears.toFixed(0) +
      ' years if you invest the extra each month and pay only the minimum on this debt: ' +
      fmtCurrency(netWorthInvestExtra) +
      '.';

    payDebtHeadline.textContent =
      fmtCurrency(payDebtFirst.invest) + ' invested, ' +
      (payDebtFirst.debt > 0 ? fmtCurrency(payDebtFirst.debt) + ' debt remaining' : 'no debt remaining');

    payDebtDetail.textContent =
      'Approximate net worth after ' +
      horizonYears.toFixed(0) +
      ' years if you send the extra to debt first (then invest what you were paying once it is gone): ' +
      fmtCurrency(netWorthPayDebt) +
      '.';

    if (!isFinite(netWorthInvestExtra) || !isFinite(netWorthPayDebt)) {
      winnerHeadline.textContent = 'Needs more information';
      winnerDetail.textContent =
        'Check your inputs—especially interest rates and horizon—to see a clearer comparison.';
    } else if (Math.abs(netWorthInvestExtra - netWorthPayDebt) < 1) {
      winnerHeadline.textContent = 'Roughly a tie';
      winnerDetail.textContent =
        'On these assumptions, both strategies land you at almost the same place financially. Other factors—like risk, peace of mind, or minimum payment flexibility—may matter more.';
    } else if (netWorthPayDebt > netWorthInvestExtra) {
      const diff = netWorthPayDebt - netWorthInvestExtra;
      winnerHeadline.textContent = 'Paying debt first comes out ahead';
      winnerDetail.textContent =
        'In this scenario, using the extra to pay down debt first and then investing the freed‑up cash leaves you with about ' +
        fmtCurrency(diff) +
        ' more net worth after ' +
        horizonYears.toFixed(0) +
        ' years than investing the extra from day one.';
    } else {
      const diff = netWorthInvestExtra - netWorthPayDebt;
      winnerHeadline.textContent = 'Investing extra comes out ahead';
      winnerDetail.textContent =
        'On these numbers, investing the extra each month (while paying only the minimum on the debt) leaves you with about ' +
        fmtCurrency(diff) +
        ' more net worth after ' +
        horizonYears.toFixed(0) +
        ' years than focusing on debt first.';
    }

    const explanation = [];
    explanation.push(
      'Both strategies use the same household budget: minimum payment plus extra. Unused debt payments, including the final partial payment and all payments after payoff, go to savings in BOTH strategies. Interest accrues before end-of-month payments and contributions.'
    );
    explanation.push(
      'It ignores taxes, multiple debts, changes in income, and risk tolerance, so use it as a rough guide rather than a strict rule.'
    );
    explanation.push(
      'Many people still prefer to pay down high‑interest debt aggressively for peace of mind, even when the math is close.'
    );
    explanationText.textContent = explanation.join(' ');

    resultsEl.style.display = 'block';
    } catch (error) {
      resultsEl.style.display = 'none';
      if (showAlerts) alert(error.message);
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    updateDebtVsSaving(true);
  });

  ['debtBalance', 'debtRate', 'minPayment', 'extraPerMonth', 'investReturn', 'horizonYears'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => updateDebtVsSaving(false));
  });

  updateDebtVsSaving(false);
});
