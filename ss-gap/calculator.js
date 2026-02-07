// Social Security + Spending Gap Calculator

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

// Main calculation
document.getElementById('gapForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get inputs
    const targetSpending = parseFloat(document.getElementById('targetSpending').value);
    const ssIncome = parseFloat(document.getElementById('ssIncome').value);
    const otherIncome = parseFloat(document.getElementById('otherIncome').value) || 0;
    const withdrawalRate = parseFloat(document.getElementById('withdrawalRate').value);
    const filingStatus = document.getElementById('filingStatus').value;
    
    // Calculate gaps
    const totalIncome = ssIncome + otherIncome;
    const monthlyGap = Math.max(0, targetSpending - totalIncome);
    const annualGap = monthlyGap * 12;
    
    // Calculate portfolio needed at specified rate
    const portfolioNeeded = calculatePortfolioNeeded(annualGap, withdrawalRate);
    
    // Calculate what portfolio would be needed WITHOUT Social Security
    const monthlyGapWithoutSS = Math.max(0, targetSpending - otherIncome);
    const annualGapWithoutSS = monthlyGapWithoutSS * 12;
    const portfolioWithoutSS = calculatePortfolioNeeded(annualGapWithoutSS, withdrawalRate);
    
    // Calculate savings from Social Security
    const portfolioSavings = portfolioWithoutSS - portfolioNeeded;
    const savingsPercent = portfolioWithoutSS > 0 ? (portfolioSavings / portfolioWithoutSS * 100) : 0;
    
    // Create summary cards
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
    
    // Create interpretation
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
    
    // Create comparison table for different withdrawal rates
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
    
    // Create bar chart showing portfolio needed at different rates
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
    
    // Show results
    document.getElementById('results').style.display = 'block';
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
