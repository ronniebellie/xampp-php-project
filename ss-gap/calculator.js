// Social Security + Spending Gap Calculator

// API base URL
const SSG_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)ss-gap\/?/);
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

// Approximate historical success rates for different withdrawal rates
function getSuccessRate(rate) {
    if (rate <= 3.0) return '~100%';
    if (rate <= 3.5) return '~98%';
    if (rate <= 4.0) return '~95%';
    if (rate <= 4.5) return '~85%';
    if (rate <= 5.0) return '~75%';
    if (rate <= 5.5) return '~65%';
    if (rate <= 6.0) return '~50%';
    return '<50%';
}

// Calculate portfolio needed for a given withdrawal rate
function calculatePortfolioNeeded(annualGap, withdrawalRate) {
    return annualGap / (withdrawalRate / 100);
}

function updateGap() {
    const targetSpending = parseFloat(document.getElementById('targetSpending').value) || 0;
    const ssIncome = parseFloat(document.getElementById('ssIncome').value) || 0;
    const otherIncome = parseFloat(document.getElementById('otherIncome').value) || 0;
    const withdrawalRate = parseFloat(document.getElementById('withdrawalRate').value) || 4;
    const filingStatus = document.getElementById('filingStatus').value;

    const targetLabel = document.getElementById('targetSpendingLabel');
    if (targetLabel) targetLabel.textContent = formatCurrency(targetSpending) + '/mo';
    const ssLabel = document.getElementById('ssIncomeLabel');
    if (ssLabel) ssLabel.textContent = formatCurrency(ssIncome) + '/mo';
    const otherLabel = document.getElementById('otherIncomeLabel');
    if (otherLabel) otherLabel.textContent = formatCurrency(otherIncome) + '/mo';
    const wrLabel = document.getElementById('withdrawalRateLabel');
    if (wrLabel) wrLabel.textContent = withdrawalRate.toFixed(1).replace(/\.0$/, '') + '%';
    
    const totalIncome = ssIncome + otherIncome;
    const monthlyGap = Math.max(0, targetSpending - totalIncome);
    const annualGap = monthlyGap * 12;
    
    const portfolioNeeded = calculatePortfolioNeeded(annualGap, withdrawalRate);
    
    const monthlyGapWithoutSS = Math.max(0, targetSpending - otherIncome);
    const annualGapWithoutSS = monthlyGapWithoutSS * 12;
    const portfolioWithoutSS = calculatePortfolioNeeded(annualGapWithoutSS, withdrawalRate);
    
    const portfolioSavings = portfolioWithoutSS - portfolioNeeded;
    const savingsPercent = portfolioWithoutSS > 0 ? (portfolioSavings / portfolioWithoutSS * 100) : 0;

    // Persist last computed values for Premium features (Explain/PDF/CSV).
    window.lastSSGapResult = {
        targetSpending,
        ssIncome,
        otherIncome,
        withdrawalRate,
        filingStatus,
        monthlyGap,
        annualGap,
        portfolioNeeded,
        portfolioWithoutSS,
        portfolioSavings,
        savingsPercent
    };
    
    let html = '<div class="summary-grid">';
    html += `
        <div class="summary-card">
            <div class="summary-label">Monthly Spending Gap</div>
            <div class="summary-value">${formatCurrency(monthlyGap)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Annual Spending Gap</div>
            <div class="summary-value">${formatCurrency(annualGap)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Portfolio Needed</div>
            <div class="summary-value">${formatCurrency(portfolioNeeded)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">SS Reduces Need By</div>
            <div class="summary-value">${savingsPercent.toFixed(0)}%</div>
        </div>
    `;
    html += '</div>';
    document.getElementById('summaryCards').innerHTML = html;
    
    let interpretation = '<h3>What This Means</h3><ul>';
    
    interpretation += `<li><strong>Your monthly spending gap is ${formatCurrency(monthlyGap)}.</strong> `;
    interpretation += `This is what you need from your portfolio each month to cover the difference between your `;
    interpretation += `${formatCurrency(targetSpending)} spending goal and your ${formatCurrency(totalIncome)} monthly income.</li>`;
    
    interpretation += `<li><strong>To sustainably cover this gap at a ${withdrawalRate}% withdrawal rate,</strong> `;
    interpretation += `you need a portfolio of approximately ${formatCurrency(portfolioNeeded)}.</li>`;
    
    if (ssIncome > 0) {
        interpretation += `<li><strong>Social Security is saving you ${formatCurrency(portfolioSavings)}!</strong> `;
        interpretation += `Without your ${formatCurrency(ssIncome)}/month Social Security benefit, you'd need `;
        interpretation += `${formatCurrency(portfolioWithoutSS)} to maintain the same lifestyle - that's ${savingsPercent.toFixed(0)}% more.</li>`;
    }
    
    const coveragePercent = totalIncome > 0 ? (totalIncome / targetSpending * 100) : 0;
    interpretation += `<li><strong>Your guaranteed income covers ${coveragePercent.toFixed(0)}% of your spending.</strong> `;
    if (coveragePercent >= 100) {
        interpretation += `You don't need any portfolio withdrawals - your income exceeds your spending!</li>`;
    } else if (coveragePercent >= 80) {
        interpretation += `This is excellent! Most of your retirement is funded by guaranteed sources.</li>`;
    } else if (coveragePercent >= 50) {
        interpretation += `About half your spending is covered by guaranteed income, reducing portfolio risk.</li>`;
    } else {
        interpretation += `Consider ways to increase guaranteed income or reduce spending to lower portfolio dependency.</li>`;
    }
    
    interpretation += '</ul>';
    document.getElementById('interpretation').innerHTML = interpretation;
    
    const rates = [3.0, 3.5, 4.0, 4.5, 5.0, 5.5, 6.0];
    let tableHTML = '';
    
    rates.forEach(rate => {
        const portfolio = calculatePortfolioNeeded(annualGap, rate);
        const annual = portfolio * (rate / 100);
        const monthly = annual / 12;
        const successRate = getSuccessRate(rate);
        
        const isSelected = Math.abs(rate - withdrawalRate) < 0.01;
        const rowClass = isSelected ? ' style="background: #f0f9ff; font-weight: 600;"' : '';
        
        tableHTML += `<tr${rowClass}>
            <td>${rate.toFixed(1)}%${isSelected ? ' (Your Rate)' : ''}</td>
            <td>${formatCurrency(portfolio)}</td>
            <td>${formatCurrency(annual)}</td>
            <td>${formatCurrency(monthly)}</td>
            <td>${successRate}</td>
        </tr>`;
    });
    
    document.getElementById('tableBody').innerHTML = tableHTML;
    
    const ctx = document.getElementById('withdrawalChart');
    if (window.withdrawalChart instanceof Chart) {
        window.withdrawalChart.destroy();
    }
    
    window.withdrawalChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: rates.map(r => r.toFixed(1) + '%'),
            datasets: [{
                label: 'Portfolio Needed',
                data: rates.map(r => calculatePortfolioNeeded(annualGap, r)),
                backgroundColor: rates.map(r => {
                    if (Math.abs(r - withdrawalRate) < 0.01) {
                        return 'rgba(34, 197, 94, 0.7)'; // Green for selected
                    } else if (r <= 4.0) {
                        return 'rgba(59, 130, 246, 0.7)'; // Blue for conservative
                    } else if (r <= 5.0) {
                        return 'rgba(251, 191, 36, 0.7)'; // Yellow for moderate
                    } else {
                        return 'rgba(239, 68, 68, 0.7)'; // Red for aggressive
                    }
                }),
                borderColor: rates.map(r => {
                    if (Math.abs(r - withdrawalRate) < 0.01) {
                        return 'rgb(34, 197, 94)';
                    } else if (r <= 4.0) {
                        return 'rgb(59, 130, 246)';
                    } else if (r <= 5.0) {
                        return 'rgb(251, 191, 36)';
                    } else {
                        return 'rgb(239, 68, 68)';
                    }
                }),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const rate = rates[context.dataIndex];
                            const portfolio = context.parsed.y;
                            return [
                                'Portfolio: ' + formatCurrency(portfolio),
                                'Annual: ' + formatCurrency(portfolio * rate / 100),
                                'Success: ' + getSuccessRate(rate)
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Withdrawal Rate'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Portfolio Size Needed'
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
    
    document.getElementById('results').style.display = 'block';

    setTimeout(() => {
        createAnnualWithdrawalChart(annualGap, rates, withdrawalRate);
    }, 100);
    
}

document.getElementById('gapForm').addEventListener('submit', function(e) {
    e.preventDefault();
    updateGap();
});

['targetSpending', 'ssIncome', 'otherIncome', 'withdrawalRate', 'filingStatus'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateGap);
});
// Premium Save/Load Functionality

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    const r = window.lastSSGapResult;
    if (!r) {
        alert('Please run the calculation first to see results.');
        return;
    }

    const fmt = (n) => '$' + (n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
    const fmtMo = (n) => fmt(n) + '/mo';
    let summary = 'Social Security + Spending Gap Analysis.\n\n';
    summary += 'Target monthly spending: ' + fmtMo(r.targetSpending) + '. ';
    summary += 'Monthly Social Security income: ' + fmtMo(r.ssIncome) + '. ';
    summary += 'Other monthly income: ' + fmtMo(r.otherIncome) + '. ';
    summary += 'Assumed withdrawal rate: ' + (r.withdrawalRate || 0).toFixed(1).replace(/\.0$/, '') + '%. ';
    summary += 'Household type: ' + (r.filingStatus === 'married' ? 'Married' : 'Single') + '.\n\n';
    summary += 'Monthly spending gap: ' + fmt(r.monthlyGap) + '. ';
    summary += 'Annual spending gap: ' + fmt(r.annualGap) + '. ';
    summary += 'Estimated portfolio needed to fund the gap: ' + fmt(r.portfolioNeeded) + '.\n\n';
    if ((r.ssIncome || 0) > 0) {
        summary += 'Compared with having no Social Security, estimated portfolio needed would be ' + fmt(r.portfolioWithoutSS) + ', ';
        summary += 'so Social Security reduces the needed portfolio by about ' + fmt(r.portfolioSavings) + ' (' + (r.savingsPercent || 0).toFixed(0) + '%).';
    }

    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

    const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(explainUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ calculator_type: 'ss-gap', results_summary: summary })
    })
    .then(r => r.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let data;
        try { data = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Try logging out and back in.');
        }
        if (data.error) throw new Error(data.error);
        showExplainModal(data.explanation);
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
    overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
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

function createAnnualWithdrawalChart(annualGap, rates, selectedRate) {
    const ctx = document.getElementById('annualWithdrawalChart');
    if (!ctx) return;
    
    if (window.annualWithdrawalChart instanceof Chart) {
        window.annualWithdrawalChart.destroy();
    }
    
    const portfolios = rates.map(r => calculatePortfolioNeeded(annualGap, r));
    const annualWithdrawals = portfolios.map((p, i) => p * (rates[i] / 100));
    
    window.annualWithdrawalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: rates.map(r => r.toFixed(1) + '%'),
            datasets: [{
                label: 'Annual Withdrawal Amount',
                data: annualWithdrawals,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: rates.map(r => Math.abs(r - selectedRate) < 0.01 ? 6 : 4),
                pointBackgroundColor: rates.map(r => Math.abs(r - selectedRate) < 0.01 ? '#22c55e' : '#667eea'),
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const rate = rates[context.dataIndex];
                            const annual = context.parsed.y;
                            const monthly = annual / 12;
                            return [
                                'Annual: ' + formatCurrency(annual),
                                'Monthly: ' + formatCurrency(monthly),
                                'Rate: ' + rate.toFixed(1) + '%'
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Withdrawal Rate'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Annual Withdrawal Amount'
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
function updateGapLabelsOnly() {
    const targetSpending = parseFloat(document.getElementById('targetSpending').value) || 0;
    const ssIncome = parseFloat(document.getElementById('ssIncome').value) || 0;
    const otherIncome = parseFloat(document.getElementById('otherIncome').value) || 0;
    const withdrawalRate = parseFloat(document.getElementById('withdrawalRate').value) || 4;
    const targetLabel = document.getElementById('targetSpendingLabel');
    if (targetLabel) targetLabel.textContent = formatCurrency(targetSpending) + '/mo';
    const ssLabel = document.getElementById('ssIncomeLabel');
    if (ssLabel) ssLabel.textContent = formatCurrency(ssIncome) + '/mo';
    const otherLabel = document.getElementById('otherIncomeLabel');
    if (otherLabel) otherLabel.textContent = formatCurrency(otherIncome) + '/mo';
    const wrLabel = document.getElementById('withdrawalRateLabel');
    if (wrLabel) wrLabel.textContent = withdrawalRate.toFixed(1).replace(/\.0$/, '') + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    updateGapLabelsOnly();

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
    const scenarioName = prompt('Enter a name for this scenario:', 'My SS Gap Plan');
    if (!scenarioName) return;
    
    const formData = {
        targetSpending: document.getElementById('targetSpending')?.value,
        ssIncome: document.getElementById('ssIncome')?.value,
        otherIncome: document.getElementById('otherIncome')?.value,
        withdrawalRate: document.getElementById('withdrawalRate')?.value,
        filingStatus: document.getElementById('filingStatus')?.value
    };
    
    fetch(SSG_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'ss-gap',
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
    fetch(SSG_API_BASE + 'api/load_scenarios.php?calculator_type=ss-gap')
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
                    fetch(SSG_API_BASE + 'api/delete_scenario.php', {
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
                alert('Scenario loaded! Move any slider to see results.');
            }
        }
    });
}

function compareScenarios() {
    if (typeof CompareScenariosModal === 'undefined') {
        alert('Compare feature failed to load. Please refresh the page.');
        return;
    }
    CompareScenariosModal.open(SSG_API_BASE, 'ss-gap', function (selected) {
        showSSGapComparison(selected);
    }, { maxScenarios: 3 });
}

function showSSGapComparison(selected) {
    const wrap = document.querySelector('.wrap');
    let panel = document.getElementById('ssGapComparePanel');
    if (panel) panel.remove();
    panel = document.createElement('div');
    panel.id = 'ssGapComparePanel';
    panel.style.cssText = 'background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;';
    const labels = ['Target spending ($/yr)', 'SS income ($/yr)', 'Other income ($/yr)', 'Withdrawal rate (%)', 'Filing status'];
    const keys = ['targetSpending', 'ssIncome', 'otherIncome', 'withdrawalRate', 'filingStatus'];
    let html = '<h2 style="margin:0 0 15px 0; color: #92400e;">⚖️ Scenario comparison</h2><table style="width:100%; border-collapse: collapse;"><thead><tr style="background: #f59e0b; color: white;"><th style="padding: 8px; text-align: left;">Input</th>';
    selected.forEach(function (s) { html += '<th style="padding: 8px; text-align: right;">' + (s.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</th>'; });
    html += '</tr></thead><tbody>';
    keys.forEach(function (key, i) {
        html += '<tr style="border-bottom: 1px solid #e5e7eb;"><td style="padding: 8px; font-weight: 600;">' + labels[i] + '</td>';
        selected.forEach(function (s) {
            const v = (s.data && s.data[key]) != null ? String(s.data[key]) : '—';
            html += '<td style="padding: 8px; text-align: right;">' + v.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
        });
        html += '</tr>';
    });
    html += '</tbody></table><p style="margin: 12px 0 0 0; font-size: 0.9rem; color: #92400e;">Load a scenario to see its full results and chart.</p>';
    panel.innerHTML = html;
    const firstForm = document.querySelector('form');
    if (firstForm && firstForm.parentNode) firstForm.parentNode.insertBefore(panel, firstForm);
    else if (wrap) wrap.insertBefore(panel, wrap.firstChild);
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function downloadPDF() {
    alert('PDF download: Please run a calculation first, then use the PDF button.');
}

function downloadCSV() {
    alert('CSV export: Please run a calculation first, then use the CSV button.');
}