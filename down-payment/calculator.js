// Down Payment / House Savings — live slider updates
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

function updateDownPayment() {
  const housePrice = parseFloat(document.getElementById('housePrice').value) || 0;
  const downPct = parseInt(document.getElementById('downPct').value, 10) || 20;
  const targetAmount = parseFloat(document.getElementById('targetAmount').value) || 0;
  const currentSavings = parseFloat(document.getElementById('currentSavings').value) || 0;
  const monthlyContribution = parseFloat(document.getElementById('monthlyContribution').value) || 0;
  const interestRate = parseFloat(document.getElementById('interestRate').value) || 0;

  const effectiveTarget = housePrice > 0 ? Math.round(housePrice * downPct / 100) : targetAmount;

  document.getElementById('housePriceLabel').textContent = housePrice === 0 ? 'Not set' : formatCurrency(housePrice);
  document.getElementById('downPctLabel').textContent = downPct + '%';
  document.getElementById('targetAmountLabel').textContent = formatCurrency(housePrice > 0 ? effectiveTarget : targetAmount);
  document.getElementById('currentSavingsLabel').textContent = formatCurrency(currentSavings);
  document.getElementById('monthlyContributionLabel').textContent = formatCurrency(monthlyContribution) + '/mo';
  document.getElementById('interestRateLabel').textContent = interestRate.toFixed(1) + '%';

  if (effectiveTarget <= 0) {
    document.getElementById('resultTarget').textContent = formatCurrency(0);
    document.getElementById('resultMonths').textContent = '—';
    document.getElementById('resultDate').textContent = '—';
    document.getElementById('progressMessage').textContent = 'Set a target down payment (or house price and down %) to see your plan.';
    document.getElementById('progressMessage').style.background = '#fef3c7';
    document.getElementById('progressMessage').style.color = '#92400e';
    document.getElementById('tableBody').innerHTML = '';
    if (savingsChart) { savingsChart.destroy(); savingsChart = null; }
    if (progressChart) { progressChart.destroy(); progressChart = null; }
    window.lastDPResult = null;
    return;
  }

  const result = runProjection(effectiveTarget, currentSavings, monthlyContribution, interestRate);

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
          { label: 'Savings balance', data: balanceData, borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.15)', borderWidth: 2, tension: 0.2, fill: true },
          { label: 'Goal', data: labels.map(() => result.target), borderColor: '#dc2626', borderDash: [5, 5], borderWidth: 2, fill: false }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + formatCurrency(c.parsed.y) } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => formatCurrency(v) } } }
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
        datasets: [{ label: '% of goal', data: pctData, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.15)', borderWidth: 2, tension: 0.2, fill: true }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.parsed.y.toFixed(1) + '%' } } },
        scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } }
      }
    });
  }

  window.lastDPResult = result;
}

['housePrice', 'downPct', 'targetAmount', 'currentSavings', 'monthlyContribution', 'interestRate'].forEach(function(id) {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', updateDownPayment);
});

document.addEventListener('DOMContentLoaded', function() {
  updateDownPayment();
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
