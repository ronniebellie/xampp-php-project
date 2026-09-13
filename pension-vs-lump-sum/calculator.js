(function () {
  'use strict';

  const summaryBox = document.getElementById('summaryBox');
  const resultsBody = document.getElementById('resultsBody');

  function formatCurrency(n) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(n);
  }

  function updatePension() {
    try {
    const monthlyPension = parseFloat(document.getElementById('monthlyPension').value) || 0;
    const lumpSum = parseFloat(document.getElementById('lumpSum').value) || 0;
    const currentAge = parseInt(document.getElementById('currentAge').value, 10) || 65;
    const growthRatePct = parseFloat(document.getElementById('growthRate').value);
    const lifeExpectancy = parseInt(document.getElementById('lifeExpectancy').value, 10) || 90;

    document.getElementById('monthlyPensionLabel').textContent = formatCurrency(monthlyPension);
    document.getElementById('lumpSumLabel').textContent = formatCurrency(lumpSum);
    document.getElementById('currentAgeLabel').textContent = currentAge + ' yrs';
    document.getElementById('growthRateLabel').textContent = Number.isFinite(growthRatePct) ? String(growthRatePct) + '%' : 'Invalid rate';
    document.getElementById('lifeExpectancyLabel').textContent = lifeExpectancy;

    const annualPension = 12 * monthlyPension;
    const r = growthRatePct / 100;
    const maxYears = lifeExpectancy - currentAge;
    if (![monthlyPension,lumpSum,r,maxYears].every(Number.isFinite) || monthlyPension < 0 || lumpSum < 0 || r <= -1 || maxYears < 1 || maxYears > 120) throw new RangeError('Enter valid nonnegative amounts, a discount rate above -100%, and a positive planning horizon.');
    const valuation = RBNumerical.pensionPV(annualPension, r, maxYears);

    let breakEvenYear = null;
    let breakEvenAge = null;
    const rows = [];

    for (let t = 1; t <= maxYears; t++) {
      const pensionPV = valuation.rows[t - 1].pensionPV;
      const lumpSumPV = lumpSum;
      const age = currentAge + t;
      rows.push({ year: t, age, pensionPV, lumpSumPV });
      if (breakEvenYear === null && pensionPV >= lumpSumPV) {
        breakEvenYear = t;
        breakEvenAge = age;
      }
    }

    const planYears = lifeExpectancy - currentAge + 2;
    const tableYears = Math.min(Math.max(planYears, (breakEvenYear || 0) + 3), maxYears);
    const displayRows = rows.slice(0, tableYears);

    let summaryHtml = '<p>At valuation age ' + currentAge + ', the present value of ' + maxYears + ' annual end-of-year pension payments is <strong>' + formatCurrency(valuation.value) + '</strong>, compared with <strong>' + formatCurrency(lumpSum) + '</strong> available now.</p>';
    summaryHtml += '<p>Discount rate: ' + growthRatePct + '%. ' + (breakEvenAge === null ? 'No PV crossover within the modeled horizon.' : 'First modeled PV crossover: age ' + breakEvenAge + '.') + ' This is a comparison under fixed assumptions, not a guarantee of permanent superiority. Nominal pension payments total ' + formatCurrency(annualPension * maxYears) + '; this nominal total is not compared with an investment balance. Survivor benefits, taxes and residual legacy assets are not modeled.</p>';
    summaryBox.innerHTML = summaryHtml;

    resultsBody.innerHTML = displayRows.map(function (row) {
      const highlight = row.age === breakEvenAge ? ' style="background: #e0f2fe;"' : '';
      return '<tr' + highlight + '><td>' + row.year + '</td><td>' + row.age + '</td><td>' + formatCurrency(row.pensionPV) + '</td><td>' + formatCurrency(row.lumpSumPV) + '</td></tr>';
    }).join('');

    createComparisonChart(displayRows, breakEvenAge);

    window.lastPensionResult = {
      monthlyPension,
      lumpSum,
      currentAge,
      growthRatePct,
      lifeExpectancy,
      breakEvenYear,
      breakEvenAge,
      annualPension,
      pensionPV: valuation.value,
      valuationAge: currentAge,
      rows: displayRows,
      summary: 'Both alternatives valued at age ' + currentAge + ': pension PV ' + formatCurrency(valuation.value) + ', lump sum ' + formatCurrency(lumpSum) + ', annual end-of-year payments at discount rate ' + growthRatePct + '%.'
    };

    document.getElementById('results').style.display = 'block';
    } catch (error) {
      window.lastPensionResult = null;
      resultsBody.innerHTML = '';
      summaryBox.textContent = error.message;
      if (window.pensionComparisonChart) window.pensionComparisonChart.destroy();
    }
  }

  function createComparisonChart(displayRows, breakEvenAge) {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) return;

    if (window.pensionComparisonChart && typeof window.pensionComparisonChart.destroy === 'function') {
      window.pensionComparisonChart.destroy();
    }

    const labels = displayRows.map(function (r) { return 'Age ' + r.age; });
    const pensionData = displayRows.map(function (r) { return r.pensionPV; });
    const lumpSumData = displayRows.map(function (r) { return r.lumpSumPV; });

    window.pensionComparisonChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Pension present value at valuation age',
            data: pensionData,
            borderColor: '#3182ce',
            backgroundColor: 'rgba(49, 130, 206, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.1,
            pointRadius: breakEvenAge ? displayRows.map(function (r) { return r.age === breakEvenAge ? 6 : 2; }) : 2,
            pointBackgroundColor: breakEvenAge ? displayRows.map(function (r) { return r.age === breakEvenAge ? '#1d4ed8' : '#3182ce'; }) : '#3182ce'
          },
          {
            label: 'Lump sum at valuation age',
            data: lumpSumData,
            borderColor: '#38a169',
            backgroundColor: 'rgba(56, 161, 105, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.1,
            pointRadius: breakEvenAge ? displayRows.map(function (r) { return r.age === breakEvenAge ? 6 : 2; }) : 2,
            pointBackgroundColor: breakEvenAge ? displayRows.map(function (r) { return r.age === breakEvenAge ? '#2f855a' : '#38a169'; }) : '#38a169'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': ' + formatCurrency(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            title: { display: true, text: 'Age' },
            ticks: { maxRotation: 45 }
          },
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Amount ($)' },
            ticks: {
              callback: function (value) {
                if (value >= 1000000) return '$' + (value / 1000000).toFixed(1) + 'M';
                return '$' + (value / 1000).toFixed(0) + 'k';
              }
            }
          }
        }
      }
    });
  }

  ['monthlyPension', 'lumpSum', 'currentAge', 'growthRate', 'lifeExpectancy'].forEach(function (id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updatePension);
  });

  function updateLabelsOnly() {
    const monthlyPension = parseFloat(document.getElementById('monthlyPension').value) || 0;
    const lumpSum = parseFloat(document.getElementById('lumpSum').value) || 0;
    const currentAge = parseInt(document.getElementById('currentAge').value, 10) || 65;
    const growthRatePct = parseFloat(document.getElementById('growthRate').value);
    const lifeExpectancy = parseInt(document.getElementById('lifeExpectancy').value, 10) || 90;
    document.getElementById('monthlyPensionLabel').textContent = formatCurrency(monthlyPension);
    document.getElementById('lumpSumLabel').textContent = formatCurrency(lumpSum);
    document.getElementById('currentAgeLabel').textContent = currentAge + ' yrs';
    document.getElementById('growthRateLabel').textContent = growthRatePct.toFixed(2).replace(/\.?0+$/, '') + '%';
    document.getElementById('lifeExpectancyLabel').textContent = lifeExpectancy;
  }

  document.addEventListener('DOMContentLoaded', updateLabelsOnly);

  var saveBtn = document.getElementById('saveScenarioBtn');
  var loadBtn = document.getElementById('loadScenarioBtn');
  if (saveBtn) saveBtn.addEventListener('click', function () {
    var status = document.getElementById('saveStatus');
    if (status) status.textContent = 'Save/load can be added here.';
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
  var r = window.lastPensionResult;
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
    body: JSON.stringify({ calculator_type: 'pension-vs-lump-sum', results_summary: summary })
  })
  .then(function(res) { return res.text(); })
  .then(function(text) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    var data;
    try { data = JSON.parse(text); } catch (e) {
      throw new Error('Server returned an unexpected response. Try logging out and back in.');
    }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation, { calculatorType: 'pension-vs-lump-sum', resultsSummary: summary });
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}
