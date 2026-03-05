(function () {
  'use strict';

  const form = document.getElementById('pensionForm');
  const resultsEl = document.getElementById('results');
  const summaryBox = document.getElementById('summaryBox');
  const resultsBody = document.getElementById('resultsBody');

  function formatCurrency(n) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(n);
  }

  function runComparison() {
    const monthlyPension = parseFloat(document.getElementById('monthlyPension').value) || 0;
    const lumpSum = parseFloat(document.getElementById('lumpSum').value) || 0;
    const currentAge = parseInt(document.getElementById('currentAge').value, 10) || 65;
    const growthRatePct = parseFloat(document.getElementById('growthRate').value) || 5;
    const lifeExpectancy = parseInt(document.getElementById('lifeExpectancy').value, 10) || 90;

    const annualPension = 12 * monthlyPension;
    const r = growthRatePct / 100;
    const maxYears = Math.max(lifeExpectancy - currentAge + 5, 30);

    let breakEvenYear = null;
    let breakEvenAge = null;
    const rows = [];

    for (let t = 1; t <= maxYears; t++) {
      const cumPension = annualPension * t;
      const lumpSumFV = lumpSum * Math.pow(1 + r, t);
      const age = currentAge + t;
      rows.push({ year: t, age, cumPension, lumpSumFV });
      if (breakEvenYear === null && cumPension >= lumpSumFV) {
        breakEvenYear = t;
        breakEvenAge = age;
      }
    }

    // Show table through plan horizon, or through break-even + a few years if later
    const planYears = lifeExpectancy - currentAge + 2;
    const tableYears = Math.min(Math.max(planYears, (breakEvenYear || 0) + 3), maxYears);
    const displayRows = rows.slice(0, tableYears);

    // Summary text
    let summaryHtml = '';
    if (breakEvenAge !== null) {
      summaryHtml = '<p><strong>Break-even:</strong> At age <strong>' + breakEvenAge + '</strong> (in ' + breakEvenYear + ' years), the total pension you will have received equals what the lump sum would have grown to at ' + growthRatePct + '% per year.</p>';
      summaryHtml += '<p>If you live past age ' + breakEvenAge + ', the pension pays more in total than the lump sum would have grown to. If you die before then, the lump sum (or what you drew from it) could be worth more to you or your heirs.</p>';
    } else {
      summaryHtml = '<p>At your assumed growth rate (' + growthRatePct + '% per year), the lump sum\'s future value exceeds the total pension received at every age shown. The pension may still be valuable for <strong>guaranteed income</strong> and longevity protection.</p>';
    }
    summaryBox.innerHTML = summaryHtml;

    // Table
    resultsBody.innerHTML = displayRows.map(function (row) {
      const highlight = row.age === breakEvenAge ? ' style="background: #e0f2fe;"' : '';
      return '<tr' + highlight + '><td>' + row.year + '</td><td>' + row.age + '</td><td>' + formatCurrency(row.cumPension) + '</td><td>' + formatCurrency(row.lumpSumFV) + '</td></tr>';
    }).join('');

    // Chart: cumulative pension vs lump sum FV over time
    createComparisonChart(displayRows, breakEvenAge);

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

    window.lastPensionResult = {
      monthlyPension,
      lumpSum,
      currentAge,
      growthRatePct,
      lifeExpectancy,
      breakEvenYear,
      breakEvenAge,
      annualPension,
      summary: breakEvenAge !== null
        ? 'Break-even at age ' + breakEvenAge + ' (in ' + breakEvenYear + ' years). Monthly pension $' + monthlyPension + ', lump sum $' + lumpSum + ', growth rate ' + growthRatePct + '%.'
        : 'No break-even at assumed growth ' + growthRatePct + '%. Lump sum FV exceeds cumulative pension. Monthly pension $' + monthlyPension + ', lump sum $' + lumpSum + ', planned to age ' + lifeExpectancy + '.'
    };
  }

  function createComparisonChart(displayRows, breakEvenAge) {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) return;

    if (window.pensionComparisonChart && typeof window.pensionComparisonChart.destroy === 'function') {
      window.pensionComparisonChart.destroy();
    }

    const labels = displayRows.map(function (r) { return 'Age ' + r.age; });
    const pensionData = displayRows.map(function (r) { return r.cumPension; });
    const lumpSumData = displayRows.map(function (r) { return r.lumpSumFV; });

    window.pensionComparisonChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Cumulative pension received',
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
            label: 'Lump sum if invested (FV)',
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

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    runComparison();
  });

  // Premium save/load stubs if needed later
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
    showExplainModal(data.explanation);
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

function showExplainModal(explanation) {
  var overlay = document.getElementById('explainResultsModalOverlay');
  if (overlay) overlay.remove();
  overlay = document.createElement('div');
  overlay.id = 'explainResultsModalOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
  overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
  var box = document.createElement('div');
  box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:560px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
  box.addEventListener('click', function(e) { e.stopPropagation(); });
  box.innerHTML = '<div style="padding:24px 24px 16px;">' +
    '<h2 style="margin:0 0 16px 0;font-size:1.25rem;color:#1f2937;">🤖 AI Explanation</h2>' +
    '<div style="color:#374151;line-height:1.7;white-space:pre-wrap;overflow-y:auto;max-height:50vh;">' + escapeHtml(explanation) + '</div>' +
    '</div>' +
    '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
    '<p style="margin:0 0 12px 0;font-size:12px;color:#6b7280;">This is an AI-generated explanation for educational purposes. Not financial or legal advice.</p>' +
    '<button type="button" id="explainModalCloseBtn" style="padding:10px 24px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Close</button>' +
    '</div>';
  overlay.appendChild(box);
  document.body.appendChild(overlay);
  document.getElementById('explainModalCloseBtn').addEventListener('click', function() { overlay.remove(); });
}
