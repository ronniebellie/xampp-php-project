/**
 * Early Exit Social Security Impact — Quick / Advanced estimate
 * Approximates AIME/PIA impact of stopping work earlier than planned.
 */

(function () {
  'use strict';

  // --- SSA tables (eligibility year = year turn 62) ---
  const BEND_POINTS = {
    2022: { b1: 1024, b2: 6172 },
    2023: { b1: 1115, b2: 6721 },
    2024: { b1: 1174, b2: 7078 },
    2025: { b1: 1226, b2: 7391 },
    2026: { b1: 1286, b2: 7749 }
  };

  const WAGE_BASE = {
    2022: 147000,
    2023: 160200,
    2024: 168600,
    2025: 176100,
    2026: 184500
  };

  let scenarioBarChart = null;
  let waterfallChart = null;
  let lastResults = null;

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

  function calculateEarlyReduction(monthsEarly) {
    const first36 = Math.min(monthsEarly, 36);
    const beyond36 = Math.max(0, monthsEarly - 36);
    return 1 - (first36 * (5 / 9) / 100 + beyond36 * (5 / 12) / 100);
  }

  function calculateDelayCredit(monthsDelayed) {
    return 1 + (monthsDelayed * (2 / 3) / 100);
  }

  function claimingFactor(birthYear, claimAge) {
    const fra = getFRA(birthYear);
    const fraMonths = fra.years * 12 + fra.months;
    const claimMonths = Math.min(claimAge * 12, 70 * 12);
    const diff = claimMonths - fraMonths;
    if (diff < 0) return calculateEarlyReduction(-diff);
    if (diff > 0) return calculateDelayCredit(diff);
    return 1;
  }

  function calculateMonthlyBenefit(pia, birthYear, claimAge) {
    return pia * claimingFactor(birthYear, claimAge);
  }

  function eligibilityYear(birthYear) {
    return birthYear + 62;
  }

  function getBendPoints(eligYear) {
    if (BEND_POINTS[eligYear]) return BEND_POINTS[eligYear];
    const years = Object.keys(BEND_POINTS).map(Number).sort((a, b) => a - b);
    if (eligYear < years[0]) return BEND_POINTS[years[0]];
    return BEND_POINTS[years[years.length - 1]];
  }

  function getWageBase(year) {
    if (WAGE_BASE[year]) return WAGE_BASE[year];
    const years = Object.keys(WAGE_BASE).map(Number).sort((a, b) => a - b);
    if (year < years[0]) return WAGE_BASE[years[0]];
    const latest = years[years.length - 1];
    // Rough forward fill ~3%/yr for future projection years
    const steps = year - latest;
    return Math.round(WAGE_BASE[latest] * Math.pow(1.03, steps));
  }

  function piaFromAime(aime, eligYear) {
    const { b1, b2 } = getBendPoints(eligYear);
    let pia;
    if (aime <= b1) {
      pia = 0.9 * aime;
    } else if (aime <= b2) {
      pia = 0.9 * b1 + 0.32 * (aime - b1);
    } else {
      pia = 0.9 * b1 + 0.32 * (b2 - b1) + 0.15 * (aime - b2);
    }
    return Math.floor(pia * 10) / 10;
  }

  function aimeFromPia(pia, eligYear) {
    const { b1, b2 } = getBendPoints(eligYear);
    const piaAtB1 = 0.9 * b1;
    const piaAtB2 = piaAtB1 + 0.32 * (b2 - b1);
    if (pia <= piaAtB1) return pia / 0.9;
    if (pia <= piaAtB2) return b1 + (pia - piaAtB1) / 0.32;
    return b2 + (pia - piaAtB2) / 0.15;
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount || 0);
  }

  function ageFromBirthDate(yyyyMmDd) {
    const parts = String(yyyyMmDd).substr(0, 10).split('-');
    if (parts.length !== 3) return null;
    const b = new Date(+parts[0], +parts[1] - 1, +parts[2]);
    const today = new Date();
    let age = today.getFullYear() - b.getFullYear();
    const m = today.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
    return age;
  }

  function projectEarnings(currentAge, stopAge, currentEarnings, growthPct, postStopAnnual, claimAge) {
    const years = [];
    const g = (growthPct || 0) / 100;
    const calendarYearNow = new Date().getFullYear();
    const ageFloor = Math.floor(currentAge);

    for (let age = ageFloor; age < Math.ceil(stopAge); age++) {
      const yearOffset = age - ageFloor;
      const year = calendarYearNow + yearOffset;
      const raw = currentEarnings * Math.pow(1 + g, yearOffset);
      years.push(Math.min(raw, getWageBase(year)));
    }

    // Fractional stop year: include partial year of earnings
    const frac = stopAge - Math.floor(stopAge);
    if (frac > 0.01 && Math.floor(stopAge) >= ageFloor) {
      const yearOffset = Math.floor(stopAge) - ageFloor;
      const year = calendarYearNow + yearOffset;
      const raw = currentEarnings * Math.pow(1 + g, yearOffset) * frac;
      // Replace last full year if we already pushed floor age matching floor(stop)
      if (years.length && Math.floor(stopAge) === ageFloor + years.length - 1) {
        years[years.length - 1] = Math.min(raw, getWageBase(year));
      }
    }

    const post = Math.max(0, postStopAnnual || 0);
    if (post > 0) {
      for (let age = Math.ceil(stopAge); age < claimAge; age++) {
        const yearOffset = age - ageFloor;
        const year = calendarYearNow + yearOffset;
        years.push(Math.min(post, getWageBase(year)));
      }
    }

    return years;
  }

  function top35Sum(earningsList) {
    const sorted = earningsList.slice().sort((a, b) => b - a);
    const top = sorted.slice(0, 35);
    while (top.length < 35) top.push(0);
    return top.reduce((s, v) => s + v, 0);
  }

  function piaFromEarningsList(earningsList, eligYear) {
    const aime = top35Sum(earningsList) / 420;
    return { aime, pia: piaFromAime(aime, eligYear) };
  }

  /**
   * Build synthetic career: past years (H or entered) + future projected years.
   * Calibrate H so plan-path PIA matches target when targetPia is set.
   */
  function estimateForStopAge(inputs, stopAge, targetPia) {
    const {
      currentAge,
      currentAnnualEarnings,
      earningsGrowthRatePct,
      postStopAnnualEarnings,
      claimingAge,
      yearsAlreadyWorked,
      historicalEarningsRatio,
      pastEarningsAmounts,
      eligYear
    } = inputs;

    const future = projectEarnings(
      currentAge,
      stopAge,
      currentAnnualEarnings,
      earningsGrowthRatePct,
      postStopAnnualEarnings,
      claimingAge
    );

    const nPast = Math.min(35, Math.max(0, Math.round(yearsAlreadyWorked)));
    const knownPast = (pastEarningsAmounts || []).filter((v) => v > 0);
    const nKnown = Math.min(knownPast.length, nPast);
    const nSynth = Math.max(0, nPast - nKnown);

    function buildWithH(H) {
      const past = knownPast.slice(0, nKnown);
      for (let i = 0; i < nSynth; i++) past.push(H);
      return past.concat(future);
    }

    let H = currentAnnualEarnings * (historicalEarningsRatio / 100);
    let calibrated = false;

    if (targetPia && targetPia > 0 && nSynth > 0) {
      // Binary search H so plan PIA ≈ target (caller passes plan-path target only for plan stop)
      let lo = 0;
      let hi = Math.max(currentAnnualEarnings * 2, getWageBase(new Date().getFullYear()));
      for (let i = 0; i < 40; i++) {
        const mid = (lo + hi) / 2;
        const { pia } = piaFromEarningsList(buildWithH(mid), eligYear);
        if (pia < targetPia) lo = mid;
        else hi = mid;
      }
      H = (lo + hi) / 2;
      calibrated = true;
    } else if (targetPia && targetPia > 0 && nSynth === 0 && knownPast.length > 0) {
      // Scale known past to hit target when combined with future
      let lo = 0.2;
      let hi = 3;
      for (let i = 0; i < 40; i++) {
        const mid = (lo + hi) / 2;
        const scaled = knownPast.map((v) => v * mid);
        const list = scaled.concat(future);
        // pad synth zeros already handled in top35
        const { pia } = piaFromEarningsList(list, eligYear);
        if (pia < targetPia) lo = mid;
        else hi = mid;
      }
      const scale = (lo + hi) / 2;
      const list = knownPast.map((v) => v * scale).concat(future);
      const result = piaFromEarningsList(list, eligYear);
      return {
        ...result,
        H: null,
        scale,
        calibrated: true,
        futureYears: future.length,
        pastYearsUsed: nPast
      };
    }

    const list = buildWithH(H);
    const result = piaFromEarningsList(list, eligYear);
    return {
      ...result,
      H,
      calibrated,
      futureYears: future.length,
      pastYearsUsed: nPast
    };
  }

  function lifetimeTotal(monthlyAtClaim, claimAge, lifeExpectancy, colaPct) {
    let monthly = monthlyAtClaim;
    let total = 0;
    for (let age = claimAge; age <= lifeExpectancy; age++) {
      if (age > claimAge) monthly *= 1 + colaPct / 100;
      total += monthly * 12;
    }
    return total;
  }

  function collectInputs() {
    const birthDate = document.getElementById('birthDate').value;
    const birthYear = parseInt(birthDate.substr(0, 4), 10);
    const currentAge = ageFromBirthDate(birthDate);
    const mode = document.getElementById('mode').value || 'quick';

    let yearsAlreadyWorked = parseInt(document.getElementById('yearsAlreadyWorked').value, 10);
    if (mode === 'quick' || !yearsAlreadyWorked) {
      yearsAlreadyWorked = Math.min(40, Math.max(0, (currentAge || 60) - 22));
    }

    let historicalEarningsRatio = parseFloat(document.getElementById('historicalEarningsRatio').value);
    if (mode === 'quick' || !historicalEarningsRatio) historicalEarningsRatio = 65;

    let postStopAnnualEarnings = parseFloat(document.getElementById('postStopAnnualEarnings').value);
    if (mode === 'quick' || isNaN(postStopAnnualEarnings)) postStopAnnualEarnings = 0;

    const pastEarningsAmounts = [];
    if (mode === 'advanced') {
      document.querySelectorAll('.past-earnings-amount').forEach((el) => {
        const v = parseFloat(el.value);
        if (!isNaN(v) && v > 0) pastEarningsAmounts.push(v);
      });
    }

    return {
      mode,
      birthDate,
      birthYear,
      currentAge,
      plannedRetirementAge: parseFloat(document.getElementById('plannedRetirementAge').value),
      actualStopAge: parseFloat(document.getElementById('actualStopAge').value),
      claimingAge: parseFloat(document.getElementById('claimingAge').value),
      currentAnnualEarnings: parseFloat(document.getElementById('currentAnnualEarnings').value),
      earningsGrowthRatePct: parseFloat(document.getElementById('earningsGrowthRatePct').value) || 0,
      ssaBenefitMonthly: parseFloat(document.getElementById('ssaBenefitMonthly').value) || 0,
      ssaBenefitBasis: document.getElementById('ssaBenefitBasis').value,
      lifeExpectancy: parseInt(document.getElementById('lifeExpectancy').value, 10),
      colaRatePct: parseFloat(document.getElementById('colaRatePct').value) || 0,
      withdrawalRatePct: parseFloat(document.getElementById('withdrawalRatePct').value) || 4,
      yearsAlreadyWorked,
      historicalEarningsRatio,
      postStopAnnualEarnings,
      pastEarningsAmounts,
      eligYear: eligibilityYear(birthYear),
      fra: getFRA(birthYear)
    };
  }

  function resolveTargetPia(inputs) {
    if (!inputs.ssaBenefitMonthly || inputs.ssaBenefitMonthly <= 0) return null;
    if (inputs.ssaBenefitBasis === 'claimingAge') {
      const f = claimingFactor(inputs.birthYear, inputs.claimingAge);
      return f > 0 ? inputs.ssaBenefitMonthly / f : inputs.ssaBenefitMonthly;
    }
    return inputs.ssaBenefitMonthly;
  }

  function earningsListForStop(inputs, stopAge, calibratedH, scale) {
    const future = projectEarnings(
      inputs.currentAge,
      stopAge,
      inputs.currentAnnualEarnings,
      inputs.earningsGrowthRatePct,
      inputs.postStopAnnualEarnings,
      inputs.claimingAge
    );
    const nPast = Math.min(35, Math.max(0, Math.round(inputs.yearsAlreadyWorked)));
    const knownPast = (inputs.pastEarningsAmounts || []).filter((v) => v > 0);
    const nKnown = Math.min(knownPast.length, nPast);
    const nSynth = Math.max(0, nPast - nKnown);
    const s = scale != null ? scale : 1;
    const past = knownPast.slice(0, nKnown).map((v) => v * s);
    const H = calibratedH != null
      ? calibratedH
      : inputs.currentAnnualEarnings * (inputs.historicalEarningsRatio / 100);
    for (let i = 0; i < nSynth; i++) past.push(H);
    return { list: past.concat(future), futureYears: future.length, H };
  }

  function runScenario(inputs, stopAge, calibratedH, scale) {
    const built = earningsListForStop(inputs, stopAge, calibratedH, scale);
    const result = piaFromEarningsList(built.list, inputs.eligYear);
    const monthly = calculateMonthlyBenefit(result.pia, inputs.birthYear, inputs.claimingAge);
    return {
      stopAge,
      pia: result.pia,
      aime: result.aime,
      monthly,
      futureYears: built.futureYears,
      H: built.H,
      calibrated: calibratedH != null || scale != null
    };
  }

  function buildScenarios(inputs) {
    const targetPia = resolveTargetPia(inputs);
    const planStop = inputs.plannedRetirementAge;

    // Calibrate on plan path
    const planEst = estimateForStopAge(inputs, planStop, targetPia);
    const calibratedH = planEst.H != null ? planEst.H : null;
    const scale = planEst.scale != null ? planEst.scale : null;

    const plan = runScenario(inputs, planStop, calibratedH, scale);
    plan.label = 'As planned';

    // If we calibrated to SSA PIA, force plan PIA to statement (claiming applied)
    if (targetPia && planEst.calibrated) {
      plan.pia = targetPia;
      plan.aime = aimeFromPia(targetPia, inputs.eligYear);
      plan.monthly = calculateMonthlyBenefit(targetPia, inputs.birthYear, inputs.claimingAge);
      plan.calibrated = true;
    }

    const earlyYears = [1, 2, 5];
    const scenarios = [plan];
    const seen = new Set([roundAge(planStop)]);

    earlyYears.forEach((k) => {
      const stop = Math.max(inputs.currentAge, planStop - k);
      const key = roundAge(stop);
      if (seen.has(key)) return;
      seen.add(key);
      const s = runScenario(inputs, stop, calibratedH, scale);
      s.label = k + ' year' + (k > 1 ? 's' : '') + ' early';
      s.yearsEarly = k;
      scenarios.push(s);
    });

    // Always include actual stop age
    const actualKey = roundAge(inputs.actualStopAge);
    if (!seen.has(actualKey)) {
      const s = runScenario(inputs, inputs.actualStopAge, calibratedH, scale);
      s.label = 'Your actual stop';
      s.yearsEarly = Math.max(0, planStop - inputs.actualStopAge);
      s.isActual = true;
      scenarios.push(s);
    } else {
      const match = scenarios.find((s) => roundAge(s.stopAge) === actualKey);
      if (match) match.isActual = true;
    }

    const actual = runScenario(inputs, inputs.actualStopAge, calibratedH, scale);
    actual.label = 'Your actual stop';
    actual.isActual = true;

    return { plan, actual, scenarios, targetPia, calibratedH };
  }

  function roundAge(a) {
    return Math.round(a * 10) / 10;
  }

  function validate(inputs) {
    const errors = [];
    if (inputs.currentAge == null || inputs.currentAge < 18) {
      errors.push('Enter a valid birth date.');
    }
    if (inputs.actualStopAge > inputs.plannedRetirementAge) {
      errors.push('Actual stop age should be on or before planned retirement age.');
    }
    if (inputs.actualStopAge < inputs.currentAge) {
      errors.push('Actual stop age should be at or after your current age.');
    }
    if (inputs.claimingAge < 62 || inputs.claimingAge > 70) {
      errors.push('Claiming age must be between 62 and 70.');
    }
    if (inputs.currentAnnualEarnings < 0) {
      errors.push('Current earnings must be zero or greater.');
    }
    return errors;
  }

  function displayResults(inputs, built) {
    const { plan, actual, scenarios, targetPia } = built;
    const deltaMo = plan.monthly - actual.monthly;
    const lifePlan = lifetimeTotal(plan.monthly, inputs.claimingAge, inputs.lifeExpectancy, inputs.colaRatePct);
    const lifeActual = lifetimeTotal(actual.monthly, inputs.claimingAge, inputs.lifeExpectancy, inputs.colaRatePct);
    const deltaLife = lifePlan - lifeActual;
    const nestEgg = deltaMo > 0
      ? (deltaMo * 12) / (inputs.withdrawalRatePct / 100)
      : 0;

    const fra = inputs.fra;
    const fraStr = fra.years + (fra.months > 0 ? ' + ' + fra.months + ' mo' : '');

    document.getElementById('summaryCards').innerHTML = `
      <div class="summary-card">
        <div class="summary-label">Monthly reduction</div>
        <div class="summary-value">${deltaMo >= 0 ? '−' : '+'}${formatCurrency(Math.abs(deltaMo))}</div>
        <div class="summary-sub">vs working until ${plan.stopAge}</div>
      </div>
      <div class="summary-card">
        <div class="summary-label">Lifetime hit (to ${inputs.lifeExpectancy})</div>
        <div class="summary-value">${deltaLife >= 0 ? '−' : '+'}${formatCurrency(Math.abs(deltaLife))}</div>
        <div class="summary-sub">With ${inputs.colaRatePct}% COLA</div>
      </div>
      <div class="summary-card">
        <div class="summary-label">Extra nest egg @ ${inputs.withdrawalRatePct}%</div>
        <div class="summary-value">${formatCurrency(nestEgg)}</div>
        <div class="summary-sub">To replace the monthly cut</div>
      </div>
      <div class="summary-card">
        <div class="summary-label">Benefit if you stop at ${actual.stopAge}</div>
        <div class="summary-value">${formatCurrency(actual.monthly)}</div>
        <div class="summary-sub">vs ${formatCurrency(plan.monthly)} as planned · claim @ ${inputs.claimingAge}</div>
      </div>
    `;

    // Bar chart — unique stop ages, highlight actual
    const chartScenarios = scenarios
      .slice()
      .sort((a, b) => b.stopAge - a.stopAge);

    const labels = chartScenarios.map((s) => {
      let t = 'Stop ' + s.stopAge;
      if (s.label && s.label !== 'As planned') t += ' (' + s.label + ')';
      else if (s.stopAge === plan.stopAge) t += ' (as planned)';
      return t;
    });
    const data = chartScenarios.map((s) => Math.round(s.monthly));
    const colors = chartScenarios.map((s) => {
      if (s.isActual || roundAge(s.stopAge) === roundAge(actual.stopAge)) return 'rgb(220, 38, 38)';
      if (roundAge(s.stopAge) === roundAge(plan.stopAge)) return 'rgb(37, 99, 235)';
      return 'rgb(100, 116, 139)';
    });

    const ctx1 = document.getElementById('scenarioBarChart');
    if (scenarioBarChart) scenarioBarChart.destroy();
    scenarioBarChart = new Chart(ctx1, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Monthly benefit at claim',
          data,
          backgroundColor: colors
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => formatCurrency(ctx.raw) + '/mo'
            }
          }
        },
        scales: {
          x: {
            ticks: {
              callback: (v) => '$' + Number(v).toLocaleString()
            }
          }
        }
      }
    });

    // Waterfall-style: baseline → early-exit
    const ctx2 = document.getElementById('waterfallChart');
    if (waterfallChart) waterfallChart.destroy();
    waterfallChart = new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: [
          'SSA / plan baseline',
          'Lost high-earnings years',
          'Early-exit benefit'
        ],
        datasets: [{
          label: 'Monthly $',
          data: [
            Math.round(plan.monthly),
            Math.round(-deltaMo),
            Math.round(actual.monthly)
          ],
          backgroundColor: [
            'rgb(37, 99, 235)',
            'rgb(245, 158, 11)',
            'rgb(220, 38, 38)'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const v = ctx.raw;
                if (ctx.dataIndex === 1) return 'Reduction: ' + formatCurrency(Math.abs(v));
                return formatCurrency(v) + '/mo';
              }
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: (v) => '$' + Number(v).toLocaleString()
            }
          }
        }
      }
    });

    const yearsEarly = Math.max(0, inputs.plannedRetirementAge - inputs.actualStopAge);
    let interpretation = '<h3>What this means</h3><ul>';
    interpretation += `<li><strong>Your Full Retirement Age is ${fraStr}.</strong> Claiming age in this model is held at ${inputs.claimingAge} for every work-stop scenario.</li>`;

    if (deltaMo > 5) {
      interpretation += `<li><strong>Stopping at ${actual.stopAge} instead of ${plan.stopAge}</strong> (about ${yearsEarly.toFixed(1)} years earlier) reduces the estimated benefit by about <strong>${formatCurrency(deltaMo)}/month</strong> at your claiming age.</li>`;
      interpretation += `<li><strong>To replace that cut</strong> at a ${inputs.withdrawalRatePct}% withdrawal rate, you would need roughly <strong>${formatCurrency(nestEgg)}</strong> more in savings.</li>`;
      interpretation += `<li><strong>Over a lifetime to age ${inputs.lifeExpectancy}</strong> (with COLA), the difference is about <strong>${formatCurrency(deltaLife)}</strong>.</li>`;
    } else if (deltaMo < -5) {
      interpretation += `<li>Under these inputs, the early-stop path did not show a material reduction (or slightly increased the estimate). Check ages, earnings, and years already worked.</li>`;
    } else {
      interpretation += `<li>Under these assumptions, stopping at ${actual.stopAge} vs ${plan.stopAge} has little effect on the estimated benefit — often true if future years would not displace lower years in your top 35, or if you are already near the wage base.</li>`;
    }

    if (!targetPia) {
      interpretation += '<li><strong>No SSA statement amount was entered</strong>, so absolute benefit levels are a rough synthetic estimate. Relative year-by-year differences are still useful for direction.</li>';
    } else if (plan.calibrated) {
      interpretation += '<li>The model was <strong>calibrated</strong> so the “work as planned” path matches your SSA estimate, then future high-earning years were removed for earlier stop ages.</li>';
    }

    interpretation += '<li>This is an <strong>estimate</strong>, not an official SSA projection. It ignores disability, WEP/GPO, spouse/survivor benefits, and exact wage indexing of your full earnings record.</li>';
    interpretation += '</ul>';

    const claimUrl = (typeof RBUrlPrefill !== 'undefined')
      ? RBUrlPrefill.buildUrl('../social-security-claiming-analyzer/', {
          monthlyPIA: Math.round(actual.pia),
          birthDate: inputs.birthDate,
          claimAgeB: inputs.claimingAge
        })
      : '../social-security-claiming-analyzer/';

    const gapUrl = (typeof RBUrlPrefill !== 'undefined')
      ? RBUrlPrefill.buildUrl('../ss-gap/', {
          ssIncome: Math.round(actual.monthly)
        })
      : '../ss-gap/';

    interpretation += `<div class="deep-links">
      <a href="${claimUrl}">Open Claiming Analyzer with ${formatCurrency(actual.pia)} PIA</a>
      <a href="${gapUrl}">Open Spending Gap with ${formatCurrency(actual.monthly)}/mo</a>
    </div>`;

    document.getElementById('interpretation').innerHTML = interpretation;

    const bp = getBendPoints(inputs.eligYear);
    document.getElementById('howEstimated').innerHTML = `
      <p><strong>Method:</strong> Approximate highest-35 earnings (AIME → PIA bend points), calibrated${targetPia ? ' to your SSA estimate' : ' from a synthetic career'}, then remove future earnings when you stop earlier.</p>
      <ul>
        <li>Eligibility year (age 62): <strong>${inputs.eligYear}</strong> — bend points $${bp.b1.toLocaleString()} / $${bp.b2.toLocaleString()}</li>
        <li>Implied AIME (plan path): <strong>${formatCurrency(plan.aime)}</strong>/mo of indexed earnings</li>
        <li>Years already worked (model): <strong>${inputs.yearsAlreadyWorked}</strong> · Historical ratio: <strong>${inputs.historicalEarningsRatio}%</strong></li>
        <li>Calibrated past-year level (H): <strong>${plan.H != null ? formatCurrency(plan.H) : 'n/a'}</strong></li>
        <li>Future years under plan / actual stop: <strong>${plan.futureYears}</strong> / <strong>${actual.futureYears}</strong></li>
        <li>Mode: <strong>${inputs.mode}</strong></li>
      </ul>
      <p><strong>Limitations:</strong> No full earnings history or true AWI indexing; taxable maximum approximated for future years; no WEP/GPO, disability, or family benefits. Best used for direction (“small vs material”), not dollar-perfect planning.</p>
    `;

    const tbody = document.getElementById('tableBody');
    const rows = chartScenarios.slice().sort((a, b) => b.stopAge - a.stopAge);
    tbody.innerHTML = rows.map((s) => {
      const d = plan.monthly - s.monthly;
      const egg = d > 0 ? (d * 12) / (inputs.withdrawalRatePct / 100) : 0;
      const highlight = s.isActual || roundAge(s.stopAge) === roundAge(actual.stopAge)
        ? ' style="font-weight:700;background:#fef2f2;"'
        : '';
      return `<tr${highlight}>
        <td>${s.stopAge}${s.isActual ? ' ★' : ''}</td>
        <td>${formatCurrency(s.pia)}</td>
        <td>${formatCurrency(s.monthly)}</td>
        <td>${d === 0 ? '—' : (d > 0 ? '−' : '+') + formatCurrency(Math.abs(d))}</td>
        <td>${egg > 0 ? formatCurrency(egg) : '—'}</td>
      </tr>`;
    }).join('');

    document.getElementById('results').style.display = 'block';
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

    lastResults = {
      inputs,
      plan,
      actual,
      scenarios,
      deltaMo,
      deltaLife,
      nestEgg,
      targetPia
    };
  }

  function setMode(mode) {
    document.getElementById('mode').value = mode;
    document.body.classList.toggle('mode-advanced', mode === 'advanced');
    document.getElementById('modeQuickBtn').classList.toggle('active', mode === 'quick');
    document.getElementById('modeAdvancedBtn').classList.toggle('active', mode === 'advanced');
  }

  function addEarningsRow(year, amount) {
    const list = document.getElementById('pastEarningsList');
    const row = document.createElement('div');
    row.className = 'earnings-row';
    const y = year || new Date().getFullYear() - 1;
    row.innerHTML = `
      <div>
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Year</label>
        <input type="number" class="past-earnings-year" min="1951" max="2100" value="${y}" style="width:100%;padding:8px;">
      </div>
      <div>
        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Amount ($)</label>
        <input type="number" class="past-earnings-amount" min="0" step="any" value="${amount != null ? amount : ''}" style="width:100%;padding:8px;">
      </div>
      <button type="button" class="btn-secondary remove-earnings-row" style="padding:8px 12px;">Remove</button>
    `;
    row.querySelector('.remove-earnings-row').addEventListener('click', () => row.remove());
    list.appendChild(row);
  }

  function syncYearsAlreadyWorkedDefault(age) {
    const el = document.getElementById('yearsAlreadyWorked');
    if (!el || document.getElementById('mode').value === 'advanced') return;
    if (age != null) el.value = Math.min(40, Math.max(0, age - 22));
  }

  window.onBirthDateSynced = function (_date, age) {
    syncYearsAlreadyWorkedDefault(age);
  };

  document.getElementById('modeQuickBtn').addEventListener('click', () => setMode('quick'));
  document.getElementById('modeAdvancedBtn').addEventListener('click', () => setMode('advanced'));
  document.getElementById('addEarningsYearBtn').addEventListener('click', () => addEarningsRow());

  document.getElementById('earlyExitForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const inputs = collectInputs();
    const errors = validate(inputs);
    if (errors.length) {
      alert(errors.join('\n'));
      return;
    }
    const built = buildScenarios(inputs);
    displayResults(inputs, built);
  });

  // --- Premium: save / load / compare / PDF / CSV / explain ---
  function apiBase() {
    const path = window.location.pathname || '';
    return path.indexOf('/ss-early-exit') !== -1 ? '..' : '';
  }

  function scenarioDisplayName(scenario) {
    return (scenario && (scenario.scenario_name || scenario.name)) || 'Untitled scenario';
  }

  function gatherScenarioData() {
    return {
      mode: document.getElementById('mode').value,
      birthDate: document.getElementById('birthDate').value,
      plannedRetirementAge: document.getElementById('plannedRetirementAge').value,
      actualStopAge: document.getElementById('actualStopAge').value,
      claimingAge: document.getElementById('claimingAge').value,
      currentAnnualEarnings: document.getElementById('currentAnnualEarnings').value,
      earningsGrowthRatePct: document.getElementById('earningsGrowthRatePct').value,
      ssaBenefitMonthly: document.getElementById('ssaBenefitMonthly').value,
      ssaBenefitBasis: document.getElementById('ssaBenefitBasis').value,
      yearsAlreadyWorked: document.getElementById('yearsAlreadyWorked').value,
      historicalEarningsRatio: document.getElementById('historicalEarningsRatio').value,
      postStopAnnualEarnings: document.getElementById('postStopAnnualEarnings').value,
      lifeExpectancy: document.getElementById('lifeExpectancy').value,
      colaRatePct: document.getElementById('colaRatePct').value,
      withdrawalRatePct: document.getElementById('withdrawalRatePct').value,
      results: lastResults ? {
        planMonthly: lastResults.plan.monthly,
        actualMonthly: lastResults.actual.monthly,
        planPia: lastResults.plan.pia,
        actualPia: lastResults.actual.pia,
        deltaMo: lastResults.deltaMo,
        deltaLife: lastResults.deltaLife,
        nestEgg: lastResults.nestEgg
      } : null
    };
  }

  function applyScenarioData(d) {
    if (!d) return;
    if (d.mode) setMode(d.mode);
    if (d.birthDate && typeof window.setBirthDateFromString === 'function') {
      window.setBirthDateFromString(d.birthDate);
    }
    const map = {
      plannedRetirementAge: 'plannedRetirementAge',
      actualStopAge: 'actualStopAge',
      claimingAge: 'claimingAge',
      currentAnnualEarnings: 'currentAnnualEarnings',
      earningsGrowthRatePct: 'earningsGrowthRatePct',
      ssaBenefitMonthly: 'ssaBenefitMonthly',
      ssaBenefitBasis: 'ssaBenefitBasis',
      yearsAlreadyWorked: 'yearsAlreadyWorked',
      historicalEarningsRatio: 'historicalEarningsRatio',
      postStopAnnualEarnings: 'postStopAnnualEarnings',
      lifeExpectancy: 'lifeExpectancy',
      colaRatePct: 'colaRatePct',
      withdrawalRatePct: 'withdrawalRatePct'
    };
    Object.keys(map).forEach((k) => {
      if (d[k] != null && document.getElementById(map[k])) {
        document.getElementById(map[k]).value = d[k];
      }
    });
  }

  function inputsFromSaved(d) {
    const birthDate = d.birthDate || '1965-01-15';
    const birthYear = parseInt(String(birthDate).substr(0, 4), 10);
    const currentAge = ageFromBirthDate(birthDate) || 60;
    const mode = d.mode || 'quick';
    let yearsAlreadyWorked = parseInt(d.yearsAlreadyWorked, 10);
    if (!yearsAlreadyWorked || mode === 'quick') {
      yearsAlreadyWorked = Math.min(40, Math.max(0, currentAge - 22));
    }
    let historicalEarningsRatio = parseFloat(d.historicalEarningsRatio);
    if (!historicalEarningsRatio) historicalEarningsRatio = 65;
    return {
      mode,
      birthDate,
      birthYear,
      currentAge,
      plannedRetirementAge: parseFloat(d.plannedRetirementAge),
      actualStopAge: parseFloat(d.actualStopAge),
      claimingAge: parseFloat(d.claimingAge),
      currentAnnualEarnings: parseFloat(d.currentAnnualEarnings),
      earningsGrowthRatePct: parseFloat(d.earningsGrowthRatePct) || 0,
      ssaBenefitMonthly: parseFloat(d.ssaBenefitMonthly) || 0,
      ssaBenefitBasis: d.ssaBenefitBasis || 'fra',
      lifeExpectancy: parseInt(d.lifeExpectancy, 10) || 85,
      colaRatePct: parseFloat(d.colaRatePct) || 2.5,
      withdrawalRatePct: parseFloat(d.withdrawalRatePct) || 4,
      yearsAlreadyWorked,
      historicalEarningsRatio,
      postStopAnnualEarnings: parseFloat(d.postStopAnnualEarnings) || 0,
      pastEarningsAmounts: [],
      eligYear: eligibilityYear(birthYear),
      fra: getFRA(birthYear)
    };
  }

  function computeSavedScenario(d) {
    const inputs = inputsFromSaved(d);
    const built = buildScenarios(inputs);
    const lifePlan = lifetimeTotal(built.plan.monthly, inputs.claimingAge, inputs.lifeExpectancy, inputs.colaRatePct);
    const lifeActual = lifetimeTotal(built.actual.monthly, inputs.claimingAge, inputs.lifeExpectancy, inputs.colaRatePct);
    const deltaMo = built.plan.monthly - built.actual.monthly;
    const nestEgg = deltaMo > 0 ? (deltaMo * 12) / (inputs.withdrawalRatePct / 100) : 0;
    return {
      inputs,
      plan: built.plan,
      actual: built.actual,
      deltaMo,
      deltaLife: lifePlan - lifeActual,
      nestEgg
    };
  }

  function exportPayload() {
    if (!lastResults) return null;
    const r = lastResults;
    const wdr = r.inputs.withdrawalRatePct;
    const scenarios = (r.scenarios || []).map((s) => {
      const vs = r.plan.monthly - s.monthly;
      return {
        stopAge: s.stopAge,
        label: s.label || '',
        pia: Math.round(s.pia * 100) / 100,
        monthly: Math.round(s.monthly * 100) / 100,
        vsPlan: Math.round(vs * 100) / 100,
        nestEgg: vs > 0 ? Math.round((vs * 12) / (wdr / 100)) : 0,
        isActual: !!s.isActual || roundAge(s.stopAge) === roundAge(r.actual.stopAge)
      };
    });
    return {
      birthDate: r.inputs.birthDate,
      fra: r.inputs.fra,
      plannedRetirementAge: r.inputs.plannedRetirementAge,
      actualStopAge: r.inputs.actualStopAge,
      claimingAge: r.inputs.claimingAge,
      currentAnnualEarnings: r.inputs.currentAnnualEarnings,
      earningsGrowthRatePct: r.inputs.earningsGrowthRatePct,
      ssaBenefitMonthly: r.inputs.ssaBenefitMonthly,
      lifeExpectancy: r.inputs.lifeExpectancy,
      colaRatePct: r.inputs.colaRatePct,
      withdrawalRatePct: wdr,
      planMonthly: r.plan.monthly,
      actualMonthly: r.actual.monthly,
      planPia: r.plan.pia,
      actualPia: r.actual.pia,
      deltaMo: r.deltaMo,
      deltaLife: r.deltaLife,
      nestEgg: r.nestEgg,
      scenarios
    };
  }

  function downloadBlob(blob, filename) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function fetchBlob(url, payload) {
    return fetch(apiBase() + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then((res) => {
      if (!res.ok) {
        return res.text().then((t) => {
          let msg = 'Request failed';
          try {
            const j = JSON.parse(t);
            if (j && j.error) msg = j.error;
          } catch (e) {
            if (t) msg = t;
          }
          throw new Error(msg);
        });
      }
      return res.blob();
    });
  }

  function showComparison(name1, name2, r1, r2) {
    const resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';
    const existing = document.getElementById('scenarioCompareBanner');
    if (existing) existing.remove();

    const html = `
      <div id="scenarioCompareBanner" style="background:#fef3c7;border:2px solid #f59e0b;border-radius:8px;padding:20px;margin-bottom:30px;">
        <h2 style="margin-top:0;color:#92400e;">⚖️ Scenario Comparison</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <div>
            <h3 style="color:#2563eb;">${name1}</h3>
            <div style="font-size:0.95em;color:#444;line-height:1.55;">
              <div>Stop ${r1.inputs.actualStopAge} vs plan ${r1.inputs.plannedRetirementAge} · Claim @ ${r1.inputs.claimingAge}</div>
              <div>Plan benefit: <strong>${formatCurrency(r1.plan.monthly)}</strong>/mo</div>
              <div>Early-exit benefit: <strong>${formatCurrency(r1.actual.monthly)}</strong>/mo</div>
              <div>Monthly reduction: <strong>${formatCurrency(r1.deltaMo)}</strong></div>
              <div>Lifetime hit: <strong>${formatCurrency(r1.deltaLife)}</strong></div>
              <div>Extra nest egg: <strong>${formatCurrency(r1.nestEgg)}</strong></div>
            </div>
          </div>
          <div>
            <h3 style="color:#dc2626;">${name2}</h3>
            <div style="font-size:0.95em;color:#444;line-height:1.55;">
              <div>Stop ${r2.inputs.actualStopAge} vs plan ${r2.inputs.plannedRetirementAge} · Claim @ ${r2.inputs.claimingAge}</div>
              <div>Plan benefit: <strong>${formatCurrency(r2.plan.monthly)}</strong>/mo</div>
              <div>Early-exit benefit: <strong>${formatCurrency(r2.actual.monthly)}</strong>/mo</div>
              <div>Monthly reduction: <strong>${formatCurrency(r2.deltaMo)}</strong></div>
              <div>Lifetime hit: <strong>${formatCurrency(r2.deltaLife)}</strong></div>
              <div>Extra nest egg: <strong>${formatCurrency(r2.nestEgg)}</strong></div>
            </div>
          </div>
        </div>
      </div>`;
    resultsDiv.insertAdjacentHTML('afterbegin', html);
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
  }

  function buildExplainSummary() {
    const r = lastResults;
    if (!r) return '';
    let summary = 'Early Exit Social Security Impact results.\n\n';
    summary += 'Planned stop age: ' + r.plan.stopAge + '. Actual stop age: ' + r.actual.stopAge + '.\n';
    summary += 'Claiming age: ' + r.inputs.claimingAge + '.\n';
    summary += 'Plan benefit: ' + formatCurrency(r.plan.monthly) + '/mo. Early-exit benefit: ' + formatCurrency(r.actual.monthly) + '/mo.\n';
    summary += 'Monthly reduction: ' + formatCurrency(r.deltaMo) + '. Lifetime hit: ' + formatCurrency(r.deltaLife) + '.\n';
    summary += 'Extra nest egg at ' + r.inputs.withdrawalRatePct + '%: ' + formatCurrency(r.nestEgg) + '.\n';
    summary += 'This is an approximate model of highest-35 earnings impact, not an SSA projection.\n';
    return summary;
  }

  async function explainResults() {
    if (!lastResults) {
      alert('Calculate results first (click Estimate Impact).');
      return;
    }
    const summary = buildExplainSummary();
    const btn = document.getElementById('explainResultsBtnInResults');
    const orig = btn ? btn.textContent : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Loading…';
    }
    try {
      const res = await fetch(apiBase() + '/api/explain_results.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          calculator_type: 'ss-early-exit',
          results_summary: summary
        })
      });
      const data = await res.json();
      if (data.explanation && typeof showExplainModal === 'function') {
        showExplainModal(data.explanation, {
          calculatorType: 'ss-early-exit',
          resultsSummary: summary
        });
      } else {
        alert(data.error || 'Could not generate explanation.');
      }
    } catch (err) {
      alert('Explain request failed.');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = orig || '🤖 Explain my results';
      }
    }
  }

  const saveBtn = document.getElementById('saveScenarioBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      const name = prompt('Scenario name:');
      if (!name) return;
      const status = document.getElementById('saveStatus');
      try {
        const res = await fetch(apiBase() + '/api/save_scenario.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            calculator_type: 'ss-early-exit',
            scenario_name: name,
            scenario_data: gatherScenarioData()
          })
        });
        const data = await res.json();
        if (status) status.textContent = data.success ? 'Saved.' : (data.error || 'Save failed');
      } catch (err) {
        if (status) status.textContent = 'Save failed';
      }
    });
  }

  const loadBtn = document.getElementById('loadScenarioBtn');
  if (loadBtn) {
    loadBtn.addEventListener('click', async () => {
      const status = document.getElementById('saveStatus');
      try {
        const res = await fetch(apiBase() + '/api/load_scenarios.php?calculator_type=ss-early-exit');
        const data = await res.json();
        if (!data.success || !data.scenarios || !data.scenarios.length) {
          if (status) status.textContent = 'No saved scenarios.';
          return;
        }
        const list = data.scenarios.map((s, i) => (i + 1) + '. ' + scenarioDisplayName(s)).join('\n');
        const choice = prompt('Load which scenario?\n' + list + '\n\nEnter number:');
        const idx = parseInt(choice, 10) - 1;
        if (isNaN(idx) || idx < 0 || idx >= data.scenarios.length) return;
        let parsed = data.scenarios[idx].data != null ? data.scenarios[idx].data : data.scenarios[idx].scenario_data;
        if (typeof parsed === 'string') parsed = JSON.parse(parsed);
        applyScenarioData(parsed);
        if (status) status.textContent = 'Loaded. Click Estimate Impact.';
      } catch (err) {
        if (status) status.textContent = 'Load failed';
      }
    });
  }

  const compareBtn = document.getElementById('compareScenariosBtn');
  if (compareBtn) {
    compareBtn.addEventListener('click', async () => {
      try {
        const res = await fetch(apiBase() + '/api/load_scenarios.php?calculator_type=ss-early-exit');
        const data = await res.json();
        if (!data.success) {
          alert('Error: ' + (data.error || 'Could not load scenarios'));
          return;
        }
        if (!data.scenarios || data.scenarios.length < 2) {
          alert('Save at least 2 scenarios first, then compare.');
          return;
        }
        const list = data.scenarios.map((s, i) => (i + 1) + '. ' + scenarioDisplayName(s)).join('\n');
        const choice = prompt('Select TWO scenarios to compare:\n\n' + list + '\n\nEnter two numbers (e.g. 1,2):');
        if (!choice) return;
        const parts = choice.split(',').map((s) => parseInt(s.trim(), 10) - 1);
        if (parts.length !== 2 || parts[0] === parts[1] ||
            parts.some((p) => isNaN(p) || p < 0 || p >= data.scenarios.length)) {
          alert('Invalid selection. Enter two different numbers (e.g. 1,2).');
          return;
        }
        const d1 = data.scenarios[parts[0]].data;
        const d2 = data.scenarios[parts[1]].data;
        const r1 = computeSavedScenario(d1);
        const r2 = computeSavedScenario(d2);
        showComparison(
          scenarioDisplayName(data.scenarios[parts[0]]),
          scenarioDisplayName(data.scenarios[parts[1]]),
          r1,
          r2
        );
      } catch (err) {
        alert('Compare failed.');
      }
    });
  }

  const pdfBtn = document.getElementById('downloadPdfBtn');
  if (pdfBtn) {
    pdfBtn.addEventListener('click', () => {
      const payload = exportPayload();
      if (!payload) {
        alert('Calculate results first (click Estimate Impact).');
        return;
      }
      const canvas = document.getElementById('scenarioBarChart');
      payload.chartImage = (canvas && scenarioBarChart) ? canvas.toDataURL('image/png') : null;
      fetchBlob('/api/generate_ss_early_exit_pdf.php', payload)
        .then((blob) => downloadBlob(blob, 'SS_Early_Exit_Report_' + new Date().toISOString().split('T')[0] + '.pdf'))
        .catch((e) => alert('Download PDF: ' + e.message));
    });
  }

  const csvBtn = document.getElementById('downloadCsvBtn');
  if (csvBtn) {
    csvBtn.addEventListener('click', () => {
      const payload = exportPayload();
      if (!payload) {
        alert('Calculate results first (click Estimate Impact).');
        return;
      }
      fetchBlob('/api/export_ss_early_exit_csv.php', payload)
        .then((blob) => downloadBlob(blob, 'SS_Early_Exit_' + new Date().toISOString().split('T')[0] + '.csv'))
        .catch((e) => alert('Export CSV: ' + e.message));
    });
  }

  const summaryBtn = document.getElementById('downloadSummaryBtn');
  if (summaryBtn) {
    summaryBtn.addEventListener('click', () => {
      const payload = exportPayload();
      if (!payload) {
        alert('Calculate results first (click Estimate Impact).');
        return;
      }
      fetchBlob('/api/generate_ss_early_exit_summary_pdf.php', payload)
        .then((blob) => downloadBlob(blob, 'SS_Early_Exit_Summary_' + new Date().toISOString().split('T')[0] + '.pdf'))
        .catch((e) => alert('Impact Summary: ' + e.message));
    });
  }

  const explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) {
    explainBtn.addEventListener('click', explainResults);
  }

  // URL prefill
  if (typeof RBUrlPrefill !== 'undefined') {
    RBUrlPrefill.applyFromUrl({
      birthDate: function (v) {
        if (typeof window.setBirthDateFromString === 'function') window.setBirthDateFromString(v);
      },
      plannedRetirementAge: 'plannedRetirementAge',
      actualStopAge: 'actualStopAge',
      claimingAge: 'claimingAge',
      currentAnnualEarnings: 'currentAnnualEarnings',
      ssaBenefitMonthly: 'ssaBenefitMonthly',
      mode: function (v) {
        if (v === 'advanced' || v === 'quick') setMode(v);
      }
    }, {
      formId: 'earlyExitForm',
      autoSubmit: false
    });
  }

  setMode('quick');
  // Seed one optional earnings row for advanced UX discoverability (empty amount)
  // Not added until Advanced — keep list empty initially.
})();
