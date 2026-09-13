// Student Loan Payoff Calculator - Avalanche vs Snowball
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

function getLoans() {
  const loans = [];
  for (let i = 1; i <= 5; i++) {
    const balance = parseFloat(document.getElementById('balance' + i).value) || 0;
    if (balance <= 0) continue;
    loans.push({
      id: i,
      name: document.getElementById('name' + i).value || 'Loan ' + i,
      balance: balance,
      apr: parseFloat(document.getElementById('apr' + i).value) || 0,
      minPayment: parseFloat(document.getElementById('min' + i).value) || 0
    });
  }
  return loans;
}

function runPayoff(loans, strategy, extraPayment) {
  return RBNumerical.debtPayoff(loans, strategy, extraPayment);
}

let balanceChart = null;

function displayResults(result) {
  if (result.status !== 'paid_off') {
    alert(result.status === 'non_amortizing' ? 'Payment does not exceed monthly interest; this loan does not amortize.' : 'Balance remains beyond the 720-month modeled horizon; payoff is not necessarily impossible.');
    document.getElementById('results').style.display = 'none';
    return;
  }
  if (result.months === 0) {
    alert('Please enter at least one loan with a balance greater than 0.');
    return;
  }

  document.getElementById('resultMonths').textContent = result.months + ' months';
  document.getElementById('resultInterest').textContent = formatCurrency(result.totalInterest);
  document.getElementById('resultTotal').textContent = formatCurrency(result.totalPaid);

  const orderHtml = result.payoffOrder.map((name, i) => (i + 1) + '. ' + name).join('<br>');
  document.getElementById('payoffOrder').innerHTML = orderHtml;

  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = '';
  result.schedule.slice(0, 24).forEach(row => {
    const tr = document.createElement('tr');
    const totalBal = row.balances.reduce((a, b) => a + b, 0);
    tr.innerHTML = '<td>' + row.month + '</td><td>' + row.targetDebt + '</td><td>' + formatCurrency(row.payment) + '</td><td>' + formatCurrency(row.interest) + '</td><td>' + formatCurrency(totalBal) + '</td>';
    tbody.appendChild(tr);
  });

  document.getElementById('results').style.display = 'block';
  document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.getElementById('loanForm').addEventListener('submit', function(e) {
  e.preventDefault();
  try {
  window.lastLoanResult = null;
  const loans = getLoans();
  const strategy = document.getElementById('strategy').value;
  const extra = parseFloat(document.getElementById('extra').value) || 0;
  const result = runPayoff(loans, strategy, extra);
  displayResults(result);
  if (result.status !== 'paid_off' || result.months === 0) return;

  const labels = [];
  for (let m = 0; m <= result.months; m++) labels.push(m);
  const colors = ['#dc2626', '#2563eb', '#059669', '#d97706', '#7c3aed'];
  const datasets = result.order.map((d, i) => ({
    label: d.name,
    data: result.series[i],
    borderColor: colors[i % colors.length],
    backgroundColor: colors[i % colors.length] + '20',
    borderWidth: 2,
    tension: 0.2,
    fill: false
  }));

  if (balanceChart) balanceChart.destroy();
  const ctx = document.getElementById('balanceChart');
  if (ctx) {
    balanceChart = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: { labels, datasets },
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

  window.lastLoanResult = result;
  } catch (error) { document.getElementById('results').style.display = 'none'; alert(error.message); }
});

// Premium stubs
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
