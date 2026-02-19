// Down Payment / House Savings Calculator
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

function runProjection(target, currentSavings, monthlyContribution, annualRatePercent) {
  const monthlyRate = (annualRatePercent || 0) / 100 / 12;
  const months = [];
  let balance = currentSavings;
  let month = 0;
  while (balance < target && month < 600) {
    const interest = balance * monthlyRate;
    balance += interest + monthlyContribution;
    month++;
    months.push({
      month,
      balance,
      interest,
      contribution: monthlyContribution,
      pct: Math.min(100, (balance / target) * 100)
    });
  }
  if (balance >= target && months.length > 0) {
    months[months.length - 1].balance = Math.min(balance, target);
    months[months.length - 1].pct = 100;
  }
  return {
    target,
    monthsToGoal: balance >= target ? month : null,
    reachedDate: balance >= target && month > 0 ? new Date(Date.now() + month * 30 * 24 * 60 * 60 * 1000) : null,
    schedule: months,
    finalBalance: balance
  };
}

let savingsChart = null;
let progressChart = null;

// If house price and down % are set, update target field
document.getElementById('housePrice').addEventListener('input', syncTargetFromHouse);
document.getElementById('downPct').addEventListener('input', syncTargetFromHouse);
function syncTargetFromHouse() {
  const housePrice = parseFloat(document.getElementById('housePrice').value) || 0;
  const pct = parseFloat(document.getElementById('downPct').value) || 20;
  if (housePrice > 0) {
    document.getElementById('targetAmount').value = Math.round(housePrice * (pct / 100));
  }
}

document.getElementById('dpForm').addEventListener('submit', function(e) {
  e.preventDefault();
  let target = parseFloat(document.getElementById('targetAmount').value) || 0;
  const housePrice = parseFloat(document.getElementById('housePrice').value) || 0;
  const downPct = parseFloat(document.getElementById('downPct').value) || 20;
  if (housePrice > 0) {
    target = Math.round(housePrice * (downPct / 100));
    document.getElementById('targetAmount').value = target;
  }
  const currentSavings = parseFloat(document.getElementById('currentSavings').value) || 0;
  const monthlyContribution = parseFloat(document.getElementById('monthlyContribution').value) || 0;
  const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;

  if (target <= 0) {
    alert('Please enter a target down payment (or house price and down payment %).');
    return;
  }

  const result = runProjection(target, currentSavings, monthlyContribution, interestRate);

  document.getElementById('resultTarget').textContent = formatCurrency(result.target);
  if (currentSavings >= result.target) {
    document.getElementById('resultMonths').textContent = '0';
    document.getElementById('resultDate').textContent = 'Now';
  } else if (result.monthsToGoal != null) {
    document.getElementById('resultMonths').textContent = result.monthsToGoal + ' months';
    document.getElementById('resultDate').textContent = result.reachedDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric', day: 'numeric' });
  } else {
    document.getElementById('resultMonths').textContent = '—';
    document.getElementById('resultDate').textContent = '—';
  }

  const progressEl = document.getElementById('progressMessage');
  if (currentSavings >= result.target) {
    progressEl.textContent = 'You\'ve already reached your down payment goal. You\'re ready to shop—or set a higher target for a larger down payment.';
    progressEl.style.background = '#f0fdf4';
    progressEl.style.color = '#166534';
  } else if (monthlyContribution <= 0) {
    progressEl.textContent = 'Add a monthly contribution to see when you\'ll reach your goal.';
    progressEl.style.background = '#fef3c7';
    progressEl.style.color = '#92400e';
  } else if (result.monthsToGoal != null) {
    progressEl.textContent = 'You\'re ' + Math.round((currentSavings / result.target) * 100) + '% of the way. At ' + formatCurrency(monthlyContribution) + '/month, you\'ll reach your down payment in ' + result.monthsToGoal + ' months.';
    progressEl.style.background = '#eff6ff';
    progressEl.style.color = '#1e40af';
  } else {
    progressEl.textContent = 'You\'re ' + Math.round((currentSavings / result.target) * 100) + '% of the way. Increase your monthly contribution to reach the goal sooner.';
    progressEl.style.background = '#fef3c7';
    progressEl.style.color = '#92400e';
  }

  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = '';
  const tableRows = result.schedule.length ? result.schedule.slice(0, 24) : [{ month: 0, balance: currentSavings, interest: 0, contribution: 0, pct: 100 }];
  tableRows.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td>' + row.month + '</td><td>' + formatCurrency(row.balance) + '</td><td>' + formatCurrency(row.interest) + '</td><td>' + formatCurrency(row.contribution) + '</td><td>' + row.pct.toFixed(1) + '%</td>';
    tbody.appendChild(tr);
  });

  const labels = result.schedule.length ? [0].concat(result.schedule.map(r => r.month)) : [0];
  const balanceData = result.schedule.length ? [currentSavings].concat(result.schedule.map(r => r.balance)) : [currentSavings];
  const pctData = result.schedule.length ? [Math.min(100, (currentSavings / result.target) * 100)].concat(result.schedule.map(r => r.pct)) : [Math.min(100, (currentSavings / result.target) * 100)];

  if (savingsChart) savingsChart.destroy();
  const ctx1 = document.getElementById('savingsChart');
  if (ctx1) {
    savingsChart = new Chart(ctx1.getContext('2d'), {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Savings balance',
            data: balanceData,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.15)',
            borderWidth: 2,
            tension: 0.2,
            fill: true
          },
          {
            label: 'Goal',
            data: labels.map(() => result.target),
            borderColor: '#dc2626',
            borderDash: [5, 5],
            borderWidth: 2,
            fill: false
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: { label: c => c.dataset.label + ': ' + formatCurrency(c.parsed.y) } }
        },
        scales: {
          y: { beginAtZero: true, ticks: { callback: v => formatCurrency(v) } }
        }
      }
    });
  }

  if (progressChart) progressChart.destroy();
  const ctx2 = document.getElementById('progressChart');
  if (ctx2) {
    progressChart = new Chart(ctx2.getContext('2d'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: '% of goal',
          data: pctData,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.15)',
          borderWidth: 2,
          tension: 0.2,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.parsed.y.toFixed(1) + '%' } }
        },
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
        }
      }
    });
  }

  document.getElementById('results').style.display = 'block';
  document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

  window.lastDPResult = result;
});

// Premium Save/Load/Compare/PDF/CSV stubs
document.addEventListener('DOMContentLoaded', function() {
  if (typeof isPremiumUser === 'undefined' || !isPremiumUser) return;
  const saveBtn = document.getElementById('saveScenarioBtn');
  const loadBtn = document.getElementById('loadScenarioBtn');
  const compareBtn = document.getElementById('compareScenariosBtn');
  const pdfBtn = document.getElementById('downloadPdfBtn');
  const csvBtn = document.getElementById('downloadCsvBtn');
  if (saveBtn) saveBtn.addEventListener('click', function() { alert('Save scenario: coming soon.'); });
  if (loadBtn) loadBtn.addEventListener('click', function() { alert('Load scenario: coming soon.'); });
  if (compareBtn) compareBtn.addEventListener('click', function() { alert('Compare scenarios: coming soon.'); });
  if (pdfBtn) pdfBtn.addEventListener('click', function() { alert('PDF export: coming soon.'); });
  if (csvBtn) csvBtn.addEventListener('click', function() { alert('CSV export: coming soon.'); });
});
