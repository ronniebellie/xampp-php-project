(function () {
  'use strict';

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
    portfolio: { min: 1000, max: 50000000 },
    withdrawal: { min: 0, max: 5000000 },
    years: { min: 5, max: 50 },
    expectedReturn: { min: 0, max: 20 },
    volatility: { min: 0, max: 50 },
    simulations: { min: 100, max: 10000 },
    inflationRate: { min: 0, max: 10 },
    delayYears: { min: 0, max: 40 }
  };

  function fmtCurrency(n) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(n);
  }

  // Read a dollar amount from a free-text field, ignoring $, commas, spaces.
  function parseAmount(id) {
    var el = document.getElementById(id);
    if (!el) return NaN;
    var raw = String(el.value).replace(/[^0-9.]/g, '');
    if (raw === '') return NaN;
    return parseFloat(raw);
  }

  // Re-display a dollar field with thousands separators (whole dollars).
  function formatAmountField(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var n = parseAmount(id);
    el.value = isNaN(n) ? '' : Math.round(n).toLocaleString('en-US');
  }

  function localTodayISO() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + day;
  }

  // Fractional years from today until the chosen "withdrawals start" date.
  // Past/empty dates mean withdrawals start now (0). Capped for sanity.
  function getDelayYears() {
    var el = document.getElementById('withdrawalStartDate');
    if (!el || !el.value) return 0;
    var p = el.value.split('-');
    if (p.length !== 3) return 0;
    var start = new Date(+p[0], +p[1] - 1, +p[2]);
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var yrs = (start.getTime() - today.getTime()) / (365.25 * 24 * 3600 * 1000);
    if (isNaN(yrs) || yrs < 0) return 0;
    return Math.min(yrs, LIMITS.delayYears.max);
  }

  function formatDelay(yrs) {
    if (yrs <= 0.02) return 'Starts now';
    var months = Math.round(yrs * 12);
    if (months < 24) return 'In ~' + months + ' mo';
    return 'In ~' + yrs.toFixed(1) + ' yrs';
  }

  function getTiming() {
    var el = document.getElementById('withdrawalTiming');
    return el && el.value === 'annual' ? 'annual' : 'monthly';
  }

  function updateLabels() {
    var inflationRatePct = parseFloat(document.getElementById('inflationRate').value);
    var years = parseInt(document.getElementById('years').value, 10);
    var expectedReturnPct = parseFloat(document.getElementById('expectedReturn').value);
    var volatilityPct = parseFloat(document.getElementById('volatility').value);
    var numSims = parseInt(document.getElementById('simulations').value, 10);
    var inflationRateLabel = document.getElementById('inflationRateLabel');
    var yearsLabel = document.getElementById('yearsLabel');
    var delayYearsLabel = document.getElementById('delayYearsLabel');
    var expectedReturnLabel = document.getElementById('expectedReturnLabel');
    var volatilityLabel = document.getElementById('volatilityLabel');
    var simulationsLabel = document.getElementById('simulationsLabel');
    if (inflationRateLabel) inflationRateLabel.textContent = isNaN(inflationRatePct) ? '' : inflationRatePct.toFixed(1) + '%';
    if (yearsLabel) yearsLabel.textContent = isNaN(years) ? '' : years + ' yrs';
    if (delayYearsLabel) delayYearsLabel.textContent = formatDelay(getDelayYears());
    if (expectedReturnLabel) expectedReturnLabel.textContent = isNaN(expectedReturnPct) ? '' : expectedReturnPct.toFixed(2).replace(/\.00$/, '') + '%';
    if (volatilityLabel) volatilityLabel.textContent = isNaN(volatilityPct) ? '' : volatilityPct.toFixed(1) + '%';
    if (simulationsLabel) simulationsLabel.textContent = isNaN(numSims) ? '' : numSims.toLocaleString() + ' sims';
  }

  function validateInputs() {
    var portfolio = parseAmount('portfolio');
    var withdrawal = parseAmount('withdrawal');
    var years = parseInt(document.getElementById('years').value, 10);
    var expectedReturnPct = parseFloat(document.getElementById('expectedReturn').value);
    var volatilityPct = parseFloat(document.getElementById('volatility').value);
    var numSims = parseInt(document.getElementById('simulations').value, 10);
    var inflationRatePct = parseFloat(document.getElementById('inflationRate').value);
    var err = [];
    if (isNaN(portfolio) || portfolio < LIMITS.portfolio.min || portfolio > LIMITS.portfolio.max) err.push('Starting portfolio: $1,000 to $50,000,000');
    if (isNaN(withdrawal) || withdrawal < LIMITS.withdrawal.min || withdrawal > LIMITS.withdrawal.max) err.push('Annual withdrawal: $0 to $5,000,000');
    if (isNaN(years) || years < LIMITS.years.min || years > LIMITS.years.max) err.push('Years to model: 5 to 50');
    if (isNaN(expectedReturnPct) || expectedReturnPct < LIMITS.expectedReturn.min || expectedReturnPct > LIMITS.expectedReturn.max) err.push('Expected return: 0% to 20%');
    if (isNaN(volatilityPct) || volatilityPct < LIMITS.volatility.min || volatilityPct > LIMITS.volatility.max) err.push('Volatility: 0% to 50%');
    if (isNaN(numSims) || numSims < LIMITS.simulations.min || numSims > LIMITS.simulations.max) err.push('Simulations: 100 to 10,000');
    if (isNaN(inflationRatePct) || inflationRatePct < LIMITS.inflationRate.min || inflationRatePct > LIMITS.inflationRate.max) err.push('Inflation rate: 0% to 10%');
    return { err: err, portfolio: portfolio, withdrawal: withdrawal, years: years, expectedReturnPct: expectedReturnPct, volatilityPct: volatilityPct, numSims: numSims, inflationRatePct: inflationRatePct, delayYears: getDelayYears(), timing: getTiming() };
  }

  function runMonteCarlo(shouldScroll) {
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
    var delayYears = v.delayYears;
    var timing = v.timing;

    var mean = expectedReturnPct / 100;
    var stdDev = volatilityPct / 100;
    var infl = inflationRatePct / 100;

    var fullDelay = Math.floor(delayYears);
    var fracDelay = delayYears - fullDelay;

    var successCount = 0;
    var endingBalances = [];

    for (var s = 0; s < numSims; s++) {
      var bal = portfolio;
      var failed = false;

      // Growth-only phase: portfolio compounds untouched until withdrawals start.
      for (var g = 0; g < fullDelay && !failed; g++) {
        bal = bal * (1 + normalRandom(mean, stdDev));
        if (bal <= 0) { failed = true; endingBalances.push(bal); }
      }
      // Partial first year (e.g. ~6 months). Scale drift/vol by sqrt of time.
      if (!failed && fracDelay > 0) {
        bal = bal * (1 + normalRandom(mean * fracDelay, stdDev * Math.sqrt(fracDelay)));
        if (bal <= 0) { failed = true; endingBalances.push(bal); }
      }

      // Withdrawal phase.
      for (var y = 0; y < years && !failed; y++) {
        var ret = normalRandom(mean, stdDev);
        var withdrawalThisYear = withdrawal;
        if (infl > 0) {
          withdrawalThisYear = withdrawal * Math.pow(1 + infl, y);
        }
        if (timing === 'annual') {
          // Full year's withdrawal taken on Jan 1, remainder grows all year.
          bal = (bal - withdrawalThisYear) * (1 + ret);
          if (bal <= 0) {
            failed = true;
            endingBalances.push(bal);
          }
        } else {
          // Monthly: take 1/12 at the start of each month, grow by the
          // monthly-equivalent of the same annual return draw.
          var oneplus = 1 + ret;
          var mFactor = oneplus <= 0 ? 0 : Math.pow(oneplus, 1 / 12);
          var monthlyW = withdrawalThisYear / 12;
          for (var m = 0; m < 12 && !failed; m++) {
            bal = (bal - monthlyW) * mFactor;
            if (bal <= 0) {
              failed = true;
              endingBalances.push(bal);
            }
          }
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
      return fmtCurrency(n);
    }

    var delayNote = delayYears > 0.02
      ? ' (after growing untouched for ' + (formatDelay(delayYears).replace(/^In ~/, '~')) + ')'
      : '';
    var timingNote = timing === 'annual' ? 'annually on January 1' : 'monthly';
    summaryBox.innerHTML =
      '<p><strong>Success rate:</strong> Your plan lasted all ' + years + ' years of withdrawals' + delayNote + ' in <strong>' + successRate + '%</strong> of ' + numSims.toLocaleString() + ' simulations.</p>' +
      '<p style="font-size: 13px; color: #4b5563;">Withdrawals taken <strong>' + timingNote + '</strong>.</p>' +
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

    window.lastPlanSuccessResult = {
      portfolio,
      withdrawal,
      years,
      expectedReturnPct,
      volatilityPct,
      numSims,
      inflationRatePct,
      delayYears,
      timing,
      successRate,
      p25, p50, p75,
      summary: 'Plan Success (Monte Carlo). Starting portfolio $' + portfolio.toLocaleString() + (delayYears > 0.02 ? ', growing untouched for about ' + (delayYears < 2 ? Math.round(delayYears * 12) + ' months' : delayYears.toFixed(1) + ' years') + ' before withdrawals begin' : '') + ', annual withdrawal $' + withdrawal.toLocaleString() + ' (' + (timing === 'annual' ? 'taken annually on January 1' : 'taken monthly') + ') for ' + years + ' years. Expected return ' + expectedReturnPct + '%, volatility ' + volatilityPct + '%. Success rate: ' + successRate + '% of ' + numSims.toLocaleString() + ' simulations. Ending portfolio percentiles: 25th ' + fmt(p25) + ', median ' + fmt(p50) + ', 75th ' + fmt(p75) + '.'
    };

    if (shouldScroll) {
      resultsEl.style.display = 'block';
      resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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

  var runBtn = document.getElementById('runMonteCarloBtn');
  if (runBtn) runBtn.addEventListener('click', function () { runMonteCarlo(true); });

  var inputIds = ['portfolio', 'withdrawal', 'inflationRate', 'years', 'expectedReturn', 'volatility', 'simulations', 'withdrawalStartDate', 'withdrawalTiming'];
  var runTimeout = null;
  function scheduleRun() {
    updateLabels();
    if (runTimeout) clearTimeout(runTimeout);
    runTimeout = setTimeout(function () {
      runTimeout = null;
      runMonteCarlo(false);
    }, 400);
  }
  inputIds.forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', scheduleRun);
  });

  ['portfolio', 'withdrawal'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('blur', function () { formatAmountField(id); });
      formatAmountField(id);
    }
  });

  var startDateEl = document.getElementById('withdrawalStartDate');
  if (startDateEl) {
    startDateEl.min = localTodayISO();
    if (!startDateEl.value) startDateEl.value = localTodayISO();
    startDateEl.addEventListener('change', scheduleRun);
  }

  var timingEl = document.getElementById('withdrawalTiming');
  if (timingEl) timingEl.addEventListener('change', scheduleRun);

  updateLabels();

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

  var explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) explainBtn.addEventListener('click', explainResults);
})();

function escapeHtml(s) {
  var div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function explainResults() {
  var r = window.lastPlanSuccessResult;
  if (!r) {
    alert('Please run the calculation first to see results.');
    return;
  }
  var summary = r.summary;

  var btn = document.getElementById('explainResultsBtnInResults');
  var origText = btn ? btn.textContent : '';
  if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

  var explainUrl = (window.location.origin || '') + '/api/explain_results.php';
  fetch(explainUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ calculator_type: 'plan-success-monte-carlo', results_summary: summary })
  })
  .then(function(res) { return res.text(); })
  .then(function(text) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    var data;
    try { data = JSON.parse(text); } catch (e) {
      throw new Error('Server returned an unexpected response. Try logging out and back in.');
    }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation, { calculatorType: 'plan-success', resultsSummary: summary });
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

