/**
 * Household Social Security simulation with survivor benefit transition.
 * Used by Social Security Survivor Impact Calculator.
 */
(function (global) {
  'use strict';

  var FC = global.RBFinance;

  function spouseMonthlyAtYear(spouse, age, colaRate) {
    if (age < spouse.claimAge || age > spouse.deathAge) return 0;
    var yearsReceiving = age - spouse.claimAge;
    return spouse.startMonthly * Math.pow(1 + colaRate / 100, yearsReceiving);
  }

  /** Deceased spouse benefit for survivor: COLA continues from amount at death. */
  function survivorBenefitFromDeceased(spouse, deceasedAge, colaRate) {
    if (deceasedAge < spouse.deathAge) {
      return spouseMonthlyAtYear(spouse, deceasedAge, colaRate);
    }
    var atDeath = spouseMonthlyAtYear(spouse, spouse.deathAge, colaRate);
    var yearsSinceDeath = deceasedAge - spouse.deathAge;
    return atDeath * Math.pow(1 + colaRate / 100, yearsSinceDeath);
  }

  var PHASE_LABELS = {
    both_alive: 'Both spouses living',
    survivor_lower: 'Lower earner surviving',
    survivor_higher: 'Higher earner surviving',
    both_deceased: 'Both spouses deceased'
  };

  function formatHouseholdPhase(phase) {
    return PHASE_LABELS[phase] || phase;
  }

  function prepareSpouse(raw, isHigher) {
    var s = {
      birthYear: raw.birthYear,
      pia: raw.pia,
      claimAge: raw.claimAge,
      deathAge: raw.deathAge,
      label: raw.label || (isHigher ? 'Higher earner' : 'Lower earner'),
      isHigher: isHigher
    };
    s.startMonthly = FC.calculateMonthlyBenefit(s.pia, s.birthYear, s.claimAge);
    s.fra = FC.getFRA(s.birthYear);
    s.fraAge = FC.fraAgeFromBirthYear(s.birthYear);
    s.monthlyAtAge = function (age, colaRate) {
      return spouseMonthlyAtYear(s, age, colaRate);
    };
    return s;
  }

  function householdMonthlyForYear(higher, lower, ageH, ageL, colaRate) {
    var hAlive = ageH <= higher.deathAge;
    var lAlive = ageL <= lower.deathAge;
    var monthlyH = hAlive && ageH >= higher.claimAge ? spouseMonthlyAtYear(higher, ageH, colaRate) : 0;
    var monthlyL = lAlive && ageL >= lower.claimAge ? spouseMonthlyAtYear(lower, ageL, colaRate) : 0;
    var phase;
    var householdMonthly;

    if (!hAlive && !lAlive) {
      phase = 'both_deceased';
      householdMonthly = 0;
    } else if (hAlive && lAlive) {
      phase = 'both_alive';
      householdMonthly = monthlyH + monthlyL;
    } else if (!hAlive && lAlive) {
      phase = 'survivor_lower';
      var survivorFromH = survivorBenefitFromDeceased(higher, ageH, colaRate);
      householdMonthly = Math.max(monthlyL, survivorFromH);
    } else {
      phase = 'survivor_higher';
      var survivorFromL = survivorBenefitFromDeceased(lower, ageL, colaRate);
      householdMonthly = Math.max(monthlyH, survivorFromL);
    }

    return {
      phase: phase,
      householdMonthly: householdMonthly,
      monthlyH: monthlyH,
      monthlyL: monthlyL,
      hAlive: hAlive,
      lAlive: lAlive
    };
  }

  function simulateHouseholdSS(opts) {
    var colaRate = opts.colaRate || 0;
    var discountRate = opts.discountRate || 0;
    var higher = prepareSpouse(opts.higherEarner, true);
    var lower = prepareSpouse(opts.lowerEarner, false);

    var lowerEarlyAge = opts.lowerEarlyCompareAge;
    if (lowerEarlyAge == null) lowerEarlyAge = Math.round(lower.fraAge);
    lower.earlyCompareAge = lowerEarlyAge;
    lower.earlyMonthly = FC.calculateMonthlyBenefit(lower.pia, lower.birthYear, lowerEarlyAge);

    var ageGap = lower.birthYear - higher.birthYear;
    var simStart = Math.min(higher.birthYear, lower.birthYear) + 62;
    var simEnd = Math.max(higher.birthYear + higher.deathAge, lower.birthYear + lower.deathAge);

    var yearly = [];
    var totalHousehold = 0;
    var beforeFirstDeath = 0;
    var afterFirstDeath = 0;
    var firstDeathWho = null;
    var firstDeathCalendarYear = null;
    var lowerOwnReceived = 0;
    var survivorFloorAtDeath = 0;

    for (var year = simStart; year <= simEnd; year++) {
      var ageH = year - higher.birthYear;
      var ageL = year - lower.birthYear;
      var row = householdMonthlyForYear(higher, lower, ageH, ageL, colaRate);

      if (firstDeathCalendarYear == null && row.phase.indexOf('survivor') === 0) {
        firstDeathCalendarYear = year;
        firstDeathWho = row.phase === 'survivor_lower' ? 'higher' : 'lower';
        if (firstDeathWho === 'higher') {
          survivorFloorAtDeath = spouseMonthlyAtYear(higher, higher.deathAge, colaRate);
        }
      }

      if (row.phase === 'both_alive' && row.monthlyL > 0) {
        lowerOwnReceived += row.monthlyL * 12;
      } else if (row.phase === 'survivor_lower' && ageL >= lower.claimAge && ageL <= lower.deathAge && row.monthlyL >= row.householdMonthly) {
        lowerOwnReceived += row.monthlyL * 12;
      }

      var annual = row.householdMonthly * 12;
      var yearsFromStart = year - simStart;
      var pvAnnual = annual * Math.pow(1 + discountRate / 100, -yearsFromStart);
      totalHousehold += pvAnnual;

      if (firstDeathCalendarYear == null) {
        beforeFirstDeath += pvAnnual;
      } else {
        afterFirstDeath += pvAnnual;
      }

      yearly.push({
        calendarYear: year,
        higherAge: ageH,
        lowerAge: ageL,
        phase: row.phase,
        monthlyHigher: row.monthlyH,
        monthlyLower: row.monthlyL,
        householdMonthly: row.householdMonthly,
        annualHousehold: annual,
        cumulativeHousehold: totalHousehold
      });
    }

    var delayAnalysis = computeDelayAnalysis(higher, lower, colaRate, discountRate, firstDeathWho, ageGap);

    return {
      higher: higher,
      lower: lower,
      yearly: yearly,
      totalHousehold: totalHousehold,
      beforeFirstDeath: beforeFirstDeath,
      afterFirstDeath: afterFirstDeath,
      firstDeathWho: firstDeathWho,
      firstDeathCalendarYear: firstDeathCalendarYear,
      lowerOwnReceived: lowerOwnReceived,
      delayAnalysis: delayAnalysis,
      survivorFloor: survivorFloorAtDeath || higher.startMonthly
    };
  }

  function computeDelayAnalysis(higher, lower, colaRate, discountRate, firstDeathWho, ageGap) {
    var earlyAge = lower.earlyCompareAge;
    var claimAge = lower.claimAge;
    var forgone = 0;

    if (claimAge > earlyAge) {
      for (var age = earlyAge; age < claimAge; age++) {
        var yearsFromEarly = age - earlyAge;
        var monthly = lower.earlyMonthly * Math.pow(1 + colaRate / 100, yearsFromEarly);
        forgone += monthly * 12 * Math.pow(1 + discountRate / 100, -yearsFromEarly);
      }
    }

    var recovered = 0;
    var extraMonths = 0;

    if (firstDeathWho === 'higher' && claimAge <= higher.deathAge) {
      for (var ha = lower.claimAge + ageGap; ha <= higher.deathAge; ha++) {
        var la = ha - ageGap;
        if (la < lower.claimAge || la > lower.deathAge) continue;
        var delayedMo = spouseMonthlyAtYear(lower, la, colaRate);
        var earlyMo = lower.earlyMonthly * Math.pow(1 + colaRate / 100, la - earlyAge);
        var extra = Math.max(0, delayedMo - earlyMo);
        if (extra > 0) {
          recovered += extra * 12;
          extraMonths += 12;
        }
      }
    }

    var higherAtFra = FC.calculateMonthlyBenefit(higher.pia, higher.birthYear, Math.round(higher.fraAge));
    var higherAtDeath = spouseMonthlyAtYear(higher, higher.deathAge, colaRate);

    return {
      earlyCompareAge: earlyAge,
      earlyMonthly: lower.earlyMonthly,
      delayedMonthly: lower.startMonthly,
      forgone: forgone,
      recovered: recovered,
      netLoss: Math.max(0, forgone - recovered),
      extraMonths: extraMonths,
      higherAtFra: higherAtFra,
      higherAtDeath: higherAtDeath,
      higherDelayBonusMonthly: Math.max(0, higherAtDeath - higherAtFra)
    };
  }

  function compareStrategies(baseOpts, strategies) {
    return strategies.map(function (strat) {
      var opts = {
        colaRate: baseOpts.colaRate,
        discountRate: baseOpts.discountRate,
        lowerEarlyCompareAge: baseOpts.lowerEarlyCompareAge,
        higherEarner: Object.assign({}, baseOpts.higherEarner, strat.higher || {}),
        lowerEarner: Object.assign({}, baseOpts.lowerEarner, strat.lower || {})
      };
      return {
        name: strat.name,
        description: strat.description || '',
        result: simulateHouseholdSS(opts)
      };
    });
  }

  global.RBSSHousehold = {
    simulateHouseholdSS: simulateHouseholdSS,
    compareStrategies: compareStrategies,
    spouseMonthlyAtYear: spouseMonthlyAtYear,
    survivorBenefitFromDeceased: survivorBenefitFromDeceased,
    formatHouseholdPhase: formatHouseholdPhase
  };
})(typeof window !== 'undefined' ? window : this);
