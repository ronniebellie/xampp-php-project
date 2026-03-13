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
    'yearsToRetirement',
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
  updateOnTrack();
});

function updateOnTrack() {
  const currentAge = parseInt(document.getElementById('currentAge').value, 10) || 40;
  let years = parseInt(document.getElementById('yearsToRetirement').value, 10);
  if (isNaN(years) || years < 1) years = 1;
  const retirementAge = currentAge + years;
  const currentAgeLabelEl = document.getElementById('currentAgeLabel');
  if (currentAgeLabelEl) currentAgeLabelEl.textContent = currentAge + ' yrs';
  const yearsLabelEl = document.getElementById('yearsToRetirementLabel');
  if (yearsLabelEl) yearsLabelEl.textContent = years + ' yrs (retire at ~' + retirementAge + ')';
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
    document.getElementById('progressMessage').textContent = 'Set a target balance (or desired income and withdrawal rate) to see whether you are on track.';
    document.getElementById('progressMessage').style.background = '#fef3c7';
    document.getElementById('progressMessage').style.color = '#92400e';
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
    progressEl.textContent = 'At your current contribution rate, you\'re projected to reach your target in ' + years + ' years (around age ' + retirementAge + ').';
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

  const summary = '401(k) On Track. Age ' + currentAge + ', retirement age ' + retirementAge + ', ' + years + ' years to retirement. Current balance ' + formatCurrency(currentBalance) + ', annual contribution ' + formatCurrency(annualContribution) + ', expected return ' + expectedReturn + '%. Projected balance at retirement: ' + formatCurrency(result.projected) + '. Target: ' + formatCurrency(targetBalance) + '. ' + (onTrack ? 'On track.' : 'Shortfall: ' + formatCurrency(shortfall) + (result.suggestedContribution ? '. Suggested contribution to be on track: ' + formatCurrency(Math.round(result.suggestedContribution)) + '/year.' : '.'));
  window.lastOnTrackResult = { result, currentAge, retirementAge, onTrack, shortfall, summary };

  // Set share URL so that "Share" actions can reproduce this scenario/results
  const shareEl = document.getElementById('shareResults');
  if (shareEl) {
    const url = buildShareUrlFromOnTrackForm();
    shareEl.setAttribute('data-share-url', url);
  }
}

['currentAge', 'yearsToRetirement', 'currentBalance', 'annualContribution', 'expectedReturn', 'targetBalance'].forEach(function(id) {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', updateOnTrack);
});

// Premium stubs + load-from-URL for sharing results
document.addEventListener('DOMContentLoaded', function() {
  (function applyScenarioFromUrl() {
    const params = new URLSearchParams(window.location.search || '');
    if (!params.has('currentAge') && !params.has('currentBalance')) return;
    let any = false;
    const currentAgeEl = document.getElementById('currentAge');
    if (currentAgeEl && params.has('currentAge')) {
      currentAgeEl.value = params.get('currentAge');
      any = true;
    }
    const yearsEl = document.getElementById('yearsToRetirement');
    if (yearsEl) {
      if (params.has('yearsToRetirement')) {
        yearsEl.value = params.get('yearsToRetirement');
        any = true;
      } else if (params.has('retirementAge') && currentAgeEl) {
        const ca = parseInt(currentAgeEl.value, 10) || 40;
        const ra = parseInt(params.get('retirementAge'), 10) || (ca + 25);
        yearsEl.value = Math.max(1, ra - ca);
        any = true;
      }
    }
    ['currentBalance', 'annualContribution', 'expectedReturn', 'targetBalance', 'desiredIncome', 'withdrawalRate'].forEach(id => {
      const el = document.getElementById(id);
      if (el && params.has(id)) {
        el.value = params.get(id);
        any = true;
      }
    });
    if (any) {
      updateOnTrack();
    }
  })();

  if (!window.location.search) {
    updateOnTrack();
  }

  const explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) explainBtn.addEventListener('click', explainResults);

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

function escapeHtml(s) {
  const div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function explainResults() {
  const r = window.lastOnTrackResult;
  if (!r || !r.summary) {
    alert('Please run the calculation first to see results.');
    return;
  }
  const btn = document.getElementById('explainResultsBtnInResults');
  const origText = btn ? btn.textContent : '';
  if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }
  const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
  fetch(explainUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ calculator_type: '401k-on-track', results_summary: r.summary })
  })
  .then(res => res.text())
  .then(text => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Server returned an unexpected response. Try logging out and back in.'); }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation);
  })
  .catch(err => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

function showExplainModal(explanation) {
  let overlay = document.getElementById('explainResultsModalOverlay');
  if (overlay) overlay.remove();
  overlay = document.createElement('div');
  overlay.id = 'explainResultsModalOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
  overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
  const box = document.createElement('div');
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
