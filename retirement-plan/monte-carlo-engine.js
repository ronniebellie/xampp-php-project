/**
 * Monte Carlo stress test for retirement plan (random returns in retirement phase).
 */
(function (global) {
  'use strict';

  var FC = global.RBFinance;
  var TR = global.RBTaxRmd;
  if (!FC || !TR) throw new Error('RBFinance and RBTaxRmd must load before monte-carlo-engine.js');

  var RNG_SEED = 0x9E3779B9;
  var rngState = RNG_SEED;

  function resetRng() { rngState = RNG_SEED | 0; }
  function rng() {
    rngState = (rngState + 0x6D2B79F5) | 0;
    var t = Math.imul(rngState ^ (rngState >>> 15), 1 | rngState);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  }

  function normalRandom(mean, stdDev) {
    var u1 = rng();
    var u2 = rng();
    if (u1 < 1e-10) u1 = 1e-10;
    var z = Math.sqrt(-2 * Math.log(u1)) * Math.cos(2 * Math.PI * u2);
    return mean + stdDev * z;
  }

  function percentile(arr, p) {
    if (!arr.length) return 0;
    var k = (arr.length - 1) * (p / 100);
    var i = Math.floor(k);
    var f = k - i;
    if (i >= arr.length - 1) return arr[arr.length - 1];
    return arr[i] * (1 - f) + arr[i + 1] * f;
  }

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

  function portfolioWithdrawalStartAge(inputs) {
    var start = inputs.portfolioWithdrawalStartAge;
    if (start != null && !isNaN(start) && start > 0) return start;
    return inputs.retirementAge;
  }

  function annualSpendingAtAge(age, inputs) {
    var yearsSinceRetirement = age - inputs.retirementAge;
    return inputs.baseAnnualSpending * Math.pow(1 + inputs.inflation / 100, yearsSinceRetirement);
  }

  function simulateRetirementYear(balanceStart, age, inputs, ssMonthlyAtClaim, returnRate, taxDeferredPct, spouseAge, isSpouseBeneficiary) {
    var taxDeferredBalance = balanceStart * taxDeferredPct;
    var rmd = TR.calculateRMD(age, taxDeferredBalance, isSpouseBeneficiary, spouseAge);
    var spending = annualSpendingAtAge(age, inputs);
    var ssAnnual = annualSocialSecurity(age, inputs, ssMonthlyAtClaim);
    var spouseSsAnnual = annualSpouseSocialSecurity(age, inputs);
    var otherIncome = inputs.otherGuaranteedAnnual;
    var withdrawalStartAge = portfolioWithdrawalStartAge(inputs);
    var spendingGap = Math.max(0, spending - ssAnnual - spouseSsAnnual - otherIncome);
    var spendingGapWithdrawal = age >= withdrawalStartAge ? spendingGap : 0;
    var portfolioWithdrawal = Math.max(rmd, spendingGapWithdrawal);
    if (portfolioWithdrawal > balanceStart) portfolioWithdrawal = balanceStart;
    var balanceEnd = Math.max(0, balanceStart - portfolioWithdrawal) * (1 + returnRate);
    return { balanceEnd: balanceEnd, depleted: balanceEnd <= 0 };
  }

  /**
   * Stress-test the retirement phase using the same spending/SS/RMD rules as the deterministic plan.
   *
   * @param {object} inputs - plan inputs
   * @param {object} deterministic - output from runDeterministicPlan
   * @param {object} options - { expectedReturnPct, volatilityPct, numSims }
   */
  function runRetirementStressTest(inputs, deterministic, options) {
    var startAge = Math.max(inputs.currentAge, inputs.retirementAge, portfolioWithdrawalStartAge(inputs));
    var yearsToModel = inputs.planEndAge - startAge;
    if (yearsToModel <= 0) {
      return {
        successRate: 100,
        numSims: options.numSims || 0,
        p25: 0, p50: 0, p75: 0,
        endingBalances: [],
        startAge: startAge,
        yearsToModel: 0,
        histogram: { labels: [], counts: [] }
      };
    }

    var startRow = deterministic.years.find(function (y) { return y.age === startAge; });
    var startBalance = startRow ? startRow.balanceStart : inputs.balance;

    var ssMonthlyAtClaim = FC.calculateMonthlyBenefit(
      inputs.ssPiaMonthly,
      inputs.birthYear,
      inputs.ssClaimAge
    );

    var taxDeferredPct = FC.clamp(inputs.taxDeferredPct != null ? inputs.taxDeferredPct : 85, 0, 100) / 100;
    var mean = (options.expectedReturnPct || inputs.returnRetirement || 5) / 100;
    var stdDev = (options.volatilityPct || 12) / 100;
    var numSims = FC.clamp(options.numSims || 1000, 100, 5000);

    resetRng();

    var successCount = 0;
    var endingBalances = [];
    var spouseAge = inputs.spouseAge || null;
    var isSpouseBeneficiary = !!inputs.spouseIsBeneficiary;

    for (var s = 0; s < numSims; s++) {
      var balance = startBalance;
      var failed = false;
      var simSpouseAge = spouseAge;

      var yearRets = [];
      for (var yr = 0; yr < yearsToModel; yr++) {
        yearRets.push(normalRandom(mean, stdDev));
      }

      for (var y = 0; y < yearsToModel && !failed; y++) {
        var age = startAge + y;
        var step = simulateRetirementYear(
          balance,
          age,
          inputs,
          ssMonthlyAtClaim,
          yearRets[y],
          taxDeferredPct,
          simSpouseAge,
          isSpouseBeneficiary
        );
        balance = step.balanceEnd;
        if (step.depleted) {
          failed = true;
          endingBalances.push(balance);
        }
        if (simSpouseAge) simSpouseAge++;
      }

      if (!failed) {
        successCount++;
        endingBalances.push(balance);
      }
    }

    endingBalances.sort(function (a, b) { return a - b; });
    var successRate = parseFloat((successCount / numSims * 100).toFixed(1));

    var minB = endingBalances.length ? endingBalances[0] : 0;
    var maxB = endingBalances.length ? endingBalances[endingBalances.length - 1] : 0;
    var bucketCount = 24;
    var range = maxB - minB;
    if (range <= 0) range = 1;
    var step = range / bucketCount;
    var buckets = [];
    var i;
    for (i = 0; i < bucketCount; i++) buckets.push(0);
    endingBalances.forEach(function (v) {
      var idx = Math.min(bucketCount - 1, Math.floor((v - minB) / step));
      if (idx < 0) idx = 0;
      buckets[idx]++;
    });
    var labels = [];
    for (i = 0; i < bucketCount; i++) {
      var lo = minB + i * step;
      var hi = minB + (i + 1) * step;
      labels.push(FC.formatCurrency((lo + hi) / 2));
    }

    return {
      successRate: successRate,
      numSims: numSims,
      p25: percentile(endingBalances, 25),
      p50: percentile(endingBalances, 50),
      p75: percentile(endingBalances, 75),
      endingBalances: endingBalances,
      startAge: startAge,
      startBalance: startBalance,
      yearsToModel: yearsToModel,
      expectedReturnPct: options.expectedReturnPct || inputs.returnRetirement,
      volatilityPct: options.volatilityPct || 12,
      histogram: { labels: labels, counts: buckets }
    };
  }

  global.RBMonteCarlo = {
    runRetirementStressTest: runRetirementStressTest
  };
})(typeof window !== 'undefined' ? window : this);
