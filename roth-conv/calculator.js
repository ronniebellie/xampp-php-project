// Roth Conversion Calculator Logic

// 2026 Federal Tax Brackets (projected based on inflation adjustments)
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

// Standard Deductions 2026
const STANDARD_DEDUCTION_2026 = {
  single: 15000,
  married: 30000,
  married_separate: 15000,
  head: 22500
};

// RMD Divisors (Uniform Lifetime Table)
const RMD_DIVISORS = {
  73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
  80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
  87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
  94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4,
  101: 6.0, 102: 5.6, 103: 5.2, 104: 4.9, 105: 4.6, 106: 4.3, 107: 4.1,
  108: 3.9, 109: 3.7, 110: 3.5, 111: 3.4, 112: 3.3, 113: 3.1, 114: 3.0,
  115: 2.9, 116: 2.8, 117: 2.7, 118: 2.5, 119: 2.3, 120: 2.0
};

// Calculate Federal Tax
function calculateFederalTax(taxableIncome, filingStatus) {
  const brackets = TAX_BRACKETS_2026[filingStatus];
  let tax = 0;
  
  for (let i = 0; i < brackets.length; i++) {
    const bracket = brackets[i];
    const taxableInBracket = Math.min(
      Math.max(0, taxableIncome - bracket.min),
      bracket.max - bracket.min
    );
    tax += taxableInBracket * bracket.rate;
    
    if (taxableIncome <= bracket.max) break;
  }
  
  return tax;
}

// Calculate marginal tax rate at a given income level
function getMarginalRate(taxableIncome, filingStatus) {
  const brackets = TAX_BRACKETS_2026[filingStatus];
  
  for (let bracket of brackets) {
    if (taxableIncome >= bracket.min && taxableIncome < bracket.max) {
      return bracket.rate;
    }
  }
  
  return brackets[brackets.length - 1].rate;
}

// Calculate RMD for a given age and balance
function calculateRMD(age, balance) {
  if (age < 73) return 0;
  const divisor = RMD_DIVISORS[Math.min(age, 120)];
  return balance / divisor;
}

/** Get current form values as an object (for save/load and runRothAnalysis). */
function getRothFormData() {
  const el = id => document.getElementById(id);
  return {
    currentAge: el('currentAge')?.value,
    retirementAge: el('retirementAge')?.value,
    lifeExpectancy: el('lifeExpectancy')?.value,
    filingStatus: el('filingStatus')?.value,
    traditionalIRA: el('traditionalIRA')?.value,
    rothIRA: el('rothIRA')?.value,
    currentIncome: el('currentIncome')?.value,
    retirementIncome: el('retirementIncome')?.value,
    conversionAmount: el('conversionAmount')?.value,
    conversionYears: el('conversionYears')?.value,
    returnRate: el('returnRate')?.value,
    inflationRate: el('inflationRate')?.value
  };
}

/** Run Roth analysis from a data object (form or loaded scenario). Returns same shape as displayResults expects. */
function runRothAnalysis(data) {
  const currentAge = parseInt(data.currentAge, 10);
  const retirementAge = data.retirementAge ? parseInt(data.retirementAge, 10) : currentAge;
  const lifeExpectancy = parseInt(data.lifeExpectancy, 10);
  const filingStatus = data.filingStatus;
  const traditionalIRA = parseFloat(data.traditionalIRA) || 0;
  const rothIRA = parseFloat(data.rothIRA) || 0;
  const currentIncome = parseFloat(data.currentIncome) || 0;
  const retirementIncome = parseFloat(data.retirementIncome) || 0;
  const conversionAmount = parseFloat(data.conversionAmount) || 0;
  const conversionYears = parseInt(data.conversionYears, 10) || 1;
  const returnRate = (parseFloat(data.returnRate) || 0) / 100;
  const inflationRate = (parseFloat(data.inflationRate) || 0) / 100;
  const standardDeduction = STANDARD_DEDUCTION_2026[filingStatus];
  const conversionStartAge = Math.max(currentAge, retirementAge);
  const conversionEndAge = conversionStartAge + conversionYears - 1;

  function projectRetirement(doConversion) {
    let traditionalBalance = traditionalIRA;
    let rothBalance = rothIRA;
    let totalTaxesPaid = 0;
    let totalRMDs = 0;
    const yearlyData = [];
    for (let age = currentAge; age <= lifeExpectancy; age++) {
      const year = age - currentAge + 2026;
      let income = age >= retirementAge ? retirementIncome : currentIncome;
      // Apply investment growth at start of year
      traditionalBalance *= (1 + returnRate);
      rothBalance *= (1 + returnRate);
      let rmd = 0, conversion = 0;
      if (age >= 73 && traditionalBalance > 0) {
        rmd = calculateRMD(age, traditionalBalance);
        traditionalBalance -= rmd;
        totalRMDs += rmd;
        income += rmd;
      }
      if (doConversion && age >= conversionStartAge && age <= conversionEndAge) {
        conversion = Math.min(conversionAmount, traditionalBalance);
        traditionalBalance -= conversion;
        rothBalance += conversion;
        income += conversion;
      }
      const taxableIncome = Math.max(0, income - standardDeduction);
      const federalTax = calculateFederalTax(taxableIncome, filingStatus);
      totalTaxesPaid += federalTax;
      yearlyData.push({
        age, year, traditionalBalance, rothBalance, conversion, rmd, income, taxableIncome, federalTax, totalTaxesPaid, totalRMDs
      });
    }
    return {
      totalTaxesPaid, totalRMDs,
      finalTraditionalBalance: yearlyData[yearlyData.length - 1].traditionalBalance,
      finalRothBalance: yearlyData[yearlyData.length - 1].rothBalance,
      yearlyData
    };
  }

  const withConversion = projectRetirement(true);
  const withoutConversion = projectRetirement(false);
  const taxSavings = withoutConversion.totalTaxesPaid - withConversion.totalTaxesPaid;
  const rmdReduction = withoutConversion.totalRMDs - withConversion.totalRMDs;
  let breakEvenAge = null;
  for (let i = 0; i < withConversion.yearlyData.length; i++) {
    if (withConversion.yearlyData[i].totalTaxesPaid < withoutConversion.yearlyData[i].totalTaxesPaid) {
      breakEvenAge = withConversion.yearlyData[i].age;
      break;
    }
  }
  const taxableIncome = Math.max(0, currentIncome - standardDeduction);
  const taxWithoutConversion = calculateFederalTax(taxableIncome, filingStatus);
  const taxWithConversion = calculateFederalTax(taxableIncome + conversionAmount, filingStatus);
  const conversionTaxCost = taxWithConversion - taxWithoutConversion;
  const effectiveTaxRate = (conversionTaxCost / conversionAmount) * 100;
  return {
    conversionAmount, conversionYears, conversionStartAge, conversionEndAge,
    conversionTaxCost, effectiveTaxRate,
    currentMarginalRate: getMarginalRate(taxableIncome, filingStatus),
    marginalRateWithConversion: getMarginalRate(taxableIncome + conversionAmount, filingStatus),
    taxableIncome, taxSavings, rmdReduction, netBenefit: taxSavings, breakEvenAge,
    withConversion, withoutConversion
  };
}

function calculate() {
    const formData = getRothFormData();
    const result = runRothAnalysis(formData);
    displayResults(result);
    window.lastRothResult = result;
}

function displayResults(data) {
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('resultsContent');
    
    const isWorthIt = data.netBenefit > 0;
    const recommendation = isWorthIt 
        ? `Converting appears beneficial! You could save approximately $${Math.abs(data.netBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})} in lifetime taxes.`
        : `Converting may not be optimal. You would pay approximately $${Math.abs(data.netBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})} more in lifetime taxes.`;
    
    resultsContent.innerHTML = `
        <div class="info-box ${isWorthIt ? 'info-box-blue' : ''}" style="margin-bottom: 25px;">
            <h3>💡 Recommendation</h3>
            <p style="font-size: 16px; margin-top: 10px;">${recommendation}</p>
            ${data.breakEvenAge ? `<p style="margin-top: 10px;"><strong>Break-even age:</strong> ${data.breakEvenAge}</p>` : ''}
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Lifetime Tax Comparison</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Without Conversion:</strong><br>
                    $${data.withoutConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>With Conversion:</strong><br>
                    $${data.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: ${isWorthIt ? '#059669' : '#dc2626'};">
                    <strong>Tax Savings:</strong><br>
                    ${isWorthIt ? '+' : ''}$${data.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>

        <div class="chart-section" style="margin-bottom: 30px;">
            <h3>Cumulative Taxes Paid Over Time</h3>
            <div class="chart-wrapper">
                <canvas id="cumulativeTaxChart"></canvas>
            </div>
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>RMD Impact</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Total RMDs (No Conversion):</strong><br>
                    $${data.withoutConversion.totalRMDs.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Total RMDs (With Conversion):</strong><br>
                    $${data.withConversion.totalRMDs.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: #059669;">
                    <strong>RMD Reduction:</strong><br>
                    $${data.rmdReduction.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>First Year Conversion Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Conversion Amount:</strong><br>
                    $${data.conversionAmount.toLocaleString()}
                </div>
                <div>
                    <strong>Tax Cost:</strong><br>
                    $${data.conversionTaxCost.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Effective Rate:</strong><br>
                    ${data.effectiveTaxRate.toFixed(2)}%
                </div>
            </div>
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Tax Brackets</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Current Taxable Income:</strong><br>
                    $${data.taxableIncome.toLocaleString()}
                </div>
                <div>
                    <strong>Current Marginal Rate:</strong><br>
                    ${(data.currentMarginalRate * 100).toFixed(0)}%
                </div>
                <div>
                    <strong>Rate After Conversion:</strong><br>
                    ${(data.marginalRateWithConversion * 100).toFixed(0)}%
                </div>
            </div>
        </div>
        
        <div class="info-box info-box-blue">
            <h3>Conversion Plan Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Total to Convert:</strong><br>
                    $${(data.conversionAmount * data.conversionYears).toLocaleString()}
                </div>
                <div>
                    <strong>Conversion Period:</strong><br>
                    Age ${data.conversionStartAge} to ${data.conversionEndAge}
                </div>
                <div>
                    <strong>Total Conversion Tax:</strong><br>
                    ~$${(data.conversionTaxCost * data.conversionYears).toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>
        
        <div class="table-section" style="margin-top: 30px;">
            <h3>Year-by-Year Projection (With Conversion)</h3>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Age</th>
                            <th>Year</th>
                            <th>Conversion</th>
                            <th>RMD</th>
                            <th>Total Income</th>
                            <th>Federal Tax</th>
                            <th>Traditional IRA</th>
                            <th>Roth IRA</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generateTableRows(data.withConversion.yearlyData)}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Create chart after DOM is updated
    setTimeout(() => {
        createCumulativeTaxChart(data);
    }, 100);
}

function generateTableRows(yearlyData) {
    return yearlyData.map(row => `
        <tr>
            <td>${row.age}</td>
            <td>${row.year}</td>
            <td>$${row.conversion.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.rmd.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.income.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.federalTax.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.traditionalBalance.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.rothBalance.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
        </tr>
    `).join('');
}

// Chart creation function
function createCumulativeTaxChart(data) {
    const ctx = document.getElementById('cumulativeTaxChart');
    if (!ctx) return;
    
    const ages = data.withConversion.yearlyData.map(d => d.age);
    const withConversionTaxes = data.withConversion.yearlyData.map(d => d.totalTaxesPaid);
    const withoutConversionTaxes = data.withoutConversion.yearlyData.map(d => d.totalTaxesPaid);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ages,
            datasets: [
                {
                    label: 'With Conversion',
                    data: withConversionTaxes,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Without Conversion',
                    data: withoutConversionTaxes,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {maximumFractionDigits: 0});
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Age'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Cumulative Taxes Paid'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// API base URL so api/... always resolves correctly (works for /roth-conv/ or /xamppfiles/htdocs/roth-conv/)
const RC_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)roth-conv\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

// Event listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rothForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculate();
    });
});
// Premium Save/Load/Compare/PDF/CSV
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My Roth Plan');
    if (!scenarioName) return;
    const formData = getRothFormData();
    fetch(RC_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'roth-conversion',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text: text })))
    .then(({ ok, status, text }) => {
        let data;
        try { data = JSON.parse(text); } catch (_) { throw new Error(text || 'Server error'); }
        if (!ok) throw new Error(data.error || 'Save failed');
        return data;
    })
    .then(data => {
        if (data.success) {
            document.getElementById('saveStatus').textContent = '✓ Saved!';
            setTimeout(() => { document.getElementById('saveStatus').textContent = ''; }, 3000);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Save scenario failed: ' + err.message));
}

function loadScenario() {
    fetch(RC_API_BASE + 'api/load_scenarios.php?calculator_type=roth-conversion')
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.error);
            return;
        }
        
        if (data.scenarios.length === 0) {
            alert('No saved scenarios yet. Save your first one!');
            return;
        }
        
        let message = 'Select a scenario to load (or type "d" + number to delete):\n\n';
        data.scenarios.forEach((s, i) => {
            message += `${i + 1}. ${s.name} (saved ${new Date(s.updated_at).toLocaleDateString()})\n`;
        });
        message += '\nExamples: Enter "1" to load, "d1" to delete';
        
        const choice = prompt(message + '\n\nEnter number or d+number:');
        if (!choice) return;
        
        if (choice.toLowerCase().startsWith('d')) {
            const index = parseInt(choice.substring(1)) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                if (confirm(`Delete "${scenario.name}"? This cannot be undone.`)) {
                    fetch(RC_API_BASE + 'api/delete_scenario.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ scenario_id: scenario.id })
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            alert('Scenario deleted!');
                        } else {
                            alert('Error: ' + result.error);
                        }
                    });
                }
            }
        } else {
            const index = parseInt(choice) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                Object.keys(scenario.data).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) input.value = scenario.data[key];
                });
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    });
}

function compareScenarios() {
    fetch(RC_API_BASE + 'api/load_scenarios.php?calculator_type=roth-conversion')
    .then(res => res.json())
    .then(data => {
        if (!data.success) { alert('Error: ' + data.error); return; }
        if (data.scenarios.length < 2) {
            alert('You need at least 2 saved scenarios to compare. Save more first!');
            return;
        }
        let message = 'Select TWO scenarios to compare:\n\n';
        data.scenarios.forEach((s, i) => { message += `${i + 1}. ${s.name}\n`; });
        message += '\nEnter two numbers separated by comma (e.g., "1,2"):';
        const choice = prompt(message);
        if (!choice) return;
        const parts = choice.split(',').map(s => parseInt(s.trim(), 10) - 1);
        if (parts.length !== 2 || parts[0] < 0 || parts[0] >= data.scenarios.length ||
            parts[1] < 0 || parts[1] >= data.scenarios.length || parts[0] === parts[1]) {
            alert('Invalid selection. Enter two different numbers (e.g., "1,2").');
            return;
        }
        const s1 = data.scenarios[parts[0]];
        const s2 = data.scenarios[parts[1]];
        const result1 = runRothAnalysis(s1.data);
        const result2 = runRothAnalysis(s2.data);
        showRothComparison(s1.name, s2.name, result1, result2, s1.data, s2.data);
    })
    .catch(() => alert('Failed to load scenarios.'));
}

function showRothComparison(name1, name2, result1, result2, data1, data2) {
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('resultsContent');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
    const safe = (obj) => (obj && (obj.currentAge != null || obj.traditionalIRA != null)) ? obj : {};
    const d1 = safe(data1), d2 = safe(data2);
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Age: ${d1.currentAge || '-'} | Traditional: $${(parseFloat(d1.traditionalIRA) || 0).toLocaleString()} | Conversion: $${(parseFloat(d1.conversionAmount) || 0).toLocaleString()}/yr
                    </div>
                    <div style="margin-top: 8px;"><strong>Lifetime tax (with conversion):</strong> $${result1.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                    <div><strong>Tax savings vs no conversion:</strong> $${result1.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                </div>
                <div>
                    <h3 style="color: #e53e3e;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Age: ${d2.currentAge || '-'} | Traditional: $${(parseFloat(d2.traditionalIRA) || 0).toLocaleString()} | Conversion: $${(parseFloat(d2.conversionAmount) || 0).toLocaleString()}/yr
                    </div>
                    <div style="margin-top: 8px;"><strong>Lifetime tax (with conversion):</strong> $${result2.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                    <div><strong>Tax savings vs no conversion:</strong> $${result2.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                </div>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f0f0f0;"><th>Metric</th><th>${name1}</th><th>${name2}</th><th>Difference</th></tr>
                <tr><td>Lifetime tax (with conversion)</td><td>$${result1.withConversion.totalTaxesPaid.toLocaleString(0)}</td><td>$${result2.withConversion.totalTaxesPaid.toLocaleString(0)}</td><td>$${(result2.withConversion.totalTaxesPaid - result1.withConversion.totalTaxesPaid).toLocaleString(0)}</td></tr>
                <tr><td>Tax savings</td><td>$${result1.taxSavings.toLocaleString(0)}</td><td>$${result2.taxSavings.toLocaleString(0)}</td><td>$${(result2.taxSavings - result1.taxSavings).toLocaleString(0)}</td></tr>
                <tr><td>Break-even age</td><td>${result1.breakEvenAge || '-'}</td><td>${result2.breakEvenAge || '-'}</td><td>-</td></tr>
            </table>
        </div>
    `;
    resultsContent.innerHTML = comparisonHTML + resultsContent.innerHTML;
}

function downloadPDF() {
    const res = window.lastRothResult;
    if (!res) {
        alert('Please run Calculate first, then download the PDF.');
        return;
    }
    const chartCanvas = document.getElementById('cumulativeTaxChart');
    const chartImage = chartCanvas && window.Chart ? chartCanvas.toDataURL('image/png') : null;
    const payload = {
        ...getRothFormData(),
        withConversion: res.withConversion,
        withoutConversion: res.withoutConversion,
        conversionAmount: res.conversionAmount,
        conversionYears: res.conversionYears,
        conversionStartAge: res.conversionStartAge,
        conversionEndAge: res.conversionEndAge,
        taxSavings: res.taxSavings,
        rmdReduction: res.rmdReduction,
        breakEvenAge: res.breakEvenAge,
        conversionTaxCost: res.conversionTaxCost,
        effectiveTaxRate: res.effectiveTaxRate,
        chartImage: chartImage
    };
    fetch(RC_API_BASE + 'api/generate_roth_pdf.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'PDF failed'); } catch (e) { throw new Error(t || 'PDF failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) return r.text().then(t => { throw new Error('Server did not return a PDF.'); });
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Roth_Conversion_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Download PDF: ' + e.message));
}

function downloadCSV() {
    const res = window.lastRothResult;
    if (!res) {
        alert('Please run Calculate first, then export CSV.');
        return;
    }
    const payload = {
        withConversion: res.withConversion,
        withoutConversion: res.withoutConversion
    };
    fetch(RC_API_BASE + 'api/export_roth_csv.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'CSV failed'); } catch (e) { throw new Error(t || 'CSV failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) throw new Error('Server did not return CSV.');
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Roth_Conversion_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Export CSV: ' + e.message));
}