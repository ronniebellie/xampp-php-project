/** Finite, level survivor payments valued at the annuitant's death. */
(function (root) {
    'use strict';
    function calculate(input) {
        const { singleLifeMonthly, jointLifeMonthly, survivorYears, survivorPercent, discountRate } = input;
        if (![singleLifeMonthly, jointLifeMonthly, survivorYears, survivorPercent, discountRate].every(Number.isFinite) ||
            singleLifeMonthly < 0 || jointLifeMonthly < 0 || !Number.isInteger(survivorYears) || survivorYears < 1 || survivorYears > 40 ||
            survivorPercent < 0 || survivorPercent > 100 || discountRate < 0 || discountRate > 20) {
            throw new RangeError('Enter nonnegative pensions, 1–40 whole survivor years, 0–100% continuation and a 0–20% discount rate.');
        }
        const survivorMonthly = jointLifeMonthly * survivorPercent / 100;
        const months = survivorYears * 12;
        const monthlyRate = Math.expm1(Math.log1p(discountRate / 100) / 12);
        const presentValue = monthlyRate === 0 ? survivorMonthly * months :
            survivorMonthly * -Math.expm1(-months * Math.log1p(monthlyRate)) / monthlyRate;
        return { optionCostMonthly: singleLifeMonthly - jointLifeMonthly, survivorMonthly,
            totalPayments: survivorMonthly * months, presentValue };
    }
    const api = { calculate };
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.RBSurvivorGap = api;
})(typeof window !== 'undefined' ? window : this);
