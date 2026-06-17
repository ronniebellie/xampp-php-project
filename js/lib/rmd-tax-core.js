/**
 * Shared RMD divisors and simplified federal tax estimates.
 * Sourced from rmd-impact and roth-conv calculator logic.
 */
(function (global) {
  'use strict';

  var RMD_START_AGE = 73;

  var rmdDivisors = {
    73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
    80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
    87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
    94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4
  };

  var jointLifeExpectancy = {
    '73_63': 23.1, '73_62': 23.3, '73_61': 23.6, '73_60': 23.8, '73_59': 24.0,
    '74_64': 22.3, '74_63': 22.5, '74_62': 22.7, '75_65': 21.5, '75_64': 21.7,
    '80_70': 17.5, '80_69': 17.7, '85_75': 14.2, '85_74': 14.4, '90_80': 11.7,
    '95_85': 9.6, '100_90': 8.1
  };

  var taxBrackets2026 = {
    single: [
      { min: 0, max: 11925, rate: 0.10 },
      { min: 11925, max: 48475, rate: 0.12 },
      { min: 48475, max: 103350, rate: 0.22 },
      { min: 103350, max: 197300, rate: 0.24 },
      { min: 197300, max: 250525, rate: 0.32 },
      { min: 250525, max: 626350, rate: 0.35 },
      { min: 626350, max: Infinity, rate: 0.37 }
    ],
    married: [
      { min: 0, max: 23850, rate: 0.10 },
      { min: 23850, max: 96950, rate: 0.12 },
      { min: 96950, max: 206700, rate: 0.22 },
      { min: 206700, max: 394600, rate: 0.24 },
      { min: 394600, max: 501050, rate: 0.32 },
      { min: 501050, max: 751600, rate: 0.35 },
      { min: 751600, max: Infinity, rate: 0.37 }
    ],
    hoh: [
      { min: 0, max: 17000, rate: 0.10 },
      { min: 17000, max: 64850, rate: 0.12 },
      { min: 64850, max: 103350, rate: 0.22 },
      { min: 103350, max: 197300, rate: 0.24 },
      { min: 197300, max: 250500, rate: 0.32 },
      { min: 250500, max: 626350, rate: 0.35 },
      { min: 626350, max: Infinity, rate: 0.37 }
    ]
  };

  var standardDeductions2026 = {
    single: 16100,
    married: 32200,
    hoh: 24150
  };

  function normalizeFilingStatus(status) {
    if (status === 'married' || status === 'married_filing_jointly') return 'married';
    if (status === 'hoh' || status === 'head') return 'hoh';
    return 'single';
  }

  function getRMDDivisor(ownerAge, isSpouseBeneficiary, spouseAge) {
    if (isSpouseBeneficiary && spouseAge && (ownerAge - spouseAge) > 10) {
      var key = ownerAge + '_' + spouseAge;
      if (jointLifeExpectancy[key]) return jointLifeExpectancy[key];
    }
    return rmdDivisors[ownerAge] || 6.4;
  }

  function calculateRMD(age, taxDeferredBalance, isSpouseBeneficiary, spouseAge) {
    if (age < RMD_START_AGE || taxDeferredBalance <= 0) return 0;
    var divisor = getRMDDivisor(age, isSpouseBeneficiary, spouseAge);
    return taxDeferredBalance / divisor;
  }

  function calculateFederalTax(taxableIncome, filingStatus) {
    var status = normalizeFilingStatus(filingStatus);
    var brackets = taxBrackets2026[status];
    var tax = 0;
    for (var i = 0; i < brackets.length; i++) {
      var bracket = brackets[i];
      var taxableInBracket = Math.min(
        Math.max(0, taxableIncome - bracket.min),
        bracket.max - bracket.min
      );
      tax += taxableInBracket * bracket.rate;
      if (taxableIncome <= bracket.max) break;
    }
    return tax;
  }

  function getMarginalRate(taxableIncome, filingStatus) {
    var status = normalizeFilingStatus(filingStatus);
    var brackets = taxBrackets2026[status];
    for (var i = 0; i < brackets.length; i++) {
      if (taxableIncome >= brackets[i].min && taxableIncome < brackets[i].max) {
        return brackets[i].rate;
      }
    }
    return brackets[brackets.length - 1].rate;
  }

  /**
   * Rough taxable income: portfolio withdrawals + other income + 50% of Social Security.
   */
  function estimateTaxableIncome(portfolioWithdrawal, socialSecurity, otherIncome, filingStatus, useStandardDeduction) {
    var gross = (portfolioWithdrawal || 0) + (otherIncome || 0) + (socialSecurity || 0) * 0.5;
    var deduction = useStandardDeduction !== false
      ? standardDeductions2026[normalizeFilingStatus(filingStatus)]
      : 0;
    return Math.max(0, gross - deduction);
  }

  global.RBTaxRmd = {
    RMD_START_AGE: RMD_START_AGE,
    getRMDDivisor: getRMDDivisor,
    calculateRMD: calculateRMD,
    calculateFederalTax: calculateFederalTax,
    getMarginalRate: getMarginalRate,
    estimateTaxableIncome: estimateTaxableIncome,
    normalizeFilingStatus: normalizeFilingStatus
  };
})(typeof window !== 'undefined' ? window : this);
