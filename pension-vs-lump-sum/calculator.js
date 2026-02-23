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
})();
