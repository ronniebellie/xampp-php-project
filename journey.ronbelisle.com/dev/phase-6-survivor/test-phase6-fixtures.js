/**
 * Phase 6 Survivor Planning — unit-style fixture checks (development only).
 * Usage (macOS):
 *   /System/Library/Frameworks/JavaScriptCore.framework/Versions/Current/Helpers/jsc test-phase6-fixtures.js
 */
if (typeof window === 'undefined') {
    this.window = this;
}
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/assets/js/phase6-survivor-engine.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/assets/js/phase6-priorities.js');
load('/Applications/XAMPP/xamppfiles/htdocs/journey.ronbelisle.com/assets/js/journey-records.js');

var engine = Phase6SurvivorEngine;
var priorities = Phase6Priorities;
var records = rbJourneyRecords;

var passed = [];
var failed = [];

function expect(name, cond, detail) {
    if (cond) passed.push(name);
    else failed.push(name + (detail ? ' — ' + detail : ''));
}

function idsEqual(a, b) {
    if (!a || !b || a.length !== b.length) return false;
    for (var i = 0; i < a.length; i += 1) {
        if (a[i] !== b[i]) return false;
    }
    return true;
}

function run(plan, q1, q2) {
    return engine.runSurvivorPicture(plan, q1, q2);
}

function priorityIds(result) {
    return priorities.recommendPriorities(result).map(function (o) { return o.id; });
}

function uniqueCount(arr) {
    var seen = {};
    var n = 0;
    arr.forEach(function (id) {
        if (!seen[id]) {
            seen[id] = true;
            n += 1;
        }
    });
    return n;
}

function basePlan(overrides) {
    var p = {
        monthlySocialSecurityAssumption: 4000,
        annualNeededFromRetirementSavings: 48000,
        monthlyNeededFromRetirementSavings: 4000,
        baseCaseAssessment: 'workable',
        temporarySocialSecurityEstimateUsed: false,
        monthlyRetirementSpendingGoal: 9500,
        monthlyOtherDependableIncome: 0,
        retirementSavingsBalance: 1000000,
        assessmentStatus: 'complete',
        saved: true
    };
    if (overrides) {
        Object.keys(overrides).forEach(function (k) { p[k] = overrides[k]; });
    }
    return p;
}

var Q1 = ['recently', 'may_need_review', 'not_yet', 'not_sure'];
var Q2 = ['thought_through', 'discussed_review_again', 'not_reviewed', 'not_sure'];

var EXPECTED_BASE = {
    recently: {
        thought_through: ['none_dominant'],
        discussed_review_again: ['social_security_change', 'survivor_spending_look'],
        not_reviewed: ['social_security_change', 'survivor_spending_look'],
        not_sure: ['social_security_change', 'survivor_spending_look']
    },
    may_need_review: {
        thought_through: ['beneficiary_review'],
        discussed_review_again: ['social_security_change', 'survivor_spending_look'],
        not_reviewed: ['social_security_change', 'survivor_spending_look'],
        not_sure: ['social_security_change', 'survivor_spending_look']
    },
    not_yet: {
        thought_through: ['beneficiary_review'],
        discussed_review_again: ['beneficiary_review', 'social_security_change'],
        not_reviewed: ['beneficiary_review', 'survivor_income_review'],
        not_sure: ['beneficiary_review', 'survivor_income_review']
    },
    not_sure: {
        thought_through: ['beneficiary_review'],
        discussed_review_again: ['beneficiary_review', 'social_security_change'],
        not_reviewed: ['beneficiary_review', 'survivor_income_review'],
        not_sure: ['beneficiary_review', 'survivor_income_review']
    }
};

var plan = basePlan();
Q1.forEach(function (q1) {
    Q2.forEach(function (q2) {
        var result = run(plan, q1, q2);
        var expected = EXPECTED_BASE[q1][q2];
        var name = 'matrix ' + q1 + ' × ' + q2;
        expect(name, idsEqual(result.mainIssueIds, expected),
            'got=[' + result.mainIssueIds.join(',') + '] expected=[' + expected.join(',') + ']');
        var again = run(plan, q1, q2);
        expect(name + ' repeatable', idsEqual(result.mainIssueIds, again.mainIssueIds));
        expect(name + ' ≤2 issues', result.mainIssueIds.length <= 2);
        var pIds = priorityIds(result);
        expect(name + ' ≤3 priorities', pIds.length <= 3);
        expect(name + ' keep present', pIds.indexOf('keep-review-annually') !== -1);
        expect(name + ' unique priorities', pIds.length === uniqueCount(pIds));
    });
});

var noSs = basePlan({ monthlySocialSecurityAssumption: 0 });
var rNoSs = run(noSs, 'recently', 'discussed_review_again');
expect('SS zero: no Tie 2',
    idsEqual(rNoSs.mainIssueIds, ['survivor_spending_look']),
    rNoSs.mainIssueIds.join(','));

var noW = basePlan({
    annualNeededFromRetirementSavings: 0,
    monthlyNeededFromRetirementSavings: 0
});
var rNoW = run(noW, 'recently', 'discussed_review_again');
expect('W zero workable: SS single (not Tie 2)',
    idsEqual(rNoW.mainIssueIds, ['social_security_change']),
    rNoW.mainIssueIds.join(','));

var tightNoW = basePlan({
    annualNeededFromRetirementSavings: 0,
    monthlyNeededFromRetirementSavings: 0,
    baseCaseAssessment: 'close'
});
var rTight = run(tightNoW, 'recently', 'discussed_review_again');
expect('assessment close W=0: spending (not Tie 2)',
    idsEqual(rTight.mainIssueIds, ['survivor_spending_look']),
    rTight.mainIssueIds.join(','));

var difficult = basePlan({
    annualNeededFromRetirementSavings: 0,
    monthlyNeededFromRetirementSavings: 0,
    baseCaseAssessment: 'difficult'
});
var rDiff = run(difficult, 'recently', 'discussed_review_again');
expect('assessment difficult W=0: spending',
    idsEqual(rDiff.mainIssueIds, ['survivor_spending_look']),
    rDiff.mainIssueIds.join(','));

var tempSs = basePlan({ temporarySocialSecurityEstimateUsed: true });
var rTemp = run(tempSs, 'not_yet', 'not_reviewed');
expect('temporary SS guidance present',
    typeof rTemp.guidanceText === 'string' && rTemp.guidanceText.indexOf('temporary') !== -1,
    String(rTemp.guidanceText));

expect('Tie 1',
    idsEqual(run(plan, 'not_yet', 'not_reviewed').mainIssueIds,
        ['beneficiary_review', 'survivor_income_review']));
expect('Tie 2',
    idsEqual(run(plan, 'recently', 'not_reviewed').mainIssueIds,
        ['social_security_change', 'survivor_spending_look']));
expect('Tie 3',
    idsEqual(run(plan, 'not_yet', 'discussed_review_again').mainIssueIds,
        ['beneficiary_review', 'social_security_change']));
expect('none_dominant',
    idsEqual(run(plan, 'recently', 'thought_through').mainIssueIds, ['none_dominant']));
expect('SS alone does not force SS issue when complete',
    idsEqual(run(plan, 'recently', 'thought_through').mainIssueIds, ['none_dominant']));

function mapFor(ids, mode) {
    return priorityIds({ mainIssueIds: ids, pressureMode: mode });
}
expect('map beneficiary',
    idsEqual(mapFor(['beneficiary_review'], 'single'),
        ['review-account-recipients', 'consult-professional', 'keep-review-annually']));
expect('map income',
    idsEqual(mapFor(['survivor_income_review'], 'single'),
        ['review-continuing-income', 'revisit-one-person-spending', 'keep-review-annually']));
expect('map ss',
    idsEqual(mapFor(['social_security_change'], 'single'),
        ['review-survivor-social-security', 'review-continuing-income', 'keep-review-annually']));
expect('map spending',
    idsEqual(mapFor(['survivor_spending_look'], 'single'),
        ['revisit-one-person-spending', 'review-continuing-income', 'keep-review-annually']));
expect('map none',
    idsEqual(mapFor(['none_dominant'], 'none'),
        ['keep-review-annually', 'consult-professional']));
expect('map Tie 1 priorities',
    idsEqual(mapFor(['beneficiary_review', 'survivor_income_review'], 'tied'),
        ['review-account-recipients', 'review-continuing-income', 'keep-review-annually']));
expect('map Tie 2 priorities',
    idsEqual(mapFor(['social_security_change', 'survivor_spending_look'], 'tied'),
        ['review-survivor-social-security', 'revisit-one-person-spending', 'keep-review-annually']));
expect('map Tie 3 priorities',
    idsEqual(mapFor(['beneficiary_review', 'social_security_change'], 'tied'),
        ['review-account-recipients', 'review-survivor-social-security', 'keep-review-annually']));

var created = records.createSurvivorPlanningRecord({
    saved: true,
    phase3Snapshot: { monthlyRetirementSpendingGoal: 9500 },
    assumptions: { assetRecipientReview: 'recently', survivorIncomePreparedness: 'thought_through' },
    result: {
        pressureMode: 'none',
        mainIssueIds: ['none_dominant'],
        issueTitles: ['No single issue stands out strongly from these answers.'],
        issueExplanations: ['An annual review may be enough for now.'],
        guidanceText: null,
        strategyChoicesShown: []
    },
    nextPriorityId: 'keep-review-annually',
    nextPriorityLabel: 'Keep the current approach and review it annually'
}, { journeyComplete: true, reviewed: true });
expect('record phaseId', created.phaseId === 'survivor-planning');
expect('record toolId', created.source && created.source.toolId === 'survivor-planning');
expect('record schemaVersion', created.schemaVersion === 1);
expect('record notAnEstatePlan', created.notAnEstatePlan === true);
expect('record educationalNonAdvice', created.educationalNonAdvice === true);
expect('record downstreamReady', created.downstreamReady === true);
var normalized = records.normalizeSurvivorPlanningRecord(created, false);
expect('normalize keeps phaseId', normalized.phaseId === 'survivor-planning');

var forbidden = /(safe|unsafe|unprotected|shortfall|power of attorney|medical directive)/i;
Object.keys(engine.TITLES).forEach(function (id) {
    expect('title clean ' + id, !forbidden.test(engine.TITLES[id]));
    expect('explanation clean ' + id, !forbidden.test(engine.EXPLANATIONS[id]));
});

print(JSON.stringify({
    passed: passed.length,
    failed: failed.length,
    failures: failed
}, null, 2));

if (failed.length) {
    throw new Error(failed.length + ' fixture checks failed');
}
