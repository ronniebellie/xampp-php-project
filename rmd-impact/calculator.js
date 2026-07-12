// RMD Impact Calculator JavaScript

// RMD divisor table from IRS Uniform Lifetime Table
const rmdDivisors = {
    73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
    80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
    87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
    94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4
};

// Joint Life and Last Survivor Expectancy Table (for when spouse is sole beneficiary and 10+ years younger)
// Key format: "ownerAge-spouseAge" -> divisor
// This is a simplified version - in production you'd want the full IRS table
const jointLifeExpectancy = {
    // Sample entries - format: ownerAge_spouseAge: divisor
    '73_63': 23.1, '73_62': 23.3, '73_61': 23.6, '73_60': 23.8, '73_59': 24.0, '73_58': 24.2, '73_57': 24.4, '73_56': 24.7, '73_55': 24.9, '73_54': 25.1, '73_53': 25.3,
    '74_64': 22.3, '74_63': 22.5, '74_62': 22.7, '74_61': 22.9, '74_60': 23.1, '74_59': 23.4, '74_58': 23.6, '74_57': 23.8, '74_56': 24.0, '74_55': 24.2, '74_54': 24.5,
    '75_65': 21.5, '75_64': 21.7, '75_63': 21.9, '75_62': 22.1, '75_61': 22.3, '75_60': 22.5, '75_59': 22.7, '75_58': 23.0, '75_57': 23.2, '75_56': 23.4, '75_55': 23.6,
    '80_70': 17.5, '80_69': 17.7, '80_68': 17.9, '80_67': 18.1, '80_66': 18.3, '80_65': 18.5, '80_64': 18.7, '80_63': 18.9, '80_62': 19.1, '80_61': 19.3, '80_60': 19.5,
    '85_75': 14.2, '85_74': 14.4, '85_73': 14.5, '85_72': 14.7, '85_71': 14.9, '85_70': 15.0, '85_69': 15.2, '85_68': 15.4, '85_67': 15.6, '85_66': 15.7, '85_65': 15.9,
    '90_80': 11.7, '90_79': 11.8, '90_78': 12.0, '90_77': 12.1, '90_76': 12.3, '90_75': 12.4, '90_74': 12.5, '90_73': 12.7, '90_72': 12.8, '90_71': 13.0, '90_70': 13.1,
    '95_85': 9.6, '95_84': 9.7, '95_83': 9.8, '95_82': 9.9, '95_81': 10.1, '95_80': 10.2, '95_79': 10.3, '95_78': 10.4, '95_77': 10.5, '95_76': 10.6, '95_75': 10.7,
    '100_90': 8.1, '100_89': 8.2, '100_88': 8.3, '100_87': 8.4, '100_86': 8.5, '100_85': 8.5, '100_84': 8.6, '100_83': 8.7, '100_82': 8.8, '100_81': 8.9, '100_80': 9.0
};

/**
 * Get RMD divisor based on age and spouse beneficiary status
 */
function getRMDDivisor(ownerAge, isSpouseBeneficiary, spouseAge) {
    // If spouse is sole beneficiary and more than 10 years younger, use Joint Life table
    if (isSpouseBeneficiary && spouseAge && (ownerAge - spouseAge) > 10) {
        const key = `${ownerAge}_${spouseAge}`;
        if (jointLifeExpectancy[key]) {
            return jointLifeExpectancy[key];
        }
        // If exact match not found, interpolate or use closest value
        // For simplicity, fall back to uniform table if not in our simplified table
    }
    
    // Use Uniform Lifetime Table
    return rmdDivisors[ownerAge] || 6.4; // Default to 6.4 for ages over 100
}

// 2026 Tax Brackets (estimated)
const taxBrackets2026 = {
    single: [
        { max: 11600, rate: 0.10 },
        { max: 47150, rate: 0.12 },
        { max: 100525, rate: 0.22 },
        { max: 191950, rate: 0.24 },
        { max: 243725, rate: 0.32 },
        { max: 609350, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ],
    married: [
        { max: 23200, rate: 0.10 },
        { max: 94300, rate: 0.12 },
        { max: 201050, rate: 0.22 },
        { max: 383900, rate: 0.24 },
        { max: 487450, rate: 0.32 },
        { max: 731200, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ],
    hoh: [
        { max: 16550, rate: 0.10 },
        { max: 63100, rate: 0.12 },
        { max: 100500, rate: 0.22 },
        { max: 191950, rate: 0.24 },
        { max: 243700, rate: 0.32 },
        { max: 609350, rate: 0.35 },
        { max: Infinity, rate: 0.37 }
    ]
};

const standardDeductions2026 = {
    single: 14600,
    married: 29200,
    hoh: 21900
};

let myChart = null;

function calculateTaxBracket(taxableIncome, filingStatus) {
    const brackets = taxBrackets2026[filingStatus];
    for (let bracket of brackets) {
        if (taxableIncome <= bracket.max) {
            return bracket.rate * 100;
        }
    }
    return 37;
}

/** Progressive federal income tax on taxable income (ordinary rates only). */
function calculateFederalTax(taxableIncome, filingStatus) {
    const brackets = taxBrackets2026[filingStatus];
    if (!brackets || taxableIncome <= 0) return 0;
    let tax = 0;
    let prevMax = 0;
    for (const bracket of brackets) {
        const width = bracket.max - prevMax;
        const inBracket = Math.min(Math.max(0, taxableIncome - prevMax), width);
        tax += inBracket * bracket.rate;
        prevMax = bracket.max;
        if (taxableIncome <= bracket.max) break;
    }
    return tax;
}

function formatPercent(value, decimals) {
    decimals = decimals != null ? decimals : 1;
    return (Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals)).toFixed(decimals) + '%';
}

/** Planned traditional withdrawal vs RMD — shortfall or voluntary excess after age 73. */
function computeRmdInteraction(plannedTraditional, rmdAmount, age) {
    const planned = plannedTraditional || 0;
    const rmd = rmdAmount || 0;
    if (age < 73 || rmd <= 0) {
        return { rmdShortfall: 0, excessOverRmd: 0 };
    }
    return {
        rmdShortfall: Math.max(0, rmd - planned),
        excessOverRmd: Math.max(0, planned - rmd)
    };
}

function getCalculationMethodologyHtml() {
    return '<div class="calc-methodology" style="margin-top: 20px; padding: 16px 18px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">' +
        '<h4 style="margin: 0 0 10px 0; color: #334155;">How each projection year is calculated</h4>' +
        '<ol style="margin: 0; padding-left: 20px; color: #475569; font-size: 0.95em; line-height: 1.6;">' +
        '<li><strong>Start-of-year balance</strong> — Traditional IRA balance at the beginning of the age year (after all prior-year withdrawals and growth).</li>' +
        '<li><strong>Planned withdrawal</strong> — Your entered annual amount (optionally inflation-adjusted), split across Traditional / Roth / Taxable per your source setting.</li>' +
        '<li><strong>Required RMD</strong> — At age 73+, the IRS minimum on the <em>start-of-year</em> traditional balance (Uniform or Joint Life table).</li>' +
        '<li><strong>Traditional IRA withdrawal</strong> — The greater of your planned traditional amount and the RMD (capped at the account balance). Any planned traditional withdrawal counts toward the RMD; only a shortfall is added on top.</li>' +
        '<li><strong>Subtract withdrawals</strong> — Planned Roth and taxable withdrawals are taken from those balances; the traditional withdrawal reduces the IRA balance.</li>' +
        '<li><strong>Apply growth</strong> — Each account\'s <em>remaining</em> balance grows at your entered rate for the rest of the year. Withdrawals happen before growth, not after.</li>' +
        '<li><strong>Tax estimate</strong> — Total income includes traditional withdrawals, Social Security, pension, and other income (Roth withdrawals are tax-free). Federal tax uses progressive 2026 brackets on income after the standard deduction. Marginal bracket is the rate on the last dollar; effective rate is total federal tax ÷ total income.</li>' +
        '</ol></div>';
}

const MONTH_NAMES = ['', 'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'];

function getPlanStartYear(data) {
    if (data && data.planStartYear) return parseInt(data.planStartYear, 10);
    const el = document.getElementById('planStartYear');
    if (el) return parseInt(el.value, 10) || new Date().getFullYear();
    return new Date().getFullYear();
}

function calendarYearForAge(currentAge, planStartYear, age) {
    return planStartYear + (age - currentAge);
}

/** True for boolean true, 'yes', or 1; false for false, 'no', 0, null, undefined. */
function isTruthyFlag(value) {
    return value === true || value === 'yes' || value === 1 || value === '1';
}

function isWithdrawalsEnabled(data) {
    return isTruthyFlag(data && data.enableWithdrawals) &&
        (parseFloat(data.withdrawalAmount) || 0) > 0;
}

/**
 * Resolve when withdrawals begin — either a specific age or a calendar month/year.
 */
function resolveWithdrawalStart(data) {
    const planStartYear = getPlanStartYear(data);
    const mode = data.withdrawalStartMode || 'age';

    if (mode === 'date') {
        const startYear = parseInt(data.withdrawalStartYear, 10) || planStartYear;
        const startMonth = parseInt(data.withdrawalStartMonth, 10) || 1;
        const startAge = data.currentAge + (startYear - planStartYear);
        return {
            startMode: 'date',
            planStartYear,
            startYear,
            startMonth,
            startAge: Math.max(data.currentAge, Math.min(100, startAge)),
            startLabel: MONTH_NAMES[startMonth] + ' ' + startYear
        };
    }

    const startAge = parseInt(data.withdrawalStartAge, 10) || data.currentAge;
    return {
        startMode: 'age',
        planStartYear,
        startAge,
        startLabel: 'age ' + startAge
    };
}

function isWithdrawalActive(age, data, wc) {
    if (!wc.enabled || age > wc.endAge) return false;
    if (wc.startMode === 'date') {
        return calendarYearForAge(data.currentAge, wc.planStartYear, age) >= wc.startYear;
    }
    return age >= wc.startAge;
}

function getProjectionStartAge(data) {
    if (isWithdrawalsEnabled(data)) {
        return Math.min(73, getWithdrawalConfig(data).startAge);
    }
    return 73;
}

/**
 * Normalize the planned-withdrawal inputs into a config object.
 * When withdrawals are disabled the projection behaves exactly as before.
 */
function getWithdrawalConfig(data) {
    const enabled = isWithdrawalsEnabled(data);
    const source = data.withdrawalSource || 'traditional';
    const pctT = data.pctTraditional != null ? parseFloat(data.pctTraditional) : 100;
    const pctR = data.pctRoth != null ? parseFloat(data.pctRoth) : 0;
    const pctX = data.pctTaxable != null ? parseFloat(data.pctTaxable) : 0;
    const resolved = resolveWithdrawalStart(data);
    return {
        enabled,
        amount: enabled ? (parseFloat(data.withdrawalAmount) || 0) : 0,
        startMode: resolved.startMode,
        startAge: resolved.startAge,
        startYear: resolved.startYear,
        startMonth: resolved.startMonth,
        startLabel: resolved.startLabel,
        planStartYear: resolved.planStartYear,
        endAge: parseInt(data.withdrawalEndAge, 10) || 100,
        inflate: isTruthyFlag(data.withdrawalInflation),
        inflationRate: parseFloat(data.withdrawalInflationRate) || 0,
        source: source,
        pct: source === 'combination'
            ? { traditional: pctT, roth: pctR, taxable: pctX }
            : { traditional: 100, roth: 0, taxable: 0 }
    };
}

/**
 * Split a total planned withdrawal into per-account amounts based on the
 * selected source. "combination" uses the user-provided percentages.
 */
function splitPlannedWithdrawal(totalPlanned, wc) {
    if (totalPlanned <= 0) return { traditional: 0, roth: 0, taxable: 0 };
    switch (wc.source) {
        case 'roth':
            return { traditional: 0, roth: totalPlanned, taxable: 0 };
        case 'taxable':
            return { traditional: 0, roth: 0, taxable: totalPlanned };
        case 'combination':
            return {
                traditional: totalPlanned * (wc.pct.traditional / 100),
                roth: totalPlanned * (wc.pct.roth / 100),
                taxable: totalPlanned * (wc.pct.taxable / 100)
            };
        case 'traditional':
        default:
            return { traditional: totalPlanned, roth: 0, taxable: 0 };
    }
}

function calculateProjection(data) {
    const results = [];
    let tradBalance = data.accountBalance;
    let rothBalance = parseFloat(data.rothBalance) || 0;
    let taxableBalance = parseFloat(data.taxableBalance) || 0;
    const startAge = data.currentAge;
    const rmdStartAge = 73;
    let currentSpouseAge = data.spouseAge;
    const wc = getWithdrawalConfig(data);

    for (let age = startAge; age <= 100; age++) {
        const tradStart = tradBalance;
        const rothStart = rothBalance;
        const taxableStart = taxableBalance;

        // Planned withdrawal for this year (optionally inflation-adjusted from start)
        let planned = 0;
        if (isWithdrawalActive(age, data, wc)) {
            const yearsElapsed = age - wc.startAge;
            planned = wc.inflate
                ? wc.amount * Math.pow(1 + wc.inflationRate / 100, yearsElapsed)
                : wc.amount;
        }
        const split = splitPlannedWithdrawal(planned, wc);

        // RMD applies to the traditional (tax-deferred) account only, based on
        // the start-of-year balance.
        let rmdAmount = 0;
        if (age >= rmdStartAge && tradStart > 0) {
            const divisor = getRMDDivisor(age, data.isSpouseBeneficiary, currentSpouseAge);
            if (divisor) {
                rmdAmount = tradStart / divisor;
            }
        }

        // Once RMDs begin, any planned traditional withdrawal counts toward the
        // RMD; only the shortfall (if planned < RMD) is withdrawn on top. Never
        // withdraw more than the account holds.
        let tradWithdrawal = Math.min(tradStart, Math.max(split.traditional, rmdAmount));
        const rothWithdrawal = Math.min(rothStart, split.roth);
        const taxableWithdrawal = Math.min(taxableStart, split.taxable);

        tradBalance = tradStart - tradWithdrawal;
        rothBalance = rothStart - rothWithdrawal;
        taxableBalance = taxableStart - taxableWithdrawal;

        const totalWithdrawal = tradWithdrawal + rothWithdrawal + taxableWithdrawal;

        // Ordinary taxable income: traditional withdrawals (which include the RMD)
        // plus other income. Roth withdrawals are tax-free; taxable-brokerage
        // withdrawals are excluded here because long-term capital gains are taxed
        // under a separate schedule (disclosed in the UI).
        const totalIncome = tradWithdrawal + data.socialSecurity + data.pension + data.otherIncome;
        const deduction = data.useStandardDeduction ? standardDeductions2026[data.filingStatus] : 0;
        const taxableIncome = Math.max(0, totalIncome - deduction);
        const federalTax = calculateFederalTax(taxableIncome, data.filingStatus);
        const taxBracket = calculateTaxBracket(taxableIncome, data.filingStatus);
        const effectiveTaxRate = totalIncome > 0 ? (federalTax / totalIncome) * 100 : 0;
        const rmdInteraction = computeRmdInteraction(split.traditional, rmdAmount, age);

        results.push({
            age,
            balance: tradStart, // traditional balance at start of year (back-compat key)
            rothBalance: rothStart,
            taxableBalance: taxableStart,
            totalBalance: tradStart + rothStart + taxableStart,
            rmdAmount,
            plannedWithdrawal: planned,
            plannedTraditional: split.traditional,
            rmdShortfall: rmdInteraction.rmdShortfall,
            excessOverRmd: rmdInteraction.excessOverRmd,
            traditionalWithdrawal: tradWithdrawal,
            rothWithdrawal,
            taxableWithdrawal,
            totalWithdrawal,
            totalIncome,
            taxableIncome,
            federalTax,
            taxBracket,
            effectiveTaxRate
        });

        // Grow whatever remains in each account
        const g = 1 + data.growthRate / 100;
        if (tradBalance > 0) tradBalance *= g;
        if (rothBalance > 0) rothBalance *= g;
        if (taxableBalance > 0) taxableBalance *= g;

        // Age spouse along with owner
        if (currentSpouseAge) {
            currentSpouseAge++;
        }
    }

    return results;
}

function buildRMDSummary(results) {
    const firstRMD = results.find(r => r.rmdAmount > 0) || { rmdAmount: 0 };
    const age80Data = results.find(r => r.age === 80) || firstRMD;
    const age90Data = results.find(r => r.age === 90) || age80Data;
    return {
        firstRMD: firstRMD.rmdAmount,
        age80RMD: age80Data.rmdAmount,
        age90RMD: age90Data.rmdAmount,
        peakTaxBracket: Math.max(...results.map(r => r.taxBracket)),
        peakEffectiveTaxRate: Math.round(Math.max(...results.map(r => r.effectiveTaxRate)) * 10) / 10
    };
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

function generateInterpretation(results, data) {
    const zeroRow = { rmdAmount: 0, taxBracket: results.length ? results[0].taxBracket : 0 };
    const firstRMD = results.find(r => r.rmdAmount > 0) || zeroRow;
    const age80Data = results.find(r => r.age === 80);
    const age90Data = results.find(r => r.age === 90);
    
    let interpretation = '<h3>What This Means For You</h3><ul>';

    // Planned-withdrawal comparison: show how pre-73 withdrawals lower future RMDs
    if (isWithdrawalsEnabled(data)) {
        const baselineData = Object.assign({}, data, { enableWithdrawals: false });
        const baseline = calculateProjection(baselineData);
        const baseFirst = baseline.find(r => r.rmdAmount > 0) || zeroRow;
        const basePeak = Math.max.apply(null, baseline.map(r => r.taxBracket));
        const planPeak = Math.max.apply(null, results.map(r => r.taxBracket));
        const firstDiff = baseFirst.rmdAmount - firstRMD.rmdAmount;

        let msg = '<li><strong>Impact of your planned withdrawals:</strong> ';
        if (data.withdrawalStartMode === 'date' && data.withdrawalStartYear) {
            const month = parseInt(data.withdrawalStartMonth, 10) || 1;
            msg += 'Withdrawals begin in ' + MONTH_NAMES[month] + ' ' + data.withdrawalStartYear + '. ';
        }
        if (firstDiff > 1) {
            msg += `Because you plan to withdraw before RMDs begin, your first RMD at age 73 is about ${formatCurrency(firstRMD.rmdAmount)} instead of ${formatCurrency(baseFirst.rmdAmount)} — roughly ${formatCurrency(firstDiff)} lower per year. `;
        } else if (data.withdrawalSource === 'roth' || data.withdrawalSource === 'taxable') {
            msg += 'Your withdrawals come from Roth and/or taxable accounts, so they don\'t reduce your traditional (tax-deferred) balance — your RMDs stay the same as if you hadn\'t withdrawn. Drawing from your traditional IRA/401(k) first is what lowers future RMDs. ';
        } else {
            msg += 'Your plan changes the year-by-year picture below. ';
        }
        if (basePeak !== planPeak) {
            msg += `Your peak estimated tax bracket changes from ${basePeak}% to ${planPeak}%. `;
        }
        msg += '</li>';
        interpretation += msg;

        interpretation += '<li><strong>Note on taxes by source:</strong> Traditional IRA/401(k) withdrawals (including RMDs) are counted as ordinary income here. Roth withdrawals are treated as tax-free, and taxable-brokerage withdrawals are excluded from the ordinary-income tax bracket because long-term capital gains are taxed under a separate schedule.</li>';
    }

    // Mention if using Joint Life table
    if (data.isSpouseBeneficiary && data.spouseAge && (data.currentAge - data.spouseAge) > 10) {
        interpretation += '<li><strong>Special calculation applies:</strong> Because your spouse is your sole beneficiary and is more than 10 years younger, we\'re using the IRS Joint Life and Last Survivor Expectancy Table, which results in lower RMDs than the standard table.</li>';
    }

    if (data.accountBalance <= 50000) {
        interpretation += `<li><strong>Your RMDs will be very modest.</strong> With a current balance of ${formatCurrency(data.accountBalance)}, your first RMD at age 73 will only be around ${formatCurrency(firstRMD.rmdAmount)}. This is unlikely to create any significant tax burden.</li>`;
    } else if (data.accountBalance <= 200000) {
        interpretation += `<li><strong>Your RMDs will be manageable.</strong> Starting at ${formatCurrency(firstRMD.rmdAmount)} at age 73, these withdrawals shouldn't dramatically impact your taxes for most situations.</li>`;
    } else if (data.accountBalance <= 600000) {
        interpretation += `<li><strong>RMD planning may be beneficial.</strong> With ${formatCurrency(data.accountBalance)} in tax-deferred accounts, your RMDs will be substantial enough that strategies like Roth conversions or QCDs could help reduce your tax burden.</li>`;
    } else {
        interpretation += `<li><strong>RMD planning is important for you.</strong> With ${formatCurrency(data.accountBalance)} in tax-deferred accounts, RMDs will be significant. You should seriously consider tax planning strategies like Roth conversions, qualified charitable distributions, and tax bracket management.</li>`;
    }

    if (firstRMD.taxBracket <= 12) {
        interpretation += '<li><strong>You\'re likely in a favorable tax situation.</strong> Your estimated marginal bracket remains low even with RMDs.</li>';
    } else if (firstRMD.taxBracket <= 22) {
        interpretation += '<li><strong>You\'re in a moderate tax bracket.</strong> RMDs are adding to your tax bill, but you still have room for planning opportunities.</li>';
    } else {
        interpretation += '<li><strong>RMDs may push you into higher tax brackets.</strong> Your marginal bracket (rate on the last dollar) can be much higher than your effective rate (total tax ÷ total income). Review the Effective Federal Rate column for your true average burden.</li>';
    }

    if (age80Data && age80Data.balance > data.accountBalance * 1.2) {
        interpretation += '<li><strong>Your account is projected to continue growing.</strong> Even with RMDs, your portfolio growth is outpacing withdrawals, which means RMDs will increase over time.</li>';
    }

    if (data.accountBalance >= 500000) {
        interpretation += '<li><strong>Recommended actions:</strong> Consider working with a financial advisor on Roth conversion strategies, especially in lower-income years before Social Security or pension income begins. Qualified Charitable Distributions (QCDs) can also help if you\'re charitably inclined.</li>';
    } else if (data.accountBalance >= 150000) {
        interpretation += '<li><strong>Consider:</strong> Reviewing your withdrawal strategy to see if taking distributions earlier than required might smooth out your tax burden over time.</li>';
    } else {
        interpretation += '<li><strong>Good news:</strong> For most people with account balances like yours, RMDs are simply part of normal retirement income and don\'t require complex planning strategies.</li>';
    }

    interpretation += '</ul>';
    interpretation += getCalculationMethodologyHtml();
    return interpretation;
}

function formatRmdAdjustmentCell(r) {
    if (r.rmdShortfall > 0) {
        return formatCurrency(r.rmdShortfall) + ' <span style="color:#b45309;font-size:0.85em;">(shortfall)</span>';
    }
    if (r.excessOverRmd > 0) {
        return formatCurrency(r.excessOverRmd) + ' <span style="color:#059669;font-size:0.85em;">(excess)</span>';
    }
    return '—';
}

function buildProjectionTableRow(r) {
    return `
            <tr>
                <td>${r.age}</td>
                <td>${formatCurrency(r.balance)}</td>
                <td>${formatCurrency(r.plannedTraditional)}</td>
                <td>${r.age >= 73 ? formatCurrency(r.rmdAmount) : '—'}</td>
                <td>${formatRmdAdjustmentCell(r)}</td>
                <td>${formatCurrency(r.traditionalWithdrawal)}</td>
                <td>${formatCurrency(r.totalIncome)}</td>
                <td>${r.taxBracket}%</td>
                <td>${formatPercent(r.effectiveTaxRate)}</td>
            </tr>`;
}

function displayResults(results, data) {
    window.lastRMDResult = results;
    window.lastRMDData = data;

    const resultsDiv = document.getElementById('results');
    resultsDiv.style.display = 'block';

    const firstRMD = results.find(r => r.rmdAmount > 0) || { rmdAmount: 0, taxBracket: 0 };
    const age80Data = results.find(r => r.age === 80) || firstRMD;
    const age90Data = results.find(r => r.age === 90) || age80Data;

    const peakMarginal = Math.max(...results.map(r => r.taxBracket));
    const peakEffective = Math.max(...results.map(r => r.effectiveTaxRate));

    const summaryHTML = `
        <div class="summary-card">
            <div class="summary-label">First RMD (Age 73)</div>
            <div class="summary-value">${formatCurrency(firstRMD.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">RMD at Age 80</div>
            <div class="summary-value">${formatCurrency(age80Data.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">RMD at Age 90</div>
            <div class="summary-value">${formatCurrency(age90Data.rmdAmount)}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Peak Marginal Bracket</div>
            <div class="summary-value">${peakMarginal}%</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Peak Effective Federal Rate</div>
            <div class="summary-value">${formatPercent(peakEffective)}</div>
        </div>
    `;
    document.getElementById('summaryCards').innerHTML = summaryHTML;

    document.getElementById('interpretation').innerHTML = generateInterpretation(results, data);

    const chartData = results.filter(r => r.age >= data.currentAge && r.age <= 100);

    // Render the chart defensively: if Chart.js failed to load (ad blocker,
    // privacy extension, offline, or CDN outage), don't let it abort the rest
    // of the results (summary table + scroll). Otherwise the page would appear
    // to "do nothing" when the Calculate button is clicked.
    try {
        if (typeof Chart === 'undefined') {
            throw new Error('Chart.js library not loaded');
        }

        if (myChart) {
            myChart.destroy();
        }

        const ctx = document.getElementById('rmdChart').getContext('2d');
        myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(r => r.age),
            datasets: (function() {
                const sets = [
                    {
                        label: 'Traditional Balance',
                        data: chartData.map(r => r.balance),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Annual RMD',
                        data: chartData.map(r => r.rmdAmount),
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }
                ];
                if (isWithdrawalsEnabled(data)) {
                    sets.push({
                        label: 'Total Withdrawals',
                        data: chartData.map(r => r.totalWithdrawal),
                        borderColor: '#e53e3e',
                        backgroundColor: 'rgba(229, 62, 62, 0.08)',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        fill: false,
                        tension: 0.4
                    });
                }
                return sets;
            })()
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return '$' + (value/1000).toFixed(0) + 'K';
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Age'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            }
        }
        });
    } catch (chartError) {
        console.error('RMD chart could not be rendered:', chartError);
        const chartCanvas = document.getElementById('rmdChart');
        if (chartCanvas) {
            const wrapper = chartCanvas.parentNode;
            if (wrapper && !wrapper.querySelector('.chart-unavailable-note')) {
                const note = document.createElement('p');
                note.className = 'chart-unavailable-note';
                note.style.cssText = 'color:#92400e;background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px;margin:0;';
                note.textContent = 'The chart could not be displayed (a required library was blocked from loading), but your full year-by-year results are shown below.';
                wrapper.insertBefore(note, chartCanvas);
            }
            chartCanvas.style.display = 'none';
        }
    }

    // Generate table data based on premium status
    const tableBody = document.getElementById('tableBody');
    let tableHTML = '';

    const wc = getWithdrawalConfig(data);
    const wStartAge = isWithdrawalsEnabled(data)
        ? Math.max(data.currentAge, wc.startAge)
        : 73;
    const tableFromAge = Math.min(73, wStartAge);

    if (typeof isPremiumUser !== 'undefined' && isPremiumUser) {
        // Premium: Show ALL years from the first relevant age to 100
        const tableData = results.filter(r => r.age >= tableFromAge);
        tableHTML = tableData.map(r => buildProjectionTableRow(r)).join('');
    } else {
        // Free: Show first 3 rows, then blurred preview
        const freeData = results.filter(r => r.age >= tableFromAge && (r.age - tableFromAge) % 5 === 0).slice(0, 3);
        tableHTML = freeData.map(r => buildProjectionTableRow(r)).join('');
        
        // Add blurred preview rows
        tableHTML += `
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>88</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>—</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
                <td>XX%</td>
            </tr>
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>93</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>—</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
                <td>XX%</td>
            </tr>
            <tr style="filter: blur(4px); user-select: none; pointer-events: none;">
                <td>98</td>
                <td>$XXX,XXX</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>—</td>
                <td>$XX,XXX</td>
                <td>$XX,XXX</td>
                <td>XX%</td>
                <td>XX%</td>
            </tr>
        `;
    }

    tableBody.innerHTML = tableHTML;

    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Read the optional planned-withdrawal inputs from the form. Returns fields
 * that are merged into the main data object. Safe to call even if the withdrawal
 * section is absent (returns disabled defaults).
 */
function readWithdrawalInputs() {
    const el = function(id) { return document.getElementById(id); };
    const enableEl = el('enableWithdrawals');
    const enabled = enableEl ? isTruthyFlag(enableEl.value) : false;
    const planYearEl = el('planStartYear');
    return {
        enableWithdrawals: enabled,
        withdrawalAmount: enabled && el('withdrawalAmount') ? (parseFloat(el('withdrawalAmount').value) || 0) : 0,
        withdrawalStartMode: el('withdrawalStartMode') ? el('withdrawalStartMode').value : 'age',
        withdrawalStartAge: el('withdrawalStartAge') ? (parseInt(el('withdrawalStartAge').value, 10) || null) : null,
        withdrawalStartMonth: el('withdrawalStartMonth') ? (parseInt(el('withdrawalStartMonth').value, 10) || 1) : 1,
        withdrawalStartYear: el('withdrawalStartYear') ? (parseInt(el('withdrawalStartYear').value, 10) || null) : null,
        planStartYear: planYearEl ? (parseInt(planYearEl.value, 10) || new Date().getFullYear()) : new Date().getFullYear(),
        withdrawalEndAge: el('withdrawalEndAge') ? (parseInt(el('withdrawalEndAge').value, 10) || 100) : 100,
        withdrawalInflation: el('withdrawalInflation') ? isTruthyFlag(el('withdrawalInflation').value) : false,
        withdrawalInflationRate: el('withdrawalInflationRate') ? (parseFloat(el('withdrawalInflationRate').value) || 0) : 0,
        withdrawalSource: el('withdrawalSource') ? el('withdrawalSource').value : 'traditional',
        rothBalance: el('rothBalance') ? (parseFloat(el('rothBalance').value) || 0) : 0,
        taxableBalance: el('taxableBalance') ? (parseFloat(el('taxableBalance').value) || 0) : 0,
        pctTraditional: el('pctTraditional') ? (parseFloat(el('pctTraditional').value) || 0) : 100,
        pctRoth: el('pctRoth') ? (parseFloat(el('pctRoth').value) || 0) : 0,
        pctTaxable: el('pctTaxable') ? (parseFloat(el('pctTaxable').value) || 0) : 0
    };
}

function gatherRMDFormData() {
    const spouseBeneficiary = document.getElementById('spouseBeneficiary').value === 'yes';
    const spouseAge = spouseBeneficiary ? parseInt(document.getElementById('spouseAge').value, 10) : null;
    const data = {
        currentAge: parseInt(document.getElementById('currentAge').value, 10),
        accountBalance: parseFloat(document.getElementById('accountBalance').value),
        growthRate: parseFloat(document.getElementById('growthRate').value),
        socialSecurity: parseFloat(document.getElementById('socialSecurity').value) || 0,
        pension: parseFloat(document.getElementById('pension').value) || 0,
        otherIncome: parseFloat(document.getElementById('otherIncome').value) || 0,
        filingStatus: document.getElementById('filingStatus').value,
        useStandardDeduction: document.getElementById('standardDeduction').value === 'yes',
        isSpouseBeneficiary: spouseBeneficiary,
        spouseAge: spouseAge
    };
    Object.assign(data, readWithdrawalInputs());
    return data;
}

const RMD_FORM_COMPARE_KEYS = [
    'currentAge', 'accountBalance', 'growthRate', 'socialSecurity', 'pension', 'otherIncome',
    'filingStatus', 'useStandardDeduction', 'isSpouseBeneficiary', 'spouseAge',
    'enableWithdrawals', 'withdrawalAmount', 'withdrawalStartMode', 'withdrawalStartAge',
    'withdrawalStartMonth', 'withdrawalStartYear', 'planStartYear', 'withdrawalEndAge',
    'withdrawalInflation', 'withdrawalInflationRate', 'withdrawalSource',
    'rothBalance', 'taxableBalance', 'pctTraditional', 'pctRoth', 'pctTaxable'
];

function rmdFormMatchesLastCalculation(data) {
    const last = window.lastRMDData;
    if (!last || !window.lastRMDResult) return false;
    for (let i = 0; i < RMD_FORM_COMPARE_KEYS.length; i++) {
        const key = RMD_FORM_COMPARE_KEYS[i];
        if (JSON.stringify(data[key]) !== JSON.stringify(last[key])) {
            return false;
        }
    }
    return true;
}

function getRMDCalculationResults(data) {
    if (rmdFormMatchesLastCalculation(data)) {
        return window.lastRMDResult;
    }
    return calculateProjection(data);
}

/**
 * Validate the planned-withdrawal inputs. Returns an error string or null.
 */
function validateWithdrawalInputs(w, currentAge) {
    if (!w.enableWithdrawals) return null;
    if (!(w.withdrawalAmount > 0)) {
        return 'Please enter a planned annual withdrawal amount greater than 0 (or set "Include planned withdrawals" to No).';
    }
    const resolved = resolveWithdrawalStart(Object.assign({ currentAge: currentAge }, w));
    const start = resolved.startAge;
    if (w.withdrawalStartMode === 'date') {
        const planYear = w.planStartYear || getPlanStartYear(w);
        const startYear = parseInt(w.withdrawalStartYear, 10);
        if (!startYear || startYear < planYear) {
            return 'Withdrawal start year must be ' + planYear + ' or later.';
        }
        if (startYear > planYear + (100 - currentAge)) {
            return 'Withdrawal start year is too far in the future for your current age.';
        }
    } else if (start < currentAge || start > 100) {
        return 'Withdrawal start age must be between your current age and 100.';
    }
    if (w.withdrawalEndAge < start || w.withdrawalEndAge > 100) {
        return 'Withdrawal end age must be between the start age and 100.';
    }
    if ((w.withdrawalSource === 'roth' || w.withdrawalSource === 'combination') && w.rothBalance < 0) {
        return 'Please enter a valid Roth balance.';
    }
    if ((w.withdrawalSource === 'taxable' || w.withdrawalSource === 'combination') && w.taxableBalance < 0) {
        return 'Please enter a valid taxable brokerage balance.';
    }
    if (w.withdrawalSource === 'combination') {
        const sum = w.pctTraditional + w.pctRoth + w.pctTaxable;
        if (Math.abs(sum - 100) > 0.01) {
            return 'For a combination source, the Traditional / Roth / Taxable percentages must add up to 100%. They currently total ' + sum + '%.';
        }
    }
    return null;
}

document.addEventListener('DOMContentLoaded', function() {
    const rmdForm = document.getElementById('rmdForm');
    if (!rmdForm) return;
    rmdForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const data = gatherRMDFormData();

    if (data.currentAge < 50 || data.currentAge > 100) {
        alert('Please enter a valid age between 50 and 100');
        return;
    }

    if (data.accountBalance < 0) {
        alert('Please enter a valid account balance');
        return;
    }

    if (data.growthRate < 0 || data.growthRate > 20) {
        alert('Please enter a valid growth rate between 0 and 20%');
        return;
    }

    if (data.isSpouseBeneficiary && (!data.spouseAge || data.spouseAge < 18 || data.spouseAge > 100)) {
        alert('Please enter a valid spouse age between 18 and 100');
        return;
    }

    const withdrawalError = validateWithdrawalInputs(data, data.currentAge);
    if (withdrawalError) {
        alert(withdrawalError);
        return;
    }

    const results = calculateProjection(data);
    displayResults(results, data);

    // Set share URL so that "Share" actions can reproduce this scenario/results
    const shareEl = document.getElementById('shareResults');
    if (shareEl) {
        const params = new URLSearchParams();
        params.set('currentAge', String(data.currentAge));
        params.set('accountBalance', String(data.accountBalance));
        params.set('growthRate', String(data.growthRate));
        params.set('socialSecurity', String(data.socialSecurity));
        params.set('pension', String(data.pension));
        params.set('otherIncome', String(data.otherIncome));
        params.set('filingStatus', data.filingStatus);
        params.set('standardDeduction', data.useStandardDeduction ? 'yes' : 'no');
        params.set('spouseBeneficiary', data.isSpouseBeneficiary ? 'yes' : 'no');
        if (data.spouseAge) {
            params.set('spouseAge', String(data.spouseAge));
        }
        if (isWithdrawalsEnabled(data)) {
            params.set('enableWithdrawals', 'yes');
            params.set('withdrawalAmount', String(data.withdrawalAmount));
            params.set('withdrawalStartMode', data.withdrawalStartMode || 'age');
            if (data.withdrawalStartAge) params.set('withdrawalStartAge', String(data.withdrawalStartAge));
            if (data.withdrawalStartMonth) params.set('withdrawalStartMonth', String(data.withdrawalStartMonth));
            if (data.withdrawalStartYear) params.set('withdrawalStartYear', String(data.withdrawalStartYear));
            if (data.planStartYear) params.set('planStartYear', String(data.planStartYear));
            params.set('withdrawalEndAge', String(data.withdrawalEndAge));
            params.set('withdrawalInflation', data.withdrawalInflation ? 'yes' : 'no');
            params.set('withdrawalInflationRate', String(data.withdrawalInflationRate));
            params.set('withdrawalSource', data.withdrawalSource);
            params.set('rothBalance', String(data.rothBalance));
            params.set('taxableBalance', String(data.taxableBalance));
            if (data.withdrawalSource === 'combination') {
                params.set('pctTraditional', String(data.pctTraditional));
                params.set('pctRoth', String(data.pctRoth));
                params.set('pctTaxable', String(data.pctTaxable));
            }
        }
        const url = window.location.origin + window.location.pathname + '?' + params.toString();
        shareEl.setAttribute('data-share-url', url);
    }
    });
});

// If URL contains scenario parameters, pre-fill the form and auto-run the calculation
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search || '');
    if (!params.has('currentAge') || !params.has('accountBalance')) {
        return;
    }

    function setValue(id, key) {
        const el = document.getElementById(id);
        if (el && params.has(key)) {
            el.value = params.get(key);
        }
    }

    setValue('currentAge', 'currentAge');
    setValue('accountBalance', 'accountBalance');
    setValue('growthRate', 'growthRate');
    setValue('socialSecurity', 'socialSecurity');
    setValue('pension', 'pension');
    setValue('otherIncome', 'otherIncome');
    setValue('filingStatus', 'filingStatus');
    if (params.has('standardDeduction')) {
        setValue('standardDeduction', 'standardDeduction');
    }
    if (params.has('spouseBeneficiary')) {
        setValue('spouseBeneficiary', 'spouseBeneficiary');
        if (typeof toggleSpouseAge === 'function') {
            toggleSpouseAge();
        }
    }
    if (params.has('spouseAge')) {
        setValue('spouseAge', 'spouseAge');
    }

    if (params.has('enableWithdrawals')) {
        setValue('enableWithdrawals', 'enableWithdrawals');
        setValue('withdrawalAmount', 'withdrawalAmount');
        setValue('withdrawalStartMode', 'withdrawalStartMode');
        setValue('withdrawalStartAge', 'withdrawalStartAge');
        setValue('withdrawalStartMonth', 'withdrawalStartMonth');
        setValue('withdrawalStartYear', 'withdrawalStartYear');
        setValue('planStartYear', 'planStartYear');
        setValue('withdrawalEndAge', 'withdrawalEndAge');
        setValue('withdrawalInflation', 'withdrawalInflation');
        setValue('withdrawalInflationRate', 'withdrawalInflationRate');
        setValue('withdrawalSource', 'withdrawalSource');
        setValue('rothBalance', 'rothBalance');
        setValue('taxableBalance', 'taxableBalance');
        setValue('pctTraditional', 'pctTraditional');
        setValue('pctRoth', 'pctRoth');
        setValue('pctTaxable', 'pctTaxable');
        if (typeof toggleWithdrawalFields === 'function') {
            toggleWithdrawalFields();
        }
        if (typeof toggleWithdrawalStartMode === 'function') {
            toggleWithdrawalStartMode();
        }
    }

    const form = document.getElementById('rmdForm');
    if (form) {
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
});

// Premium Save/Load/PDF Functionality
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    const calendarBtn = document.getElementById('downloadCalendarBtn');
    const explainBtn = document.getElementById('explainResultsBtnInResults');

    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (calendarBtn) calendarBtn.addEventListener('click', downloadCalendar);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

// Global delegated handler so Explain works even after DOM replacements
document.addEventListener('click', function (event) {
    const target = event.target && event.target.closest ? event.target.closest('#explainResultsBtnInResults') : null;
    if (!target) return;
    event.preventDefault();
    try {
        if (typeof explainResults === 'function') {
            explainResults();
        }
    } catch (e) {
        console.error('Explain results handler error:', e);
    }
});

function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My RMD Plan');
    if (!scenarioName) return;
    
    // Gather all form inputs
    const val = function(id) { const e = document.getElementById(id); return e ? e.value : ''; };
    const formData = {
        currentAge: val('currentAge'),
        accountBalance: val('accountBalance'),
        growthRate: val('growthRate'),
        socialSecurity: val('socialSecurity'),
        pension: val('pension'),
        otherIncome: val('otherIncome'),
        filingStatus: val('filingStatus'),
        standardDeduction: val('standardDeduction'),
        spouseBeneficiary: val('spouseBeneficiary'),
        spouseAge: val('spouseAge'),
        enableWithdrawals: val('enableWithdrawals'),
        withdrawalAmount: val('withdrawalAmount'),
        withdrawalStartMode: val('withdrawalStartMode'),
        withdrawalStartAge: val('withdrawalStartAge'),
        withdrawalStartMonth: val('withdrawalStartMonth'),
        withdrawalStartYear: val('withdrawalStartYear'),
        planStartYear: val('planStartYear'),
        withdrawalEndAge: val('withdrawalEndAge'),
        withdrawalInflation: val('withdrawalInflation'),
        withdrawalInflationRate: val('withdrawalInflationRate'),
        withdrawalSource: val('withdrawalSource'),
        rothBalance: val('rothBalance'),
        taxableBalance: val('taxableBalance'),
        pctTraditional: val('pctTraditional'),
        pctRoth: val('pctRoth'),
        pctTaxable: val('pctTaxable')
    };
    
    fetch('/api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'rmd-impact',
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
    fetch('/api/load_scenarios.php?calculator_type=rmd-impact')
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
                    fetch('/api/delete_scenario.php', {
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
                if (typeof toggleSpouseAge === 'function') toggleSpouseAge();
                if (typeof toggleWithdrawalFields === 'function') toggleWithdrawalFields();
                if (typeof toggleWithdrawalStartMode === 'function') toggleWithdrawalStartMode();
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    });
}

function scenarioToProjectionData(s) {
    const d = s.data || {};
    return {
        currentAge: parseInt(d.currentAge, 10),
        accountBalance: parseFloat(d.accountBalance) || 0,
        growthRate: parseFloat(d.growthRate) || 0,
        socialSecurity: parseFloat(d.socialSecurity) || 0,
        pension: parseFloat(d.pension) || 0,
        otherIncome: parseFloat(d.otherIncome) || 0,
        filingStatus: d.filingStatus || 'single',
        useStandardDeduction: d.standardDeduction === 'yes',
        isSpouseBeneficiary: d.spouseBeneficiary === 'yes',
        spouseAge: d.spouseBeneficiary === 'yes' && d.spouseAge ? parseInt(d.spouseAge, 10) : null,
        enableWithdrawals: isTruthyFlag(d.enableWithdrawals),
        withdrawalAmount: parseFloat(d.withdrawalAmount) || 0,
        withdrawalStartMode: d.withdrawalStartMode || 'age',
        withdrawalStartAge: d.withdrawalStartAge ? parseInt(d.withdrawalStartAge, 10) : null,
        withdrawalStartMonth: d.withdrawalStartMonth ? parseInt(d.withdrawalStartMonth, 10) : 1,
        withdrawalStartYear: d.withdrawalStartYear ? parseInt(d.withdrawalStartYear, 10) : null,
        planStartYear: d.planStartYear ? parseInt(d.planStartYear, 10) : getPlanStartYear(),
        withdrawalEndAge: d.withdrawalEndAge ? parseInt(d.withdrawalEndAge, 10) : 100,
        withdrawalInflation: isTruthyFlag(d.withdrawalInflation),
        withdrawalInflationRate: parseFloat(d.withdrawalInflationRate) || 0,
        withdrawalSource: d.withdrawalSource || 'traditional',
        rothBalance: parseFloat(d.rothBalance) || 0,
        taxableBalance: parseFloat(d.taxableBalance) || 0,
        pctTraditional: d.pctTraditional != null && d.pctTraditional !== '' ? parseFloat(d.pctTraditional) : 100,
        pctRoth: parseFloat(d.pctRoth) || 0,
        pctTaxable: parseFloat(d.pctTaxable) || 0
    };
}

function compareScenarios() {
    if (typeof CompareScenariosModal === 'undefined') {
        alert('Compare feature failed to load. Please refresh the page.');
        return;
    }
    CompareScenariosModal.open('/', 'rmd-impact', function (selected) {
        const data1 = scenarioToProjectionData(selected[0]);
        const data2 = scenarioToProjectionData(selected[1]);
        const results1 = calculateProjection(data1);
        const results2 = calculateProjection(data2);

        // Store comparison context for AI explanations in compare mode
        window.lastRMDCompare = {
            name1: selected[0].name,
            name2: selected[1].name,
            data1,
            data2,
            results1,
            results2
        };

        if (selected.length >= 3) {
            const data3 = scenarioToProjectionData(selected[2]);
            const results3 = calculateProjection(data3);
            showComparisonThree(selected[0].name, selected[1].name, selected[2].name, results1, results2, results3, data1, data2, data3);
        } else {
            showComparison(selected[0].name, selected[1].name, results1, results2, data1, data2);
        }
    }, { maxScenarios: 3 });
}

function showComparison(name1, name2, results1, results2, data1, data2) {
    // Create comparison container
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') {
        resultsDiv.style.display = 'block';
    }
    
    // Scroll to results
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
    
    // Create comparison HTML
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 10px;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>Age: ${data1.currentAge} | Balance: $${data1.accountBalance.toLocaleString()}</div>
                        <div>Growth: ${data1.growthRate}% | SS: $${data1.socialSecurity.toLocaleString()}</div>
                    </div>
                </div>
                <div>
                    <h3 style="color: #e53e3e; margin-bottom: 10px;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        <div>Age: ${data2.currentAge} | Balance: $${data2.accountBalance.toLocaleString()}</div>
                        <div>Growth: ${data2.growthRate}% | SS: $${data2.socialSecurity.toLocaleString()}</div>
                    </div>
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px;">Key Differences</h3>
            <div id="comparisonTable"></div>
        </div>
    `;
    
    // Insert comparison at top of results
    const resultsContent = resultsDiv.innerHTML;
    resultsDiv.innerHTML = comparisonHTML + resultsContent;
    
    // Build comparison table
    const firstRMD1 = results1.find(r => r.rmdAmount > 0);
    const firstRMD2 = results2.find(r => r.rmdAmount > 0);
    const age80_1 = results1.find(r => r.age === 80) || firstRMD1;
    const age80_2 = results2.find(r => r.age === 80) || firstRMD2;
    const age90_1 = results1.find(r => r.age === 90) || age80_1;
    const age90_2 = results2.find(r => r.age === 90) || age80_2;
    const peakTax1 = Math.max(...results1.map(r => r.taxBracket));
    const peakTax2 = Math.max(...results2.map(r => r.taxBracket));
    const peakEff1 = Math.max(...results1.map(r => r.effectiveTaxRate));
    const peakEff2 = Math.max(...results2.map(r => r.effectiveTaxRate));
    
    const tableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f59e0b; color: white;">
                    <th style="padding: 10px; text-align: left;">Metric</th>
                    <th style="padding: 10px; text-align: right;">${name1}</th>
                    <th style="padding: 10px; text-align: right;">${name2}</th>
                    <th style="padding: 10px; text-align: right;">Difference</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">First RMD (Age 73)</td>
                    <td style="padding: 8px; text-align: right;">$${firstRMD1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${firstRMD2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${firstRMD2.rmdAmount - firstRMD1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(firstRMD2.rmdAmount - firstRMD1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">RMD at Age 80</td>
                    <td style="padding: 8px; text-align: right;">$${age80_1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${age80_2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${age80_2.rmdAmount - age80_1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(age80_2.rmdAmount - age80_1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">RMD at Age 90</td>
                    <td style="padding: 8px; text-align: right;">$${age90_1.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right;">$${age90_2.rmdAmount.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${age90_2.rmdAmount - age90_1.rmdAmount >= 0 ? '#e53e3e' : '#10b981'};">
                        $${(age90_2.rmdAmount - age90_1.rmdAmount).toLocaleString(undefined, {maximumFractionDigits: 0})}
                    </td>
                </tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">Peak Marginal Bracket</td>
                    <td style="padding: 8px; text-align: right;">${peakTax1}%</td>
                    <td style="padding: 8px; text-align: right;">${peakTax2}%</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${peakTax2 - peakTax1 >= 0 ? '#e53e3e' : '#10b981'};">
                        ${peakTax2 - peakTax1 >= 0 ? '+' : ''}${peakTax2 - peakTax1}%
                    </td>
                </tr>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: 600;">Peak Effective Federal Rate</td>
                    <td style="padding: 8px; text-align: right;">${formatPercent(peakEff1)}</td>
                    <td style="padding: 8px; text-align: right;">${formatPercent(peakEff2)}</td>
                    <td style="padding: 8px; text-align: right; font-weight: 600; color: ${peakEff2 - peakEff1 >= 0 ? '#e53e3e' : '#10b981'};">
                        ${peakEff2 - peakEff1 >= 0 ? '+' : ''}${formatPercent(peakEff2 - peakEff1)}
                    </td>
                </tr>
            </tbody>
        </table>
    `;
    
    document.getElementById('comparisonTable').innerHTML = tableHTML;

    // Re-bind Explain button because innerHTML replacement recreates the DOM node
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) {
        explainBtn.addEventListener('click', explainResults);
    }
}

function showComparisonThree(name1, name2, name3, results1, results2, results3, data1, data2, data3) {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });

    const firstRMD = (r) => r.find(x => x.rmdAmount > 0);
    const atAge = (r, age) => r.find(x => x.age === age) || firstRMD(r);
    const peakTax = (r) => Math.max(...r.map(x => x.taxBracket));
    const fmt = (n) => (n == null ? '—' : '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }));
    const pct = (n) => (n == null ? '—' : n + '%');

    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison (3 scenarios)</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div><h3 style="color: #667eea; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name1)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data1.currentAge} | $${data1.accountBalance.toLocaleString()} | ${data1.growthRate}%</div></div>
                <div><h3 style="color: #e53e3e; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name2)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data2.currentAge} | $${data2.accountBalance.toLocaleString()} | ${data2.growthRate}%</div></div>
                <div><h3 style="color: #059669; margin-bottom: 8px; font-size: 1rem;">${escapeHtml(name3)}</h3><div style="font-size: 0.85em; color: #666;">Age ${data3.currentAge} | $${data3.accountBalance.toLocaleString()} | ${data3.growthRate}%</div></div>
            </div>
            <h3 style="margin-bottom: 10px;">Key metrics</h3>
            <div id="comparisonTableThree"></div>
        </div>
    `;
    const resultsContent = resultsDiv.innerHTML;
    resultsDiv.innerHTML = comparisonHTML + resultsContent;

    const r80_1 = atAge(results1, 80);
    const r80_2 = atAge(results2, 80);
    const r80_3 = atAge(results3, 80);
    const r90_1 = atAge(results1, 90);
    const r90_2 = atAge(results2, 90);
    const r90_3 = atAge(results3, 90);
    const first1 = firstRMD(results1);
    const first2 = firstRMD(results2);
    const first3 = firstRMD(results3);

    const tableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f59e0b; color: white;">
                    <th style="padding: 10px; text-align: left;">Metric</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name1)}</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name2)}</th>
                    <th style="padding: 10px; text-align: right;">${escapeHtml(name3)}</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">First RMD (Age 73)</td><td style="padding: 8px; text-align: right;">${fmt(first1 && first1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(first2 && first2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(first3 && first3.rmdAmount)}</td></tr>
                <tr style="background: #f9fafb; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">RMD at Age 80</td><td style="padding: 8px; text-align: right;">${fmt(r80_1 && r80_1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r80_2 && r80_2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r80_3 && r80_3.rmdAmount)}</td></tr>
                <tr style="background: #fff; border-bottom: 1px solid #ddd;"><td style="padding: 8px; font-weight: 600;">RMD at Age 90</td><td style="padding: 8px; text-align: right;">${fmt(r90_1 && r90_1.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r90_2 && r90_2.rmdAmount)}</td><td style="padding: 8px; text-align: right;">${fmt(r90_3 && r90_3.rmdAmount)}</td></tr>
                <tr style="background: #f9fafb;"><td style="padding: 8px; font-weight: 600;">Peak Tax Bracket</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results1))}</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results2))}</td><td style="padding: 8px; text-align: right;">${pct(peakTax(results3))}</td></tr>
            </tbody>
        </table>
    `;
    const el = document.getElementById('comparisonTableThree');
    if (el) el.innerHTML = tableHTML;

    // Re-bind Explain button after DOM replacement in three-scenario comparison
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) {
        explainBtn.addEventListener('click', explainResults);
    }
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    let summary = '';

    // If a recent comparison exists, build a true comparison explanation
    if (window.lastRMDCompare && window.lastRMDCompare.results1 && window.lastRMDCompare.results2) {
        const c = window.lastRMDCompare;
        const r1 = c.results1;
        const r2 = c.results2;
        const d1 = c.data1;
        const d2 = c.data2;

        const first1 = r1.find(r => r.rmdAmount > 0) || r1[r1.length - 1];
        const first2 = r2.find(r => r.rmdAmount > 0) || r2[r2.length - 1];
        const age80_1 = r1.find(r => r.age === 80) || first1;
        const age80_2 = r2.find(r => r.age === 80) || first2;
        const age90_1 = r1.find(r => r.age === 90) || age80_1;
        const age90_2 = r2.find(r => r.age === 90) || age80_2;
        const peakTax1 = Math.max(...r1.map(r => r.taxBracket));
        const peakTax2 = Math.max(...r2.map(r => r.taxBracket));

        summary += 'RMD Impact Comparison for two scenarios.\n\n';
        summary += 'Scenario 1 – ' + c.name1 + ': starting balance ' + formatCurrency(d1.accountBalance) +
                   ', current age ' + d1.currentAge + ', expected growth ' + d1.growthRate + '%. ';
        summary += 'Scenario 2 – ' + c.name2 + ': starting balance ' + formatCurrency(d2.accountBalance) +
                   ', current age ' + d2.currentAge + ', expected growth ' + d2.growthRate + '%. ';
        summary += 'Both scenarios assume Social Security of ' + formatCurrency(d1.socialSecurity) + ' per year and the same tax filing details.\n\n';

        summary += 'At age 73, the first RMD in Scenario 1 is ' + formatCurrency(first1.rmdAmount) +
                   ' versus ' + formatCurrency(first2.rmdAmount) + ' in Scenario 2. ';
        summary += 'By age 80 the RMDs grow to ' + formatCurrency(age80_1.rmdAmount) + ' vs ' +
                   formatCurrency(age80_2.rmdAmount) + ', and by age 90 they reach ' +
                   formatCurrency(age90_1.rmdAmount) + ' vs ' + formatCurrency(age90_2.rmdAmount) + '. ';
        summary += 'Peak estimated tax brackets are around ' + peakTax1 + '% for Scenario 1 and ' +
                   peakTax2 + '% for Scenario 2.\n\n';

        summary += 'In plain terms, the larger-balance scenario produces higher RMDs and slightly higher peak tax brackets, ';
        summary += 'while the smaller-balance scenario keeps required withdrawals and taxable income lower. ';
        summary += 'The trade-off is that higher RMDs mean more taxable income but also more money coming out of tax-deferred accounts each year.\n';
    } else {
        // Fallback: single-scenario explanation (original behavior)
        const results = window.lastRMDResult;
        const data = window.lastRMDData;
        if (!results || !data) {
            alert('Please run "Calculate RMD Impact" first to see results.');
            return;
        }
        const firstRMD = results.find(r => r.rmdAmount > 0) || results[results.length - 1];
        const age80Data = results.find(r => r.age === 80) || firstRMD;
        const age90Data = results.find(r => r.age === 90) || age80Data;

        summary += 'RMD Impact Projection.\n\n';
        summary += 'Current age: ' + data.currentAge + '. Tax-deferred account balance: ' + formatCurrency(data.accountBalance) + '. Expected growth rate: ' + data.growthRate + '%.\n\n';
        summary += 'Other income: Social Security ' + formatCurrency(data.socialSecurity) + '/year, Pension ' + formatCurrency(data.pension) + '/year, Other ' + formatCurrency(data.otherIncome) + '/year. ';
        summary += 'Filing status: ' + data.filingStatus + '. ' + (data.useStandardDeduction ? 'Standard deduction.' : 'Itemizing.') + '\n\n';
        if (data.isSpouseBeneficiary && data.spouseAge) {
            summary += 'Spouse is sole beneficiary, age ' + data.spouseAge + '. ';
        }
        if (isWithdrawalsEnabled(data)) {
            const wc = getWithdrawalConfig(data);
            summary += 'Planned annual withdrawals of ' + formatCurrency(data.withdrawalAmount) +
                (data.withdrawalInflation ? ' (inflation-adjusted at ' + data.withdrawalInflationRate + '%)' : '') +
                ' beginning ' + wc.startLabel + ' until age ' + data.withdrawalEndAge +
                ', sourced from: ' + data.withdrawalSource +
                (data.withdrawalSource === 'combination' ? ' (' + data.pctTraditional + '% traditional / ' + data.pctRoth + '% Roth / ' + data.pctTaxable + '% taxable)' : '') + '. ';
            if (data.rothBalance) summary += 'Roth balance: ' + formatCurrency(data.rothBalance) + '. ';
            if (data.taxableBalance) summary += 'Taxable brokerage balance: ' + formatCurrency(data.taxableBalance) + '. ';
            summary += 'Traditional withdrawals count toward RMDs once they begin. ';
        }
        const peakTax = Math.max(...results.map(r => r.taxBracket));
        const peakEffective = Math.max(...results.map(r => r.effectiveTaxRate));

        summary += 'First RMD at age 73: ' + formatCurrency(firstRMD.rmdAmount) + '. ';
        summary += 'RMD at age 80: ' + formatCurrency(age80Data.rmdAmount) + '. ';
        summary += 'RMD at age 90: ' + formatCurrency(age90Data.rmdAmount) + '. ';
        summary += 'Peak estimated marginal tax bracket: ' + peakTax + '%. ';
        summary += 'Peak estimated effective federal tax rate: ' + formatPercent(peakEffective) + '.';
    }

    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Loading…';
    }

    const apiUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            calculator_type: 'rmd-impact',
            results_summary: summary
        })
    })
    .then(r => r.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let resp;
        try { resp = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Try logging out and back in, or check if the AI Explain feature is configured.');
        }
        if (resp.error) throw new Error(resp.error);
        showExplainModal(resp.explanation, { calculatorType: 'rmd-impact', resultsSummary: summary });
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Explain results: ' + err.message);
    });
}


function downloadPDF() {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') {
        alert('Please calculate your RMD impact first before downloading the PDF.');
        return;
    }

    // Capture the chart as an image
    const canvas = document.getElementById('rmdChart');
    const chartImage = canvas.toDataURL('image/png');

    const data = gatherRMDFormData();
    const results = getRMDCalculationResults(data);
    const summary = buildRMDSummary(results);
    const projStart = getProjectionStartAge(data);
    const projections = results.filter(r => r.age >= projStart);

    fetch('/api/generate_rmd_pdf.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            summary: summary,
            projections: projections,
            chartImage: chartImage
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'PDF generation failed';
                try {
                    const j = JSON.parse(t);
                    if (j.error) msg = j.error;
                } catch (_) {}
                throw new Error(msg);
            });
        }
        const ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) {
            throw new Error('Server did not return a PDF. You may need to log in again or refresh.');
        }
        return response.blob();
    })
    .then(blob => {
        if (blob.type && blob.type.indexOf('pdf') === -1) {
            throw new Error('Download was not a PDF. Try again or check your login.');
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'RMD_Analysis_' + new Date().toISOString().split('T')[0] + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error generating PDF: ' + error.message);
    });
}

function downloadCSV() {
    // Check if results are displayed (check for summary cards or chart)
    const summaryCards = document.querySelectorAll('.summary-value');
    const chartCanvas = document.getElementById('rmdChart');
    if (summaryCards.length === 0 && (!chartCanvas || !myChart)) {
        alert('Please calculate your RMD projection first.');
        return;
    }

    // Gather form data (same as PDF)
    const data = gatherRMDFormData();
    const results = getRMDCalculationResults(data);
    const summary = buildRMDSummary(results);
    const projStart = getProjectionStartAge(data);
    const projections = results.filter(r => r.age >= projStart);

    fetch('/api/export_rmd_csv.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            summary: summary,
            projections: projections
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'CSV export failed';
                try {
                    const j = JSON.parse(t);
                    if (j.error) msg = j.error;
                } catch (_) {}
                throw new Error(msg);
            });
        }
        const ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) {
            throw new Error('Server did not return a CSV. You may need to log in again or refresh.');
        }
        return response.blob();
    })
    .then(blob => {
        if (blob.type && blob.type.indexOf('csv') === -1 && blob.type.indexOf('text') === -1) {
            throw new Error('Download was not a CSV. Try again or check your login.');
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'RMD_Analysis_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error exporting CSV: ' + error.message);
    });
}

function downloadCalendar() {
    // Check if results are displayed (check for summary cards or chart)
    const summaryCards = document.querySelectorAll('.summary-value');
    const chartCanvas = document.getElementById('rmdChart');
    if (summaryCards.length === 0 && (!chartCanvas || !myChart)) {
        alert('Please calculate your RMD projection first.');
        return;
    }

    // Gather form data
    const data = gatherRMDFormData();

    const results = getRMDCalculationResults(data);
    const projStart = getProjectionStartAge(data);
    const projections = results.filter(r => r.age >= projStart);

    fetch('/api/generate_rmd_calendar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ...data,
            projections: projections
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(t => {
                let msg = 'Calendar generation failed';
                try {
                    const j = JSON.parse(t);
                    if (j.error) msg = j.error;
                } catch (_) {}
                throw new Error(msg);
            });
        }
        const ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) {
            throw new Error('Server did not return a PDF. You may need to log in again or refresh.');
        }
        return response.blob();
    })
    .then(blob => {
        if (blob.type && blob.type.indexOf('pdf') === -1) {
            throw new Error('Download was not a PDF. Try again or check your login.');
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'RMD_Calendar_' + new Date().toISOString().split('T')[0] + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        alert('Error generating calendar: ' + error.message);
    });
}
