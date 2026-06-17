/**
 * Shared retirement finance helpers (SS FRA, benefit adjustments, growth projections).
 * Used by Retirement Plan Builder and available for other calculators.
 */
(function (global) {
  'use strict';

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
    return { years: 67, months: 0 };
  }

  function fraAgeFromBirthYear(birthYear) {
    var fra = getFRA(birthYear);
    return fra.years + fra.months / 12;
  }

  function calculateEarlyReduction(monthsEarly) {
    var first36 = Math.min(monthsEarly, 36);
    var beyond36 = Math.max(0, monthsEarly - 36);
    return 1 - (first36 * (5 / 9) / 100) - (beyond36 * (5 / 12) / 100);
  }

  function calculateDelayCredit(monthsDelayed) {
    return 1 + (monthsDelayed * (2 / 3) / 100);
  }

  function calculateMonthlyBenefit(pia, birthYear, claimAge) {
    var fra = getFRA(birthYear);
    var fraInMonths = fra.years * 12 + fra.months;
    var cappedClaimMonths = Math.min(claimAge * 12, 70 * 12);
    var monthsDiff = cappedClaimMonths - fraInMonths;
    var factor = 1;
    if (monthsDiff < 0) factor = calculateEarlyReduction(Math.abs(monthsDiff));
    else if (monthsDiff > 0) factor = calculateDelayCredit(monthsDiff);
    return pia * factor;
  }

  function projectBalance(startBalance, annualContribution, annualReturnPct, years) {
    var r = (annualReturnPct || 0) / 100;
    var bal = startBalance;
    for (var i = 0; i < years; i++) {
      bal = bal * (1 + r) + annualContribution;
    }
    return bal;
  }

  function formatCurrency(amount) {
    if (!isFinite(amount)) return '—';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
  }

  global.RBFinance = {
    getFRA: getFRA,
    fraAgeFromBirthYear: fraAgeFromBirthYear,
    calculateMonthlyBenefit: calculateMonthlyBenefit,
    projectBalance: projectBalance,
    formatCurrency: formatCurrency,
    clamp: clamp
  };
})(typeof window !== 'undefined' ? window : this);
