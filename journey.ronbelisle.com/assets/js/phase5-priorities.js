/**
 * Phase 5 priority / strategy choices.
 * Coaching map only — never “best,” never auto-changes Phases 1–4.
 * Always includes a calm keep-and-review option among the ≤3 shown.
 */
(function (global) {
    'use strict';

    var OPTIONS = {
        confirmMix: {
            id: 'confirm-account-mix',
            label: 'Confirm how your retirement savings are divided among account types'
        },
        setAside: {
            id: 'set-aside-for-taxes',
            label: 'Set aside part of withdrawals for taxes'
        },
        reviewRothWithdrawals: {
            id: 'review-roth-withdrawals',
            label: 'Review whether some future withdrawals should come from Roth savings'
        },
        exploreConversions: {
            id: 'explore-roth-conversions',
            label: 'Explore Roth conversions before RMDs begin'
        },
        reviewRmd: {
            id: 'review-rmd-exposure',
            label: 'Review future RMD exposure'
        },
        reviewSs: {
            id: 'review-ss-withdrawal-interaction',
            label: 'Review how Social Security and withdrawals interact'
        },
        consult: {
            id: 'consult-professional',
            label: 'Consult a tax professional before changing the plan'
        },
        keep: {
            id: 'keep-and-review-annually',
            label: 'Keep the current approach and review annually'
        }
    };

    function uniquePush(list, option) {
        if (!option) return;
        if (list.some(function (item) { return item.id === option.id; })) return;
        list.push(option);
    }

    function ensureKeep(list) {
        uniquePush(list, OPTIONS.keep);
        if (list.length > 3) {
            // Keep calm exit: drop last non-keep if needed, preserve keep.
            var keepItem = OPTIONS.keep;
            var withoutKeep = list.filter(function (item) { return item.id !== keepItem.id; });
            return withoutKeep.slice(0, 2).concat([keepItem]);
        }
        return list.slice(0, 3);
    }

    function recommendPriorities(result) {
        var ids = (result && result.mainIssueIds) ? result.mainIssueIds : [];
        var mode = result && result.pressureMode;
        var picked = [];

        function has(id) {
            return ids.indexOf(id) !== -1;
        }

        if (mode === 'tied') {
            if (has('account_mix_unclear')) uniquePush(picked, OPTIONS.confirmMix);
            if (has('gross_vs_spendable') || has('tax_deferred_pressure')) {
                uniquePush(picked, OPTIONS.setAside);
                uniquePush(picked, OPTIONS.reviewRothWithdrawals);
            }
            if (has('rmd_attention')) {
                uniquePush(picked, OPTIONS.reviewRmd);
                if (result.rothReviewFlag || has('roth_review') || has('tax_deferred_pressure')) {
                    uniquePush(picked, OPTIONS.exploreConversions);
                }
            }
            if (has('roth_review')) {
                uniquePush(picked, OPTIONS.exploreConversions);
                uniquePush(picked, OPTIONS.reviewRothWithdrawals);
            }
            return ensureKeep(picked);
        }

        var primary = ids[0] || 'none_dominant';

        if (primary === 'account_mix_unclear') {
            uniquePush(picked, OPTIONS.confirmMix);
            if (result && result.rmdNote &&
                (result.rmdNote.code === 'already' || result.rmdNote.code === 'within_about_5_years')) {
                uniquePush(picked, OPTIONS.reviewRmd);
            }
            uniquePush(picked, OPTIONS.consult);
            return ensureKeep(picked);
        }

        if (primary === 'gross_vs_spendable') {
            uniquePush(picked, OPTIONS.setAside);
            uniquePush(picked, OPTIONS.reviewRothWithdrawals);
            return ensureKeep(picked);
        }

        if (primary === 'tax_deferred_pressure') {
            uniquePush(picked, OPTIONS.setAside);
            uniquePush(picked, OPTIONS.reviewRothWithdrawals);
            return ensureKeep(picked);
        }

        if (primary === 'rmd_attention') {
            uniquePush(picked, OPTIONS.reviewRmd);
            if (result && result.rothReviewFlag) {
                uniquePush(picked, OPTIONS.exploreConversions);
            } else {
                uniquePush(picked, OPTIONS.consult);
            }
            return ensureKeep(picked);
        }

        if (primary === 'roth_review') {
            uniquePush(picked, OPTIONS.exploreConversions);
            uniquePush(picked, OPTIONS.reviewRothWithdrawals);
            return ensureKeep(picked);
        }

        if (primary === 'ss_income_interaction') {
            uniquePush(picked, OPTIONS.reviewSs);
            uniquePush(picked, OPTIONS.setAside);
            return ensureKeep(picked);
        }

        // none_dominant
        uniquePush(picked, OPTIONS.keep);
        uniquePush(picked, OPTIONS.consult);
        return ensureKeep(picked);
    }

    global.Phase5Priorities = {
        OPTIONS: OPTIONS,
        recommendPriorities: recommendPriorities
    };
}(typeof window !== 'undefined' ? window : globalThis));
