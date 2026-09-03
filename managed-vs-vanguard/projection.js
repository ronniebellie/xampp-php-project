/*
 * Pure pre-tax projection engine for Managed Portfolio vs Vanguard.
 * Contributions are deposited at the end of the selected period.
 */
(function(root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    root.MVProjection = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function() {
    'use strict';

    const FREQUENCY_MONTHS = {
        monthly: 1,
        quarterly: 3,
        annual: 12
    };

    function normalizeInputs(input) {
        const source = input || {};
        const contributionFrequency = FREQUENCY_MONTHS[source.contributionFrequency]
            ? source.contributionFrequency
            : 'monthly';

        return {
            initialBalance: Number(source.initialBalance),
            annualReturnPct: Number(source.annualReturnPct),
            annualFeePct: Number(source.annualFeePct),
            years: Number(source.years),
            contributionAmount: source.contributionAmount === undefined || source.contributionAmount === null || source.contributionAmount === ''
                ? 0
                : Number(source.contributionAmount),
            contributionFrequency: contributionFrequency
        };
    }

    function projectPortfolio(input) {
        const values = normalizeInputs(input);
        const monthsPerContribution = FREQUENCY_MONTHS[values.contributionFrequency];
        const totalMonths = Math.round(values.years * 12);
        const monthlyReturn = Math.pow(1 + values.annualReturnPct / 100, 1 / 12) - 1;
        const monthlyFeeRate = 1 - Math.pow(1 - values.annualFeePct / 100, 1 / 12);
        let balance = values.initialBalance;
        let cumulativeContributions = 0;
        let cumulativeFees = 0;
        const yearlyData = [{
            year: 0,
            startingBalance: balance,
            contributions: 0,
            grossGrowth: 0,
            annualFees: 0,
            cumulativeContributions: 0,
            cumulativeFees: 0,
            endingBalance: balance,
            // Existing consumers use these aliases.
            balance: balance,
            fee: 0,
            totalFees: 0
        }];

        for (let month = 1; month <= totalMonths; month++) {
            const yearIndex = Math.ceil(month / 12);
            let row = yearlyData[yearIndex];
            if (!row) {
                row = {
                    year: yearIndex,
                    startingBalance: balance,
                    contributions: 0,
                    grossGrowth: 0,
                    annualFees: 0,
                    cumulativeContributions: cumulativeContributions,
                    cumulativeFees: cumulativeFees,
                    endingBalance: balance,
                    balance: balance,
                    fee: 0,
                    totalFees: cumulativeFees
                };
                yearlyData.push(row);
            }

            const grossGrowth = balance * monthlyReturn;
            const balanceAfterGrowth = balance + grossGrowth;
            const fee = balanceAfterGrowth * monthlyFeeRate;
            balance = balanceAfterGrowth - fee;

            // End-of-period deposit: this month's contribution receives no
            // return or fee impact until the following monthly period.
            const contribution = month % monthsPerContribution === 0 ? values.contributionAmount : 0;
            balance += contribution;
            cumulativeContributions += contribution;
            cumulativeFees += fee;

            row.contributions += contribution;
            row.grossGrowth += grossGrowth;
            row.annualFees += fee;
            row.cumulativeContributions = cumulativeContributions;
            row.cumulativeFees = cumulativeFees;
            row.endingBalance = balance;
            row.balance = balance;
            row.fee = row.annualFees;
            row.totalFees = cumulativeFees;
        }

        return {
            inputs: values,
            yearlyData: yearlyData,
            finalBalance: balance,
            cumulativeContributions: cumulativeContributions,
            cumulativeFees: cumulativeFees,
            totalInvestedCapital: values.initialBalance + cumulativeContributions
        };
    }

    return {
        FREQUENCY_MONTHS: FREQUENCY_MONTHS,
        normalizeInputs: normalizeInputs,
        projectPortfolio: projectPortfolio
    };
});
