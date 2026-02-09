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
    
    // Create chart
    createChart(managedData, vanguardData, years);
    
    // Show results
    document.getElementById('results').style.display = 'block';
    
    // Scroll to results
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Create chart
let chartInstance = null;

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