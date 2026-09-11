/**
 * Deterministic year-by-year retirement plan projection.
 */
(function (global) {
  'use strict';

  var FC = global.RBFinance;
  var TR = global.RBTaxRmd;
  if (!FC) throw new Error('RBFinance (finance-core.js) must load before plan-engine.js');
  if (!TR) throw new Error('RBTaxRmd (rmd-tax-core.js) must load before plan-engine.js');

  var RMD_START_AGE = TR.RMD_START_AGE;

  function annualSocialSecurity(age, inputs, ssMonthlyBaseline) {
    if (inputs.ssAlreadyReceiving) {
      if (age < inputs.currentAge) return 0;
      var yearsFromNow = age - inputs.currentAge;
      var monthlyNow = ssMonthlyBaseline;
      for (var y = 1; y <= yearsFromNow; y++) {
        monthlyNow *= 1 + inputs.colaRate / 100;
      }
      return monthlyNow * 12;
    }
    if (age < inputs.ssClaimAge) return 0;
    var yearsSinceClaim = age - inputs.ssClaimAge;
    var monthly = ssMonthlyBaseline;
    for (var y = 1; y <= yearsSinceClaim; y++) {
      monthly *= 1 + inputs.colaRate / 100;
    }
    return monthly * 12;
  }

  function annualSpouseSocialSecurity(age, inputs) {
    if (inputs.spouseSsAlreadyReceiving) {
      var monthlyReceiving = inputs.spouseSsCurrentMonthly || 0;
      if (monthlyReceiving <= 0) return 0;
      if (age < inputs.currentAge) return 0;
      var yearsFromNow = age - inputs.currentAge;
      var spouseMonthly = monthlyReceiving;
      for (var s = 1; s <= yearsFromNow; s++) {
        spouseMonthly *= 1 + inputs.colaRate / 100;
      }
      return spouseMonthly * 12;
    }
    var monthly = inputs.spouseSsMonthly || 0;
    if (monthly <= 0) return 0;
    var startAge = inputs.spouseSsClaimAge || inputs.ssClaimAge;
    if (age < startAge) return 0;
    var yearsSinceClaim = age - startAge;
    for (var i = 1; i <= yearsSinceClaim; i++) {
      monthly *= 1 + inputs.colaRate / 100;
    }
    return monthly * 12;
  }

  function householdSocialSecurityAnnual(age, inputs, ssMonthlyAtClaim) {
    return annualSocialSecurity(age, inputs, ssMonthlyAtClaim) +
      annualSpouseSocialSecurity(age, inputs);
  }

  function portfolioWithdrawalStartAge(inputs) {
    var start = inputs.portfolioWithdrawalStartAge;
    if (start != null && !isNaN(start) && start > 0) return start;
    return inputs.retirementAge;
  }

  function annualSpendingAtAge(age, inputs) {
    if (age < inputs.retirementAge) return 0;
    var yearsSinceRetirement = age - inputs.retirementAge;
    return inputs.baseAnnualSpending * Math.pow(1 + inputs.inflation / 100, yearsSinceRetirement);
  }

  function targetNestEggAtRetirement(inputs, ssMonthlyBaseline) {
    var startAge = Math.max(inputs.retirementAge, portfolioWithdrawalStartAge(inputs));
    var spending = annualSpendingAtAge(startAge, inputs);
    var ssAnnual = annualSocialSecurity(startAge, inputs, ssMonthlyBaseline);
    var spouseSsAnnual = annualSpouseSocialSecurity(startAge, inputs);
    var guaranteed = ssAnnual + spouseSsAnnual + inputs.otherGuaranteedAnnual;
    var needed = Math.max(0, spending - guaranteed);
    if (needed <= 0 || inputs.withdrawalRate <= 0) return 0;
    return needed / inputs.withdrawalRate;
  }

  function pickMilestoneAges(inputs) {
    var ages = [inputs.currentAge, inputs.retirementAge];
    if (inputs.ssClaimAge !== inputs.retirementAge) ages.push(inputs.ssClaimAge);
    if (inputs.currentAge < RMD_START_AGE && inputs.planEndAge >= RMD_START_AGE) ages.push(RMD_START_AGE);
    ages.push(inputs.planEndAge);
    var seen = {};
    return ages.filter(function (a) {
      if (a < inputs.currentAge || a > inputs.planEndAge || seen[a]) return false;
      seen[a] = true;
      return true;
    }).sort(function (a, b) { return a - b; });
  }

  function describeStatus(compareBalance, targetNestEgg, context) {
    context = context || {};
    if (targetNestEgg <= 0) {
      return {
        code: 'covered',
        headline: 'Guaranteed income covers spending',
        detail: 'Your Social Security and other guaranteed income may cover your retirement spending target. Your portfolio can fund extras, flexibility, and legacy goals.',
        tone: 'good'
      };
    }
    if (!isFinite(compareBalance) || compareBalance <= 0) {
      return {
        code: 'shortfall',
        headline: 'Likely shortfall at retirement',
        detail: 'On this rule-of-thumb, projected savings at retirement are well below the target nest egg.',
        tone: 'bad'
      };
    }
    var ratio = compareBalance / targetNestEgg;
    var cushionPct = Math.round((ratio - 1) * 100);
    var planEndAge = context.planEndAge || 90;
    var inRetirementPhase = !!context.inRetirementPhase;
    var planSustainable = context.depletedAge === null || context.depletedAge === undefined;

    if (inRetirementPhase && planSustainable && ratio >= 1) {
      return {
        code: 'on_track',
        headline: 'On track — plan lasts through age ' + planEndAge,
        detail: 'Your year-by-year plan funds spending through age ' + planEndAge +
          (context.endingBalance > 0 ? ' with about ' + FC.formatCurrency(context.endingBalance) + ' remaining.' : '.') +
          ' Portfolio at withdrawal start is about ' + cushionPct + '% above the rule-of-thumb target.',
        tone: 'good'
      };
    }

    if (ratio >= 1.1) {
      return {
        code: 'on_track',
        headline: 'On track (with cushion)',
        detail: 'Projected savings at retirement are about ' + cushionPct + '% above the rule-of-thumb target.',
        tone: 'good'
      };
    }
    if (ratio >= 0.9) {
      var closeDetail = inRetirementPhase
        ? 'You are within about 10% of the rule-of-thumb target nest egg. Small spending trims or a modest return cushion could add margin.'
        : 'You are within about 10% of the target nest egg. Retiring a year or two later, saving more, or trimming spending could close the gap.';
      if (inRetirementPhase && planSustainable) {
        closeDetail += ' Your full timeline still funds spending through age ' + planEndAge + '.';
      }
      return {
        code: 'close',
        headline: 'Close — small adjustments may help',
        detail: closeDetail,
        tone: 'warn'
      };
    }
    return {
      code: 'shortfall',
      headline: 'Likely shortfall at retirement',
      detail: 'Projected savings at retirement are about ' + FC.formatCurrency(targetNestEgg - compareBalance) + ' below the rule-of-thumb target.',
      tone: 'bad'
    };
  }

  /**
   * @param {object} inputs
   * @returns {{ years: object[], summary: object, milestones: object[] }}
   */
  function runDeterministicPlan(inputs, retirementReturn) {
    var ssMonthlyBaseline = inputs.ssAlreadyReceiving
      ? (inputs.ssCurrentMonthly || 0)
      : FC.calculateMonthlyBenefit(
        inputs.ssPiaMonthly,
        inputs.birthYear,
        inputs.ssClaimAge
      );

    var taxDeferredPct = FC.clamp(inputs.taxDeferredPct != null ? inputs.taxDeferredPct : 85, 0, 100) / 100;
    var spouseAge = inputs.spouseAge || null;
    var isSpouseBeneficiary = !!inputs.spouseIsBeneficiary;
    var lifetimeFederalTax = 0;

    var years = [];
    var balance = inputs.balance;
    var depletedAge = null;
    var shortfallAge = null;
    var traditional = balance * taxDeferredPct;
    var otherAssets = balance - traditional;

    for (var age = inputs.currentAge; age <= inputs.planEndAge; age++) {
      var balanceStart = balance;
      var traditionalStart = traditional;
      var otherAssetsStart = otherAssets;
      var phase = age < inputs.retirementAge ? 'accumulation' : 'retirement';
      var contribution = phase === 'accumulation' ? inputs.annualContribution : 0;
      var returnRate = phase === 'accumulation' ? inputs.returnPreRetirement : inputs.returnRetirement;

      if (phase === 'retirement' && retirementReturn) returnRate = retirementReturn(age);
      returnRate = Math.max(-100, returnRate);
      if (phase === 'accumulation') {
        traditional = traditional * (1 + returnRate / 100) + contribution * taxDeferredPct;
        otherAssets = otherAssets * (1 + returnRate / 100) + contribution * (1 - taxDeferredPct);
        balance = traditional + otherAssets;
        years.push({
          age: age,
          phase: phase,
          balanceStart: balanceStart,
          balanceEnd: balance,
          traditionalBalance: traditional, otherAssetsBalance: otherAssets,
          traditionalStart: traditionalStart, otherAssetsStart: otherAssetsStart,
          investmentReturn: balanceStart * returnRate / 100,
          requestedSpending: 0, fundedSpending: 0, spendingShortfall: 0, taxShortfall: 0,
          taxesPaid: 0, traditionalWithdrawal: 0, otherWithdrawal: 0, surplus: 0,
          spending: 0,
          socialSecurity: 0,
          otherIncome: 0,
          rmd: 0,
          withdrawal: 0,
          federalTax: 0,
          marginalRate: 0,
          totalIncome: 0,
          contribution: contribution,
          rmdStarts: false
        });
      } else {
        var rmd = Math.min(traditional, TR.calculateRMD(age, traditional, isSpouseBeneficiary, spouseAge));
        var spending = annualSpendingAtAge(age, inputs);
        var householdSsAnnual = householdSocialSecurityAnnual(age, inputs, ssMonthlyBaseline);
        var otherIncome = inputs.otherGuaranteedAnnual;
        var income = householdSsAnnual + otherIncome;
        var canWithdraw = age >= portfolioWithdrawalStartAge(inputs);
        // Keep the actual account split over time. Mandatory distributions come
        // from traditional assets; discretionary draws use other assets first.
        var traditionalWithdrawal = rmd, otherWithdrawal = 0;
        traditional -= rmd;
        var cash = income + rmd;
        var taxableIncome, federalTax;
        for (var pass = 0; pass < 128; pass++) {
          taxableIncome = TR.estimateTaxableIncome(traditionalWithdrawal, householdSsAnnual,
            otherIncome, inputs.filingStatus, inputs.useStandardDeduction !== false);
          federalTax = TR.calculateFederalTax(taxableIncome, inputs.filingStatus);
          var need = Math.max(0, spending + federalTax - cash);
          if (!canWithdraw || need < 1e-7 || traditional + otherAssets < 1e-7) break;
          var fromOther = Math.min(need, otherAssets);
          otherAssets -= fromOther; otherWithdrawal += fromOther; cash += fromOther;
          var fromTraditional = Math.min(need - fromOther, traditional);
          traditional -= fromTraditional; traditionalWithdrawal += fromTraditional; cash += fromTraditional;
        }
        var taxesPaid = Math.min(cash, federalTax);
        var taxShortfall = Math.max(0, federalTax - taxesPaid);
        var fundedSpending = Math.min(spending, Math.max(0, cash - taxesPaid));
        var spendingShortfall = Math.max(0, spending - fundedSpending);
        var surplus = Math.max(0, cash - taxesPaid - fundedSpending);
        otherAssets += surplus; // Excess RMD/income is saved, not consumed or lost.
        var investmentReturn = (traditional + otherAssets) * returnRate / 100;
        traditional *= 1 + returnRate / 100;
        otherAssets *= 1 + returnRate / 100;
        balance = traditional + otherAssets;
        var portfolioWithdrawal = traditionalWithdrawal + otherWithdrawal;
        var marginalRate = TR.getMarginalRate(taxableIncome, inputs.filingStatus);
        lifetimeFederalTax += taxesPaid;
        if (balance <= 1e-7 && depletedAge === null) depletedAge = age;
        if ((spendingShortfall > 1e-6 || taxShortfall > 1e-6) && shortfallAge === null) shortfallAge = age;

        years.push({
          age: age,
          phase: phase,
          balanceStart: balanceStart,
          balanceEnd: balance,
          traditionalStart: traditionalStart, otherAssetsStart: otherAssetsStart,
          traditionalBalance: traditional, otherAssetsBalance: otherAssets,
          traditionalWithdrawal: traditionalWithdrawal, otherWithdrawal: otherWithdrawal,
          requestedSpending: spending, fundedSpending: fundedSpending, spendingShortfall: spendingShortfall,
          taxesPaid: taxesPaid, taxShortfall: taxShortfall, surplus: surplus, investmentReturn: investmentReturn,
          taxFunding: {income: Math.min(taxesPaid, income),
            traditional: Math.min(Math.max(0, taxesPaid-income), traditionalWithdrawal),
            other: Math.max(0, taxesPaid-income-traditionalWithdrawal)},
          contribution: 0,
          spending: fundedSpending,
          socialSecurity: householdSsAnnual,
          otherIncome: otherIncome,
          rmd: rmd,
          withdrawal: portfolioWithdrawal,
          federalTax: federalTax,
          marginalRate: marginalRate,
          totalIncome: householdSsAnnual + otherIncome + portfolioWithdrawal,
          rmdStarts: age === RMD_START_AGE
        });
      }

      if (spouseAge) spouseAge++;
    }

    var withdrawalStartAge = portfolioWithdrawalStartAge(inputs);
    var retirementRow = years.find(function (y) { return y.age === inputs.retirementAge; });
    var withdrawalStartRow = years.find(function (y) { return y.age === withdrawalStartAge; });
    var balanceAtRetirement = retirementRow ? retirementRow.balanceEnd : balance;
    var balanceAtWithdrawalStart = withdrawalStartRow
      ? withdrawalStartRow.balanceStart
      : (withdrawalStartAge > inputs.currentAge ? inputs.balance : balanceAtRetirement);
    var compareBalance = withdrawalStartAge > inputs.currentAge
      ? balanceAtWithdrawalStart
      : balanceAtRetirement;
    var targetNestEgg = targetNestEggAtRetirement(inputs, ssMonthlyBaseline);
    var endingBalance = years.length ? years[years.length - 1].balanceEnd : balance;
    var status = describeStatus(compareBalance, targetNestEgg, {
      inRetirementPhase: inputs.currentAge >= inputs.retirementAge,
      depletedAge: depletedAge,
      planEndAge: inputs.planEndAge,
      endingBalance: endingBalance,
      withdrawalStartAge: withdrawalStartAge
    });

    if (shortfallAge !== null) status = {
      code: 'shortfall', headline: 'Unfunded spending or taxes from age ' + shortfallAge,
      detail: 'Available income and permitted withdrawals do not fully fund the plan. See annual shortfalls.', tone: 'bad'
    };
    var milestoneAges = pickMilestoneAges(inputs);
    var milestones = milestoneAges.map(function (a) {
      return years.find(function (y) { return y.age === a; }) || null;
    }).filter(Boolean);

    var retirementIncomeRow = years.find(function (y) {
      return y.age === Math.max(inputs.retirementAge, inputs.ssClaimAge, withdrawalStartAge);
    }) || retirementRow;

    var firstRmdRow = years.find(function (y) { return y.age === RMD_START_AGE; });

    var summaryAge = Math.max(inputs.currentAge, inputs.retirementAge);
    var summaryUserMonthly = annualSocialSecurity(summaryAge, inputs, ssMonthlyBaseline) / 12;
    var summarySpouseMonthly = annualSpouseSocialSecurity(summaryAge, inputs) / 12;

    return {
      years: years,
      milestones: milestones,
      summary: {
        status: status,
        balanceAtRetirement: balanceAtRetirement,
        balanceAtWithdrawalStart: balanceAtWithdrawalStart,
        compareBalanceForStatus: compareBalance,
        portfolioWithdrawalStartAge: withdrawalStartAge,
        targetNestEgg: targetNestEgg,
        ssMonthlyAtClaim: summaryUserMonthly,
        ssAnnualAtClaim: summaryUserMonthly * 12,
        spouseSsMonthly: inputs.spouseSsAlreadyReceiving
          ? (inputs.spouseSsCurrentMonthly || 0)
          : (inputs.spouseSsMonthly || 0),
        householdSsMonthlyAtClaim: summaryUserMonthly + summarySpouseMonthly,
        fraAge: FC.fraAgeFromBirthYear(inputs.birthYear),
        shortfallAge: shortfallAge,
        totalSpendingShortfall: years.reduce(function (sum, row) { return sum + row.spendingShortfall; }, 0),
        totalTaxShortfall: years.reduce(function (sum, row) { return sum + row.taxShortfall; }, 0),
        depletedAge: depletedAge,
        endingBalance: years.length ? years[years.length - 1].balanceEnd : balance,
        retirementAnnualIncome: retirementIncomeRow ? retirementIncomeRow.totalIncome : 0,
        lifetimeFederalTax: lifetimeFederalTax,
        firstRmdAmount: firstRmdRow ? firstRmdRow.rmd : 0,
        rmdStartAge: RMD_START_AGE
      }
    };
  }

  global.RBPlanEngine = {
    RMD_START_AGE: RMD_START_AGE,
    runDeterministicPlan: runDeterministicPlan,
    targetNestEggAtRetirement: targetNestEggAtRetirement
  };
})(typeof window !== 'undefined' ? window : this);
