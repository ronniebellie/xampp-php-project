/**
 * Phase 6 Survivor Planning — product-level issue engine.
 * Educational heuristics only. No shortfall dollars, no legal conclusions,
 * no exact survivor Social Security calculations.
 */
(function (global) {
    'use strict';

    var ISSUE = {
        BENEFICIARY: 'beneficiary_review',
        INCOME: 'survivor_income_review',
        SS: 'social_security_change',
        SPENDING: 'survivor_spending_look',
        NONE: 'none_dominant'
    };

    var TITLES = {};
    TITLES[ISSUE.BENEFICIARY] = 'Who would receive accounts and assets may need review.';
    TITLES[ISSUE.INCOME] = 'Survivor income may need a closer review.';
    TITLES[ISSUE.SS] = 'Social Security changes may deserve attention.';
    TITLES[ISSUE.SPENDING] = 'The survivor spending goal may need another look.';
    TITLES[ISSUE.NONE] = 'No single issue stands out strongly from these answers.';

    var EXPLANATIONS = {};
    EXPLANATIONS[ISSUE.BENEFICIARY] =
        'Knowing who would receive retirement accounts and other financial assets supports continuity. This Journey does not verify those designations.';
    EXPLANATIONS[ISSUE.INCOME] =
        'Some household income may stop or change. Looking at what would continue for the person who remains is a useful next step.';
    EXPLANATIONS[ISSUE.SS] =
        'Social Security for a survivor often differs from the household picture in Phase 3. A closer look may help—without calculating an exact survivor benefit here.';
    EXPLANATIONS[ISSUE.SPENDING] =
        'Living costs for one person often do not fall by half. The household spending goal may need another look as a one-person plan.';
    EXPLANATIONS[ISSUE.NONE] =
        'An annual review may be enough for now. Revisit survivor planning as life and accounts change.';

    function num(value, fallback) {
        var n = Number(value);
        return Number.isFinite(n) ? n : fallback;
    }

    function annualNeedFromPlan(plan) {
        var annual = num(plan.annualNeededFromRetirementSavings, NaN);
        if (Number.isFinite(annual)) return Math.max(0, annual);
        var monthly = num(plan.monthlyNeededFromRetirementSavings, NaN);
        if (Number.isFinite(monthly)) return Math.max(0, monthly * 12);
        return 0;
    }

    function analyzeSignals(plan, assetRecipientReview, survivorIncomePreparedness) {
        var monthlySs = num(plan.monthlySocialSecurityAssumption, 0);
        var W = annualNeedFromPlan(plan);
        var assessment = plan.baseCaseAssessment || '';

        return {
            ssPresent: monthlySs > 0,
            hasWithdrawalNeed: W > 0,
            assessmentTight: assessment === 'close' || assessment === 'difficult',
            q1Strong: assetRecipientReview === 'not_yet' || assetRecipientReview === 'not_sure',
            q1Moderate: assetRecipientReview === 'may_need_review',
            q1Complete: assetRecipientReview === 'recently',
            q2Strong: survivorIncomePreparedness === 'not_reviewed' || survivorIncomePreparedness === 'not_sure',
            q2Moderate: survivorIncomePreparedness === 'discussed_review_again',
            q2Complete: survivorIncomePreparedness === 'thought_through',
            temporarySs: plan.temporarySocialSecurityEstimateUsed === true
        };
    }

    function selectIssues(s) {
        // Tie rules first (exactly two issues).
        if (s.q1Strong && s.q2Strong) {
            return { mode: 'tied', ids: [ISSUE.BENEFICIARY, ISSUE.INCOME] };
        }
        if (s.ssPresent && s.hasWithdrawalNeed && !s.q2Complete && !s.q1Strong) {
            return { mode: 'tied', ids: [ISSUE.SS, ISSUE.SPENDING] };
        }
        if (s.q1Strong && s.ssPresent && !s.q2Complete && !s.q2Strong) {
            return { mode: 'tied', ids: [ISSUE.BENEFICIARY, ISSUE.SS] };
        }

        // Single-issue order.
        if (s.q1Strong) return { mode: 'single', ids: [ISSUE.BENEFICIARY] };
        if (s.q2Strong) return { mode: 'single', ids: [ISSUE.INCOME] };
        if (s.q1Moderate && !s.q2Complete) return { mode: 'single', ids: [ISSUE.BENEFICIARY] };
        if ((s.hasWithdrawalNeed || s.assessmentTight) && !s.q2Complete) {
            return { mode: 'single', ids: [ISSUE.SPENDING] };
        }
        if (s.ssPresent && !s.q2Complete) return { mode: 'single', ids: [ISSUE.SS] };
        if (s.q2Moderate) return { mode: 'single', ids: [ISSUE.INCOME] };
        if (s.q1Moderate) return { mode: 'single', ids: [ISSUE.BENEFICIARY] };
        if (s.q1Complete && s.q2Complete) return { mode: 'none', ids: [ISSUE.NONE] };
        return { mode: 'none', ids: [ISSUE.NONE] };
    }

    function buildPresentation(selection) {
        var mode = selection.mode;
        var ids = selection.ids.slice();
        if (mode === 'none' || (ids.length === 1 && ids[0] === ISSUE.NONE)) {
            return {
                pressureMode: 'none',
                mainIssueIds: [ISSUE.NONE],
                issueTitles: [TITLES[ISSUE.NONE]],
                issueExplanations: [EXPLANATIONS[ISSUE.NONE]]
            };
        }
        if (mode === 'tied' && ids.length >= 2) {
            return {
                pressureMode: 'tied',
                mainIssueIds: ids.slice(0, 2),
                issueTitles: [TITLES[ids[0]], TITLES[ids[1]]],
                issueExplanations: [EXPLANATIONS[ids[0]], EXPLANATIONS[ids[1]]]
            };
        }
        return {
            pressureMode: 'single',
            mainIssueIds: [ids[0]],
            issueTitles: [TITLES[ids[0]]],
            issueExplanations: [EXPLANATIONS[ids[0]]]
        };
    }

    function guidanceText(s, presentation) {
        if (presentation.pressureMode === 'none') {
            return 'Your answers suggest an annual check-in may be enough for now.';
        }
        if (s.temporarySs) {
            return 'This review uses your Phase 3 Social Security assumption, including any temporary estimate.';
        }
        return null;
    }

    function runSurvivorPicture(planInput, assetRecipientReview, survivorIncomePreparedness) {
        var plan = planInput || {};
        var s = analyzeSignals(plan, assetRecipientReview || '', survivorIncomePreparedness || '');
        var selection = selectIssues(s);
        var presentation = buildPresentation(selection);

        return {
            signals: {
                ssPresent: s.ssPresent,
                hasWithdrawalNeed: s.hasWithdrawalNeed,
                assessmentTight: s.assessmentTight,
                q1Strong: s.q1Strong,
                q1Moderate: s.q1Moderate,
                q1Complete: s.q1Complete,
                q2Strong: s.q2Strong,
                q2Moderate: s.q2Moderate,
                q2Complete: s.q2Complete
            },
            pressureMode: presentation.pressureMode,
            mainIssueIds: presentation.mainIssueIds.slice(),
            issueTitles: presentation.issueTitles.slice(),
            issueExplanations: presentation.issueExplanations.slice(),
            guidanceText: guidanceText(s, presentation)
        };
    }

    global.Phase6SurvivorEngine = {
        ISSUE: ISSUE,
        TITLES: TITLES,
        EXPLANATIONS: EXPLANATIONS,
        runSurvivorPicture: runSurvivorPicture,
        analyzeSignals: analyzeSignals,
        selectIssues: selectIssues
    };
}(typeof window !== 'undefined' ? window : globalThis));
