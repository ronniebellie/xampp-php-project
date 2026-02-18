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
    return pv * Math.pow(1 + rate, years);
}

function presentValue(fv, rate, years) {
    return fv / Math.pow(1 + rate, years);
}

function futureValueAnnuity(payment, rate, years) {
    const periods = years * 12;
    const monthlyRate = rate / 12;
    return payment * ((Math.pow(1 + monthlyRate, periods) - 1) / monthlyRate);
}

function requiredPayment(targetFV, rate, years, presentValue = 0) {
    const fvOfPresent = presentValue * Math.pow(1 + rate, years);
    const remaining = targetFV - fvOfPresent;
    const periods = years * 12;
    const monthlyRate = rate / 12;
    return remaining * monthlyRate / (Math.pow(1 + monthlyRate, periods) - 1);
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
function generateAnnuityGrowthData(payment, rate, years) {
    const data = [];
    const monthlyRate = rate / 12;
    let balance = 0;
    let totalContributed = 0;
    
    data.push({ year: 0, value: 0, contributed: 0, interest: 0 });
    
    for (let year = 1; year <= years; year++) {
        for (let month = 1; month <= 12; month++) {
            balance = (balance + payment) * (1 + monthlyRate);
            totalContributed += payment;
        }
        data.push({
            year: year,
            value: balance,
            contributed: totalContributed,
            interest: balance - totalContributed
        });
    }
    return data;
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
    
    const type = document.getElementById('singleType').value;
    const amount = parseFloat(document.getElementById('singleAmount').value);
    const rate = parseFloat(document.getElementById('singleRate').value) / 100;
    const years = parseInt(document.getElementById('singleYears').value);
    
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
            <div class="summary-value">${(future / principal).toFixed(2)}x</div>
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
        html += `<li>That's a total gain of <strong>${formatCurrency(totalGrowth)}</strong> (${((totalGrowth / principal) * 100).toFixed(0)}% growth)</li>`;
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
});

// Target Future Value Calculator
document.getElementById('targetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const targetGoal = parseFloat(document.getElementById('targetGoal').value);
    const presentValue = parseFloat(document.getElementById('targetPresent').value);
    const rate = parseFloat(document.getElementById('targetRate').value) / 100;
    const years = parseInt(document.getElementById('targetYears').value);
    
    const monthlyPayment = requiredPayment(targetGoal, rate, years, presentValue);
    const totalContributed = monthlyPayment * years * 12 + presentValue;
    const totalGrowth = targetGoal - totalContributed;
    
    const growthData = generateAnnuityGrowthData(monthlyPayment, rate, years);
    
    // Adjust for initial present value
    if (presentValue > 0) {
        growthData.forEach(row => {
            const pvGrowth = presentValue * Math.pow(1 + rate, row.year);
            row.value += pvGrowth;
            row.contributed += (row.year === 0 ? presentValue : 0);
        });
    }
    
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
    html += `<li>Interest will add: <strong>${formatCurrency(totalGrowth)}</strong> (${((totalGrowth / totalContributed) * 100).toFixed(0)}% gain)</li>`;
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
});

// Annuity Future Value Calculator
document.getElementById('annuityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const payment = parseFloat(document.getElementById('annuityPayment').value);
    const rate = parseFloat(document.getElementById('annuityRate').value) / 100;
    const years = parseInt(document.getElementById('annuityYears').value);
    
    const finalValue = futureValueAnnuity(payment, rate, years);
    const totalContributed = payment * years * 12;
    const totalGrowth = finalValue - totalContributed;
    
    const growthData = generateAnnuityGrowthData(payment, rate, years);
    
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
    html += `<li>At ${(rate * 100).toFixed(1)}% annual return, you'll accumulate <strong>${formatCurrency(finalValue)}</strong></li>`;
    html += `<li>You'll contribute a total of <strong>${formatCurrency(totalContributed)}</strong></li>`;
    html += `<li>Interest will add <strong>${formatCurrency(totalGrowth)}</strong> (${((totalGrowth / totalContributed) * 100).toFixed(0)}% gain)</li>`;
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
        annuityYears: document.getElementById('annuityYears')?.value
    };
    
    fetch(FV_API_BASE + 'api/save_scenario.php', {
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
                    fetch(FV_API_BASE + 'api/delete_scenario.php', {
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
    fetch(FV_API_BASE + 'api/load_scenarios.php?calculator_type=future-value')
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
        alert('Compare feature: Load scenarios individually to see their results side-by-side.');
    })
    .catch(() => alert('Failed to load scenarios.'));
}

function downloadPDF() {
    alert('PDF download: Please run a calculation first, then use the PDF button.');
}

function downloadCSV() {
    alert('CSV export: Please run a calculation first, then use the CSV button.');
}