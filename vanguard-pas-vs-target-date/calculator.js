(function () {
  'use strict';

  function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  function calculatePortfolio(principal, annualReturnPct, feeRatePct, years, withdrawalPct) {
    withdrawalPct = withdrawalPct || 0;
    var yearlyData = [];
    var balance = principal;
    var totalFees = 0;
    var totalWithdrawals = 0;
    yearlyData.push({ year: 0, balance: balance, fee: 0, totalFees: 0, withdrawal: 0, totalWithdrawals: 0 });
    for (var y = 1; y <= years; y++) {
      balance = balance * (1 + annualReturnPct / 100);
      var yearFee = balance * (feeRatePct / 100);
      var yearWithdrawal = withdrawalPct > 0 ? balance * (withdrawalPct / 100) : 0;
      totalFees += yearFee;
      totalWithdrawals += yearWithdrawal;
      yearlyData.push({
        year: y,
        balance: balance,
        fee: yearFee,
        totalFees: totalFees,
        withdrawal: yearWithdrawal,
        totalWithdrawals: totalWithdrawals
      });
      balance = balance - yearFee - yearWithdrawal;
    }
    return yearlyData;
  }

  function getNormalizedAllocation() {
    var c = parseFloat(document.getElementById('pctConservative').value) || 0;
    var m = parseFloat(document.getElementById('pctModerate').value) || 0;
    var a = parseFloat(document.getElementById('pctAggressive').value) || 0;
    var total = c + m + a;
    if (total <= 0) return { c: 33.33, m: 33.33, a: 33.34, sum: 0 };
    return {
      c: Math.round((c / total) * 1000) / 10,
      m: Math.round((m / total) * 1000) / 10,
      a: Math.round((a / total) * 1000) / 10,
      sum: total
    };
  }

  function updateLabels() {
    var portfolio = parseFloat(document.getElementById('portfolioValue').value);
    var years = parseInt(document.getElementById('years').value, 10);
    var returnRate = parseFloat(document.getElementById('returnRate').value);
    var withdrawalPct = parseFloat(document.getElementById('withdrawalPct').value) || 0;
    var cRaw = parseFloat(document.getElementById('pctConservative').value) || 0;
    var mRaw = parseFloat(document.getElementById('pctModerate').value) || 0;
    var aRaw = parseFloat(document.getElementById('pctAggressive').value) || 0;
    var alloc = getNormalizedAllocation();

    var portfolioLabel = document.getElementById('portfolioValueLabel');
    if (portfolioLabel) portfolioLabel.textContent = isNaN(portfolio) ? '' : formatCurrency(portfolio);
    var yearsLabel = document.getElementById('yearsLabel');
    if (yearsLabel) yearsLabel.textContent = isNaN(years) ? '' : years + ' yrs';
    var returnRateLabel = document.getElementById('returnRateLabel');
    if (returnRateLabel) returnRateLabel.textContent = isNaN(returnRate) ? '' : returnRate.toFixed(2).replace(/\.00$/, '') + '%';
    var withdrawalPctLabel = document.getElementById('withdrawalPctLabel');
    if (withdrawalPctLabel) withdrawalPctLabel.textContent = (withdrawalPct === 0 ? '0%' : withdrawalPct.toFixed(1) + '%');

    var pctCL = document.getElementById('pctConservativeLabel');
    var pctML = document.getElementById('pctModerateLabel');
    var pctAL = document.getElementById('pctAggressiveLabel');
    if (pctCL) pctCL.textContent = alloc.c + '%';
    if (pctML) pctML.textContent = alloc.m + '%';
    if (pctAL) pctAL.textContent = alloc.a + '%';

    var sumEl = document.getElementById('allocationSum');
    if (sumEl) {
      var total = cRaw + mRaw + aRaw;
      if (total <= 0) {
        sumEl.textContent = 'Set allocation percentages (they will be normalized to 100%).';
      } else if (Math.abs(total - 100) < 0.1) {
        sumEl.textContent = 'Allocation: ' + alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '% (sum = 100%).';
      } else {
        sumEl.textContent = 'Raw sum = ' + total.toFixed(0) + '%. Normalized to 100%: ' + alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '%';
      }
    }
  }

  function calculate(shouldScroll) {
    var portfolioValue = parseFloat(document.getElementById('portfolioValue').value);
    var pasFee = parseFloat(document.getElementById('pasFee').value);
    var targetDateFee = parseFloat(document.getElementById('targetDateFee').value);
    var years = parseInt(document.getElementById('years').value, 10);
    var returnRate = parseFloat(document.getElementById('returnRate').value);
    var withdrawalPct = parseFloat(document.getElementById('withdrawalPct').value) || 0;

    updateLabels();

    if (isNaN(portfolioValue) || isNaN(pasFee) || isNaN(years) || isNaN(returnRate)) {
      if (shouldScroll) alert('Please enter valid numbers for all fields.');
      return;
    }

    var pasData = calculatePortfolio(portfolioValue, returnRate, pasFee, years, withdrawalPct);
    var targetData = calculatePortfolio(portfolioValue, returnRate, targetDateFee, years, withdrawalPct);

    var alloc = getNormalizedAllocation();
    var midYear = Math.floor(years / 2);
    var pasFinal = pasData[years].balance;
    var targetFinal = targetData[years].balance;
    var opportunityCost = targetFinal - pasFinal;

    document.getElementById('resultYears').textContent = years;
    document.getElementById('opportunityCost').textContent = formatCurrency(opportunityCost);
    document.getElementById('pasFeeResultLabel').textContent = pasFee.toFixed(2) + '% fee';

    document.getElementById('pasYear1Fee').textContent = formatCurrency(pasData[1].fee);
    document.getElementById('targetYear1Fee').textContent = formatCurrency(targetData[1].fee);
    document.getElementById('year1FeeDiff').textContent = formatCurrency(pasData[1].fee - targetData[1].fee);

    document.getElementById('midYearLabel').textContent = midYear;
    document.getElementById('pasMidValue').textContent = formatCurrency(pasData[midYear].balance);
    document.getElementById('targetMidValue').textContent = formatCurrency(targetData[midYear].balance);
    document.getElementById('midValueDiff').textContent = formatCurrency(targetData[midYear].balance - pasData[midYear].balance);

    document.getElementById('finalYearLabel').textContent = years;
    document.getElementById('pasFinalValue').textContent = formatCurrency(pasFinal);
    document.getElementById('targetFinalValue').textContent = formatCurrency(targetFinal);
    document.getElementById('finalValueDiff').textContent = formatCurrency(opportunityCost);

    var directFeeDiff = pasData[years].totalFees - targetData[years].totalFees;
    document.getElementById('pasTotalFees').textContent = formatCurrency(pasData[years].totalFees);
    document.getElementById('targetTotalFees').textContent = formatCurrency(targetData[years].totalFees);
    document.getElementById('totalFeesDiff').textContent = formatCurrency(directFeeDiff);

    var lostGrowth = opportunityCost - directFeeDiff;
    document.getElementById('insightDirectFees').textContent = formatCurrency(directFeeDiff);
    document.getElementById('insightYears').textContent = years;
    document.getElementById('insightLostGrowth').textContent = formatCurrency(lostGrowth > 0 ? lostGrowth : opportunityCost);
    document.getElementById('insightAllocation').textContent = alloc.c + '% / ' + alloc.m + '% / ' + alloc.a + '%';

    createChart(pasData, targetData, years);
    createFeesChart(pasData, targetData, years);

    window.lastPASvsTargetResult = {
      portfolioValue: portfolioValue,
      pasFee: pasFee,
      targetDateFee: targetDateFee,
      years: years,
      returnRate: returnRate,
      withdrawalPct: withdrawalPct,
      allocation: { conservative: alloc.c, moderate: alloc.m, aggressive: alloc.a },
      pasData: pasData,
      targetData: targetData,
      opportunityCost: opportunityCost,
      directFeeDiff: directFeeDiff,
      lostGrowth: lostGrowth,
      pasFinal: pasFinal,
      targetFinal: targetFinal
    };

    if (shouldScroll) {
      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  var chartInstance = null;
  var feesChartInstance = null;

  function createChart(pasData, targetData, years) {
    var ctx = document.getElementById('growthChart');
    if (!ctx) return;
    if (chartInstance) chartInstance.destroy();

    var labels = pasData.map(function (d) { return 'Year ' + d.year; });
    var pasValues = pasData.map(function (d) { return d.balance; });
    var targetValues = targetData.map(function (d) { return d.balance; });

    chartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'Vanguard PAS', data: pasValues, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.1)', borderWidth: 3, tension: 0.4, fill: false },
          { label: 'Target Date Blend', data: targetValues, borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, 0.1)', borderWidth: 3, tension: 0.4, fill: false }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: { label: function (c) { return c.dataset.label + ': ' + formatCurrency(c.parsed.y); } }
          }
        },
        scales: {
          y: {
            beginAtZero: false,
            ticks: { callback: function (v) { return formatCurrency(v); } }
          }
        }
      }
    });
  }

  function createFeesChart(pasData, targetData, years) {
    var ctx = document.getElementById('feesChart');
    if (!ctx) return;
    if (feesChartInstance) feesChartInstance.destroy();

    var labels = pasData.map(function (d) { return 'Year ' + d.year; });
    var pasFees = pasData.map(function (d) { return d.totalFees; });
    var targetFees = targetData.map(function (d) { return d.totalFees; });

    feesChartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'PAS Fees', data: pasFees, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.1)', borderWidth: 3, tension: 0.4, fill: true },
          { label: 'Target Date Fees', data: targetFees, borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, 0.1)', borderWidth: 3, tension: 0.4, fill: true }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: { label: function (c) { return c.dataset.label + ': ' + formatCurrency(c.parsed.y); } }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: function (v) { return formatCurrency(v); } }
          }
        }
      }
    });
  }

  document.getElementById('calculateBtn').addEventListener('click', function () { calculate(true); });

  ['portfolioValue', 'years', 'returnRate', 'withdrawalPct', 'pctConservative', 'pctModerate', 'pctAggressive'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function () { calculate(false); });
  });

  window.addEventListener('load', function () {
    updateLabels();
  });

  document.addEventListener('DOMContentLoaded', function () {
    var saveBtn = document.getElementById('saveScenarioBtn');
    var loadBtn = document.getElementById('loadScenarioBtn');
    if (saveBtn) saveBtn.addEventListener('click', function () {
      var status = document.getElementById('saveStatus');
      if (status) status.textContent = 'Save/load can be added.';
    });
    if (loadBtn) loadBtn.addEventListener('click', function () {
      var status = document.getElementById('saveStatus');
      if (status) status.textContent = '';
    });
    var explainBtn = document.getElementById('explainResultsBtnInResults');
    if (explainBtn) explainBtn.addEventListener('click', explainPASResults);
  });
})();

function explainPASResults() {
  var r = window.lastPASvsTargetResult;
  if (!r) {
    alert('Please run the calculation first to see results.');
    return;
  }
  var summary = 'Vanguard Personal Advisor vs Target Date Funds. Portfolio $' + r.portfolioValue.toLocaleString() + ', PAS fee ' + r.pasFee + '%, Target Date fee ' + r.targetDateFee + '%. ';
  summary += 'Timeline ' + r.years + ' years, expected return ' + r.returnRate + '%. ';
  if (r.withdrawalPct > 0) summary += 'Annual withdrawal ' + r.withdrawalPct + '% of portfolio. ';
  summary += 'Allocation: ' + r.allocation.conservative + '% conservative, ' + r.allocation.moderate + '% moderate, ' + r.allocation.aggressive + '% aggressive. ';
  summary += 'Opportunity cost over ' + r.years + ' years: $' + Math.round(r.opportunityCost).toLocaleString() + '. ';
  summary += 'Direct fee difference: $' + Math.round(r.directFeeDiff).toLocaleString() + '. Lost growth: $' + Math.round(r.lostGrowth > 0 ? r.lostGrowth : r.opportunityCost).toLocaleString() + '.';

  var btn = document.getElementById('explainResultsBtnInResults');
  var origText = btn ? btn.textContent : '';
  if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

  var explainUrl = (window.location.origin || '') + '/api/explain_results.php';
  fetch(explainUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ calculator_type: 'vanguard-pas-vs-target-date', results_summary: summary })
  })
  .then(function (res) { return res.text(); })
  .then(function (text) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    var data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Server returned an unexpected response.'); }
    if (data.error) throw new Error(data.error);
    showExplainModal(data.explanation);
  })
  .catch(function (err) {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

function showExplainModal(explanation) {
  var overlay = document.getElementById('explainResultsModalOverlay');
  if (overlay) overlay.remove();
  overlay = document.createElement('div');
  overlay.id = 'explainResultsModalOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
  overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.remove(); });
  var box = document.createElement('div');
  box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:560px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
  box.addEventListener('click', function (e) { e.stopPropagation(); });
  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }
  box.innerHTML = '<div style="padding:24px 24px 16px;">' +
    '<h2 style="margin:0 0 16px 0;font-size:1.25rem;color:#1f2937;">🤖 AI Explanation</h2>' +
    '<div style="color:#374151;line-height:1.7;white-space:pre-wrap;overflow-y:auto;max-height:50vh;">' + escapeHtml(explanation) + '</div>' +
    '</div>' +
    '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
    '<button type="button" id="explainModalCloseBtn" style="padding:10px 24px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Close</button>' +
    '</div>';
  overlay.appendChild(box);
  document.body.appendChild(overlay);
  document.getElementById('explainModalCloseBtn').addEventListener('click', function () { overlay.remove(); });
}
