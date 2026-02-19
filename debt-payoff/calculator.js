// Debt Payoff Calculator - Avalanche vs Snowball
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

function getDebts() {
  const debts = [];
  for (let i = 1; i <= 3; i++) {
    const balance = parseFloat(document.getElementById('balance' + i).value) || 0;
    if (balance <= 0) continue;
    debts.push({
      id: i,
      name: document.getElementById('name' + i).value || 'Debt ' + i,
      balance: balance,
      apr: parseFloat(document.getElementById('apr' + i).value) || 0,
      minPayment: parseFloat(document.getElementById('min' + i).value) || 0
    });
  }
  return debts;
}

function runPayoff(debts, strategy, extraPayment) {
  if (debts.length === 0) return { months: 0, totalInterest: 0, totalPaid: 0, schedule: [], payoffOrder: [], series: [] };

  const order = strategy === 'avalanche'
    ? [...debts].sort((a, b) => b.apr - a.apr)
    : [...debts].sort((a, b) => a.balance - b.balance);

  const payoffOrder = order.map(d => d.name);
  const n = debts.length;
  let balances = debts.map(d => d.balance);
  const aprs = debts.map(d => d.apr / 100 / 12);
  const mins = debts.map(d => d.minPayment);
  const names = debts.map(d => d.name);
  const debtIdByIndex = debts.map((_, i) => i);

  const schedule = [];
  const series = order.map(() => []); // balance over time per ordered debt
  let month = 0;
  let totalInterest = 0;
  let totalPaid = 0;

  while (balances.some(b => b > 0.01)) {
    month++;
    let targetIndex = -1;
    if (strategy === 'avalanche') {
      let maxApr = -1;
      for (let i = 0; i < n; i++) {
        if (balances[i] > 0.01 && aprs[i] > maxApr) {
          maxApr = aprs[i];
          targetIndex = i;
        }
      }
    } else {
      let minBal = Infinity;
      for (let i = 0; i < n; i++) {
        if (balances[i] > 0.01 && balances[i] < minBal) {
          minBal = balances[i];
          targetIndex = i;
        }
      }
    }
    if (targetIndex < 0) break;

    let interestThisMonth = 0;
    const payments = [];
    for (let i = 0; i < n; i++) {
      const interest = balances[i] * aprs[i];
      interestThisMonth += interest;
      const pay = i === targetIndex ? mins[i] + extraPayment : mins[i];
      const payAmount = Math.min(pay, balances[i] + interest);
      payments.push(payAmount);
      balances[i] = Math.max(0, balances[i] + interest - payAmount);
      totalPaid += payAmount;
    }
    totalInterest += interestThisMonth;

    for (let k = 0; k < order.length; k++) {
      const idx = debts.indexOf(order[k]);
      series[k].push(idx >= 0 ? balances[idx] : 0);
    }

    schedule.push({
      month,
      targetDebt: names[targetIndex],
      payment: payments[targetIndex],
      interest: interestThisMonth,
      balances: [...balances]
    });
  }

  return {
    months: month,
    totalInterest,
    totalPaid,
    schedule,
    payoffOrder,
    series,
    order,
    names: order.map(d => d.name)
  };
}

let balanceChart = null;

function displayResults(result) {
  if (result.months === 0) {
    alert('Please enter at least one debt with a balance greater than 0.');
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

function runPayoffFixed(debts, strategy, extraPayment) {
  if (debts.length === 0) return { months: 0, totalInterest: 0, totalPaid: 0, schedule: [], payoffOrder: [], series: [], order: [] };

  const order = strategy === 'avalanche'
    ? [...debts].sort((a, b) => b.apr - a.apr)
    : [...debts].sort((a, b) => a.balance - b.balance);

  const n = debts.length;
  const nameToIndex = {};
  debts.forEach((d, i) => { nameToIndex[d.name] = i; });
  const orderIndex = order.map(d => debts.indexOf(d));

  let balances = debts.map(d => d.balance);
  const aprs = debts.map(d => d.apr / 100 / 12);
  const mins = debts.map(d => d.minPayment);
  const names = debts.map(d => d.name);

  const schedule = [];
  const seriesData = order.map(d => [d.balance]); // month 0 = initial balance per ordered debt
  let month = 0;
  let totalInterest = 0;
  let totalPaid = 0;

  while (balances.some(b => b > 0.01)) {
    month++;
    let targetIndex = -1;
    if (strategy === 'avalanche') {
      let maxApr = -1;
      for (let i = 0; i < n; i++) {
        if (balances[i] > 0.01 && aprs[i] > maxApr) {
          maxApr = aprs[i];
          targetIndex = i;
        }
      }
    } else {
      let minBal = Infinity;
      for (let i = 0; i < n; i++) {
        if (balances[i] > 0.01 && balances[i] < minBal) {
          minBal = balances[i];
          targetIndex = i;
        }
      }
    }
    if (targetIndex < 0) break;

    let interestThisMonth = 0;
    const payments = [];
    for (let i = 0; i < n; i++) {
      const interest = balances[i] * aprs[i];
      interestThisMonth += interest;
      const pay = i === targetIndex ? mins[i] + extraPayment : mins[i];
      const payAmount = Math.min(pay, balances[i] + interest);
      payments.push(payAmount);
      balances[i] = Math.max(0, balances[i] + interest - payAmount);
      totalPaid += payAmount;
    }
    totalInterest += interestThisMonth;

    for (let k = 0; k < order.length; k++) {
      seriesData[k].push(balances[orderIndex[k]]);
    }

    schedule.push({
      month,
      targetDebt: names[targetIndex],
      payment: payments[targetIndex],
      interest: interestThisMonth,
      balances: [...balances]
    });
  }

  return {
    months: month,
    totalInterest,
    totalPaid,
    schedule,
    payoffOrder: order.map(d => d.name),
    series: seriesData,
    order,
    names: order.map(d => d.name),
    orderIndex
  };
}

document.getElementById('debtForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const debts = getDebts();
  const strategy = document.getElementById('strategy').value;
  const extra = parseFloat(document.getElementById('extra').value) || 0;
  const result = runPayoffFixed(debts, strategy, extra);
  displayResults(result);

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

  window.lastDebtResult = result;
});
