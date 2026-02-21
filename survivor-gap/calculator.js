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

function displayResults(singleLifeMonthly, jointLifeMonthly, yearsInRetirement, insurancePremium, yearsPayingPremiums) {
    const monthlyGap = Math.max(0, singleLifeMonthly - jointLifeMonthly);
    const annualGap = monthlyGap * 12;
    const totalGap = annualGap * yearsInRetirement;
    const reductionPercent = singleLifeMonthly > 0
        ? ((singleLifeMonthly - jointLifeMonthly) / singleLifeMonthly * 100)
        : 0;
    const increasePercent = jointLifeMonthly > 0
        ? ((singleLifeMonthly - jointLifeMonthly) / jointLifeMonthly * 100)
        : 0;

    // Insurance comparison (optional)
    const insEl = document.getElementById('insuranceComparison');
    if (insurancePremium > 0) {
        const totalPremiums = insurancePremium * 12 * (yearsPayingPremiums || yearsInRetirement);
        const netMonthlyWithInsurance = singleLifeMonthly - insurancePremium;
        const monthlySavingsVsJoint = netMonthlyWithInsurance - jointLifeMonthly;
        const premiumVsGap = monthlyGap - insurancePremium;

        let html = '<h3 style="color: #166534; margin-top: 0;">How Life Insurance Fills the Gap</h3>';
        html += '<p><strong>Strategy:</strong> Take single-life (higher annuity) + buy life insurance. When you pass, your survivor receives the death benefit tax-free—roughly equal to the total gap. (Often, whole life premiums are lower than the monthly gap for equivalent coverage.)</p>';
        html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';
        html += `<div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0;"><strong>Total premiums paid</strong><br><span style="font-size: 1.3em;">${formatCurrency(totalPremiums)}</span></div>`;
        html += `<div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0;"><strong>Survivor receives (death benefit)</strong><br><span style="font-size: 1.3em;">${formatCurrency(totalGap)}</span> <small style="color: #166534;">tax-free</small></div>`;
        html += `<div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0;"><strong>Your net monthly</strong><br><span style="font-size: 1.3em;">${formatCurrency(netMonthlyWithInsurance)}</span><br><small>single-life minus premium</small></div>`;
        html += '</div>';
        html += '<ul style="margin: 0; padding-left: 20px;">';
        html += `<li><strong>Monthly cost comparison:</strong> Joint-life costs you ${formatCurrency(monthlyGap)}/month in foregone income. Insurance costs ${formatCurrency(insurancePremium)}/month. `;
        if (premiumVsGap > 0) {
            html += `Insurance is ${formatCurrency(premiumVsGap)}/month <em>less</em> than the joint-life reduction—you keep more while alive and still protect your survivor.</li>`;
        } else if (premiumVsGap < 0) {
            html += `Insurance costs ${formatCurrency(-premiumVsGap)}/month more than the joint-life reduction; the trade-off is a tax-free lump sum for your survivor.</li>`;
        } else {
            html += `Same monthly cost either way; insurance gives your survivor a tax-free lump sum.</li>`;
        }
        if (monthlySavingsVsJoint > 0) {
            html += `<li><strong>vs joint-life:</strong> With single-life + insurance, you receive ${formatCurrency(monthlySavingsVsJoint)}/month more than with joint-life alone.</li>`;
        }
        html += '</ul>';
        html += '<div style="margin-top: 20px; height: 220px;"><canvas id="insuranceComparisonChart"></canvas></div>';
        insEl.innerHTML = html;
        insEl.style.display = 'block';
        createInsuranceComparisonChart(monthlyGap, insurancePremium);
    } else {
        insEl.style.display = 'none';
    }

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
    interpretation += `By choosing a single-life annuity instead of a joint-life annuity, you receive ${formatCurrency(singleLifeMonthly)}/month—${formatCurrency(monthlyGap)} more per month compared to a joint-life annuity, a ${increasePercent.toFixed(1)}% increase. `;
    interpretation += `Over ${yearsInRetirement} years, this totals ${formatCurrency(totalGap)} in savings.</li>`;

    interpretation += `<li><strong>But if you pass first, your survivor receives nothing from this annuity under single-life.</strong> `;
    interpretation += `With joint-life, they would have continued to receive ${formatCurrency(jointLifeMonthly)}/month. `;
    interpretation += `That's why life insurance can be a way to keep your higher single-life payments and still protect your survivor.</li>`;

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

function createInsuranceComparisonChart(monthlyGap, insurancePremium) {
    const ctx = document.getElementById('insuranceComparisonChart');
    if (!ctx) return;

    if (window.insuranceComparisonChart instanceof Chart) {
        window.insuranceComparisonChart.destroy();
    }

    window.insuranceComparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Joint-life reduction\n(foregone income)', 'Insurance premium'],
            datasets: [{
                label: 'Monthly cost ($)',
                data: [monthlyGap, insurancePremium],
                backgroundColor: ['#f59e0b', '#22c55e'],
                borderColor: ['#d97706', '#16a34a'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.raw) + '/month';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Monthly cost ($)' },
                    ticks: {
                        callback: function(v) { return '$' + (v / 1000).toFixed(1) + 'k'; }
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

    displayResults(singleLifeMonthly, jointLifeMonthly, yearsInRetirement, insurancePremium, yearsPayingPremiums);
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
        yearsInRetirement: document.getElementById('yearsInRetirement')?.value,
        insurancePremium: document.getElementById('insurancePremium')?.value,
        yearsPayingPremiums: document.getElementById('yearsPayingPremiums')?.value
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
                Object.keys(scenario.data || {}).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) input.value = scenario.data[key] ?? '';
                });
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    })
    .catch(() => alert('Failed to load scenarios.'));
}
