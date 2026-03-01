// Inherited IRA & Legacy Tax Impact Calculator
// Reuses 2026 brackets and RMD logic; projects owner to death, then simulates heir 10-year rule.

const TAX_BRACKETS_2026 = {
  single: [
    { min: 0, max: 11925, rate: 0.10 },
    { min: 11925, max: 48475, rate: 0.12 },
    { min: 48475, max: 103350, rate: 0.22 },
    { min: 103350, max: 197300, rate: 0.24 },
    { min: 197300, max: 250525, rate: 0.32 },
    { min: 250525, max: 626350, rate: 0.35 },
    { min: 626350, max: Infinity, rate: 0.37 }
  ],
  married: [
    { min: 0, max: 23850, rate: 0.10 },
    { min: 23850, max: 96950, rate: 0.12 },
    { min: 96950, max: 206700, rate: 0.22 },
    { min: 206700, max: 394600, rate: 0.24 },
    { min: 394600, max: 501050, rate: 0.32 },
    { min: 501050, max: 751600, rate: 0.35 },
    { min: 751600, max: Infinity, rate: 0.37 }
  ],
  married_separate: [
    { min: 0, max: 11925, rate: 0.10 },
    { min: 11925, max: 48475, rate: 0.12 },
    { min: 48475, max: 103350, rate: 0.22 },
    { min: 103350, max: 197300, rate: 0.24 },
    { min: 197300, max: 250525, rate: 0.32 },
    { min: 250525, max: 375800, rate: 0.35 },
    { min: 375800, max: Infinity, rate: 0.37 }
  ],
  head: [
    { min: 0, max: 17000, rate: 0.10 },
    { min: 17000, max: 64850, rate: 0.12 },
    { min: 64850, max: 103350, rate: 0.22 },
    { min: 103350, max: 197300, rate: 0.24 },
    { min: 197300, max: 250500, rate: 0.32 },
    { min: 250500, max: 626350, rate: 0.35 },
    { min: 626350, max: Infinity, rate: 0.37 }
  ]
};

const STANDARD_DEDUCTION_2026 = {
  single: 15000,
  married: 30000,
  married_separate: 15000,
  head: 22500
};

const RMD_DIVISORS = {
  73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
  80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
  87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
  94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4,
  101: 6.0, 102: 5.6, 103: 5.2, 104: 4.9, 105: 4.6, 106: 4.3, 107: 4.1,
  108: 3.9, 109: 3.7, 110: 3.5, 111: 3.4, 112: 3.3, 113: 3.1, 114: 3.0,
  115: 2.9, 116: 2.8, 117: 2.7, 118: 2.5, 119: 2.3, 120: 2.0
};

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

function calculateRMD(age, balance) {
  if (age < 73) return 0;
  const divisor = RMD_DIVISORS[Math.min(age, 120)] || 6.4;
  return balance / divisor;
}

/** Project owner from currentAge to deathAge: growth, RMDs, optional conversions. Returns balances at death and owner lifetime tax. */
function projectOwnerToDeath(params) {
  const {
    currentAge,
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
    trad *= (1 + returnRate);
    roth *= (1 + returnRate);

    let income = retirementIncome;
    let rmd = 0;
    let conversion = 0;

    if (age >= 73 && trad > 0) {
      rmd = calculateRMD(age, trad);
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
    inheritedReturnRate: (parseFloat(document.getElementById('inheritedReturnRate')?.value) || 5) / 100
  };
}

function runAnalysis() {
  const d = getFormData();
  if (d.heirs.length === 0) {
    alert('Please enter at least one heir with a share % greater than 0.');
    return null;
  }

  const noConv = projectOwnerToDeath({
    ...d,
    conversionAmount: 0,
    conversionYears: 0
  });
  const withConv = projectOwnerToDeath({
    ...d,
    conversionAmount: d.conversionAmount,
    conversionYears: d.conversionYears
  });

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
