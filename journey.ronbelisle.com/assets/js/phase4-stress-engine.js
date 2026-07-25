/**
 * Phase 4 Stress Test engine (Hybrid Round 2 baseline).
 *
 * Extracted from the validated calibration engine. Assumptions live in
 * Phase4Config and remain provisional educational values — not predictions.
 * Do not retune without a documented calibration review.
 */
(function (global) {
    'use strict';

    var IMPACT = {
        LITTLE: 'little',
        NOTICEABLE: 'noticeable',
        SEVERE: 'severe'
    };

    var IMPACT_LABEL = {
        little: 'Little change',
        noticeable: 'Noticeable strain',
        severe: 'Severe strain'
    };

    var OVERALL = {
        HOLDS: 'holds',
        SENSITIVE: 'sensitive',
        NEEDS: 'needs'
    };

    var OVERALL_LABEL = {
        holds: 'Holds up reasonably well in these tests',
        sensitive: 'Sensitive to one or more risks',
        needs: 'Needs meaningful adjustment before relying on it'
    };

    var SCENARIO_IDS = ['weakerGrowth', 'earlyDecline', 'longerRetirement'];

    var SCENARIO_NAMES = {
        weakerGrowth: 'Weaker long-term growth',
        earlyDecline: 'Early market decline',
        longerRetirement: 'Longer retirement'
    };

    function clampNumber(value, fallback) {
        var n = Number(value);
        return Number.isFinite(n) ? n : fallback;
    }

    function assessPhase3(annualNeed, balance) {
        var need = Math.max(0, clampNumber(annualNeed, 0));
        var bal = clampNumber(balance, NaN);

        if (need === 0) {
            return {
                code: 'workable',
                label: 'Looks workable on these assumptions',
                ratePct: 0
            };
        }

        if (!(bal > 0)) {
            return {
                code: 'difficult',
                label: 'Looks difficult on these assumptions',
                ratePct: null
            };
        }

        var ratePct = (need / bal) * 100;
        if (ratePct <= 4) {
            return {
                code: 'workable',
                label: 'Looks workable on these assumptions',
                ratePct: ratePct
            };
        }
        if (ratePct <= 5) {
            return {
                code: 'close',
                label: 'Looks close and may need adjustment',
                ratePct: ratePct
            };
        }
        return {
            code: 'difficult',
            label: 'Looks difficult on these assumptions',
            ratePct: ratePct
        };
    }

    /**
     * Project balance year by year.
     * Order: start balance → withdraw min(W, balance) → apply growth to remainder.
     * Optional startingDeclinePct applied to starting balance before year 1.
     */
    function projectPath(balance, annualNeed, years, growthRate, startingDeclinePct) {
        var b = Math.max(0, clampNumber(balance, 0));
        var w = Math.max(0, clampNumber(annualNeed, 0));
        var horizon = Math.max(0, Math.floor(clampNumber(years, 0)));
        var g = clampNumber(growthRate, 0);
        var decline = clampNumber(startingDeclinePct, 0);

        if (decline !== 0) {
            b = Math.max(0, b * (1 + decline / 100));
        }

        var cannotFundFirstYear = w > 0 && b + 1e-9 < w;
        var depletedYear = null;
        var yearsFunded = 0;
        var history = [];

        for (var year = 1; year <= horizon; year += 1) {
            var startBal = b;
            if (b <= 1e-9) {
                if (depletedYear === null) depletedYear = year;
                history.push({
                    year: year,
                    start: 0,
                    withdrawn: 0,
                    endBeforeGrowth: 0,
                    end: 0
                });
                b = 0;
                continue;
            }

            var withdrawn = Math.min(w, b);
            var afterWithdraw = b - withdrawn;
            if (withdrawn + 1e-9 >= w) {
                yearsFunded += 1;
            } else if (depletedYear === null) {
                depletedYear = year;
            }

            var end = afterWithdraw * (1 + g);
            if (end < 0) end = 0;

            history.push({
                year: year,
                start: startBal,
                withdrawn: withdrawn,
                endBeforeGrowth: afterWithdraw,
                end: end
            });

            b = end;
            if (b <= 1e-9 && depletedYear === null) {
                depletedYear = year;
            }
        }

        var lastedFullHorizon = depletedYear === null && (w === 0 || yearsFunded >= horizon);

        return {
            startingBalanceAfterShock: decline !== 0
                ? Math.max(0, clampNumber(balance, 0) * (1 + decline / 100))
                : Math.max(0, clampNumber(balance, 0)),
            endingBalance: b,
            depletedYear: depletedYear,
            yearsFunded: yearsFunded,
            lastedFullHorizon: lastedFullHorizon,
            cannotFundFirstYear: cannotFundFirstYear,
            history: history
        };
    }

    function endingRatio(scenarioEnding, baseEnding) {
        if (!(baseEnding > 0)) return null;
        return scenarioEnding / baseEnding;
    }

    function yearsOfWithdrawals(endingBalance, annualNeed) {
        var end = clampNumber(endingBalance, 0);
        var w = clampNumber(annualNeed, 0);
        if (!(w > 0)) return end > 0 ? Number.POSITIVE_INFINITY : 0;
        return end / w;
    }

    function lateDepletionStartYear(scenarioHorizon, lateFraction) {
        var horizon = Math.max(1, Math.floor(clampNumber(scenarioHorizon, 1)));
        var frac = clampNumber(lateFraction, 0.2);
        if (frac < 0) frac = 0;
        if (frac > 0.5) frac = 0.5;
        return Math.floor(horizon * (1 - frac)) + 1;
    }

    /**
     * Round 2 timing-sensitive classification (provisional).
     * Severe reserved for clearer distress; late depletion → Noticeable;
     * absolute-cushion guard can keep strong plans at Little despite low ratios.
     */
    function classifyImpact(basePath, scenarioPath, scenarioHorizon, pack, startingBalance, annualNeed) {
        var ratioFloor = clampNumber(pack.endingBalanceRatioFloor, 0.7);
        var earlierYears = clampNumber(pack.earlierDepletionYears, 5);
        var lateFraction = clampNumber(pack.lateDepletionFraction, 0.2);
        var cushionYearsLittle = clampNumber(pack.cushionYearsForLittle, 8);
        var cushionPctStartLittle = clampNumber(pack.cushionPctOfStartForLittle, 0.3);
        var lateStart = lateDepletionStartYear(scenarioHorizon, lateFraction);
        var es = scenarioPath.endingBalance;
        var eb = basePath.endingBalance;
        var ratio = endingRatio(es, eb);
        var yearsLeft = yearsOfWithdrawals(es, annualNeed);
        var pctOfStart = startingBalance > 0 ? es / startingBalance : null;

        if (scenarioPath.cannotFundFirstYear) {
            return {
                code: IMPACT.SEVERE,
                reason: 'Cannot fund the first year’s withdrawal after the applied shock (or with starting balance).',
                severityKind: 'year1_shortfall',
                yearsOfWithdrawals: yearsLeft,
                endingRatio: ratio,
                pctOfStart: pctOfStart
            };
        }

        var baseDepleted = basePath.depletedYear !== null;
        var scenDepleted = scenarioPath.depletedYear !== null;

        if (scenDepleted) {
            var depYear = scenarioPath.depletedYear;

            if (!baseDepleted) {
                if (depYear >= lateStart) {
                    return {
                        code: IMPACT.NOTICEABLE,
                        reason: 'Scenario depletes only near the end of the horizon (year ' +
                            depYear + ' of ' + scenarioHorizon + ', final ' +
                            Math.round(lateFraction * 100) + '% window starting year ' +
                            lateStart + ') while the base-growth reference lasts.',
                        severityKind: 'late_depletion',
                        yearsOfWithdrawals: 0,
                        endingRatio: ratio,
                        pctOfStart: pctOfStart
                    };
                }
                return {
                    code: IMPACT.SEVERE,
                    reason: 'Savings deplete materially before the end of the scenario period (year ' +
                        depYear + ' of ' + scenarioHorizon + ') while the base-growth reference lasts.',
                    severityKind: 'early_depletion',
                    yearsOfWithdrawals: 0,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }

            // Base also depletes. Compare timing only — do not treat "both eventually
            // deplete" as Severe by itself (validation defect: incomplete_funding fallthrough).
            var earlierBy = basePath.depletedYear - depYear;
            if (earlierBy >= earlierYears) {
                return {
                    code: IMPACT.SEVERE,
                    reason: 'Scenario depletes at least ' + earlierYears +
                        ' years earlier than the base-growth reference.',
                    severityKind: 'much_earlier_than_base',
                    yearsOfWithdrawals: 0,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }
            if (earlierBy > 0) {
                return {
                    code: IMPACT.NOTICEABLE,
                    reason: 'Scenario depletes earlier than the base-growth reference (' +
                        earlierBy + ' year(s)).',
                    severityKind: 'somewhat_earlier_than_base',
                    yearsOfWithdrawals: 0,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }
            // earlierBy <= 0: same year as base, or later than base (scenario same/better).
            if (earlierBy === 0) {
                return {
                    code: IMPACT.LITTLE,
                    reason: 'Scenario depletes in the same year as the already-depleting base-growth reference — not worse than base solely because both paths eventually deplete.',
                    severityKind: 'same_as_base_depletion',
                    yearsOfWithdrawals: 0,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }
            return {
                code: IMPACT.LITTLE,
                reason: 'Scenario depletes later than the already-depleting base-growth reference (year ' +
                    depYear + ' vs base year ' + basePath.depletedYear +
                    ') — not Severe merely because both paths eventually deplete.',
                severityKind: 'better_than_base_depletion',
                yearsOfWithdrawals: 0,
                endingRatio: ratio,
                pctOfStart: pctOfStart
            };
        }

        // Scenario lasts (no depletion year) while base depleted — treat as not worse than base.
        if (!scenDepleted && baseDepleted) {
            return {
                code: IMPACT.LITTLE,
                reason: 'Scenario lasts its horizon while the base-growth reference depletes — not worse than base.',
                severityKind: 'better_than_base_depletion',
                yearsOfWithdrawals: yearsLeft,
                endingRatio: ratio,
                pctOfStart: pctOfStart
            };
        }

        if (scenarioPath.lastedFullHorizon) {
            var strongCushion =
                yearsLeft >= cushionYearsLittle ||
                (pctOfStart !== null && pctOfStart >= cushionPctStartLittle);

            if (strongCushion) {
                return {
                    code: IMPACT.LITTLE,
                    reason: 'Scenario lasts the full horizon with a substantial absolute cushion (' +
                        (Number.isFinite(yearsLeft) ? yearsLeft.toFixed(1) + ' years of withdrawals' : 'no withdrawal need') +
                        (pctOfStart !== null ? ', ' + (pctOfStart * 100).toFixed(0) + '% of starting savings' : '') +
                        ') — absolute-cushion guard; not flagged merely for a lower ratio vs base.',
                    severityKind: 'absolute_cushion_guard',
                    yearsOfWithdrawals: yearsLeft,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }

            if (ratio === null || ratio >= ratioFloor) {
                return {
                    code: IMPACT.LITTLE,
                    reason: 'Scenario lasts the full horizon and ending cushion is not meaningfully thin vs the base-growth reference.',
                    severityKind: 'full_horizon_ok',
                    yearsOfWithdrawals: yearsLeft,
                    endingRatio: ratio,
                    pctOfStart: pctOfStart
                };
            }

            return {
                code: IMPACT.NOTICEABLE,
                reason: 'Scenario lasts the full horizon, but ending cushion is meaningfully reduced vs base (ratio ' +
                    (ratio * 100).toFixed(1) + '%; ' +
                    (Number.isFinite(yearsLeft) ? yearsLeft.toFixed(1) + ' years of withdrawals left' : 'n/a') + ').',
                severityKind: 'thin_relative_cushion',
                yearsOfWithdrawals: yearsLeft,
                endingRatio: ratio,
                pctOfStart: pctOfStart
            };
        }

        return {
            code: IMPACT.SEVERE,
            reason: 'Scenario does not fully fund withdrawals through its horizon.',
            severityKind: 'incomplete_funding',
            yearsOfWithdrawals: yearsLeft,
            endingRatio: ratio,
            pctOfStart: pctOfStart
        };
    }

    function impactRank(code) {
        if (code === IMPACT.SEVERE) return 3;
        if (code === IMPACT.NOTICEABLE) return 2;
        return 1;
    }

    function pickMostImportant(results) {
        var best = null;
        SCENARIO_IDS.forEach(function (id) {
            var row = results[id];
            if (!row) return;
            var candidate = {
                id: id,
                name: SCENARIO_NAMES[id],
                impact: row.impact.code,
                depletedYear: row.path.depletedYear,
                endingRatio: row.endingRatio,
                endingBalance: row.path.endingBalance
            };
            if (!best) {
                best = candidate;
                return;
            }
            var rankDiff = impactRank(candidate.impact) - impactRank(best.impact);
            if (rankDiff > 0) {
                best = candidate;
                return;
            }
            if (rankDiff < 0) return;

            // Same impact: prefer earlier depletion, then worse ending ratio
            var cDep = candidate.depletedYear === null ? 9999 : candidate.depletedYear;
            var bDep = best.depletedYear === null ? 9999 : best.depletedYear;
            if (cDep < bDep) {
                best = candidate;
                return;
            }
            if (cDep > bDep) return;

            var cRatio = candidate.endingRatio === null ? 1 : candidate.endingRatio;
            var bRatio = best.endingRatio === null ? 1 : best.endingRatio;
            if (cRatio < bRatio) best = candidate;
        });

        if (!best) {
            return {
                id: null,
                name: 'No single stress dominated',
                impact: IMPACT.LITTLE,
                note: 'All scenarios showed little change under this pack.'
            };
        }

        if (best.impact === IMPACT.LITTLE) {
            return {
                id: null,
                name: 'No single stress dominated',
                impact: IMPACT.LITTLE,
                note: 'All scenarios showed little change under this pack.',
                tieBreakCandidate: best
            };
        }

        return best;
    }

    /**
     * Presentation-only pressure analysis. Does not change severity or aggregation.
     */
    function analyzePressure(results) {
        var rows = SCENARIO_IDS.map(function (id) {
            var row = results[id];
            return {
                id: id,
                name: SCENARIO_NAMES[id],
                impact: row.impact.code,
                rank: impactRank(row.impact.code),
                depletedYear: row.path.depletedYear
            };
        });
        var topRank = 0;
        rows.forEach(function (row) {
            if (row.rank > topRank) topRank = row.rank;
        });

        if (topRank <= 1) {
            return {
                mode: 'none',
                ids: [],
                names: [],
                displayName: 'No single stress dominated',
                sentence: 'In these tests, no single stress dominated.'
            };
        }

        var band = rows.filter(function (row) { return row.rank === topRank; });

        // Materially worse: depletes at least 3 years earlier than every other peer in the band
        var clearWinner = null;
        band.forEach(function (candidate) {
            if (candidate.depletedYear === null) return;
            var clearlyWorse = band.every(function (other) {
                if (other.id === candidate.id) return true;
                if (other.depletedYear === null) return true;
                return (other.depletedYear - candidate.depletedYear) >= 3;
            });
            if (clearlyWorse) clearWinner = candidate;
        });

        if (clearWinner || band.length === 1) {
            var single = clearWinner || band[0];
            return {
                mode: 'single',
                ids: [single.id],
                names: [single.name],
                displayName: single.name,
                sentence: 'In these tests, the main pressure was ' + single.name + '.'
            };
        }

        if (band.length === 2) {
            return {
                mode: 'tie',
                ids: [band[0].id, band[1].id],
                names: [band[0].name, band[1].name],
                displayName: band[0].name + ' and ' + band[1].name,
                sentence: 'In these tests, the plan looks similarly sensitive to ' +
                    band[0].name + ' and ' + band[1].name + '.'
            };
        }

        return {
            mode: 'tie',
            ids: band.map(function (row) { return row.id; }),
            names: band.map(function (row) { return row.name; }),
            displayName: 'Weaker growth, early market decline, and longer retirement',
            sentence: 'In these tests, weaker growth, an early market decline, and a longer retirement each created similar strain. No single stress stood out alone.'
        };
    }

    function aggregateOverall(impacts, impactDetails, phase3Code, ratePct, pack) {
        var severe = 0;
        var noticeable = 0;
        var genuineSevere = 0;
        var lateOnlySevere = 0;

        SCENARIO_IDS.forEach(function (id) {
            var code = impacts[id];
            var detail = impactDetails && impactDetails[id] ? impactDetails[id] : null;
            var kind = detail && detail.severityKind ? detail.severityKind : null;
            if (code === IMPACT.SEVERE) {
                severe += 1;
                if (kind === 'late_depletion') {
                    lateOnlySevere += 1;
                } else {
                    genuineSevere += 1;
                }
            } else if (code === IMPACT.NOTICEABLE) {
                noticeable += 1;
            }
        });

        var difficultBoost = pack.difficultPlusAnySevereNeedsAdjustment !== false;
        var code = OVERALL.HOLDS;
        var notes = [];

        // Round 2: Needs requires two or more genuine Severes (or difficult + any genuine Severe).
        // Late-depletion Severes (if any remain) do not alone force Needs for workable ~4% plans.
        if (genuineSevere >= 2 || (difficultBoost && phase3Code === 'difficult' && genuineSevere >= 1)) {
            code = OVERALL.NEEDS;
            notes.push('Needs from genuine severe count / Phase 3 difficult boost.');
        } else if (severe >= 2 && genuineSevere < 2) {
            // Two+ severes but not enough "genuine" — treat as Sensitive unless difficult boost on any severe
            if (difficultBoost && phase3Code === 'difficult' && severe >= 1) {
                code = OVERALL.NEEDS;
                notes.push('Needs from Phase 3 difficult + severe.');
            } else {
                code = OVERALL.SENSITIVE;
                notes.push('Multiple severes were late-depletion style; capped at Sensitive.');
            }
        } else if (severe === 1 || noticeable >= 2) {
            code = OVERALL.SENSITIVE;
        } else if (noticeable <= 1 && severe === 0) {
            code = OVERALL.HOLDS;
        } else {
            code = OVERALL.SENSITIVE;
        }

        // Workable near-4% guard: late-only distress should not auto-promote to Needs.
        var capEnabled = pack.workableNearFourCapNeeds !== false;
        var minRate = clampNumber(pack.workableNearFourMinRate, 3.5);
        var maxRate = clampNumber(pack.workableNearFourMaxRate, 4.5);
        var nearFour = ratePct !== null && ratePct >= minRate && ratePct <= maxRate;
        if (
            capEnabled &&
            code === OVERALL.NEEDS &&
            phase3Code === 'workable' &&
            nearFour &&
            genuineSevere === 0
        ) {
            code = OVERALL.SENSITIVE;
            notes.push('Workable near-4% guard: no genuine early breakdown — Needs capped to Sensitive.');
        }

        return {
            code: code,
            severe: severe,
            noticeable: noticeable,
            genuineSevere: genuineSevere,
            lateOnlySevere: lateOnlySevere,
            notes: notes
        };
    }

    function normalizePlan(plan) {
        var monthlySpending = clampNumber(plan.monthlySpending, 0);
        var monthlySs = clampNumber(plan.monthlySocialSecurity, 0);
        var monthlyOther = clampNumber(plan.monthlyOtherIncome, 0);
        var balance = clampNumber(plan.savingsBalance, 0);
        var monthlyNeed = plan.monthlyFromSavings;
        if (monthlyNeed === undefined || monthlyNeed === null || monthlyNeed === '') {
            monthlyNeed = Math.max(0, monthlySpending - monthlySs - monthlyOther);
        } else {
            monthlyNeed = Math.max(0, clampNumber(monthlyNeed, 0));
        }
        var annualNeed = monthlyNeed * 12;
        var assessment = assessPhase3(annualNeed, balance);

        return {
            id: plan.id || '',
            name: plan.name || 'Untitled plan',
            persona: plan.persona || '',
            notes: plan.notes || '',
            expectedOverall: plan.expectedOverall || null,
            expectedDominant: plan.expectedDominant || null,
            monthlySpending: monthlySpending,
            monthlySocialSecurity: monthlySs,
            monthlyOtherIncome: monthlyOther,
            monthlyFromSavings: monthlyNeed,
            annualFromSavings: annualNeed,
            savingsBalance: balance,
            phase3: assessment,
            enabled: plan.enabled !== false,
            isConfigurable: !!plan.isConfigurable,
            source: plan.source || 'fixture'
        };
    }

    function runStressTest(planInput, pack) {
        var plan = normalizePlan(planInput);
        var B = plan.savingsBalance;
        var W = plan.annualFromSavings;
        var baseYears = Math.max(1, Math.floor(clampNumber(pack.baseHorizonYears, 30)));
        var longYears = Math.max(
            baseYears + 1,
            Math.floor(clampNumber(pack.longerHorizonYears, baseYears + clampNumber(pack.longerExtensionYears, 5)))
        );
        var baseGrowth = clampNumber(pack.baseGrowthRate, 0.025);
        var weakGrowth = clampNumber(pack.weakerGrowthRate, 0.005);
        var earlyDeclinePct = clampNumber(pack.earlyDeclinePct, -20);

        var basePath = projectPath(B, W, baseYears, baseGrowth, 0);
        var weakerPath = projectPath(B, W, baseYears, weakGrowth, 0);
        var earlyPath = projectPath(B, W, baseYears, baseGrowth, earlyDeclinePct);
        var longerPath = projectPath(B, W, longYears, baseGrowth, 0);
        var earlyDeclineBeforeWithdrawal = pack.earlyDeclineBeforeWithdrawal !== false;

        var scenarios = {
            weakerGrowth: {
                id: 'weakerGrowth',
                name: SCENARIO_NAMES.weakerGrowth,
                horizonYears: baseYears,
                growthRate: weakGrowth,
                startingDeclinePct: 0,
                path: weakerPath,
                comparePath: basePath
            },
            earlyDecline: {
                id: 'earlyDecline',
                name: SCENARIO_NAMES.earlyDecline,
                horizonYears: baseYears,
                growthRate: baseGrowth,
                startingDeclinePct: earlyDeclinePct,
                path: earlyPath,
                comparePath: basePath
            },
            longerRetirement: {
                id: 'longerRetirement',
                name: SCENARIO_NAMES.longerRetirement,
                horizonYears: longYears,
                growthRate: baseGrowth,
                startingDeclinePct: 0,
                path: longerPath,
                comparePath: basePath
            }
        };

        var results = {};
        var impactCodes = {};
        var impactDetails = {};

        SCENARIO_IDS.forEach(function (id) {
            var s = scenarios[id];
            var compare = s.comparePath;
            var impact;

            if (id === 'longerRetirement') {
                impact = classifyLongerRetirement(
                    basePath,
                    longerPath,
                    baseYears,
                    longYears,
                    pack,
                    B,
                    W
                );
            } else {
                impact = classifyImpact(compare, s.path, s.horizonYears, pack, B, W);
            }

            var ratio = endingRatio(s.path.endingBalance, compare.endingBalance);
            var yow = yearsOfWithdrawals(s.path.endingBalance, W);
            results[id] = {
                id: id,
                name: s.name,
                horizonYears: s.horizonYears,
                growthRate: s.growthRate,
                startingDeclinePct: s.startingDeclinePct,
                path: {
                    endingBalance: s.path.endingBalance,
                    depletedYear: s.path.depletedYear,
                    yearsFunded: s.path.yearsFunded,
                    lastedFullHorizon: s.path.lastedFullHorizon,
                    cannotFundFirstYear: s.path.cannotFundFirstYear,
                    startingBalanceAfterShock: s.path.startingBalanceAfterShock,
                    yearsOfWithdrawals: yow,
                    pctOfStart: B > 0 ? s.path.endingBalance / B : null
                },
                compareEndingBalance: compare.endingBalance,
                compareDepletedYear: compare.depletedYear,
                endingRatio: ratio,
                impact: {
                    code: impact.code,
                    label: IMPACT_LABEL[impact.code],
                    reason: impact.reason,
                    severityKind: impact.severityKind || null
                }
            };
            impactCodes[id] = impact.code;
            impactDetails[id] = impact;
        });

        var overall = aggregateOverall(
            impactCodes,
            impactDetails,
            plan.phase3.code,
            plan.phase3.ratePct,
            pack
        );
        var mostImportant = pickMostImportant(results);
        var pressure = analyzePressure(results);

        return {
            plan: plan,
            packId: pack.id || pack.configId || null,
            packName: pack.name || null,
            parameters: {
                classificationVersion: 'round2',
                baseHorizonYears: baseYears,
                longerHorizonYears: longYears,
                longerExtensionYears: longYears - baseYears,
                baseGrowthRate: baseGrowth,
                weakerGrowthRate: weakGrowth,
                earlyDeclinePct: earlyDeclinePct,
                postDeclineGrowth: 'base',
                earlyDeclineBeforeWithdrawal: earlyDeclineBeforeWithdrawal,
                endingBalanceRatioFloor: clampNumber(pack.endingBalanceRatioFloor, 0.7),
                earlierDepletionYears: clampNumber(pack.earlierDepletionYears, 5),
                lateDepletionFraction: clampNumber(pack.lateDepletionFraction, 0.2),
                cushionYearsForLittle: clampNumber(pack.cushionYearsForLittle, 8),
                cushionPctOfStartForLittle: clampNumber(pack.cushionPctOfStartForLittle, 0.3),
                longevityRatioFloor: clampNumber(
                    pack.longevityRatioFloor != null ? pack.longevityRatioFloor : pack.endingBalanceRatioFloor,
                    0.7
                ),
                workableNearFourCapNeeds: pack.workableNearFourCapNeeds !== false,
                todaysDollars: true
            },
            baseReference: {
                endingBalance: basePath.endingBalance,
                depletedYear: basePath.depletedYear,
                yearsFunded: basePath.yearsFunded,
                lastedFullHorizon: basePath.lastedFullHorizon,
                yearsOfWithdrawals: yearsOfWithdrawals(basePath.endingBalance, W)
            },
            scenarios: results,
            overall: {
                code: overall.code,
                label: OVERALL_LABEL[overall.code],
                severeCount: overall.severe,
                noticeableCount: overall.noticeable,
                genuineSevereCount: overall.genuineSevere,
                lateOnlySevereCount: overall.lateOnlySevere,
                aggregationNotes: overall.notes
            },
            mostImportant: mostImportant,
            pressure: pressure
        };
    }

    function classifyLongerRetirement(basePath, longerPath, baseYears, longYears, pack, startingBalance, annualNeed) {
        var ratioFloor = clampNumber(
            pack.longevityRatioFloor != null ? pack.longevityRatioFloor : pack.endingBalanceRatioFloor,
            0.7
        );
        var lateFraction = clampNumber(pack.lateDepletionFraction, 0.2);
        var cushionYearsLittle = clampNumber(pack.cushionYearsForLittle, 8);
        var cushionPctStartLittle = clampNumber(pack.cushionPctOfStartForLittle, 0.3);
        var extension = Math.max(0, longYears - baseYears);
        var yearsLeftLong = yearsOfWithdrawals(longerPath.endingBalance, annualNeed);
        var yearsLeftBase = yearsOfWithdrawals(basePath.endingBalance, annualNeed);
        var pctOfStart = startingBalance > 0 ? longerPath.endingBalance / startingBalance : null;
        var stretchRatio = endingRatio(longerPath.endingBalance, basePath.endingBalance);

        if (longerPath.cannotFundFirstYear) {
            return {
                code: IMPACT.SEVERE,
                reason: 'Cannot fund the first year’s withdrawal.',
                severityKind: 'year1_shortfall',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        if (basePath.depletedYear !== null) {
            if (longerPath.depletedYear !== null &&
                longerPath.depletedYear <= basePath.depletedYear) {
                return {
                    code: IMPACT.SEVERE,
                    reason: 'Savings already deplete within the base horizon; a longer retirement adds no recovery room.',
                    severityKind: 'early_depletion',
                    yearsOfWithdrawals: 0,
                    endingRatio: stretchRatio,
                    pctOfStart: pctOfStart
                };
            }
            return {
                code: IMPACT.NOTICEABLE,
                reason: 'Base horizon already shows depletion pressure; extending longevity increases strain.',
                severityKind: 'base_already_stressed',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        // Depletes during the longer horizon
        if (longerPath.depletedYear !== null) {
            if (longerPath.depletedYear <= baseYears) {
                return {
                    code: IMPACT.SEVERE,
                    reason: 'Depletes within the base horizon under longer-retirement comparison.',
                    severityKind: 'early_depletion',
                    yearsOfWithdrawals: 0,
                    endingRatio: stretchRatio,
                    pctOfStart: pctOfStart
                };
            }
            var lateStartLong = lateDepletionStartYear(longYears, lateFraction);
            if (longerPath.depletedYear >= lateStartLong) {
                return {
                    code: IMPACT.NOTICEABLE,
                    reason: 'Savings fund the base horizon but run out near the end of the longer period (year ' +
                        longerPath.depletedYear + ' of ' + longYears + ').',
                    severityKind: 'late_depletion',
                    yearsOfWithdrawals: 0,
                    endingRatio: stretchRatio,
                    pctOfStart: pctOfStart
                };
            }
            return {
                code: IMPACT.SEVERE,
                reason: 'Extending the horizon causes depletion well before the longer planning period ends (year ' +
                    longerPath.depletedYear + ' of ' + longYears + ').',
                severityKind: 'early_depletion',
                yearsOfWithdrawals: 0,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        // Lasts full longer horizon — longevity sensitivity + absolute cushion
        var strongCushion =
            yearsLeftLong >= cushionYearsLittle ||
            (pctOfStart !== null && pctOfStart >= cushionPctStartLittle);

        // Educational longevity signal: base-horizon cushion (years of W) shorter than the extension
        var longevityPressure = Number.isFinite(yearsLeftBase) && yearsLeftBase < extension;

        if (strongCushion && !longevityPressure && (stretchRatio === null || stretchRatio >= ratioFloor)) {
            return {
                code: IMPACT.LITTLE,
                reason: 'Savings last the longer horizon with a substantial remaining cushion.',
                severityKind: 'absolute_cushion_guard',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        if (longevityPressure) {
            return {
                code: IMPACT.NOTICEABLE,
                reason: 'Base-horizon ending cushion is only about ' +
                    yearsLeftBase.toFixed(1) + ' years of withdrawals, less than the +' +
                    extension + ' year longevity extension — longer retirement is material.',
                severityKind: 'longevity_cushion_short',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        if (stretchRatio !== null && stretchRatio < ratioFloor) {
            return {
                code: IMPACT.NOTICEABLE,
                reason: 'Savings last the longer horizon, but the ending cushion is thinner than ' +
                    Math.round(ratioFloor * 100) + '% of the base-horizon ending balance (ratio ' +
                    (stretchRatio * 100).toFixed(1) + '%).',
                severityKind: 'thin_relative_cushion',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        if (strongCushion) {
            return {
                code: IMPACT.LITTLE,
                reason: 'Savings last the longer horizon with a substantial absolute cushion.',
                severityKind: 'absolute_cushion_guard',
                yearsOfWithdrawals: yearsLeftLong,
                endingRatio: stretchRatio,
                pctOfStart: pctOfStart
            };
        }

        return {
            code: IMPACT.LITTLE,
            reason: 'Longer retirement does not materially change the funding picture under these provisional rules.',
            severityKind: 'full_horizon_ok',
            yearsOfWithdrawals: yearsLeftLong,
            endingRatio: stretchRatio,
            pctOfStart: pctOfStart
        };
    }

    function judgmentFlags(run) {
        var flags = [];
        var phase3 = run.plan.phase3.code;
        var overall = run.overall.code;
        var rate = run.plan.phase3.ratePct;

        if (phase3 === 'workable' && overall === OVERALL.NEEDS) {
            flags.push({
                code: 'harsh_vs_phase3',
                severity: 'warn',
                text: 'Phase 3 workable but overall “needs adjustment” — possibly too harsh.'
            });
        }
        if (phase3 === 'difficult' && overall === OVERALL.HOLDS) {
            flags.push({
                code: 'soft_vs_phase3',
                severity: 'warn',
                text: 'Phase 3 difficult but overall “holds up” — possibly too soft / contradictory.'
            });
        }
        if (phase3 === 'close' && overall === OVERALL.NEEDS) {
            flags.push({
                code: 'harsh_for_close',
                severity: 'info',
                text: 'Phase 3 “close” landed in “needs adjustment” — check whether that feels educational or extreme.'
            });
        }
        if (rate !== null && rate <= 2 && overall !== OVERALL.HOLDS) {
            flags.push({
                code: 'low_wr_not_calm',
                severity: 'warn',
                text: 'Very low withdrawal rate but not “holds up” — possibly too harsh.'
            });
        }
        if (rate !== null && rate >= 7 && overall === OVERALL.HOLDS) {
            flags.push({
                code: 'high_wr_calm',
                severity: 'warn',
                text: 'High withdrawal rate still “holds up” — possibly too soft.'
            });
        }
        if (rate !== null && rate >= 3.5 && rate <= 4.5 && overall === OVERALL.HOLDS &&
            run.overall.noticeableCount === 0 && run.overall.severeCount === 0) {
            flags.push({
                code: 'near4_no_nuance',
                severity: 'info',
                text: 'Near ~4% with all “little change” — may lack useful nuance for calibration.'
            });
        }

        if (run.plan.expectedOverall) {
            var expOverall = run.plan.expectedOverall;
            var overallMatches = expOverall === overall ||
                (expOverall === 'holds_or_sensitive' && (overall === OVERALL.HOLDS || overall === OVERALL.SENSITIVE)) ||
                (expOverall === 'sensitive_or_needs' && (overall === OVERALL.SENSITIVE || overall === OVERALL.NEEDS));
            if (!overallMatches) {
                flags.push({
                    code: 'expected_overall_mismatch',
                    severity: 'info',
                    text: 'Overall “' + overall + '” differs from fixture expectation “' +
                        expOverall + '”.'
                });
            }
        }

        if (run.plan.expectedDominant) {
            var got = run.mostImportant && run.mostImportant.id ? run.mostImportant.id : 'none';
            var exp = run.plan.expectedDominant;
            if (exp !== 'none' && exp !== 'any' && got !== exp) {
                flags.push({
                    code: 'expected_dominant_mismatch',
                    severity: 'info',
                    text: 'Most important “' + got + '” differs from fixture expectation “' + exp + '”.'
                });
            }
            if (exp === 'none' && got !== 'none') {
                flags.push({
                    code: 'expected_dominant_mismatch',
                    severity: 'info',
                    text: 'Expected no dominant stress, but “' + got + '” was selected.'
                });
            }
        }

        return flags;
    }

    global.Phase4StressEngine = {
        IMPACT: IMPACT,
        IMPACT_LABEL: IMPACT_LABEL,
        OVERALL: OVERALL,
        OVERALL_LABEL: OVERALL_LABEL,
        SCENARIO_IDS: SCENARIO_IDS,
        SCENARIO_NAMES: SCENARIO_NAMES,
        assessPhase3: assessPhase3,
        projectPath: projectPath,
        normalizePlan: normalizePlan,
        runStressTest: runStressTest,
        analyzePressure: analyzePressure,
        yearsOfWithdrawals: yearsOfWithdrawals,
        lateDepletionStartYear: lateDepletionStartYear,
        classifyImpact: classifyImpact
    };
}(typeof window !== 'undefined' ? window : globalThis));
