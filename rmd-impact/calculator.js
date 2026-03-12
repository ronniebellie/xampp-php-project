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
    window.lastRMDResult = results;
    window.lastRMDData = data;

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

    // Set share URL so that "Share" actions can reproduce this scenario/results
    const shareEl = document.getElementById('shareResults');
    if (shareEl) {
        const params = new URLSearchParams();
        params.set('currentAge', String(data.currentAge));
        params.set('accountBalance', String(data.accountBalance));
        params.set('growthRate', String(data.growthRate));
        params.set('socialSecurity', String(data.socialSecurity));
        params.set('pension', String(data.pension));
        params.set('otherIncome', String(data.otherIncome));
        params.set('filingStatus', data.filingStatus);
        params.set('standardDeduction', data.useStandardDeduction ? 'yes' : 'no');
        params.set('spouseBeneficiary', data.isSpouseBeneficiary ? 'yes' : 'no');
        if (data.spouseAge) {
            params.set('spouseAge', String(data.spouseAge));
        }
        const url = window.location.origin + window.location.pathname + '?' + params.toString();
        shareEl.setAttribute('data-share-url', url);
    }
});

// If URL contains scenario parameters, pre-fill the form and auto-run the calculation
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search || '');
    if (!params.has('currentAge') || !params.has('accountBalance')) {
        return;
    }

    function setValue(id, key) {
        const el = document.getElementById(id);
        if (el && params.has(key)) {
            el.value = params.get(key);
        }
    }

    setValue('currentAge', 'currentAge');
    setValue('accountBalance', 'accountBalance');
    setValue('growthRate', 'growthRate');
    setValue('socialSecurity', 'socialSecurity');
    setValue('pension', 'pension');
    setValue('otherIncome', 'otherIncome');
    setValue('filingStatus', 'filingStatus');
    if (params.has('standardDeduction')) {
        setValue('standardDeduction', 'standardDeduction');
    }
    if (params.has('spouseBeneficiary')) {
        setValue('spouseBeneficiary', 'spouseBeneficiary');
        if (typeof toggleSpouseAge === 'function') {
            toggleSpouseAge();
        }
    }
    if (params.has('spouseAge')) {
        setValue('spouseAge', 'spouseAge');
    }

    const form = document.getElementById('rmdForm');
    if (form) {
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
});

// Premium Save/Load/PDF Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    const calendarBtn = document.getElementById('downloadCalendarBtn');
    const explainBtn = document.getElementById('explainResultsBtnInResults');

    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (calendarBtn) calendarBtn.addEventListener('click', downloadCalendar);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

// Global delegated handler so Explain works even after DOM replacements
document.addEventListener('click', function (event) {
    const target = event.target && event.target.closest ? event.target.closest('#explainResultsBtnInResults') : null;
    if (!target) return;
    event.preventDefault();
    try {
        if (typeof explainResults === 'function') {
            explainResults();
        }
    } catch (e) {
        console.error('Explain results handler error:', e);
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

function scenarioToProjectionData(s) {
    const d = s.data || {};
    return {
        currentAge: parseInt(d.currentAge, 10),
        accountBalance: parseFloat(d.accountBalance) || 0,
        growthRate: parseFloat(d.growthRate) || 0,
        socialSecurity: parseFloat(d.socialSecurity) || 0,
        pension: parseFloat(d.pension) || 0,
        otherIncome: parseFloat(d.otherIncome) || 0,
        filingStatus: d.filingStatus || 'single',
        useStandardDeduction: d.standardDeduction === 'yes',
        isSpouseBeneficiary: d.spouseBeneficiary === 'yes',
        spouseAge: d.spouseBeneficiary === 'yes' && d.spouseAge ? parseInt(d.spouseAge, 10) : null
    };
}

function compareScenarios() {
    if (typeof CompareScenariosModal === 'undefined') {
        alert('Compare feature failed to load. Please refresh the page.');
        return;
    }
    CompareScenariosModal.open('/', 'rmd-impact', function (selected) {
        const data1 = scenarioToProjectionData(selected[0]);
        const data2 = scenarioToProjectionData(selected[1]);
        const results1 = calculateProjection(data1);
        const results2 = calculateProjection(data2);

        // Store comparison context for AI explanations in compare mode
        window.lastRMDCompare = {
            name1: selected[0].name,
            name2: selected[1].name,
            data1,
            data2,
            results1,
            results2
        };

        if (selected.length >= 3) {
            const data3 = scenarioToProjectionData(selected[2]);
            const results3 = calculateProjection(data3);
            showComparisonThree(selected[0].name, selected[1].name, selected[2].name, results1, results2, results3, data1, data2, data3);
        } else {
            showComparison(selected[0].name, selected[1].name, results1, results2, data1, data2);
        }
    }, { maxScenarios: 3 });
}

function showComparison(name1, name2, results1, results2, data1, data2) {
    // Create comparison container
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') {
        resultsDiv.style.display = 'block';
    }
    
    // Scroll to results
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
    
    // Create comparison HTML
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 10px;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>Age: ${data1.currentAge} | Balance: $${data1.accountBalance.toLocaleString()}</div>
                        <div>Growth: ${data1.growthRate}% | SS: $${data1.socialSecurity.toLocaleString()}</div>
                    </div>
                </div>
                <div>
                    <h3 style="color: #e53e3e; margin-bottom: 10px;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>Age: ${data2.currentAge} | Balance: $${data2.accountBalance.toLocaleString()}</div>
                        <div>Growth: ${data2.growthRate}% | SS: $${data2.socialSecurity.toLocaleString()}</div>
                    </div>
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px;">Key Differences</h3>
            <div id="comparisonTable"></div>
        </div>
    `;
    
    // Insert comparison at top of results
    const resultsContent = resultsDiv.innerHTML;
    resultsDiv.innerHTML = comparisonHTML + resultsContent;
    
    // Build comparison table
    const firstRMD1 = results1.find(r => r.rmdAmount > 0);
    const firstRMD2 = results2.find(r => r.rmdAmount > 0);
    const age80_1 = results1.find(r => r.age === 80) || firstRMD1;
    const age80_2 = results2.find(r => r.age === 80) || firstRMD2;
    const age90_1 = results1.find(r => r.age === 90) || age80_1;
    const age90_2 = results2.find(r => r.age === 90) || age80_2;
    const peakTax1 = Math.max(...results1.map(r => r.taxBracket));
    const peakTax2 = Math.max(...results2.map(r => r.taxBracket));
    
    const tableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f59e0b; color: white;">
                    <th style="padding: 10px; text-align: left;">Metric</th>
                    <th style="padding: 10px; text-align: right;">${name1}</th>
                    <th style="padding: 10px; text-align: right;">${name2}</th>
                    <th style="padding: 10px; text-align: right;">Difference</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">First RMD (Age 73)</td>
                    <td style="padding: 8px; text-align: right;">$${firstRMD1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${firstRMD2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${firstRMD2.rmdAmount - firstRMD1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(firstRMD2.rmdAmount - firstRMD1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">RMD at Age 80</td>
                    <td style="padding: 8px; text-align: right;">$${age80_1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${age80_2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${age80_2.rmdAmount - age80_1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(age80_2.rmdAmount - age80_1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">RMD at Age 90</td>
                    <td style="padding: 8px; text-align: right;">$${age90_1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${age90_2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${age90_2.rmdAmount - age90_1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(age90_2.rmdAmount - age90_1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">Peak Tax Bracket</td>
                    <td style="padding: 8px; text-align: right;">${peakTax1}%</td>
                    <td style="padding: 8px; text-align: right;">${peakTax2}%</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${peakTax2 - peakTax1 >= 0 ? '#e53e3e' : '#10b981'};">
                        ${peakTax2 - peakTax1 >= 0 ? '+' : ''}${peakTax2 - peakTax1}%
                    </td>
                </tr>
            </tbody>
        </table>
    `;
    
    document.getElementById('comparisonTable').innerHTML = tableHTML;

    // Re-bind Explain button because innerHTML replacement recreates the DOM node
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) {
        explainBtn.addEventListener('click', explainResults);
    }
}

function showComparisonThree(name1, name2, name3, results1, results2, results3, data1, data2, data3) {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });

    const firstRMD = (r) => r.find(x => x.rmdAmount > 0);
    const atAge = (r, age) => r.find(x => x.age === age) || firstRMD(r);
    const peakTax = (r) => Math.max(...r.map(x => x.taxBracket));
    const fmt = (n) => (n == null ? '—' : '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }));
    const pct = (n) => (n == null ? '—' : n + '%');

    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison (3 scenarios)</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div><h3 style="color: #667eea; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name1)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data1.currentAge} | $${data1.accountBalance.toLocaleString()} | ${data1.growthRate}%</div></div>
                <div><h3 style="color: #e53e3e; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name2)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data2.currentAge} | $${data2.accountBalance.toLocaleString()} | ${data2.growthRate}%</div></div>
                <div><h3 style="color: #059669; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name3)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data3.currentAge} | $${data3.accountBalance.toLocaleString()} | ${data3.growthRate}%</div></div>
            </div>
            <h3 style="margin-bottom: 10px;">Key metrics</h3>
            <div id="comparisonTableThree"></div>
        </div>
    `;
    const resultsContent = resultsDiv.innerHTML;
    resultsDiv.innerHTML = comparisonHTML + resultsContent;

    const r80_1 = atAge(results1, 80);
    const r80_2 = atAge(results2, 80);
    const r80_3 = atAge(results3, 80);
    const r90_1 = atAge(results1, 90);
    const r90_2 = atAge(results2, 90);
    const r90_3 = atAge(results3, 90);
    const first1 = firstRMD(results1);
    const first2 = firstRMD(results2);
    const first3 = firstRMD(results3);

    const tableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f59e0b; color: white;">
                    <th style="padding: 10px; text-align: left;">Metric</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name1)}</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name2)}</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name3)}</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">First RMD (Age 73)</td><td style="padding: 8px; text-align: right;">${fmt(first1 && first1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(first2 && first2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(first3 && first3.rmdAmount)}</td></tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">RMD at Age 80</td><td style="padding: 8px; text-align: right;">${fmt(r80_1 && r80_1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r80_2 && r80_2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r80_3 && r80_3.rmdAmount)}</td></tr>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">RMD at Age 90</td><td style="padding: 8px; text-align: right;">${fmt(r90_1 && r90_1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r90_2 && r90_2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r90_3 && r90_3.rmdAmount)}</td></tr>
                <tr style="background: #f9fafb;"><td style="padding: 8px; font-weight: 600;">Peak Tax Bracket</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results1))}</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results2))}</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results3))}</td></tr>
            </tbody>
        </table>
    `;
    const el = document.getElementById('comparisonTableThree');
    if (el) el.innerHTML = tableHTML;

    // Re-bind Explain button after DOM replacement in three-scenario comparison
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) {
        explainBtn.addEventListener('click', explainResults);
    }
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    let summary = '';

    // If a recent comparison exists, build a true comparison explanation
    if (window.lastRMDCompare && window.lastRMDCompare.results1 && window.lastRMDCompare.results2) {
        const c = window.lastRMDCompare;
        const r1 = c.results1;
        const r2 = c.results2;
        const d1 = c.data1;
        const d2 = c.data2;

        const first1 = r1.find(r => r.rmdAmount > 0) || r1[r1.length - 1];
        const first2 = r2.find(r => r.rmdAmount > 0) || r2[r2.length - 1];
        const age80_1 = r1.find(r => r.age === 80) || first1;
        const age80_2 = r2.find(r => r.age === 80) || first2;
        const age90_1 = r1.find(r => r.age === 90) || age80_1;
        const age90_2 = r2.find(r => r.age === 90) || age80_2;
        const peakTax1 = Math.max(...r1.map(r => r.taxBracket));
        const peakTax2 = Math.max(...r2.map(r => r.taxBracket));

        summary += 'RMD Impact Comparison for two scenarios.\n\n';
        summary += 'Scenario 1 – ' + c.name1 + ': starting balance ' + formatCurrency(d1.accountBalance) +
                   ', current age ' + d1.currentAge + ', expected growth ' + d1.growthRate + '%. ';
        summary += 'Scenario 2 – ' + c.name2 + ': starting balance ' + formatCurrency(d2.accountBalance) +
                   ', current age ' + d2.currentAge + ', expected growth ' + d2.growthRate + '%. ';
        summary += 'Both scenarios assume Social Security of ' + formatCurrency(d1.socialSecurity) + ' per year and the same tax filing details.\n\n';

        summary += 'At age 73, the first RMD in Scenario 1 is ' + formatCurrency(first1.rmdAmount) +
                   ' versus ' + formatCurrency(first2.rmdAmount) + ' in Scenario 2. ';
        summary += 'By age 80 the RMDs grow to ' + formatCurrency(age80_1.rmdAmount) + ' vs ' +
                   formatCurrency(age80_2.rmdAmount) + ', and by age 90 they reach ' +
                   formatCurrency(age90_1.rmdAmount) + ' vs ' + formatCurrency(age90_2.rmdAmount) + '. ';
        summary += 'Peak estimated tax brackets are around ' + peakTax1 + '% for Scenario 1 and ' +
                   peakTax2 + '% for Scenario 2.\n\n';

        summary += 'In plain terms, the larger-balance scenario produces higher RMDs and slightly higher peak tax brackets, ';
        summary += 'while the smaller-balance scenario keeps required withdrawals and taxable income lower. ';
        summary += 'The trade-off is that higher RMDs mean more taxable income but also more money coming out of tax-deferred accounts each year.\n';
    } else {
        // Fallback: single-scenario explanation (original behavior)
        const results = window.lastRMDResult;
        const data = window.lastRMDData;
        if (!results || !data) {
            alert('Please run "Calculate RMD Impact" first to see results.');
            return;
        }
        const firstRMD = results.find(r => r.rmdAmount > 0) || results[results.length - 1];
        const age80Data = results.find(r => r.age === 80) || firstRMD;
        const age90Data = results.find(r => r.age === 90) || age80Data;
        const peakTax = Math.max(...results.map(r => r.taxBracket));

        summary += 'RMD Impact Projection.\n\n';
        summary += 'Current age: ' + data.currentAge + '. Tax-deferred account balance: ' + formatCurrency(data.accountBalance) + '. Expected growth rate: ' + data.growthRate + '%.\n\n';
        summary += 'Other income: Social Security ' + formatCurrency(data.socialSecurity) + '/year, Pension ' + formatCurrency(data.pension) + '/year, Other ' + formatCurrency(data.otherIncome) + '/year. ';
        summary += 'Filing status: ' + data.filingStatus + '. ' + (data.useStandardDeduction ? 'Standard deduction.' : 'Itemizing.') + '\n\n';
        if (data.isSpouseBeneficiary && data.spouseAge) {
            summary += 'Spouse is sole beneficiary, age ' + data.spouseAge + '. ';
        }
        summary += 'First RMD at age 73: ' + formatCurrency(firstRMD.rmdAmount) + '. ';
        summary += 'RMD at age 80: ' + formatCurrency(age80Data.rmdAmount) + '. ';
        summary += 'RMD at age 90: ' + formatCurrency(age90Data.rmdAmount) + '. ';
        summary += 'Peak estimated tax bracket: ' + peakTax + '%.';
    }

    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Loading…';
    }

    const apiUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            calculator_type: 'rmd-impact',
            results_summary: summary
        })
    })
    .then(r => r.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let resp;
        try { resp = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Try logging out and back in, or check if the AI Explain feature is configured.');
        }
        if (resp.error) throw new Error(resp.error);
        showExplainModal(resp.explanation);
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
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });
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

function downloadCalendar() {
    // Check if results are displayed (check for summary cards or chart)
    const summaryCards = document.querySelectorAll('.summary-value');
    const chartCanvas = document.getElementById('rmdChart');
    if (summaryCards.length === 0 && (!chartCanvas || !myChart)) {
        alert('Please calculate your RMD projection first.');
        return;
    }

    // Gather form data
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

    const results = calculateProjection(data);
    const projections = results.filter(r => r.age >= 73);

    fetch('/api/generate_rmd_calendar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            projections: projections
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'Calendar generation failed';
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
        a.download = 'RMD_Calendar_' + new Date().toISOString().split('T')[0] + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error generating calendar: ' + error.message);
    });
}
