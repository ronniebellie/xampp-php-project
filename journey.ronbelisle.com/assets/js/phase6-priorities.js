/**
 * Phase 6 Survivor Planning — priority choices.
 * Coaching map only. Always includes keep-review-annually.
 */
(function (global) {
    'use strict';

    var OPTIONS = {
        continuingIncome: {
            id: 'review-continuing-income',
            label: 'Review what income would continue for the survivor'
        },
        onePersonSpending: {
            id: 'revisit-one-person-spending',
            label: 'Revisit the spending goal as a one-person household'
        },
        survivorSs: {
            id: 'review-survivor-social-security',
            label: 'Review how Social Security may change for the survivor'
        },
        accountRecipients: {
            id: 'review-account-recipients',
            label: 'Review who would receive retirement accounts and other financial assets'
        },
        consult: {
            id: 'consult-professional',
            label: 'Discuss the plan with a financial or estate-planning professional'
        },
        keep: {
            id: 'keep-review-annually',
            label: 'Keep the current approach and review it annually'
        }
    };

    function uniquePush(list, option) {
        if (!option) return;
        if (list.some(function (item) { return item.id === option.id; })) return;
        list.push(option);
    }

    function ensureKeep(list) {
        uniquePush(list, OPTIONS.keep);
        if (list.length <= 3) return list.slice(0, 3);
        var withoutKeep = list.filter(function (item) { return item.id !== OPTIONS.keep.id; });
        return withoutKeep.slice(0, 2).concat([OPTIONS.keep]);
    }

    function recommendPriorities(result) {
        var ids = (result && result.mainIssueIds) ? result.mainIssueIds : [];
        var mode = result && result.pressureMode;
        var picked = [];

        function has(id) {
            return ids.indexOf(id) !== -1;
        }

        if (mode === 'tied') {
            if (has('beneficiary_review') && has('survivor_income_review')) {
                uniquePush(picked, OPTIONS.accountRecipients);
                uniquePush(picked, OPTIONS.continuingIncome);
                return ensureKeep(picked);
            }
            if (has('social_security_change') && has('survivor_spending_look')) {
                uniquePush(picked, OPTIONS.survivorSs);
                uniquePush(picked, OPTIONS.onePersonSpending);
                return ensureKeep(picked);
            }
            if (has('beneficiary_review') && has('social_security_change')) {
                uniquePush(picked, OPTIONS.accountRecipients);
                uniquePush(picked, OPTIONS.survivorSs);
                return ensureKeep(picked);
            }
        }

        var primary = ids[0] || 'none_dominant';

        if (primary === 'beneficiary_review') {
            uniquePush(picked, OPTIONS.accountRecipients);
            uniquePush(picked, OPTIONS.consult);
            return ensureKeep(picked);
        }
        if (primary === 'survivor_income_review') {
            uniquePush(picked, OPTIONS.continuingIncome);
            uniquePush(picked, OPTIONS.onePersonSpending);
            return ensureKeep(picked);
        }
        if (primary === 'social_security_change') {
            uniquePush(picked, OPTIONS.survivorSs);
            uniquePush(picked, OPTIONS.continuingIncome);
            return ensureKeep(picked);
        }
        if (primary === 'survivor_spending_look') {
            uniquePush(picked, OPTIONS.onePersonSpending);
            uniquePush(picked, OPTIONS.continuingIncome);
            return ensureKeep(picked);
        }

        uniquePush(picked, OPTIONS.keep);
        uniquePush(picked, OPTIONS.consult);
        return ensureKeep(picked);
    }

    global.Phase6Priorities = {
        OPTIONS: OPTIONS,
        recommendPriorities: recommendPriorities
    };
}(typeof window !== 'undefined' ? window : globalThis));
