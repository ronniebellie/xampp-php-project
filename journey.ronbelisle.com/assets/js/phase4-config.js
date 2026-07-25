/**
 * Phase 4 Stress Test — Hybrid Round 2 configuration.
 *
 * These are provisional educational stress-test assumptions.
 * They are configurable.
 * They are not predictions or guarantees.
 * They should not be changed without another documented calibration review.
 *
 * Baseline: Hybrid Round 2 (PHASE_4_CALIBRATION.md).
 */
(function (global) {
    'use strict';

    var PHASE4_CONFIG = {
        configId: 'hybrid_r2',
        configVersion: 'hybrid_r2-v1',
        classificationVersion: 'round2',
        name: 'Hybrid Round 2',

        // Horizon (provisional educational assumptions — not predictions)
        baseHorizonYears: 28,
        longerExtensionYears: 5,
        longerHorizonYears: 33,

        // Growth and decline (today’s dollars; provisional)
        baseGrowthRate: 0.0275,
        weakerGrowthRate: 0.01,
        earlyDeclinePct: -15,
        postDeclineGrowth: 'base',
        earlyDeclineBeforeWithdrawal: true,

        // Classification thresholds (provisional)
        endingBalanceRatioFloor: 0.65,
        longevityRatioFloor: 0.88,
        earlierDepletionYears: 6,
        lateDepletionFraction: 0.2,
        cushionYearsForLittle: 8,
        cushionPctOfStartForLittle: 0.3,

        // Aggregation (provisional)
        workableNearFourCapNeeds: true,
        workableNearFourMinRate: 3.5,
        workableNearFourMaxRate: 4.5,
        difficultPlusAnySevereNeedsAdjustment: true
    };

    global.Phase4Config = PHASE4_CONFIG;
}(typeof window !== 'undefined' ? window : globalThis));
