document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('tradeoffForm');
  const resultsEl = document.getElementById('results');

  if (!form) return;

  const baselineHeadline = document.getElementById('baselineHeadline');
  const baselineDetail = document.getElementById('baselineDetail');
  const retireLaterSummary = document.getElementById('retireLaterSummary');
  const saveMoreSummary = document.getElementById('saveMoreSummary');
  const spendLessSummary = document.getElementById('spendLessSummary');
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

  function projectBalance(startBalance, annualContribution, annualReturnPct, years) {
    const r = (annualReturnPct || 0) / 100;
    let bal = startBalance;
    for (let i = 0; i < years; i++) {
      const growth = bal * r;
      bal = bal + growth + annualContribution;
    }
    return bal;
  }

  function describeShortfall(shortfallIncomePerYear) {
    if (!isFinite(shortfallIncomePerYear)) return 'Needs more information.';
    if (shortfallIncomePerYear <= 0) {
      const surplus = -shortfallIncomePerYear;
      return `On this simple rule-of-thumb, you have about ${fmtCurrency(surplus)} more annual income than your target (a surplus).`;
    }
    return `On this simple rule-of-thumb, you are short by about ${fmtCurrency(shortfallIncomePerYear)} per year relative to your target income.`;
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const currentAge = Number(document.getElementById('currentAge').value || 0);
    const retirementAge = Number(document.getElementById('retirementAge').value || 0);
    const currentSavings = Number(document.getElementById('currentSavings').value || 0);
    const annualContribution = Number(document.getElementById('annualContribution').value || 0);
    const expectedReturn = Number(document.getElementById('expectedReturn').value || 0);

    const desiredIncome = Number(document.getElementById('desiredIncome').value || 0);
    const guaranteedIncome = Number(document.getElementById('guaranteedIncome').value || 0);
    let withdrawalRatePct = Number(document.getElementById('withdrawalRate').value || 0);

    const retireLater2 = Number(document.getElementById('retireLater2').value || 0);
    const retireLater3 = Number(document.getElementById('retireLater3').value || 0);
    const extraSavings = Number(document.getElementById('extraSavings').value || 0);
    const spendingCutPct = Number(document.getElementById('spendingCutPct').value || 0);
    const partTimeIncome = Number(document.getElementById('partTimeIncome').value || 0);

    const errors = [];
    if (currentAge <= 0) errors.push('current age');
    if (retirementAge <= currentAge) errors.push('retirement age (must be greater than current age)');
    if (desiredIncome <= 0) errors.push('desired annual income');
    if (withdrawalRatePct <= 0) errors.push('withdrawal rate');

    if (errors.length) {
      alert('Please check: ' + errors.join(', ') + '.');
      return;
    }

    const yearsToRetire = retirementAge - currentAge;
    withdrawalRatePct = clamp(withdrawalRatePct, 0.5, 15);
    const withdrawalRate = withdrawalRatePct / 100;

    const neededFromPortfolio = Math.max(desiredIncome - guaranteedIncome, 0);
    const targetNestEgg = neededFromPortfolio > 0 && withdrawalRate > 0
      ? neededFromPortfolio / withdrawalRate
      : 0;

    const projectedBaselineBal = projectBalance(
      currentSavings,
      annualContribution,
      expectedReturn,
      yearsToRetire
    );

    const supportableIncomeBaseline = guaranteedIncome + projectedBaselineBal * withdrawalRate;
    const baselineShortfallIncome = desiredIncome - supportableIncomeBaseline;

    baselineHeadline.textContent =
      fmtCurrency(projectedBaselineBal) + ' projected vs ' + (targetNestEgg > 0 ? fmtCurrency(targetNestEgg) : 'no required target');

    if (targetNestEgg === 0 && neededFromPortfolio === 0 && guaranteedIncome >= desiredIncome) {
      baselineDetail.textContent =
        'Your guaranteed income already meets or exceeds your desired retirement income. On this rule-of-thumb, your portfolio can be reserved for extras and flexibility.';
    } else {
      baselineDetail.textContent = describeShortfall(baselineShortfallIncome);
    }

    // Retire later scenarios: X vs X + N
    const laterPieces = [];
    const laterVariants = [
      { label: 'Baseline age', addYears: 0 },
      { label: '+ second scenario', addYears: retireLater2 },
      { label: '+ third scenario', addYears: retireLater3 }
    ];

    laterVariants.forEach((variant, idx) => {
      if (variant.addYears < 0) return;
      const age = retirementAge + variant.addYears;
      if (age <= currentAge) return;
      const years = age - currentAge;
      const projBal = projectBalance(currentSavings, annualContribution, expectedReturn, years);
      const supportableIncome = guaranteedIncome + projBal * withdrawalRate;
      const shortfallIncome = desiredIncome - supportableIncome;

      const label =
        idx === 0
          ? `Retire at age ${age} (baseline)`
          : `Retire at age ${age} (X+${variant.addYears})`;

      laterPieces.push(
        `${label}: projected balance ${fmtCurrency(projBal)} → ` +
        `${shortfallIncome <= 0 ? 'no income shortfall (surplus of ' + fmtCurrency(-shortfallIncome) + '/year)' : 'about ' + fmtCurrency(shortfallIncome) + '/year short of your target.'}`
      );
    });

    retireLaterSummary.textContent = laterPieces.length
      ? laterPieces.join(' ')
      : 'Adjust the retirement ages above to see how working longer changes the gap.';

    // Save more scenario
    if (extraSavings > 0) {
      const projSaveMoreBal = projectBalance(
        currentSavings,
        annualContribution + extraSavings,
        expectedReturn,
        yearsToRetire
      );
      const supportableIncomeSaveMore = guaranteedIncome + projSaveMoreBal * withdrawalRate;
      const shortfallSaveMore = desiredIncome - supportableIncomeSaveMore;

      saveMoreSummary.textContent =
        `If you increase annual savings by ${fmtCurrency(extraSavings)}, your projected balance at age ${retirementAge} becomes ` +
        `${fmtCurrency(projSaveMoreBal)}. That translates to ` +
        (shortfallSaveMore <= 0
          ? `no shortfall (a surplus of about ${fmtCurrency(-shortfallSaveMore)}/year).`
          : `an income shortfall of about ${fmtCurrency(shortfallSaveMore)}/year instead of ${fmtCurrency(baselineShortfallIncome)}/year today.`);
    } else {
      saveMoreSummary.textContent =
        'Enter an extra annual savings amount to see how saving more changes your projected balance and income shortfall.';
    }

    // Spend less / part-time income scenario
    const cut = clamp(spendingCutPct, 0, 80) / 100;
    const desiredCutIncome = desiredIncome * (1 - cut);
    const boostedGuaranteed = guaranteedIncome + Math.max(partTimeIncome, 0);

    const neededFromPortfolioCut = Math.max(desiredCutIncome - boostedGuaranteed, 0);
    const targetNestEggCut = neededFromPortfolioCut > 0 && withdrawalRate > 0
      ? neededFromPortfolioCut / withdrawalRate
      : 0;

    const supportableIncomeBaselineSameBal =
      boostedGuaranteed + projectedBaselineBal * withdrawalRate;
    const shortfallCut = desiredCutIncome - supportableIncomeBaselineSameBal;

    if (spendingCutPct > 0 || partTimeIncome > 0) {
      const pieces = [];
      pieces.push(
        `New target income: ${fmtCurrency(desiredCutIncome)} after a ${spendingCutPct.toFixed(0)}% budget cut` +
        (partTimeIncome > 0
          ? ` and ${fmtCurrency(partTimeIncome)} of part‑time or side income.`
          : '.')
      );
      if (targetNestEggCut > 0) {
        pieces.push(
          `Implied portfolio target falls to about ${fmtCurrency(targetNestEggCut)} (vs ${fmtCurrency(targetNestEgg)} today).`
        );
      } else if (desiredCutIncome > 0 && boostedGuaranteed >= desiredCutIncome) {
        pieces.push(
          'Your boosted guaranteed income would fully cover this lower spending target—no portfolio needed for this rule‑of‑thumb income goal.'
        );
      }
      pieces.push(
        shortfallCut <= 0
          ? `With your current savings path, this lever would eliminate the shortfall and create a surplus of about ${fmtCurrency(-shortfallCut)}/year.`
          : `With your current savings path, you would still be short by about ${fmtCurrency(shortfallCut)}/year, instead of ${fmtCurrency(baselineShortfallIncome)}/year under the baseline.`
      );
      spendLessSummary.textContent = pieces.join(' ');
    } else {
      spendLessSummary.textContent =
        'Try a percentage budget cut and/or some part‑time income to see how a smaller target and higher guaranteed income change the required nest egg and income gap.';
    }

    const explanation = [];
    explanation.push(
      'This explorer uses a simple accumulation model (steady annual contributions and a constant return) and a withdrawal-rate rule of thumb to estimate how large a portfolio you might need for your target income.'
    );
    explanation.push(
      'It then compares your projected balance to that target and expresses the gap as an annual income shortfall or surplus, so you can see how much each lever helps.'
    );
    explanation.push(
      'Real planning should also consider taxes, inflation, market volatility, and changing spending over time, so treat these results as directional, not precise.'
    );
    explanationText.textContent = explanation.join(' ');

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

