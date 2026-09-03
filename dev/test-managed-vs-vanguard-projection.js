'use strict';

const assert = require('assert');
const { projectPortfolio } = require('../managed-vs-vanguard/projection.js');

function close(actual, expected, message, tolerance = 1e-8) {
    assert(Math.abs(actual - expected) <= tolerance, `${message}: expected ${expected}, got ${actual}`);
}

function run(name, fn) {
    try {
        fn();
        console.log(`PASS ${name}`);
        return true;
    } catch (error) {
        console.error(`FAIL ${name}: ${error.message}`);
        return false;
    }
}

const base = {
    initialBalance: 100000,
    annualReturnPct: 0,
    annualFeePct: 0,
    years: 1,
    contributionAmount: 0,
    contributionFrequency: 'monthly'
};

const checks = [
    run('zero contribution preserves starting balance with zero return and fee', () => {
        const result = projectPortfolio(base);
        close(result.finalBalance, 100000, 'Final balance');
        close(result.cumulativeContributions, 0, 'Contributions');
        close(result.cumulativeFees, 0, 'Fees');
    }),
    run('final balance is post-fee rather than the previous pre-fee display', () => {
        const result = projectPortfolio({ ...base, annualFeePct: 1 });
        close(result.finalBalance, 99000, 'Post-fee final balance', 1e-6);
        close(result.yearlyData[1].balance, result.finalBalance, 'Annual row uses ending balance');
        close(result.cumulativeFees, 1000, 'Annual fee total', 1e-6);
    }),
    run('monthly deposits occur after each monthly return and fee', () => {
        const result = projectPortfolio({ ...base, initialBalance: 0, contributionAmount: 100, contributionFrequency: 'monthly' });
        close(result.cumulativeContributions, 1200, 'Twelve deposits');
        close(result.finalBalance, 1200, 'End-of-month zero-return deposits');
        close(result.yearlyData[1].contributions, 1200, 'Annual contribution total');
    }),
    run('quarterly deposits occur four times per year', () => {
        const result = projectPortfolio({ ...base, initialBalance: 0, contributionAmount: 100, contributionFrequency: 'quarterly' });
        close(result.cumulativeContributions, 400, 'Four deposits');
        close(result.finalBalance, 400, 'End-of-quarter zero-return deposits');
    }),
    run('annual deposits occur once at year end', () => {
        const result = projectPortfolio({ ...base, initialBalance: 0, contributionAmount: 100, contributionFrequency: 'annual' });
        close(result.cumulativeContributions, 100, 'One deposit');
        close(result.finalBalance, 100, 'End-of-year deposit');
    }),
    run('end-of-period contribution does not receive that period return', () => {
        const result = projectPortfolio({ ...base, initialBalance: 0, annualReturnPct: 12, contributionAmount: 1200, contributionFrequency: 'annual' });
        close(result.finalBalance, 1200, 'Year-end contribution remains ungrown');
    }),
    run('higher fee produces lower final balance and higher cumulative fees', () => {
        const lowFee = projectPortfolio({ ...base, annualReturnPct: 8, annualFeePct: 0.04, years: 20, contributionAmount: 500 });
        const highFee = projectPortfolio({ ...base, annualReturnPct: 8, annualFeePct: 1, years: 20, contributionAmount: 500 });
        assert(highFee.finalBalance < lowFee.finalBalance, 'Higher fee should reduce final balance');
        assert(highFee.cumulativeFees > lowFee.cumulativeFees, 'Higher fee should increase cumulative fees');
    }),
    run('legacy missing contribution fields default to zero and monthly', () => {
        const result = projectPortfolio({ initialBalance: 1000, annualReturnPct: 0, annualFeePct: 0, years: 1 });
        close(result.cumulativeContributions, 0, 'Legacy contributions default to zero');
        assert.strictEqual(result.inputs.contributionFrequency, 'monthly');
    })
];

if (checks.every(Boolean)) {
    console.log(`Managed vs Vanguard projection tests passed (${checks.length} checks).`);
    process.exit(0);
}

process.exit(1);
