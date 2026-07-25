/**
 * Phase 4 adjustment coaching — maps stress results to ≤3 options.
 * Does not rewrite Phases 1–3.
 */
(function (global) {
    'use strict';

    var OPTIONS = {
        reduceSpending: {
            id: 'reduce-spending',
            label: 'Reduce planned spending'
        },
        delayWithdrawals: {
            id: 'delay-withdrawals',
            label: 'Delay retirement or withdrawals'
        },
        revisitSs: {
            id: 'revisit-social-security',
            label: 'Revisit the Social Security assumption'
        },
        increaseSavings: {
            id: 'increase-savings',
            label: 'Increase retirement savings'
        },
        tempCutAfterDecline: {
            id: 'temp-cut-after-decline',
            label: 'Temporarily reduce spending after a market decline'
        },
        keepAsIs: {
            id: 'keep-as-is',
            label: 'Keep the Phase 3 plan as-is and revisit it later'
        }
    };

    function impactRank(code) {
        if (code === 'severe') return 3;
        if (code === 'noticeable') return 2;
        return 1;
    }

    function recommendAdjustments(run, phase3Snapshot) {
        var overall = run.overall.code;
        if (overall === 'holds') {
            return [OPTIONS.keepAsIs];
        }

        var scenarios = run.scenarios;
        var orderedIds = ['earlyDecline', 'weakerGrowth', 'longerRetirement'].sort(function (a, b) {
            return impactRank(scenarios[b].impact.code) - impactRank(scenarios[a].impact.code);
        });

        var picked = [];
        function add(option) {
            if (!option) return;
            if (picked.some(function (item) { return item.id === option.id; })) return;
            if (picked.length >= 3) return;
            picked.push(option);
        }

        orderedIds.forEach(function (id) {
            if (impactRank(scenarios[id].impact.code) < 2) return;
            if (id === 'earlyDecline') {
                add(OPTIONS.tempCutAfterDecline);
                add(OPTIONS.reduceSpending);
                add(OPTIONS.increaseSavings);
            } else if (id === 'weakerGrowth') {
                add(OPTIONS.reduceSpending);
                add(OPTIONS.increaseSavings);
                add(OPTIONS.delayWithdrawals);
            } else if (id === 'longerRetirement') {
                add(OPTIONS.delayWithdrawals);
                add(OPTIONS.reduceSpending);
                add(OPTIONS.increaseSavings);
            }
        });

        if (phase3Snapshot && phase3Snapshot.temporarySocialSecurityEstimateUsed) {
            add(OPTIONS.revisitSs);
        }

        add(OPTIONS.keepAsIs);
        return picked.slice(0, 3);
    }

    global.Phase4Adjustments = {
        OPTIONS: OPTIONS,
        recommendAdjustments: recommendAdjustments
    };
}(typeof window !== 'undefined' ? window : globalThis));
