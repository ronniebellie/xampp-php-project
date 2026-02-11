// Social Security Claiming Analyzer - Enhanced Calculator

// Full Retirement Age lookup table based on birth year
function getFRA(birthYear) {
    if (birthYear <= 1937) return { years: 65, months: 0 };
    if (birthYear === 1938) return { years: 65, months: 2 };
    if (birthYear === 1939) return { years: 65, months: 4 };
    if (birthYear === 1940) return { years: 65, months: 6 };
    if (birthYear === 1941) return { years: 65, months: 8 };
    if (birthYear === 1942) return { years: 65, months: 10 };
    if (birthYear >= 1943 && birthYear <= 1954) return { years: 66, months: 0 };
    if (birthYear === 1955) return { years: 66, months: 2 };
    if (birthYear === 1956) return { years: 66, months: 4 };
    if (birthYear === 1957) return { years: 66, months: 6 };
    if (birthYear === 1958) return { years: 66, months: 8 };
    if (birthYear === 1959) return { years: 66, months: 10 };
    return { years: 67, months: 0 }; // 1960 and later
}

// Calculate reduction for claiming before FRA
function calculateEarlyReduction(monthsEarly) {
    // First 36 months: 5/9 of 1% per month
    // Beyond 36 months: 5/12 of 1% per month
    const first36 = Math.min(monthsEarly, 36);
    const beyond36 = Math.max(0, monthsEarly - 36);
    
    const reduction = (first36 * (5/9) / 100) + (beyond36 * (5/12) / 100);
    return 1 - reduction;
}

// Calculate increase for delaying past FRA
function calculateDelayCredit(monthsDelayed) {
    // 2/3 of 1% per month (8% per year)
    return 1 + (monthsDelayed * (2/3) / 100);
}

// Calculate monthly benefit at a given claiming age
function calculateMonthlyBenefit(pia, birthYear, claimAge) {
    const fra = getFRA(birthYear);
    const fraInMonths = fra.years * 12 + fra.months;
    const claimAgeInMonths = claimAge * 12;
    
    // Cap at age 70 for delayed credits
    const cappedClaimMonths = Math.min(claimAgeInMonths, 70 * 12);
    
    const monthsDiff = cappedClaimMonths - fraInMonths;
    
    let adjustmentFactor = 1;
    if (monthsDiff < 0) {
        adjustmentFactor = calculateEarlyReduction(Math.abs(monthsDiff));
    } else if (monthsDiff > 0) {
        adjustmentFactor = calculateDelayCredit(monthsDiff);
    }
    
    return pia * adjustmentFactor;
}

// Calculate lifetime benefits for a claiming scenario
function calculateLifetimeBenefits(monthlyBenefit, claimAge, endAge, colaRate, discountRate) {
    const yearlyData = [];
    let currentMonthly = monthlyBenefit;
    let cumulativeTotal = 0;
    
    for (let age = claimAge; age <= endAge; age++) {
        // Apply COLA after first year
        if (age > claimAge) {
            currentMonthly *= (1 + colaRate / 100);
        }
        
        const annualBenefit = currentMonthly * 12;
        
        // Apply discount rate if specified
        const yearsFromClaim = age - claimAge;
        const discountFactor = Math.pow(1 + discountRate / 100, -yearsFromClaim);
        const presentValueAnnual = annualBenefit * discountFactor;
        
        cumulativeTotal += presentValueAnnual;
        
        yearlyData.push({
            age: age,
            monthlyBenefit: currentMonthly,
            annualBenefit: annualBenefit,
            cumulativeTotal: cumulativeTotal
        });
    }
    
    return yearlyData;
}

// Find break-even age between two scenarios
function findBreakEvenAge(dataA, dataB) {
    for (let i = 0; i < dataA.length; i++) {
        const ageA = dataA[i].age;
        const cumA = dataA[i].cumulativeTotal;
        
        // Find corresponding age in dataB
        const matchB = dataB.find(d => d.age === ageA);
        if (matchB) {
            const cumB = matchB.cumulativeTotal;
            
            // Check if B has caught up to or passed A
            if (i > 0 && cumB >= cumA) {
                const prevMatch = dataB.find(d => d.age === dataA[i-1].age);
                if (prevMatch && prevMatch.cumulativeTotal < dataA[i-1].cumulativeTotal) {
                    return ageA;
                }
            }
        }
    }
    return null;
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Main calculation and display function
document.getElementById('ssForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get inputs
    const birthDate = new Date(document.getElementById('birthDate').value);
    const birthYear = birthDate.getFullYear();
    const monthlyPIA = parseFloat(document.getElementById('monthlyPIA').value);
    const lifeExpectancy = parseInt(document.getElementById('lifeExpectancy').value);
    const claimAgeA = parseInt(document.getElementById('claimAgeA').value);
    const claimAgeB = parseInt(document.getElementById('claimAgeB').value);
    const claimAgeC = parseInt(document.getElementById('claimAgeC').value);
    const colaRate = parseFloat(document.getElementById('colaRate').value);
    const discountRate = parseFloat(document.getElementById('discountRate').value);
    
    // Calculate FRA
    const fra = getFRA(birthYear);
    const fraAge = fra.years + fra.months / 12;
    
    // Calculate monthly benefits for each scenario
    const monthlyA = calculateMonthlyBenefit(monthlyPIA, birthYear, claimAgeA);
    const monthlyB = calculateMonthlyBenefit(monthlyPIA, birthYear, claimAgeB);
    const monthlyC = calculateMonthlyBenefit(monthlyPIA, birthYear, claimAgeC);
    
    // Calculate lifetime benefits
    const dataA = calculateLifetimeBenefits(monthlyA, claimAgeA, lifeExpectancy, colaRate, discountRate);
    const dataB = calculateLifetimeBenefits(monthlyB, claimAgeB, lifeExpectancy, colaRate, discountRate);
    const dataC = calculateLifetimeBenefits(monthlyC, claimAgeC, lifeExpectancy, colaRate, discountRate);
    
    // Get final cumulative totals
    const totalA = dataA[dataA.length - 1].cumulativeTotal;
    const totalB = dataB[dataB.length - 1].cumulativeTotal;
    const totalC = dataC[dataC.length - 1].cumulativeTotal;
    
    // Find best option
    const scenarios = [
        { age: claimAgeA, total: totalA, monthly: monthlyA, label: 'A' },
        { age: claimAgeB, total: totalB, monthly: monthlyB, label: 'B' },
        { age: claimAgeC, total: totalC, monthly: monthlyC, label: 'C' }
    ];
    const bestScenario = scenarios.reduce((max, s) => s.total > max.total ? s : max);
    
    // Find break-even ages
    const breakEvenAB = findBreakEvenAge(dataA, dataB);
    const breakEvenBC = findBreakEvenAge(dataB, dataC);
    const breakEvenAC = findBreakEvenAge(dataA, dataC);
    
    // Create summary cards
    const summaryHTML = `
        <div class="summary-card">
            <div class="summary-label">Your Full Retirement Age</div>
            <div class="summary-value">${fra.years}${fra.months > 0 ? ` + ${fra.months}mo` : ''}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Age ${claimAgeA} Monthly</div>
            <div class="summary-value">${formatCurrency(monthlyA)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Age ${claimAgeB} Monthly</div>
            <div class="summary-value">${formatCurrency(monthlyB)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Age ${claimAgeC} Monthly</div>
            <div class="summary-value">${formatCurrency(monthlyC)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Best Option to Age ${lifeExpectancy}</div>
            <div class="summary-value">Age ${bestScenario.age}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Lifetime Total (Best)</div>
            <div class="summary-value">${formatCurrency(bestScenario.total)}</div>
        </div>
    `;
    document.getElementById('summaryCards').innerHTML = summaryHTML;
    
    // Create interpretation
    let interpretationHTML = '<h3>Analysis & Recommendations</h3><ul>';
    
    interpretationHTML += `<li><strong>Your Full Retirement Age is ${fra.years}${fra.months > 0 ? ` and ${fra.months} months` : ''}.</strong> `;
    interpretationHTML += `This is when you can claim 100% of your benefit (${formatCurrency(monthlyPIA)}/month).</li>`;
    
    interpretationHTML += `<li><strong>If you live to age ${lifeExpectancy},</strong> claiming at age ${bestScenario.age} `;
    interpretationHTML += `gives you the highest lifetime total: ${formatCurrency(bestScenario.total)}.</li>`;
    
    if (breakEvenAB) {
        interpretationHTML += `<li><strong>Break-even between age ${claimAgeA} and ${claimAgeB}:</strong> Age ${breakEvenAB}. `;
        interpretationHTML += `If you live past ${breakEvenAB}, claiming at ${claimAgeB} gives you more total benefits.</li>`;
    }
    
    if (breakEvenBC) {
        interpretationHTML += `<li><strong>Break-even between age ${claimAgeB} and ${claimAgeC}:</strong> Age ${breakEvenBC}. `;
        interpretationHTML += `If you live past ${breakEvenBC}, claiming at ${claimAgeC} gives you more total benefits.</li>`;
    }
    
    const monthlyDiff = monthlyC - monthlyA;
    const pctIncrease = ((monthlyC / monthlyA - 1) * 100).toFixed(0);
    interpretationHTML += `<li><strong>Waiting from ${claimAgeA} to ${claimAgeC}</strong> increases your monthly benefit by `;
    interpretationHTML += `${formatCurrency(monthlyDiff)} (${pctIncrease}% more).</li>`;
    
    interpretationHTML += '</ul>';
    document.getElementById('interpretation').innerHTML = interpretationHTML;
    
    // Create cumulative benefits chart
    const ages = Array.from({length: lifeExpectancy - claimAgeA + 1}, (_, i) => claimAgeA + i);
    
    const ctx1 = document.getElementById('lifetimeBenefitsChart');
    if (window.lifetimeBenefitsChart instanceof Chart) {
        window.lifetimeBenefitsChart.destroy();
    }
    window.lifetimeBenefitsChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ages,
            datasets: [
                {
                    label: `Age ${claimAgeA}`,
                    data: dataA.map(d => d.cumulativeTotal),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: `Age ${claimAgeB}`,
                    data: ages.map(age => {
                        const match = dataB.find(d => d.age === age);
                        return match ? match.cumulativeTotal : null;
                    }),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: `Age ${claimAgeC}`,
                    data: ages.map(age => {
                        const match = dataC.find(d => d.age === age);
                        return match ? match.cumulativeTotal : null;
                    }),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
                    fill: true
                }
            ]
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
                        text: 'Your Age'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Cumulative Lifetime Benefits'
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
    
    // Create monthly benefits bar chart
    const ctx2 = document.getElementById('monthlyBenefitsChart');
    if (window.monthlyBenefitsChart instanceof Chart) {
        window.monthlyBenefitsChart.destroy();
    }
    window.monthlyBenefitsChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: [`Age ${claimAgeA}`, `Age ${claimAgeB}`, `Age ${claimAgeC}`],
            datasets: [{
                label: 'Monthly Benefit',
                data: [monthlyA, monthlyB, monthlyC],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(34, 197, 94, 0.7)'
                ],
                borderColor: [
                    'rgb(239, 68, 68)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)'
                ],
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
                            return 'Monthly: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Monthly Benefit Amount'
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
    
    // Create comparison table
    const tableHeader = `
        <th>Age</th>
        <th>Age ${claimAgeA} Monthly</th>
        <th>Age ${claimAgeA} Cumulative</th>
        <th>Age ${claimAgeB} Monthly</th>
        <th>Age ${claimAgeB} Cumulative</th>
        <th>Age ${claimAgeC} Monthly</th>
        <th>Age ${claimAgeC} Cumulative</th>
    `;
    document.getElementById('tableHeader').innerHTML = tableHeader;
    
    let tableBodyHTML = '';
    for (let age = claimAgeA; age <= lifeExpectancy; age++) {
        const rowA = dataA.find(d => d.age === age);
        const rowB = dataB.find(d => d.age === age);
        const rowC = dataC.find(d => d.age === age);
        
        tableBodyHTML += `<tr>
            <td>${age}</td>
            <td>${rowA ? formatCurrency(rowA.monthlyBenefit) : '-'}</td>
            <td>${rowA ? formatCurrency(rowA.cumulativeTotal) : '-'}</td>
            <td>${rowB ? formatCurrency(rowB.monthlyBenefit) : '-'}</td>
            <td>${rowB ? formatCurrency(rowB.cumulativeTotal) : '-'}</td>
            <td>${rowC ? formatCurrency(rowC.monthlyBenefit) : '-'}</td>
            <td>${rowC ? formatCurrency(rowC.cumulativeTotal) : '-'}</td>
        </tr>`;
    }
    document.getElementById('tableBody').innerHTML = tableBodyHTML;
    
    // Show results
    document.getElementById('results').style.display = 'block';
    
    // Scroll to results
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
// Premium Save/Load Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    
    if (saveBtn) {
        saveBtn.addEventListener('click', saveScenario);
    }
    
    if (loadBtn) {
        loadBtn.addEventListener('click', loadScenario);
    }
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My SS Plan');
    if (!scenarioName) return;
    
    // Gather all form inputs - you'll need to add all the actual input IDs here
    const formData = {
    birthDate: document.getElementById('birthDate')?.value,
    monthlyPIA: document.getElementById('monthlyPIA')?.value,
    lifeExpectancy: document.getElementById('lifeExpectancy')?.value,
    claimAgeA: document.getElementById('claimAgeA')?.value,
    claimAgeB: document.getElementById('claimAgeB')?.value,
    claimAgeC: document.getElementById('claimAgeC')?.value,
    colaRate: document.getElementById('colaRate')?.value,
    discountRate: document.getElementById('discountRate')?.value
};
    
    fetch('/api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'social-security',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('saveStatus').textContent = '✓ Saved!';
            setTimeout(() => {
                document.getElementById('saveStatus').textContent = '';
            }, 3000);
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function loadScenario() {
    fetch('/api/load_scenarios.php?calculator_type=social-security')
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
        
        // Show list of scenarios
        let message = 'Select a scenario to load:\n\n';
        data.scenarios.forEach((s, i) => {
            message += `${i + 1}. ${s.name} (saved ${new Date(s.updated_at).toLocaleDateString()})\n`;
        });
        
        const choice = prompt(message + '\nEnter number:');
        const index = parseInt(choice) - 1;
        
        if (index >= 0 && index < data.scenarios.length) {
            const scenario = data.scenarios[index];
            // Load data into form
            Object.keys(scenario.data).forEach(key => {
                const input = document.getElementById(key);
                if (input) input.value = scenario.data[key];
            });
            alert('Scenario loaded! Click Calculate to see results.');
        }
    });
}