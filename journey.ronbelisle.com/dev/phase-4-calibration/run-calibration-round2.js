/**
 * Round 2 offline calibration runner — development only.
 * Usage: jsc run-calibration-round2.js > calibration-run-round2.json
 */
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/phase4-provisional-engine.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/calibration-data.js');

var eng = Phase4ProvisionalEngine;
var data = Phase4CalibrationData;

function money(n) {
    if (n == null || !isFinite(Number(n))) return null;
    return Math.round(Number(n));
}

function yow(n) {
    if (n == null || !isFinite(Number(n))) return null;
    return Math.round(Number(n) * 100) / 100;
}

function judgment(plan, run) {
    var expO = plan.expectedOverall;
    var expD = plan.expectedDominant;
    var overall = run.overall.code;
    var dom = run.mostImportant.id || 'none';
    var flags = [];

    var overallMatch = false;
    if (expO === 'holds' || expO === 'sensitive' || expO === 'needs') overallMatch = expO === overall;
    else if (expO === 'holds_or_sensitive') overallMatch = overall === 'holds' || overall === 'sensitive';
    else if (expO === 'sensitive_or_needs') overallMatch = overall === 'sensitive' || overall === 'needs';

    var dominantMatch = true;
    if (expD === 'none') dominantMatch = dom === 'none';
    else if (expD === 'any') dominantMatch = true;
    else dominantMatch = dom === expD;

    if ((plan.id === 'A' || plan.id === 'C' || plan.id === 'E') &&
        (overall === 'sensitive' || overall === 'needs')) {
        flags.push('Too harsh');
    }
    if (plan.id === 'D' && overall === 'holds') flags.push('Too soft');
    if (plan.id === 'B' && overall === 'needs') flags.push('Too harsh');
    if (plan.id === 'F' &&
        run.scenarios.longerRetirement.impact.code === 'little' &&
        dom !== 'longerRetirement') {
        flags.push('Needs human review');
    }
    if (!dominantMatch && expD !== 'any') flags.push('Unexpected dominant stress');

    if (!flags.length) {
        if (overallMatch) flags.push('Reasonable match');
        else if (expO === 'holds_or_sensitive' && overall === 'needs') flags.push('Too harsh');
        else if (expO === 'sensitive_or_needs' && overall === 'holds') flags.push('Too soft');
        else flags.push('Needs human review');
    }

    var seen = {};
    var uniq = [];
    flags.forEach(function (f) {
        if (!seen[f]) {
            seen[f] = 1;
            uniq.push(f);
        }
    });
    return uniq;
}

function scenSummary(s) {
    return {
        impact: s.impact.code,
        impactLabel: s.impact.label,
        reason: s.impact.reason,
        severityKind: s.impact.severityKind,
        endingBalance: money(s.path.endingBalance),
        depletedYear: s.path.depletedYear,
        yearsFunded: s.path.yearsFunded,
        yearsOfWithdrawals: yow(s.path.yearsOfWithdrawals),
        pctOfStart: s.path.pctOfStart == null ? null : Math.round(s.path.pctOfStart * 1000) / 1000,
        endingRatio: s.endingRatio == null ? null : Math.round(s.endingRatio * 1000) / 1000,
        startingBalanceAfterShock: money(s.path.startingBalanceAfterShock),
        lastedFullHorizon: s.path.lastedFullHorizon,
        cannotFundFirstYear: s.path.cannotFundFirstYear
    };
}

function runOne(plan, pack) {
    var run = eng.runStressTest(plan, pack);
    return {
        planId: plan.id,
        packId: pack.id,
        expectedOverall: plan.expectedOverall,
        expectedDominant: plan.expectedDominant,
        phase3: {
            ratePct: run.plan.phase3.ratePct,
            code: run.plan.phase3.code,
            annualNeed: money(run.plan.annualFromSavings),
            balance: money(run.plan.savingsBalance)
        },
        parameters: run.parameters,
        baseReference: {
            endingBalance: money(run.baseReference.endingBalance),
            depletedYear: run.baseReference.depletedYear,
            yearsOfWithdrawals: yow(run.baseReference.yearsOfWithdrawals),
            lastedFullHorizon: run.baseReference.lastedFullHorizon
        },
        scenarios: {
            weakerGrowth: scenSummary(run.scenarios.weakerGrowth),
            earlyDecline: scenSummary(run.scenarios.earlyDecline),
            longerRetirement: scenSummary(run.scenarios.longerRetirement)
        },
        overall: run.overall,
        mostImportant: {
            id: run.mostImportant.id || null,
            name: run.mostImportant.name
        },
        comparisonFlags: judgment(plan, run)
    };
}

var packIds = ['mild', 'central', 'strict', 'hybrid_r2'];
var fixtures = data.FIXTURES.filter(function (f) {
    return !f.isConfigurable;
});
var results = [];
packIds.forEach(function (pid) {
    var pack = data.PACKS[pid];
    fixtures.forEach(function (plan) {
        results.push(runOne(plan, pack));
    });
});

function find(planId, packId) {
    for (var i = 0; i < results.length; i++) {
        if (results[i].planId === planId && results[i].packId === packId) return results[i];
    }
    return null;
}

var report = {
    generatedAt: new Date().toISOString(),
    round: 2,
    planGIncluded: false,
    planGNote: 'Plan G omitted — no session Phase 3 values were supplied; none were inferred or hard-coded.',
    packs: data.PACKS,
    fixtures: fixtures.map(function (f) {
        var n = eng.normalizePlan(f);
        return {
            id: f.id,
            name: f.name,
            ratePct: n.phase3.ratePct,
            phase3: n.phase3.code,
            annualNeed: n.annualFromSavings,
            savingsBalance: n.savingsBalance,
            expectedOverall: f.expectedOverall,
            expectedDominant: f.expectedDominant
        };
    }),
    results: results,
    matrices: {
        overall: fixtures.map(function (f) {
            return {
                planId: f.id,
                expected: f.expectedOverall,
                mild: find(f.id, 'mild').overall.code,
                central: find(f.id, 'central').overall.code,
                strict: find(f.id, 'strict').overall.code,
                hybrid_r2: find(f.id, 'hybrid_r2').overall.code
            };
        }),
        dominant: fixtures.map(function (f) {
            return {
                planId: f.id,
                expected: f.expectedDominant,
                mild: find(f.id, 'mild').mostImportant.id || 'none',
                central: find(f.id, 'central').mostImportant.id || 'none',
                strict: find(f.id, 'strict').mostImportant.id || 'none',
                hybrid_r2: find(f.id, 'hybrid_r2').mostImportant.id || 'none'
            };
        })
    },
    focus: {
        B: {
            mild: find('B', 'mild'),
            central: find('B', 'central'),
            hybrid_r2: find('B', 'hybrid_r2'),
            strict: find('B', 'strict')
        },
        C: {
            mild: find('C', 'mild'),
            central: find('C', 'central'),
            hybrid_r2: find('C', 'hybrid_r2'),
            strict: find('C', 'strict')
        },
        D: {
            mild: find('D', 'mild'),
            central: find('D', 'central'),
            hybrid_r2: find('D', 'hybrid_r2'),
            strict: find('D', 'strict')
        },
        F: {
            mild: find('F', 'mild'),
            central: find('F', 'central'),
            hybrid_r2: find('F', 'hybrid_r2'),
            strict: find('F', 'strict')
        }
    }
};

print(JSON.stringify(report));
