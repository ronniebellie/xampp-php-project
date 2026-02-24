// 401(k) / IRA On Track? Calculator
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

function runProjection(currentBalance, annualContribution, annualReturnPct, years) {
  const r = (annualReturnPct || 0) / 100;
  const rows = [];
  let balance = currentBalance;
  for (let y = 1; y <= years; y++) {
    const growth = balance * r;
    const startBalance = balance;
    balance = balance + growth + annualContribution;
    rows.push({
      year: y,
      startBalance,
      contribution: annualContribution,
      growth,
      endBalance: balance
    });
  }
  const projected = rows.length ? rows[rows.length - 1].endBalance : currentBalance;
  return {
    years,
    projected,
    target: null, // set by caller
    rows,
    suggestedContribution: null // set by caller if shortfall
  };
}

function suggestedAnnualContribution(currentBalance, target, annualReturnPct, years) {
  const r = (annualReturnPct || 0) / 100;
  const fvLump = currentBalance * Math.pow(1 + r, years);
  if (fvLump >= target) return 0;
  const factor = (Math.pow(1 + r, years) - 1) / (r || 1e-9);
  return (target - fvLump) / factor;
}

let growthChart = null;

function buildShareUrlFromOnTrackForm() {
  const params = new URLSearchParams();
  const ids = [
    'currentAge',
    'retirementAge',
    'currentBalance',
    'annualContribution',
    'expectedReturn',
    'targetBalance',
    'desiredIncome',
    'withdrawalRate'
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el && el.value !== '') {
      params.set(id, el.value);
    }
  });
  const qs = params.toString();
  return window.location.origin + window.location.pathname + (qs ? '?' + qs : '');
}

document.getElementById('setTargetFromIncome').addEventListener('click', function() {
  const income = parseFloat(document.getElementById('desiredIncome').value) || 0;
  const rate = parseFloat(document.getElementById('withdrawalRate').value) || 4;
  if (income <= 0) {
    alert('Enter a desired annual income first.');
    return;
  }
  const target = Math.round(income / (rate / 100));
  document.getElementById('targetBalance').value = target;
});

document.getElementById('onTrackForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const currentAge = parseInt(document.getElementById('currentAge').value, 10) || 40;
  const retirementAge = parseInt(document.getElementById('retirementAge').value, 10) || 65;
  let years = Math.max(0, retirementAge - currentAge);
  if (years <= 0) {
    alert('Retirement age must be greater than current age.');
    return;
  }
  const currentBalance = parseFloat(document.getElementById('currentBalance').value) || 0;
  const annualContribution = parseFloat(document.getElementById('annualContribution').value) || 0;
  const expectedReturn = parseFloat(document.getElementById('expectedReturn').value) || 6;
  let targetBalance = parseFloat(document.getElementById('targetBalance').value) || 0;
  const desiredIncome = parseFloat(document.getElementById('desiredIncome').value) || 0;
  const withdrawalRate = parseFloat(document.getElementById('withdrawalRate').value) || 4;
  if (desiredIncome > 0 && withdrawalRate > 0) {
    targetBalance = Math.round(desiredIncome / (withdrawalRate / 100));
    document.getElementById('targetBalance').value = targetBalance;
  }
  if (targetBalance <= 0) {
    alert('Please enter a target balance (or desired income + withdrawal rate).');
    return;
  }

  const result = runProjection(currentBalance, annualContribution, expectedReturn, years);
  result.target = targetBalance;
  const onTrack = result.projected >= targetBalance;
  const shortfall = onTrack ? 0 : targetBalance - result.projected;
  result.suggestedContribution = onTrack ? null : suggestedAnnualContribution(currentBalance, targetBalance, expectedReturn, years);

  document.getElementById('resultYears').textContent = years + ' years';
  document.getElementById('resultProjected').textContent = formatCurrency(result.projected);
  document.getElementById('resultTarget').textContent = formatCurrency(targetBalance);

  const onTrackCard = document.getElementById('resultOnTrackCard');
  const onTrackLabel = document.getElementById('resultOnTrackLabel');
  const onTrackEl = document.getElementById('resultOnTrack');
  if (onTrack) {
    onTrackCard.style.background = '#f0fdf4';
    onTrackCard.style.border = '1px solid #86efac';
    onTrackLabel.style.color = '#166534';
    onTrackLabel.textContent = 'On track?';
    onTrackEl.textContent = 'Yes';
    onTrackEl.style.color = '#14532d';
  } else {
    onTrackCard.style.background = '#fef2f2';
    onTrackCard.style.border = '1px solid #fca5a5';
    onTrackLabel.style.color = '#991b1b';
    onTrackLabel.textContent = 'On track?';
    onTrackEl.textContent = 'Shortfall: ' + formatCurrency(shortfall);
    onTrackEl.style.color = '#b91c1c';
  }

  const progressEl = document.getElementById('progressMessage');
  if (onTrack) {
    progressEl.textContent = 'At your current contribution rate, you\'re projected to reach your target by age ' + retirementAge + '.';
    progressEl.style.background = '#f0fdf4';
    progressEl.style.color = '#166534';
  } else {
    let msg = 'You\'re projected to be ' + formatCurrency(shortfall) + ' short of your target.';
    if (result.suggestedContribution != null && result.suggestedContribution > 0) {
      msg += ' To be on track, consider saving about ' + formatCurrency(Math.round(result.suggestedContribution)) + ' per year (instead of ' + formatCurrency(annualContribution) + ').';
    }
    progressEl.textContent = msg;
    progressEl.style.background = '#fef2f2';
    progressEl.style.color = '#991b1b';
  }

  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = '';
  const showRows = result.rows.slice(0, 40);
  showRows.forEach(row => {
    const tr = document.createElement('tr');
    const age = currentAge + row.year;
    tr.innerHTML = '<td>' + row.year + '</td><td>' + age + '</td><td>' + formatCurrency(row.startBalance) + '</td><td>' + formatCurrency(row.contribution) + '</td><td>' + formatCurrency(row.growth) + '</td><td>' + formatCurrency(row.endBalance) + '</td>';
    tbody.appendChild(tr);
  });
  if (result.rows.length > 40) {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="6" style="text-align: center; color: #718096;">... ' + (result.rows.length - 40) + ' more years</td>';
    tbody.appendChild(tr);
  }

  const labels = [0].concat(result.rows.map(r => r.year));
  const balanceData = [currentBalance].concat(result.rows.map(r => r.endBalance));
  const targetData = labels.map(() => targetBalance);

  if (growthChart) growthChart.destroy();
  const ctx = document.getElementById('growthChart');
  if (ctx) {
    growthChart = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Projected balance',
            data: balanceData,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.15)',
            borderWidth: 2,
            tension: 0.2,
            fill: true
          },
          {
            label: 'Target',
            data: targetData,
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

  document.getElementById('results').style.display = 'block';
  document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

  window.lastOnTrackResult = result;

  // Set share URL so that "Share" actions can reproduce this scenario/results
  const shareEl = document.getElementById('shareResults');
  if (shareEl) {
    const url = buildShareUrlFromOnTrackForm();
    shareEl.setAttribute('data-share-url', url);
  }
});

// Premium stubs + load-from-URL for sharing results
document.addEventListener('DOMContentLoaded', function() {
  // If URL contains scenario parameters, pre-fill the form and auto-run the calculation
  (function applyScenarioFromUrl() {
    const params = new URLSearchParams(window.location.search || '');
    if (!params.has('currentAge') && !params.has('currentBalance')) return;
    const ids = [
      'currentAge',
      'retirementAge',
      'currentBalance',
      'annualContribution',
      'expectedReturn',
      'targetBalance',
      'desiredIncome',
      'withdrawalRate'
    ];
    let any = false;
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el && params.has(id)) {
        el.value = params.get(id);
        any = true;
      }
    });
    if (any) {
      const form = document.getElementById('onTrackForm');
      if (form) {
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
    }
  })();

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
