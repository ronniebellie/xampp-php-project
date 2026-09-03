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

function normalizeScenarioInputs(data) {
    const source = data || {};
    return {
        portfolioValue: parseFloat(source.portfolioValue),
        contributionAmount: source.contributionAmount === undefined || source.contributionAmount === null || source.contributionAmount === ''
            ? 0
            : parseFloat(source.contributionAmount),
        contributionFrequency: ['monthly', 'quarterly', 'annual'].includes(source.contributionFrequency)
            ? source.contributionFrequency
            : 'monthly',
        advisorFee: parseFloat(source.advisorFee),
        vanguardFee: parseFloat(source.vanguardFee),
        years: parseInt(source.years, 10),
        returnRate: parseFloat(source.returnRate)
    };
}

function projectScenario(inputs, feeRate) {
    return MVProjection.projectPortfolio({
        initialBalance: inputs.portfolioValue,
        annualReturnPct: inputs.returnRate,
        annualFeePct: feeRate,
        years: inputs.years,
        contributionAmount: inputs.contributionAmount,
        contributionFrequency: inputs.contributionFrequency
    });
}

// Main calculation
function calculate(showAlerts) {
    const inputs = normalizeScenarioInputs({
        portfolioValue: document.getElementById('portfolioValue').value,
        contributionAmount: document.getElementById('contributionAmount').value,
        contributionFrequency: document.getElementById('contributionFrequency').value,
        advisorFee: document.getElementById('advisorFee').value,
        vanguardFee: document.getElementById('vanguardFee').value,
        years: document.getElementById('years').value,
        returnRate: document.getElementById('returnRate').value
    });
    const { portfolioValue, contributionAmount, contributionFrequency, advisorFee, vanguardFee, years, returnRate } = inputs;
    const validationError = document.getElementById('validationError');

    const advisorFeeLabel = document.getElementById('advisorFeeLabel');
    if (advisorFeeLabel) advisorFeeLabel.textContent = advisorFee.toFixed(2).replace(/\.00$/, '') + '%';
    const yearsLabel = document.getElementById('yearsLabel');
    if (yearsLabel) yearsLabel.textContent = years + ' yrs';
    const returnRateLabel = document.getElementById('returnRateLabel');
    if (returnRateLabel) returnRateLabel.textContent = returnRate.toFixed(2).replace(/\.00$/, '') + '%';

    let errorMessage = '';
    if (isNaN(portfolioValue) || portfolioValue <= 0) {
        errorMessage = 'Enter a portfolio value greater than zero.';
    } else if (isNaN(advisorFee) || advisorFee < 0 || advisorFee > 5) {
        errorMessage = 'Enter an advisor fee between 0% and 5%.';
    } else if (isNaN(vanguardFee) || vanguardFee < 0 || vanguardFee > 1) {
        errorMessage = 'Enter a Vanguard expense ratio between 0% and 1%.';
    } else if (isNaN(contributionAmount) || contributionAmount < 0) {
        errorMessage = 'Enter an ongoing contribution of zero or more.';
    } else if (isNaN(years) || years < 1 || years > 50) {
        errorMessage = 'Choose an investment timeline between 1 and 50 years.';
    } else if (isNaN(returnRate) || returnRate < 0 || returnRate > 20) {
        errorMessage = 'Enter an expected return between 0% and 20%.';
    }

    if (errorMessage) {
        if (validationError) {
            validationError.textContent = errorMessage;
            validationError.style.display = 'block';
        } else if (showAlerts) {
            alert(errorMessage);
        }
        return;
    }

    if (validationError) validationError.style.display = 'none';
    // Both the UI and exports consume these same annualized monthly projections.
    const managedProjection = projectScenario(inputs, advisorFee);
    const vanguardProjection = projectScenario(inputs, vanguardFee);
    const managedData = managedProjection.yearlyData;
    const vanguardData = vanguardProjection.yearlyData;
    
    // Get key values
    const midYear = Math.floor(years / 2);
    const managedFinal = managedProjection.finalBalance;
    const vanguardFinal = vanguardProjection.finalBalance;
    const opportunityCost = vanguardFinal - managedFinal;
    
    const managedYear1Fee = managedData[1].fee;
    const vanguardYear1Fee = vanguardData[1].fee;
    
    const managedMidValue = managedData[midYear].balance;
    const vanguardMidValue = vanguardData[midYear].balance;
    
    const managedTotalFees = managedProjection.cumulativeFees;
    const vanguardTotalFees = vanguardProjection.cumulativeFees;
    
    const directFeeDiff = managedTotalFees - vanguardTotalFees;
    const lostGrowth = opportunityCost - directFeeDiff;
    
    // Update results
    document.getElementById('resultYears').textContent = years;
    document.getElementById('opportunityCost').textContent = formatCurrency(opportunityCost);
    const avgAnnualEl = document.getElementById('avgAnnualCost');
    if (avgAnnualEl) avgAnnualEl.textContent = formatCurrency(years > 0 ? opportunityCost / years : 0);
    const breakdownFeesEl = document.getElementById('breakdownFees');
    const breakdownGrowthEl = document.getElementById('breakdownGrowth');
    const breakdownTotalEl = document.getElementById('breakdownTotal');
    if (breakdownFeesEl) breakdownFeesEl.textContent = formatCurrency(directFeeDiff);
    if (breakdownGrowthEl) breakdownGrowthEl.textContent = formatCurrency(lostGrowth);
    if (breakdownTotalEl) breakdownTotalEl.textContent = formatCurrency(opportunityCost);
    const contributionSummary = document.getElementById('contributionSummary');
    const totalContributions = document.getElementById('totalContributions');
    const totalInvestedCapital = document.getElementById('totalInvestedCapital');
    if (contributionSummary) contributionSummary.textContent = formatCurrency(contributionAmount) + ' ' + contributionFrequency;
    if (totalContributions) totalContributions.textContent = formatCurrency(managedProjection.cumulativeContributions) + ' total contributions';
    if (totalInvestedCapital) totalInvestedCapital.textContent = formatCurrency(managedProjection.totalInvestedCapital) + ' total invested';
    
    // Update fee labels
    const managedFeeText = advisorFee.toFixed(2).replace(/\.00$/, '') + '% fee';
    document.getElementById('managedFeeLabel').textContent = managedFeeText;
    const managedFeeLabelPortfolio = document.getElementById('managedFeeLabelPortfolio');
    if (managedFeeLabelPortfolio) managedFeeLabelPortfolio.textContent = managedFeeText;
    const vanguardFeeText = vanguardFee.toFixed(2).replace(/\.00$/, '') + '% fee';
    document.querySelectorAll('.vanguard-fee-label').forEach(function(el) {
        el.textContent = vanguardFeeText;
    });
    // Update comparison table
    document.getElementById('managedYear1Fee').textContent = formatCurrency(managedYear1Fee);
    document.getElementById('vanguardYear1Fee').textContent = formatCurrency(vanguardYear1Fee);
    document.getElementById('year1FeeDiff').textContent = formatCurrency(managedYear1Fee - vanguardYear1Fee);
    
    document.getElementById('midYearLabel').textContent = midYear;
    document.getElementById('managedMidValue').textContent = formatCurrency(managedMidValue);
    document.getElementById('vanguardMidValue').textContent = formatCurrency(vanguardMidValue);
    document.getElementById('midValueDiff').textContent = formatCurrency(vanguardMidValue - managedMidValue);
    
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
    document.getElementById('insightLostGrowth').textContent = formatCurrency(lostGrowth);
    document.getElementById('insightBeatBy').textContent = (advisorFee - vanguardFee).toFixed(2) + '%';
    
    // Create charts
    createChart(managedData, vanguardData, years);
    createFeesChart(managedData, vanguardData, years);
    
    // Store result for PDF/CSV
    window.lastMVResult = {
        portfolioValue,
        contributionAmount,
        contributionFrequency,
        advisorFee,
        vanguardFee,
        years,
        returnRate,
        managedData,
        vanguardData,
        managedProjection,
        vanguardProjection,
        cumulativeContributions: managedProjection.cumulativeContributions,
        totalInvestedCapital: managedProjection.totalInvestedCapital,
        opportunityCost,
        directFeeDiff,
        lostGrowth,
        managedFinal,
        vanguardFinal,
        managedTotalFees,
        vanguardTotalFees
    };
    
    // Show results only when user clicked Calculate, not on load or slider drag
    if (showAlerts) {
        document.getElementById('results').style.display = 'block';
        document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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
            maintainAspectRatio: false,
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
            maintainAspectRatio: false,
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
document.getElementById('calculateBtn').addEventListener('click', function() {
    calculate(true);
});

['portfolioValue', 'contributionAmount', 'advisorFee', 'vanguardFee', 'years', 'returnRate'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', function() {
        calculate(false);
    });
});

const contributionFrequencySelect = document.getElementById('contributionFrequency');
if (contributionFrequencySelect) {
    contributionFrequencySelect.addEventListener('change', function() {
        calculate(false);
    });
}

window.addEventListener('load', function() {
    calculate(false); // Update labels and result data; results panel stays hidden until user clicks Calculate
});
// Premium Save/Load/Compare/PDF/CSV
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My Comparison');
    if (!scenarioName) return;
    
    const formData = {
        portfolioValue: document.getElementById('portfolioValue')?.value,
        contributionAmount: document.getElementById('contributionAmount')?.value,
        contributionFrequency: document.getElementById('contributionFrequency')?.value,
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

function scenarioDisplayName(scenario) {
    return (scenario && (scenario.scenario_name || scenario.name)) || 'Untitled scenario';
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
            message += `${i + 1}. ${scenarioDisplayName(s)} (saved ${new Date(s.updated_at).toLocaleDateString()})\\n`;
        });
        message += '\\nExamples: Enter "1" to load, "d1" to delete';
        
        const choice = prompt(message + '\\n\\nEnter number or d+number:');
        if (!choice) return;
        
        if (choice.toLowerCase().startsWith('d')) {
            const index = parseInt(choice.substring(1)) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                if (confirm(`Delete "${scenarioDisplayName(scenario)}"? This cannot be undone.`)) {
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
                const normalized = normalizeScenarioInputs(scenario.data);
                Object.keys(normalized).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) input.value = normalized[key];
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
        data.scenarios.forEach((s, i) => { message += `${i + 1}. ${scenarioDisplayName(s)}\n`; });
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
        const d1 = normalizeScenarioInputs(s1.data);
        const d2 = normalizeScenarioInputs(s2.data);
        const managed1 = projectScenario(d1, d1.advisorFee);
        const managed2 = projectScenario(d2, d2.advisorFee);
        const vanguard1 = projectScenario(d1, d1.vanguardFee);
        const vanguard2 = projectScenario(d2, d2.vanguardFee);
        const opp1 = vanguard1.finalBalance - managed1.finalBalance;
        const opp2 = vanguard2.finalBalance - managed2.finalBalance;
        showMVComparison(scenarioDisplayName(s1), scenarioDisplayName(s2), managed1, managed2, vanguard1, vanguard2, d1, d2, opp1, opp2);
    })
    .catch(() => alert('Failed to load scenarios.'));
}

function showMVComparison(name1, name2, m1, m2, v1, v2, d1, d2, opp1, opp2) {
    const resultsDiv = document.getElementById('results');
    const comparisonContainer = document.getElementById('mvComparisonContainer');
    if (!comparisonContainer) return;

    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });

    comparisonContainer.innerHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea;">${escapeHtml(name1)}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Portfolio: ${formatCurrency(d1.portfolioValue)} | Contribution: ${formatCurrency(d1.contributionAmount)} ${d1.contributionFrequency} | Fee: ${d1.advisorFee}% | Years: ${d1.years} | Return: ${d1.returnRate}%
                    </div>
                    <div style="margin-top: 8px;"><strong>Final value (managed):</strong> ${formatCurrency(m1.finalBalance)}</div>
                    <div><strong>Opportunity cost:</strong> ${formatCurrency(opp1)}</div>
                </div>
                <div>
                    <h3 style="color: #e53e3e;">${escapeHtml(name2)}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Portfolio: ${formatCurrency(d2.portfolioValue)} | Contribution: ${formatCurrency(d2.contributionAmount)} ${d2.contributionFrequency} | Fee: ${d2.advisorFee}% | Years: ${d2.years} | Return: ${d2.returnRate}%
                    </div>
                    <div style="margin-top: 8px;"><strong>Final value (managed):</strong> ${formatCurrency(m2.finalBalance)}</div>
                    <div><strong>Opportunity cost:</strong> ${formatCurrency(opp2)}</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 520px;">
                <tr style="background: #f0f0f0;"><th>Metric</th><th>${escapeHtml(name1)}</th><th>${escapeHtml(name2)}</th><th>Difference</th></tr>
                <tr><td>Total contributions</td><td>${formatCurrency(m1.cumulativeContributions)}</td><td>${formatCurrency(m2.cumulativeContributions)}</td><td>${formatCurrency(m2.cumulativeContributions - m1.cumulativeContributions)}</td></tr>
                <tr><td>Final value (managed)</td><td>${formatCurrency(m1.finalBalance)}</td><td>${formatCurrency(m2.finalBalance)}</td><td>${formatCurrency(m2.finalBalance - m1.finalBalance)}</td></tr>
                <tr><td>Final value (Vanguard)</td><td>${formatCurrency(v1.finalBalance)}</td><td>${formatCurrency(v2.finalBalance)}</td><td>${formatCurrency(v2.finalBalance - v1.finalBalance)}</td></tr>
                <tr><td>Opportunity cost</td><td>${formatCurrency(opp1)}</td><td>${formatCurrency(opp2)}</td><td>${formatCurrency(opp2 - opp1)}</td></tr>
                <tr><td>Total fees (managed)</td><td>${formatCurrency(m1.cumulativeFees)}</td><td>${formatCurrency(m2.cumulativeFees)}</td><td>${formatCurrency(m2.cumulativeFees - m1.cumulativeFees)}</td></tr>
            </table>
            </div>
        </div>
    `;
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    const res = window.lastMVResult;
    if (!res) {
        alert('Please run "Calculate True Cost" first to see results.');
        return;
    }
    let summary = 'Managed Portfolio vs Vanguard Index Fund comparison.\n\n';
    summary += 'Starting portfolio value: ' + formatCurrency(res.portfolioValue) + '. Ongoing contribution: ' + formatCurrency(res.contributionAmount) + ' ' + res.contributionFrequency + '. ';
    summary += 'Total contributions: ' + formatCurrency(res.cumulativeContributions) + '. Total invested capital: ' + formatCurrency(res.totalInvestedCapital) + '. ';
    summary += 'Advisor fee: ' + res.advisorFee + '%. Vanguard fee: ' + res.vanguardFee + '%. ';
    summary += 'Timeline: ' + res.years + ' years. Expected annual return (before fees): ' + res.returnRate + '%. All values are pre-tax.\n\n';
    summary += 'OPPORTUNITY COST BREAKDOWN (do not double-count):\n';
    summary += '- Total Opportunity Cost (grand total): ' + formatCurrency(res.opportunityCost) + '\n';
    summary += '- Direct Fee Difference (paid out of pocket): ' + formatCurrency(res.directFeeDiff) + '\n';
    summary += '- Lost Growth (fees removed from market, could not compound): ' + formatCurrency(res.lostGrowth) + '\n';
    summary += 'Relationship: Total Opportunity Cost = Direct Fee Difference + Lost Growth.\n\n';
    summary += 'Final portfolio: Managed ' + formatCurrency(res.managedFinal) + ' vs Vanguard ' + formatCurrency(res.vanguardFinal) + '. ';
    summary += 'Total fees paid: Managed ' + formatCurrency(res.managedTotalFees) + ' vs Vanguard ' + formatCurrency(res.vanguardTotalFees) + '. ';
    summary += 'Advisor must beat Vanguard by ' + (res.advisorFee - res.vanguardFee).toFixed(2) + '% annually to justify their fee. Most do not.';

    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Loading…';
    }

    const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(explainUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            calculator_type: 'managed-vs-vanguard',
            results_summary: summary
        })
    })
    .then(r => r.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let resp;
        try { resp = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Try logging out and back in.');
        }
        if (resp.error) throw new Error(resp.error);
        showExplainModal(resp.explanation, { calculatorType: 'managed-vs-vanguard', resultsSummary: summary });
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Explain results: ' + err.message);
    });
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
        contributionAmount: res.contributionAmount,
        contributionFrequency: res.contributionFrequency,
        cumulativeContributions: res.cumulativeContributions,
        totalInvestedCapital: res.totalInvestedCapital,
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
        portfolioValue: res.portfolioValue,
        contributionAmount: res.contributionAmount,
        contributionFrequency: res.contributionFrequency,
        cumulativeContributions: res.cumulativeContributions,
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
