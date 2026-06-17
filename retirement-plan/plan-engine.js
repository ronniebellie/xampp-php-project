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

  function annualSocialSecurity(age, inputs, ssMonthlyAtClaim) {
    if (age < inputs.ssClaimAge) return 0;
    var yearsSinceClaim = age - inputs.ssClaimAge;
    var monthly = ssMonthlyAtClaim;
    for (var y = 1; y <= yearsSinceClaim; y++) {
      monthly *= 1 + inputs.colaRate / 100;
    }
    return monthly * 12;
  }

  function annualSpouseSocialSecurity(age, inputs) {
    var monthly = inputs.spouseSsMonthly || 0;
    if (monthly <= 0) return 0;
    var startAge = inputs.spouseSsClaimAge || inputs.ssClaimAge;
    if (age < startAge) return 0;
    var yearsSinceClaim = age - startAge;
    for (var y = 1; y <= yearsSinceClaim; y++) {
      monthly *= 1 + inputs.colaRate / 100;
    }
    return monthly * 12;
  }

  function householdSocialSecurityAnnual(age, inputs, ssMonthlyAtClaim) {
    return annualSocialSecurity(age, inputs, ssMonthlyAtClaim) +
      annualSpouseSocialSecurity(age, inputs);
  }

  function annualSpendingAtAge(age, inputs) {
    if (age < inputs.retirementAge) return 0;
    var yearsSinceRetirement = age - inputs.retirementAge;
    return inputs.baseAnnualSpending * Math.pow(1 + inputs.inflation / 100, yearsSinceRetirement);
  }

  function targetNestEggAtRetirement(inputs, ssMonthlyAtClaim) {
    var spending = inputs.baseAnnualSpending;
    var ssAnnual = annualSocialSecurity(inputs.retirementAge, inputs, ssMonthlyAtClaim);
    var spouseSsAnnual = annualSpouseSocialSecurity(inputs.retirementAge, inputs);
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

  function describeStatus(balanceAtRetirement, targetNestEgg) {
    if (targetNestEgg <= 0) {
      return {
        code: 'covered',
        headline: 'Guaranteed income covers spending',
        detail: 'Your Social Security and other guaranteed income may cover your retirement spending target. Your portfolio can fund extras, flexibility, and legacy goals.',
        tone: 'good'
      };
    }
    if (!isFinite(balanceAtRetirement) || balanceAtRetirement <= 0) {
      return {
        code: 'shortfall',
        headline: 'Likely shortfall at retirement',
        detail: 'On this rule-of-thumb, projected savings at retirement are well below the target nest egg.',
        tone: 'bad'
      };
    }
    var ratio = balanceAtRetirement / targetNestEgg;
    if (ratio >= 1.1) {
      return {
        code: 'on_track',
        headline: 'On track (with cushion)',
        detail: 'Projected savings at retirement are about ' + Math.round((ratio - 1) * 100) + '% above the rule-of-thumb target.',
        tone: 'good'
      };
    }
    if (ratio >= 0.9) {
      return {
        code: 'close',
        headline: 'Close — small adjustments may help',
        detail: 'You are within about 10% of the target nest egg. Retiring a year or two later, saving more, or trimming spending could close the gap.',
        tone: 'warn'
      };
    }
    return {
      code: 'shortfall',
      headline: 'Likely shortfall at retirement',
      detail: 'Projected savings at retirement are about ' + FC.formatCurrency(targetNestEgg - balanceAtRetirement) + ' below the rule-of-thumb target.',
      tone: 'bad'
    };
  }

  /**
   * @param {object} inputs
   * @returns {{ years: object[], summary: object, milestones: object[] }}
   */
  function runDeterministicPlan(inputs) {
    var ssMonthlyAtClaim = FC.calculateMonthlyBenefit(
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

    for (var age = inputs.currentAge; age <= inputs.planEndAge; age++) {
      var balanceStart = balance;
      var phase = age < inputs.retirementAge ? 'accumulation' : 'retirement';
      var contribution = phase === 'accumulation' ? inputs.annualContribution : 0;
      var returnRate = phase === 'accumulation' ? inputs.returnPreRetirement : inputs.returnRetirement;

      if (phase === 'accumulation') {
        balance = balance * (1 + returnRate / 100) + contribution;
        years.push({
          age: age,
          phase: phase,
          balanceStart: balanceStart,
          balanceEnd: balance,
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
        var taxDeferredBalance = balanceStart * taxDeferredPct;
        var rmd = TR.calculateRMD(age, taxDeferredBalance, isSpouseBeneficiary, spouseAge);
        var spending = annualSpendingAtAge(age, inputs);
        var ssAnnual = annualSocialSecurity(age, inputs, ssMonthlyAtClaim);
        var spouseSsAnnual = annualSpouseSocialSecurity(age, inputs);
        var householdSsAnnual = ssAnnual + spouseSsAnnual;
        var otherIncome = inputs.otherGuaranteedAnnual;
        var spendingGap = Math.max(0, spending - householdSsAnnual - otherIncome);
        var portfolioWithdrawal = Math.max(rmd, spendingGap);
        if (portfolioWithdrawal > balanceStart) portfolioWithdrawal = balanceStart;

        var taxableIncome = TR.estimateTaxableIncome(
          portfolioWithdrawal,
          householdSsAnnual,
          otherIncome,
          inputs.filingStatus,
          inputs.useStandardDeduction !== false
        );
        var federalTax = TR.calculateFederalTax(taxableIncome, inputs.filingStatus);
        var marginalRate = TR.getMarginalRate(taxableIncome, inputs.filingStatus);
        lifetimeFederalTax += federalTax;

        var afterWithdrawal = Math.max(0, balanceStart - portfolioWithdrawal);
        balance = afterWithdrawal * (1 + returnRate / 100);

        if (balance <= 0 && depletedAge === null && age < inputs.planEndAge) {
          depletedAge = age;
        }

        years.push({
          age: age,
          phase: phase,
          balanceStart: balanceStart,
          balanceEnd: balance,
          spending: spending,
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

    var retirementRow = years.find(function (y) { return y.age === inputs.retirementAge; });
    var balanceAtRetirement = retirementRow ? retirementRow.balanceEnd : balance;
    var targetNestEgg = targetNestEggAtRetirement(inputs, ssMonthlyAtClaim);
    var status = describeStatus(balanceAtRetirement, targetNestEgg);

    var milestoneAges = pickMilestoneAges(inputs);
    var milestones = milestoneAges.map(function (a) {
      return years.find(function (y) { return y.age === a; }) || null;
    }).filter(Boolean);

    var retirementIncomeRow = years.find(function (y) {
      return y.age === Math.max(inputs.retirementAge, inputs.ssClaimAge);
    }) || retirementRow;

    var firstRmdRow = years.find(function (y) { return y.age === RMD_START_AGE; });

    return {
      years: years,
      milestones: milestones,
      summary: {
        status: status,
        balanceAtRetirement: balanceAtRetirement,
        targetNestEgg: targetNestEgg,
        ssMonthlyAtClaim: ssMonthlyAtClaim,
        ssAnnualAtClaim: ssMonthlyAtClaim * 12,
        spouseSsMonthly: inputs.spouseSsMonthly || 0,
        householdSsMonthlyAtClaim: ssMonthlyAtClaim + (inputs.spouseSsMonthly || 0),
        fraAge: FC.fraAgeFromBirthYear(inputs.birthYear),
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
