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
    
    // Store for premium PDF/CSV/Summary
    window.lastSSResult = {
        dataA, dataB, dataC,
        monthlyA, monthlyB, monthlyC,
        totalA, totalB, totalC,
        fra, claimAgeA, claimAgeB, claimAgeC, lifeExpectancy,
        breakEvenAB, breakEvenBC, breakEvenAC,
        bestScenario, birthYear, monthlyPIA, colaRate, discountRate
    };
    
    // Show results
    document.getElementById('results').style.display = 'block';
    
    // Scroll to results
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
// API base path (works when app is in a subfolder, e.g. /social-security-claiming-analyzer/)
const SS_API_BASE = (function() {
    const path = window.location.pathname;
    return path.indexOf('/social-security-claiming-analyzer') !== -1 ? '..' : '';
})();

// Premium Save/Load/PDF/CSV/Summary Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    const summaryBtn = document.getElementById('downloadSummaryBtn');
    
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (summaryBtn) summaryBtn.addEventListener('click', downloadClaimingSummary);
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My SS Plan');
    if (!scenarioName) return;
    
    const formData = {
        birthDate: document.getElementById('birthDate')?.value || '',
        monthlyPIA: document.getElementById('monthlyPIA')?.value,
        lifeExpectancy: document.getElementById('lifeExpectancy')?.value,
        claimAgeA: document.getElementById('claimAgeA')?.value,
        claimAgeB: document.getElementById('claimAgeB')?.value,
        claimAgeC: document.getElementById('claimAgeC')?.value,
        colaRate: document.getElementById('colaRate')?.value,
        discountRate: document.getElementById('discountRate')?.value
    };
    
    fetch(SS_API_BASE + '/api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'social-security',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => {
        return res.text().then(text => ({ ok: res.ok, status: res.status, text: text }));
    })
    .then(({ ok, status, text }) => {
        let data;
        try { data = JSON.parse(text); } catch (_) {
            var snippet = (text || 'Empty response').substring(0, 300).replace(/\s+/g, ' ');
            throw new Error('Server returned: ' + snippet);
        }
        if (!ok) throw new Error(data.error || 'Save failed (' + status + ')');
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
    fetch(SS_API_BASE + '/api/load_scenarios.php?calculator_type=social-security')
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
                    fetch(SS_API_BASE + '/api/delete_scenario.php', {
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
                if (scenario.data.birthDate && typeof window.setBirthDateFromString === 'function') {
                    window.setBirthDateFromString(scenario.data.birthDate);
                }
                alert('Scenario loaded! Click "Compare Scenarios" (the form button) to see results.');
            }
        }
    });
}

function compareScenarios() {
    fetch(SS_API_BASE + '/api/load_scenarios.php?calculator_type=social-security')
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.error);
            return;
        }
        if (data.scenarios.length < 2) {
            alert('You need at least 2 saved scenarios to compare. Save more first!');
            return;
        }
        let message = 'Select TWO scenarios to compare:\n\n';
        data.scenarios.forEach((s, i) => { message += `${i + 1}. ${s.name}\n`; });
        const choice = prompt(message + '\nEnter two numbers separated by comma (e.g., "1,2"):');
        if (!choice) return;
        const parts = choice.split(',').map(s => parseInt(s.trim()) - 1);
        if (parts.length !== 2 || parts[0] < 0 || parts[0] >= data.scenarios.length ||
            parts[1] < 0 || parts[1] >= data.scenarios.length || parts[0] === parts[1]) {
            alert('Invalid selection. Enter two different numbers (e.g., "1,2").');
            return;
        }
        const s1 = data.scenarios[parts[0]], s2 = data.scenarios[parts[1]];
        const d1 = s1.data, d2 = s2.data;
        const birthYear1 = d1.birthDate ? new Date(d1.birthDate).getFullYear() : 1960;
        const birthYear2 = d2.birthDate ? new Date(d2.birthDate).getFullYear() : 1960;
        const fra1 = getFRA(birthYear1), fra2 = getFRA(birthYear2);
        const pia1 = parseFloat(d1.monthlyPIA) || 3000, pia2 = parseFloat(d2.monthlyPIA) || 3000;
        const life1 = parseInt(d1.lifeExpectancy) || 85, life2 = parseInt(d2.lifeExpectancy) || 85;
        const a1 = parseInt(d1.claimAgeA) || 62, b1 = parseInt(d1.claimAgeB) || 67, c1 = parseInt(d1.claimAgeC) || 70;
        const a2 = parseInt(d2.claimAgeA) || 62, b2 = parseInt(d2.claimAgeB) || 67, c2 = parseInt(d2.claimAgeC) || 70;
        const cola1 = parseFloat(d1.colaRate) || 2.5, cola2 = parseFloat(d2.colaRate) || 2.5;
        const disc1 = parseFloat(d1.discountRate) || 0, disc2 = parseFloat(d2.discountRate) || 0;
        const monthlyA1 = calculateMonthlyBenefit(pia1, birthYear1, a1);
        const monthlyB1 = calculateMonthlyBenefit(pia1, birthYear1, b1);
        const monthlyC1 = calculateMonthlyBenefit(pia1, birthYear1, c1);
        const monthlyA2 = calculateMonthlyBenefit(pia2, birthYear2, a2);
        const monthlyB2 = calculateMonthlyBenefit(pia2, birthYear2, b2);
        const monthlyC2 = calculateMonthlyBenefit(pia2, birthYear2, c2);
        const dataA1 = calculateLifetimeBenefits(monthlyA1, a1, life1, cola1, disc1);
        const dataB1 = calculateLifetimeBenefits(monthlyB1, b1, life1, cola1, disc1);
        const dataC1 = calculateLifetimeBenefits(monthlyC1, c1, life1, cola1, disc1);
        const dataA2 = calculateLifetimeBenefits(monthlyA2, a2, life2, cola2, disc2);
        const dataB2 = calculateLifetimeBenefits(monthlyB2, b2, life2, cola2, disc2);
        const dataC2 = calculateLifetimeBenefits(monthlyC2, c2, life2, cola2, disc2);
        const total1 = Math.max(dataA1[dataA1.length-1].cumulativeTotal, dataB1[dataB1.length-1].cumulativeTotal, dataC1[dataC1.length-1].cumulativeTotal);
        const total2 = Math.max(dataA2[dataA2.length-1].cumulativeTotal, dataB2[dataB2.length-1].cumulativeTotal, dataC2[dataC2.length-1].cumulativeTotal);
        showComparisonSS(s1.name, s2.name, {
            fra: fra1, monthlyA: monthlyA1, monthlyB: monthlyB1, monthlyC: monthlyC1,
            total: total1, life: life1, claimA: a1, claimB: b1, claimC: c1, pia: pia1
        }, {
            fra: fra2, monthlyA: monthlyA2, monthlyB: monthlyB2, monthlyC: monthlyC2,
            total: total2, life: life2, claimA: a2, claimB: b2, claimC: c2, pia: pia2
        });
    });
}

function showComparisonSS(name1, name2, r1, r2) {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    const fraStr = (f) => f.years + (f.months > 0 ? ' + ' + f.months + 'mo' : '');
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3 style="color: #667eea;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>FRA: ${fraStr(r1.fra)} | PIA: $${r1.pia.toLocaleString()}/mo</div>
                        <div>Life: ${r1.life} | Best total: ${formatCurrency(r1.total)}</div>
                        <div>Monthly at ${r1.claimA}/${r1.claimB}/${r1.claimC}: ${formatCurrency(r1.monthlyA)} / ${formatCurrency(r1.monthlyB)} / ${formatCurrency(r1.monthlyC)}</div>
                    </div>
                </div>
                <div>
                    <h3 style="color: #e53e3e;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>FRA: ${fraStr(r2.fra)} | PIA: $${r2.pia.toLocaleString()}/mo</div>
                        <div>Life: ${r2.life} | Best total: ${formatCurrency(r2.total)}</div>
                        <div>Monthly at ${r2.claimA}/${r2.claimB}/${r2.claimC}: ${formatCurrency(r2.monthlyA)} / ${formatCurrency(r2.monthlyB)} / ${formatCurrency(r2.monthlyC)}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    resultsDiv.innerHTML = comparisonHTML + resultsDiv.innerHTML;
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
}

function downloadPDF() {
    const res = window.lastSSResult;
    if (!res) {
        alert('Please run "Compare Scenarios" first to see results.');
        return;
    }
    const chartCanvas = document.getElementById('lifetimeBenefitsChart');
    const chartImage = chartCanvas && window.lifetimeBenefitsChart ? chartCanvas.toDataURL('image/png') : null;
    const payload = {
        birthDate: document.getElementById('birthDate').value,
        monthlyPIA: res.monthlyPIA,
        lifeExpectancy: res.lifeExpectancy,
        claimAgeA: res.claimAgeA,
        claimAgeB: res.claimAgeB,
        claimAgeC: res.claimAgeC,
        colaRate: res.colaRate,
        discountRate: res.discountRate,
        fra: res.fra,
        monthlyA: res.monthlyA, monthlyB: res.monthlyB, monthlyC: res.monthlyC,
        totalA: res.totalA, totalB: res.totalB, totalC: res.totalC,
        bestScenario: res.bestScenario,
        breakEvenAB: res.breakEvenAB, breakEvenBC: res.breakEvenBC, breakEvenAC: res.breakEvenAC,
        dataA: res.dataA, dataB: res.dataB, dataC: res.dataC,
        chartImage: chartImage
    };
    fetch(SS_API_BASE + '/api/generate_ss_pdf.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'PDF failed'); } catch(e) { throw new Error(t || 'PDF failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) return r.text().then(t => { throw new Error('Server did not return a PDF. Log in as premium and try again.'); });
        return r.blob();
    })
    .then(blob => { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'SS_Claiming_Report_' + new Date().toISOString().split('T')[0] + '.pdf'; a.click(); URL.revokeObjectURL(a.href); })
    .catch(e => alert('Download PDF: ' + e.message));
}

function downloadCSV() {
    const res = window.lastSSResult;
    if (!res) {
        alert('Please run "Compare Scenarios" first to see results.');
        return;
    }
    const payload = {
        claimAgeA: res.claimAgeA, claimAgeB: res.claimAgeB, claimAgeC: res.claimAgeC,
        lifeExpectancy: res.lifeExpectancy,
        dataA: res.dataA, dataB: res.dataB, dataC: res.dataC
    };
    fetch(SS_API_BASE + '/api/export_ss_csv.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => { if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'Failed'); } catch(e) { throw new Error(t || 'Failed'); } }); return r.blob(); })
    .then(blob => { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'SS_Claiming_' + new Date().toISOString().split('T')[0] + '.csv'; a.click(); URL.revokeObjectURL(a.href); })
    .catch(e => alert('Error: ' + e.message));
}

function downloadClaimingSummary() {
    const res = window.lastSSResult;
    if (!res) {
        alert('Please run "Compare Scenarios" first to see results.');
        return;
    }
    const payload = {
        birthDate: document.getElementById('birthDate').value,
        monthlyPIA: res.monthlyPIA,
        lifeExpectancy: res.lifeExpectancy,
        claimAgeA: res.claimAgeA, claimAgeB: res.claimAgeB, claimAgeC: res.claimAgeC,
        fra: res.fra,
        monthlyA: res.monthlyA, monthlyB: res.monthlyB, monthlyC: res.monthlyC,
        totalA: res.totalA, totalB: res.totalB, totalC: res.totalC,
        bestScenario: res.bestScenario,
        breakEvenAB: res.breakEvenAB, breakEvenBC: res.breakEvenBC, breakEvenAC: res.breakEvenAC
    };
    fetch(SS_API_BASE + '/api/generate_ss_summary_pdf.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => { if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'Failed'); } catch(e) { throw new Error(t || 'Failed'); } }); return r.blob(); })
    .then(blob => { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'SS_Claiming_Summary_' + new Date().toISOString().split('T')[0] + '.pdf'; a.click(); URL.revokeObjectURL(a.href); })
    .catch(e => alert('Error: ' + e.message));
}