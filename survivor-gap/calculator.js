// Survivor Gap Calculator
// Compares single-life vs joint-life annuity payouts and shows the cost of the survivor gap

const SURVIVOR_GAP_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)survivor-gap\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function displayResults(singleLifeMonthly, jointLifeMonthly, survivorYears, insurancePremium, yearsPayingPremiums, survivorPercent, discountRate) {
    const result = RBSurvivorGap.calculate({ singleLifeMonthly, jointLifeMonthly, survivorYears, survivorPercent, discountRate });
    const monthlyGap = result.optionCostMonthly;
    document.getElementById('summaryCards').innerHTML = '<div class="summary-grid">' + [
        ['Pension option cost while both alive / month', monthlyGap],
        ['Lost survivor income / month', result.survivorMonthly],
        ['Undiscounted survivor payments', result.totalPayments],
        ['Capital needed at death (present value)', result.presentValue]
    ].map(([label, value]) => `<div class="summary-card"><div class="summary-label">${label}</div><div class="summary-value">${formatCurrency(value)}</div></div>`).join('') + '</div>';
    const summary = `Single-life pension ${formatCurrency(singleLifeMonthly)}/month ends at death. Joint-life pension ${formatCurrency(jointLifeMonthly)}/month has a ${survivorPercent}% survivor continuation: ${formatCurrency(result.survivorMonthly)}/month for an assumed ${survivorYears} years. At ${discountRate}% effective annual discount rate, the capital needed at death is ${formatCurrency(result.presentValue)}. Payments are level, at month-end. No taxes, inflation, fees, mortality probabilities or insurance pricing are modeled. This is a finite-period income replacement estimate, not a guaranteed lifetime benefit or insurance quote.`;
    document.getElementById('interpretation').innerHTML = '<h3>What This Means</h3><p>' + summary + '</p><p>The pension option cost while both spouses live is separate from the survivor income lost after death. Actual contract terms, other survivor resources and the chosen survivor period must be reviewed.</p>';
    const insEl = document.getElementById('insuranceComparison');
    insEl.style.display = insurancePremium > 0 ? 'block' : 'none';
    if (insurancePremium > 0) {
        insEl.innerHTML = `<h3>Entered premium budget only</h3><p>${formatCurrency(insurancePremium)}/month for ${yearsPayingPremiums} years totals ${formatCurrency(insurancePremium * 12 * yearsPayingPremiums)} in undiscounted premiums. This input does not establish a purchasable death benefit, eligibility, policy duration, guarantees, or tax treatment.</p>`;
    }
    window.lastSurvivorGapResult = { summary, ...result };
    document.getElementById('results').style.display = 'block';
    createComparisonChart(singleLifeMonthly, jointLifeMonthly, monthlyGap);
    createCumulativeGapChart(result.survivorMonthly * 12, survivorYears);
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function createComparisonChart(singleLifeMonthly, jointLifeMonthly, monthlyGap) {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) return;

    if (window.comparisonChart instanceof Chart) {
        window.comparisonChart.destroy();
    }

    window.comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Single-Life', 'Joint-Life', 'Pension option cost'],
            datasets: [{
                label: 'Monthly Benefit ($)',
                data: [singleLifeMonthly, jointLifeMonthly, monthlyGap],
                backgroundColor: ['#667eea', '#48bb78', '#f59e0b'],
                borderColor: ['#5a67d8', '#38a169', '#d97706'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000).toFixed(0) + 'k';
                        }
                    }
                }
            }
        }
    });
}

function createCumulativeGapChart(annualGap, yearsInRetirement) {
    const ctx = document.getElementById('cumulativeGapChart');
    if (!ctx) return;

    if (window.cumulativeGapChart instanceof Chart) {
        window.cumulativeGapChart.destroy();
    }

    const years = [];
    const cumulative = [];
    for (let y = 1; y <= yearsInRetirement; y++) {
        years.push('Year ' + y);
        cumulative.push(annualGap * y);
    }

    window.cumulativeGapChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: years,
            datasets: [{
                label: 'Undiscounted survivor payments',
                data: cumulative,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                tension: 0.2,
                fill: true,
                pointRadius: years.length <= 25 ? 3 : 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Years after annuitant death' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Undiscounted survivor payments' },
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000).toFixed(0) + 'k';
                        }
                    }
                }
            }
        }
    });
}

document.getElementById('survivorGapForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const singleLifeMonthly = parseFloat(document.getElementById('singleLifeMonthly').value) || 0;
    const jointLifeMonthly = parseFloat(document.getElementById('jointLifeMonthly').value) || 0;
    const yearsInRetirement = Number(document.getElementById('yearsInRetirement').value);
    const insurancePremium = parseFloat(document.getElementById('insurancePremium').value) || 0;
    const yearsPayingPremiums = parseInt(document.getElementById('yearsPayingPremiums').value, 10) || yearsInRetirement;

    if (singleLifeMonthly <= 0 || jointLifeMonthly <= 0) {
        alert('Please enter valid monthly amounts for both annuity options.');
        return;
    }

    if (jointLifeMonthly >= singleLifeMonthly) {
        alert('Joint-life benefit should typically be lower than single-life. Please check your numbers.');
        return;
    }

    try {
        displayResults(singleLifeMonthly, jointLifeMonthly, yearsInRetirement, insurancePremium, yearsPayingPremiums,
            Number(document.getElementById('survivorPercent').value), Number(document.getElementById('discountRate').value));
    } catch (error) { alert(error.message); }
});

// Premium Save/Load + Explain
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    const r = window.lastSurvivorGapResult;
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
        body: JSON.stringify({ calculator_type: 'survivor-gap', results_summary: r.summary })
    })
    .then(res => res.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let data;
        try { data = JSON.parse(text); } catch (e) { throw new Error('Server returned an unexpected response. Try logging out and back in.'); }
        if (data.error) throw new Error(data.error);
        showExplainModal(data.explanation, { calculatorType: 'survivor-gap', resultsSummary: summary });
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Explain results: ' + err.message);
    });
}


function saveScenario() {
    if (!isPremiumUser) return;
    const scenarioName = prompt('Enter a name for this scenario:', 'My Survivor Gap Plan');
    if (!scenarioName) return;

    const formData = {
        modelVersion: 3,
        singleLifeMonthly: document.getElementById('singleLifeMonthly')?.value,
        jointLifeMonthly: document.getElementById('jointLifeMonthly')?.value,
        yearsInRetirement: document.getElementById('yearsInRetirement')?.value,
        survivorPercent: document.getElementById('survivorPercent')?.value,
        discountRate: document.getElementById('discountRate')?.value,
        insurancePremium: document.getElementById('insurancePremium')?.value,
        yearsPayingPremiums: document.getElementById('yearsPayingPremiums')?.value
    };

    rbScenarioFetch(SURVIVOR_GAP_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            calculator_type: 'survivor_gap',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text: text })))
    .then(({ ok, text }) => {
        let data;
        try { data = JSON.parse(text); } catch (_) { throw new Error(text || 'Server error'); }
        if (!ok) throw new Error(data.error || 'Save failed');
        return data;
    })
    .then(data => {
        if (data.success) {
            const statusEl = document.getElementById('saveStatus');
            if (statusEl) {
                statusEl.textContent = '✓ Saved!';
                setTimeout(() => { statusEl.textContent = ''; }, 3000);
            }
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
    if (!isPremiumUser) return;
    fetch(SURVIVOR_GAP_API_BASE + 'api/load_scenarios.php?calculator_type=survivor_gap')
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
            message += `${i + 1}. ${scenarioDisplayName(s)} (saved ${new Date(s.updated_at).toLocaleDateString()})\n`;
        });
        message += '\nExamples: Enter "1" to load, "d1" to delete';

        const choice = prompt(message + '\n\nEnter number or d+number:');
        if (!choice) return;

        if (choice.toLowerCase().startsWith('d')) {
            const index = parseInt(choice.substring(1)) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                if (confirm(`Delete "${scenarioDisplayName(scenario)}"? This cannot be undone.`)) {
                    rbScenarioFetch(SURVIVOR_GAP_API_BASE + 'api/delete_scenario.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ scenario_id: scenario.id })
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) alert('Scenario deleted!');
                        else alert('Error: ' + result.error);
                    });
                }
            }
        } else {
            const index = parseInt(choice) - 1;
            if (index >= 0 && index < data.scenarios.length) {
                const scenario = data.scenarios[index];
                document.getElementById('survivorPercent').value = 100;
                document.getElementById('discountRate').value = 0;
                Object.keys(scenario.data || {}).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) input.value = scenario.data[key] ?? '';
                });
                if (!scenario.data || scenario.data.modelVersion !== 3) {
                    document.getElementById('yearsInRetirement').value = '';
                    alert('Legacy scenario loaded. Enter the survivor duration after death and review the survivor percentage and discount rate before calculating. The old retirement duration was not a survivor period.');
                } else {
                    alert('Scenario loaded! Click Calculate to see results.');
                }
            }
        }
    })
    .catch(() => alert('Failed to load scenarios.'));
}
