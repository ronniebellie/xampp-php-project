// RMD Impact Calculator JavaScript

// RMD divisor table from IRS Uniform Lifetime Table
const rmdDivisors = {
    73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
    80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
    87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
    94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4
};

// Joint Life and Last Survivor Expectancy Table (for when spouse is sole beneficiary and 10+ years younger)
// Key format: "ownerAge-spouseAge" -> divisor
// This is a simplified version - in production you'd want the full IRS table
const jointLifeExpectancy = {
    // Sample entries - format: ownerAge_spouseAge: divisor
    '73_63': 23.1, '73_62': 23.3, '73_61': 23.6, '73_60': 23.8, '73_59': 24.0, '73_58': 24.2, '73_57': 24.4, '73_56': 24.7, '73_55': 24.9, '73_54': 25.1, '73_53': 25.3,
    '74_64': 22.3, '74_63': 22.5, '74_62': 22.7, '74_61': 22.9, '74_60': 23.1, '74_59': 23.4, '74_58': 23.6, '74_57': 23.8, '74_56': 24.0, '74_55': 24.2, '74_54': 24.5,
    '75_65': 21.5, '75_64': 21.7, '75_63': 21.9, '75_62': 22.1, '75_61': 22.3, '75_60': 22.5, '75_59': 22.7, '75_58': 23.0, '75_57': 23.2, '75_56': 23.4, '75_55': 23.6,
    '80_70': 17.5, '80_69': 17.7, '80_68': 17.9, '80_67': 18.1, '80_66': 18.3, '80_65': 18.5, '80_64': 18.7, '80_63': 18.9, '80_62': 19.1, '80_61': 19.3, '80_60': 19.5,
    '85_75': 14.2, '85_74': 14.4, '85_73': 14.5, '85_72': 14.7, '85_71': 14.9, '85_70': 15.0, '85_69': 15.2, '85_68': 15.4, '85_67': 15.6, '85_66': 15.7, '85_65': 15.9,
    '90_80': 11.7, '90_79': 11.8, '90_78': 12.0, '90_77': 12.1, '90_76': 12.3, '90_75': 12.4, '90_74': 12.5, '90_73': 12.7, '90_72': 12.8, '90_71': 13.0, '90_70': 13.1,
    '95_85': 9.6, '95_84': 9.7, '95_83': 9.8, '95_82': 9.9, '95_81': 10.1, '95_80': 10.2, '95_79': 10.3, '95_78': 10.4, '95_77': 10.5, '95_76': 10.6, '95_75': 10.7,
    '100_90': 8.1, '100_89': 8.2, '100_88': 8.3, '100_87': 8.4, '100_86': 8.5, '100_85': 8.5, '100_84': 8.6, '100_83': 8.7, '100_82': 8.8, '100_81': 8.9, '100_80': 9.0
};

/**
 * Get RMD divisor based on age and spouse beneficiary status
 */
function getRMDDivisor(ownerAge, isSpouseBeneficiary, spouseAge) {
    // If spouse is sole beneficiary and more than 10 years younger, use Joint Life table
    if (isSpouseBeneficiary && spouseAge && (ownerAge - spouseAge) > 10) {
        const key = `${ownerAge}_${spouseAge}`;
        if (jointLifeExpectancy[key]) {
            return jointLifeExpectancy[key];
        }
        // If exact match not found, interpolate or use closest value
        // For simplicity, fall back to uniform table if not in our simplified table
    }
    
    // Use Uniform Lifetime Table
    return rmdDivisors[ownerAge] || 6.4; // Default to 6.4 for ages over 100
}

// 2026 Tax Brackets (estimated)
const taxBrackets2026 = {
    single: [
        { max: 11600, rate: 0.10 },
        { max: 47150, rate: 0.12 },
        { max: 100525, rate: 0.22 },
        { max: 191950, rate: 0.24 },
        { max: 243725, rate: 0.32 },
        { max: 609350, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ],
    married: [
        { max: 23200, rate: 0.10 },
        { max: 94300, rate: 0.12 },
        { max: 201050, rate: 0.22 },
        { max: 383900, rate: 0.24 },
        { max: 487450, rate: 0.32 },
        { max: 731200, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ],
    hoh: [
        { max: 16550, rate: 0.10 },
        { max: 63100, rate: 0.12 },
        { max: 100500, rate: 0.22 },
        { max: 191950, rate: 0.24 },
        { max: 243700, rate: 0.32 },
        { max: 609350, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ]
};

const standardDeductions2026 = {
    single: 14600,
    married: 29200,
    hoh: 21900
};

let myChart = null;

function calculateTaxBracket(taxableIncome, filingStatus) {
    const brackets = taxBrackets2026[filingStatus];
    for (let bracket of brackets) {
        if (taxableIncome <= bracket.max) {
            return bracket.rate * 100;
        }
    }
    return 37;
}

function calculateProjection(data) {
    const results = [];
    let balance = data.accountBalance;
    const startAge = data.currentAge;
    const rmdStartAge = 73;
    let currentSpouseAge = data.spouseAge;

    for (let age = startAge; age <= 100; age++) {
        let rmdAmount = 0;
        let accountBalanceBeforeRMD = balance;

        if (age >= rmdStartAge) {
            // Get the appropriate divisor based on spouse beneficiary status
            const divisor = getRMDDivisor(age, data.isSpouseBeneficiary, currentSpouseAge);
            
            if (divisor) {
                rmdAmount = balance / divisor;
                balance -= rmdAmount;
            }
        }

        const totalIncome = rmdAmount + data.socialSecurity + data.pension + data.otherIncome;
        const deduction = data.useStandardDeduction ? standardDeductions2026[data.filingStatus] : 0;
        const taxableIncome = Math.max(0, totalIncome - deduction);
        const taxBracket = calculateTaxBracket(taxableIncome, data.filingStatus);

        results.push({
            age,
            balance: accountBalanceBeforeRMD,
            rmdAmount,
            totalIncome,
            taxableIncome,
            taxBracket
        });

        if (balance > 0) {
            balance = balance * (1 + data.growthRate / 100);
        }
        
        // Age spouse along with owner
        if (currentSpouseAge) {
            currentSpouseAge++;
        }
    }

    return results;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

function generateInterpretation(results, data) {
    const firstRMD = results.find(r => r.rmdAmount > 0);
    const age80Data = results.find(r => r.age === 80);
    const age90Data = results.find(r => r.age === 90);
    
    let interpretation = '<h3>What This Means For You</h3><ul>';

    // Mention if using Joint Life table
    if (data.isSpouseBeneficiary && data.spouseAge && (data.currentAge - data.spouseAge) > 10) {
        interpretation += '<li><strong>Special calculation applies:</strong> Because your spouse is your sole beneficiary and is more than 10 years younger, we\'re using the IRS Joint Life and Last Survivor Expectancy Table, which results in lower RMDs than the standard table.</li>';
    }

    if (data.accountBalance <= 50000) {
        interpretation += `<li><strong>Your RMDs will be very modest.</strong> With a current balance of ${formatCurrency(data.accountBalance)}, your first RMD at age 73 will only be around ${formatCurrency(firstRMD.rmdAmount)}. This is unlikely to create any significant tax burden.</li>`;
    } else if (data.accountBalance <= 200000) {
        interpretation += `<li><strong>Your RMDs will be manageable.</strong> Starting at ${formatCurrency(firstRMD.rmdAmount)} at age 73, these withdrawals shouldn't dramatically impact your taxes for most situations.</li>`;
    } else if (data.accountBalance <= 600000) {
        interpretation += `<li><strong>RMD planning may be beneficial.</strong> With ${formatCurrency(data.accountBalance)} in tax-deferred accounts, your RMDs will be substantial enough that strategies like Roth conversions or QCDs could help reduce your tax burden.</li>`;
    } else {
        interpretation += `<li><strong>RMD planning is important for you.</strong> With ${formatCurrency(data.accountBalance)} in tax-deferred accounts, RMDs will be significant. You should seriously consider tax planning strategies like Roth conversions, qualified charitable distributions, and tax bracket management.</li>`;
    }

    if (firstRMD.taxBracket <= 12) {
        interpretation += '<li><strong>You\'re likely in a favorable tax situation.</strong> Your estimated tax bracket remains low even with RMDs.</li>';
    } else if (firstRMD.taxBracket <= 22) {
        interpretation += '<li><strong>You\'re in a moderate tax bracket.</strong> RMDs are adding to your tax bill, but you still have room for planning opportunities.</li>';
    } else {
        interpretation += '<li><strong>RMDs may push you into higher tax brackets.</strong> Consider strategies to reduce your tax-deferred balance before RMDs become mandatory.</li>';
    }

    if (age80Data && age80Data.balance > data.accountBalance * 1.2) {
        interpretation += '<li><strong>Your account is projected to continue growing.</strong> Even with RMDs, your portfolio growth is outpacing withdrawals, which means RMDs will increase over time.</li>';
    }

    if (data.accountBalance >= 500000) {
        interpretation += '<li><strong>Recommended actions:</strong> Consider working with a financial advisor on Roth conversion strategies, especially in lower-income years before Social Security or pension income begins. Qualified Charitable Distributions (QCDs) can also help if you\'re charitably inclined.</li>';
    } else if (data.accountBalance >= 150000) {
        interpretation += '<li><strong>Consider:</strong> Reviewing your withdrawal strategy to see if taking distributions earlier than required might smooth out your tax burden over time.</li>';
    } else {
        interpretation += '<li><strong>Good news:</strong> For most people with account balances like yours, RMDs are simply part of normal retirement income and don\'t require complex planning strategies.</li>';
    }

    interpretation += '</ul>';
    return interpretation;
}

function displayResults(results, data) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.style.display = 'block';

    const firstRMD = results.find(r => r.rmdAmount > 0);
    const age80Data = results.find(r => r.age === 80) || firstRMD;
    const age90Data = results.find(r => r.age === 90) || age80Data;

    const summaryHTML = `
        <div class="summary-card">
            <div class="summary-label">First RMD (Age 73)</div>
            <div class="summary-value">${formatCurrency(firstRMD.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">RMD at Age 80</div>
            <div class="summary-value">${formatCurrency(age80Data.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">RMD at Age 90</div>
            <div class="summary-value">${formatCurrency(age90Data.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Peak Tax Bracket</div>
            <div class="summary-value">${Math.max(...results.map(r => r.taxBracket))}%</div>
        </div>
    `;
    document.getElementById('summaryCards').innerHTML = summaryHTML;

    document.getElementById('interpretation').innerHTML = generateInterpretation(results, data);

    const chartData = results.filter(r => r.age >= data.currentAge && r.age <= 100);
    
    if (myChart) {
        myChart.destroy();
    }

    const ctx = document.getElementById('rmdChart').getContext('2d');
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(r => r.age),
            datasets: [
                {
                    label: 'Account Balance',
                    data: chartData.map(r => r.balance),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Annual RMD',
                    data: chartData.map(r => r.rmdAmount),
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return '$' + (value/1000).toFixed(0) + 'K';
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Age'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            }
        }
    });

    // Generate table data based on premium status
    const tableBody = document.getElementById('tableBody');
    let tableHTML = '';

    if (typeof isPremiumUser !== 'undefined' && isPremiumUser) {
        // Premium: Show ALL years from 73 to 100
        const tableData = results.filter(r => r.age >= 73);
        tableHTML = tableData.map(r => `
            <tr>
                <td>${r.age}</td>
                <td>${formatCurrency(r.balance)}</td>
                <td>${formatCurrency(r.rmdAmount)}</td>
                <td>${formatCurrency(r.totalIncome)}</td>
                <td>${r.taxBracket}%</td>
            </tr>
        `).join('');
    } else {
        // Free: Show first 3 rows (ages 73, 78, 83), then blurred preview
        const freeData = results.filter(r => r.age >= 73 && (r.age - 73) % 5 === 0).slice(0, 3);
        tableHTML = freeData.map(r => `
            <tr>
                <td>${r.age}</td>
                <td>${formatCurrency(r.balance)}</td>
                <td>${formatCurrency(r.rmdAmount)}</td>
                <td>${formatCurrency(r.totalIncome)}</td>
                <td>${r.taxBracket}%</td>
            </tr>
        `).join('');
        
        // Add blurred preview rows
        tableHTML += `
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>88</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
            </tr>
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>93</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
            </tr>
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>98</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
            </tr>
        `;
    }

    tableBody.innerHTML = tableHTML;

    // Add premium upsell banner for free users
    if (typeof isPremiumUser === 'undefined' || !isPremiumUser) {
        const tableSection = document.querySelector('.table-section');
        const upsellBanner = document.createElement('div');
        upsellBanner.style.cssText = 'margin-top: 20px; padding: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; text-align: center;';
        upsellBanner.innerHTML = `
            <h3 style="margin: 0 0 12px 0;">🔒 See Your Complete Retirement Timeline</h3>
            <p style="margin: 0 0 16px 0; opacity: 0.95;">Upgrade to Premium to see year-by-year projections from age 73 to 100, plus save unlimited scenarios.</p>
            <a href="../premium.html" style="display: inline-block; background: white; color: #667eea; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700;">Upgrade to Premium</a>
        `;
        tableSection.appendChild(upsellBanner);
    }

    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.getElementById('rmdForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const spouseBeneficiary = document.getElementById('spouseBeneficiary').value === 'yes';
    const spouseAge = spouseBeneficiary ? parseInt(document.getElementById('spouseAge').value) : null;

    const data = {
        currentAge: parseInt(document.getElementById('currentAge').value),
        accountBalance: parseFloat(document.getElementById('accountBalance').value),
        growthRate: parseFloat(document.getElementById('growthRate').value),
        socialSecurity: parseFloat(document.getElementById('socialSecurity').value) || 0,
        pension: parseFloat(document.getElementById('pension').value) || 0,
        otherIncome: parseFloat(document.getElementById('otherIncome').value) || 0,
        filingStatus: document.getElementById('filingStatus').value,
        useStandardDeduction: document.getElementById('standardDeduction').value === 'yes',
        isSpouseBeneficiary: spouseBeneficiary,
        spouseAge: spouseAge
    };

    if (data.currentAge < 50 || data.currentAge > 100) {
        alert('Please enter a valid age between 50 and 100');
        return;
    }

    if (data.accountBalance < 0) {
        alert('Please enter a valid account balance');
        return;
    }

    if (data.growthRate < 0 || data.growthRate > 20) {
        alert('Please enter a valid growth rate between 0 and 20%');
        return;
    }

    if (spouseBeneficiary && (!spouseAge || spouseAge < 18 || spouseAge > 100)) {
        alert('Please enter a valid spouse age between 18 and 100');
        return;
    }

    const results = calculateProjection(data);
    displayResults(results, data);
});

// Premium Save/Load/PDF Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    
    if (saveBtn) {
        saveBtn.addEventListener('click', saveScenario);
    }
    
    if (loadBtn) {
        loadBtn.addEventListener('click', loadScenario);
    }
    
    if (pdfBtn) {
        pdfBtn.addEventListener('click', downloadPDF);
    }
    
    if (csvBtn) {
        csvBtn.addEventListener('click', downloadCSV);
    }
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My RMD Plan');
    if (!scenarioName) return;
    
    // Gather all form inputs
    const formData = {
        currentAge: document.getElementById('currentAge').value,
        accountBalance: document.getElementById('accountBalance').value,
        growthRate: document.getElementById('growthRate').value,
        socialSecurity: document.getElementById('socialSecurity').value,
        pension: document.getElementById('pension').value,
        otherIncome: document.getElementById('otherIncome').value,
        filingStatus: document.getElementById('filingStatus').value,
        standardDeduction: document.getElementById('standardDeduction').value,
        spouseBeneficiary: document.getElementById('spouseBeneficiary').value,
        spouseAge: document.getElementById('spouseAge').value
    };
    
    fetch('/api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'rmd-impact',
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
    fetch('/api/load_scenarios.php?calculator_type=rmd-impact')
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

function downloadPDF() {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') {
        alert('Please calculate your RMD impact first before downloading the PDF.');
        return;
    }

    // Capture the chart as an image
    const canvas = document.getElementById('rmdChart');
    const chartImage = canvas.toDataURL('image/png');

    const data = {
        currentAge: parseInt(document.getElementById('currentAge').value),
        accountBalance: parseFloat(document.getElementById('accountBalance').value),
        growthRate: parseFloat(document.getElementById('growthRate').value),
        socialSecurity: parseFloat(document.getElementById('socialSecurity').value) || 0,
        pension: parseFloat(document.getElementById('pension').value) || 0,
        otherIncome: parseFloat(document.getElementById('otherIncome').value) || 0,
        filingStatus: document.getElementById('filingStatus').value,
        useStandardDeduction: document.getElementById('standardDeduction').value === 'yes',
        isSpouseBeneficiary: document.getElementById('spouseBeneficiary').value === 'yes',
        spouseAge: document.getElementById('spouseBeneficiary').value === 'yes' ? 
            parseInt(document.getElementById('spouseAge').value) : null
    };

    const summaryCards = document.querySelectorAll('.summary-value');
    const summary = {
        firstRMD: parseFloat(summaryCards[0].textContent.replace(/[$,]/g, '')),
        age80RMD: parseFloat(summaryCards[1].textContent.replace(/[$,]/g, '')),
        age90RMD: parseFloat(summaryCards[2].textContent.replace(/[$,]/g, '')),
        peakTaxBracket: parseInt(summaryCards[3].textContent.replace('%', ''))
    };

    const results = calculateProjection(data);
    const projections = results.filter(r => r.age >= 73);

    fetch('/api/generate_rmd_pdf.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            summary: summary,
            projections: projections,
            chartImage: chartImage
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'PDF generation failed';
                try {
                    const j = JSON.parse(t);
                    if (j.error) msg = j.error;
                } catch (_) {}
                throw new Error(msg);
            });
        }
        const ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) {
            throw new Error('Server did not return a PDF. You may need to log in again or refresh.');
        }
        return response.blob();
    })
    .then(blob => {
        if (blob.type && blob.type.indexOf('pdf') === -1) {
            throw new Error('Download was not a PDF. Try again or check your login.');
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'RMD_Analysis_' + new Date().toISOString().split('T')[0] + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error generating PDF: ' + error.message);
    });
}

function downloadCSV() {
    // Check if results are displayed (check for summary cards or chart)
    const summaryCards = document.querySelectorAll('.summary-value');
    const chartCanvas = document.getElementById('rmdChart');
    if (summaryCards.length === 0 && (!chartCanvas || !myChart)) {
        alert('Please calculate your RMD projection first.');
        return;
    }

    // Get chart image if available
    let chartImage = null;
    const chartCanvas = document.getElementById('rmdChart');
    if (chartCanvas && myChart) {
        chartImage = chartCanvas.toDataURL('image/png');
    }

    // Gather form data (same as PDF)
    const data = {
        currentAge: parseInt(document.getElementById('currentAge').value),
        accountBalance: parseFloat(document.getElementById('accountBalance').value),
        growthRate: parseFloat(document.getElementById('growthRate').value),
        socialSecurity: parseFloat(document.getElementById('socialSecurity').value) || 0,
        pension: parseFloat(document.getElementById('pension').value) || 0,
        otherIncome: parseFloat(document.getElementById('otherIncome').value) || 0,
        filingStatus: document.getElementById('filingStatus').value,
        useStandardDeduction: document.getElementById('standardDeduction').value === 'yes',
        isSpouseBeneficiary: document.getElementById('spouseBeneficiary').value === 'yes',
        spouseAge: document.getElementById('spouseBeneficiary').value === 'yes' ? 
            parseInt(document.getElementById('spouseAge').value) : null
    };

    const summaryCards = document.querySelectorAll('.summary-value');
    const summary = {
        firstRMD: parseFloat(summaryCards[0].textContent.replace(/[$,]/g, '')),
        age80RMD: parseFloat(summaryCards[1].textContent.replace(/[$,]/g, '')),
        age90RMD: parseFloat(summaryCards[2].textContent.replace(/[$,]/g, '')),
        peakTaxBracket: parseInt(summaryCards[3].textContent.replace('%', ''))
    };

    const results = calculateProjection(data);
    const projections = results.filter(r => r.age >= 73);

    fetch('/api/export_rmd_csv.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            summary: summary,
            projections: projections
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'CSV export failed';
                try {
                    const j = JSON.parse(t);
                    if (j.error) msg = j.error;
                } catch (_) {}
                throw new Error(msg);
            });
        }
        const ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) {
            throw new Error('Server did not return a CSV. You may need to log in again or refresh.');
        }
        return response.blob();
    })
    .then(blob => {
        if (blob.type && blob.type.indexOf('csv') === -1 && blob.type.indexOf('text') === -1) {
            throw new Error('Download was not a CSV. Try again or check your login.');
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'RMD_Analysis_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error exporting CSV: ' + error.message);
    });
}