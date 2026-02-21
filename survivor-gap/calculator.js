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

function displayResults(singleLifeMonthly, jointLifeMonthly, yearsInRetirement) {
    const monthlyGap = Math.max(0, singleLifeMonthly - jointLifeMonthly);
    const annualGap = monthlyGap * 12;
    const totalGap = annualGap * yearsInRetirement;
    const reductionPercent = singleLifeMonthly > 0
        ? ((singleLifeMonthly - jointLifeMonthly) / singleLifeMonthly * 100)
        : 0;

    // Summary cards
    let html = '<div class="summary-grid">';
    html += `
        <div class="summary-card">
            <div class="summary-label">Monthly Gap</div>
            <div class="summary-value">${formatCurrency(monthlyGap)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Annual Gap</div>
            <div class="summary-value">${formatCurrency(annualGap)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Gap (${yearsInRetirement} yrs)</div>
            <div class="summary-value">${formatCurrency(totalGap)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Joint-Life Reduction</div>
            <div class="summary-value">${reductionPercent.toFixed(1)}%</div>
        </div>
    `;
    html += '</div>';
    document.getElementById('summaryCards').innerHTML = html;

    // Interpretation
    let interpretation = '<h3>What This Means</h3><ul>';

    interpretation += `<li><strong>The monthly gap is ${formatCurrency(monthlyGap)}.</strong> `;
    interpretation += `By choosing joint-life instead of single-life, you receive ${formatCurrency(jointLifeMonthly)}/month instead of `;
    interpretation += `${formatCurrency(singleLifeMonthly)}/month—a ${reductionPercent.toFixed(1)}% reduction.</li>`;

    interpretation += `<li><strong>Over ${yearsInRetirement} years, that's ${formatCurrency(totalGap)} less in total payments.</strong> `;
    interpretation += `If you pass first, your survivor would have received that amount from the joint-life annuity. `;
    interpretation += `If you take single-life, they receive nothing from this annuity.</li>`;

    interpretation += `<li><strong>Life insurance could fill the gap.</strong> `;
    interpretation += `A life insurance policy with a death benefit of approximately ${formatCurrency(totalGap)} could provide your survivor with tax-free funds to replace the lost annuity income. `;
    interpretation += `Premiums paid during your working years may be lower than the ${formatCurrency(monthlyGap)}/month you're giving up with joint-life—especially if you're at peak earning years with a paid-off home and no dependents at home.</li>`;

    interpretation += `<li><strong>Get your exact numbers.</strong> `;
    interpretation += `Annuity payout amounts vary by provider (TIAA, state retirement systems, etc.) and depend on your age and your spouse's age. `;
    interpretation += `Use your plan's online estimator or contact them directly for precise single-life and joint-life quotes.</li>`;

    interpretation += '</ul>';
    document.getElementById('interpretation').innerHTML = interpretation;

    // Show results first so chart containers exist
    document.getElementById('results').style.display = 'block';

    // Create charts (after DOM is visible)
    createComparisonChart(singleLifeMonthly, jointLifeMonthly, monthlyGap);
    createCumulativeGapChart(annualGap, yearsInRetirement);

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
            labels: ['Single-Life', 'Joint-Life', 'Monthly Gap'],
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
                label: 'Cumulative Gap',
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
                    title: { display: true, text: 'Years in Retirement' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Cumulative Gap' },
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
    const yearsInRetirement = parseInt(document.getElementById('yearsInRetirement').value, 10) || 18;

    if (singleLifeMonthly <= 0 || jointLifeMonthly <= 0) {
        alert('Please enter valid monthly amounts for both annuity options.');
        return;
    }

    if (jointLifeMonthly >= singleLifeMonthly) {
        alert('Joint-life benefit should typically be lower than single-life. Please check your numbers.');
        return;
    }

    displayResults(singleLifeMonthly, jointLifeMonthly, yearsInRetirement);
});

// Premium Save/Load
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
});

function saveScenario() {
    if (!isPremiumUser) return;
    const scenarioName = prompt('Enter a name for this scenario:', 'My Survivor Gap Plan');
    if (!scenarioName) return;

    const formData = {
        singleLifeMonthly: document.getElementById('singleLifeMonthly')?.value,
        jointLifeMonthly: document.getElementById('jointLifeMonthly')?.value,
        yearsInRetirement: document.getElementById('yearsInRetirement')?.value
    };

    fetch(SURVIVOR_GAP_API_BASE + 'api/save_scenario.php', {
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
                    fetch(SURVIVOR_GAP_API_BASE + 'api/delete_scenario.php', {
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
                Object.keys(scenario.data).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) input.value = scenario.data[key];
                });
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    })
    .catch(() => alert('Failed to load scenarios.'));
}
