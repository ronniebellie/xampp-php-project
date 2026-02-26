(function () {
  'use strict';

  const form = document.getElementById('planForm');
  const resultsEl = document.getElementById('results');
  const summaryBox = document.getElementById('summaryBox');

  // Approximate normal random (Box-Muller)
  function normalRandom(mean, stdDev) {
    var u1 = Math.random();
    var u2 = Math.random();
    if (u1 < 1e-10) u1 = 1e-10;
    var z = Math.sqrt(-2 * Math.log(u1)) * Math.cos(2 * Math.PI * u2);
    return mean + stdDev * z;
  }

  var LIMITS = {
    portfolio: { min: 0, max: 500000000 },
    withdrawal: { min: 0, max: 5000000 },
    years: { min: 5, max: 50 },
    expectedReturn: { min: 0, max: 20 },
    volatility: { min: 0, max: 50 },
    simulations: { min: 100, max: 10000 },
    inflationRate: { min: 0, max: 10 }
  };

  function validateInputs() {
    var portfolio = parseFloat(document.getElementById('portfolio').value);
    var withdrawal = parseFloat(document.getElementById('withdrawal').value);
    var years = parseInt(document.getElementById('years').value, 10);
    var expectedReturnPct = parseFloat(document.getElementById('expectedReturn').value);
    var volatilityPct = parseFloat(document.getElementById('volatility').value);
    var numSims = parseInt(document.getElementById('simulations').value, 10);
    var inflationRatePct = parseFloat(document.getElementById('inflationRate').value);
    var err = [];
    if (isNaN(portfolio) || portfolio < LIMITS.portfolio.min || portfolio > LIMITS.portfolio.max) err.push('Starting portfolio: $0 to $500,000,000');
    if (isNaN(withdrawal) || withdrawal < LIMITS.withdrawal.min || withdrawal > LIMITS.withdrawal.max) err.push('Annual withdrawal: $0 to $5,000,000');
    if (isNaN(years) || years < LIMITS.years.min || years > LIMITS.years.max) err.push('Years to model: 5 to 50');
    if (isNaN(expectedReturnPct) || expectedReturnPct < LIMITS.expectedReturn.min || expectedReturnPct > LIMITS.expectedReturn.max) err.push('Expected return: 0% to 20%');
    if (isNaN(volatilityPct) || volatilityPct < LIMITS.volatility.min || volatilityPct > LIMITS.volatility.max) err.push('Volatility: 0% to 50%');
    if (isNaN(numSims) || numSims < LIMITS.simulations.min || numSims > LIMITS.simulations.max) err.push('Simulations: 100 to 10,000');
    if (isNaN(inflationRatePct) || inflationRatePct < LIMITS.inflationRate.min || inflationRatePct > LIMITS.inflationRate.max) err.push('Inflation rate: 0% to 10%');
    return { err: err, portfolio: portfolio, withdrawal: withdrawal, years: years, expectedReturnPct: expectedReturnPct, volatilityPct: volatilityPct, numSims: numSims, inflationRatePct: inflationRatePct };
  }

  function runMonteCarlo() {
    var validationEl = document.getElementById('validationError');
    var v = validateInputs();
    if (v.err.length > 0) {
      if (validationEl) {
        validationEl.style.display = 'block';
        validationEl.textContent = 'Please keep inputs in these ranges: ' + v.err.join('; ') + '.';
      }
      return;
    }
    if (validationEl) validationEl.style.display = 'none';

    var portfolio = v.portfolio;
    var withdrawal = v.withdrawal;
    var years = v.years;
    var expectedReturnPct = v.expectedReturnPct;
    var volatilityPct = v.volatilityPct;
    var numSims = v.numSims;
    var inflationRatePct = v.inflationRatePct;

    var mean = expectedReturnPct / 100;
    var stdDev = volatilityPct / 100;
    var infl = inflationRatePct / 100;

    var successCount = 0;
    var endingBalances = [];

    for (var s = 0; s < numSims; s++) {
      var bal = portfolio;
      var failed = false;
      for (var y = 0; y < years; y++) {
        var ret = normalRandom(mean, stdDev);
        var withdrawalThisYear = withdrawal;
        if (infl > 0) {
          withdrawalThisYear = withdrawal * Math.pow(1 + infl, y);
        }
        bal = (bal - withdrawalThisYear) * (1 + ret);
        if (bal <= 0) {
          failed = true;
          endingBalances.push(bal);
          break;
        }
      }
      if (!failed) {
        successCount++;
        endingBalances.push(bal);
      }
    }

    var successRate = (successCount / numSims * 100).toFixed(1);
    endingBalances.sort(function (a, b) { return a - b; });

    function percentile(arr, p) {
      if (!arr.length) return 0;
      var k = (arr.length - 1) * (p / 100);
      var i = Math.floor(k);
      var f = k - i;
      if (i >= arr.length - 1) return arr[arr.length - 1];
      return arr[i] * (1 - f) + arr[i + 1] * f;
    }

    var p25 = percentile(endingBalances, 25);
    var p50 = percentile(endingBalances, 50);
    var p75 = percentile(endingBalances, 75);

    function fmt(n) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(n);
    }

    summaryBox.innerHTML =
      '<p><strong>Success rate:</strong> Your plan lasted all ' + years + ' years in <strong>' + successRate + '%</strong> of ' + numSims.toLocaleString() + ' simulations.</p>' +
      '<p><strong>Ending portfolio percentiles:</strong> 25th = ' + fmt(p25) + ', 50th (median) = ' + fmt(p50) + ', 75th = ' + fmt(p75) + '.</p>' +
      '<p>Lower percentiles include runs that ran out of money (negative ending balance).</p>';

    // Histogram: bucket ending balances
    var minB = Math.min.apply(null, endingBalances);
    var maxB = Math.max.apply(null, endingBalances);
    var bucketCount = 30;
    var range = maxB - minB;
    if (range <= 0) range = 1;
    var step = range / bucketCount;
    var buckets = [];
    for (var b = 0; b < bucketCount; b++) buckets.push(0);
    endingBalances.forEach(function (v) {
      var idx = Math.min(bucketCount - 1, Math.floor((v - minB) / step));
      if (idx < 0) idx = 0;
      buckets[idx]++;
    });
    var labels = [];
    for (var i = 0; i < bucketCount; i++) {
      var lo = minB + i * step;
      var hi = minB + (i + 1) * step;
      labels.push(fmt((lo + hi) / 2));
    }

    createChart(labels, buckets, numSims);

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  var chartInstance = null;

  function createChart(labels, data, numSims) {
    var ctx = document.getElementById('distributionChart');
    if (!ctx) return;
    if (chartInstance && typeof chartInstance.destroy === 'function') chartInstance.destroy();

    chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Number of outcomes',
          data: data,
          backgroundColor: 'rgba(49, 130, 206, 0.6)',
          borderColor: '#3182ce',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (c) { return c.raw + ' of ' + numSims + ' runs'; }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            title: { display: true, text: 'Number of simulations' }
          },
          y: {
            title: { display: true, text: 'Ending portfolio' },
            ticks: { maxTicksLimit: 20 }
          }
        }
      }
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    runMonteCarlo();
  });

  var saveBtn = document.getElementById('saveScenarioBtn');
  var loadBtn = document.getElementById('loadScenarioBtn');
  if (saveBtn) saveBtn.addEventListener('click', function () {
    var status = document.getElementById('saveStatus');
    if (status) status.textContent = 'Save/load can be added.';
  });
  if (loadBtn) loadBtn.addEventListener('click', function () {
    var status = document.getElementById('saveStatus');
    if (status) status.textContent = '';
  });
})();
