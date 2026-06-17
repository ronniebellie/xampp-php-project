(function () {
  'use strict';

  var FC = window.RBFinance;
  var PE = window.RBPlanEngine;
  var MC = window.RBMonteCarlo;
  var fmt = FC.formatCurrency;
  var planChart = null;
  var mcChart = null;
  var lastResult = null;
  var lastMcResult = null;
  var lastInputs = null;

  var API_BASE = (function () {
    var path = window.location.pathname;
    var match = path.match(/^(.*\/)retirement-plan\/?/);
    var basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
  })();

  var CALCULATOR_TYPE = 'retirement-plan';

  function el(id) {
    return document.getElementById(id);
  }

  function readNumber(id, fallback) {
    var node = el(id);
    if (!node) return fallback || 0;
    var n = parseFloat(String(node.value).replace(/[^0-9.-]/g, ''));
    return isNaN(n) ? (fallback || 0) : n;
  }

  function currentCalendarYear() {
    return new Date().getFullYear();
  }

  function ageFromBirthYear(birthYear) {
    return currentCalendarYear() - birthYear;
  }

  function isAlreadyRetired() {
    var retired = el('alreadyRetired');
    return !!(retired && retired.checked);
  }

  function syncSpendingMode() {
    var method = document.querySelector('input[name="spendingMethod"]:checked');
    var useEstimate = method && method.value === 'estimate';
    var directWrap = el('directSpendingWrap');
    var estimateWrap = el('estimateSpendingWrap');
    if (directWrap) directWrap.style.display = useEstimate ? 'none' : 'block';
    if (estimateWrap) estimateWrap.style.display = useEstimate ? 'grid' : 'none';
  }

  function syncSsReceivingMode() {
    var ssReceiving = el('ssAlreadyReceiving');
    var spouseReceiving = el('spouseSsAlreadyReceiving');
    var ssEstimate = el('ssEstimateWrap');
    var ssReceivingWrap = el('ssReceivingWrap');
    var spouseEstimate = el('spouseSsEstimateWrap');
    var spouseReceivingWrap = el('spouseSsReceivingWrap');

    if (ssReceiving && ssEstimate && ssReceivingWrap) {
      var showCurrent = ssReceiving.checked;
      ssEstimate.style.display = showCurrent ? 'none' : 'grid';
      ssReceivingWrap.style.display = showCurrent ? 'grid' : 'none';
    }
    if (spouseReceiving && spouseEstimate && spouseReceivingWrap) {
      var showSpouseCurrent = spouseReceiving.checked;
      spouseEstimate.style.display = showSpouseCurrent ? 'none' : 'grid';
      spouseReceivingWrap.style.display = showSpouseCurrent ? 'grid' : 'none';
    }
  }

  function syncRetiredState() {
    var retired = el('alreadyRetired');
    var retirementAge = el('retirementAge');
    var retirementAgeWrap = el('retirementAgeWrap');
    var contribution = el('annualContribution');
    var contributionWrap = el('annualContributionWrap');
    var returnPreWrap = el('returnPreRetirementWrap');
    var pct = el('retirementSpendingPct');
    if (!retired) return;
    if (retired.checked) {
      if (retirementAgeWrap) retirementAgeWrap.style.display = 'none';
      if (retirementAge) {
        retirementAge.value = ageFromBirthYear(readNumber('birthYear'));
        retirementAge.disabled = true;
      }
      if (contribution) {
        contribution.value = 0;
        contribution.disabled = true;
      }
      if (contributionWrap) contributionWrap.style.display = 'none';
      if (returnPreWrap) returnPreWrap.style.display = 'none';
      if (pct) {
        pct.value = 100;
        pct.disabled = true;
      }
      var ssReceiving = el('ssAlreadyReceiving');
      var spouseReceiving = el('spouseSsAlreadyReceiving');
      if (ssReceiving && !ssReceiving.dataset.userTouched) ssReceiving.checked = true;
      if (spouseReceiving && !spouseReceiving.dataset.userTouched) spouseReceiving.checked = true;
      syncSsReceivingMode();
    } else {
      if (retirementAgeWrap) retirementAgeWrap.style.display = '';
      if (retirementAge) retirementAge.disabled = false;
      if (contribution) contribution.disabled = false;
      if (contributionWrap) contributionWrap.style.display = '';
      if (returnPreWrap) returnPreWrap.style.display = '';
      if (pct) {
        pct.disabled = false;
        if (!pct.value) pct.value = 80;
      }
      syncBirthYearDerived();
      syncSsReceivingMode();
    }
  }

  function syncBirthYearDerived() {
    var birthYear = readNumber('birthYear', currentCalendarYear() - 60);
    var age = ageFromBirthYear(birthYear);
    var fra = FC.fraAgeFromBirthYear(birthYear);
    var fraHint = el('fraHint');
    var claimSelect = el('ssClaimAge');
    var retirementAge = el('retirementAge');

    if (fraHint) {
      fraHint.textContent =
        'You\'re about ' + age + ' today. Your Full Retirement Age is about ' +
        fra.toFixed(1).replace(/\.0$/, '') + '.';
    }
    if (claimSelect && !claimSelect.dataset.userTouched) {
      claimSelect.value = String(Math.round(fra));
    }
    if (retirementAge && !isAlreadyRetired()) {
      retirementAge.min = Math.max(18, age);
      if (readNumber('retirementAge') < age) retirementAge.value = age;
    }
  }

  function syncFraClaimAge() {
    syncBirthYearDerived();
  }

  function collectInputs() {
    var birthYear = readNumber('birthYear');
    var currentAge = ageFromBirthYear(birthYear);
    var retirementAge = isAlreadyRetired() ? currentAge : readNumber('retirementAge');
    var method = document.querySelector('input[name="spendingMethod"]:checked');
    var spendingMethod = method ? method.value : 'estimate';
    var baseAnnualSpending = 0;

    if (spendingMethod === 'direct') {
      baseAnnualSpending = readNumber('annualSpendingDirect');
    } else {
      var monthly = readNumber('currentMonthlySpending');
      var pct = readNumber('retirementSpendingPct', 80);
      baseAnnualSpending = monthly * 12 * (pct / 100);
    }

    return {
      currentAge: currentAge,
      retirementAge: retirementAge,
      planEndAge: 90,
      birthYear: birthYear,
      balance: readNumber('balance'),
      portfolioWithdrawalStartAge: (function () {
        var v = readNumber('portfolioWithdrawalStartAge', NaN);
        return isNaN(v) || v <= 0 ? retirementAge : v;
      })(),
      annualContribution: readNumber('annualContribution'),
      returnPreRetirement: FC.clamp(readNumber('returnPreRetirement', 6), 0, 15),
      returnRetirement: FC.clamp(readNumber('returnRetirement', 5), 0, 12),
      baseAnnualSpending: baseAnnualSpending,
      ssAlreadyReceiving: !!(el('ssAlreadyReceiving') && el('ssAlreadyReceiving').checked),
      ssCurrentMonthly: readNumber('ssCurrentMonthly'),
      ssPiaMonthly: readNumber('ssPiaMonthly'),
      ssClaimAge: parseInt(el('ssClaimAge').value, 10) || Math.round(FC.fraAgeFromBirthYear(birthYear)),
      spouseSsAlreadyReceiving: !!(el('spouseSsAlreadyReceiving') && el('spouseSsAlreadyReceiving').checked),
      spouseSsCurrentMonthly: readNumber('spouseSsCurrentMonthly'),
      spouseSsMonthly: readNumber('spouseSsMonthly'),
      spouseSsClaimAge: parseInt(el('spouseSsClaimAge').value, 10) || parseInt(el('ssClaimAge').value, 10) || 67,
      otherGuaranteedAnnual: readNumber('otherGuaranteedAnnual'),
      withdrawalRate: FC.clamp(readNumber('withdrawalRate', 4), 0.5, 10) / 100,
      inflation: FC.clamp(readNumber('inflation', 2.5), 0, 8),
      colaRate: FC.clamp(readNumber('colaRate', 2.5), 0, 8),
      filingStatus: el('filingStatus') ? el('filingStatus').value : 'married',
      taxDeferredPct: FC.clamp(readNumber('taxDeferredPct', 85), 0, 100),
      spouseIsBeneficiary: el('spouseBeneficiary') && el('spouseBeneficiary').value === 'yes',
      spouseAge: (el('spouseBeneficiary') && el('spouseBeneficiary').value === 'yes')
        ? readNumber('spouseAge', 0) || null
        : null,
      useStandardDeduction: true,
      volatilityPct: FC.clamp(readNumber('volatility', 12), 0, 50),
      numSims: FC.clamp(readNumber('simulations', 1000), 100, 5000)
    };
  }

  function validateInputs(inputs) {
    var errors = [];
    if (inputs.currentAge < 18 || inputs.currentAge > 100) errors.push('birth year (implies age 18–100)');
    if (!isAlreadyRetired() && inputs.retirementAge < inputs.currentAge) {
      errors.push('planned retirement age (must be at or after your current age)');
    }
    if (inputs.balance < 0) errors.push('retirement savings');
    if (inputs.baseAnnualSpending <= 0) errors.push('retirement spending');
    if (inputs.birthYear < 1900 || inputs.birthYear > new Date().getFullYear()) errors.push('birth year');
    if (inputs.portfolioWithdrawalStartAge < inputs.currentAge) {
      errors.push('portfolio withdrawal start age (must be at or after your current age)');
    }
    if (inputs.ssAlreadyReceiving) {
      if (inputs.ssCurrentMonthly <= 0) errors.push('your current monthly Social Security benefit');
    } else if (inputs.ssPiaMonthly <= 0) {
      errors.push('your Social Security benefit at full retirement age');
    }
    return errors;
  }

  function statusStyles(tone) {
    if (tone === 'good') return { bg: '#ecfdf3', border: '#4ade80', text: '#166534' };
    if (tone === 'warn') return { bg: '#fffbeb', border: '#fbbf24', text: '#92400e' };
    return { bg: '#fef2f2', border: '#f87171', text: '#991b1b' };
  }

  function renderSummary(result) {
    var s = result.summary;
    var card = el('statusCard');
    var styles = statusStyles(s.status.tone);
    card.style.background = styles.bg;
    card.style.border = '2px solid ' + styles.border;
    el('statusHeadline').style.color = styles.text;
    el('statusHeadline').textContent = s.status.headline;
    el('statusDetail').textContent = s.status.detail;

    var projectedLabel = el('metricProjectedLabel');
    var withdrawalsFuture = s.portfolioWithdrawalStartAge > lastInputs.currentAge;
    if (projectedLabel) {
      projectedLabel.textContent = withdrawalsFuture
        ? 'Portfolio today'
        : (lastInputs.currentAge === lastInputs.retirementAge ? 'Portfolio (current year)' : 'Projected at retirement');
    }
    el('metricProjected').textContent = withdrawalsFuture
      ? fmt(lastInputs.balance)
      : fmt(s.balanceAtRetirement);

    var portfolioNote = el('portfolioWithdrawalNote');
    if (portfolioNote) {
      if (withdrawalsFuture) {
        portfolioNote.style.display = 'block';
        portfolioNote.textContent =
          'Spending-gap withdrawals from your portfolio begin at age ' + s.portfolioWithdrawalStartAge +
          ' (projected balance then: ' + fmt(s.balanceAtWithdrawalStart) + '). Until then, only investment growth and any required RMDs affect the balance in this model.';
      } else {
        portfolioNote.style.display = 'none';
        portfolioNote.textContent = '';
      }
    }

    el('metricTarget').textContent = s.targetNestEgg > 0 ? fmt(s.targetNestEgg) : 'Not required';
    el('metricIncome').textContent = fmt(s.retirementAnnualIncome);
    el('metricLifetimeTax').textContent = fmt(s.lifetimeFederalTax);

    var rmdNote = el('rmdNote');
    if (s.firstRmdAmount > 0) {
      rmdNote.textContent = 'Estimated first RMD at age ' + s.rmdStartAge + ': ' + fmt(s.firstRmdAmount) +
        ' (on tax-deferred portion). Withdrawals never fall below the RMD once RMDs begin.';
    } else if (lastInputs.currentAge >= s.rmdStartAge) {
      rmdNote.textContent = 'RMD rules apply now based on your tax-deferred portfolio share.';
    } else {
      rmdNote.textContent = 'RMDs begin at age ' + s.rmdStartAge + ' on the tax-deferred portion of your portfolio.';
    }

    var depletedNote = el('depletedNote');
    if (s.depletedAge) {
      depletedNote.style.display = 'block';
      depletedNote.textContent = 'On this projection, portfolio withdrawals may exhaust savings around age ' + s.depletedAge + ' (before age ' + lastInputs.planEndAge + '). Premium users can see the full year-by-year path.';
    } else {
      depletedNote.style.display = 'block';
      depletedNote.textContent = 'Ending balance at age ' + lastInputs.planEndAge + ': ' + fmt(s.endingBalance) + '.';
    }
  }

  function renderMonteCarlo(deterministic, inputs) {
    var isPremium = typeof isPremiumUser !== 'undefined' && isPremiumUser;
    if (!isPremium || !MC) return;

    var mc = MC.runRetirementStressTest(inputs, deterministic, {
      expectedReturnPct: inputs.returnRetirement,
      volatilityPct: inputs.volatilityPct,
      numSims: inputs.numSims
    });
    lastMcResult = mc;

    var rateEl = el('mcSuccessRate');
    var summaryEl = el('mcSummaryText');
    if (rateEl) {
      rateEl.textContent = mc.successRate + '%';
      rateEl.style.color = mc.successRate >= 80 ? '#166534' : (mc.successRate >= 60 ? '#92400e' : '#991b1b');
    }
    if (summaryEl) {
      summaryEl.innerHTML =
        'Your plan lasted from age <strong>' + mc.startAge + '</strong> through <strong>' + inputs.planEndAge +
        '</strong> in <strong>' + mc.successRate + '%</strong> of ' + mc.numSims.toLocaleString() +
        ' simulations (starting portfolio ' + fmt(mc.startBalance) + ', return ' + mc.expectedReturnPct +
        '%, volatility ' + mc.volatilityPct + '%).<br><br>' +
        '<strong>Ending balance at age ' + inputs.planEndAge + ':</strong> 25th percentile = ' + fmt(mc.p25) +
        ', median = ' + fmt(mc.p50) + ', 75th = ' + fmt(mc.p75) + '. Negative endings mean the portfolio ran out.';
    }

    var canvas = el('mcDistributionChart');
    if (!canvas || typeof Chart === 'undefined') return;
    if (mcChart) mcChart.destroy();

    mcChart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: mc.histogram.labels,
        datasets: [{
          label: 'Simulations',
          data: mc.histogram.counts,
          backgroundColor: 'rgba(13, 148, 136, 0.55)',
          borderColor: '#0d9488',
          borderWidth: 1
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
              label: function (c) { return c.raw + ' of ' + mc.numSims + ' runs'; }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            title: { display: true, text: 'Number of simulations' }
          },
          y: {
            title: { display: true, text: 'Ending portfolio at age ' + inputs.planEndAge },
            ticks: { maxTicksLimit: 12 }
          }
        }
      }
    });
  }

  function renderChart(result) {
    var canvas = el('planChart');
    if (!canvas || typeof Chart === 'undefined') return;
    var labels = result.years.map(function (y) { return 'Age ' + y.age; });
    var data = result.years.map(function (y) { return Math.round(y.balanceEnd); });

    if (planChart) planChart.destroy();

    var annotations = [];
    if (lastInputs.retirementAge >= lastInputs.currentAge) {
      annotations.push(lastInputs.retirementAge - lastInputs.currentAge);
    }

    planChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Portfolio balance',
          data: data,
          borderColor: '#1d4ed8',
          backgroundColor: 'rgba(29, 78, 216, 0.08)',
          fill: true,
          tension: 0.2,
          pointRadius: 0,
          pointHitRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return fmt(ctx.parsed.y);
              }
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: function (v) { return fmt(v); }
            }
          },
          x: {
            ticks: {
              maxTicksLimit: 12
            }
          }
        }
      }
    });
  }

  function rowHtml(y) {
    var taxCell = y.federalTax ? fmt(y.federalTax) : '—';
    var rmdCell = y.rmd ? fmt(y.rmd) : '—';
    return '<tr>' +
      '<td>' + y.age + (y.rmdStarts ? ' <span style="color:#7c3aed;font-size:11px;">RMDs</span>' : '') + '</td>' +
      '<td>' + fmt(y.balanceEnd) + '</td>' +
      '<td>' + (y.withdrawal ? fmt(y.withdrawal) : '—') + '</td>' +
      '<td>' + (y.socialSecurity ? fmt(y.socialSecurity) : '—') + '</td>' +
      '<td>' + (y.otherIncome ? fmt(y.otherIncome) : '—') + '</td>' +
      '<td>' + rmdCell + '</td>' +
      '<td>' + taxCell + '</td>' +
      '<td>' + (y.totalIncome ? fmt(y.totalIncome) : '—') + '</td>' +
      '</tr>';
  }

  function renderMilestones(result) {
    var tbody = el('milestoneBody');
    tbody.innerHTML = result.milestones.map(rowHtml).join('');
  }

  function renderFullTable(result) {
    var tbody = el('fullTableBody');
    var isPremium = typeof isPremiumUser !== 'undefined' && isPremiumUser;
    var rows = result.years;

    if (isPremium) {
      tbody.innerHTML = rows.map(rowHtml).join('');
      return;
    }

    var milestoneAges = {};
    result.milestones.forEach(function (y) { milestoneAges[y.age] = true; });
    var html = rows.map(function (y) {
      if (milestoneAges[y.age]) return rowHtml(y);
      return '';
    }).join('');

    html += '<tr style="filter:blur(4px);user-select:none;pointer-events:none;opacity:0.7;">' +
      '<td>…</td><td>$•••,•••</td><td>$••,•••</td><td>$••,•••</td><td>—</td><td>$••,•••</td><td>$••,•••</td><td>$••,•••</td></tr>';
    html += '<tr style="filter:blur(4px);user-select:none;pointer-events:none;opacity:0.7;">' +
      '<td>…</td><td>$•••,•••</td><td>$••,•••</td><td>$••,•••</td><td>—</td><td>$••,•••</td><td>$••,•••</td><td>$••,•••</td></tr>';
    html += '<tr><td colspan="8" style="text-align:center;padding:16px;background:#f5f3ff;color:#5b21b6;font-weight:600;">' +
      'Premium: see every year from age ' + lastInputs.currentAge + ' to ' + lastInputs.planEndAge + '</td></tr>';
    tbody.innerHTML = html;
  }

  function buildSummaryText(result) {
    var s = result.summary;
    return 'Retirement Plan Builder. Age ' + lastInputs.currentAge + ' to ' + lastInputs.planEndAge +
      '. Retire at ' + lastInputs.retirementAge + '. Projected balance at retirement ' + fmt(s.balanceAtRetirement) +
      '. Target nest egg ' + (s.targetNestEgg > 0 ? fmt(s.targetNestEgg) : 'not required') +
      '. Status: ' + s.status.headline + '. Household SS at claim (approx.): ' +
      fmt((s.householdSsMonthlyAtClaim || s.ssMonthlyAtClaim) * 12) + '/year. Lifetime est. federal tax in retirement years: ' +
      fmt(s.lifetimeFederalTax) + '. First RMD at age ' + s.rmdStartAge + ': ' +
      (s.firstRmdAmount > 0 ? fmt(s.firstRmdAmount) : 'n/a') + '.' +
      (lastMcResult ? ' Monte Carlo success rate (age ' + lastMcResult.startAge + ' to ' + lastInputs.planEndAge + '): ' +
        lastMcResult.successRate + '% of ' + lastMcResult.numSims + ' simulations. Median ending balance: ' + fmt(lastMcResult.p50) + '.' : '');
  }

  function renderDeepLinks(inputs, result) {
    if (!window.RBDeepLinks) return;
    var links = RBDeepLinks.buildDeepDiveLinks(inputs, result);
    Object.keys(links).forEach(function (id) {
      var node = el(id);
      if (node && links[id]) node.href = links[id];
    });
  }

  function displayResults(result, inputs) {
    lastResult = result;
    lastInputs = inputs;
    renderSummary(result);
    renderMonteCarlo(result, inputs);
    renderChart(result);
    renderMilestones(result);
    renderFullTable(result);
    renderDeepLinks(inputs, result);
    el('results').style.display = 'block';
    window.lastRetirementPlanResult = { summary: buildSummaryText(result) };
    el('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function runPlan() {
    var inputs = collectInputs();
    var errors = validateInputs(inputs);
    if (errors.length) {
      alert('Please check: ' + errors.join(', ') + '.');
      return;
    }
    displayResults(PE.runDeterministicPlan(inputs), inputs);
  }

  function getFormDataForSave() {
    return {
      birthYear: readNumber('birthYear'),
      retirementAge: readNumber('retirementAge'),
      balance: readNumber('balance'),
      portfolioWithdrawalStartAge: readNumber('portfolioWithdrawalStartAge'),
      annualContribution: readNumber('annualContribution'),
      returnPreRetirement: readNumber('returnPreRetirement'),
      returnRetirement: readNumber('returnRetirement'),
      spendingMethod: document.querySelector('input[name="spendingMethod"]:checked').value,
      annualSpendingDirect: readNumber('annualSpendingDirect'),
      currentMonthlySpending: readNumber('currentMonthlySpending'),
      retirementSpendingPct: readNumber('retirementSpendingPct'),
      ssAlreadyReceiving: !!(el('ssAlreadyReceiving') && el('ssAlreadyReceiving').checked),
      ssCurrentMonthly: readNumber('ssCurrentMonthly'),
      ssPiaMonthly: readNumber('ssPiaMonthly'),
      ssClaimAge: el('ssClaimAge').value,
      spouseSsAlreadyReceiving: !!(el('spouseSsAlreadyReceiving') && el('spouseSsAlreadyReceiving').checked),
      spouseSsCurrentMonthly: readNumber('spouseSsCurrentMonthly'),
      spouseSsMonthly: readNumber('spouseSsMonthly'),
      spouseSsClaimAge: el('spouseSsClaimAge').value,
      otherGuaranteedAnnual: readNumber('otherGuaranteedAnnual'),
      withdrawalRate: readNumber('withdrawalRate'),
      inflation: readNumber('inflation'),
      colaRate: readNumber('colaRate'),
      filingStatus: el('filingStatus') ? el('filingStatus').value : 'married',
      taxDeferredPct: readNumber('taxDeferredPct'),
      spouseBeneficiary: el('spouseBeneficiary') ? el('spouseBeneficiary').value : 'no',
      spouseAge: readNumber('spouseAge'),
      volatility: readNumber('volatility'),
      simulations: readNumber('simulations'),
      alreadyRetired: el('alreadyRetired').checked
    };
  }

  function applyFormData(data) {
    if (!data) return;
    if (data.currentAge && el('birthYear') && !data.birthYear) {
      el('birthYear').value = currentCalendarYear() - data.currentAge;
    }
    Object.keys(data).forEach(function (key) {
      if (key === 'currentAge') return;
      var node = el(key);
      if (!node) return;
      if (node.type === 'checkbox') node.checked = !!data[key];
      else if (node.tagName === 'SELECT' || node.type === 'number' || node.type === 'text') node.value = data[key];
    });
    if (data.spendingMethod) {
      var radio = document.querySelector('input[name="spendingMethod"][value="' + data.spendingMethod + '"]');
      if (radio) radio.checked = true;
    }
    if (data.spouseBeneficiary && el('spouseBeneficiary')) {
      el('spouseBeneficiary').value = data.spouseBeneficiary;
      if (typeof toggleSpouseAgeField === 'function') toggleSpouseAgeField();
    }
    if (data.ssClaimAge && el('ssClaimAge')) {
      el('ssClaimAge').dataset.userTouched = '1';
    }
    if (data.ssAlreadyReceiving && el('ssAlreadyReceiving')) {
      el('ssAlreadyReceiving').dataset.userTouched = '1';
    }
    if (data.spouseSsAlreadyReceiving && el('spouseSsAlreadyReceiving')) {
      el('spouseSsAlreadyReceiving').dataset.userTouched = '1';
    }
    syncSpendingMode();
    syncSsReceivingMode();
    syncRetiredState();
    syncFraClaimAge();
  }

  function saveScenario() {
    var name = prompt('Name this scenario:');
    if (!name) return;
    fetch(API_BASE + 'api/save_scenario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        calculator_type: CALCULATOR_TYPE,
        scenario_name: name.trim(),
        scenario_data: getFormDataForSave()
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        var status = el('saveStatus');
        if (data.success) {
          status.textContent = 'Saved!';
          setTimeout(function () { status.textContent = ''; }, 3000);
        } else {
          alert(data.error || 'Could not save scenario');
        }
      })
      .catch(function () { alert('Could not save scenario'); });
  }

  function loadScenario() {
    fetch(API_BASE + 'api/load_scenarios.php?calculator_type=' + encodeURIComponent(CALCULATOR_TYPE))
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success || !data.scenarios || !data.scenarios.length) {
          alert(data.error || 'No saved scenarios found.');
          return;
        }
        var names = data.scenarios.map(function (s, i) {
          return (i + 1) + '. ' + s.scenario_name;
        }).join('\n');
        var pick = prompt('Enter scenario number to load:\n\n' + names);
        if (!pick) return;
        var idx = parseInt(pick, 10) - 1;
        if (isNaN(idx) || !data.scenarios[idx]) return;
        applyFormData(data.scenarios[idx].data);
        runPlan();
      })
      .catch(function () { alert('Could not load scenarios'); });
  }

  function exportCsv() {
    if (!lastResult || !lastInputs) return;
    var header = ['Age', 'Portfolio', 'Withdrawal', 'Social Security (household)', 'Other Income', 'RMD', 'Est Federal Tax', 'Total Income'];
    var lines = [header.join(',')];
    lastResult.years.forEach(function (y) {
      lines.push([
        y.age,
        Math.round(y.balanceEnd),
        Math.round(y.withdrawal || 0),
        Math.round(y.socialSecurity || 0),
        Math.round(y.otherIncome || 0),
        Math.round(y.rmd || 0),
        Math.round(y.federalTax || 0),
        Math.round(y.totalIncome || 0)
      ].join(','));
    });
    var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'retirement-plan.csv';
    a.click();
  }

  function chartCanvasToDataUrl(canvasId) {
    var canvas = el(canvasId);
    if (!canvas) return null;
    try {
      return canvas.toDataURL('image/png');
    } catch (e) {
      return null;
    }
  }

  function downloadPdf() {
    if (!lastResult || !lastInputs) {
      alert('Please build your plan first before downloading the PDF.');
      return;
    }

    var pdfBtn = el('downloadPdfBtn');
    var origText = pdfBtn ? pdfBtn.textContent : '';
    if (pdfBtn) {
      pdfBtn.disabled = true;
      pdfBtn.textContent = 'Generating…';
    }

    var payload = {
      inputs: {
        currentAge: lastInputs.currentAge,
        retirementAge: lastInputs.retirementAge,
        planEndAge: lastInputs.planEndAge,
        balance: lastInputs.balance,
        annualContribution: lastInputs.annualContribution,
        portfolioWithdrawalStartAge: lastInputs.portfolioWithdrawalStartAge,
        baseAnnualSpending: lastInputs.baseAnnualSpending,
        ssAlreadyReceiving: lastInputs.ssAlreadyReceiving,
        ssCurrentMonthly: lastInputs.ssCurrentMonthly,
        ssPiaMonthly: lastInputs.ssPiaMonthly,
        ssClaimAge: lastInputs.ssClaimAge,
        spouseSsAlreadyReceiving: lastInputs.spouseSsAlreadyReceiving,
        spouseSsCurrentMonthly: lastInputs.spouseSsCurrentMonthly,
        spouseSsMonthly: lastInputs.spouseSsMonthly,
        spouseSsClaimAge: lastInputs.spouseSsClaimAge,
        otherGuaranteedAnnual: lastInputs.otherGuaranteedAnnual,
        filingStatus: lastInputs.filingStatus,
        taxDeferredPct: lastInputs.taxDeferredPct,
        returnPreRetirement: lastInputs.returnPreRetirement,
        returnRetirement: lastInputs.returnRetirement
      },
      summary: {
        statusHeadline: lastResult.summary.status.headline,
        statusDetail: lastResult.summary.status.detail,
        balanceAtRetirement: lastResult.summary.balanceAtRetirement,
        targetNestEgg: lastResult.summary.targetNestEgg,
        retirementAnnualIncome: lastResult.summary.retirementAnnualIncome,
        lifetimeFederalTax: lastResult.summary.lifetimeFederalTax
      },
      projections: lastResult.years.map(function (y) {
        return {
          age: y.age,
          balanceEnd: y.balanceEnd,
          withdrawal: y.withdrawal || 0,
          socialSecurity: y.socialSecurity || 0,
          otherIncome: y.otherIncome || 0,
          rmd: y.rmd || 0,
          federalTax: y.federalTax || 0,
          totalIncome: y.totalIncome || 0
        };
      }),
      chartImage: chartCanvasToDataUrl('planChart'),
      mcChartImage: chartCanvasToDataUrl('mcDistributionChart'),
      monteCarlo: lastMcResult ? {
        successRate: lastMcResult.successRate,
        numSims: lastMcResult.numSims,
        volatilityPct: lastMcResult.volatilityPct,
        p25: lastMcResult.p25,
        p50: lastMcResult.p50,
        p75: lastMcResult.p75
      } : null
    };

    fetch(API_BASE + 'api/generate_retirement_plan_pdf.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    })
      .then(function (response) {
        if (!response.ok) {
          return response.text().then(function (t) {
            var msg = 'PDF generation failed';
            try {
              var j = JSON.parse(t);
              if (j.error) msg = j.error;
            } catch (e) { /* ignore */ }
            throw new Error(msg);
          });
        }
        var ct = response.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) {
          throw new Error('Server did not return a PDF. You may need to log in again.');
        }
        return response.blob();
      })
      .then(function (blob) {
        if (blob.type && blob.type.indexOf('pdf') === -1) {
          throw new Error('Download was not a PDF. Try again.');
        }
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'Retirement_Plan_' + new Date().toISOString().split('T')[0] + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      })
      .catch(function (err) {
        alert('Error generating PDF: ' + err.message);
      })
      .finally(function () {
        if (pdfBtn) {
          pdfBtn.disabled = false;
          pdfBtn.textContent = origText;
        }
      });
  }

  function explainResults() {
    var r = window.lastRetirementPlanResult;
    if (!r || !r.summary) {
      alert('Please build your plan first.');
      return;
    }
    var btn = el('explainResultsBtnInResults');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }
    fetch((window.location.origin || '') + '/api/explain_results.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ calculator_type: CALCULATOR_TYPE, results_summary: r.summary })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        if (!data.success) {
          alert(data.error || 'Could not generate explanation.');
          return;
        }
        if (window.showExplainModal) {
          window.showExplainModal(data.explanation, {
            calculatorType: CALCULATOR_TYPE,
            resultsSummary: r.summary
          });
        }
      })
      .catch(function () {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Could not generate explanation.');
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = el('planForm');
    if (!form) return;

    document.querySelectorAll('input[name="spendingMethod"]').forEach(function (radio) {
      radio.addEventListener('change', syncSpendingMode);
    });
    ['birthYear'].forEach(function (id) {
      var node = el(id);
      if (node) node.addEventListener('change', function () {
        syncBirthYearDerived();
        syncRetiredState();
      });
      if (node) node.addEventListener('input', function () {
        syncBirthYearDerived();
        syncRetiredState();
      });
    });
    var claimSelect = el('ssClaimAge');
    if (claimSelect) {
      claimSelect.addEventListener('change', function () {
        claimSelect.dataset.userTouched = '1';
      });
    }
    var retired = el('alreadyRetired');
    if (retired) retired.addEventListener('change', syncRetiredState);

    ['ssAlreadyReceiving', 'spouseSsAlreadyReceiving'].forEach(function (id) {
      var node = el(id);
      if (node) node.addEventListener('change', function () {
        node.dataset.userTouched = '1';
        syncSsReceivingMode();
      });
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      runPlan();
    });

    if (typeof isPremiumUser !== 'undefined' && isPremiumUser) {
      var saveBtn = el('saveScenarioBtn');
      var loadBtn = el('loadScenarioBtn');
      var csvBtn = el('downloadCsvBtn');
      var pdfBtn = el('downloadPdfBtn');
      var compareBtn = el('compareScenariosBtn');
      var explainBtn = el('explainResultsBtnInResults');
      if (saveBtn) saveBtn.addEventListener('click', saveScenario);
      if (loadBtn) loadBtn.addEventListener('click', loadScenario);
      if (csvBtn) csvBtn.addEventListener('click', exportCsv);
      if (pdfBtn) pdfBtn.addEventListener('click', downloadPdf);
      if (explainBtn) explainBtn.addEventListener('click', explainResults);
      if (compareBtn && window.CompareScenariosModal) {
        compareBtn.addEventListener('click', function () {
          CompareScenariosModal.open(API_BASE, CALCULATOR_TYPE, function (scenarios) {
            if (!scenarios.length) return;
            applyFormData(scenarios[0].data);
            runPlan();
          });
        });
      }
    }

    syncSpendingMode();
    syncSsReceivingMode();
    syncBirthYearDerived();
    syncRetiredState();
  });
})();
