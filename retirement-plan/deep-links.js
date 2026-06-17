/**
 * Build pre-filled deep-dive URLs from Retirement Plan Builder inputs.
 */
(function (global) {
  'use strict';

  function mapFilingForRoth(status) {
    if (status === 'hoh') return 'head';
    return status || 'married';
  }

  function mapFilingForSsGap(status) {
    return status === 'single' || status === 'hoh' ? 'single' : 'married';
  }

  function clampRound(value, min, max, step) {
    var v = Math.max(min, Math.min(max, value));
    if (step > 0) v = Math.round(v / step) * step;
    return v;
  }

  function firstRetirementYearRow(result) {
    if (!result || !result.years) return null;
    for (var i = 0; i < result.years.length; i++) {
      if (result.years[i].phase === 'retirement') return result.years[i];
    }
    return null;
  }

  /**
   * @param {object} inputs plan inputs
   * @param {object} result deterministic plan result
   * @returns {object} keyed link id -> href
   */
  function buildDeepDiveLinks(inputs, result) {
    var UP = global.RBUrlPrefill;
    if (!UP || !inputs || !result) return {};

    var taxDeferred = Math.round(inputs.balance * (inputs.taxDeferredPct / 100));
    var rothPortion = Math.round(inputs.balance - taxDeferred);
    var ssAnnual = Math.round(result.summary.ssAnnualAtClaim || 0);
    var mcStartAge = Math.max(inputs.currentAge, inputs.retirementAge);
    var yearsToModel = Math.max(1, inputs.planEndAge - mcStartAge);
    var startRow = result.years.find(function (y) { return y.age === mcStartAge; });
    var portfolioForMc = startRow ? Math.round(startRow.balanceStart) : Math.round(inputs.balance);
    var retireRow = firstRetirementYearRow(result);
    var annualWithdrawal = retireRow ? Math.round(retireRow.withdrawal || 0) : 0;
    var guaranteedMonthly = Math.round((inputs.otherGuaranteedAnnual || 0) / 12);
    var ssMonthly = Math.round(result.summary.ssMonthlyAtClaim || inputs.ssPiaMonthly || 0);

    var common = { fromPlan: 1 };

    return {
      deepLinkSs: UP.buildUrl('../social-security-claiming-analyzer/', Object.assign({}, common, {
        birthDateYear: inputs.birthYear,
        birthDateMonth: 1,
        birthDateDay: 1,
        monthlyPIA: Math.round(inputs.ssPiaMonthly),
        claimAgeB: inputs.ssClaimAge,
        lifeExpectancy: inputs.planEndAge,
        colaRate: inputs.colaRate
      })),
      deepLinkPlanSuccess: UP.buildUrl('../plan-success/', Object.assign({}, common, {
        portfolio: portfolioForMc,
        withdrawal: annualWithdrawal,
        years: yearsToModel,
        expectedReturn: inputs.returnRetirement,
        volatility: inputs.volatilityPct,
        simulations: inputs.numSims,
        inflationRate: inputs.inflation
      })),
      deepLinkRoth: UP.buildUrl('../roth-conv/', Object.assign({}, common, {
        currentAge: inputs.currentAge,
        retirementAge: inputs.currentAge >= inputs.retirementAge ? '' : inputs.retirementAge,
        lifeExpectancy: inputs.planEndAge,
        filingStatus: mapFilingForRoth(inputs.filingStatus),
        traditionalIRA: taxDeferred,
        rothIRA: rothPortion,
        retirementIncome: ssAnnual + Math.round(inputs.otherGuaranteedAnnual || 0),
        annualPortfolioWithdrawalRate: (inputs.withdrawalRate * 100).toFixed(2),
        spouseAge: inputs.spouseIsBeneficiary && inputs.spouseAge ? inputs.spouseAge : ''
      })),
      deepLinkRmd: UP.buildUrl('../rmd-impact/', Object.assign({}, common, {
        currentAge: inputs.currentAge,
        accountBalance: taxDeferred,
        growthRate: inputs.returnRetirement,
        socialSecurity: ssAnnual,
        pension: Math.round(inputs.otherGuaranteedAnnual || 0),
        otherIncome: 0,
        filingStatus: inputs.filingStatus === 'hoh' ? 'hoh' : inputs.filingStatus,
        spouseBeneficiary: inputs.spouseIsBeneficiary ? 'yes' : 'no',
        spouseAge: inputs.spouseIsBeneficiary && inputs.spouseAge ? inputs.spouseAge : ''
      })),
      deepLinkSpending: UP.buildUrl('../retirement-spending-checkup/', Object.assign({}, common, {
        currentMonthlySpending: Math.round(inputs.baseAnnualSpending / 12),
        retirementSpendingPct: 100,
        currentSavings: Math.round(inputs.balance),
        guaranteedMonthlyIncome: guaranteedMonthly + ssMonthly,
        withdrawalRate: (inputs.withdrawalRate * 100).toFixed(2),
        alreadyRetired: inputs.currentAge >= inputs.retirementAge ? 1 : ''
      })),
      deepLinkSsGap: UP.buildUrl('../ss-gap/', Object.assign({}, common, {
        targetSpending: clampRound(inputs.baseAnnualSpending / 12, 3000, 15000, 100),
        ssIncome: clampRound(ssMonthly, 0, 6000, 100),
        otherIncome: clampRound((inputs.otherGuaranteedAnnual || 0) / 12, 0, 4000, 100),
        withdrawalRate: clampRound(inputs.withdrawalRate * 100, 2.5, 6.0, 0.1).toFixed(1),
        filingStatus: mapFilingForSsGap(inputs.filingStatus)
      })),
      deepLinkNestEgg: UP.buildUrl('../nest-egg-target/', Object.assign({}, common, {
        incomeMethod: 'direct',
        desiredAnnualIncome: clampRound(inputs.baseAnnualSpending, 20000, 200000, 5000),
        guaranteedAnnualIncome: clampRound(ssAnnual + Math.round(inputs.otherGuaranteedAnnual || 0), 0, 120000, 5000),
        withdrawalRate: clampRound(inputs.withdrawalRate * 100, 2, 8, 0.25),
        currentSavings: Math.round(inputs.balance)
      }))
    };
  }

  global.RBDeepLinks = {
    buildDeepDiveLinks: buildDeepDiveLinks
  };
})(typeof window !== 'undefined' ? window : this);
