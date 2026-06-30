(function () {
  'use strict';

  const resultsEl = document.getElementById('results');
  const summaryBox = document.getElementById('summaryBox');

  // Deterministic PRNG (mulberry32). Seeding the run means every simulation
  // draws the SAME set of market scenarios, so changing one input (e.g.
  // withdrawal timing) is a true apples-to-apples comparison instead of being
  // masked by fresh random sampling each run. Results are reproducible.
  var RNG_SEED = 0x9E3779B9;
  var rngState = RNG_SEED;
  function resetRng() { rngState = RNG_SEED | 0; }
  function rng() {
    rngState = (rngState + 0x6D2B79F5) | 0;
    var t = Math.imul(rngState ^ (rngState >>> 15), 1 | rngState);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  }

  // Approximate normal random (Box-Muller)
  function normalRandom(mean, stdDev) {
    var u1 = rng();
    var u2 = rng();
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

  function formatStartDate() {
    var el = document.getElementById('withdrawalStartDate');
    if (!el || !el.value) return '';
    var p = el.value.split('-');
    if (p.length !== 3) return '';
    var d = new Date(+p[0], +p[1] - 1, +p[2]);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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

    // Use the same scenario set every run so input changes are comparable.
    resetRng();

    var successCount = 0;
    var endingBalances = [];
    var startBalances = [];

    for (var s = 0; s < numSims; s++) {
      var bal = portfolio;
      var failed = false;

      // Pre-draw this path's entire return sequence up front. This keeps the
      // per-path random draw count fixed, so an early failure can't shift the
      // RNG stream — the scenario set stays identical across timing modes and
      // reruns (true common random numbers).
      var delayRets = [];
      for (var dg = 0; dg < fullDelay; dg++) delayRets.push(normalRandom(mean, stdDev));
      var fracRet = fracDelay > 0 ? normalRandom(mean * fracDelay, stdDev * Math.sqrt(fracDelay)) : null;
      var yearRets = [];
      for (var yr = 0; yr < years; yr++) yearRets.push(normalRandom(mean, stdDev));

      // Growth-only phase: portfolio compounds untouched until withdrawals start.
      for (var g = 0; g < fullDelay && !failed; g++) {
        bal = bal * (1 + delayRets[g]);
        if (bal <= 0) { failed = true; endingBalances.push(bal); }
      }
      // Partial first year (e.g. ~6 months); drift/vol already scaled by sqrt(t).
      if (!failed && fracRet !== null) {
        bal = bal * (1 + fracRet);
        if (bal <= 0) { failed = true; endingBalances.push(bal); }
      }

      // Portfolio value at the moment withdrawals begin (after any growth delay).
      startBalances.push(bal);

      // Withdrawal phase.
      for (var y = 0; y < years && !failed; y++) {
        var ret = yearRets[y];
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

    startBalances.sort(function (a, b) { return a - b; });
    var startMedian = percentile(startBalances, 50);
    var startP25 = percentile(startBalances, 25);
    var startP75 = percentile(startBalances, 75);

    function fmt(n) {
      return fmtCurrency(n);
    }

    var delayNote = delayYears > 0.02
      ? ' (after growing untouched for ' + (formatDelay(delayYears).replace(/^In ~/, '~')) + ')'
      : '';
    var startDateLabel = formatStartDate();
    var startBalanceNote = delayYears > 0.02
      ? '<p><strong>Projected portfolio when withdrawals begin' + (startDateLabel ? ' (' + startDateLabel + ')' : '') + ':</strong> median ' + fmt(startMedian) + ' &mdash; 25th&ndash;75th: ' + fmt(startP25) + '&ndash;' + fmt(startP75) + '. Grown untouched from your ' + fmt(portfolio) + ' starting value.</p>'
      : '';
    var timingNote = timing === 'annual' ? 'annually on January 1' : 'monthly';
    summaryBox.innerHTML =
      '<p><strong>Success rate:</strong> Your plan lasted all ' + years + ' years of withdrawals' + delayNote + ' in <strong>' + successRate + '%</strong> of ' + numSims.toLocaleString() + ' simulations.</p>' +
      '<p style="font-size: 13px; color: #4b5563;">Withdrawals taken <strong>' + timingNote + '</strong>.</p>' +
      startBalanceNote +
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
      startMedian,
      successRate,
      p25, p50, p75,
      summary: 'Plan Success (Monte Carlo). Starting portfolio $' + portfolio.toLocaleString() + (delayYears > 0.02 ? ', growing untouched for about ' + (delayYears < 2 ? Math.round(delayYears * 12) + ' months' : delayYears.toFixed(1) + ' years') + ' before withdrawals begin (projected median ' + fmt(startMedian) + ' at that point)' : '') + ', annual withdrawal $' + withdrawal.toLocaleString() + ' (' + (timing === 'annual' ? 'taken annually on January 1' : 'taken monthly') + ') for ' + years + ' years. Expected return ' + expectedReturnPct + '%, volatility ' + volatilityPct + '%. Success rate: ' + successRate + '% of ' + numSims.toLocaleString() + ' simulations. Ending portfolio percentiles: 25th ' + fmt(p25) + ', median ' + fmt(p50) + ', 75th ' + fmt(p75) + '.'
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

  var SCENARIO_TYPE = 'plan-success-monte-carlo';
  var SCENARIO_FIELDS = ['portfolio', 'withdrawal', 'withdrawalTiming', 'inflationRate', 'withdrawalStartDate', 'years', 'expectedReturn', 'volatility', 'simulations'];

  function setSaveStatus(text) {
    var status = document.getElementById('saveStatus');
    if (status) status.textContent = text || '';
  }

  function saveScenario() {
    var name = prompt('Enter a name for this scenario:', 'My Plan');
    if (!name) return;
    var data = {};
    SCENARIO_FIELDS.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) data[id] = el.value;
    });
    setSaveStatus('Saving…');
    fetch('/api/save_scenario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ calculator_type: SCENARIO_TYPE, scenario_name: name, scenario_data: data })
    })
      .then(function (res) { return res.text().then(function (t) { return { ok: res.ok, text: t }; }); })
      .then(function (r) {
        var d;
        try { d = JSON.parse(r.text); } catch (e) { throw new Error('Server returned an unexpected response. Try logging out and back in.'); }
        if (!d.success) throw new Error(d.error || 'Save failed');
        setSaveStatus('✓ Saved!');
        setTimeout(function () { setSaveStatus(''); }, 3000);
      })
      .catch(function (err) {
        setSaveStatus('');
        alert('Save scenario failed: ' + err.message);
      });
  }

  function loadScenario() {
    fetch('/api/load_scenarios.php?calculator_type=' + encodeURIComponent(SCENARIO_TYPE), { credentials: 'same-origin' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success) { alert('Error: ' + data.error); return; }
        if (!data.scenarios || data.scenarios.length === 0) { alert('No saved scenarios yet. Save your first one!'); return; }
        var msg = 'Select a scenario to load (or type "d" + number to delete):\n\n';
        data.scenarios.forEach(function (s, i) {
          msg += (i + 1) + '. ' + s.name + ' (saved ' + new Date(s.updated_at).toLocaleDateString() + ')\n';
        });
        msg += '\nExamples: enter "1" to load, "d1" to delete';
        var choice = prompt(msg);
        if (!choice) return;
        choice = choice.trim();
        if (choice.toLowerCase().charAt(0) === 'd') {
          var di = parseInt(choice.substring(1), 10) - 1;
          if (di < 0 || di >= data.scenarios.length) { alert('Invalid selection.'); return; }
          var del = data.scenarios[di];
          if (!confirm('Delete "' + del.name + '"? This cannot be undone.')) return;
          fetch('/api/delete_scenario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ scenario_id: del.id })
          })
            .then(function (res) { return res.json(); })
            .then(function (r) { alert(r.success ? 'Scenario deleted.' : 'Error: ' + (r.error || 'Unknown error')); })
            .catch(function (err) { alert('Delete failed: ' + err.message); });
          return;
        }
        var idx = parseInt(choice, 10) - 1;
        if (idx < 0 || idx >= data.scenarios.length) { alert('Invalid selection.'); return; }
        var s = data.scenarios[idx];
        var sd = s.data || {};
        Object.keys(sd).forEach(function (key) {
          var el = document.getElementById(key);
          if (el) el.value = sd[key];
        });
        formatAmountField('portfolio');
        formatAmountField('withdrawal');
        updateLabels();
        runMonteCarlo(true);
        setSaveStatus('✓ Loaded "' + s.name + '"');
        setTimeout(function () { setSaveStatus(''); }, 3000);
      })
      .catch(function (err) { alert('Load scenario failed: ' + err.message); });
  }

  var saveBtn = document.getElementById('saveScenarioBtn');
  var loadBtn = document.getElementById('loadScenarioBtn');
  if (saveBtn) saveBtn.addEventListener('click', saveScenario);
  if (loadBtn) loadBtn.addEventListener('click', loadScenario);

  var explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) explainBtn.addEventListener('click', explainResults);

  if (window.RBUrlPrefill) {
    RBUrlPrefill.applyFromUrl({
      portfolio: 'portfolio',
      withdrawal: 'withdrawal',
      years: 'years',
      expectedReturn: 'expectedReturn',
      volatility: 'volatility',
      simulations: 'simulations',
      inflationRate: 'inflationRate'
    }, {
      required: ['fromPlan', 'portfolio'],
      afterApply: function () {
        updateLabels();
        ['portfolio', 'withdrawal'].forEach(function (id) {
          var field = document.getElementById(id);
          if (field) {
            var n = parseFloat(String(field.value).replace(/[^0-9.]/g, ''));
            if (!isNaN(n)) field.value = Math.round(n).toLocaleString('en-US');
          }
        });
        var btn = document.getElementById('runMonteCarloBtn');
        if (btn) btn.click();
      }
    });
  }
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

