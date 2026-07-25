/**
 * Representative calibration fixtures A–F (illustrative only).
 * Plan G is intentionally empty / user-supplied — never hard-code personal figures here.
 */
(function (global) {
    'use strict';

    var FIXTURES = [
        {
            id: 'A',
            name: 'Plan A — Conservative retiree',
            persona: 'conservative',
            notes: 'Modest lifestyle, strong dependable income, large savings, low WR. Should usually hold up.',
            monthlySpending: 5000,
            monthlySocialSecurity: 3500,
            monthlyOtherIncome: 700,
            savingsBalance: 900000,
            expectedOverall: 'holds',
            expectedDominant: 'none',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'B',
            name: 'Plan B — Average / typical Journey retiree',
            persona: 'average',
            notes: 'Near ~4% withdrawal rate. Expect holds or sensitive with useful nuance.',
            monthlySpending: 7500,
            monthlySocialSecurity: 3200,
            monthlyOtherIncome: 800,
            savingsBalance: 1050000,
            expectedOverall: 'holds_or_sensitive',
            expectedDominant: 'any',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'C',
            name: 'Plan C — Aggressive saver',
            persona: 'aggressive_saver',
            notes: 'High savings vs gap (~2.5% WR). Should feel reassuring, not “guaranteed.”',
            monthlySpending: 8000,
            monthlySocialSecurity: 2800,
            monthlyOtherIncome: 700,
            savingsBalance: 2200000,
            expectedOverall: 'holds',
            expectedDominant: 'none',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'D',
            name: 'Plan D — High withdrawal rate',
            persona: 'high_wr',
            notes: 'Large savings gap vs balance (~8%+ WR). Should look vulnerable.',
            monthlySpending: 10000,
            monthlySocialSecurity: 2500,
            monthlyOtherIncome: 500,
            savingsBalance: 1000000,
            expectedOverall: 'sensitive_or_needs',
            expectedDominant: 'earlyDecline',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'E',
            name: 'Plan E — Low withdrawal rate',
            persona: 'low_wr',
            notes: 'Dependable income covers most spending. Barely moves under stress.',
            monthlySpending: 6000,
            monthlySocialSecurity: 4800,
            monthlyOtherIncome: 1000,
            savingsBalance: 750000,
            expectedOverall: 'holds',
            expectedDominant: 'none',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'F',
            name: 'Plan F — Very long retirement emphasis',
            persona: 'long_horizon',
            notes: 'Moderate ~3.4% WR where longevity extension should matter.',
            monthlySpending: 6500,
            monthlySocialSecurity: 2000,
            monthlyOtherIncome: 500,
            savingsBalance: 1400000,
            expectedOverall: 'holds_or_sensitive',
            expectedDominant: 'longerRetirement',
            enabled: true,
            source: 'fixture'
        },
        {
            id: 'G',
            name: 'Plan G — Real Phase 3 case (configurable)',
            persona: 'real_world',
            notes: 'Enter actual Phase 3 values for this session. Not stored in source. No automatic localStorage read.',
            monthlySpending: '',
            monthlySocialSecurity: '',
            monthlyOtherIncome: '',
            savingsBalance: '',
            expectedOverall: 'holds_or_sensitive',
            expectedDominant: 'earlyDecline',
            enabled: false,
            isConfigurable: true,
            source: 'user_supplied'
        }
    ];

    /**
     * Shared Round 2 classification defaults (provisional).
     * Applied by the engine to every pack unless overridden.
     */
    var ROUND2_CLASSIFICATION_DEFAULTS = {
        classificationVersion: 'round2',
        earlyDeclineBeforeWithdrawal: true,
        postDeclineGrowth: 'base',
        lateDepletionFraction: 0.2,
        cushionYearsForLittle: 8,
        cushionPctOfStartForLittle: 0.3,
        workableNearFourCapNeeds: true,
        workableNearFourMinRate: 3.5,
        workableNearFourMaxRate: 4.5,
        difficultPlusAnySevereNeedsAdjustment: true
    };

    /**
     * Candidate assumption packs — provisional only.
     * Mild / Central / Strict kept for comparison; Hybrid Round 2 is the new candidate.
     */
    var PACKS = {
        mild: Object.assign({}, ROUND2_CLASSIFICATION_DEFAULTS, {
            id: 'mild',
            name: 'Mild pack',
            description: 'Shorter horizons, softer decline, looser cushions — Round 1 soft reference (now with Round 2 classification).',
            baseHorizonYears: 25,
            longerExtensionYears: 3,
            longerHorizonYears: 28,
            baseGrowthRate: 0.03,
            weakerGrowthRate: 0.015,
            earlyDeclinePct: -12,
            endingBalanceRatioFloor: 0.55,
            longevityRatioFloor: 0.55,
            earlierDepletionYears: 7
        }),
        central: Object.assign({}, ROUND2_CLASSIFICATION_DEFAULTS, {
            id: 'central',
            name: 'Central pack (§18 hypotheses)',
            description: 'Round 1 §18 scenario hypotheses, re-run with Round 2 classification rules.',
            baseHorizonYears: 30,
            longerExtensionYears: 5,
            longerHorizonYears: 35,
            baseGrowthRate: 0.025,
            weakerGrowthRate: 0.005,
            earlyDeclinePct: -20,
            endingBalanceRatioFloor: 0.7,
            longevityRatioFloor: 0.7,
            earlierDepletionYears: 5
        }),
        strict: Object.assign({}, ROUND2_CLASSIFICATION_DEFAULTS, {
            id: 'strict',
            name: 'Strict pack',
            description: 'Harsh scenario assumptions, re-run with Round 2 classification rules.',
            baseHorizonYears: 35,
            longerExtensionYears: 7,
            longerHorizonYears: 42,
            baseGrowthRate: 0.02,
            weakerGrowthRate: 0,
            earlyDeclinePct: -30,
            endingBalanceRatioFloor: 0.8,
            longevityRatioFloor: 0.8,
            earlierDepletionYears: 3,
            lateDepletionFraction: 0.2
        }),
        hybrid_r2: Object.assign({}, ROUND2_CLASSIFICATION_DEFAULTS, {
            id: 'hybrid_r2',
            name: 'Hybrid Round 2',
            description: 'Between Mild and Central on scenario dials; Round 2 timing-sensitive classification + absolute-cushion guard.',
            baseHorizonYears: 28,
            longerExtensionYears: 5,
            longerHorizonYears: 33,
            baseGrowthRate: 0.0275,
            weakerGrowthRate: 0.01,
            earlyDeclinePct: -15,
            endingBalanceRatioFloor: 0.65,
            // Slightly stricter than the general ratio floor so +5y thinning can register
            // for moderate-WR plans (Plan F) without relying on depletion.
            longevityRatioFloor: 0.88,
            earlierDepletionYears: 6,
            lateDepletionFraction: 0.2,
            cushionYearsForLittle: 8,
            cushionPctOfStartForLittle: 0.3
        })
    };

    global.Phase4CalibrationData = {
        FIXTURES: FIXTURES,
        PACKS: PACKS
    };
}(typeof window !== 'undefined' ? window : globalThis));
