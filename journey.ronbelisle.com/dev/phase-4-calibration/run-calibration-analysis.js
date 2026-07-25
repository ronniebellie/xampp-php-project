/**
 * Offline calibration runner — development only.
 * Usage: jsc run-calibration-analysis.js > calibration-run-raw.json
 */
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/phase4-provisional-engine.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/calibration-data.js');

var eng = Phase4ProvisionalEngine;
var data = Phase4CalibrationData;

function money(n) {
    if (n == null || !isFinite(Number(n))) return null;
    return Math.round(Number(n));
}

function adjustments(run) {
    var overall = run.overall.code;
    if (overall === 'holds') {
        return ['Keep the Phase 3 plan as-is and revisit it later'];
    }
    var opts = [];
    var mi = run.mostImportant && run.mostImportant.id;
    var impacts = {
        weakerGrowth: run.scenarios.weakerGrowth.impact.code,
        earlyDecline: run.scenarios.earlyDecline.impact.code,
        longerRetirement: run.scenarios.longerRetirement.impact.code
    };
    function rank(c) {
        return c === 'severe' ? 3 : c === 'noticeable' ? 2 : 1;
    }
    var order = ['earlyDecline', 'weakerGrowth', 'longerRetirement'].sort(function (a, b) {
        var d = rank(impacts[b]) - rank(impacts[a]);
        if (d) return d;
        if (a === mi) return -1;
        if (b === mi) return 1;
        return 0;
    });
    order.forEach(function (id) {
        if (rank(impacts[id]) < 2) return;
        if (id === 'earlyDecline') {
            opts.push('Temporarily reduce spending after a market decline');
            opts.push('Reduce planned spending');
        } else if (id === 'weakerGrowth') {
            opts.push('Reduce planned spending');
            opts.push('Increase retirement savings');
        } else if (id === 'longerRetirement') {
            opts.push('Delay retirement or withdrawals');
            opts.push('Reduce planned spending');
        }
    });
    var seen = {};
    var out = [];
    opts.forEach(function (o) {
        if (!seen[o]) {
            seen[o] = 1;
            out.push(o);
        }
    });
    out.push('Keep the Phase 3 plan as-is and revisit it later');
    return out.slice(0, 3);
}

function judgment(plan, run) {
    var expO = plan.expectedOverall;
    var expD = plan.expectedDominant;
    var overall = run.overall.code;
    var dom = run.mostImportant.id || 'none';
    var flags = [];

    var overallMatch = false;
    if (expO === 'holds' || expO === 'sensitive' || expO === 'needs') {
        overallMatch = expO === overall;
    } else if (expO === 'holds_or_sensitive') {
        overallMatch = overall === 'holds' || overall === 'sensitive';
    } else if (expO === 'sensitive_or_needs') {
        overallMatch = overall === 'sensitive' || overall === 'needs';
    }

    var dominantMatch = true;
    if (expD === 'none') dominantMatch = dom === 'none';
    else if (expD === 'any') dominantMatch = true;
    else dominantMatch = dom === expD;

    if ((plan.id === 'A' || plan.id === 'C' || plan.id === 'E') &&
        (overall === 'sensitive' || overall === 'needs')) {
        flags.push('Too harsh');
    }
    if (plan.id === 'D' && overall === 'holds') {
        flags.push('Too soft');
    }
    if (plan.id === 'B' && overall === 'needs') {
        flags.push('Too harsh');
    }
    if (plan.id === 'F' &&
        run.scenarios.longerRetirement.impact.code === 'little' &&
        dom !== 'longerRetirement') {
        flags.push('Needs human review');
    }
    if (!dominantMatch && expD !== 'any') {
        flags.push('Unexpected dominant stress');
    }

    if (!flags.length) {
        if (overallMatch) flags.push('Reasonable match');
        else if (expO === 'holds_or_sensitive' && overall === 'needs') flags.push('Too harsh');
        else if (expO === 'sensitive_or_needs' && overall === 'holds') flags.push('Too soft');
        else if (expO === 'holds' && overall !== 'holds') flags.push('Too harsh');
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

function summarizePath(path) {
    return {
        endingBalance: money(path.endingBalance),
        depletedYear: path.depletedYear,
        yearsFunded: path.yearsFunded,
        lastedFullHorizon: !!path.lastedFullHorizon,
        cannotFundFirstYear: !!path.cannotFundFirstYear,
        startingBalanceAfterShock: path.startingBalanceAfterShock != null
            ? money(path.startingBalanceAfterShock)
            : null
    };
}

function runOne(plan, pack) {
    var run = eng.runStressTest(plan, pack);
    run.flags = eng.judgmentFlags(run);
    return {
        planId: plan.id,
        planName: plan.name,
        expectedOverall: plan.expectedOverall,
        expectedDominant: plan.expectedDominant,
        phase3: {
            ratePct: run.plan.phase3.ratePct,
            code: run.plan.phase3.code,
            label: run.plan.phase3.label,
            annualNeed: money(run.plan.annualFromSavings),
            monthlyNeed: money(run.plan.monthlyFromSavings),
            balance: money(run.plan.savingsBalance)
        },
        packId: pack.id,
        parameters: run.parameters,
        baseReference: summarizePath(run.baseReference),
        scenarios: {
            weakerGrowth: {
                impact: run.scenarios.weakerGrowth.impact,
                path: summarizePath(run.scenarios.weakerGrowth.path),
                endingRatio: run.scenarios.weakerGrowth.endingRatio,
                compareEndingBalance: money(run.scenarios.weakerGrowth.compareEndingBalance),
                compareDepletedYear: run.scenarios.weakerGrowth.compareDepletedYear
            },
            earlyDecline: {
                impact: run.scenarios.earlyDecline.impact,
                path: summarizePath(run.scenarios.earlyDecline.path),
                endingRatio: run.scenarios.earlyDecline.endingRatio,
                compareEndingBalance: money(run.scenarios.earlyDecline.compareEndingBalance)
            },
            longerRetirement: {
                impact: run.scenarios.longerRetirement.impact,
                path: summarizePath(run.scenarios.longerRetirement.path),
                endingRatio: run.scenarios.longerRetirement.endingRatio,
                compareEndingBalance: money(run.scenarios.longerRetirement.compareEndingBalance)
            }
        },
        overall: run.overall,
        mostImportant: {
            id: run.mostImportant.id || null,
            name: run.mostImportant.name,
            impact: run.mostImportant.impact || null
        },
        suggestedAdjustments: adjustments(run),
        comparisonFlags: judgment(plan, run),
        engineFlags: run.flags
    };
}

function projectWithdrawThenDecline(balance, annualNeed, years, growthRate, declinePct) {
    var b = Math.max(0, balance);
    var w = Math.max(0, annualNeed);
    var g = growthRate;
    var depletedYear = null;
    var yearsFunded = 0;
    var cannotFundFirstYear = w > 0 && b + 1e-9 < w;

    for (var year = 1; year <= years; year++) {
        if (b <= 1e-9) {
            if (depletedYear === null) depletedYear = year;
            b = 0;
            continue;
        }
        var withdrawn = Math.min(w, b);
        var after = b - withdrawn;
        if (withdrawn + 1e-9 >= w) yearsFunded++;
        else if (depletedYear === null) depletedYear = year;

        if (year === 1) {
            after = Math.max(0, after * (1 + declinePct / 100));
        }
        b = after * (1 + g);
        if (b < 0) b = 0;
        if (b <= 1e-9 && depletedYear === null) depletedYear = year;
    }
    return {
        endingBalance: money(b),
        depletedYear: depletedYear,
        yearsFunded: yearsFunded,
        lastedFullHorizon: depletedYear === null && (w === 0 || yearsFunded >= years),
        cannotFundFirstYear: cannotFundFirstYear,
        ordering: 'withdraw_then_decline'
    };
}

function classifyEarly(basePath, earlyPath, pack) {
    var ratioFloor = pack.endingBalanceRatioFloor;
    var earlierYears = pack.earlierDepletionYears;
    if (earlyPath.cannotFundFirstYear) {
        return { code: 'severe', reason: 'Cannot fund first year' };
    }
    var baseDep = basePath.depletedYear !== null;
    var scenDep = earlyPath.depletedYear !== null;
    var eb = basePath.endingBalance || 0;
    var es = earlyPath.endingBalance || 0;
    var ratio = eb > 0 ? es / eb : null;
    if (scenDep && !baseDep) {
        return { code: 'severe', reason: 'Depletes while base does not' };
    }
    if (scenDep && baseDep) {
        var earlierBy = basePath.depletedYear - earlyPath.depletedYear;
        if (earlierBy >= earlierYears) {
            return { code: 'severe', reason: 'Depletes >= ' + earlierYears + 'y earlier' };
        }
        if (earlierBy > 0) {
            return { code: 'noticeable', reason: 'Depletes earlier' };
        }
    }
    if (!scenDep) {
        if (ratio === null || ratio >= ratioFloor) {
            return { code: 'little', reason: 'Lasts; ratio ok' };
        }
        return {
            code: 'noticeable',
            reason: 'Lasts but ratio ' + (ratio * 100).toFixed(1) + '%'
        };
    }
    return { code: 'severe', reason: 'Does not fully fund' };
}

function aggregate(impacts, phase3Code, pack) {
    var severe = 0;
    var noticeable = 0;
    ['weakerGrowth', 'earlyDecline', 'longerRetirement'].forEach(function (id) {
        if (impacts[id] === 'severe') severe++;
        else if (impacts[id] === 'noticeable') noticeable++;
    });
    if (severe >= 2 ||
        (pack.difficultPlusAnySevereNeedsAdjustment && phase3Code === 'difficult' && severe >= 1)) {
        return 'needs';
    }
    if (severe === 1 || noticeable >= 2) return 'sensitive';
    return 'holds';
}

var packIds = ['mild', 'central', 'strict'];
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

var planB = fixtures.filter(function (f) {
    return f.id === 'B';
})[0];
var central = data.PACKS.central;
var bRun = eng.runStressTest(planB, central);
var B = bRun.plan.savingsBalance;
var W = bRun.plan.annualFromSavings;
var basePath = eng.projectPath(B, W, 30, 0.025, 0);
var weakPath = eng.projectPath(B, W, 30, 0.005, 0);
var earlyPath = eng.projectPath(B, W, 30, 0.025, -20);
var longPath = eng.projectPath(B, W, 35, 0.025, 0);

var impactsB = {
    weakerGrowth: bRun.scenarios.weakerGrowth.impact.code,
    earlyDecline: bRun.scenarios.earlyDecline.impact.code,
    longerRetirement: bRun.scenarios.longerRetirement.impact.code
};

var orderingPlans = fixtures.filter(function (f) {
    return f.id === 'B' || f.id === 'D' || f.id === 'E';
});
var ordering = orderingPlans.map(function (plan) {
    var n = eng.normalizePlan(plan);
    var pack = central;
    var years = pack.baseHorizonYears;
    var g = pack.baseGrowthRate;
    var drop = pack.earlyDeclinePct;
    var full = eng.runStressTest(plan, pack);
    var base = eng.projectPath(n.savingsBalance, n.annualFromSavings, years, g, 0);
    var declineFirst = eng.projectPath(n.savingsBalance, n.annualFromSavings, years, g, drop);
    var withdrawFirst = projectWithdrawThenDecline(
        n.savingsBalance,
        n.annualFromSavings,
        years,
        g,
        drop
    );
    var weakImp = full.scenarios.weakerGrowth.impact.code;
    var longImp = full.scenarios.longerRetirement.impact.code;
    var baseSum = {
        endingBalance: base.endingBalance,
        depletedYear: base.depletedYear
    };
    var c1 = classifyEarly(baseSum, declineFirst, pack);
    var c2 = classifyEarly(baseSum, {
        endingBalance: withdrawFirst.endingBalance,
        depletedYear: withdrawFirst.depletedYear,
        cannotFundFirstYear: withdrawFirst.cannotFundFirstYear
    }, pack);
    var overallA = aggregate(
        { weakerGrowth: weakImp, earlyDecline: c1.code, longerRetirement: longImp },
        n.phase3.code,
        pack
    );
    var overallB = aggregate(
        { weakerGrowth: weakImp, earlyDecline: c2.code, longerRetirement: longImp },
        n.phase3.code,
        pack
    );
    return {
        planId: plan.id,
        ratePct: n.phase3.ratePct,
        phase3: n.phase3.code,
        declineBeforeWithdrawal: {
            startingAfterShock: money(n.savingsBalance * (1 + drop / 100)),
            endingBalance: money(declineFirst.endingBalance),
            depletedYear: declineFirst.depletedYear,
            yearsFunded: declineFirst.yearsFunded,
            cannotFundFirstYear: declineFirst.cannotFundFirstYear,
            impact: c1,
            overallWithSameOtherScenarios: overallA
        },
        withdrawalBeforeDecline: {
            endingBalance: withdrawFirst.endingBalance,
            depletedYear: withdrawFirst.depletedYear,
            yearsFunded: withdrawFirst.yearsFunded,
            cannotFundFirstYear: withdrawFirst.cannotFundFirstYear,
            impact: c2,
            overallWithSameOtherScenarios: overallB
        }
    };
});

var cushionNotes = fixtures.map(function (plan) {
    var pack = central;
    var run = eng.runStressTest(plan, pack);
    var baseEnd = run.baseReference.endingBalance;
    var weakEnd = run.scenarios.weakerGrowth.path.endingBalance;
    var ratio = baseEnd > 0 ? weakEnd / baseEnd : null;
    return {
        planId: plan.id,
        ratePct: run.plan.phase3.ratePct,
        baseEnd: money(baseEnd),
        weakEnd: money(weakEnd),
        weakRatio: ratio,
        weakImpact: run.scenarios.weakerGrowth.impact.code,
        earlyImpact: run.scenarios.earlyDecline.impact.code,
        longImpact: run.scenarios.longerRetirement.impact.code,
        overall: run.overall.code,
        dominant: run.mostImportant.id || 'none'
    };
});

// Counterfactuals for Plan B: soften one lever at a time
function counterfactual(label, packOverride) {
    var pack = {};
    Object.keys(central).forEach(function (k) {
        pack[k] = central[k];
    });
    Object.keys(packOverride).forEach(function (k) {
        pack[k] = packOverride[k];
    });
    pack.id = 'cf';
    pack.name = label;
    var run = eng.runStressTest(planB, pack);
    return {
        label: label,
        overall: run.overall.code,
        severe: run.overall.severeCount,
        noticeable: run.overall.noticeableCount,
        impacts: {
            weakerGrowth: run.scenarios.weakerGrowth.impact.code,
            earlyDecline: run.scenarios.earlyDecline.impact.code,
            longerRetirement: run.scenarios.longerRetirement.impact.code
        },
        dominant: run.mostImportant.id || 'none',
        weakEnd: money(run.scenarios.weakerGrowth.path.endingBalance),
        earlyEnd: money(run.scenarios.earlyDecline.path.endingBalance),
        weakDep: run.scenarios.weakerGrowth.path.depletedYear,
        earlyDep: run.scenarios.earlyDecline.path.depletedYear
    };
}

var planBCounterfactuals = [
    counterfactual('Central as-is', {}),
    counterfactual('Weaker growth 1.0% instead of 0.5%', { weakerGrowthRate: 0.01 }),
    counterfactual('Weaker growth 1.5%', { weakerGrowthRate: 0.015 }),
    counterfactual('Early decline -15% instead of -20%', { earlyDeclinePct: -15 }),
    counterfactual('Early decline -12%', { earlyDeclinePct: -12 }),
    counterfactual('Horizon 25y / long 30y', {
        baseHorizonYears: 25,
        longerExtensionYears: 5,
        longerHorizonYears: 30
    }),
    counterfactual('Ratio floor 0.55 (does not help if depleted)', {
        endingBalanceRatioFloor: 0.55
    }),
    counterfactual('Mild pack assumptions on Plan B', data.PACKS.mild)
];

// Aggregation-only counterfactual keeping central impacts
function aggOnly(label, impacts, phase3Code) {
    return {
        label: label,
        overall: aggregate(impacts, phase3Code, central),
        impacts: impacts
    };
}

var aggregationCounterfactuals = [
    aggOnly('Current impacts + current aggregation', impactsB, 'workable'),
    aggOnly('If earlyDecline forced to noticeable only', {
        weakerGrowth: 'severe',
        earlyDecline: 'noticeable',
        longerRetirement: 'noticeable'
    }, 'workable'),
    aggOnly('If weakerGrowth forced to noticeable only', {
        weakerGrowth: 'noticeable',
        earlyDecline: 'severe',
        longerRetirement: 'noticeable'
    }, 'workable'),
    aggOnly('If both severe→ but aggregation = one severe is Sensitive (hypothetical)', {
        weakerGrowth: 'severe',
        earlyDecline: 'severe',
        longerRetirement: 'noticeable'
    }, 'workable')
];
// Note: last one still returns needs under current aggregate(); show desired rule separately
aggregationCounterfactuals.push({
    label: 'Desired rule: exactly one severe OR two+ noticeable = Sensitive; two+ severe = Needs',
    overall: 'sensitive',
    note: 'Under this revised rule, Plan B central (2 severe + 1 noticeable) would still be Needs. To get Sensitive, need to reduce to one severe.',
    impacts: impactsB
});
aggregationCounterfactuals.push({
    label: 'Revised rule + treat “depletes only after year 25/30” as noticeable not severe',
    overall: 'sensitive',
    note: 'If late depletion (e.g. earlyDecline y28 of 30) were noticeable, impacts become severe+noticeable+noticeable → Sensitive.',
    impacts: {
        weakerGrowth: 'severe',
        earlyDecline: 'noticeable',
        longerRetirement: 'noticeable'
    }
});

var report = {
    generatedAt: new Date().toISOString(),
    planGIncluded: false,
    planGNote: 'Plan G omitted — no session Phase 3 values were supplied; none were inferred or hard-coded.',
    packs: data.PACKS,
    fixtures: fixtures.map(function (f) {
        var n = eng.normalizePlan(f);
        return {
            id: f.id,
            name: f.name,
            persona: f.persona,
            notes: f.notes,
            monthlySpending: f.monthlySpending,
            monthlySocialSecurity: f.monthlySocialSecurity,
            monthlyOtherIncome: f.monthlyOtherIncome,
            savingsBalance: f.savingsBalance,
            annualNeed: n.annualFromSavings,
            ratePct: n.phase3.ratePct,
            phase3: n.phase3.code,
            expectedOverall: f.expectedOverall,
            expectedDominant: f.expectedDominant
        };
    }),
    results: results,
    planBCentralDiagnosis: {
        inputs: {
            B: B,
            W: W,
            ratePct: bRun.plan.phase3.ratePct,
            phase3: bRun.plan.phase3.code
        },
        paths: {
            base: summarizePath(basePath),
            weakerGrowth: summarizePath(weakPath),
            earlyDecline: summarizePath(earlyPath),
            longerRetirement: summarizePath(longPath)
        },
        ratios: {
            weakVsBase: basePath.endingBalance > 0
                ? weakPath.endingBalance / basePath.endingBalance
                : null,
            earlyVsBase: basePath.endingBalance > 0
                ? earlyPath.endingBalance / basePath.endingBalance
                : null,
            longEndVsBaseEnd: basePath.endingBalance > 0
                ? longPath.endingBalance / basePath.endingBalance
                : null
        },
        impacts: impactsB,
        overall: bRun.overall,
        mostImportant: {
            id: bRun.mostImportant.id || null,
            name: bRun.mostImportant.name
        },
        aggregationRuleFired:
            'Two or more severe → Needs. weakerGrowth=severe and earlyDecline=severe. Phase 3 difficult boost did NOT fire (phase3=workable).',
        whatMakesWeakSevere:
            'weakPath ending balance is 0 (depletes) while basePath lasts full 30y with ending ~$312k → severe via “depletes when base does not”.',
        whatMakesEarlySevere:
            'earlyPath depletes (year 28) while base does not → severe via same rule. First-year shortfall does NOT fire (after -20% balance is $840k > $42k W).',
        lateDepletionNote:
            'Early-decline depletion at year 28 of 30 is inside the final 20% of the horizon, but classifyImpact returns severe earlier because “depletes when base does not” takes precedence over the late-depletion noticeable rule.',
        scenarioAssumptionCounterfactuals: planBCounterfactuals,
        aggregationCounterfactuals: aggregationCounterfactuals
    },
    earlyDeclineOrdering: ordering,
    cushionNotes: cushionNotes
};

print(JSON.stringify(report));
