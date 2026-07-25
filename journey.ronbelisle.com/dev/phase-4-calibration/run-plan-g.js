/**
 * Run Hybrid Round 2 against a private Plan G file (development only).
 *
 * Setup:
 *   cd journey.ronbelisle.com/dev/phase-4-calibration
 *   cp plan-g.local.json.example plan-g.local.json
 *   # edit plan-g.local.json with real Phase 3 values (gitignored)
 *
 * Run:
 *   jsc run-plan-g.js
 *   jsc run-plan-g.js /absolute/path/to/phase3.json
 *
 * Browser alternative (no localStorage): open the harness UI and paste Phase 3
 * JSON into the Plan G panel, or type the fields manually.
 *
 * Does not read browser localStorage. Does not commit personal data.
 */
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/phase4-provisional-engine.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/calibration-data.js');

var eng = Phase4ProvisionalEngine;
var hybrid = Phase4CalibrationData.PACKS.hybrid_r2;
var defaultPath = '/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/plan-g.local.json';
var path = (typeof arguments !== 'undefined' && arguments.length > 0)
    ? String(arguments[0])
    : defaultPath;

var raw;
try {
    raw = read(path);
} catch (err) {
    print(JSON.stringify({
        ok: false,
        error: 'Plan G file not found or unreadable',
        path: path,
        hint: 'Copy plan-g.local.json.example to plan-g.local.json and fill Phase 3 fields, or pass a JSON path argument. Or use the harness Plan G paste panel.'
    }, null, 2));
}

if (raw) {
    var obj;
    try {
        obj = JSON.parse(raw);
    } catch (err) {
        print(JSON.stringify({ ok: false, error: 'Invalid JSON', path: path, detail: String(err) }, null, 2));
        obj = null;
    }

    if (obj) {
        function num(v) {
            if (v === null || v === undefined || v === '') return null;
            var n = Number(v);
            return Number.isFinite(n) ? n : null;
        }

        var spending = num(obj.monthlySpending);
        var ss = num(obj.monthlySocialSecurity);
        var other = num(obj.monthlyOtherIncome);
        if (other === null) other = 0;
        var balance = num(obj.savingsBalance);
        var monthlyNeed = num(obj.monthlyFromSavings);

        if (spending === null || ss === null || balance === null) {
            print(JSON.stringify({
                ok: false,
                error: 'Required fields: monthlySpending, monthlySocialSecurity, savingsBalance',
                path: path
            }, null, 2));
        } else {
            var plan = {
                id: 'G',
                name: 'Plan G — local Phase 3 (private)',
                persona: 'real_world',
                monthlySpending: spending,
                monthlySocialSecurity: ss,
                monthlyOtherIncome: other,
                savingsBalance: balance,
                expectedOverall: obj.expectedOverall || 'holds_or_sensitive',
                expectedDominant: obj.expectedDominant || 'any',
                isConfigurable: true,
                source: 'plan_g_local_file'
            };
            if (monthlyNeed !== null) plan.monthlyFromSavings = monthlyNeed;

            var run = eng.runStressTest(plan, hybrid);
            var n = run.plan;
            print(JSON.stringify({
                ok: true,
                path: path,
                pack: hybrid.id,
                phase3: {
                    monthlySpending: n.monthlySpending,
                    monthlySocialSecurity: n.monthlySocialSecurity,
                    monthlyOtherIncome: n.monthlyOtherIncome,
                    monthlyFromSavings: n.monthlyFromSavings,
                    annualFromSavings: n.annualFromSavings,
                    savingsBalance: n.savingsBalance,
                    withdrawalRatePct: n.phase3.ratePct,
                    assessment: n.phase3.code,
                    assessmentLabel: n.phase3.label
                },
                overall: run.overall,
                mostImportant: {
                    id: run.mostImportant.id || null,
                    name: run.mostImportant.name
                },
                scenarios: {
                    weakerGrowth: {
                        impact: run.scenarios.weakerGrowth.impact.code,
                        endingBalance: run.scenarios.weakerGrowth.path.endingBalance,
                        depletedYear: run.scenarios.weakerGrowth.path.depletedYear,
                        yearsOfWithdrawals: run.scenarios.weakerGrowth.path.yearsOfWithdrawals
                    },
                    earlyDecline: {
                        impact: run.scenarios.earlyDecline.impact.code,
                        endingBalance: run.scenarios.earlyDecline.path.endingBalance,
                        depletedYear: run.scenarios.earlyDecline.path.depletedYear,
                        yearsOfWithdrawals: run.scenarios.earlyDecline.path.yearsOfWithdrawals
                    },
                    longerRetirement: {
                        impact: run.scenarios.longerRetirement.impact.code,
                        endingBalance: run.scenarios.longerRetirement.path.endingBalance,
                        depletedYear: run.scenarios.longerRetirement.path.depletedYear,
                        yearsOfWithdrawals: run.scenarios.longerRetirement.path.yearsOfWithdrawals
                    }
                },
                baseReference: run.baseReference,
                note: 'Private local run only. Results are not written back to the repository.'
            }, null, 2));
        }
    }
}
