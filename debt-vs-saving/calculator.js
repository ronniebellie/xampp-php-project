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

  function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
  }

  function simulateInvestExtra(balance, rateAnnual, minPayment, extra, investReturnAnnual, years) {
    const months = Math.max(1, Math.round(years * 12));
    const rDebt = rateAnnual / 100 / 12;
    const rInv = investReturnAnnual / 100 / 12;
    let debt = balance;
    let invest = 0;

    for (let m = 0; m < months; m++) {
      if (debt > 0) {
        debt += debt * rDebt;
        const payment = Math.min(minPayment, debt);
        debt -= payment;
      }

      if (extra > 0) {
        invest = invest * (1 + rInv) + extra;
      } else {
        invest = invest * (1 + rInv);
      }
    }

    return { debt, invest };
  }

  function simulatePayDebtFirst(balance, rateAnnual, minPayment, extra, investReturnAnnual, years) {
    const months = Math.max(1, Math.round(years * 12));
    const rDebt = rateAnnual / 100 / 12;
    const rInv = investReturnAnnual / 100 / 12;
    let debt = balance;
    let invest = 0;

    for (let m = 0; m < months; m++) {
      let investContribution = 0;

      if (debt > 0) {
        const totalPayment = minPayment + extra;
        debt += debt * rDebt;
        const payment = Math.min(totalPayment, debt);
        debt -= payment;
        const leftover = Math.max(totalPayment - payment, 0);
        investContribution += leftover;
      } else {
        investContribution += minPayment + extra;
      }

      if (investContribution > 0) {
        invest = invest * (1 + rInv) + investContribution;
      } else {
        invest = invest * (1 + rInv);
      }
    }

    return { debt, invest };
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const debtBalance = Number(document.getElementById('debtBalance').value || 0);
    let debtRate = Number(document.getElementById('debtRate').value || 0);
    const minPayment = Number(document.getElementById('minPayment').value || 0);
    const extraPerMonth = Number(document.getElementById('extraPerMonth').value || 0);
    let investReturn = Number(document.getElementById('investReturn').value || 0);
    const horizonYears = Number(document.getElementById('horizonYears').value || 0);

    const errors = [];
    if (debtBalance <= 0) errors.push('debt balance');
    if (minPayment <= 0) errors.push('minimum payment');
    if (extraPerMonth < 0) errors.push('extra amount (must be zero or positive)');
    if (horizonYears <= 0) errors.push('time horizon');

    if (errors.length) {
      alert('Please check: ' + errors.join(', ') + '.');
      return;
    }

    debtRate = clamp(debtRate, 0, 60);
    investReturn = clamp(investReturn, 0, 25);

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
      'This comparison treats both debt and investments with simple monthly compounding and steady payments/returns.'
    );
    explanation.push(
      'It ignores taxes, multiple debts, changes in income, and risk tolerance, so use it as a rough guide rather than a strict rule.'
    );
    explanation.push(
      'Many people still prefer to pay down high‑interest debt aggressively for peace of mind, even when the math is close.'
    );
    explanationText.textContent = explanation.join(' ');

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

