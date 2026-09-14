function formatCurrencyCI(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  }).format(amount);
}

function runCompoundProjection(initial, monthly, annualRatePercent, years) {
  if(![initial,monthly,annualRatePercent,years].every(Number.isFinite)||initial<0||monthly<0||annualRatePercent<=-100||annualRatePercent>100||!Number.isInteger(years)||years<0||years>120)throw new RangeError('Enter finite nonnegative savings, 0–120 whole years and a return above -100% through 100%.');
  const rows=RBNumerical.annuityLedger(monthly,annualRatePercent/1200,years*12,false,initial);
  const annual=rows.filter(row=>row.period%12===0),last=rows.at(-1);
  return {labels:annual.map(row=>row.period?'Yr '+row.period/12:'Now'),balances:annual.map(row=>row.value),invested:annual.map(row=>row.contributed),finalBalance:last.value,totalInvested:last.contributed,interestEarned:last.interest};
}

let compoundChart = null;

function updateCompoundCalculatorUnsafe() {
  const initialEl = document.getElementById('initial');
  const rateEl = document.getElementById('rate');
  const yearsEl = document.getElementById('years');
  const monthlyEl = document.getElementById('monthly');

  const initial = parseFloat(initialEl.value) || 0;
  const rate = parseFloat(rateEl.value) || 0;
  const years = Number(yearsEl.value);
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
          data: result.balances.map((b, i) => b - result.invested[i]),
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

  const resultsEl = document.getElementById('compoundResults');
  if (resultsEl) resultsEl.style.display = 'block';
}

function updateCompoundCalculator() {
  try {
    ['initial','rate','years','monthly'].forEach(id=>{const raw=document.getElementById(id).value;if(String(raw).trim()===''||!Number.isFinite(Number(raw)))throw new RangeError('Enter valid numeric inputs.');});
    updateCompoundCalculatorUnsafe();
  } catch(error) { document.getElementById('compoundResults').style.display='none'; alert(error.message); }
}

['initial', 'rate', 'years', 'monthly'].forEach(function (id) {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('input', updateCompoundCalculator);
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const initial = parseFloat(document.getElementById('initial').value) || 0;
  const rate = parseFloat(document.getElementById('rate').value) || 0;
  const years = parseInt(document.getElementById('years').value, 10) || 0;
  const monthly = parseFloat(document.getElementById('monthly').value) || 0;
  document.getElementById('initialLabel').textContent = formatCurrencyCI(initial);
  document.getElementById('returnLabel').textContent = rate.toFixed(2).replace(/\.00$/, '') + '%';
  document.getElementById('yearsLabel').textContent = years + (years === 1 ? ' year' : ' years');
  document.getElementById('monthlyLabel').textContent = formatCurrencyCI(monthly) + '/mo';
});

