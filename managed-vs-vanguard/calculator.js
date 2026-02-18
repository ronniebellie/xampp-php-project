// API base URL so api/... always resolves correctly
const MV_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)managed-vs-vanguard\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Calculate portfolio growth
function calculatePortfolio(principal, annualReturn, feeRate, years) {
    const netReturn = (annualReturn - feeRate) / 100;
    let yearlyData = [];
    let balance = principal;
    let totalFees = 0;
    
    for (let year = 0; year <= years; year++) {
        const yearFee = balance * (feeRate / 100);
        totalFees += yearFee;
        
        yearlyData.push({
            year: year,
            balance: balance,
            fee: yearFee,
            totalFees: totalFees
        });
        
        // Grow for next year (after fees)
        balance = balance * (1 + netReturn);
    }
    
    return yearlyData;
}

// Main calculation
function calculate() {
    // Get inputs
    const portfolioValue = parseFloat(document.getElementById('portfolioValue').value);
    const advisorFee = parseFloat(document.getElementById('advisorFee').value);
    const vanguardFee = parseFloat(document.getElementById('vanguardFee').value);
    const years = parseInt(document.getElementById('years').value);
    const returnRate = parseFloat(document.getElementById('returnRate').value);
    
    // Validate inputs
    if (isNaN(portfolioValue) || isNaN(advisorFee) || isNaN(years) || isNaN(returnRate)) {
        alert('Please enter valid numbers for all fields');
        return;
    }
    
    // Calculate both scenarios
    const managedData = calculatePortfolio(portfolioValue, returnRate, advisorFee, years);
    const vanguardData = calculatePortfolio(portfolioValue, returnRate, vanguardFee, years);
    
    // Get key values
    const midYear = Math.floor(years / 2);
    const managedFinal = managedData[years].balance;
    const vanguardFinal = vanguardData[years].balance;
    const opportunityCost = vanguardFinal - managedFinal;
    
    const managedYear1Fee = managedData[1].fee;
    const vanguardYear1Fee = vanguardData[1].fee;
    
    const managedMidValue = managedData[midYear].balance;
    const vanguardMidValue = vanguardData[midYear].balance;
    
    const managedTotalFees = managedData[years].totalFees;
    const vanguardTotalFees = vanguardData[years].totalFees;
    
    const directFeeDiff = managedTotalFees - vanguardTotalFees;
    const lostGrowth = opportunityCost - directFeeDiff;
    
    // Update results
    document.getElementById('resultYears').textContent = years;
    document.getElementById('opportunityCost').textContent = formatCurrency(opportunityCost);
    
    // Update fee label
    document.getElementById('managedFeeLabel').textContent = advisorFee + '% fee';
    
    // Update comparison table
    document.getElementById('managedYear1Fee').textContent = formatCurrency(managedYear1Fee);
    document.getElementById('vanguardYear1Fee').textContent = formatCurrency(vanguardYear1Fee);
    document.getElementById('year1FeeDiff').textContent = formatCurrency(managedYear1Fee - vanguardYear1Fee);
    
    document.getElementById('midYearLabel').textContent = midYear;
    document.getElementById('managedMidValue').textContent = formatCurrency(managedMidValue);
    document.getElementById('vanguardMidValue').textContent = formatCurrency(vanguardMidValue);
    document.getElementById('midValueDiff').textContent = formatCurrency(managedMidValue - vanguardMidValue);
    
    document.getElementById('finalYearLabel').textContent = years;
    document.getElementById('managedFinalValue').textContent = formatCurrency(managedFinal);
    document.getElementById('vanguardFinalValue').textContent = formatCurrency(vanguardFinal);
    document.getElementById('finalValueDiff').textContent = formatCurrency(opportunityCost);
    
    document.getElementById('managedTotalFees').textContent = formatCurrency(managedTotalFees);
    document.getElementById('vanguardTotalFees').textContent = formatCurrency(vanguardTotalFees);
    document.getElementById('totalFeesDiff').textContent = formatCurrency(directFeeDiff);
    
    // Update insights
    document.getElementById('insightDirectFees').textContent = formatCurrency(directFeeDiff);
    document.getElementById('insightYears').textContent = years;
    document.getElementById('insightLostGrowth').textContent = formatCurrency(lostGrowth > 0 ? lostGrowth : opportunityCost);
    document.getElementById('insightBeatBy').textContent = (advisorFee - vanguardFee).toFixed(2) + '%';
    
    // Create charts
    createChart(managedData, vanguardData, years);
    createFeesChart(managedData, vanguardData, years);
    
    // Store result for PDF/CSV
    window.lastMVResult = {
        portfolioValue,
        advisorFee,
        vanguardFee,
        years,
        returnRate,
        managedData,
        vanguardData,
        opportunityCost,
        directFeeDiff,
        lostGrowth,
        managedFinal,
        vanguardFinal,
        managedTotalFees,
        vanguardTotalFees
    };
    
    // Show results
    document.getElementById('results').style.display = 'block';
    
    // Scroll to results
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Create charts
let chartInstance = null;
let feesChartInstance = null;

function createChart(managedData, vanguardData, years) {
    const ctx = document.getElementById('growthChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    // Prepare data
    const labels = managedData.map(d => 'Year ' + d.year);
    const managedValues = managedData.map(d => d.balance);
    const vanguardValues = vanguardData.map(d => d.balance);
    
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Managed Portfolio',
                    data: managedValues,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Vanguard VTSAX',
                    data: vanguardValues,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
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
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        },
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function createFeesChart(managedData, vanguardData, years) {
    const ctx = document.getElementById('feesChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (feesChartInstance) {
        feesChartInstance.destroy();
    }
    
    // Prepare data
    const labels = managedData.map(d => 'Year ' + d.year);
    const managedFees = managedData.map(d => d.totalFees);
    const vanguardFees = vanguardData.map(d => d.totalFees);
    
    feesChartInstance = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Managed Portfolio Fees',
                    data: managedFees,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Vanguard VTSAX Fees',
                    data: vanguardFees,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        },
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Cumulative Fees Paid'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Year'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

// Event listener
document.getElementById('calculateBtn').addEventListener('click', calculate);

// Allow Enter key to calculate
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            calculate();
        }
    });
});

// Calculate on page load with default values
window.addEventListener('load', function() {
    // Optional: Auto-calculate on load
    // calculate();
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
    const scenarioName = prompt('Enter a name for this scenario:', 'My Comparison');
    if (!scenarioName) return;
    
    const formData = {
        portfolioValue: document.getElementById('portfolioValue')?.value,
        advisorFee: document.getElementById('advisorFee')?.value,
        vanguardFee: document.getElementById('vanguardFee')?.value,
        years: document.getElementById('years')?.value,
        returnRate: document.getElementById('returnRate')?.value
    };
    
    fetch(MV_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'managed-vs-vanguard',
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
    fetch(MV_API_BASE + 'api/load_scenarios.php?calculator_type=managed-vs-vanguard')
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
        
        let message = 'Select a scenario to load (or type "d" + number to delete):\\n\\n';
        data.scenarios.forEach((s, i) => {
            message += `${i + 1}. ${s.name} (saved ${new Date(s.updated_at).toLocaleDateString()})\\n`;
        });
        message += '\\nExamples: Enter "1" to load, "d1" to delete';
        
        const choice = prompt(message + '\\n\\nEnter number or d+number:');
        if (!choice) return;
        
        if (choice.toLowerCase().startsWith('d')) {
            const index = parseInt(choice.substring(1)) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                if (confirm(`Delete "${scenario.name}"? This cannot be undone.`)) {
                    fetch(MV_API_BASE + 'api/delete_scenario.php', {
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
    fetch(MV_API_BASE + 'api/load_scenarios.php?calculator_type=managed-vs-vanguard')
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
        const d1 = {
            portfolioValue: parseFloat(s1.data.portfolioValue),
            advisorFee: parseFloat(s1.data.advisorFee),
            vanguardFee: parseFloat(s1.data.vanguardFee),
            years: parseInt(s1.data.years),
            returnRate: parseFloat(s1.data.returnRate)
        };
        const d2 = {
            portfolioValue: parseFloat(s2.data.portfolioValue),
            advisorFee: parseFloat(s2.data.advisorFee),
            vanguardFee: parseFloat(s2.data.vanguardFee),
            years: parseInt(s2.data.years),
            returnRate: parseFloat(s2.data.returnRate)
        };
        const res1 = calculatePortfolio(d1.portfolioValue, d1.returnRate, d1.advisorFee, d1.years);
        const res2 = calculatePortfolio(d2.portfolioValue, d2.returnRate, d2.advisorFee, d2.years);
        const v1 = calculatePortfolio(d1.portfolioValue, d1.returnRate, d1.vanguardFee || 0.04, d1.years);
        const v2 = calculatePortfolio(d2.portfolioValue, d2.returnRate, d2.vanguardFee || 0.04, d2.years);
        const opp1 = v1[v1.length - 1].balance - res1[res1.length - 1].balance;
        const opp2 = v2[v2.length - 1].balance - res2[res2.length - 1].balance;
        showMVComparison(s1.name, s2.name, res1, res2, v1, v2, d1, d2, opp1, opp2);
    })
    .catch(() => alert('Failed to load scenarios.'));
}

function showMVComparison(name1, name2, m1, m2, v1, v2, d1, d2, opp1, opp2) {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Portfolio: ${formatCurrency(d1.portfolioValue)} | Fee: ${d1.advisorFee}% | Years: ${d1.years} | Return: ${d1.returnRate}%
                    </div>
                    <div style="margin-top: 8px;"><strong>Final value (managed):</strong> ${formatCurrency(m1[m1.length - 1].balance)}</div>
                    <div><strong>Opportunity cost:</strong> ${formatCurrency(opp1)}</div>
                </div>
                <div>
                    <h3 style="color: #e53e3e;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Portfolio: ${formatCurrency(d2.portfolioValue)} | Fee: ${d2.advisorFee}% | Years: ${d2.years} | Return: ${d2.returnRate}%
                    </div>
                    <div style="margin-top: 8px;"><strong>Final value (managed):</strong> ${formatCurrency(m2[m2.length - 1].balance)}</div>
                    <div><strong>Opportunity cost:</strong> ${formatCurrency(opp2)}</div>
                </div>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f0f0f0;"><th>Metric</th><th>${name1}</th><th>${name2}</th><th>Difference</th></tr>
                <tr><td>Final value (managed)</td><td>${formatCurrency(m1[m1.length - 1].balance)}</td><td>${formatCurrency(m2[m2.length - 1].balance)}</td><td>${formatCurrency(m2[m2.length - 1].balance - m1[m1.length - 1].balance)}</td></tr>
                <tr><td>Final value (Vanguard)</td><td>${formatCurrency(v1[v1.length - 1].balance)}</td><td>${formatCurrency(v2[v2.length - 1].balance)}</td><td>${formatCurrency(v2[v2.length - 1].balance - v1[v1.length - 1].balance)}</td></tr>
                <tr><td>Opportunity cost</td><td>${formatCurrency(opp1)}</td><td>${formatCurrency(opp2)}</td><td>${formatCurrency(opp2 - opp1)}</td></tr>
                <tr><td>Total fees (managed)</td><td>${formatCurrency(m1[m1.length - 1].totalFees)}</td><td>${formatCurrency(m2[m2.length - 1].totalFees)}</td><td>${formatCurrency(m2[m2.length - 1].totalFees - m1[m1.length - 1].totalFees)}</td></tr>
            </table>
        </div>
    `;
    const existingContent = resultsDiv.innerHTML;
    resultsDiv.innerHTML = comparisonHTML + existingContent;
}

function downloadPDF() {
    const res = window.lastMVResult;
    if (!res) {
        alert('Please run Calculate first, then download the PDF.');
        return;
    }
    const chartCanvas1 = document.getElementById('growthChart');
    const chartCanvas2 = document.getElementById('feesChart');
    const chartImage1 = chartCanvas1 && window.Chart ? chartCanvas1.toDataURL('image/png') : null;
    const chartImage2 = chartCanvas2 && window.Chart ? chartCanvas2.toDataURL('image/png') : null;
    const payload = {
        portfolioValue: res.portfolioValue,
        advisorFee: res.advisorFee,
        vanguardFee: res.vanguardFee,
        years: res.years,
        returnRate: res.returnRate,
        opportunityCost: res.opportunityCost,
        directFeeDiff: res.directFeeDiff,
        lostGrowth: res.lostGrowth,
        managedFinal: res.managedFinal,
        vanguardFinal: res.vanguardFinal,
        managedTotalFees: res.managedTotalFees,
        vanguardTotalFees: res.vanguardTotalFees,
        managedData: res.managedData,
        vanguardData: res.vanguardData,
        chartImage1: chartImage1,
        chartImage2: chartImage2
    };
    fetch(MV_API_BASE + 'api/generate_mv_pdf.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'PDF failed'); } catch (e) { throw new Error(t || 'PDF failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) return r.text().then(t => { throw new Error('Server did not return a PDF.'); });
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Managed_vs_Vanguard_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Download PDF: ' + e.message));
}

function downloadCSV() {
    const res = window.lastMVResult;
    if (!res) {
        alert('Please run Calculate first, then export CSV.');
        return;
    }
    const payload = {
        managedData: res.managedData,
        vanguardData: res.vanguardData
    };
    fetch(MV_API_BASE + 'api/export_mv_csv.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'CSV failed'); } catch (e) { throw new Error(t || 'CSV failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) throw new Error('Server did not return CSV.');
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Managed_vs_Vanguard_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Export CSV: ' + e.message));
}