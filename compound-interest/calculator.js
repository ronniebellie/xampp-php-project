function formatCurrencyCI(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  }).format(amount);
}

function runCompoundProjection(initial, monthly, annualRatePercent, years) {
  const monthlyRate = annualRatePercent / 100 / 12;
  const totalMonths = years * 12;
  const labels = [];
  const balances = [];
  const invested = [];

  let balance = initial;
  let contributed = initial;

  labels.push('Now');
  balances.push(balance);
  invested.push(contributed);

  for (let m = 1; m <= totalMonths; m++) {
    balance = balance * (1 + monthlyRate) + monthly;
    contributed += monthly;

    if (m % 12 === 0 || m === totalMonths) {
      const year = m / 12;
      labels.push('Yr ' + year);
      balances.push(balance);
      invested.push(contributed);
    }
  }

  return {
    labels,
    balances,
    invested,
    finalBalance: balance,
    totalInvested: contributed,
    interestEarned: balance - contributed
  };
}

let compoundChart = null;

function updateCompoundCalculator() {
  const initialEl = document.getElementById('initial');
  const rateEl = document.getElementById('rate');
  const yearsEl = document.getElementById('years');
  const monthlyEl = document.getElementById('monthly');

  const initial = parseFloat(initialEl.value) || 0;
  const rate = parseFloat(rateEl.value) || 0;
  const years = parseInt(yearsEl.value, 10) || 0;
  const monthly = parseFloat(monthlyEl.value) || 0;

  document.getElementById('initialLabel').textContent = formatCurrencyCI(initial);
  document.getElementById('returnLabel').textContent = rate.toFixed(2).replace(/\.00$/, '') + '%';
  document.getElementById('yearsLabel').textContent = years + (years === 1 ? ' year' : ' years');
  document.getElementById('monthlyLabel').textContent = formatCurrencyCI(monthly) + '/mo';

  const result = runCompoundProjection(initial, monthly, rate, years);

  document.getElementById('finalBalance').textContent = formatCurrencyCI(result.finalBalance);
  document.getElementById('totalInvested').textContent = formatCurrencyCI(result.totalInvested);
  document.getElementById('interestEarned').textContent = formatCurrencyCI(result.interestEarned);

  const ctx = document.getElementById('compoundChart');
  if (!ctx) return;

  if (compoundChart) {
    compoundChart.destroy();
  }

  compoundChart = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: result.labels,
      datasets: [
        {
          label: 'Amount invested',
          data: result.invested,
          backgroundColor: '#93c5fd'
        },
        {
          label: 'Growth (interest)',
          data: result.balances.map((b, i) => Math.max(0, b - result.invested[i])),
          backgroundColor: '#34d399'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              return ctx.dataset.label + ': ' + formatCurrencyCI(ctx.parsed.y);
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return formatCurrencyCI(value);
            }
          }
        }
      }
    }
  });
}

['initial', 'rate', 'years', 'monthly'].forEach(function (id) {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('input', updateCompoundCalculator);
  }
});

document.addEventListener('DOMContentLoaded', function () {
  updateCompoundCalculator();
});

