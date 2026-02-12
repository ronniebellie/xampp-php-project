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

function calculate() {
    // Get input values
    const currentAge = parseInt(document.getElementById('currentAge').value);
    const retirementAge = document.getElementById('retirementAge').value 
        ? parseInt(document.getElementById('retirementAge').value) 
        : currentAge;
    const lifeExpectancy = parseInt(document.getElementById('lifeExpectancy').value);
    const filingStatus = document.getElementById('filingStatus').value;
    
    const traditionalIRA = parseFloat(document.getElementById('traditionalIRA').value);
    const rothIRA = parseFloat(document.getElementById('rothIRA').value);
    const currentIncome = parseFloat(document.getElementById('currentIncome').value);
    const retirementIncome = parseFloat(document.getElementById('retirementIncome').value);
    
    const conversionAmount = parseFloat(document.getElementById('conversionAmount').value);
    const conversionYears = parseInt(document.getElementById('conversionYears').value);
    
    const returnRate = parseFloat(document.getElementById('returnRate').value) / 100;
    const inflationRate = parseFloat(document.getElementById('inflationRate').value) / 100;
    
    // Calculate standard deduction
    const standardDeduction = STANDARD_DEDUCTION_2026[filingStatus];
    
    // Determine starting age for conversions
    const conversionStartAge = Math.max(currentAge, retirementAge);
    const conversionEndAge = conversionStartAge + conversionYears - 1;
    
    // Run two scenarios: with conversion and without conversion
    const withConversion = projectRetirement(true);
    const withoutConversion = projectRetirement(false);
    
    function projectRetirement(doConversion) {
        let traditionalBalance = traditionalIRA;
        let rothBalance = rothIRA;
        let totalTaxesPaid = 0;
        let totalRMDs = 0;
        let yearlyData = [];
        
        for (let age = currentAge; age <= lifeExpectancy; age++) {
            const year = age - currentAge + 2026;
            let income = age >= retirementAge ? retirementIncome : currentIncome;
            let rmd = 0;
            let conversion = 0;
            let taxableIncome = 0;
            let federalTax = 0;
            
            // Apply investment growth at start of year
            traditionalBalance *= (1 + returnRate);
            rothBalance *= (1 + returnRate);
            
            // Handle RMDs (start at age 73)
            if (age >= 73 && traditionalBalance > 0) {
                rmd = calculateRMD(age, traditionalBalance);
                traditionalBalance -= rmd;
                totalRMDs += rmd;
                income += rmd;
            }
            
            // Handle conversions
            if (doConversion && age >= conversionStartAge && age <= conversionEndAge) {
                conversion = Math.min(conversionAmount, traditionalBalance);
                traditionalBalance -= conversion;
                rothBalance += conversion;
                income += conversion;
            }
            
            // Calculate taxes
            taxableIncome = Math.max(0, income - standardDeduction);
            federalTax = calculateFederalTax(taxableIncome, filingStatus);
            totalTaxesPaid += federalTax;
            
            // Store yearly data
            yearlyData.push({
                age: age,
                year: year,
                traditionalBalance: traditionalBalance,
                rothBalance: rothBalance,
                conversion: conversion,
                rmd: rmd,
                income: income,
                taxableIncome: taxableIncome,
                federalTax: federalTax,
                totalTaxesPaid: totalTaxesPaid,
                totalRMDs: totalRMDs
            });
        }
        
        return {
            totalTaxesPaid: totalTaxesPaid,
            totalRMDs: totalRMDs,
            finalTraditionalBalance: yearlyData[yearlyData.length - 1].traditionalBalance,
            finalRothBalance: yearlyData[yearlyData.length - 1].rothBalance,
            yearlyData: yearlyData
        };
    }
    
    // Calculate savings
    const taxSavings = withoutConversion.totalTaxesPaid - withConversion.totalTaxesPaid;
    const rmdReduction = withoutConversion.totalRMDs - withConversion.totalRMDs;
    const netBenefit = taxSavings;
    
    // Find break-even age
    let breakEvenAge = null;
    for (let i = 0; i < withConversion.yearlyData.length; i++) {
        if (withConversion.yearlyData[i].totalTaxesPaid < withoutConversion.yearlyData[i].totalTaxesPaid) {
            breakEvenAge = withConversion.yearlyData[i].age;
            break;
        }
    }
    
    // Calculate first year conversion details
    const taxableIncome = Math.max(0, currentIncome - standardDeduction);
    const taxWithoutConversion = calculateFederalTax(taxableIncome, filingStatus);
    const taxWithConversion = calculateFederalTax(taxableIncome + conversionAmount, filingStatus);
    const conversionTaxCost = taxWithConversion - taxWithoutConversion;
    const effectiveTaxRate = (conversionTaxCost / conversionAmount) * 100;
    const currentMarginalRate = getMarginalRate(taxableIncome, filingStatus);
    const marginalRateWithConversion = getMarginalRate(taxableIncome + conversionAmount, filingStatus);
    
    // Display results
    displayResults({
        conversionAmount,
        conversionYears,
        conversionStartAge,
        conversionEndAge,
        conversionTaxCost,
        effectiveTaxRate,
        currentMarginalRate,
        marginalRateWithConversion,
        taxableIncome,
        taxSavings,
        rmdReduction,
        netBenefit,
        breakEvenAge,
        withConversion,
        withoutConversion
    });
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

// Event listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rothForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculate();
    });
});
// Premium Save/Load Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    
    if (saveBtn) {
        saveBtn.addEventListener('click', saveScenario);
    }
    
    if (loadBtn) {
        loadBtn.addEventListener('click', loadScenario);
    }
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My Roth Plan');
    if (!scenarioName) return;
    
    const formData = {
        currentAge: document.getElementById('currentAge')?.value,
        retirementAge: document.getElementById('retirementAge')?.value,
        lifeExpectancy: document.getElementById('lifeExpectancy')?.value,
        filingStatus: document.getElementById('filingStatus')?.value,
        traditionalIRA: document.getElementById('traditionalIRA')?.value,
        rothIRA: document.getElementById('rothIRA')?.value,
        currentIncome: document.getElementById('currentIncome')?.value,
        retirementIncome: document.getElementById('retirementIncome')?.value,
        conversionAmount: document.getElementById('conversionAmount')?.value,
        conversionYears: document.getElementById('conversionYears')?.value,
        returnRate: document.getElementById('returnRate')?.value,
        inflationRate: document.getElementById('inflationRate')?.value
    };
    
    fetch('/api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'roth-conversion',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('saveStatus').textContent = '✓ Saved!';
            setTimeout(() => {
                document.getElementById('saveStatus').textContent = '';
            }, 3000);
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function loadScenario() {
    fetch('/api/load_scenarios.php?calculator_type=roth-conversion')
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
        
        let message = 'Select a scenario to load (or type "d" + number to delete):

';
        data.scenarios.forEach((s, i) => {
            message += `${i + 1}. ${s.name} (saved ${new Date(s.updated_at).toLocaleDateString()})
`;
        });
        message += '
Examples: Enter "1" to load, "d1" to delete';
        
        const choice = prompt(message + '

Enter number or d+number:');
        if (!choice) return;
        
        if (choice.toLowerCase().startsWith('d')) {
            const index = parseInt(choice.s            const index = parseInt(choice.s            const scenarios.length) {
                const scenario = data.scenarios[index];
                if (confirm(`Delete "${scenario.name}"? This cannot be undone.`)) {
                    fetch('/api/delete_scenario.php', {
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