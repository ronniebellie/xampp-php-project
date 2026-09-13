// Future Value Calculator - All-in-One

// API base URL
const FV_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)future-value-app\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

// Tab switching
function switchCalculator(type) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update calculator content
    document.querySelectorAll('.calculator-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${type}-calculator`).classList.add('active');
}

// Update label when single type changes
document.addEventListener('DOMContentLoaded', function() {
    const singleType = document.getElementById('singleType');
    if (singleType) {
        singleType.addEventListener('change', function() {
            const label = document.getElementById('singleAmountLabel');
            if (this.value === 'fv') {
                label.textContent = 'Starting amount today';
            } else {
                label.textContent = 'Target future amount';
            }
        });
    }
});

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Financial functions
function futureValue(pv, rate, years) {
    if (![pv,rate,years].every(Number.isFinite) || rate <= -1 || years < 0 || years > 1000) throw new RangeError('Invalid compounding base or time horizon.');
    const result = pv * Math.pow(1 + rate, years);
    if (!Number.isFinite(result)) throw new RangeError('Result exceeds the numerical range.');
    return result;
}

function presentValue(fv, rate, years) {
    if (![fv,rate,years].every(Number.isFinite) || rate <= -1 || years < 0 || years > 1000) throw new RangeError('Invalid compounding base or time horizon.');
    const result = fv / Math.pow(1 + rate, years);
    if (!Number.isFinite(result)) throw new RangeError('Result exceeds the numerical range.');
    return result;
}

function monthlyPeriods(years) {
    const count = Math.round(years * 12);
    if (!Number.isFinite(years) || years < 0 || years > 1000 || Math.abs(years * 12 - count) > 1e-8) throw new RangeError('Enter 0–12000 whole monthly periods.');
    return count;
}

function futureValueAnnuity(payment, rate, years, due = false) {
    const periods = monthlyPeriods(years);
    const monthlyRate = rate / 12;
    return RBNumerical.annuityFV(payment, monthlyRate, periods, due);
}

function requiredPayment(targetFV, rate, years, presentValue = 0) {
    const periods = monthlyPeriods(years);
    const monthlyRate = rate / 12;
    return RBNumerical.requiredPayment(targetFV, presentValue, monthlyRate, periods);
}

// Generate year-by-year data for single amount
function generateSingleGrowthData(amount, rate, years, type) {
    const data = [];
    const principal = type === 'pv' ? amount / Math.pow(1 + rate, years) : amount;
    
    for (let year = 0; year <= years; year++) {
        const value = principal * Math.pow(1 + rate, year);
        data.push({
            year: year,
            value: value,
            interest: value - principal
        });
    }
    return data;
}

// Generate year-by-year data for annuity
function generateAnnuityGrowthData(payment, rate, years, due = false) {
    const months = monthlyPeriods(years);
    return RBNumerical.annuityLedger(payment, rate / 12, months, due)
      .filter(row => row.period % 12 === 0 || row.period === months)
      .map(row => ({...row,year:row.period/12}));
}

// Create growth chart
function createGrowthChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (window[canvasId + 'Chart'] instanceof Chart) {
        window[canvasId + 'Chart'].destroy();
    }
    window[canvasId + 'Chart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Value'
                    },
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

// Single Amount Calculator
document.getElementById('singleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    try {
    
    const type = document.getElementById('singleType').value;
    const amount = parseFloat(document.getElementById('singleAmount').value);
    const rate = parseFloat(document.getElementById('singleRate').value) / 100;
    const years = parseFloat(document.getElementById('singleYears').value);
    if (!Number.isInteger(years)) throw new RangeError('The single-amount chart supports whole years.');
    
    let result, principal, future;
    
    if (type === 'fv') {
        result = futureValue(amount, rate, years);
        principal = amount;
        future = result;
    } else {
        result = presentValue(amount, rate, years);
        principal = result;
        future = amount;
    }
    
    const growthData = generateSingleGrowthData(amount, rate, years, type);
    const totalGrowth = future - principal;
    
    // Create summary cards
    let html = '<div class="results-container">';
    html += '<h2>Results</h2>';
    html += '<div class="summary-grid">';
    html += `
        <div class="summary-card">
            <div class="summary-label">${type === 'fv' ? 'Starting Amount' : 'Required Today'}</div>
            <div class="summary-value">${formatCurrency(principal)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">${type === 'fv' ? 'Future Value' : 'Future Goal'}</div>
            <div class="summary-value">${formatCurrency(future)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Growth</div>
            <div class="summary-value">${formatCurrency(totalGrowth)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Return Multiple</div>
            <div class="summary-value">${principal === 0 ? 'N/A' : (future / principal).toFixed(2) + 'x'}</div>
        </div>
    `;
    html += '</div>';
    
    // Create charts
    html += '<div class="chart-section">';
    html += '<h3>Growth Over Time</h3>';
    html += '<div class="chart-wrapper"><canvas id="singleChart"></canvas></div>';
    html += '</div>';
    
    html += '<div class="chart-section">';
    html += '<h3>Cumulative Interest Earned</h3>';
    html += '<div class="chart-wrapper"><canvas id="singleInterestChart"></canvas></div>';
    html += '</div>';
    
    // Interpretation
    html += '<div class="info-box-blue">';
    html += '<h3>What This Means</h3><ul>';
    if (type === 'fv') {
        html += `<li>If you invest <strong>${formatCurrency(principal)}</strong> today at ${(rate * 100).toFixed(1)}% annual return...</li>`;
        html += `<li>In ${years} years, it will grow to <strong>${formatCurrency(future)}</strong></li>`;
        html += `<li>That's a total gain of <strong>${formatCurrency(totalGrowth)}</strong> (${principal === 0 ? 'N/A' : ((totalGrowth / principal) * 100).toFixed(0) + '%'} growth)</li>`;
    } else {
        html += `<li>To have <strong>${formatCurrency(future)}</strong> in ${years} years...</li>`;
        html += `<li>You need to invest <strong>${formatCurrency(principal)}</strong> today at ${(rate * 100).toFixed(1)}% annual return</li>`;
        html += `<li>Your investment will grow by <strong>${formatCurrency(totalGrowth)}</strong> over that time</li>`;
    }
    html += '</ul></div>';
    
    // Year-by-year table
    html += '<div class="table-section">';
    html += '<h3>Year-by-Year Growth</h3>';
    html += '<div class="table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Year</th><th>Balance</th><th>Interest Earned</th></tr></thead><tbody>';
    
    growthData.forEach(row => {
        html += `<tr>
            <td>${row.year}</td>
            <td>${formatCurrency(row.value)}</td>
            <td>${formatCurrency(row.interest)}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div></div>';
    html += '</div>';
    
    document.getElementById('singleResults').innerHTML = html;
    document.getElementById('singleResults').style.display = 'block';
    
    // Create charts
    createGrowthChart('singleChart', 
        growthData.map(d => d.year),
        [{
            label: 'Account Value',
            data: growthData.map(d => d.value),
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            fill: true,
            tension: 0.1
        }]
    );
    
    createGrowthChart('singleInterestChart',
        growthData.map(d => d.year),
        [{
            label: 'Cumulative Interest Earned',
            data: growthData.map(d => d.interest),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.1
        }]
    );
    
    document.getElementById('singleResults').scrollIntoView({ behavior: 'smooth' });
    } catch (error) { document.getElementById('singleResults').style.display='none'; alert(error.message); }
});

// Target Future Value Calculator
document.getElementById('targetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    try {
    
    const targetGoal = parseFloat(document.getElementById('targetGoal').value);
    const presentValue = parseFloat(document.getElementById('targetPresent').value);
    const rate = parseFloat(document.getElementById('targetRate').value) / 100;
    const years = parseFloat(document.getElementById('targetYears').value);
    if (targetGoal < 0 || presentValue < 0) throw new RangeError('Target and existing savings must be nonnegative.');
    
    const monthlyPayment = requiredPayment(targetGoal, rate, years, presentValue);
    const totalContributed = monthlyPayment * monthlyPeriods(years) + presentValue;
    
    const growthData = generateAnnuityGrowthData(monthlyPayment, rate, years);
    
    // Adjust for initial present value
    if (presentValue > 0) {
        growthData.forEach(row => {
            const pvGrowth = presentValue * Math.pow(1 + rate / 12, row.year * 12);
            row.value += pvGrowth;
            row.contributed += presentValue;
            row.interest = row.value - row.contributed;
        });
    }
    const totalGrowth = growthData[growthData.length - 1].value - totalContributed;
    
    let html = '<div class="results-container">';
    html += '<h2>Results</h2>';
    html += '<div class="summary-grid">';
    html += `
        <div class="summary-card">
            <div class="summary-label">Required Monthly Payment</div>
            <div class="summary-value">${formatCurrency(monthlyPayment)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Target Goal</div>
            <div class="summary-value">${formatCurrency(targetGoal)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total You'll Contribute</div>
            <div class="summary-value">${formatCurrency(totalContributed)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Interest Earned</div>
            <div class="summary-value">${formatCurrency(totalGrowth)}</div>
        </div>
    `;
    html += '</div>';
    
    html += '<div class="chart-section">';
    html += '<h3>Path to Your Goal</h3>';
    html += '<div class="chart-wrapper"><canvas id="targetChart"></canvas></div>';
    html += '</div>';
    
    html += '<div class="chart-section">';
    html += '<h3>Cumulative Interest Earned</h3>';
    html += '<div class="chart-wrapper"><canvas id="targetInterestChart"></canvas></div>';
    html += '</div>';
    
    html += '<div class="info-box-blue">';
    html += '<h3>What This Means</h3><ul>';
    html += `<li>To reach your goal of <strong>${formatCurrency(targetGoal)}</strong> in ${years} years...</li>`;
    html += `<li>You need to save <strong>${formatCurrency(monthlyPayment)}</strong> per month</li>`;
    if (presentValue > 0) {
        html += `<li>Your starting balance of ${formatCurrency(presentValue)} will also grow during this time</li>`;
    }
    html += `<li>Your total contributions: <strong>${formatCurrency(totalContributed)}</strong></li>`;
    html += `<li>Interest will add: <strong>${formatCurrency(totalGrowth)}</strong> (${totalContributed === 0 ? 'N/A' : ((totalGrowth / totalContributed) * 100).toFixed(0) + '%'} gain)</li>`;
    html += '</ul></div>';
    
    html += '</div>';
    
    document.getElementById('targetResults').innerHTML = html;
    document.getElementById('targetResults').style.display = 'block';
    
    createGrowthChart('targetChart',
        growthData.map(d => d.year),
        [
            {
                label: 'Total Value',
                data: growthData.map(d => d.value),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.1
            },
            {
                label: 'Your Contributions',
                data: growthData.map(d => d.contributed),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.1
            }
        ]
    );
    
    createGrowthChart('targetInterestChart',
        growthData.map(d => d.year),
        [{
            label: 'Cumulative Interest Earned',
            data: growthData.map(d => d.interest),
            borderColor: 'rgb(139, 92, 246)',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.1
        }]
    );
    
    document.getElementById('targetResults').scrollIntoView({ behavior: 'smooth' });
    } catch (error) { document.getElementById('targetResults').style.display='none'; alert(error.message); }
});

// Annuity Future Value Calculator
document.getElementById('annuityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    try {
    
    const payment = parseFloat(document.getElementById('annuityPayment').value);
    const rate = parseFloat(document.getElementById('annuityRate').value) / 100;
    const years = parseFloat(document.getElementById('annuityYears').value);
    const timing = document.getElementById('annuityTiming').value;
    if (!['end','begin'].includes(timing)) throw new RangeError('Select a supported contribution timing.');
    const due = timing === 'begin';
    
    const finalValue = futureValueAnnuity(payment, rate, years, due);
    const totalContributed = payment * monthlyPeriods(years);
    const totalGrowth = finalValue - totalContributed;
    
    const growthData = generateAnnuityGrowthData(payment, rate, years, due);
    
    let html = '<div class="results-container">';
    html += '<h2>Results</h2>';
    html += '<div class="summary-grid">';
    html += `
        <div class="summary-card">
            <div class="summary-label">Monthly Payment</div>
            <div class="summary-value">${formatCurrency(payment)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Future Value</div>
            <div class="summary-value">${formatCurrency(finalValue)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Contributed</div>
            <div class="summary-value">${formatCurrency(totalContributed)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Interest Earned</div>
            <div class="summary-value">${formatCurrency(totalGrowth)}</div>
        </div>
    `;
    html += '</div>';
    
    html += '<div class="chart-section">';
    html += '<h3>Growth Over Time</h3>';
    html += '<div class="chart-wrapper"><canvas id="annuityChart"></canvas></div>';
    html += '</div>';
    
    html += '<div class="chart-section">';
    html += '<h3>Cumulative Interest Earned</h3>';
    html += '<div class="chart-wrapper"><canvas id="annuityInterestChart"></canvas></div>';
    html += '</div>';
    
    html += '<div class="info-box-blue">';
    html += '<h3>What This Means</h3><ul>';
    html += `<li>If you save <strong>${formatCurrency(payment)}</strong> per month for ${years} years...</li>`;
    html += `<li>Contributions occur at the ${due ? 'beginning' : 'end'} of each month; the summary, table and charts use this same convention.</li>`;
    html += `<li>At ${(rate * 100).toFixed(1)}% annual return, you'll accumulate <strong>${formatCurrency(finalValue)}</strong></li>`;
    html += `<li>You'll contribute a total of <strong>${formatCurrency(totalContributed)}</strong></li>`;
    html += `<li>Interest will add <strong>${formatCurrency(totalGrowth)}</strong> (${totalContributed === 0 ? 'N/A' : ((totalGrowth / totalContributed) * 100).toFixed(0) + '%'} gain)</li>`;
    html += '</ul></div>';
    
    html += '<div class="table-section">';
    html += '<h3>Year-by-Year Growth</h3>';
    html += '<div class="table-wrapper"><table class="data-table">';
    html += '<thead><tr><th>Year</th><th>Balance</th><th>Contributed</th><th>Interest Earned</th></tr></thead><tbody>';
    
    growthData.forEach(row => {
        html += `<tr>
            <td>${row.year}</td>
            <td>${formatCurrency(row.value)}</td>
            <td>${formatCurrency(row.contributed)}</td>
            <td>${formatCurrency(row.interest)}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div></div>';
    html += '</div>';
    
    document.getElementById('annuityResults').innerHTML = html;
    document.getElementById('annuityResults').style.display = 'block';
    
    createGrowthChart('annuityChart',
        growthData.map(d => d.year),
        [
            {
                label: 'Total Value',
                data: growthData.map(d => d.value),
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.1
            },
            {
                label: 'Your Contributions',
                data: growthData.map(d => d.contributed),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.1
            }
        ]
    );
    
    createGrowthChart('annuityInterestChart',
        growthData.map(d => d.year),
        [{
            label: 'Cumulative Interest Earned',
            data: growthData.map(d => d.interest),
            borderColor: 'rgb(251, 191, 36)',
            backgroundColor: 'rgba(251, 191, 36, 0.1)',
            fill: true,
            tension: 0.1
        }]
    );
    
    document.getElementById('annuityResults').scrollIntoView({ behavior: 'smooth' });
    } catch (error) { document.getElementById('annuityResults').style.display='none'; alert(error.message); }
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
    const scenarioName = prompt('Enter a name for this scenario:', 'My FV Plan');
    if (!scenarioName) return;
    
    const formData = {
        singleType: document.getElementById('singleType')?.value,
        singleAmount: document.getElementById('singleAmount')?.value,
        singleRate: document.getElementById('singleRate')?.value,
        singleYears: document.getElementById('singleYears')?.value,
        targetGoal: document.getElementById('targetGoal')?.value,
        targetPresent: document.getElementById('targetPresent')?.value,
        targetRate: document.getElementById('targetRate')?.value,
        targetYears: document.getElementById('targetYears')?.value,
        annuityPayment: document.getElementById('annuityPayment')?.value,
        annuityRate: document.getElementById('annuityRate')?.value,
        annuityYears: document.getElementById('annuityYears')?.value,
        annuityTiming: document.getElementById('annuityTiming')?.value
    };
    
    rbScenarioFetch(FV_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'future-value',
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
    fetch(FV_API_BASE + 'api/load_scenarios.php?calculator_type=future-value')
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
                    rbScenarioFetch(FV_API_BASE + 'api/delete_scenario.php', {
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
                document.getElementById('annuityTiming').value = scenario.data.annuityTiming ?? 'end';
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    });
}

function compareScenarios() {
    if (typeof CompareScenariosModal === 'undefined') {
        alert('Compare feature failed to load. Please refresh the page.');
        return;
    }
    CompareScenariosModal.open(FV_API_BASE, 'future-value', function (selected) {
        showFVComparison(selected);
    }, { maxScenarios: 3 });
}

function showFVComparison(selected) {
    const container = document.querySelector('.wrap');
    let comparisonEl = document.getElementById('fvComparePanel');
    if (comparisonEl) comparisonEl.remove();

    comparisonEl = document.createElement('div');
    comparisonEl.id = 'fvComparePanel';
    comparisonEl.style.cssText = 'background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;';
    const cols = selected.length;
    const labels = ['Amount / Goal / Payment', 'Rate (%)', 'Years', 'Type / Mode'];
    const getRow = (d) => {
        const single = d.singleAmount || d.singleYears;
        const target = d.targetGoal || d.targetYears;
        const annuity = d.annuityPayment || d.annuityYears;
        if (single !== undefined) return [d.singleAmount, d.singleRate, d.singleYears, (d.singleType === 'fv' ? 'Future Value' : 'Present Value')];
        if (target !== undefined) return [d.targetGoal, d.targetRate, d.targetYears, 'Target FV'];
        if (annuity !== undefined) return [d.annuityPayment, d.annuityRate, d.annuityYears, 'Annuity'];
        return ['—', '—', '—', '—'];
    };

    let table = '<h2 style="margin:0 0 15px 0; color: #92400e;">⚖️ Scenario comparison</h2><table style="width:100%; border-collapse: collapse;"><thead><tr style="background: #f59e0b; color: white;"><th style="padding: 8px; text-align: left;">Input</th>';
    selected.forEach(function (s) {
        table += '<th style="padding: 8px; text-align: right;">' + escapeHtml(s.name) + '</th>';
    });
    table += '</tr></thead><tbody>';
    const rowLabels = ['Amount / Goal / Payment', 'Rate (%)', 'Years', 'Mode'];
    [0, 1, 2, 3].forEach(function (i) {
        table += '<tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 8px; font-weight: 600;">' + rowLabels[i] + '</td>';
        selected.forEach(function (s) {
            const row = getRow(s.data || {});
            table += '<td style="padding: 8px; text-align: right;">' + escapeHtml(String(row[i] != null ? row[i] : '—')) + '</td>';
        });
        table += '</tr>';
    });
    table += '</tbody></table><p style="margin: 12px 0 0 0; font-size: 0.9rem; color: #92400e;">Load a scenario from the dropdown above and click Calculate to see its full results.</p>';
    comparisonEl.innerHTML = table;
    const firstForm = document.querySelector('form');
    if (firstForm && firstForm.parentNode) {
        firstForm.parentNode.insertBefore(comparisonEl, firstForm);
    } else if (container) {
        container.insertBefore(comparisonEl, container.firstChild);
    }
    comparisonEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function downloadPDF() {
    alert('PDF download: Please run a calculation first, then use the PDF button.');
}

function downloadCSV() {
    alert('CSV export: Please run a calculation first, then use the CSV button.');
}
