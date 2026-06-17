// Roth Conversion Calculator Logic

// 2026 Federal Tax Brackets (projected based on inflation adjustments)
const TAX_BRACKETS_2026 = {
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
  married_separate: [
    { min: 0, max: 11925, rate: 0.10 },
    { min: 11925, max: 48475, rate: 0.12 },
    { min: 48475, max: 103350, rate: 0.22 },
    { min: 103350, max: 197300, rate: 0.24 },
    { min: 197300, max: 250525, rate: 0.32 },
    { min: 250525, max: 375800, rate: 0.35 },
    { min: 375800, max: Infinity, rate: 0.37 }
  ],
  head: [
    { min: 0, max: 17000, rate: 0.10 },
    { min: 17000, max: 64850, rate: 0.12 },
    { min: 64850, max: 103350, rate: 0.22 },
    { min: 103350, max: 197300, rate: 0.24 },
    { min: 197300, max: 250500, rate: 0.32 },
    { min: 250500, max: 626350, rate: 0.35 },
    { min: 626350, max: Infinity, rate: 0.37 }
  ]
};

// Standard Deductions 2026
const STANDARD_DEDUCTION_2026 = {
  single: 16100,
  married: 32200,
  married_separate: 16100,
  head: 24150
};

// Enhanced deduction for seniors (age 65+). This calculator uses the commonly-cited $6,000 per eligible person.
// (If future rules phase this out by income, we can add that later.)
const SENIOR_DEDUCTION_65PLUS = 6000;

// Medicare IRMAA — 2026 Part B + Part D monthly surcharge per person above standard premium.
// Thresholds are based on MAGI from two years prior (CMS lookback). Base brackets = 2026 premiums / 2024 MAGI.
const IRMAA_BASE_YEAR = 2026;
const IRMAA_LOOKBACK_YEARS = 2;
const IRMAA_TIERS_2026 = {
  married: [
    { maxMagi: 218000, monthlySurcharge: 0 },
    { maxMagi: 274000, monthlySurcharge: 95.70 },
    { maxMagi: 342000, monthlySurcharge: 240.40 },
    { maxMagi: 410000, monthlySurcharge: 385.00 },
    { maxMagi: 749999.99, monthlySurcharge: 529.60 },
    { maxMagi: Infinity, monthlySurcharge: 578.00 }
  ],
  single: [
    { maxMagi: 109000, monthlySurcharge: 0 },
    { maxMagi: 137000, monthlySurcharge: 95.70 },
    { maxMagi: 171000, monthlySurcharge: 240.40 },
    { maxMagi: 205000, monthlySurcharge: 385.00 },
    { maxMagi: 499999.99, monthlySurcharge: 529.60 },
    { maxMagi: Infinity, monthlySurcharge: 578.00 }
  ],
  married_separate: [
    { maxMagi: 109000, monthlySurcharge: 0 },
    { maxMagi: 390999.99, monthlySurcharge: 529.60 },
    { maxMagi: Infinity, monthlySurcharge: 578.00 }
  ],
  head: [
    { maxMagi: 109000, monthlySurcharge: 0 },
    { maxMagi: 137000, monthlySurcharge: 95.70 },
    { maxMagi: 171000, monthlySurcharge: 240.40 },
    { maxMagi: 205000, monthlySurcharge: 385.00 },
    { maxMagi: 499999.99, monthlySurcharge: 529.60 },
    { maxMagi: Infinity, monthlySurcharge: 578.00 }
  ]
};

// Net Investment Income Tax (NIIT) — 3.8% on lesser of NII or MAGI above threshold. Thresholds are not inflation-indexed.
const NIIT_RATE = 0.038;
const NIIT_MAGI_THRESHOLDS = {
  single: 200000,
  married: 250000,
  married_separate: 125000,
  head: 200000
};

// RMD Divisors (Uniform Lifetime Table)
const RMD_DIVISORS = {
  73: 26.5, 74: 25.5, 75: 24.6, 76: 23.7, 77: 22.9, 78: 22.0, 79: 21.1,
  80: 20.2, 81: 19.4, 82: 18.5, 83: 17.7, 84: 16.8, 85: 16.0, 86: 15.2,
  87: 14.4, 88: 13.7, 89: 12.9, 90: 12.2, 91: 11.5, 92: 10.8, 93: 10.1,
  94: 9.5, 95: 8.9, 96: 8.4, 97: 7.8, 98: 7.3, 99: 6.8, 100: 6.4,
  101: 6.0, 102: 5.6, 103: 5.2, 104: 4.9, 105: 4.6, 106: 4.3, 107: 4.1,
  108: 3.9, 109: 3.7, 110: 3.5, 111: 3.4, 112: 3.3, 113: 3.1, 114: 3.0,
  115: 2.9, 116: 2.8, 117: 2.7, 118: 2.5, 119: 2.3, 120: 2.0
};

// Calculate Federal Tax
function calculateFederalTax(taxableIncome, filingStatus) {
  const brackets = TAX_BRACKETS_2026[filingStatus];
  let tax = 0;
  
  for (let i = 0; i < brackets.length; i++) {
    const bracket = brackets[i];
    const taxableInBracket = Math.min(
      Math.max(0, taxableIncome - bracket.min),
      bracket.max - bracket.min
    );
    tax += taxableInBracket * bracket.rate;
    
    if (taxableIncome <= bracket.max) break;
  }
  
  return tax;
}

// Calculate marginal tax rate at a given income level
function getMarginalRate(taxableIncome, filingStatus) {
  const brackets = TAX_BRACKETS_2026[filingStatus];
  
  for (let bracket of brackets) {
    if (taxableIncome >= bracket.min && taxableIncome < bracket.max) {
      return bracket.rate;
    }
  }
  
  return brackets[brackets.length - 1].rate;
}

// Calculate RMD for a given age and balance
function calculateRMD(age, balance) {
  if (age < 73) return 0;
  const divisor = RMD_DIVISORS[Math.min(age, 120)];
  return balance / divisor;
}

/** Present value of a tax payment yearsFromStart years in the future. */
function discountTaxToPresent(taxAmount, yearsFromStart, discountRate) {
  if (!discountRate || discountRate <= 0 || yearsFromStart <= 0) return taxAmount;
  return taxAmount / Math.pow(1 + discountRate, yearsFromStart);
}

/** All-in annual tax cost (federal + IRMAA + NIIT). */
function calculateAllInTax(federalTax, irmaa, niit) {
  return (federalTax || 0) + (irmaa || 0) + (niit || 0);
}

/** IRMAA filing status key (head of household uses single brackets). */
function getIrmaaFilingKey(filingStatus) {
  if (filingStatus === 'married') return 'married';
  if (filingStatus === 'married_separate') return 'married_separate';
  return 'single';
}

/** Monthly Part B + Part D IRMAA surcharge per Medicare enrollee. */
function getIrmaaMonthlySurchargePerPerson(magi, filingStatus, premiumYear, inflationRate) {
  const tiers = IRMAA_TIERS_2026[getIrmaaFilingKey(filingStatus)] || IRMAA_TIERS_2026.single;
  const yearsFromBase = premiumYear - IRMAA_BASE_YEAR;
  const factor = Math.pow(1 + (inflationRate || 0), yearsFromBase);

  for (const tier of tiers) {
    const maxMagi = tier.maxMagi === Infinity ? Infinity : tier.maxMagi * factor;
    if (magi <= maxMagi) {
      return tier.monthlySurcharge * factor;
    }
  }
  const last = tiers[tiers.length - 1];
  return last.monthlySurcharge * factor;
}

/** Annual household IRMAA cost for all enrolled Medicare beneficiaries. */
function calculateIrmaaAnnual(magi, filingStatus, medicareEnrollees, premiumYear, inflationRate) {
  if (!medicareEnrollees || medicareEnrollees <= 0) return 0;
  const monthly = getIrmaaMonthlySurchargePerPerson(magi, filingStatus, premiumYear, inflationRate);
  return monthly * 12 * medicareEnrollees;
}

/** MAGI for lookback year — uses projected history or pre-projection income estimate. */
function getMagiForLookbackYear(lookbackYear, magiByYear, fallbackMagi) {
  if (magiByYear[lookbackYear] != null) return magiByYear[lookbackYear];
  const years = Object.keys(magiByYear).map(Number).sort((a, b) => a - b);
  if (!years.length) return fallbackMagi;
  if (lookbackYear < years[0]) return fallbackMagi;
  let best = fallbackMagi;
  for (const y of years) {
    if (y <= lookbackYear) best = magiByYear[y];
    else break;
  }
  return best;
}

/** Count household members on Medicare in a given projection year. */
function getMedicareEnrollees(age, currentAge, spouseAge, filingStatus, medicareStartAge, includeIrmaa) {
  if (!includeIrmaa) return 0;
  let count = 0;
  if (age >= medicareStartAge) count++;
  if (filingStatus === 'married' && spouseAge != null) {
    const spouseAgeNow = spouseAge + (age - currentAge);
    if (spouseAgeNow >= medicareStartAge) count++;
  }
  return count;
}

/** Net Investment Income Tax — 3.8% on min(NII, MAGI − threshold). */
function calculateNIIT(magi, netInvestmentIncome, filingStatus, includeNiit) {
  if (!includeNiit || !netInvestmentIncome || netInvestmentIncome <= 0) return 0;
  const threshold = NIIT_MAGI_THRESHOLDS[filingStatus] ?? NIIT_MAGI_THRESHOLDS.single;
  const magiExcess = Math.max(0, magi - threshold);
  if (magiExcess <= 0) return 0;
  return Math.min(netInvestmentIncome, magiExcess) * NIIT_RATE;
}

/** Investment income (dividends, interest, capital gains) for a projection year. */
function getNetInvestmentIncome(age, retirementAge, investmentIncome, retirementInvestmentIncome) {
  if (age >= retirementAge && retirementInvestmentIncome != null && retirementInvestmentIncome !== '') {
    return parseFloat(retirementInvestmentIncome) || 0;
  }
  return investmentIncome || 0;
}

/** Get current form values as an object (for save/load and runRothAnalysis). */
function getRothFormData() {
  const el = id => document.getElementById(id);
  return {
    currentAge: el('currentAge')?.value,
    spouseAge: el('spouseAge')?.value,
    retirementAge: el('retirementAge')?.value,
    lifeExpectancy: el('lifeExpectancy')?.value,
    filingStatus: el('filingStatus')?.value,
    traditionalIRA: el('traditionalIRA')?.value,
    rothIRA: el('rothIRA')?.value,
    currentIncome: el('currentIncome')?.value,
    retirementIncome: el('retirementIncome')?.value,
    annualPortfolioWithdrawalRate: el('annualPortfolioWithdrawalRate')?.value,
    withdrawalMode: el('withdrawalMode')?.value,
    targetAfterTaxSpending: el('targetAfterTaxSpending')?.value,
    withdrawalOrder: el('withdrawalOrder')?.value,
    conversionAmount: el('conversionAmount')?.value,
    conversionYears: el('conversionYears')?.value,
    returnRate: el('returnRate')?.value,
    inflationRate: el('inflationRate')?.value,
    discountRate: el('discountRate')?.value,
    includeIrmaa: el('includeIrmaa')?.checked,
    medicareStartAge: el('medicareStartAge')?.value,
    taxExemptInterest: el('taxExemptInterest')?.value,
    includeNiit: el('includeNiit')?.checked,
    investmentIncome: el('investmentIncome')?.value,
    retirementInvestmentIncome: el('retirementInvestmentIncome')?.value
  };
}

/** Run Roth analysis from a data object (form or loaded scenario). Returns same shape as displayResults expects. */
function runRothAnalysis(data) {
  const currentAge = parseInt(data.currentAge, 10);
  const spouseAge = data.spouseAge ? parseInt(data.spouseAge, 10) : null;
  const retirementAge = data.retirementAge ? parseInt(data.retirementAge, 10) : currentAge;
  const lifeExpectancy = parseInt(data.lifeExpectancy, 10);
  const filingStatus = data.filingStatus;
  const traditionalIRA = parseFloat(data.traditionalIRA) || 0;
  const rothIRA = parseFloat(data.rothIRA) || 0;
  const currentIncome = parseFloat(data.currentIncome) || 0;
  const retirementIncome = parseFloat(data.retirementIncome) || 0;
  const annualPortfolioWithdrawalRate = parseFloat(data.annualPortfolioWithdrawalRate) || 0;
  // Back-compat: older saved scenarios used a fixed dollar withdrawal.
  const legacyAnnualPortfolioWithdrawal = parseFloat(data.annualPortfolioWithdrawal) || 0;
  const withdrawalMode = data.withdrawalMode || 'rate';
  const targetAfterTaxSpending = parseFloat(data.targetAfterTaxSpending) || 0;
  const withdrawalOrder = data.withdrawalOrder || 'traditional_then_roth';
  const conversionAmount = parseFloat(data.conversionAmount) || 0;
  const conversionYears = parseInt(data.conversionYears, 10) || 1;
  const returnRate = (parseFloat(data.returnRate) || 0) / 100;
  const inflationRate = (parseFloat(data.inflationRate) || 0) / 100;
  const discountRate = (parseFloat(data.discountRate) || 0) / 100;
  const includeIrmaa = data.includeIrmaa !== false && data.includeIrmaa !== 'false' && data.includeIrmaa !== '0';
  const medicareStartAge = parseInt(data.medicareStartAge, 10) || 65;
  const taxExemptInterest = parseFloat(data.taxExemptInterest) || 0;
  const includeNiit = data.includeNiit !== false && data.includeNiit !== 'false' && data.includeNiit !== '0';
  const investmentIncome = parseFloat(data.investmentIncome) || 0;
  const retirementInvestmentIncomeRaw = data.retirementInvestmentIncome;
  const hasRetirementInvestmentIncome = retirementInvestmentIncomeRaw !== undefined && retirementInvestmentIncomeRaw !== null && String(retirementInvestmentIncomeRaw).trim() !== '';
  const retirementInvestmentIncome = hasRetirementInvestmentIncome ? (parseFloat(retirementInvestmentIncomeRaw) || 0) : investmentIncome;
  const fallbackMagi = currentIncome + taxExemptInterest;
  const baseStandardDeduction = STANDARD_DEDUCTION_2026[filingStatus] || 0;
  const seniorCount =
    (currentAge >= 65 ? 1 : 0) +
    (filingStatus === 'married' && spouseAge != null && spouseAge >= 65 ? 1 : 0);
  const seniorDeductionAdded = seniorCount * SENIOR_DEDUCTION_65PLUS;
  const standardDeduction = baseStandardDeduction + seniorDeductionAdded;
  const conversionStartAge = Math.max(currentAge, retirementAge);
  const conversionEndAge = conversionStartAge + conversionYears - 1;

  function applyWithdrawals(targetWithdrawal, withdrawalOrder, traditionalBalance, rothBalance) {
    let traditionalWithdrawal = 0, rothWithdrawal = 0;
    let remaining = Math.max(0, targetWithdrawal || 0);

    if (remaining <= 0) {
      return { traditionalBalance, rothBalance, traditionalWithdrawal, rothWithdrawal, totalWithdrawal: 0 };
    }

    if (withdrawalOrder === 'roth_then_traditional') {
      rothWithdrawal = Math.min(remaining, rothBalance);
      rothBalance -= rothWithdrawal;
      remaining -= rothWithdrawal;

      traditionalWithdrawal = Math.min(remaining, traditionalBalance);
      traditionalBalance -= traditionalWithdrawal;
      remaining -= traditionalWithdrawal;
    } else {
      traditionalWithdrawal = Math.min(remaining, traditionalBalance);
      traditionalBalance -= traditionalWithdrawal;
      remaining -= traditionalWithdrawal;

      rothWithdrawal = Math.min(remaining, rothBalance);
      rothBalance -= rothWithdrawal;
      remaining -= rothWithdrawal;
    }

    return {
      traditionalBalance,
      rothBalance,
      traditionalWithdrawal,
      rothWithdrawal,
      totalWithdrawal: traditionalWithdrawal + rothWithdrawal
    };
  }

  function solveWithdrawalForAfterTaxTarget(opts) {
    const {
      baseCashIncome,
      forcedCashTraditional, // RMD cash amount (already withdrawn from traditional)
      taxableBaseIncome,     // base income that is taxable as ordinary income
      conversionTaxable,     // conversion amount (taxable but not cash)
      standardDeduction,
      filingStatus,
      withdrawalOrder,
      traditionalBalance,
      rothBalance,
      targetAfterTaxSpending,
      irmaaThisYear,
      includeNiit,
      netInvestmentIncome,
      taxExemptInterest
    } = opts;

    function netCashForWithdrawal(w) {
      const applied = applyWithdrawals(w, withdrawalOrder, traditionalBalance, rothBalance);
      const taxableIncomeGross = taxableBaseIncome + forcedCashTraditional + applied.traditionalWithdrawal + conversionTaxable;
      const taxableIncome = Math.max(0, taxableIncomeGross - standardDeduction);
      const federalTax = calculateFederalTax(taxableIncome, filingStatus);
      const magi = taxableIncomeGross + (taxExemptInterest || 0);
      const niit = calculateNIIT(magi, netInvestmentIncome, filingStatus, includeNiit);
      const cashInflow = baseCashIncome + forcedCashTraditional + applied.totalWithdrawal;
      const netCash = cashInflow - federalTax - (irmaaThisYear || 0) - niit;
      return { ...applied, taxableIncome, federalTax, magi, niit, cashInflow, netCash };
    }

    // Bisection search for w that makes netCash ~= targetAfterTaxSpending.
    const maxPossible = traditionalBalance + rothBalance;
    let lo = 0;
    let hi = maxPossible;
    let best = null;

    for (let i = 0; i < 40; i++) {
      const mid = (lo + hi) / 2;
      const r = netCashForWithdrawal(mid);
      best = r;
      if (r.netCash >= targetAfterTaxSpending) {
        hi = mid;
      } else {
        lo = mid;
      }
    }

    // Final result at hi (closest meeting target).
    return netCashForWithdrawal(hi);
  }

  function projectRetirement(doConversion) {
    let traditionalBalance = traditionalIRA;
    let rothBalance = rothIRA;
    let totalRMDs = 0;
    const draftRows = [];
    const magiByYear = {};

    for (let age = currentAge; age <= lifeExpectancy; age++) {
      const yearsFromStart = age - currentAge;
      const year = age - currentAge + 2026;
      const baseIncome = age >= retirementAge ? retirementIncome : currentIncome;
      const netInvestmentIncome = getNetInvestmentIncome(
        age, retirementAge, investmentIncome,
        hasRetirementInvestmentIncome ? retirementInvestmentIncome : null
      );
      traditionalBalance *= (1 + returnRate);
      rothBalance *= (1 + returnRate);
      let rmd = 0, conversion = 0;
      let traditionalWithdrawal = 0, rothWithdrawal = 0, totalWithdrawal = 0;
      let cashInflow = baseIncome;
      let taxableIncomeGross = baseIncome;

      const lookbackYear = year - IRMAA_LOOKBACK_YEARS;
      const lookbackMagi = getMagiForLookbackYear(lookbackYear, magiByYear, fallbackMagi);
      const medicareEnrollees = getMedicareEnrollees(age, currentAge, spouseAge, filingStatus, medicareStartAge, includeIrmaa);
      const irmaaThisYear = includeIrmaa
        ? calculateIrmaaAnnual(lookbackMagi, filingStatus, medicareEnrollees, year, inflationRate)
        : 0;

      if (age >= 73 && traditionalBalance > 0) {
        rmd = calculateRMD(age, traditionalBalance);
        traditionalBalance -= rmd;
        totalRMDs += rmd;
        cashInflow += rmd;
        taxableIncomeGross += rmd;
      }
      if (doConversion && age >= conversionStartAge && age <= conversionEndAge) {
        conversion = Math.min(conversionAmount, traditionalBalance);
        traditionalBalance -= conversion;
        rothBalance += conversion;
        taxableIncomeGross += conversion;
      }

      if (age >= retirementAge) {
        if (withdrawalMode === 'target_after_tax' && targetAfterTaxSpending > 0) {
          const solved = solveWithdrawalForAfterTaxTarget({
            baseCashIncome: baseIncome,
            forcedCashTraditional: rmd,
            taxableBaseIncome: baseIncome,
            conversionTaxable: conversion,
            standardDeduction,
            filingStatus,
            withdrawalOrder,
            traditionalBalance,
            rothBalance,
            targetAfterTaxSpending,
            irmaaThisYear,
            includeNiit,
            netInvestmentIncome,
            taxExemptInterest
          });
          traditionalBalance = solved.traditionalBalance;
          rothBalance = solved.rothBalance;
          traditionalWithdrawal = solved.traditionalWithdrawal;
          rothWithdrawal = solved.rothWithdrawal;
          totalWithdrawal = solved.totalWithdrawal;
          cashInflow = solved.cashInflow;
          taxableIncomeGross = baseIncome + rmd + traditionalWithdrawal + conversion;
        } else {
          let targetWithdrawal = 0;
          if (annualPortfolioWithdrawalRate > 0) {
            targetWithdrawal = (annualPortfolioWithdrawalRate / 100) * (traditionalBalance + rothBalance);
          } else if (legacyAnnualPortfolioWithdrawal > 0) {
            targetWithdrawal = legacyAnnualPortfolioWithdrawal;
          }
          const applied = applyWithdrawals(targetWithdrawal, withdrawalOrder, traditionalBalance, rothBalance);
          traditionalBalance = applied.traditionalBalance;
          rothBalance = applied.rothBalance;
          traditionalWithdrawal = applied.traditionalWithdrawal;
          rothWithdrawal = applied.rothWithdrawal;
          totalWithdrawal = applied.totalWithdrawal;
          cashInflow += totalWithdrawal;
          taxableIncomeGross += traditionalWithdrawal;
        }
      }

      const taxableIncome = Math.max(0, taxableIncomeGross - standardDeduction);
      const federalTax = calculateFederalTax(taxableIncome, filingStatus);
      const magi = taxableIncomeGross + taxExemptInterest;
      magiByYear[year] = magi;
      const niit = calculateNIIT(magi, netInvestmentIncome, filingStatus, includeNiit);

      draftRows.push({
        age, year, yearsFromStart,
        traditionalBalance, rothBalance,
        conversion, rmd,
        traditionalWithdrawal, rothWithdrawal, totalWithdrawal,
        income: taxableIncomeGross, taxableIncome, magi, netInvestmentIncome,
        federalTax, irmaaThisYear, niit,
        medicareEnrollees, lookbackYear, lookbackMagi,
        cashInflow
      });
    }

    let totalTaxesPaid = 0;
    let totalDiscountedTaxesPaid = 0;
    let totalIrmaaPaid = 0;
    let totalNiitPaid = 0;
    const yearlyData = draftRows.map(row => {
      const irmaa = row.irmaaThisYear;
      const niit = row.niit;
      const allInTax = calculateAllInTax(row.federalTax, irmaa, niit);
      const discountedTax = discountTaxToPresent(allInTax, row.yearsFromStart, discountRate);
      totalTaxesPaid += allInTax;
      totalDiscountedTaxesPaid += discountedTax;
      totalIrmaaPaid += irmaa;
      totalNiitPaid += niit;
      const netCash = row.cashInflow - allInTax;
      return {
        ...row,
        irmaa,
        allInTax,
        discountedTax,
        totalTaxesPaid,
        totalDiscountedTaxesPaid,
        totalRMDs,
        totalIrmaaPaid,
        totalNiitPaid,
        netCash
      };
    });

    return {
      totalTaxesPaid, totalDiscountedTaxesPaid, totalIrmaaPaid, totalNiitPaid, totalRMDs,
      finalTraditionalBalance: yearlyData[yearlyData.length - 1].traditionalBalance,
      finalRothBalance: yearlyData[yearlyData.length - 1].rothBalance,
      yearlyData
    };
  }

  const withConversion = projectRetirement(true);
  const withoutConversion = projectRetirement(false);
  const taxSavings = withoutConversion.totalTaxesPaid - withConversion.totalTaxesPaid;
  const discountedTaxSavings = withoutConversion.totalDiscountedTaxesPaid - withConversion.totalDiscountedTaxesPaid;
  const rmdReduction = withoutConversion.totalRMDs - withConversion.totalRMDs;
  let breakEvenAge = null;
  let breakEvenAgeDiscounted = null;
  for (let i = 0; i < withConversion.yearlyData.length; i++) {
    if (breakEvenAge == null && withConversion.yearlyData[i].totalTaxesPaid < withoutConversion.yearlyData[i].totalTaxesPaid) {
      breakEvenAge = withConversion.yearlyData[i].age;
    }
    if (breakEvenAgeDiscounted == null && withConversion.yearlyData[i].totalDiscountedTaxesPaid < withoutConversion.yearlyData[i].totalDiscountedTaxesPaid) {
      breakEvenAgeDiscounted = withConversion.yearlyData[i].age;
    }
  }
  const taxableIncome = Math.max(0, currentIncome - standardDeduction);
  const taxWithoutConversion = calculateFederalTax(taxableIncome, filingStatus);
  const taxWithConversion = calculateFederalTax(taxableIncome + conversionAmount, filingStatus);
  const conversionTaxCost = taxWithConversion - taxWithoutConversion;
  const effectiveTaxRate = (conversionTaxCost / conversionAmount) * 100;
  const irmaaReduction = withoutConversion.totalIrmaaPaid - withConversion.totalIrmaaPaid;
  const niitReduction = withoutConversion.totalNiitPaid - withConversion.totalNiitPaid;
  return {
    conversionAmount, conversionYears, conversionStartAge, conversionEndAge,
    conversionTaxCost, effectiveTaxRate,
    currentMarginalRate: getMarginalRate(taxableIncome, filingStatus),
    marginalRateWithConversion: getMarginalRate(taxableIncome + conversionAmount, filingStatus),
    taxableIncome, taxSavings, discountedTaxSavings, rmdReduction, irmaaReduction, niitReduction,
    netBenefit: taxSavings, discountedNetBenefit: discountedTaxSavings,
    breakEvenAge, breakEvenAgeDiscounted, discountRate,
    includeIrmaa, includeNiit, medicareStartAge, taxExemptInterest, investmentIncome, retirementInvestmentIncome: hasRetirementInvestmentIncome ? retirementInvestmentIncome : '',
    filingStatus,
    baseStandardDeduction,
    standardDeduction,
    seniorCount,
    seniorDeductionAdded,
    withdrawalMode,
    targetAfterTaxSpending,
    withConversion, withoutConversion
  };
}

function calculate() {
    const formData = getRothFormData();
    const result = runRothAnalysis(formData);
    displayResults(result);
    window.lastRothResult = result;
}

function displayResults(data) {
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('resultsContent');
    const discountPct = (data.discountRate || 0) * 100;
    const hasDiscount = discountPct > 0;
    const isWorthIt = data.netBenefit > 0;
    const isWorthItDiscounted = data.discountedNetBenefit > 0;

    let recommendation = isWorthIt
        ? `Converting appears beneficial on a <strong>nominal</strong> (undiscounted) basis. You could save approximately $${Math.abs(data.netBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})} in lifetime all-in taxes.`
        : `Converting may not be optimal on a <strong>nominal</strong> basis. You would pay approximately $${Math.abs(data.netBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})} more in lifetime all-in taxes.`;

    if (hasDiscount) {
        recommendation += isWorthItDiscounted
            ? ` After discounting future taxes at <strong>${discountPct.toFixed(1)}%</strong> (today's dollars), lifetime savings are about $${Math.abs(data.discountedNetBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})}.`
            : ` After discounting at <strong>${discountPct.toFixed(1)}%</strong>, converting would cost about $${Math.abs(data.discountedNetBenefit).toLocaleString(undefined, {maximumFractionDigits: 0})} more in present-value terms — paying taxes upfront has a real opportunity cost.`;
    }

    const breakEvenHtml = [
        data.breakEvenAge ? `<strong>Nominal break-even age:</strong> ${data.breakEvenAge}` : '',
        hasDiscount && data.breakEvenAgeDiscounted ? `<strong>Discounted break-even age:</strong> ${data.breakEvenAgeDiscounted}` : '',
        hasDiscount && data.breakEvenAge && data.breakEvenAgeDiscounted && data.breakEvenAgeDiscounted > data.breakEvenAge
            ? `<span style="color:#92400e;">Higher discount rates push the crossover later — future tax savings are worth less in today's dollars.</span>`
            : ''
    ].filter(Boolean).join('<br>');

    resultsContent.innerHTML = `
        <div class="info-box ${isWorthIt ? 'info-box-blue' : ''}" style="margin-bottom: 25px;">
            <h3>💡 Recommendation</h3>
            <p style="font-size: 16px; margin-top: 10px;">${recommendation}</p>
            ${breakEvenHtml ? `<p style="margin-top: 10px;">${breakEvenHtml}</p>` : ''}
        </div>

        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Lifetime All-In Tax Comparison</h3>
            <p style="margin: 0 0 12px; font-size: 13px; color: #4b5563;">All-in tax cost includes <strong>federal income tax</strong>${data.includeIrmaa ? ', <strong>Medicare IRMAA</strong> (2-year lookback)' : ''}${data.includeNiit ? ', and <strong>NIIT</strong> (3.8% net investment income tax)' : ''}. MAGI ≈ gross income before deductions plus tax-exempt interest.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Without Conversion:</strong><br>
                    $${data.withoutConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>With Conversion:</strong><br>
                    $${data.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: ${isWorthIt ? '#059669' : '#dc2626'};">
                    <strong>Nominal Tax Savings:</strong><br>
                    ${isWorthIt ? '+' : ''}$${data.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
            ${hasDiscount ? `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <div>
                    <strong>Without Conversion (PV):</strong><br>
                    $${data.withoutConversion.totalDiscountedTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>With Conversion (PV):</strong><br>
                    $${data.withConversion.totalDiscountedTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: ${isWorthItDiscounted ? '#059669' : '#dc2626'};">
                    <strong>Discounted Tax Savings (${discountPct.toFixed(1)}%):</strong><br>
                    ${isWorthItDiscounted ? '+' : ''}$${data.discountedTaxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
            ` : ''}
        </div>

        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Lifetime All-In Tax Breakdown</h3>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>No Conversion</th>
                            <th>With Conversion</th>
                            <th>Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generateLifetimeBreakdownRows(data)}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="chart-section" style="margin-bottom: 30px;">
            <h3>Cumulative All-In Taxes Paid Over Time</h3>
            <p style="margin: 0 0 10px; font-size: 13px; color: #4b5563;">Federal = dark blue · IRMAA = light blue · NIIT = orange in the annual charts below.${hasDiscount ? ` Solid lines = nominal; dashed = present value at ${discountPct.toFixed(1)}%.` : ''}</p>
            <div class="chart-wrapper">
                <canvas id="cumulativeTaxChart"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px;">
            <div class="chart-section">
                <h3>Annual All-In Tax Cost — No Conversion</h3>
                <div class="chart-wrapper">
                    <canvas id="annualTaxNoConvChart"></canvas>
                </div>
            </div>
            <div class="chart-section">
                <h3>Annual All-In Tax Cost — With Conversion</h3>
                <div class="chart-wrapper">
                    <canvas id="annualTaxWithConvChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="chart-section" style="margin-bottom: 30px;">
            <h3>Account Balances Over Time</h3>
            <div class="chart-wrapper">
                <canvas id="accountBalanceChart"></canvas>
            </div>
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>RMD Impact</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Total RMDs (No Conversion):</strong><br>
                    $${data.withoutConversion.totalRMDs.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Total RMDs (With Conversion):</strong><br>
                    $${data.withConversion.totalRMDs.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: #059669;">
                    <strong>RMD Reduction:</strong><br>
                    $${data.rmdReduction.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>

        ${data.includeIrmaa ? `
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Medicare IRMAA Impact</h3>
            <p style="margin: 0 0 12px; font-size: 13px; color: #4b5563;">Premiums use income from <strong>${IRMAA_LOOKBACK_YEARS} years earlier</strong>. Conversion-year income can trigger surcharges ${IRMAA_LOOKBACK_YEARS} years later. Medicare enrollment assumed at age <strong>${data.medicareStartAge}</strong>.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Lifetime IRMAA (No Conversion):</strong><br>
                    $${data.withoutConversion.totalIrmaaPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Lifetime IRMAA (With Conversion):</strong><br>
                    $${data.withConversion.totalIrmaaPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: ${data.irmaaReduction > 0 ? '#059669' : data.irmaaReduction < 0 ? '#dc2626' : '#4b5563'};">
                    <strong>IRMAA ${data.irmaaReduction >= 0 ? 'Reduction' : 'Increase'}:</strong><br>
                    ${data.irmaaReduction >= 0 ? '' : '-'}$${Math.abs(data.irmaaReduction).toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>
        ` : ''}

        ${data.includeNiit ? `
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>NIIT Impact</h3>
            <p style="margin: 0 0 12px; font-size: 13px; color: #4b5563;">3.8% on the lesser of net investment income or MAGI above <strong>$${(data.filingStatus === 'married' ? 250000 : data.filingStatus === 'married_separate' ? 125000 : 200000).toLocaleString()}</strong> (${data.filingStatus === 'married' ? 'MFJ' : data.filingStatus === 'married_separate' ? 'MFS' : 'single/HOH'}). Roth conversions can push MAGI over the threshold and trigger NIIT on dividends, interest, and capital gains.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Lifetime NIIT (No Conversion):</strong><br>
                    $${data.withoutConversion.totalNiitPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Lifetime NIIT (With Conversion):</strong><br>
                    $${data.withConversion.totalNiitPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div style="color: ${data.niitReduction > 0 ? '#059669' : data.niitReduction < 0 ? '#dc2626' : '#4b5563'};">
                    <strong>NIIT ${data.niitReduction >= 0 ? 'Reduction' : 'Increase'}:</strong><br>
                    ${data.niitReduction >= 0 ? '' : '-'}$${Math.abs(data.niitReduction).toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>
        ` : ''}
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>First Year Conversion Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Conversion Amount:</strong><br>
                    $${data.conversionAmount.toLocaleString()}
                </div>
                <div>
                    <strong>Tax Cost:</strong><br>
                    $${data.conversionTaxCost.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
                <div>
                    <strong>Effective Rate:</strong><br>
                    ${data.effectiveTaxRate.toFixed(2)}%
                </div>
            </div>
        </div>
        
        <div class="info-box" style="margin-bottom: 25px;">
            <h3>Tax Brackets</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Current Taxable Income:</strong><br>
                    $${data.taxableIncome.toLocaleString()}
                </div>
                <div>
                    <strong>Current Marginal Rate:</strong><br>
                    ${(data.currentMarginalRate * 100).toFixed(0)}%
                </div>
                <div>
                    <strong>Rate After Conversion:</strong><br>
                    ${(data.marginalRateWithConversion * 100).toFixed(0)}%
                </div>
            </div>
            <p style="margin: 12px 0 0; font-size: 13px; color: #4b5563;">
              Note: Tax brackets and standard deduction amounts shown are based on the <strong>2026</strong> assumptions in this calculator.
            </p>
            ${data.seniorDeductionAdded > 0 ? `
              <p style="margin: 12px 0 0; font-size: 13px; color: #4b5563;">
                Note: This run applied an extra 65+ senior deduction of <strong>$${data.seniorDeductionAdded.toLocaleString()}</strong>
                (${data.seniorCount} ${data.seniorCount === 1 ? 'person' : 'people'} at $${SENIOR_DEDUCTION_65PLUS.toLocaleString()} each),
                for a total standard deduction of <strong>$${data.standardDeduction.toLocaleString()}</strong>.
              </p>
            ` : ``}
            ${data.withdrawalMode === 'target_after_tax' && data.targetAfterTaxSpending > 0 ? `
              <p style="margin: 10px 0 0; font-size: 13px; color: #4b5563;">
                Note: Withdrawal mode is set to <strong>Target after‑tax spending</strong> — the model automatically increases portfolio withdrawals as needed to cover federal taxes${data.includeIrmaa ? ', IRMAA' : ''}${data.includeNiit ? ', NIIT' : ''} (including Roth conversion and RMD taxes) while targeting <strong>$${data.targetAfterTaxSpending.toLocaleString()}</strong> per year after tax.
              </p>
            ` : ``}
        </div>
        
        <div class="info-box info-box-blue">
            <h3>Conversion Plan Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Total to Convert:</strong><br>
                    $${(data.conversionAmount * data.conversionYears).toLocaleString()}
                </div>
                <div>
                    <strong>Conversion Period:</strong><br>
                    Age ${data.conversionStartAge} to ${data.conversionEndAge}
                </div>
                <div>
                    <strong>Total Conversion Tax:</strong><br>
                    ~$${(data.conversionTaxCost * data.conversionYears).toLocaleString(undefined, {maximumFractionDigits: 0})}
                </div>
            </div>
        </div>
        
        <div class="table-section" style="margin-top: 30px;">
            <h3>Year-by-Year Projection — With Conversion</h3>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Age</th>
                            <th>Year</th>
                            <th>Conversion</th>
                            <th>RMD</th>
                            <th>Portfolio Withdrawal</th>
                            <th>Total Income (tax)</th>
                            <th>MAGI</th>
                            <th>Federal Tax</th>
                            ${data.includeIrmaa ? '<th>IRMAA</th>' : ''}
                            ${data.includeNiit ? '<th>NIIT</th>' : ''}
                            <th>All-In Tax</th>
                            <th>Cumulative All-In</th>
                            ${hasDiscount ? '<th>Cumulative (PV)</th>' : ''}
                            <th>Net Cash</th>
                            <th>Traditional IRA</th>
                            <th>Roth IRA</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generateTableRows(data.withConversion.yearlyData, hasDiscount, data.includeIrmaa, data.includeNiit)}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-section" style="margin-top: 30px;">
            <h3>Year-by-Year Projection — No Conversion</h3>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Age</th>
                            <th>Year</th>
                            <th>Conversion</th>
                            <th>RMD</th>
                            <th>Portfolio Withdrawal</th>
                            <th>Total Income (tax)</th>
                            <th>MAGI</th>
                            <th>Federal Tax</th>
                            ${data.includeIrmaa ? '<th>IRMAA</th>' : ''}
                            ${data.includeNiit ? '<th>NIIT</th>' : ''}
                            <th>All-In Tax</th>
                            <th>Cumulative All-In</th>
                            ${hasDiscount ? '<th>Cumulative (PV)</th>' : ''}
                            <th>Net Cash</th>
                            <th>Traditional IRA</th>
                            <th>Roth IRA</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generateTableRows(data.withoutConversion.yearlyData, hasDiscount, data.includeIrmaa, data.includeNiit)}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Create charts after DOM is updated
    setTimeout(() => {
        createCumulativeTaxChart(data);
        createAnnualAllInTaxChart('annualTaxNoConvChart', data.withoutConversion.yearlyData, {
            includeIrmaa: data.includeIrmaa,
            includeNiit: data.includeNiit
        });
        createAnnualAllInTaxChart('annualTaxWithConvChart', data.withConversion.yearlyData, {
            includeIrmaa: data.includeIrmaa,
            includeNiit: data.includeNiit
        });
        createAccountBalanceChart(data);
    }, 100);
}

function sumLifetimeComponent(yearlyData, field) {
    return (yearlyData || []).reduce((sum, row) => sum + (row[field] || 0), 0);
}

function generateLifetimeBreakdownRows(data) {
    const noConv = data.withoutConversion.yearlyData;
    const withConv = data.withConversion.yearlyData;
    const rows = [
        { label: 'Federal income tax', field: 'federalTax' },
        { label: 'Medicare IRMAA', field: 'irmaa', show: data.includeIrmaa },
        { label: 'NIIT (3.8%)', field: 'niit', show: data.includeNiit },
        { label: 'Total all-in tax', field: 'allInTax', bold: true }
    ];
    return rows.filter(r => r.show !== false).map(r => {
        const noVal = sumLifetimeComponent(noConv, r.field);
        const withVal = sumLifetimeComponent(withConv, r.field);
        const diff = noVal - withVal;
        const diffColor = diff > 0 ? '#059669' : diff < 0 ? '#dc2626' : '#4b5563';
        return `<tr${r.bold ? ' style="font-weight:600;background:#f9fafb;"' : ''}>
            <td>${r.label}</td>
            <td>$${noVal.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${withVal.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td style="color:${diffColor};">${diff >= 0 ? '+' : ''}$${diff.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
        </tr>`;
    }).join('');
}

function generateTableRows(yearlyData, includeDiscounted, includeIrmaa, includeNiit) {
    return yearlyData.map(row => `
        <tr>
            <td>${row.age}</td>
            <td>${row.year}</td>
            <td>$${row.conversion.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.rmd.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${(row.totalWithdrawal || 0).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.income.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${(row.magi || row.income).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.federalTax.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            ${includeIrmaa ? `<td>$${(row.irmaa || 0).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>` : ''}
            ${includeNiit ? `<td>$${(row.niit || 0).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>` : ''}
            <td>$${row.allInTax.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            ${includeDiscounted ? `<td>$${row.totalDiscountedTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>` : ''}
            <td>$${(row.netCash || 0).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.traditionalBalance.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            <td>$${row.rothBalance.toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
        </tr>
    `).join('');
}

function buildAllInTaxChartDatasets(yearlyData, options) {
    const { includeIrmaa = true, includeNiit = true } = options || {};
    const datasets = [
        {
            label: 'Federal Tax',
            data: yearlyData.map(d => d.federalTax),
            backgroundColor: '#1e40af',
            stack: 'tax'
        }
    ];
    if (includeIrmaa) {
        datasets.push({
            label: 'IRMAA',
            data: yearlyData.map(d => d.irmaa || 0),
            backgroundColor: '#38bdf8',
            stack: 'tax'
        });
    }
    if (includeNiit) {
        datasets.push({
            label: 'NIIT',
            data: yearlyData.map(d => d.niit || 0),
            backgroundColor: '#f97316',
            stack: 'tax'
        });
    }
    return datasets;
}

function chartCanvasToDataUrl(canvasId) {
    const canvas = document.getElementById(canvasId);
    return canvas && window.Chart ? canvas.toDataURL('image/png') : null;
}

// Chart creation function
function createCumulativeTaxChart(data) {
    const ctx = document.getElementById('cumulativeTaxChart');
    if (!ctx) return;

    if (window.cumulativeTaxChart instanceof Chart) {
        window.cumulativeTaxChart.destroy();
    }

    const ages = data.withConversion.yearlyData.map(d => d.age);
    const withConversionTaxes = data.withConversion.yearlyData.map(d => d.totalTaxesPaid);
    const withoutConversionTaxes = data.withoutConversion.yearlyData.map(d => d.totalTaxesPaid);
    const hasDiscount = (data.discountRate || 0) > 0;

    const datasets = [
        {
            label: 'With Conversion (nominal)',
            data: withConversionTaxes,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.1)',
            tension: 0.1,
            fill: false
        },
        {
            label: 'Without Conversion (nominal)',
            data: withoutConversionTaxes,
            borderColor: '#dc2626',
            backgroundColor: 'rgba(220, 38, 38, 0.1)',
            tension: 0.1,
            fill: false
        }
    ];

    if (hasDiscount) {
        datasets.push(
            {
                label: 'With Conversion (discounted)',
                data: data.withConversion.yearlyData.map(d => d.totalDiscountedTaxesPaid),
                borderColor: '#047857',
                backgroundColor: 'transparent',
                tension: 0.1,
                fill: false,
                borderDash: [6, 4]
            },
            {
                label: 'Without Conversion (discounted)',
                data: data.withoutConversion.yearlyData.map(d => d.totalDiscountedTaxesPaid),
                borderColor: '#b91c1c',
                backgroundColor: 'transparent',
                tension: 0.1,
                fill: false,
                borderDash: [6, 4]
            }
        );
    }

    window.cumulativeTaxChart = new Chart(ctx, {
        type: 'line',
        data: { labels: ages, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {maximumFractionDigits: 0});
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Age' } },
                y: {
                    title: { display: true, text: 'Cumulative All-In Taxes' },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createAnnualAllInTaxChart(canvasId, yearlyData, options) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const chartKey = canvasId + 'Chart';
    if (window[chartKey] instanceof Chart) {
        window[chartKey].destroy();
    }

    const years = yearlyData.map(d => d.year);
    window[chartKey] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: years,
            datasets: buildAllInTaxChartDatasets(yearlyData, options)
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        footer: function(items) {
                            const total = items.reduce((sum, item) => sum + item.parsed.y, 0);
                            return 'All-in total: $' + total.toLocaleString(undefined, {maximumFractionDigits: 0});
                        },
                        label: function(context) {
                            if (context.parsed.y === 0) return null;
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {maximumFractionDigits: 0});
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    title: { display: true, text: 'Year' },
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 14 }
                },
                y: {
                    title: { display: true, text: 'Annual All-In Tax Cost' },
                    stacked: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createAccountBalanceChart(data) {
    const ctx = document.getElementById('accountBalanceChart');
    if (!ctx) return;
    
    if (window.accountBalanceChart instanceof Chart) {
        window.accountBalanceChart.destroy();
    }
    
    const ages = data.withConversion.yearlyData.map(d => d.age);
    const traditionalBalances = data.withConversion.yearlyData.map(d => d.traditionalBalance);
    const rothBalances = data.withConversion.yearlyData.map(d => d.rothBalance);
    const traditionalBalancesNoConv = data.withoutConversion.yearlyData.map(d => d.traditionalBalance);
    const rothBalancesNoConv = data.withoutConversion.yearlyData.map(d => d.rothBalance);
    
    window.accountBalanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ages,
            datasets: [
                {
                    label: 'Traditional IRA (With Conversion)',
                    data: traditionalBalances,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Roth IRA (With Conversion)',
                    data: rothBalances,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Traditional IRA (No Conversion)',
                    data: traditionalBalancesNoConv,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.05)',
                    tension: 0.1,
                    fill: false,
                    borderDash: [5, 5]
                },
                {
                    label: 'Roth IRA (No Conversion)',
                    data: rothBalancesNoConv,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.05)',
                    tension: 0.1,
                    fill: false,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {maximumFractionDigits: 0});
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Age'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Account Balance'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + (value/1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        }
    });
}

// API base URL so api/... always resolves correctly (works for /roth-conv/ or /xamppfiles/htdocs/roth-conv/)
const RC_API_BASE = (function() {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)roth-conv\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

// Event listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rothForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculate();
    });

    // Make income fields unambiguous for already-retired users.
    const currentAgeEl = document.getElementById('currentAge');
    const retirementAgeEl = document.getElementById('retirementAge');
    const filingStatusEl = document.getElementById('filingStatus');
    const spouseAgeWrap = document.getElementById('spouseAgeWrap');
    const spouseAgeEl = document.getElementById('spouseAge');
    const withdrawalModeEl = document.getElementById('withdrawalMode');
    const targetSpendingWrap = document.getElementById('targetSpendingWrap');
    const targetAfterTaxEl = document.getElementById('targetAfterTaxSpending');
    const withdrawalRateEl = document.getElementById('annualPortfolioWithdrawalRate');
    const currentIncomeLabel = document.getElementById('currentIncomeLabel');
    const currentIncomeHelp = document.getElementById('currentIncomeHelp');
    const retirementIncomeLabel = document.getElementById('retirementIncomeLabel');
    const retirementIncomeHelp = document.getElementById('retirementIncomeHelp');

    function isAlreadyRetired() {
        const currentAge = parseInt(currentAgeEl?.value || '', 10);
        const retirementAgeRaw = (retirementAgeEl?.value || '').trim();
        if (!retirementAgeRaw) return true; // UX copy says blank => already retired
        const retirementAge = parseInt(retirementAgeRaw, 10);
        if (!Number.isFinite(currentAge) || !Number.isFinite(retirementAge)) return false;
        return retirementAge <= currentAge;
    }

    function updateIncomeCopy() {
        if (!currentIncomeLabel || !currentIncomeHelp || !retirementIncomeLabel || !retirementIncomeHelp) return;

        if (isAlreadyRetired()) {
            currentIncomeLabel.textContent = 'Current Annual Income ($)';
            currentIncomeHelp.textContent = 'Your total expected income this year (Social Security, pension, interest/dividends, part-time work). Before standard deduction. Excludes Roth conversions.';

            retirementIncomeLabel.textContent = 'Ongoing Annual Income (excluding RMDs) ($)';
            retirementIncomeHelp.textContent = 'Your ongoing annual income excluding RMDs and excluding Roth conversions (often similar to current income once retired).';
        } else {
            currentIncomeLabel.textContent = 'Current Annual Gross Income ($)';
            currentIncomeHelp.textContent = 'Wages, pensions, etc. (before standard deduction)';

            retirementIncomeLabel.textContent = 'Expected Retirement Income (excluding RMDs) ($)';
            retirementIncomeHelp.textContent = 'Annual income excluding RMDs and excluding Roth conversions';
        }
    }

    function updateSpouseAgeVisibility() {
        if (!filingStatusEl || !spouseAgeWrap || !spouseAgeEl) return;
        const isJoint = filingStatusEl.value === 'married';
        spouseAgeWrap.style.display = isJoint ? 'block' : 'none';
        spouseAgeEl.required = isJoint;
        if (!isJoint) spouseAgeEl.value = '';
    }

    function updateWithdrawalModeVisibility() {
        if (!withdrawalModeEl || !targetSpendingWrap || !targetAfterTaxEl || !withdrawalRateEl) return;
        const mode = withdrawalModeEl.value;
        const isTarget = mode === 'target_after_tax';
        targetSpendingWrap.style.display = isTarget ? 'block' : 'none';
        targetAfterTaxEl.required = isTarget;
        // If solving for target spending, withdrawal % is optional and should not block form submit.
        withdrawalRateEl.required = false;
    }

    updateIncomeCopy();
    updateSpouseAgeVisibility();
    updateWithdrawalModeVisibility();
    if (currentAgeEl) currentAgeEl.addEventListener('input', updateIncomeCopy);
    if (retirementAgeEl) retirementAgeEl.addEventListener('input', updateIncomeCopy);
    if (filingStatusEl) filingStatusEl.addEventListener('change', updateSpouseAgeVisibility);
    if (withdrawalModeEl) withdrawalModeEl.addEventListener('change', updateWithdrawalModeVisibility);

    if (window.RBUrlPrefill) {
        RBUrlPrefill.applyFromUrl({
            currentAge: 'currentAge',
            retirementAge: 'retirementAge',
            lifeExpectancy: 'lifeExpectancy',
            filingStatus: 'filingStatus',
            traditionalIRA: 'traditionalIRA',
            rothIRA: 'rothIRA',
            retirementIncome: 'retirementIncome',
            annualPortfolioWithdrawalRate: 'annualPortfolioWithdrawalRate',
            spouseAge: 'spouseAge'
        }, {
            required: ['fromPlan', 'currentAge'],
            formId: 'rothForm',
            autoSubmit: true,
            afterApply: function () {
                if (filingStatusEl) filingStatusEl.dispatchEvent(new Event('change'));
                updateIncomeCopy();
            }
        });
    }
});
// Premium Save/Load/Compare/PDF/CSV
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveScenarioBtn');
    const loadBtn = document.getElementById('loadScenarioBtn');
    const compareBtn = document.getElementById('compareScenariosBtn');
    const pdfBtn = document.getElementById('downloadPdfBtn');
    const csvBtn = document.getElementById('downloadCsvBtn');
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    const r = window.lastRothResult;
    if (!r) {
        alert('Please run the calculation first to see results.');
        return;
    }
    const fmt = (n) => '$' + (n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
    let summary = 'Roth Conversion Analysis.\n\n';
    summary += 'Converting $' + r.conversionAmount.toLocaleString() + ' per year for ' + r.conversionYears + ' years (ages ' + r.conversionStartAge + ' to ' + r.conversionEndAge + '). ';
    summary += 'Current taxable income: ' + fmt(r.taxableIncome) + '. ';
    summary += 'Current marginal tax rate: ' + (r.currentMarginalRate * 100).toFixed(0) + '%. ';
    summary += 'Marginal rate with conversion: ' + (r.marginalRateWithConversion * 100).toFixed(0) + '%.\n\n';
    summary += 'First-year conversion tax cost: ' + fmt(r.conversionTaxCost) + ' (effective rate ' + r.effectiveTaxRate.toFixed(1) + '%). ';
    summary += 'Lifetime all-in taxes without conversion: ' + fmt(r.withoutConversion.totalTaxesPaid) + '. ';
    summary += 'Lifetime all-in taxes with conversion: ' + fmt(r.withConversion.totalTaxesPaid) + '. ';
    summary += 'Nominal lifetime tax ' + (r.netBenefit > 0 ? 'savings: ' + fmt(r.netBenefit) : 'cost: ' + fmt(-r.netBenefit)) + '. ';
    if ((r.discountRate || 0) > 0) {
        summary += 'Discount rate: ' + (r.discountRate * 100).toFixed(1) + '%. ';
        summary += 'Discounted lifetime tax ' + (r.discountedNetBenefit > 0 ? 'savings: ' + fmt(r.discountedNetBenefit) : 'cost: ' + fmt(-r.discountedNetBenefit)) + '. ';
        if (r.breakEvenAgeDiscounted) summary += 'Discounted break-even age: ' + r.breakEvenAgeDiscounted + '. ';
    }
    summary += 'RMD reduction from conversion: ' + fmt(r.rmdReduction) + '. ';
    if (r.includeIrmaa) {
        summary += 'Lifetime IRMAA without conversion: ' + fmt(r.withoutConversion.totalIrmaaPaid) + '. ';
        summary += 'Lifetime IRMAA with conversion: ' + fmt(r.withConversion.totalIrmaaPaid) + '. ';
        summary += 'IRMAA ' + (r.irmaaReduction >= 0 ? 'reduction: ' + fmt(r.irmaaReduction) : 'increase: ' + fmt(-r.irmaaReduction)) + '. ';
    }
    if (r.includeNiit) {
        summary += 'Lifetime NIIT without conversion: ' + fmt(r.withoutConversion.totalNiitPaid) + '. ';
        summary += 'Lifetime NIIT with conversion: ' + fmt(r.withConversion.totalNiitPaid) + '. ';
        summary += 'NIIT ' + (r.niitReduction >= 0 ? 'reduction: ' + fmt(r.niitReduction) : 'increase: ' + fmt(-r.niitReduction)) + '. ';
    }
    if (r.breakEvenAge) summary += 'Nominal break-even age: ' + r.breakEvenAge + '.';

    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

    const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(explainUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ calculator_type: 'roth-conversion', results_summary: summary })
    })
    .then(r => r.text())
    .then(text => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let data;
        try { data = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Try logging out and back in.');
        }
        if (data.error) throw new Error(data.error);
        showExplainModal(data.explanation, { calculatorType: 'roth-conversion', resultsSummary: summary });
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Explain results: ' + err.message);
    });
}


function saveScenario() {
    const scenarioName = prompt('Enter a name for this scenario:', 'My Roth Plan');
    if (!scenarioName) return;
    const formData = getRothFormData();
    fetch(RC_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            calculator_type: 'roth-conversion',
            scenario_name: scenarioName,
            scenario_data: formData
        })
    })
    .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text: text })))
    .then(({ ok, status, text }) => {
        let data;
        try { data = JSON.parse(text); } catch (_) { throw new Error(text || 'Server error'); }
        if (!ok) throw new Error(data.error || 'Save failed');
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
    fetch(RC_API_BASE + 'api/load_scenarios.php?calculator_type=roth-conversion')
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
                    fetch(RC_API_BASE + 'api/delete_scenario.php', {
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
                    if (!input) return;
                    if (input.type === 'checkbox') {
                        input.checked = scenario.data[key] === true || scenario.data[key] === 'true' || scenario.data[key] === 'on';
                    } else {
                        input.value = scenario.data[key];
                    }
                });
                alert('Scenario loaded! Click Calculate to see results.');
            }
        }
    });
}

function compareScenarios() {
    fetch(RC_API_BASE + 'api/load_scenarios.php?calculator_type=roth-conversion')
    .then(res => res.json())
    .then(data => {
        if (!data.success) { alert('Error: ' + data.error); return; }
        if (data.scenarios.length < 2) {
            alert('You need at least 2 saved scenarios to compare. Save more first!');
            return;
        }
        let message = 'Select TWO scenarios to compare:\n\n';
        data.scenarios.forEach((s, i) => { message += `${i + 1}. ${s.name}\n`; });
        message += '\nEnter two numbers separated by comma (e.g., "1,2"):';
        const choice = prompt(message);
        if (!choice) return;
        const parts = choice.split(',').map(s => parseInt(s.trim(), 10) - 1);
        if (parts.length !== 2 || parts[0] < 0 || parts[0] >= data.scenarios.length ||
            parts[1] < 0 || parts[1] >= data.scenarios.length || parts[0] === parts[1]) {
            alert('Invalid selection. Enter two different numbers (e.g., "1,2").');
            return;
        }
        const s1 = data.scenarios[parts[0]];
        const s2 = data.scenarios[parts[1]];
        const result1 = runRothAnalysis(s1.data);
        const result2 = runRothAnalysis(s2.data);
        showRothComparison(s1.name, s2.name, result1, result2, s1.data, s2.data);
    })
    .catch(() => alert('Failed to load scenarios.'));
}

function showRothComparison(name1, name2, result1, result2, data1, data2) {
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('resultsContent');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
    const safe = (obj) => (obj && (obj.currentAge != null || obj.traditionalIRA != null)) ? obj : {};
    const d1 = safe(data1), d2 = safe(data2);
    const comparisonHTML = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #92400e;">⚖️ Scenario Comparison</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #667eea;">${name1}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Age: ${d1.currentAge || '-'} | Traditional: $${(parseFloat(d1.traditionalIRA) || 0).toLocaleString()} | Conversion: $${(parseFloat(d1.conversionAmount) || 0).toLocaleString()}/yr
                    </div>
                    <div style="margin-top: 8px;"><strong>Lifetime tax (with conversion):</strong> $${result1.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                    <div><strong>Tax savings vs no conversion:</strong> $${result1.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                </div>
                <div>
                    <h3 style="color: #e53e3e;">${name2}</h3>
                    <div style="font-size: 0.9em; color: #666;">
                        Age: ${d2.currentAge || '-'} | Traditional: $${(parseFloat(d2.traditionalIRA) || 0).toLocaleString()} | Conversion: $${(parseFloat(d2.conversionAmount) || 0).toLocaleString()}/yr
                    </div>
                    <div style="margin-top: 8px;"><strong>Lifetime tax (with conversion):</strong> $${result2.withConversion.totalTaxesPaid.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                    <div><strong>Tax savings vs no conversion:</strong> $${result2.taxSavings.toLocaleString(undefined, {maximumFractionDigits: 0})}</div>
                </div>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f0f0f0;"><th>Metric</th><th>${name1}</th><th>${name2}</th><th>Difference</th></tr>
                <tr><td>Lifetime tax (with conversion)</td><td>$${result1.withConversion.totalTaxesPaid.toLocaleString(0)}</td><td>$${result2.withConversion.totalTaxesPaid.toLocaleString(0)}</td><td>$${(result2.withConversion.totalTaxesPaid - result1.withConversion.totalTaxesPaid).toLocaleString(0)}</td></tr>
                <tr><td>Tax savings</td><td>$${result1.taxSavings.toLocaleString(0)}</td><td>$${result2.taxSavings.toLocaleString(0)}</td><td>$${(result2.taxSavings - result1.taxSavings).toLocaleString(0)}</td></tr>
                <tr><td>Lifetime IRMAA (no conversion)</td><td>$${result1.withoutConversion.totalIrmaaPaid.toLocaleString(0)}</td><td>$${result2.withoutConversion.totalIrmaaPaid.toLocaleString(0)}</td><td>-</td></tr>
                <tr><td>IRMAA reduction</td><td>$${result1.irmaaReduction.toLocaleString(0)}</td><td>$${result2.irmaaReduction.toLocaleString(0)}</td><td>$${(result2.irmaaReduction - result1.irmaaReduction).toLocaleString(0)}</td></tr>
                <tr><td>Lifetime NIIT (no conversion)</td><td>$${result1.withoutConversion.totalNiitPaid.toLocaleString(0)}</td><td>$${result2.withoutConversion.totalNiitPaid.toLocaleString(0)}</td><td>-</td></tr>
                <tr><td>NIIT reduction</td><td>$${result1.niitReduction.toLocaleString(0)}</td><td>$${result2.niitReduction.toLocaleString(0)}</td><td>$${(result2.niitReduction - result1.niitReduction).toLocaleString(0)}</td></tr>
                <tr><td>Break-even age (nominal)</td><td>${result1.breakEvenAge || '-'}</td><td>${result2.breakEvenAge || '-'}</td><td>-</td></tr>
                <tr><td>Break-even age (discounted)</td><td>${result1.breakEvenAgeDiscounted || '-'}</td><td>${result2.breakEvenAgeDiscounted || '-'}</td><td>-</td></tr>
            </table>
        </div>
    `;
    resultsContent.innerHTML = comparisonHTML + resultsContent.innerHTML;

    // Re-bind Explain button because innerHTML replacement recreates the DOM node
    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) {
        explainBtn.addEventListener('click', explainResults);
    }
}

function downloadPDF() {
    const res = window.lastRothResult;
    if (!res) {
        alert('Please run Calculate first, then download the PDF.');
        return;
    }
    const payload = {
        ...getRothFormData(),
        withConversion: res.withConversion,
        withoutConversion: res.withoutConversion,
        conversionAmount: res.conversionAmount,
        conversionYears: res.conversionYears,
        conversionStartAge: res.conversionStartAge,
        conversionEndAge: res.conversionEndAge,
        taxSavings: res.taxSavings,
        discountedTaxSavings: res.discountedTaxSavings,
        rmdReduction: res.rmdReduction,
        breakEvenAge: res.breakEvenAge,
        breakEvenAgeDiscounted: res.breakEvenAgeDiscounted,
        discountRate: res.discountRate,
        irmaaReduction: res.irmaaReduction,
        niitReduction: res.niitReduction,
        includeIrmaa: res.includeIrmaa,
        includeNiit: res.includeNiit,
        conversionTaxCost: res.conversionTaxCost,
        effectiveTaxRate: res.effectiveTaxRate,
        chartImage: chartCanvasToDataUrl('cumulativeTaxChart'),
        chartNoConvImage: chartCanvasToDataUrl('annualTaxNoConvChart'),
        chartWithConvImage: chartCanvasToDataUrl('annualTaxWithConvChart')
    };
    fetch(RC_API_BASE + 'api/generate_roth_pdf.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'PDF failed'); } catch (e) { throw new Error(t || 'PDF failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) return r.text().then(t => { throw new Error('Server did not return a PDF.'); });
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Roth_Conversion_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Download PDF: ' + e.message));
}

function downloadCSV() {
    const res = window.lastRothResult;
    if (!res) {
        alert('Please run Calculate first, then export CSV.');
        return;
    }
    const payload = {
        withConversion: res.withConversion,
        withoutConversion: res.withoutConversion,
        includeIrmaa: res.includeIrmaa,
        includeNiit: res.includeNiit,
        discountRate: res.discountRate,
        taxSavings: res.taxSavings,
        discountedTaxSavings: res.discountedTaxSavings,
        breakEvenAge: res.breakEvenAge,
        breakEvenAgeDiscounted: res.breakEvenAgeDiscounted,
        irmaaReduction: res.irmaaReduction,
        niitReduction: res.niitReduction
    };
    fetch(RC_API_BASE + 'api/export_roth_csv.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
    .then(r => {
        if (!r.ok) return r.text().then(t => { try { const j = JSON.parse(t); throw new Error(j.error || 'CSV failed'); } catch (e) { throw new Error(t || 'CSV failed'); } });
        const ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('text/csv') === -1 && ct.indexOf('application/csv') === -1) throw new Error('Server did not return CSV.');
        return r.blob();
    })
    .then(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Roth_Conversion_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(e => alert('Export CSV: ' + e.message));
}