/**
 * Focused classifier edge-case tests (development only).
 * Usage: jsc test-classifier-edge-cases.js
 * Exit: prints JSON summary; non-zero conceptually via failed count in output.
 */
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/phase4-provisional-engine.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/dev/phase-4-calibration/calibration-data.js');

var eng = Phase4ProvisionalEngine;
var hybrid = Phase4CalibrationData.PACKS.hybrid_r2;
var failed = [];
var passed = [];

function expect(name, cond, detail) {
    if (cond) passed.push(name);
    else failed.push(name + (detail ? ' — ' + detail : ''));
}

var pack = {
    endingBalanceRatioFloor: 0.65,
    earlierDepletionYears: 6,
    lateDepletionFraction: 0.2,
    cushionYearsForLittle: 8,
    cushionPctOfStartForLittle: 0.3
};

// --- Direct classifyImpact cases ---

// 1) Same depletion year (both deplete)
var baseSame = eng.projectPath(200000, 48000, 10, 0, 0);
var scenSame = eng.projectPath(200000, 48000, 10, 0, 0);
expect('same-year depletion years equal', baseSame.depletedYear === scenSame.depletedYear,
    'base=' + baseSame.depletedYear + ' scen=' + scenSame.depletedYear);
var cSame = eng.classifyImpact(baseSame, scenSame, 10, pack, 200000, 48000);
expect('same-year => Little', cSame.code === 'little', cSame.code + '/' + cSame.severityKind);
expect('same-year severityKind', cSame.severityKind === 'same_as_base_depletion', cSame.severityKind);

// 2) Scenario depletes later than base
var baseEarly = eng.projectPath(400000, 60000, 15, 0, 0);
var scenLater = eng.projectPath(400000, 60000, 15, 0.08, 0);
expect('later: base depletes', baseEarly.depletedYear !== null, String(baseEarly.depletedYear));
expect('later: scenario later or lasts',
    scenLater.depletedYear === null || scenLater.depletedYear > baseEarly.depletedYear,
    'base=' + baseEarly.depletedYear + ' scen=' + scenLater.depletedYear);
var cLater = eng.classifyImpact(baseEarly, scenLater, 15, pack, 400000, 60000);
expect('later/better => Little', cLater.code === 'little', cLater.code + '/' + cLater.severityKind);
expect('later severityKind better_than_base',
    cLater.severityKind === 'better_than_base_depletion', cLater.severityKind);

// 3) Scenario depletes earlier than base (both deplete)
// Base with some growth lasts longer; scenario with 0 growth depletes sooner.
var baseLater = eng.projectPath(400000, 60000, 20, 0.05, 0);
var scenEarlier = eng.projectPath(400000, 60000, 20, 0, 0);
expect('earlier: both deplete',
    baseLater.depletedYear !== null && scenEarlier.depletedYear !== null,
    'base=' + baseLater.depletedYear + ' scen=' + scenEarlier.depletedYear);
expect('earlier: scenario year < base year',
    scenEarlier.depletedYear < baseLater.depletedYear,
    'base=' + baseLater.depletedYear + ' scen=' + scenEarlier.depletedYear);
var cEarlier = eng.classifyImpact(baseLater, scenEarlier, 20, pack, 400000, 60000);
var earlierBy = baseLater.depletedYear - scenEarlier.depletedYear;
if (earlierBy >= pack.earlierDepletionYears) {
    expect('much earlier => Severe', cEarlier.code === 'severe', cEarlier.code + '/' + cEarlier.severityKind);
} else {
    expect('somewhat earlier => Noticeable', cEarlier.code === 'noticeable', cEarlier.code + '/' + cEarlier.severityKind);
}

// 4) Base lasts, scenario depletes — use explicit path objects for a clean unit case
var lateStart = eng.lateDepletionStartYear(28, 0.2);
var baseLasts = {
    endingBalance: 400000,
    depletedYear: null,
    lastedFullHorizon: true,
    cannotFundFirstYear: false,
    yearsFunded: 28,
    startingBalanceAfterShock: 1000000
};
var scenDepEarly = {
    endingBalance: 0,
    depletedYear: 10,
    lastedFullHorizon: false,
    cannotFundFirstYear: false,
    yearsFunded: 9,
    startingBalanceAfterShock: 1000000
};
expect('fixture base lasts', baseLasts.depletedYear === null && baseLasts.lastedFullHorizon);
expect('fixture scenario depletes early', scenDepEarly.depletedYear !== null && scenDepEarly.depletedYear < lateStart,
    'dep=' + scenDepEarly.depletedYear + ' lateStart=' + lateStart);
var cBaseLasts = eng.classifyImpact(baseLasts, scenDepEarly, 28, pack, 1000000, 50000);
expect('base lasts + early scen deplete => Severe', cBaseLasts.code === 'severe',
    cBaseLasts.code + '/' + cBaseLasts.severityKind);

var scenDepLate = {
    endingBalance: 0,
    depletedYear: lateStart,
    lastedFullHorizon: false,
    cannotFundFirstYear: false,
    yearsFunded: lateStart - 1,
    startingBalanceAfterShock: 1000000
};
var cBaseLastsLate = eng.classifyImpact(baseLasts, scenDepLate, 28, pack, 1000000, 50000);
expect('base lasts + late scen deplete => Noticeable', cBaseLastsLate.code === 'noticeable',
    cBaseLastsLate.code + '/' + cBaseLastsLate.severityKind);

// 5) Both paths last
var bothBase = eng.projectPath(900000, 9600, 28, 0.0275, 0);
var bothScen = eng.projectPath(900000, 9600, 28, 0.01, 0);
expect('both last (base)', bothBase.depletedYear === null);
expect('both last (scen)', bothScen.depletedYear === null);
var cBoth = eng.classifyImpact(bothBase, bothScen, 28, pack, 900000, 9600);
expect('both last => Little or Noticeable (not Severe)', cBoth.code !== 'severe', cBoth.code + '/' + cBoth.severityKind);
expect('both last typically Little via cushion', cBoth.code === 'little', cBoth.code + '/' + cBoth.severityKind);

// --- Zero balance / zero withdrawal ---
var zW = eng.projectPath(100000, 0, 30, 0.02, 0);
expect('zero W lasts', zW.lastedFullHorizon && zW.depletedYear === null);
var zAssess = eng.assessPhase3(0, 100000);
expect('zero need Phase3 workable', zAssess.code === 'workable' && zAssess.ratePct === 0);
var zBalPlan = {
    id: 'Z',
    monthlySpending: 5000,
    monthlySocialSecurity: 1000,
    monthlyOtherIncome: 0,
    savingsBalance: 0
};
var zBalRun = eng.runStressTest(zBalPlan, hybrid);
expect('zero balance with need => difficult Phase3', zBalRun.plan.phase3.code === 'difficult');

// --- Hybrid A–F unchanged expectations ---
var expected = { A: 'holds', B: 'sensitive', C: 'holds', D: 'needs', E: 'holds', F: 'sensitive' };
var hybridOutcomes = {};
Phase4CalibrationData.FIXTURES.filter(function (f) { return !f.isConfigurable; }).forEach(function (f) {
    var run = eng.runStressTest(f, hybrid);
    hybridOutcomes[f.id] = run.overall.code;
    expect('Hybrid ' + f.id + ' => ' + expected[f.id], run.overall.code === expected[f.id],
        'got ' + run.overall.code);
});

// Repeatability
var b1 = eng.runStressTest(Phase4CalibrationData.FIXTURES[1], hybrid);
var b2 = eng.runStressTest(Phase4CalibrationData.FIXTURES[1], hybrid);
expect('repeatable Plan B overall', b1.overall.code === b2.overall.code);
expect('repeatable Plan B weak end',
    b1.scenarios.weakerGrowth.path.endingBalance === b2.scenarios.weakerGrowth.path.endingBalance);

print(JSON.stringify({
    passed: passed.length,
    failed: failed.length,
    failedDetail: failed,
    hybridOutcomes: hybridOutcomes,
    classifierSamples: {
        sameYear: { code: cSame.code, kind: cSame.severityKind },
        laterThanBase: { code: cLater.code, kind: cLater.severityKind },
        earlierThanBase: { code: cEarlier.code, kind: cEarlier.severityKind, earlierBy: earlierBy },
        baseLastsScenDepEarly: { code: cBaseLasts.code, kind: cBaseLasts.severityKind, scenDep: scenDepEarly.depletedYear, lateStart: lateStart },
        baseLastsScenDepLate: { code: cBaseLastsLate.code, kind: cBaseLastsLate.severityKind, scenDep: scenDepLate.depletedYear },
        bothLast: { code: cBoth.code, kind: cBoth.severityKind }
    }
}, null, 2));
