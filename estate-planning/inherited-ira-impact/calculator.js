// Inherited IRA & Legacy Tax Impact Calculator
// Reuses 2026 brackets and RMD logic; projects owner to death, then simulates heir 10-year rule.

const TAX_BRACKETS_2026 = RBFederalTax.brackets;
const STANDARD_DEDUCTION_2026 = RBFederalTax.deductions;

function calculateFederalTax(taxableIncome, filingStatus) {
  const brackets = TAX_BRACKETS_2026[filingStatus] || TAX_BRACKETS_2026.single;
  let tax = 0;
  for (let i = 0; i < brackets.length; i++) {
    const b = brackets[i];
    const inBracket = Math.min(
      Math.max(0, taxableIncome - b.min),
      b.max - b.min
    );
    tax += inBracket * b.rate;
    if (taxableIncome <= b.max) break;
  }
  return tax;
}

/** Project owner from currentAge to deathAge: growth, RMDs, optional conversions. Returns balances at death and owner lifetime tax. */
function projectOwnerToDeath(params) {
  const {
    currentAge, birthYear,
    deathAge,
    filingStatus,
    traditionalIRA,
    rothIRA,
    retirementIncome,
    returnRate,
    conversionAmount,
    conversionYears
  } = params;

  const deduction = STANDARD_DEDUCTION_2026[filingStatus] || 30000;
  let trad = traditionalIRA;
  let roth = rothIRA;
  let totalTax = 0;
  const conversionStart = currentAge;
  const conversionEnd = currentAge + conversionYears - 1;

  for (let age = currentAge; age <= deathAge; age++) {
    let income = retirementIncome;
    let rmd = 0;
    let conversion = 0;

    if (trad > 0) {
      const resolved = RBTaxRmd.resolveRMD({ownerAge: age, priorYearEndBalance: trad, birthYear});
      if (!resolved.supported) throw new RangeError(resolved.reason);
      rmd = resolved.amount;
      trad -= rmd;
      income += rmd;
    }
    if (conversionAmount > 0 && age >= conversionStart && age <= conversionEnd && trad > 0) {
      conversion = Math.min(conversionAmount, trad);
      trad -= conversion;
      roth += conversion;
      income += conversion;
    }

    const taxable = Math.max(0, income - deduction);
    totalTax += calculateFederalTax(taxable, filingStatus);
    trad *= (1 + returnRate);
    roth *= (1 + returnRate);
  }

  return {
    traditionalAtDeath: trad,
    rothAtDeath: roth,
    ownerLifetimeTax: totalTax
  };
}

/** Simulate one heir's 10-year inherited IRA: balance at inheritance, other income, filing status, strategy (level | year10), return rate. */
function simulateHeirInheritedIRA(params) {
  const { balance, otherIncome, filingStatus, strategy, returnRate } = params;
  const deduction = STANDARD_DEDUCTION_2026[filingStatus] || 15000;
  const years = 10;
  const yearlyData = [];
  let remaining = balance;
  let totalTax = 0;

  let withdrawals;
  if (strategy === 'year10') {
    // Grow for 9 years, withdraw all in year 10
    for (let y = 0; y < 9; y++) {
      remaining *= (1 + returnRate);
      yearlyData.push({ year: y + 1, balance: remaining, distribution: 0, income: otherIncome, tax: calculateFederalTax(Math.max(0, otherIncome - deduction), filingStatus) });
      totalTax += yearlyData[yearlyData.length - 1].tax;
    }
    const finalBalance = remaining * (1 + returnRate);
    const dist = finalBalance;
    remaining = 0;
    const taxableIncome = Math.max(0, otherIncome + dist - deduction);
    const tax = calculateFederalTax(taxableIncome, filingStatus);
    totalTax += tax;
    yearlyData.push({ year: 10, balance: 0, distribution: dist, income: otherIncome + dist, tax });
  } else {
    // Level: each year grow, then withdraw (balance / years remaining) so account empties in 10 years
    for (let y = 0; y < years; y++) {
      remaining *= (1 + returnRate);
      const yearsLeft = years - y;
      const dist = (yearsLeft > 0) ? remaining / yearsLeft : remaining;
      remaining -= dist;
      const taxableIncome = Math.max(0, otherIncome + dist - deduction);
      const tax = calculateFederalTax(taxableIncome, filingStatus);
      totalTax += tax;
      yearlyData.push({ year: y + 1, balance: remaining, distribution: dist, income: otherIncome + dist, tax });
    }
  }

  return { yearlyData, totalHeirTax: totalTax };
}

function getFormData() {
  const heirs = [];
  for (let i = 1; i <= 4; i++) {
    const share = parseFloat(document.getElementById('heirShare' + i)?.value) || 0;
    if (share <= 0) continue;
    const name = (document.getElementById('heirName' + i)?.value || 'Heir ' + i).trim();
    heirs.push({
      name: name || 'Heir ' + i,
      age: parseInt(document.getElementById('heirAge' + i)?.value, 10) || 40,
      sharePct: share,
      otherIncome: parseFloat(document.getElementById('heirIncome' + i)?.value) || 0,
      filingStatus: document.getElementById('heirFiling' + i)?.value || 'single'
    });
  }
  // Normalize shares to 100 if they don't sum to 100
  const totalShare = heirs.reduce((s, h) => s + h.sharePct, 0);
  if (totalShare > 0 && Math.abs(totalShare - 100) > 0.01) {
    heirs.forEach(h => { h.sharePct = (h.sharePct / totalShare) * 100; });
  }

  return {
    currentAge: parseInt(document.getElementById('currentAge')?.value, 10) || 68,
    birthYear: parseInt(document.getElementById('birthYear')?.value, 10),
    deathAge: parseInt(document.getElementById('deathAge')?.value, 10) || 90,
    filingStatus: document.getElementById('filingStatus')?.value || 'married',
    traditionalIRA: parseFloat(document.getElementById('traditionalIRA')?.value) || 0,
    rothIRA: parseFloat(document.getElementById('rothIRA')?.value) || 0,
    retirementIncome: parseFloat(document.getElementById('retirementIncome')?.value) || 0,
    returnRate: (parseFloat(document.getElementById('returnRate')?.value) || 5) / 100,
    conversionAmount: parseFloat(document.getElementById('conversionAmount')?.value) || 0,
    conversionYears: parseInt(document.getElementById('conversionYears')?.value, 10) || 10,
    heirs,
    payoutStrategy: document.getElementById('payoutStrategy')?.value || 'level',
    beneficiaryCategory: document.getElementById('beneficiaryCategory')?.value || 'other',
    ownerDiedBeforeRequiredBeginningDate: document.getElementById('diedBeforeRbd')?.value === 'yes',
    inheritedReturnRate: (parseFloat(document.getElementById('inheritedReturnRate')?.value) || 5) / 100
  };
}

function runAnalysis() {
  const d = getFormData();
  if (d.heirs.length === 0) {
    alert('Please enter at least one heir with a share % greater than 0.');
    return null;
  }
  const inheritedRule = RBInheritedIraRules.classify(d);
  if (!inheritedRule.supported) {
    alert('Unsupported inherited-IRA case: ' + inheritedRule.reason);
    return null;
  }

  let noConv, withConv;
  try {
  noConv = projectOwnerToDeath({
    ...d,
    conversionAmount: 0,
    conversionYears: 0
  });
  withConv = projectOwnerToDeath({
    ...d,
    conversionAmount: d.conversionAmount,
    conversionYears: d.conversionYears
  });
  } catch (error) {
    alert('Unsupported owner RMD case: ' + error.message);
    return null;
  }

  function heirResults(ownerResult) {
    const tradAtDeath = ownerResult.traditionalAtDeath;
    return d.heirs.map(heir => {
      const balance = (tradAtDeath * heir.sharePct) / 100;
      const sim = simulateHeirInheritedIRA({
        balance,
        otherIncome: heir.otherIncome,
        filingStatus: heir.filingStatus,
        strategy: d.payoutStrategy,
        returnRate: d.inheritedReturnRate
      });
      return {
        name: heir.name,
        sharePct: heir.sharePct,
        inheritedBalance: balance,
        yearlyData: sim.yearlyData,
        totalHeirTax: sim.totalHeirTax
      };
    });
  }

  const heirsNoConv = heirResults(noConv);
  const heirsWithConv = heirResults(withConv);

  const totalHeirsTaxNoConv = heirsNoConv.reduce((s, h) => s + h.totalHeirTax, 0);
  const totalHeirsTaxWithConv = heirsWithConv.reduce((s, h) => s + h.totalHeirTax, 0);

  const totalNoConv = noConv.ownerLifetimeTax + totalHeirsTaxNoConv;
  const totalWithConv = withConv.ownerLifetimeTax + totalHeirsTaxWithConv;
  const savings = totalNoConv - totalWithConv;

  return {
    noConv: {
      owner: noConv,
      heirs: heirsNoConv,
      totalHeirsTax: totalHeirsTaxNoConv,
      totalCrossGeneration: totalNoConv
    },
    withConv: {
      owner: withConv,
      heirs: heirsWithConv,
      totalHeirsTax: totalHeirsTaxWithConv,
      totalCrossGeneration: totalWithConv
    },
    savings,
    formData: d
  };
}

function formatCurrency(n) {
  return '$' + (Number(n)).toLocaleString(undefined, { maximumFractionDigits: 0, minimumFractionDigits: 0 });
}

function displayResults(result) {
  const el = document.getElementById('resultsContent');
  const r = result;
  const d = r.formData;
  const betterWithConversion = r.savings > 0;

  const summary = 'Inherited IRA Legacy Tax Impact. ' +
    (betterWithConversion
      ? 'Converting reduces total tax by about ' + formatCurrency(r.savings) + '. '
      : 'Converting increases total tax by about ' + formatCurrency(-r.savings) + '. ') +
    'No conversions total tax (you + heirs): ' + formatCurrency(r.noConv.totalCrossGeneration) + '. ' +
    'With conversions total tax: ' + formatCurrency(r.withConv.totalCrossGeneration) + '. ' +
    'Traditional IRA $' + d.traditionalIRA.toLocaleString() + ', Roth $' + d.rothIRA.toLocaleString() + '. ' +
    'Conversion $' + d.conversionAmount.toLocaleString() + '/yr for ' + d.conversionYears + ' years. ' +
    d.heirs.length + ' heir(s).';
  window.lastInheritedIRAResult = { result: r, formData: d, summary };

  let html = `
    <div class="info-box ${betterWithConversion ? 'info-box-blue' : ''}" style="margin-bottom: 24px;">
      <h3>Recommendation</h3>
      <p>
        ${betterWithConversion
          ? `Converting reduces total tax across you and your heirs by about ${formatCurrency(r.savings)}. Your heirs inherit less traditional IRA and more Roth, so they pay less tax on the 10-year inherited withdrawals.`
          : `In this scenario, converting increases total tax by about ${formatCurrency(-r.savings)}. You may be paying more now than your heirs would save. Consider a smaller conversion amount or different assumptions.`
        }
      </p>
    </div>

    <div class="summary-grid" style="margin-bottom: 24px;">
      <div class="summary-card">
        <div class="summary-label">No conversions — total tax (you + heirs)</div>
        <div class="summary-value">${formatCurrency(r.noConv.totalCrossGeneration)}</div>
      </div>
      <div class="summary-card">
        <div class="summary-label">With conversions — total tax (you + heirs)</div>
        <div class="summary-value">${formatCurrency(r.withConv.totalCrossGeneration)}</div>
      </div>
      <div class="summary-card" style="background: ${betterWithConversion ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)'};">
        <div class="summary-label">Difference</div>
        <div class="summary-value">${betterWithConversion ? '-' : '+'}${formatCurrency(Math.abs(r.savings))}</div>
      </div>
    </div>

    <div class="info-box" style="margin-bottom: 24px;">
      <h3>Breakdown</h3>
      <table class="data-table" style="margin-top: 12px;">
        <thead><tr><th>Scenario</th><th>Your lifetime tax</th><th>Heirs&apos; total tax</th><th>Combined</th></tr></thead>
        <tbody>
          <tr>
            <td>No conversions</td>
            <td>${formatCurrency(r.noConv.owner.ownerLifetimeTax)}</td>
            <td>${formatCurrency(r.noConv.totalHeirsTax)}</td>
            <td>${formatCurrency(r.noConv.totalCrossGeneration)}</td>
          </tr>
          <tr>
            <td>With conversions</td>
            <td>${formatCurrency(r.withConv.owner.ownerLifetimeTax)}</td>
            <td>${formatCurrency(r.withConv.totalHeirsTax)}</td>
            <td>${formatCurrency(r.withConv.totalCrossGeneration)}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="info-box" style="margin-bottom: 24px;">
      <h3>Balances at assumed death</h3>
      <p><strong>No conversions:</strong> Traditional ${formatCurrency(r.noConv.owner.traditionalAtDeath)}, Roth ${formatCurrency(r.noConv.owner.rothAtDeath)}</p>
      <p><strong>With conversions:</strong> Traditional ${formatCurrency(r.withConv.owner.traditionalAtDeath)}, Roth ${formatCurrency(r.withConv.owner.rothAtDeath)}</p>
    </div>
  `;

  r.withConv.heirs.forEach((heir, idx) => {
    html += `
      <div class="table-section" style="margin-bottom: 24px;">
        <h3>Heir: ${heir.name} (${heir.sharePct.toFixed(0)}% share) — 10-year inherited IRA (with-conversion scenario)</h3>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Year</th><th>Balance after distribution</th><th>Distribution</th><th>Taxable income</th><th>Federal tax</th></tr></thead>
            <tbody>
              ${heir.yearlyData.map(row => `
                <tr>
                  <td>${row.year}</td>
                  <td>${formatCurrency(row.balance)}</td>
                  <td>${formatCurrency(row.distribution)}</td>
                  <td>${formatCurrency(row.income)}</td>
                  <td>${formatCurrency(row.tax)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
        <p style="margin-top: 8px;"><strong>Total tax for this heir (10 years):</strong> ${formatCurrency(heir.totalHeirTax)}</p>
      </div>
    `;
  });

  const chartSection = `
    <div class="chart-section">
      <h3>Total tax comparison</h3>
      <div class="chart-wrapper">
        <canvas id="legacyTaxChart"></canvas>
      </div>
    </div>
  `;
  html += chartSection;

  el.innerHTML = html;
  document.getElementById('results').style.display = 'block';
  document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

  setTimeout(() => {
    const ctx = document.getElementById('legacyTaxChart');
    if (!ctx) return;
    if (window.legacyTaxChart instanceof Chart) window.legacyTaxChart.destroy();
    window.legacyTaxChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['No conversions', 'With conversions'],
        datasets: [{
          label: 'Your lifetime tax',
          data: [r.noConv.owner.ownerLifetimeTax, r.withConv.owner.ownerLifetimeTax],
          backgroundColor: 'rgba(102, 126, 234, 0.8)'
        }, {
          label: "Heirs' tax (10-year inherited IRA)",
          data: [r.noConv.totalHeirsTax, r.withConv.totalHeirsTax],
          backgroundColor: 'rgba(5, 150, 105, 0.8)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          x: { stacked: true },
          y: {
            stacked: true,
            ticks: { callback: v => '$' + (v / 1000).toFixed(0) + 'K' }
          }
        },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: ctx => ctx.dataset.label + ': ' + formatCurrency(ctx.parsed.y)
            }
          }
        }
      }
    });
  }, 100);
}

document.getElementById('inheritedIRAForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const result = runAnalysis();
  if (result) displayResults(result);
});

var explainBtn = document.getElementById('explainResultsBtnInResults');
if (explainBtn) explainBtn.addEventListener('click', explainResults);

function escapeHtml(s) {
  var div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function explainResults() {
  var r = window.lastInheritedIRAResult;
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
    body: JSON.stringify({ calculator_type: 'inherited-ira-impact', results_summary: summary })
  })
  .then(function(res) { return res.text(); })
  .then(function(text) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    var data;
    try { data = JSON.parse(text); } catch (e) {
      throw new Error('Server returned an unexpected response. Try logging out and back in.');
    }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation, { calculatorType: 'inherited-ira-impact', resultsSummary: summary });
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}
