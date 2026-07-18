(function () {
  'use strict';

  const KITCES_FEE_FACTOR = 0.4; // research-style rule of thumb: ~0.4× fee into SWR

  function formatCurrency(amount) {
    const n = Number(amount);
    if (!isFinite(n)) return '—';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(n);
  }

  function formatPct(pct, digits) {
    const d = digits == null ? 2 : digits;
    return (Number(pct) || 0).toFixed(d).replace(/\.?0+$/, '') + '%';
  }

  function pctLabel(id, value, suffix) {
    const el = document.getElementById(id);
    if (el) el.textContent = formatPct(value) + (suffix || '');
  }

  /**
   * Year order (documented on page): withdraw → apply return → deduct fee % of balance.
   * returns[] and inflation[] are decimals. If inflation is null, spending stays flat (real model).
   */
  function simulateRetirement(opts) {
    const start = opts.startBalance;
    const years = opts.years;
    const returns = opts.returns;
    const inflation = opts.inflation; // array or null
    const initialSpend = opts.initialSpend;
    const feeRate = opts.feeRate; // decimal

    let bal = start;
    let spend = initialSpend;
    let totalFees = 0;
    let minBal = start;
    let year1Fee = 0;
    let troughFee = 0;
    let troughYear = 0;
    const path = [{ year: 0, balance: bal, spend: 0, fee: 0 }];

    for (let y = 1; y <= years; y++) {
      bal -= spend;
      if (bal <= 0) {
        return {
          survived: false,
          yearsLasted: y,
          endBal: 0,
          totalFees,
          minBal: 0,
          year1Fee,
          troughFee,
          troughYear,
          path
        };
      }

      const r = returns[y - 1];
      bal *= 1 + r;
      const fee = Math.max(0, bal * feeRate);
      bal -= fee;
      totalFees += fee;
      if (y === 1) year1Fee = fee;
      if (bal < minBal) {
        minBal = bal;
        troughFee = fee;
        troughYear = y;
      }

      path.push({ year: y, balance: Math.max(0, bal), spend, fee });

      if (inflation && inflation[y - 1] != null) {
        spend *= 1 + inflation[y - 1];
      }
    }

    return {
      survived: bal > 0,
      yearsLasted: years,
      endBal: Math.max(0, bal),
      totalFees,
      minBal,
      year1Fee,
      troughFee,
      troughYear,
      path
    };
  }

  function maxSWR(startBalance, years, returns, inflation, feeRate) {
    let lo = 0.005;
    let hi = 0.15;
    let best = lo;
    for (let i = 0; i < 42; i++) {
      const mid = (lo + hi) / 2;
      const sim = simulateRetirement({
        startBalance,
        years,
        returns,
        inflation,
        initialSpend: startBalance * mid,
        feeRate
      });
      if (sim.survived && sim.endBal >= 0) {
        best = mid;
        lo = mid;
      } else {
        hi = mid;
      }
    }
    return best;
  }

  function constantReturns(years, realReturn) {
    return Array(years).fill(realReturn);
  }

  function getInputs() {
    return {
      portfolio: parseFloat(document.getElementById('portfolioValue').value),
      baselineSwrPct: parseFloat(document.getElementById('baselineSwr').value),
      aumPct: parseFloat(document.getElementById('aumFee').value),
      fundErPct: parseFloat(document.getElementById('fundEr').value),
      years: parseInt(document.getElementById('years').value, 10),
      realReturnPct: parseFloat(document.getElementById('realReturn').value),
      mode: (document.querySelector('input[name="engineMode"]:checked') || {}).value || 'simple',
      scenario: (document.querySelector('input[name="scenario"]:checked') || {}).value || 'tough'
    };
  }

  function updateLabels(inp) {
    pctLabel('baselineSwrLabel', inp.baselineSwrPct);
    pctLabel('aumFeeLabel', inp.aumPct);
    pctLabel('fundErLabel', inp.fundErPct);
    const yearsLabel = document.getElementById('yearsLabel');
    if (yearsLabel) yearsLabel.textContent = inp.years + ' years';
    pctLabel('realReturnLabel', inp.realReturnPct);
  }

  function validate(inp) {
    if (!isFinite(inp.portfolio) || inp.portfolio < 1000) {
      return 'Enter a portfolio value of at least $1,000.';
    }
    if (!isFinite(inp.baselineSwrPct) || inp.baselineSwrPct < 2 || inp.baselineSwrPct > 8) {
      return 'Choose a baseline SWR between 2% and 8%.';
    }
    if (!isFinite(inp.aumPct) || inp.aumPct < 0 || inp.aumPct > 5) {
      return 'Enter an AUM fee between 0% and 5%.';
    }
    if (!isFinite(inp.fundErPct) || inp.fundErPct < 0 || inp.fundErPct > 3) {
      return 'Enter a fund expense ratio between 0% and 3%.';
    }
    if (!isFinite(inp.years) || inp.years < 20 || inp.years > 40) {
      return 'Choose a retirement length between 20 and 40 years.';
    }
    if (inp.mode === 'sequence') {
      const pack = window.SWR_FEE_SCENARIOS;
      if (!pack || !pack.scenarios || !pack.scenarios[inp.scenario]) {
        return 'Scenario data failed to load. Refresh the page and try again.';
      }
      const sc = pack.scenarios[inp.scenario];
      if (!sc.returns || sc.returns.length < inp.years) {
        return 'This scenario has fewer return years than your selected horizon. Use 30 years or Simple mode.';
      }
    }
    return '';
  }

  function buildReturnSeries(inp) {
    if (inp.mode === 'simple') {
      const r = inp.realReturnPct / 100;
      return {
        returns: constantReturns(inp.years, r),
        inflation: null,
        label: 'Simple constant real return (' + formatPct(inp.realReturnPct) + ')',
        metaNote: 'Spending is held constant in real terms (no separate inflation array).'
      };
    }
    const sc = window.SWR_FEE_SCENARIOS.scenarios[inp.scenario];
    return {
      returns: sc.returns.slice(0, inp.years),
      inflation: sc.inflation.slice(0, inp.years),
      label: sc.label,
      subtitle: sc.subtitle,
      metaNote: (window.SWR_FEE_SCENARIOS.meta && window.SWR_FEE_SCENARIOS.meta.disclaimer) || ''
    };
  }

  function kitcesRuleOfThumb(baselineSwrPct, aumPct) {
    return Math.max(0.5, baselineSwrPct - KITCES_FEE_FACTOR * aumPct);
  }

  let chartInstance = null;

  function renderChart(pathNoAum, pathWithAum) {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const labels = pathNoAum.map(function (p) { return 'Y' + p.year; });
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(canvas.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Fund ER only',
            data: pathNoAum.map(function (p) { return p.balance; }),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            tension: 0.15,
            fill: false,
            pointRadius: 0
          },
          {
            label: 'With AUM fee',
            data: pathWithAum.map(function (p) { return p.balance; }),
            borderColor: '#c2410c',
            backgroundColor: 'rgba(194, 65, 12, 0.08)',
            tension: 0.15,
            fill: false,
            pointRadius: 0
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ctx.dataset.label + ': ' + formatCurrency(ctx.parsed.y);
              }
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: function (v) { return formatCurrency(v); }
            }
          }
        }
      }
    });
  }

  function displayResults(inp, series) {
    const start = inp.portfolio;
    const years = inp.years;
    const baseline = inp.baselineSwrPct / 100;
    const er = inp.fundErPct / 100;
    const aum = inp.aumPct / 100;
    const feeLow = er;
    const feeHigh = aum + er;

    const swrLow = maxSWR(start, years, series.returns, series.inflation, feeLow);
    const swrHigh = maxSWR(start, years, series.returns, series.inflation, feeHigh);

    // Scale the user's baseline by how fees compress this model's survival SWR.
    // Example: baseline 4%, model 4.03% → 3.55% with fees ⇒ implied ≈ 3.52%.
    const scale = swrLow > 0 ? swrHigh / swrLow : 1;
    const impliedRate = baseline * scale;
    const impliedPct = impliedRate * 100;
    const kitcesPct = kitcesRuleOfThumb(inp.baselineSwrPct, inp.aumPct);

    const grossSpend = start * baseline;
    const netSpend = start * impliedRate;

    const wealthNo = simulateRetirement({
      startBalance: start,
      years: years,
      returns: series.returns,
      inflation: series.inflation,
      initialSpend: grossSpend,
      feeRate: feeLow
    });
    const wealthYes = simulateRetirement({
      startBalance: start,
      years: years,
      returns: series.returns,
      inflation: series.inflation,
      initialSpend: grossSpend,
      feeRate: feeHigh
    });

    document.getElementById('results').style.display = 'block';
    document.getElementById('grossSpend').textContent = formatCurrency(grossSpend);
    document.getElementById('grossSpendHint').textContent =
      formatPct(inp.baselineSwrPct) + ' of ' + formatCurrency(start);

    document.getElementById('netSpend').textContent = formatCurrency(netSpend);
    document.getElementById('netSpendHint').textContent =
      'Your baseline rate scaled by how fees compress this model’s survival SWR';

    document.getElementById('impliedSwr').textContent = formatPct(impliedPct);
    const spendCut = baseline > 0 ? (1 - impliedRate / baseline) * 100 : 0;
    document.getElementById('impliedSwrHint').textContent =
      'About ' + formatPct(spendCut, 1) + ' less spending power vs your baseline rate';

    const dropPts = (swrLow - swrHigh) * 100;
    let narrative =
      'In this model, the highest ' + years + '-year survival withdrawal rate is about ' +
      formatPct(swrLow * 100) + ' with fund expenses only (' + formatPct(inp.fundErPct) +
      '), and about ' + formatPct(swrHigh * 100) + ' after adding a ' + formatPct(inp.aumPct) +
      ' AUM fee (a drop of roughly ' + dropPts.toFixed(2) + ' points, not a full ' +
      formatPct(inp.aumPct) + '). Applying that same compression to your ' +
      formatPct(inp.baselineSwrPct) + ' baseline implies about ' + formatPct(impliedPct) +
      ' for spendable withdrawals.';

    if (inp.aumPct >= 0.75 && inp.aumPct <= 1.25 && Math.abs(inp.baselineSwrPct - 4) < 0.15) {
      narrative +=
        ' For comparison, a common research rule of thumb is that ~1% of expenses may trim a 4% SWR toward about ' +
        formatPct(kitcesPct) + ' (roughly 0.4× the fee), not down to 3%.';
    }

    if (inp.mode === 'sequence' && inp.scenario === 'favorable') {
      narrative +=
        ' On a favorable path, the bigger story is often ending wealth: you may “succeed” either way, but AUM still removes a large share of terminal value.';
    }
    if (inp.mode === 'sequence' && inp.scenario === 'tough' && wealthYes.year1Fee > 0 && wealthYes.troughFee > 0) {
      const feeRatio = wealthYes.troughFee / wealthYes.year1Fee;
      if (feeRatio < 0.7) {
        narrative +=
          ' In the trough of this path, annual fee dollars fall to about ' +
          Math.round(feeRatio * 100) + '% of year-one fees — the self-mitigating effect.';
      }
    }

    document.getElementById('swrNarrative').textContent = narrative;

    document.getElementById('endNoAum').textContent = wealthNo.survived
      ? formatCurrency(wealthNo.endBal)
      : 'Depleted (year ' + wealthNo.yearsLasted + ')';
    document.getElementById('endNoAumSub').textContent =
      'Total fees paid: ' + formatCurrency(wealthNo.totalFees);

    document.getElementById('endWithAum').textContent = wealthYes.survived
      ? formatCurrency(wealthYes.endBal)
      : 'Depleted (year ' + wealthYes.yearsLasted + ')';
    document.getElementById('endWithAumSub').textContent =
      'Total fees paid: ' + formatCurrency(wealthYes.totalFees);

    const gap = (wealthNo.survived ? wealthNo.endBal : 0) - (wealthYes.survived ? wealthYes.endBal : 0);
    document.getElementById('endGap').textContent = formatCurrency(Math.max(0, gap));
    let gapSub = 'Ending balance difference at year ' + years;
    if (wealthNo.survived && wealthYes.survived && wealthNo.endBal > 0) {
      gapSub += ' (' + formatPct((gap / wealthNo.endBal) * 100, 1) + ' of the no-AUM ending value)';
    }
    document.getElementById('endGapSub').textContent = gapSub;

    renderChart(wealthNo.path, wealthYes.path);

    const assumptions = [];
    assumptions.push('Mode: ' + (inp.mode === 'simple' ? 'Simple constant real return' : 'Sequence — ' + series.label) + '.');
    if (series.subtitle) assumptions.push(series.subtitle + '.');
    assumptions.push('Each year: withdraw → apply return → deduct fees as % of remaining balance.');
    assumptions.push('Total fee rate with AUM: ' + formatPct(inp.aumPct + inp.fundErPct) + ' (AUM + fund ER).');
    if (inp.mode === 'simple') {
      assumptions.push(series.metaNote);
    } else {
      assumptions.push(series.metaNote);
      assumptions.push('Illustrative paths, not labeled calendar years. For every historical start year, use a tool like FiCalc.');
    }
    assumptions.push('Taxes, Social Security, and changing asset allocation are not modeled.');
    document.getElementById('assumptionsText').textContent = assumptions.join(' ');

    // Share / explain summary
    const summary =
      'SWR & Fee Impact. Portfolio ' + formatCurrency(start) +
      '. Baseline SWR ' + formatPct(inp.baselineSwrPct) +
      '. AUM ' + formatPct(inp.aumPct) + ', fund ER ' + formatPct(inp.fundErPct) +
      '. Mode: ' + inp.mode + (inp.mode === 'sequence' ? ' (' + inp.scenario + ')' : '') +
      '. Fee-adjusted spendable SWR about ' + formatPct(impliedPct) +
      ' (' + formatCurrency(netSpend) + ' year 1). Ending wealth without AUM ' +
      (wealthNo.survived ? formatCurrency(wealthNo.endBal) : 'depleted') +
      '; with AUM ' + (wealthYes.survived ? formatCurrency(wealthYes.endBal) : 'depleted') + '.';

    window.lastSwrFeeResults = { summary: summary };
  }

  function explainResults() {
    const r = window.lastSwrFeeResults;
    if (!r || !r.summary) {
      alert('Please run the calculation first to see results.');
      return;
    }
    const btn = document.getElementById('explainResultsBtnInResults');
    const origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }
    const explainUrl = (window.location.origin || '') + '/api/explain_results.php';
    fetch(explainUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ calculator_type: 'swr-fee-impact', results_summary: r.summary })
    })
      .then(function (res) { return res.text(); })
      .then(function (text) {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        let data;
        try { data = JSON.parse(text); } catch (e) {
          alert('Could not explain results. Please try again.');
          return;
        }
        if (data.error) {
          alert(data.error);
          return;
        }
        if (typeof showExplainModal === 'function') {
          showExplainModal(data.explanation || text, {
            calculatorType: 'swr-fee-impact',
            resultsSummary: r.summary
          });
        } else {
          alert(data.explanation || 'Explanation unavailable.');
        }
      })
      .catch(function () {
        if (btn) { btn.disabled = false; btn.textContent = origText; }
        alert('Could not explain results. Please try again.');
      });
  }

  function calculate(showAlerts) {
    const inp = getInputs();
    updateLabels(inp);
    const errEl = document.getElementById('validationError');
    const errorMessage = validate(inp);
    if (errorMessage) {
      if (errEl) {
        errEl.textContent = errorMessage;
        errEl.style.display = 'block';
      } else if (showAlerts) {
        alert(errorMessage);
      }
      return;
    }
    if (errEl) errEl.style.display = 'none';
    const series = buildReturnSeries(inp);
    displayResults(inp, series);
  }

  function syncModeUI() {
    const mode = (document.querySelector('input[name="engineMode"]:checked') || {}).value || 'simple';
    const simpleExtras = document.getElementById('simpleExtras');
    const scenarioBlock = document.getElementById('scenarioBlock');
    if (simpleExtras) simpleExtras.hidden = mode !== 'simple';
    if (scenarioBlock) scenarioBlock.hidden = mode !== 'sequence';
  }

  function buildScenarioToggle() {
    const host = document.getElementById('scenarioToggle');
    const help = document.getElementById('scenarioHelp');
    const pack = window.SWR_FEE_SCENARIOS;
    if (!host || !pack || !pack.scenarios) return;

    const order = ['tough', 'typical', 'favorable'];
    host.innerHTML = '';
    order.forEach(function (id, idx) {
      const sc = pack.scenarios[id];
      if (!sc) return;
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'scenario';
      input.value = id;
      if (idx === 0) input.checked = true;
      const span = document.createElement('span');
      span.innerHTML = '<strong>' + sc.label + '</strong> — ' + sc.subtitle;
      label.appendChild(input);
      label.appendChild(span);
      host.appendChild(label);
      input.addEventListener('change', function () {
        if (help) help.textContent = sc.subtitle + '. Switch to Favorable to see the ending-wealth lesson.';
        calculate(false);
      });
    });
    if (help && pack.scenarios.tough) {
      help.textContent = pack.scenarios.tough.subtitle + '. Use Tough for the SWR story; try Favorable for ending wealth.';
    }
  }

  function init() {
    buildScenarioToggle();
    syncModeUI();

    const form = document.getElementById('swrFeeForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        calculate(true);
      });
    }

    ['portfolioValue', 'baselineSwr', 'aumFee', 'fundEr', 'years', 'realReturn'].forEach(function (id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function () {
        updateLabels(getInputs());
      });
      el.addEventListener('change', function () { calculate(false); });
    });

    document.querySelectorAll('input[name="engineMode"]').forEach(function (el) {
      el.addEventListener('change', function () {
        syncModeUI();
        calculate(false);
      });
    });

    const explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) explainBtn.addEventListener('click', explainResults);

    updateLabels(getInputs());
    calculate(false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
