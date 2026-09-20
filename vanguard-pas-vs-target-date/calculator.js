(function () {
  'use strict';

  var PAS_API_BASE = (function () {
    var path = window.location.pathname;
    var match = path.match(/^(.*\/)vanguard-pas-vs-target-date\/?/);
    var basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
  })();

  function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  function calculatePortfolio(principal, annualReturnPct, feeRatePct, years, withdrawalPct, withdrawalStartYear) {
    withdrawalPct = withdrawalPct == null ? 0 : withdrawalPct;
    withdrawalStartYear = withdrawalStartYear != null ? withdrawalStartYear : 1;
    if (![principal,annualReturnPct,feeRatePct,years,withdrawalPct,withdrawalStartYear].every(Number.isFinite)||principal<0||annualReturnPct < -100||annualReturnPct>100||feeRatePct<0||withdrawalPct<0||feeRatePct+withdrawalPct>100||!Number.isInteger(years)||years<1||years>120)throw new RangeError('Invalid portfolio projection inputs.');
    var yearlyData = [];
    var balance = principal;
    var totalFees = 0;
    var totalWithdrawals = 0;
    yearlyData.push({ year: 0, balance: balance, fee: 0, totalFees: 0, withdrawal: 0, totalWithdrawals: 0 });
    for (var y = 1; y <= years; y++) {
      balance = balance * (1 + annualReturnPct / 100);
      var yearFee = balance * (feeRatePct / 100);
      var yearWithdrawal = (withdrawalPct > 0 && y >= withdrawalStartYear) ? balance * (withdrawalPct / 100) : 0;
      totalFees += yearFee;
      totalWithdrawals += yearWithdrawal;
      balance = balance - yearFee - yearWithdrawal;
      if(![balance,totalFees,totalWithdrawals].every(Number.isFinite))throw new RangeError('Projection exceeds the supported numerical range.');
      yearlyData.push({
        year: y,
        balance: balance,
        fee: yearFee,
        totalFees: totalFees,
        withdrawal: yearWithdrawal,
        totalWithdrawals: totalWithdrawals
      });
    }
    return yearlyData;
  }

  var bucketKeys = ['conservative', 'moderate', 'aggressive'];
  var bucketNames = ['Conservative', 'Moderate', 'Aggressive'];

  function getNormalizedAllocation() {
    var raw = ['pctConservative', 'pctModerate', 'pctAggressive'].map(function (id) {
      var value = Number(document.getElementById(id).value);
      if (!Number.isFinite(value) || value < 0) throw new RangeError('Allocation percentages must be nonnegative numbers.');
      return value;
    });
    var total = raw.reduce(function (sum, value) { return sum + value; }, 0);
    if (!Number.isFinite(total)) throw new RangeError('Invalid allocation total.');
    if (total === 0) return { c: 33.33, m: 33.33, a: 33.34, sum: 0 };
    // Largest-remainder rounding makes displayed and modeled percentages sum to 100%.
    var exact = raw.map(function (v) { return v / total * 10000; });
    var units = exact.map(Math.floor);
    var remainder = 10000 - units.reduce(function (sum, v) { return sum + v; }, 0);
    [0, 1, 2].sort(function (a, b) { return (exact[b] - units[b]) - (exact[a] - units[a]); })
      .slice(0, remainder).forEach(function (i) { units[i]++; });
    return { c: units[0] / 100, m: units[1] / 100, a: units[2] / 100, sum: total };
  }

  function calculateBuckets(principal, returns, feeRatePct, years, withdrawalPct, withdrawalStartYear, alloc, startYear) {
    // Reuse the established input contract without changing the PAS calculation.
    calculatePortfolio(principal, returns.conservative, feeRatePct, years, withdrawalPct, withdrawalStartYear);
    var weights = [alloc.c, alloc.m, alloc.a];
    var balances = {}, allocated = 0;
    var lastFunded = weights.reduce(function (last, weight, i) { return weight > 0 ? i : last; }, 0);
    bucketKeys.forEach(function (key, i) {
      balances[key] = i === lastFunded ? principal - allocated : principal * weights[i] / 100;
      allocated += balances[key];
    });
    var totalFees = 0, totalWithdrawals = 0;
    var rows = [{ year: 0, calendarYear: startYear, balance: principal, fee: 0, totalFees: 0,
      withdrawal: 0, totalWithdrawals: 0, buckets: Object.assign({}, balances),
      bucketFees: { conservative: 0, moderate: 0, aggressive: 0 },
      bucketWithdrawals: { conservative: 0, moderate: 0, aggressive: 0 } }];
    for (var y = 1; y <= years; y++) {
      var grownTotal = 0, fees = {}, withdrawals = {};
      bucketKeys.forEach(function (key) {
        // Per-bucket return map is intentionally identical for now.
        if (!Number.isFinite(returns[key]) || returns[key] < -100 || returns[key] > 100) throw new RangeError('Invalid bucket return.');
        var grown = balances[key] * (1 + returns[key] / 100);
        grownTotal += grown;
        fees[key] = grown * feeRatePct / 100;
        balances[key] = Math.max(0, grown - fees[key]);
      });
      // Both expenses and requested withdrawal use the post-growth, pre-fee base.
      var requested = y >= withdrawalStartYear ? grownTotal * withdrawalPct / 100 : 0;
      var remaining = requested;
      bucketKeys.forEach(function (key) {
        withdrawals[key] = Math.min(balances[key], remaining);
        balances[key] -= withdrawals[key];
        remaining = Math.max(0, remaining - withdrawals[key]);
      });
      var fee = bucketKeys.reduce(function (sum, key) { return sum + fees[key]; }, 0);
      var withdrawal = bucketKeys.reduce(function (sum, key) { return sum + withdrawals[key]; }, 0);
      var balance = bucketKeys.reduce(function (sum, key) { return sum + balances[key]; }, 0);
      if (!Number.isFinite(balance)) throw new RangeError('Projection exceeds supported amounts.');
      totalFees += fee;
      totalWithdrawals += withdrawal;
      rows.push({ year: y, calendarYear: startYear + y - 1, balance: balance, fee: fee,
        totalFees: totalFees, withdrawal: withdrawal, totalWithdrawals: totalWithdrawals,
        buckets: Object.assign({}, balances), bucketFees: fees, bucketWithdrawals: withdrawals });
    }
    return rows;
  }

  function depletionText(rows, key) {
    if (rows[0].buckets[key] === 0) return 'Not funded at start';
    var depleted = rows.slice(1).find(function (row) { return row.buckets[key] === 0; });
    return depleted ? 'Depleted: ' + depleted.calendarYear : 'Not depleted during simulation';
  }

  var bucketChartInstance = null;
  function renderBuckets(rows, startYear) {
    var summary = document.getElementById('bucketSummary');
    var mid = Math.floor((rows.length - 1) / 2);
    if (summary) summary.innerHTML = bucketKeys.map(function (key, i) {
      return '<tr><th scope="row">' + bucketNames[i] + '</th><td>' + formatCurrency(rows[0].buckets[key]) +
        '</td><td>' + formatCurrency(rows[mid].buckets[key]) + '</td><td>' +
        formatCurrency(rows[rows.length - 1].buckets[key]) + '</td><td>' + depletionText(rows, key) + '</td></tr>';
    }).join('');
    var midLabel = document.getElementById('bucketMidYear');
    if (midLabel) midLabel.textContent = mid === 0 ? 'Start' : 'End of ' + rows[mid].calendarYear;
    var endLabel = document.getElementById('bucketEndYear');
    if (endLabel) endLabel.textContent = 'End of ' + rows[rows.length - 1].calendarYear;
    var ctx = document.getElementById('bucketChart');
    if (!ctx) return;
    if (bucketChartInstance) bucketChartInstance.destroy();
    bucketChartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: { labels: rows.map(function (row) { return row.year === 0 ? 'Start of ' + startYear : 'End of ' + row.calendarYear; }),
        datasets: bucketKeys.map(function (key, i) { return { label: bucketNames[i],
          data: rows.map(function (row) { return row.buckets[key]; }),
          borderColor: ['#2563eb', '#d97706', '#16a34a'][i], borderWidth: 2, tension: 0, fill: false }; }) },
      options: { responsive: true, maintainAspectRatio: false,
        plugins: { tooltip: { mode: 'index', intersect: false,
          callbacks: { label: function (c) { return c.dataset.label + ': ' + formatCurrency(c.parsed.y); } } } },
        scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return formatCurrency(v); } } } } }
    });
  }

  function updateLabels() {
    var portfolio = parseFloat(document.getElementById('portfolioValue').value);
    var years = Number(document.getElementById('years').value);
    var returnRate = parseFloat(document.getElementById('returnRate').value);
    var withdrawalPct = Number(document.getElementById('withdrawalPct').value);
    var cRaw = parseFloat(document.getElementById('pctConservative').value) || 0;
    var mRaw = parseFloat(document.getElementById('pctModerate').value) || 0;
    var aRaw = parseFloat(document.getElementById('pctAggressive').value) || 0;
    var alloc = getNormalizedAllocation();

    var portfolioLabel = document.getElementById('portfolioValueLabel');
    if (portfolioLabel) portfolioLabel.textContent = isNaN(portfolio) ? '' : formatCurrency(portfolio);
    var yearsLabel = document.getElementById('yearsLabel');
    if (yearsLabel) yearsLabel.textContent = isNaN(years) ? '' : years + ' yrs';
    var returnRateLabel = document.getElementById('returnRateLabel');
    if (returnRateLabel) returnRateLabel.textContent = isNaN(returnRate) ? '' : returnRate.toFixed(2).replace(/\.00$/, '') + '%';
    var withdrawalPctLabel = document.getElementById('withdrawalPctLabel');
    if (withdrawalPctLabel) withdrawalPctLabel.textContent = (withdrawalPct === 0 ? '0%' : withdrawalPct.toFixed(1) + '%');

    var pctCL = document.getElementById('pctConservativeLabel');
    var pctML = document.getElementById('pctModerateLabel');
    var pctAL = document.getElementById('pctAggressiveLabel');
    if (pctCL) pctCL.textContent = alloc.c + '%';
    if (pctML) pctML.textContent = alloc.m + '%';
    if (pctAL) pctAL.textContent = alloc.a + '%';

    var sumEl = document.getElementById('allocationSum');
    if (sumEl) {
      var total = cRaw + mRaw + aRaw;
      if (total <= 0) {
        sumEl.textContent = 'All inputs are zero; using 33.33% Conservative / 33.33% Moderate / 33.34% Aggressive (100%).';
      } else if (Math.abs(total - 100) < 0.1) {
        sumEl.textContent = 'Allocation: ' + alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '% (sum = 100%).';
      } else {
        sumEl.textContent = 'Raw sum = ' + total.toFixed(0) + '%. Normalized to 100%: ' + alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '%';
      }
    }
  }

  function calculate(shouldScroll) {
    try { calculateValidated(shouldScroll); } catch(error) {
      window.lastPASvsTargetResult=null;
      document.getElementById('results').style.display='none';
      if(shouldScroll)alert(error.message);
    }
  }

  function calculateValidated(shouldScroll) {
    var portfolioValue = parseFloat(document.getElementById('portfolioValue').value);
    var pasFee = parseFloat(document.getElementById('pasFee').value);
    var targetDateFee = parseFloat(document.getElementById('targetDateFee').value);
    var years = Number(document.getElementById('years').value);
    var returnRate = parseFloat(document.getElementById('returnRate').value);
    var withdrawalPct = Number(document.getElementById('withdrawalPct').value);
    var timelineStartYear = Number(document.getElementById('timelineStartYear').value);
    var withdrawalsStartYear = Number(document.getElementById('withdrawalsStartYear').value);
    if(![timelineStartYear,withdrawalsStartYear].every(v=>Number.isInteger(v)&&v>=1900&&v<=9999))throw new RangeError('Enter valid whole calendar years.');
    var withdrawalStartYear = Math.max(1, withdrawalsStartYear - timelineStartYear + 1);

    updateLabels();

    if (isNaN(portfolioValue) || isNaN(pasFee) || isNaN(years) || isNaN(returnRate)) {
      throw new RangeError('Please enter valid numbers for all fields.');
    }

    var pasData = calculatePortfolio(portfolioValue, returnRate, pasFee, years, withdrawalPct, withdrawalStartYear);
    var alloc = getNormalizedAllocation();
    var targetData = calculateBuckets(portfolioValue,
      { conservative: returnRate, moderate: returnRate, aggressive: returnRate },
      targetDateFee, years, withdrawalPct, withdrawalStartYear, alloc, timelineStartYear);
    var midYear = Math.floor(years / 2);
    var pasFinal = pasData[years].balance;
    var targetFinal = targetData[years].balance;
    var opportunityCost = targetFinal - pasFinal;

    document.getElementById('resultYears').textContent = years;
    document.getElementById('opportunityCost').textContent = formatCurrency(opportunityCost);
    var avgAnnualEl = document.getElementById('avgAnnualCost');
    if (avgAnnualEl) avgAnnualEl.textContent = formatCurrency(years > 0 ? opportunityCost / years : 0);
    document.getElementById('pasFeeResultLabel').textContent = pasFee.toFixed(2) + '% fee';

    document.getElementById('pasYear1Fee').textContent = formatCurrency(pasData[1].fee);
    document.getElementById('targetYear1Fee').textContent = formatCurrency(targetData[1].fee);
    document.getElementById('year1FeeDiff').textContent = formatCurrency(pasData[1].fee - targetData[1].fee);

    document.getElementById('midYearLabel').textContent = midYear;
    document.getElementById('pasMidValue').textContent = formatCurrency(pasData[midYear].balance);
    document.getElementById('targetMidValue').textContent = formatCurrency(targetData[midYear].balance);
    document.getElementById('midValueDiff').textContent = formatCurrency(targetData[midYear].balance - pasData[midYear].balance);

    document.getElementById('finalYearLabel').textContent = years;
    document.getElementById('pasFinalValue').textContent = formatCurrency(pasFinal);
    document.getElementById('targetFinalValue').textContent = formatCurrency(targetFinal);
    document.getElementById('finalValueDiff').textContent = formatCurrency(opportunityCost);

    var pasIncome = pasData[years].totalWithdrawals;
    var targetIncome = targetData[years].totalWithdrawals;
    var incomeRow = document.getElementById('incomeRow');
    if (incomeRow) incomeRow.style.display = withdrawalPct > 0 ? '' : 'none';
    var pasIncomeEl = document.getElementById('pasTotalIncome');
    var targetIncomeEl = document.getElementById('targetTotalIncome');
    var incomeDiffEl = document.getElementById('totalIncomeDiff');
    if (pasIncomeEl) pasIncomeEl.textContent = formatCurrency(pasIncome);
    if (targetIncomeEl) targetIncomeEl.textContent = formatCurrency(targetIncome);
    if (incomeDiffEl) {
      var incomeDiff = targetIncome - pasIncome;
      incomeDiffEl.textContent = (incomeDiff > 0 ? '+' : '') + formatCurrency(incomeDiff);
    }

    var directFeeDiff = pasData[years].totalFees - targetData[years].totalFees;
    document.getElementById('pasTotalFees').textContent = formatCurrency(pasData[years].totalFees);
    document.getElementById('targetTotalFees').textContent = formatCurrency(targetData[years].totalFees);
    document.getElementById('totalFeesDiff').textContent = formatCurrency(directFeeDiff);

    var lostGrowth = opportunityCost - directFeeDiff;
    var lostGrowthDisplay = lostGrowth;
    document.getElementById('insightDirectFees').textContent = formatCurrency(directFeeDiff);
    document.getElementById('insightYears').textContent = years;
    document.getElementById('insightLostGrowth').textContent = formatCurrency(lostGrowthDisplay);
    document.getElementById('insightAllocation').textContent = alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '%';

    var breakdownFeesEl = document.getElementById('breakdownFees');
    var breakdownGrowthEl = document.getElementById('breakdownGrowth');
    var breakdownTotalEl = document.getElementById('breakdownTotal');
    if (breakdownFeesEl) breakdownFeesEl.textContent = formatCurrency(directFeeDiff);
    if (breakdownGrowthEl) breakdownGrowthEl.textContent = formatCurrency(lostGrowthDisplay);
    if (breakdownTotalEl) breakdownTotalEl.textContent = formatCurrency(opportunityCost);

    renderBuckets(targetData, timelineStartYear);
    createChart(pasData, targetData, years);
    createFeesChart(pasData, targetData, years);

    window.lastPASvsTargetResult = {
      portfolioValue: portfolioValue,
      pasFee: pasFee,
      targetDateFee: targetDateFee,
      years: years,
      returnRate: returnRate,
      withdrawalPct: withdrawalPct,
      timelineStartYear: timelineStartYear,
      withdrawalsStartYear: withdrawalsStartYear,
      allocation: { conservative: alloc.c, moderate: alloc.m, aggressive: alloc.a },
      pasData: pasData,
      targetData: targetData,
      opportunityCost: opportunityCost,
      directFeeDiff: directFeeDiff,
      lostGrowth: lostGrowth,
      pasFinal: pasFinal,
      targetFinal: targetFinal
    };

    if (shouldScroll) {
      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  var chartInstance = null;
  var feesChartInstance = null;

  function createChart(pasData, targetData, years) {
    var ctx = document.getElementById('growthChart');
    if (!ctx) return;
    if (chartInstance) chartInstance.destroy();

    var labels = pasData.map(function (d) { return 'Year ' + d.year; });
    var pasValues = pasData.map(function (d) { return d.balance; });
    var targetValues = targetData.map(function (d) { return d.balance; });

    chartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'Vanguard PAS', data: pasValues, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.1)', borderWidth: 3, tension: 0.4, fill: false },
          { label: 'Target Date Three-Bucket Total', data: targetValues, borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, 0.1)', borderWidth: 3, tension: 0.4, fill: false }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: { label: function (c) { return c.dataset.label + ': ' + formatCurrency(c.parsed.y); } }
          }
        },
        scales: {
          y: {
            beginAtZero: false,
            ticks: { callback: function (v) { return formatCurrency(v); } }
          }
        }
      }
    });
  }

  function createFeesChart(pasData, targetData, years) {
    var ctx = document.getElementById('feesChart');
    if (!ctx) return;
    if (feesChartInstance) feesChartInstance.destroy();

    var labels = pasData.map(function (d) { return 'Year ' + d.year; });
    var pasFees = pasData.map(function (d) { return d.totalFees; });
    var targetFees = targetData.map(function (d) { return d.totalFees; });

    feesChartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'PAS Fees', data: pasFees, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.1)', borderWidth: 3, tension: 0.4, fill: true },
          { label: 'Target Date Fees', data: targetFees, borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, 0.1)', borderWidth: 3, tension: 0.4, fill: true }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: { label: function (c) { return c.dataset.label + ': ' + formatCurrency(c.parsed.y); } }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: function (v) { return formatCurrency(v); } }
          }
        }
      }
    });
  }

  document.getElementById('calculateBtn').addEventListener('click', function () { calculate(true); });

  ['portfolioValue', 'years', 'returnRate', 'withdrawalPct', 'timelineStartYear', 'withdrawalsStartYear', 'pctConservative', 'pctModerate', 'pctAggressive'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function () { calculate(false); });
  });

  window.addEventListener('load', function () {
    updateLabels();
  });

  // Hide results whenever the page is shown: first load, refresh, or back/forward (bfcache).
  // This prevents Safari from showing stale calculated values after refresh.
  function hideResultsOnShow() {
    var results = document.getElementById('results');
    if (results) results.style.display = 'none';
  }
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) hideResultsOnShow();
  });
  document.addEventListener('DOMContentLoaded', hideResultsOnShow);

  function saveScenario() {
    var scenarioName = prompt('Enter a name for this scenario:', 'PAS vs Target Date');
    if (!scenarioName) return;
    var formData = {
      portfolioValue: document.getElementById('portfolioValue').value,
      pasFee: document.getElementById('pasFee').value,
      targetDateFee: document.getElementById('targetDateFee').value,
      years: document.getElementById('years').value,
      returnRate: document.getElementById('returnRate').value,
      withdrawalPct: document.getElementById('withdrawalPct').value,
      timelineStartYear: document.getElementById('timelineStartYear').value,
      withdrawalsStartYear: document.getElementById('withdrawalsStartYear').value,
      pctConservative: document.getElementById('pctConservative').value,
      pctModerate: document.getElementById('pctModerate').value,
      pctAggressive: document.getElementById('pctAggressive').value
    };
    rbScenarioFetch(PAS_API_BASE + 'api/save_scenario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        calculator_type: 'vanguard-pas-vs-target-date',
        scenario_name: scenarioName,
        scenario_data: formData
      })
    })
    .then(function (res) { return res.text().then(function (text) { return { ok: res.ok, status: res.status, text: text }; }); })
    .then(function (out) {
      var data;
      try { data = JSON.parse(out.text); } catch (_) { throw new Error(out.text || 'Server error'); }
      if (!out.ok) throw new Error(data.error || 'Save failed');
      return data;
    })
    .then(function (data) {
      var status = document.getElementById('saveStatus');
      if (status) status.textContent = 'Saved!';
      if (status) setTimeout(function () { status.textContent = ''; }, 3000);
    })
    .catch(function (err) { alert('Save scenario failed: ' + err.message); });
  }

  function scenarioDisplayName(scenario) {
    return (scenario && (scenario.scenario_name || scenario.name)) || 'Untitled scenario';
  }

  function loadScenario() {
    fetch(PAS_API_BASE + 'api/load_scenarios.php?calculator_type=vanguard-pas-vs-target-date', { credentials: 'include' })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.success) {
        alert('Error: ' + (data.error || 'Unknown error'));
        return;
      }
      if (!data.scenarios || data.scenarios.length === 0) {
        alert('No saved scenarios yet. Save your first one!');
        return;
      }
      var message = 'Select a scenario to load (or type "d" + number to delete):\n\n';
      data.scenarios.forEach(function (s, i) {
        message += (i + 1) + '. ' + scenarioDisplayName(s) + ' (saved ' + new Date(s.updated_at).toLocaleDateString() + ')\n';
      });
      message += '\nExamples: Enter "1" to load, "d1" to delete';
      var choice = prompt(message + '\n\nEnter number or d+number:');
      if (!choice) return;
      var index;
      if (choice.toLowerCase().indexOf('d') === 0) {
        index = parseInt(choice.substring(1), 10) - 1;
        if (index >= 0 && index < data.scenarios.length && confirm('Delete "' + scenarioDisplayName(data.scenarios[index]) + '"? This cannot be undone.')) {
          rbScenarioFetch(PAS_API_BASE + 'api/delete_scenario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ scenario_id: data.scenarios[index].id })
          })
          .then(function (r) { return r.json(); })
          .then(function (result) {
            if (result.success) alert('Scenario deleted!');
            else alert('Error: ' + result.error);
          });
        }
        return;
      }
      index = parseInt(choice, 10) - 1;
      if (index >= 0 && index < data.scenarios.length) {
        var scenario = data.scenarios[index];
        var d = scenario.data || {};
        ['portfolioValue', 'pasFee', 'targetDateFee', 'years', 'returnRate', 'withdrawalPct', 'timelineStartYear', 'withdrawalsStartYear', 'pctConservative', 'pctModerate', 'pctAggressive'].forEach(function (key) {
          var el = document.getElementById(key);
          if (el && d[key] !== undefined) el.value = d[key];
        });
        updateLabels();
        alert('Scenario loaded! Click "Calculate True Cost" to see results.');
      }
    })
    .catch(function (err) { alert('Load scenarios failed: ' + err.message); });
  }

  function downloadPDF() {
    var r = window.lastPASvsTargetResult;
    if (!r) {
      alert('Please run Calculate first, then download the PDF.');
      return;
    }
    var chartImage1 = null;
    var chartImage2 = null;
    try {
      var chartCanvas1 = document.getElementById('growthChart');
      var chartCanvas2 = document.getElementById('feesChart');
      if (chartCanvas1 && typeof chartCanvas1.toDataURL === 'function') chartImage1 = chartCanvas1.toDataURL('image/png');
      if (chartCanvas2 && typeof chartCanvas2.toDataURL === 'function') chartImage2 = chartCanvas2.toDataURL('image/png');
    } catch (e) {
      /* charts optional; continue without */
    }
    var payload = {
      allocation: r.allocation,
      portfolioValue: r.portfolioValue,
      pasFee: r.pasFee,
      targetDateFee: r.targetDateFee,
      years: r.years,
      returnRate: r.returnRate,
      withdrawalPct: r.withdrawalPct,
      timelineStartYear: r.timelineStartYear,
      withdrawalsStartYear: r.withdrawalsStartYear,
      opportunityCost: r.opportunityCost,
      directFeeDiff: r.directFeeDiff,
      lostGrowth: r.lostGrowth,
      pasFinal: r.pasFinal,
      targetFinal: r.targetFinal,
      pasData: r.pasData,
      targetData: r.targetData,
      chartImage1: chartImage1,
      chartImage2: chartImage2
    };
    var url = PAS_API_BASE + 'api/generate_pas_pdf.php';
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload) })
    .then(function (res) {
      var ct = (res.headers.get('Content-Type') || '').toLowerCase();
      if (!res.ok) {
        return res.text().then(function (t) {
          try { var j = JSON.parse(t); throw new Error(j.error || 'PDF failed'); } catch (err) {
            if (err.message && err.message !== 'PDF failed') throw err;
            throw new Error((t && t.length > 150 ? t.slice(0, 150) + '…' : t) || 'PDF failed');
          }
        });
      }
      if (ct.indexOf('application/pdf') === -1) {
        return res.text().then(function (t) { throw new Error((t && t.length > 150 ? t.slice(0, 150) + '…' : t) || 'Server did not return a PDF.'); });
      }
      return res.blob();
    })
    .then(function (blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'Vanguard_PAS_vs_Target_Date_' + new Date().toISOString().split('T')[0] + '.pdf';
      a.click();
      URL.revokeObjectURL(a.href);
    })
    .catch(function (e) { alert('Download PDF: ' + (e && e.message ? e.message : String(e))); });
  }

  function downloadCSV() {
    var r = window.lastPASvsTargetResult;
    if (!r) {
      alert('Please run Calculate first, then export CSV.');
      return;
    }
    var payload = Object.assign({}, r);
    fetch(PAS_API_BASE + 'api/export_pas_csv.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload) })
    .then(function (res) {
      if (!res.ok) return res.text().then(function (t) { try { var j = JSON.parse(t); throw new Error(j.error || 'CSV failed'); } catch (e) { throw new Error(t || 'CSV failed'); } });
      var ct = res.headers.get('Content-Type') || '';
      if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) throw new Error('Server did not return CSV.');
      return res.blob();
    })
    .then(function (blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'Vanguard_PAS_vs_Target_Date_' + new Date().toISOString().split('T')[0] + '.csv';
      a.click();
      URL.revokeObjectURL(a.href);
    })
    .catch(function (e) { alert('Export CSV: ' + e.message); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var saveBtn = document.getElementById('saveScenarioBtn');
    var loadBtn = document.getElementById('loadScenarioBtn');
    var pdfBtn = document.getElementById('downloadPdfBtn');
    var csvBtn = document.getElementById('downloadCsvBtn');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    var explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) explainBtn.addEventListener('click', explainPASResults);
  });
})();

function explainPASResults() {
  var r = window.lastPASvsTargetResult;
  if (!r) {
    alert('Please run the calculation first to see results.');
    return;
  }
  var totalOpportunityCost = Math.round(r.opportunityCost);
  var directFeeDiff = Math.round(r.directFeeDiff);
  var lostGrowth = Math.round(r.lostGrowth);

  var summary = 'Vanguard Personal Advisor vs Target Date Funds. Portfolio $' + r.portfolioValue.toLocaleString() + ', PAS fee ' + r.pasFee + '%, Target Date fee ' + r.targetDateFee + '%. ';
  summary += 'Timeline ' + r.years + ' years, expected return ' + r.returnRate + '%. ';
  if (r.withdrawalPct > 0) summary += 'Annual withdrawal ' + r.withdrawalPct + '% of each alternative’s current post-growth, pre-fee balance, starting ' + (r.withdrawalsStartYear != null ? r.withdrawalsStartYear : 'year 1') + '. ';
  summary += 'Allocation: ' + r.allocation.conservative + '% conservative, ' + r.allocation.moderate + '% moderate, ' + r.allocation.aggressive + '% aggressive.\n\n';
  summary += 'Withdrawals use Conservative, then Moderate, then Aggressive, with no replenishment. All buckets use the same gross return and fund expense; sequencing alone does not change the total. PAS uses the entered total advisory and underlying fund cost, applied once.\n';
  r.targetData[0] && Object.keys(r.targetData[0].buckets).forEach(function (key) {
    var depleted = r.targetData.slice(1).find(function (row) { return row.buckets[key] === 0; });
    summary += key + ' ending balance: $' + Math.round(r.targetData[r.years].buckets[key]) + '; ' + (r.targetData[0].buckets[key] === 0 ? 'not funded at start' : depleted ? 'depleted ' + depleted.calendarYear : 'not depleted during simulation') + '. ';
  });
  summary += 'OPPORTUNITY COST BREAKDOWN (do not double-count):\n';
  summary += '- Total Opportunity Cost (grand total): $' + totalOpportunityCost.toLocaleString() + '\n';
  summary += '- Direct Fee Difference (paid out of pocket): $' + directFeeDiff.toLocaleString() + '\n';
  summary += '- Growth and withdrawal effects: $' + lostGrowth.toLocaleString() + '\n';
  summary += 'Relationship: Total Opportunity Cost = Direct Fee Difference + Growth and withdrawal effects ($' +
    directFeeDiff.toLocaleString() + ' + $' + lostGrowth.toLocaleString() + ' = $' +
    totalOpportunityCost.toLocaleString() + '). The total is NOT an additional separate cost on top of the two components.';

  var btn = document.getElementById('explainResultsBtnInResults');
  var origText = btn ? btn.textContent : '';
  if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

  var explainUrl = (window.location.origin || '') + '/api/explain_results.php';
  window.rbExplainFetch(explainUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ calculator_type: 'vanguard-pas-vs-target-date', results_summary: summary })
  })
  .then(function (res) { return res.text(); })
  .then(function (text) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    var data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Server returned an unexpected response.'); }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation, { calculatorType: 'vanguard-pas-vs-target-date', resultsSummary: summary });
  })
  .catch(function (err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}
