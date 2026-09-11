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

  /**
   * Stress-test whether portfolio withdrawals can fund the spending gap through plan end age.
   *
   * @param {object} inputs - plan inputs
   * @param {object} deterministic - output from runDeterministicPlan
   * @param {object} options - { expectedReturnPct, volatilityPct, numSims }
   */
  function runRetirementStressTest(inputs, deterministic, options) {
    var withdrawalStartAge = inputs.portfolioWithdrawalStartAge || inputs.retirementAge;
    var startAge = Math.max(inputs.currentAge, inputs.retirementAge);
    var yearsToModel = inputs.planEndAge - startAge + 1;
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
    var firstGapRow = deterministic.years.find(function (y) {
      return y.age >= withdrawalStartAge && y.requestedSpending > 0;
    });
    var initialGapWithdrawal = 0;
    if (firstGapRow) {
      initialGapWithdrawal = Math.max(0, firstGapRow.requestedSpending - (firstGapRow.socialSecurity || 0) -
        (firstGapRow.otherIncome || 0));
    }
    var gapWithdrawalRatePct = startBalance > 0
      ? parseFloat((initialGapWithdrawal / startBalance * 100).toFixed(2))
      : 0;

    var expectedReturnPct = options.expectedReturnPct != null ? options.expectedReturnPct : inputs.returnRetirement;
    var volatilityPct = options.volatilityPct != null ? options.volatilityPct : 12;
    var mean = expectedReturnPct / 100;
    var stdDev = volatilityPct / 100;
    var numSims = FC.clamp(options.numSims || 1000, 100, 5000);

    resetRng();

    var successCount = 0;
    var endingBalances = [];

    for (var s = 0; s < numSims; s++) {
      // Reuse the complete annual ledger: taxes, RMDs, delayed draws and surplus.
      // Accumulation remains deterministic; only retirement returns are sampled.
      var simulation = global.RBPlanEngine.runDeterministicPlan(inputs, function () {
        return Math.max(-100, normalRandom(mean, stdDev) * 100);
      });
      if (simulation.summary.shortfallAge === null) successCount++;
      endingBalances.push(simulation.summary.endingBalance);
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
      initialGapWithdrawal: initialGapWithdrawal,
      gapWithdrawalRatePct: gapWithdrawalRatePct,
      yearsToModel: yearsToModel,
      expectedReturnPct: expectedReturnPct,
      volatilityPct: volatilityPct,
      histogram: { labels: labels, counts: buckets }
    };
  }

  global.RBMonteCarlo = {
    runRetirementStressTest: runRetirementStressTest
  };
})(typeof window !== 'undefined' ? window : this);
