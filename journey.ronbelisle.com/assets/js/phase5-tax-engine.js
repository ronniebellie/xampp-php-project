/**
 * Phase 5 Tax Strategy — product-level result engine (Approach B lean).
 *
 * Educational heuristics only. Not a tax calculator.
 * Does not use IRS thresholds, federal tax math, SS taxable %, RMD $, or conversion $.
 */
(function (global) {
    'use strict';

    var ISSUE = {
        TAX_DEFERRED: 'tax_deferred_pressure',
        GROSS_VS_SPENDABLE: 'gross_vs_spendable',
        RMD: 'rmd_attention',
        ROTH: 'roth_review',
        SS: 'ss_income_interaction',
        MIX_UNCLEAR: 'account_mix_unclear',
        NONE: 'none_dominant'
    };

    var STATEMENTS = {};
    STATEMENTS[ISSUE.TAX_DEFERRED] =
        'The main tax-planning issue in this snapshot is reliance on tax-deferred withdrawals.';
    STATEMENTS[ISSUE.GROSS_VS_SPENDABLE] =
        'The main tax-planning issue in this snapshot is that taxes may require larger gross withdrawals than your spending need alone suggests.';
    STATEMENTS[ISSUE.RMD] =
        'The main tax-planning issue in this snapshot is required minimum distributions.';
    STATEMENTS[ISSUE.ROTH] =
        'The main tax-planning issue in this snapshot is whether Roth planning deserves a closer look.';
    STATEMENTS[ISSUE.SS] =
        'The main tax-planning issue in this snapshot is how Social Security and other income may interact with withdrawals.';
    STATEMENTS[ISSUE.MIX_UNCLEAR] =
        'The main tax-planning issue in this snapshot is confirming how your retirement savings are divided among account types.';
    STATEMENTS[ISSUE.NONE] =
        'No single tax issue stands out strongly from these answers. An annual review may be enough for now.';

    var MEANINGS = {};
    MEANINGS[ISSUE.TAX_DEFERRED] =
        'Much of what you take from retirement savings may count as taxable income. The Phase 3 gross picture may overstate spendable cash until taxes are considered.';
    MEANINGS[ISSUE.GROSS_VS_SPENDABLE] =
        'If withdrawals are taxable, you may need to take out more than the spending gap alone to support the same spending power. This is guidance, not a tax estimate.';
    MEANINGS[ISSUE.RMD] =
        'Required withdrawals can raise taxable income even when your spending gap is modest. Your answer suggests this timing deserves attention.';
    MEANINGS[ISSUE.ROTH] =
        'When tax-deferred savings do much of the work as required withdrawals approach, reviewing Roth options later may be useful. This is not a recommendation to convert.';
    MEANINGS[ISSUE.SS] =
        'Social Security and withdrawals can interact in your overall income picture. A closer look at those pieces together may help before you rely on the withdrawal plan.';
    MEANINGS[ISSUE.MIX_UNCLEAR] =
        'Traditional, Roth, and taxable accounts can affect taxable income differently. Confirming your account mix would make future tax-planning decisions more useful.';
    MEANINGS[ISSUE.NONE] =
        'With these answers, nothing demands an urgent tax redesign. Revisit this review as balances, Social Security, or tax rules change.';

    var RMD_NOTES = {
        already: 'Required minimum distributions may already affect your taxable income.',
        within_about_5_years: 'Required minimum distributions may deserve attention within about the next five years.',
        later: 'Required minimum distributions look less immediate based on your answer. They can still matter later.',
        not_sure: 'If you are unsure about required minimum distributions, confirming the timing with a trusted source is a useful next step.'
    };

    var TAX_DRAG = {
        limited: {
            code: 'limited',
            text: 'Taxes may have a limited effect on the amount you need to withdraw.'
        },
        somewhat: {
            code: 'somewhat',
            text: 'Taxes may require somewhat larger gross withdrawals than your spending gap alone suggests.'
        },
        closer_review: {
            code: 'closer_review',
            text: 'Because much of the plan may rely on taxable withdrawals, the difference between gross withdrawals and spendable income deserves closer review.'
        }
    };

    var ROTH_TEXT =
        'Roth planning may be worth reviewing before required withdrawals become more important.';

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

    function analyzeSignals(plan, savingsMix, rmdTiming) {
        var W = annualNeedFromPlan(plan);
        var B = num(plan.retirementSavingsBalance, 0);
        var monthlySs = num(plan.monthlySocialSecurityAssumption, 0);

        // 3% is a product-level educational signal, not a tax rule or withdrawal recommendation.
        var hasWithdrawalNeed = W > 0;
        var substantialNeed = hasWithdrawalNeed && (B === 0 || W / B >= 0.03);

        var traditionalLean = savingsMix === 'mostly_tax_deferred';
        var rothLean = savingsMix === 'mostly_roth';
        var mixedLean = savingsMix === 'mixed';
        var mixUnknown = savingsMix === 'not_sure';
        var rmdNear = rmdTiming === 'already' || rmdTiming === 'within_about_5_years';
        var temporarySs = plan.temporarySocialSecurityEstimateUsed === true;
        var ssPresent = monthlySs > 0;

        return {
            W: W,
            B: B,
            hasWithdrawalNeed: hasWithdrawalNeed,
            substantialNeed: substantialNeed,
            traditionalLean: traditionalLean,
            rothLean: rothLean,
            mixedLean: mixedLean,
            mixUnknown: mixUnknown,
            rmdNear: rmdNear,
            temporarySs: temporarySs,
            ssPresent: ssPresent
        };
    }

    function taxDragGuidance(s) {
        // Limited: no savings withdrawals, or mostly Roth.
        if (!s.hasWithdrawalNeed || s.rothLean) {
            return TAX_DRAG.limited;
        }
        // Closer review: mostly tax-deferred with withdrawals needed.
        // Do not use closer_review for unknown mix alone, or for mixed (use somewhat).
        if (s.traditionalLean && s.hasWithdrawalNeed) {
            return TAX_DRAG.closer_review;
        }
        // Somewhat: mixed or unknown with a withdrawal need.
        if (s.mixedLean || s.mixUnknown) {
            return TAX_DRAG.somewhat;
        }
        return TAX_DRAG.somewhat;
    }

    function rothReviewFlag(s) {
        if (s.traditionalLean && s.rmdNear) return true;
        if (s.traditionalLean && s.substantialNeed) return true;
        if (s.mixedLean && s.hasWithdrawalNeed && s.rmdNear) return true;
        return false;
    }

    function issueLabelShort(id) {
        if (id === ISSUE.TAX_DEFERRED) return 'reliance on tax-deferred withdrawals';
        if (id === ISSUE.GROSS_VS_SPENDABLE) {
            return 'that taxes may require larger gross withdrawals than your spending need alone suggests';
        }
        if (id === ISSUE.RMD) return 'required minimum distributions';
        if (id === ISSUE.ROTH) return 'whether Roth planning deserves a closer look';
        if (id === ISSUE.SS) return 'how Social Security and other income may interact with withdrawals';
        if (id === ISSUE.MIX_UNCLEAR) {
            return 'confirming how your retirement savings are divided among account types';
        }
        return 'an annual review';
    }

    function buildPresentation(mode, ids) {
        if (mode === 'none' || !ids.length || ids[0] === ISSUE.NONE) {
            return {
                pressureMode: 'none',
                mainIssueIds: [ISSUE.NONE],
                mainIssueStatement: STATEMENTS[ISSUE.NONE],
                whatThisMeans: MEANINGS[ISSUE.NONE]
            };
        }
        if (mode === 'tied' && ids.length >= 2) {
            return {
                pressureMode: 'tied',
                mainIssueIds: ids.slice(0, 2),
                mainIssueStatement: 'Two issues deserve similar attention: ' +
                    issueLabelShort(ids[0]) + ' and ' + issueLabelShort(ids[1]) + '.',
                whatThisMeans: MEANINGS[ids[0]] + ' ' + MEANINGS[ids[1]]
            };
        }
        return {
            pressureMode: 'single',
            mainIssueIds: [ids[0]],
            mainIssueStatement: STATEMENTS[ids[0]],
            whatThisMeans: MEANINGS[ids[0]]
        };
    }

    /**
     * Deterministic main-issue selection with genuine two-way ties.
     * Phase 4 context must not alter ranking (caller display-only).
     */
    function selectIssues(s) {
        var deferredPressure = s.traditionalLean && s.hasWithdrawalNeed;
        var grossPressure = s.traditionalLean && s.substantialNeed;
        var mixUnclear = s.mixUnknown;
        var rmd = s.rmdNear;
        var roth = rothReviewFlag(s);
        var ssSoft = s.ssPresent && s.hasWithdrawalNeed && s.temporarySs &&
            !deferredPressure && !grossPressure && !mixUnclear;

        // Unknown mix + RMD near → tie account_mix_unclear with rmd_attention
        if (mixUnclear && rmd) {
            return { mode: 'tied', ids: [ISSUE.MIX_UNCLEAR, ISSUE.RMD] };
        }

        // Traditional-heavy + withdrawals + RMD near → tie deferred/gross with RMD
        if (deferredPressure && rmd) {
            var deferredId = grossPressure ? ISSUE.GROSS_VS_SPENDABLE : ISSUE.TAX_DEFERRED;
            return { mode: 'tied', ids: [deferredId, ISSUE.RMD] };
        }

        // Mixed + RMD near + withdrawal need → tie RMD with Roth review when both relevant
        if (s.mixedLean && rmd && s.hasWithdrawalNeed && roth) {
            return { mode: 'tied', ids: [ISSUE.RMD, ISSUE.ROTH] };
        }

        // Strong single paths
        if (grossPressure) {
            return { mode: 'single', ids: [ISSUE.GROSS_VS_SPENDABLE] };
        }
        if (deferredPressure) {
            return { mode: 'single', ids: [ISSUE.TAX_DEFERRED] };
        }
        if (mixUnclear) {
            // Unknown mix and no stronger known issue (RMD already handled as tie above)
            return { mode: 'single', ids: [ISSUE.MIX_UNCLEAR] };
        }
        if (rmd) {
            return { mode: 'single', ids: [ISSUE.RMD] };
        }
        if (roth) {
            return { mode: 'single', ids: [ISSUE.ROTH] };
        }
        if (ssSoft) {
            return { mode: 'single', ids: [ISSUE.SS] };
        }
        return { mode: 'none', ids: [ISSUE.NONE] };
    }

    function runTaxPicture(planInput, savingsMix, rmdTiming) {
        var plan = planInput || {};
        var mix = savingsMix || '';
        var rmd = rmdTiming || '';
        var s = analyzeSignals(plan, mix, rmd);
        var selected = selectIssues(s);
        var presentation = buildPresentation(selected.mode, selected.ids);
        var showRoth = rothReviewFlag(s);

        return {
            signals: {
                hasWithdrawalNeed: s.hasWithdrawalNeed,
                substantialNeed: s.substantialNeed,
                traditionalLean: s.traditionalLean,
                rothLean: s.rothLean,
                mixedLean: s.mixedLean,
                mixUnknown: s.mixUnknown,
                rmdNear: s.rmdNear
            },
            pressureMode: presentation.pressureMode,
            mainIssueIds: presentation.mainIssueIds.slice(),
            mainIssueStatement: presentation.mainIssueStatement,
            whatThisMeans: presentation.whatThisMeans,
            taxDragGuidance: taxDragGuidance(s),
            rmdNote: {
                code: rmd,
                text: RMD_NOTES[rmd] || RMD_NOTES.not_sure
            },
            rothReviewFlag: showRoth,
            rothReviewText: showRoth ? ROTH_TEXT : null,
            traditionalHeavy: s.traditionalLean
        };
    }

    global.Phase5TaxEngine = {
        ISSUE: ISSUE,
        STATEMENTS: STATEMENTS,
        MEANINGS: MEANINGS,
        runTaxPicture: runTaxPicture,
        analyzeSignals: analyzeSignals
    };
}(typeof window !== 'undefined' ? window : globalThis));
