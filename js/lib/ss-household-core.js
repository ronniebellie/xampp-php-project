/**
 * Household Social Security simulation with survivor benefit transition.
 * Used by Social Security Survivor Impact Calculator.
 */
(function (global) {
  'use strict';

  var FC = global.RBFinance;

  function spouseMonthlyAtYear(spouse, age, colaRate) {
    if (age < spouse.claimAge || Math.floor(age) > spouse.deathAge) return 0;
    var yearsReceiving = Math.floor(age - spouse.claimAge + 1e-9);
    return spouse.startMonthly * Math.pow(1 + colaRate / 100, yearsReceiving);
  }

  function deceasedWorkerBenefitBasis(spouse, colaRate) {
    return spouse.startMonthly * Math.pow(1 + colaRate / 100, Math.max(0, spouse.deathAge - spouse.claimAge));
  }

  var SURVIVOR_BANDS = [
    ['1940-01-01',780,19/40],['1941-01-01',782,57/124],['1942-01-01',784,57/128],
    ['1943-01-01',786,19/44],['1944-01-01',788,57/136],['1945-01-01',790,57/140],
    ['1957-01-01',792,19/48],['1958-01-01',794,57/148],['1959-01-01',796,57/152],
    ['1960-01-01',798,19/52],['1961-01-01',800,57/160],['1962-01-01',802,57/164],
    ['9999-12-31',804,19/56]
  ];

  function survivorBandForBirthDate(birthDate) {
    var value=String(birthDate||'');
    if(!/^\d{4}-\d{2}-\d{2}$/.test(value))throw new RangeError('A complete survivor birth date is required for the exact survivor-FRA reduction band.');
    var parts=value.split('-').map(Number),year=parts[0],month=parts[1],day=parts[2];
    var leap=year%4===0&&(year%100!==0||year%400===0),days=[31,leap?29:28,31,30,31,30,31,31,30,31,30,31];
    if(year<1||month<1||month>12||day<1||day>days[month-1])throw new RangeError('A valid calendar survivor birth date is required.');
    for(var i=0;i<SURVIVOR_BANDS.length;i++)if(value<=SURVIVOR_BANDS[i][0])return {fraMonths:SURVIVOR_BANDS[i][1],fraction:SURVIVOR_BANDS[i][2]};
    throw new RangeError('Unsupported survivor birth date.');
  }

  function survivorBenefitPercentage(birthDate,claimAgeMonths) {
    var band=survivorBandForBirthDate(birthDate),claim=Number(claimAgeMonths);
    if(!Number.isInteger(claim)||claim<720)throw new RangeError('Regular aged-survivor claiming before age 60 is unsupported.');
    if(claim>=band.fraMonths)return 1;
    return 1-(band.fraMonths-claim)*band.fraction*0.01;
  }

  /**
   * Regular aged-survivor branch: claim at first eligibility after death, using
   * the exact POMS monthly reduction through survivor FRA.
   */
  function survivorBenefitFromDeceased(spouse, deceasedAge, colaRate, survivorAge, survivorAgeAtDeath) {
    if (survivorAge < 60) return 0;
    var requestedClaimAge=arguments[6];
    if(typeof requestedClaimAge!=='number'||!Number.isFinite(requestedClaimAge)||requestedClaimAge<60||Math.abs(requestedClaimAge*12-Math.round(requestedClaimAge*12))>1e-7)throw new RangeError('A survivor claim age of at least 60 in whole months is required.');
    var claimAge=Math.max(60,survivorAgeAtDeath,Math.round(requestedClaimAge*12)/12);
    if(Math.round(survivorAge*12)<Math.round(claimAge*12))return 0;
    var deceasedBasis = deceasedWorkerBenefitBasis(spouse, colaRate);
    var percentage=survivorBenefitPercentage(arguments[5],Math.round(claimAge*12));
    var yearsSinceClaim = Math.floor(survivorAge - claimAge + 1e-9);
    return deceasedBasis * percentage * Math.pow(1 + colaRate / 100, yearsSinceClaim);
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
    survivorBandForBirthDate(raw.birthDate);
    if(!Number.isInteger(raw.birthYear)||Number(raw.birthDate.slice(0,4))!==raw.birthYear)throw new RangeError('Birth date and birth year must match.');
    if(typeof raw.survivorClaimAge!=='number'||!Number.isFinite(raw.survivorClaimAge)||raw.survivorClaimAge<60||Math.abs(raw.survivorClaimAge*12-Math.round(raw.survivorClaimAge*12))>1e-7)throw new RangeError('A survivor claim age of at least 60 in whole months is required.');
    if(!Number.isInteger(raw.deathAge)||raw.deathAge<0||!Number.isInteger(raw.claimAge)||raw.claimAge<62||raw.claimAge>70||typeof raw.pia!=='number'||!Number.isFinite(raw.pia)||raw.pia<0)throw new RangeError('Valid death age, retirement claim age 62–70, and nonnegative PIA are required.');
    var s = {
      birthYear: raw.birthYear,
      birthDate: raw.birthDate,
      survivorClaimAge: raw.survivorClaimAge,
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
    var hAlive = Math.floor(ageH) <= higher.deathAge;
    var lAlive = Math.floor(ageL) <= lower.deathAge;
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
      var lowerAgeAtDeath = higher.birthYear + higher.deathAge + 1 - lower.birthYear;
      var survivorFromH = survivorBenefitFromDeceased(higher, ageH, colaRate, ageL, lowerAgeAtDeath,lower.birthDate,lower.survivorClaimAge);
      householdMonthly = Math.max(monthlyL, survivorFromH);
    } else {
      phase = 'survivor_higher';
      var higherAgeAtDeath = lower.birthYear + lower.deathAge + 1 - higher.birthYear;
      var survivorFromL = survivorBenefitFromDeceased(lower, ageL, colaRate, ageH, higherAgeAtDeath,higher.birthDate,higher.survivorClaimAge);
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
    var simStart = Math.min(higher.birthYear, lower.birthYear) + 60;
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
      // Annual rows are age-year buckets. Death occurs at the end of the
      // selected age-year; claims begin in their exact modeled age-month.
      var annual=0,annualH=0,annualL=0;
      for(var month=0;month<12;month++){
        var payment=householdMonthlyForYear(higher,lower,ageH+month/12,ageL+month/12,colaRate);
        annual+=payment.householdMonthly;annualH+=payment.monthlyH;annualL+=payment.monthlyL;
        if(payment.phase==='both_alive'||(payment.phase==='survivor_lower'&&payment.monthlyL>=payment.householdMonthly))lowerOwnReceived+=payment.monthlyL;
      }
      row.householdMonthly=annual/12;row.monthlyH=annualH/12;row.monthlyL=annualL/12;

      if (firstDeathCalendarYear == null && row.phase.indexOf('survivor') === 0) {
        firstDeathCalendarYear = year;
        firstDeathWho = row.phase === 'survivor_lower' ? 'higher' : 'lower';
        if (firstDeathWho === 'higher') {
          var lowerAtDeath = higher.birthYear + higher.deathAge + 1 - lower.birthYear;
          var lowerSurvivorClaimAge=Math.max(60,lowerAtDeath,lower.survivorClaimAge);
          survivorFloorAtDeath = Math.floor(lowerSurvivorClaimAge)>lower.deathAge?0:survivorBenefitFromDeceased(higher, higher.deathAge, colaRate, lowerSurvivorClaimAge, lowerAtDeath,lower.birthDate,lower.survivorClaimAge);
        } else {
          var higherAtDeath=lower.birthYear+lower.deathAge+1-higher.birthYear;
          var higherSurvivorClaimAge=Math.max(60,higherAtDeath,higher.survivorClaimAge);
          survivorFloorAtDeath=Math.floor(higherSurvivorClaimAge)>higher.deathAge?0:survivorBenefitFromDeceased(lower,lower.deathAge,colaRate,higherSurvivorClaimAge,higherAtDeath,higher.birthDate,higher.survivorClaimAge);
        }
      }

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
      survivorFloor: survivorFloorAtDeath
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
    var higherAtDeath = deceasedWorkerBenefitBasis(higher, colaRate);

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
    deceasedWorkerBenefitBasis: deceasedWorkerBenefitBasis,
    survivorBandForBirthDate: survivorBandForBirthDate,
    survivorBenefitPercentage: survivorBenefitPercentage,
    formatHouseholdPhase: formatHouseholdPhase
  };
})(typeof window !== 'undefined' ? window : this);
