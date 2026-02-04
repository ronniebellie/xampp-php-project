<?php
// Social Security Claiming Analyzer
// Yearly projection table (single person comparison: option A vs option B)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Social Security Claiming Analyzer</title>
  <link rel="stylesheet" href="css/style.css?v=1" />
</head>
<body>
  <div class="page">
    <h1>Social Security Claiming Analyzer</h1>

    <section class="card">
      <p class="intro">
        This Social Security Claiming Analyzer helps you explore how different claiming ages affect your Social Security benefits over time. You can model an individual, compare monthly and annual benefit amounts, and see how total lifetime benefits grow with cost-of-living adjustments. The goal is not to predict the future, but to help you understand trade-offs and timing decisions using clear assumptions and simplified rules.
      </p>
    </section>

    <section id="inputs" class="card">
      <h2>Inputs</h2>


      <h3>Assumptions</h3>


      <div class="row">
        <label for="endAge">End age:</label>
        <input id="endAge" type="number" min="0" max="120" step="1" value="90" />
      </div>

      <div class="row">
        <label for="colaRate">COLA % increase (30-yr avg is 2.8%):</label>
        <input id="colaRate" type="number" min="0" max="20" step="0.1" value="2.8" />
      </div>

      <div class="row">
        <label for="colaMonth">COLA start month:</label>
        <select id="colaMonth">
          <option value="January" selected>January</option>
          <option value="February">February</option>
          <option value="March">March</option>
          <option value="April">April</option>
          <option value="May">May</option>
          <option value="June">June</option>
          <option value="July">July</option>
          <option value="August">August</option>
          <option value="September">September</option>
          <option value="October">October</option>
          <option value="November">November</option>
          <option value="December">December</option>
        </select>
      </div>

      <h3>Your SSA Benefit Information</h3>

      <div class="row">
        <label for="aBirthDate">Birth date:</label>
        <input id="aBirthDate" type="date" min="1900-01-01" max="2100-12-31" />
      </div>

      <div class="row">
        <label for="aPIA" title="This is your gross Social Security retirement benefit at Full Retirement Age (FRA), before any deductions for Medicare Part B / Medicare Advantage, IRMAA, taxes, or other withholdings. Use the amount shown on your SSA statement.">Monthly benefit at Full Retirement Age (FRA) (before Medicare deductions):</label>
        <input id="aPIA" type="number" min="0" step="0.01" placeholder="Gross amount from SSA statement, before Medicare (e.g., 2988.00)" />
      </div>

      <h4>Claim option A</h4>

      <div class="row">
        <label for="aClaimAgeA">Claim age A (years):</label>
        <input id="aClaimAgeA" type="number" min="0" max="120" step="1" value="62" />
      </div>

      <div class="row">
        <label for="aClaimMonthA">Claim month A (0–11):</label>
        <input id="aClaimMonthA" type="number" min="0" max="11" step="1" value="0" />
      </div>

      <h4>Claim option B</h4>

      <div class="row">
        <label for="aClaimAgeB">Claim age B (years):</label>
        <input id="aClaimAgeB" type="number" min="0" max="120" step="1" value="70" />
      </div>

      <div class="row">
        <label for="aClaimMonthB">Claim month B (0–11):</label>
        <input id="aClaimMonthB" type="number" min="0" max="11" step="1" value="0" />
      </div>


      <div class="row">
        <button id="calculate" type="button">Calculate</button>
      </div>
    </section>

    <section id="assumptions" class="card" style="display:none;">
      <h2>How the calculator ran your scenario</h2>
      <p id="assumptionsPlain"></p>
    </section>

    <section id="results" class="card"></section>
  </div>

  <script>
  // Social Security Claiming Analyzer
  // Yearly projection table (single person)

  (function () {
    "use strict";

    function $(id) {
      return document.getElementById(id);
    }

    function fmtMoney(n) {
      if (typeof n !== "number" || !isFinite(n)) return "";
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(n);
    }

    function parseDateValue(el) {
      if (!el || !el.value) return null;
      const d = new Date(el.value);
      if (Number.isNaN(d.getTime())) return null;
      return d;
    }

    // SSA FRA schedule (birth year -> FRA years/months)
    function getFRA(birthYear) {
      if (birthYear <= 1937) return { years: 65, months: 0 };
      if (birthYear === 1938) return { years: 65, months: 2 };
      if (birthYear === 1939) return { years: 65, months: 4 };
      if (birthYear === 1940) return { years: 65, months: 6 };
      if (birthYear === 1941) return { years: 65, months: 8 };
      if (birthYear === 1942) return { years: 65, months: 10 };
      if (birthYear === 1943 || birthYear === 1944) return { years: 66, months: 0 };
      if (birthYear === 1945) return { years: 66, months: 2 };
      if (birthYear === 1946) return { years: 66, months: 4 };
      if (birthYear === 1947) return { years: 66, months: 6 };
      if (birthYear === 1948) return { years: 66, months: 8 };
      if (birthYear === 1949) return { years: 66, months: 10 };
      return { years: 67, months: 0 }; // 1960+
    }

    // Very simplified early/late adjustment relative to FRA
    // Early: first 36 months = 5/9 of 1% per month; beyond = 5/12 of 1% per month
    function earlyReductionFactor(monthsEarly) {
      const first36 = Math.min(monthsEarly, 36);
      const beyond = Math.max(monthsEarly - 36, 0);
      const reduction = (first36 * (5 / 9) + beyond * (5 / 12)) / 100;
      return 1 - reduction;
    }

    // Delayed retirement credits (simplified): 2/3 of 1% per month (8%/yr)
    function delayedCreditFactor(monthsDelayed) {
      const credit = (monthsDelayed * (2 / 3)) / 100;
      return 1 + credit;
    }

    function computeMonthlyAtClaim(piaAtFRA, birthYear, claimAgeYears, claimMonth) {
      const fra = getFRA(birthYear);
      const fraTotalMonths = fra.years * 12 + fra.months;

      // Cap claim age at 70 for delayed credits
      const claimTotalMonthsRaw = claimAgeYears * 12 + claimMonth;
      const maxClaimMonths = 70 * 12;
      const claimTotalMonths = Math.min(claimTotalMonthsRaw, maxClaimMonths);

      const diffMonths = claimTotalMonths - fraTotalMonths;

      let factor = 1;
      if (diffMonths < 0) factor = earlyReductionFactor(Math.abs(diffMonths));
      if (diffMonths > 0) factor = delayedCreditFactor(diffMonths);

      return { fra: fra, claimTotalMonths: claimTotalMonths, monthly: piaAtFRA * factor };
    }

    function renderAssumptions(assumptions) {
      const section = $("assumptions");
      const plain = $("assumptionsPlain");
      if (!section || !plain) return;

      const parts = [];
      parts.push(`End age: ${assumptions.endAge}`);
      parts.push(`COLA assumption: ${assumptions.colaRatePercent}% (applied once per year)`);
      parts.push(`Person: birth date ${assumptions.person.birthDate}; benefit at full retirement age (monthly) ${fmtMoney(assumptions.person.piaMonthlyAtFRA)}.`);
      parts.push(`Claim option A: ${assumptions.claimOptionA.claimAgeYears} years, month ${assumptions.claimOptionA.claimMonth}.`);
      parts.push(`Claim option B: ${assumptions.claimOptionB.claimAgeYears} years, month ${assumptions.claimOptionB.claimMonth}.`);

      plain.textContent = parts.join(" ");
      section.style.display = "block";
    }

    function tableHtml(headers, rows) {
      let html = '<div class="results-block">';
      html += '<table class="results-table">';
      html += "<thead><tr>";
      headers.forEach((h) => (html += `<th>${h}</th>`));
      html += "</tr></thead><tbody>";
      rows.forEach((r) => {
        html += "<tr>";
        r.forEach((cell) => (html += `<td>${cell}</td>`));
        html += "</tr>";
      });
      html += "</tbody></table></div>";
      return html;
    }

    function buildYearRowsForOption(birthYear, pia, claimAgeYears, claimMonth, startDisplayAge, endAge, colaRate) {
      const computed = computeMonthlyAtClaim(pia, birthYear, claimAgeYears, claimMonth);

      let currentMonthly = computed.monthly;
      let cumulative = 0;

      const rows = [];

      for (let age = startDisplayAge; age <= endAge; age++) {
        const year = birthYear + age;

        if (age < claimAgeYears) {
          rows.push([String(year), String(age), fmtMoney(0), fmtMoney(0), fmtMoney(cumulative)]);
          continue;
        }

        if (age > claimAgeYears) currentMonthly *= 1 + colaRate / 100;

        const annual = currentMonthly * 12;
        cumulative += annual;

        rows.push([String(year), String(age), fmtMoney(currentMonthly), fmtMoney(annual), fmtMoney(cumulative)]);
      }

      return { computed, rows };
    }

    function renderComparison(params) {
      const { birthDate, pia, claimA, claimB, endAge, colaRate } = params;

      const birthYear = birthDate.getFullYear();
      const startDisplayAge = Math.min(claimA.claimAgeYears, claimB.claimAgeYears);
      const laterStartAge = Math.max(claimA.claimAgeYears, claimB.claimAgeYears);

      const optA = buildYearRowsForOption(birthYear, pia, claimA.claimAgeYears, claimA.claimMonth, startDisplayAge, endAge, colaRate);
      const optB = buildYearRowsForOption(birthYear, pia, claimB.claimAgeYears, claimB.claimMonth, startDisplayAge, endAge, colaRate);

      function computeCumulativeTotal(birthYear, pia, claimAgeYears, claimMonth, startDisplayAge, endAge, colaRate) {
        const computed = computeMonthlyAtClaim(pia, birthYear, claimAgeYears, claimMonth);
        let currentMonthly = computed.monthly;
        let cumulative = 0;

        for (let age = startDisplayAge; age <= endAge; age++) {
          if (age < claimAgeYears) continue;
          if (age > claimAgeYears) currentMonthly *= 1 + colaRate / 100;
          cumulative += currentMonthly * 12;
        }

        return cumulative;
      }

      const totalA = computeCumulativeTotal(birthYear, pia, claimA.claimAgeYears, claimA.claimMonth, startDisplayAge, endAge, colaRate);
      const totalB = computeCumulativeTotal(birthYear, pia, claimB.claimAgeYears, claimB.claimMonth, startDisplayAge, endAge, colaRate);
      const gainB = totalB - totalA;

      // Break-even: first age where cumulative(B) >= cumulative(A) (or vice versa)
      let breakEvenText = "";

      // Determine which option starts earlier/later (for clearer wording)
      const aStartMonths = claimA.claimAgeYears * 12 + claimA.claimMonth;
      const bStartMonths = claimB.claimAgeYears * 12 + claimB.claimMonth;
      const earlierLabel = aStartMonths <= bStartMonths ? "A" : "B";
      const laterLabel = aStartMonths <= bStartMonths ? "B" : "A";
      const earlierStartAge = aStartMonths <= bStartMonths ? claimA.claimAgeYears : claimB.claimAgeYears;
      const laterStartAgeYears = aStartMonths <= bStartMonths ? claimB.claimAgeYears : claimA.claimAgeYears;
      const startsSameTime = aStartMonths === bStartMonths;

      // Compute break-even using raw running totals.
      // Only look for a break-even AFTER the later-starting option has begun.
      let cumAraw = 0;
      let cumBraw = 0;

      let currentMonthlyA = optA.computed.monthly;
      let currentMonthlyB = optB.computed.monthly;

      // Track who is ahead right after the later option begins.
      let leaderAfterLateStart = null; // "A" | "B" | null

      for (let age = startDisplayAge; age <= endAge; age++) {
        const year = birthYear + age;

        // Option A
        if (age >= claimA.claimAgeYears) {
          if (age > claimA.claimAgeYears) currentMonthlyA *= 1 + colaRate / 100;
          cumAraw += currentMonthlyA * 12;
        }

        // Option B
        if (age >= claimB.claimAgeYears) {
          if (age > claimB.claimAgeYears) currentMonthlyB *= 1 + colaRate / 100;
          cumBraw += currentMonthlyB * 12;
        }

        // Establish baseline leader at the first age when BOTH have started.
        if (age === laterStartAge) {
          if (cumAraw > cumBraw) leaderAfterLateStart = "A";
          else if (cumBraw > cumAraw) leaderAfterLateStart = "B";
          else leaderAfterLateStart = null;
          continue;
        }

        // After both have started, detect a lead change.
        if (age > laterStartAge) {
          const currentLeader = cumAraw > cumBraw ? "A" : (cumBraw > cumAraw ? "B" : null);

          // If it was tied at laterStartAge, treat the first non-tie as the baseline leader.
          if (!leaderAfterLateStart && currentLeader) {
            leaderAfterLateStart = currentLeader;
            continue;
          }

          if (leaderAfterLateStart && currentLeader && currentLeader !== leaderAfterLateStart) {
            const yearsToBreakEven = age - laterStartAge;
            breakEvenText = `${yearsToBreakEven} years to catch up, at age ${age} (year ${year}) in your case. From that point on Option ${laterLabel} gains.`;
            break;
          }
        }
      }

      // If no break-even was found, state that explicitly with the relevant end age/year.
      if (!breakEvenText) {
        const endYear = birthYear + endAge;
        breakEvenText = `does not catch up by age ${endAge} (year ${endYear}) in your case.`;
      }

      let html = "<h2>Results</h2>";
      if (startsSameTime) {
        html += `<p>Option A and Option B both start at age ${earlierStartAge}. ${breakEvenText}</p>`;
      } else {
        html += `<p>Even though Option ${earlierLabel} (starting at age ${earlierStartAge}) has a head start, it only takes Option ${laterLabel} (starting at age ${laterStartAgeYears}) ${breakEvenText}</p>`;
      }

      html += `<p>Option A totals to age ${endAge}: ${fmtMoney(totalA)}</p>`;
      html += `<p>Option B totals to age ${endAge}: ${fmtMoney(totalB)}</p>`;
      html += `<p>Total gain to age ${endAge} with Option B: ${fmtMoney(gainB)}</p>`;

      html += `<h3>Option A yearly projection (starting at age ${startDisplayAge})</h3>`;
      html += tableHtml(["Year", "Age", "Monthly Benefit", "Annual Benefit", "Cumulative"], optA.rows);

      html += `<h3>Option B yearly projection (starting at age ${startDisplayAge})</h3>`;
      html += tableHtml(["Year", "Age", "Monthly Benefit", "Annual Benefit", "Cumulative"], optB.rows);

      $("results").innerHTML = html;
    }

    function buildAssumptionsObject(shared, person, claimA, claimB) {
      return {
        endAge: shared.endAge,
        colaRatePercent: shared.colaRate,
        colaMonth: shared.colaMonth,
        breakEvenBaseline: "FRA",
        benefitModel: "retirement-only",
        exclusions: ["no taxes", "no earnings test", "no Medicare / IRMAA", "no spousal benefits"],
        person: {
          birthDate: person.birthDateStr || "(not set)",
          piaMonthlyAtFRA: person.pia,
        },
        claimOptionA: {
          claimAgeYears: claimA.claimAgeYears,
          claimMonth: claimA.claimMonth,
        },
        claimOptionB: {
          claimAgeYears: claimB.claimAgeYears,
          claimMonth: claimB.claimMonth,
        },
      };
    }


    function onCalculate() {
      const resultsEl = $("results");
      if (resultsEl) resultsEl.innerHTML = "";

      const shared = {
        endAge: $("endAge") ? parseInt($("endAge").value, 10) : 90,
        colaRate: $("colaRate") ? parseFloat($("colaRate").value) : 0,
        colaMonth: $("colaMonth") ? $("colaMonth").value : "January",
      };

      const aBirth = parseDateValue($("aBirthDate"));
      if (aBirth) {
        const by = aBirth.getFullYear();
        if (by < 1900 || by > 2100) {
          $("results").innerHTML = "<p>Please enter a valid birth date using the date picker (4-digit year).</p>";
          return;
        }
      }
      const aBirthStr = $("aBirthDate") ? $("aBirthDate").value : "";
      const aPia = $("aPIA") ? parseFloat($("aPIA").value) : NaN;

      const claimAgeYearsA = $("aClaimAgeA") ? parseInt($("aClaimAgeA").value, 10) : 0;
      const claimMonthA = $("aClaimMonthA") ? parseInt($("aClaimMonthA").value, 10) : 0;
      const claimAgeYearsB = $("aClaimAgeB") ? parseInt($("aClaimAgeB").value, 10) : 0;
      const claimMonthB = $("aClaimMonthB") ? parseInt($("aClaimMonthB").value, 10) : 0;

      const person = {
        birthDate: aBirth,
        birthDateStr: aBirthStr,
        pia: aPia,
      };

      const claimA = { claimAgeYears: claimAgeYearsA, claimMonth: claimMonthA };
      const claimB = { claimAgeYears: claimAgeYearsB, claimMonth: claimMonthB };

      renderAssumptions(buildAssumptionsObject(shared, person, claimA, claimB));

      if (!person.birthDate || !isFinite(person.pia)) {
        $("results").innerHTML = "<p>Please enter a valid birth date (use the date picker) and your monthly benefit at Full Retirement Age (FRA), then click Calculate.</p>";
        return;
      }

      renderComparison({
        birthDate: person.birthDate,
        pia: person.pia,
        claimA: claimA,
        claimB: claimB,
        endAge: shared.endAge,
        colaRate: shared.colaRate,
      });
    }

    document.addEventListener("DOMContentLoaded", function () {
      const btn = $("calculate");
      if (btn) btn.addEventListener("click", onCalculate);
    });
  })();
  </script>
</body>
</html>