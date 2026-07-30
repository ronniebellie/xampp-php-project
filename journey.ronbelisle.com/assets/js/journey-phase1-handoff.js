/**
 * Phase 1 spending-target handoff — single reconciliation helper.
 *
 * Source of truth for "Phase 1 usable by later phases":
 * a positive monthlyRetirementSpendingTarget available from either
 *   - rbJourneyCalculator:retirementSpendingPlan:v1 (preferred), or
 *   - rbJourneyProgressV1.records['spending-goals']
 *
 * Keeps calculator + progress record in canonical sync so Phase 1 landing,
 * Phase 3, and cloud restore agree.
 */
(function () {
    'use strict';

    var PROGRESS_KEY = 'rbJourneyProgressV1';
    var CALCULATOR_KEY = 'rbJourneyCalculator:retirementSpendingPlan:v1';

    function readJson(key) {
        try {
            var parsed = JSON.parse(localStorage.getItem(key) || 'null');
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function writeJson(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function positiveNumber(value) {
        var n = Number(value);
        return Number.isFinite(n) && n > 0 ? n : null;
    }

    function nowIso() {
        return new Date().toISOString();
    }

    function extractFromCalculator(calc) {
        if (!calc || typeof calc !== 'object') return null;
        var outputs = calc.outputs && typeof calc.outputs === 'object' ? calc.outputs : {};
        var inputs = calc.inputs && typeof calc.inputs === 'object' ? calc.inputs : {};
        var data = calc.journeyResult && calc.journeyResult.dataForLaterPhases
            ? calc.journeyResult.dataForLaterPhases
            : {};

        var monthly = positiveNumber(outputs.monthlyRetirementSpendingTarget) ||
            positiveNumber(data.monthlyRetirementSpendingTarget) ||
            positiveNumber(outputs.monthlyTarget) ||
            positiveNumber(inputs.monthlySpendingTarget);
        if (!monthly) return null;

        var other = Math.max(
            0,
            Number(
                outputs.monthlyOtherRegularRetirementIncome != null
                    ? outputs.monthlyOtherRegularRetirementIncome
                    : data.monthlyOtherRegularRetirementIncome
            ) || 0
        );
        var annual = positiveNumber(outputs.annualRetirementSpendingTarget) ||
            positiveNumber(data.annualRetirementSpendingTarget) ||
            monthly * 12;

        var canonical = positiveNumber(outputs.monthlyRetirementSpendingTarget) > 0 ||
            positiveNumber(data.monthlyRetirementSpendingTarget) > 0;

        return {
            usable: true,
            monthlySpending: monthly,
            monthlyOther: other,
            annualSpending: annual,
            lastUpdated: calc.lastUpdated || data.lastUpdated || null,
            source: 'calculator',
            completionStatus: calc.completionStatus || '',
            canonical: !!canonical
        };
    }

    function extractFromProgressRecord(record) {
        if (!record || typeof record !== 'object') return null;
        var data = record.result && record.result.dataForLaterPhases
            ? record.result.dataForLaterPhases
            : {};
        var monthly = positiveNumber(data.monthlyRetirementSpendingTarget) ||
            positiveNumber(record.monthlyRetirementSpendingTarget) ||
            positiveNumber(record.monthlyTarget);
        if (!monthly) return null;

        var other = Math.max(
            0,
            Number(
                data.monthlyOtherRegularRetirementIncome != null
                    ? data.monthlyOtherRegularRetirementIncome
                    : record.monthlyOtherRegularRetirementIncome
            ) || 0
        );
        var annual = positiveNumber(data.annualRetirementSpendingTarget) ||
            positiveNumber(record.annualRetirementSpendingTarget) ||
            monthly * 12;
        var canonical = positiveNumber(data.monthlyRetirementSpendingTarget) > 0;

        return {
            usable: true,
            monthlySpending: monthly,
            monthlyOther: other,
            annualSpending: annual,
            lastUpdated: record.updatedAt || data.lastUpdated || null,
            source: 'progress',
            completionStatus: 'completed',
            canonical: !!canonical
        };
    }

    function extractHandoff(progress, calc) {
        var fromCalc = extractFromCalculator(calc);
        if (fromCalc) return fromCalc;
        var records = progress && progress.records && typeof progress.records === 'object'
            ? progress.records
            : {};
        return extractFromProgressRecord(records['spending-goals']) || {
            usable: false,
            monthlySpending: 0,
            monthlyOther: 0,
            annualSpending: 0,
            lastUpdated: null,
            source: 'none',
            completionStatus: '',
            canonical: false
        };
    }

    function buildCanonicalCalculator(handoff, existingCalc) {
        var timestamp = handoff.lastUpdated || nowIso();
        var existing = existingCalc && typeof existingCalc === 'object' ? existingCalc : {};
        var inputs = existing.inputs && typeof existing.inputs === 'object' ? existing.inputs : {};
        var outputs = {
            monthlyRetirementSpendingTarget: handoff.monthlySpending,
            annualRetirementSpendingTarget: handoff.annualSpending,
            monthlyOtherRegularRetirementIncome: handoff.monthlyOther
        };
        return {
            schemaVersion: 2,
            calculatorId: 'retirement-spending-plan',
            phaseId: 'spending-goals',
            completionStatus: 'completed',
            lastUpdated: timestamp,
            inputs: inputs,
            outputs: outputs,
            journeyResult: {
                keySummaryResult: {
                    label: 'Monthly retirement spending target',
                    value: handoff.monthlySpending,
                    unit: 'usd_per_month'
                },
                returnDestination: '/phases/spending-goals.php',
                dataForLaterPhases: {
                    monthlyRetirementSpendingTarget: handoff.monthlySpending,
                    annualRetirementSpendingTarget: handoff.annualSpending,
                    monthlyOtherRegularRetirementIncome: handoff.monthlyOther,
                    lastUpdated: timestamp
                }
            }
        };
    }

    function buildCanonicalProgressRecord(handoff, existingRecord) {
        var timestamp = handoff.lastUpdated || nowIso();
        var existing = existingRecord && typeof existingRecord === 'object' ? existingRecord : {};
        return {
            phaseId: 'spending-goals',
            schemaVersion: 1,
            saved: true,
            planningRecordStatus: 'current',
            result: {
                keySummaryResult: {
                    label: 'Monthly retirement spending target',
                    value: handoff.monthlySpending,
                    unit: 'usd_per_month'
                },
                returnDestination: '/phases/spending-goals.php',
                dataForLaterPhases: {
                    monthlyRetirementSpendingTarget: handoff.monthlySpending,
                    annualRetirementSpendingTarget: handoff.annualSpending,
                    monthlyOtherRegularRetirementIncome: handoff.monthlyOther,
                    lastUpdated: timestamp
                }
            },
            source: existing.source && typeof existing.source === 'object'
                ? existing.source
                : {
                    type: 'journey-native-calculator',
                    toolId: 'retirement-spending-plan',
                    name: 'Your Retirement Spending Plan',
                    url: '/calculators/retirement-spending-plan/'
                },
            updatedAt: timestamp,
            downstreamReady: true
        };
    }

    function calculatorIsCanonical(calc, handoff) {
        if (!calc || typeof calc !== 'object') return false;
        if (calc.completionStatus !== 'completed') return false;
        var outputs = calc.outputs || {};
        return Number(outputs.monthlyRetirementSpendingTarget) === handoff.monthlySpending;
    }

    function progressRecordIsCanonical(record, handoff) {
        if (!record || typeof record !== 'object') return false;
        var data = record.result && record.result.dataForLaterPhases
            ? record.result.dataForLaterPhases
            : null;
        return !!(data && Number(data.monthlyRetirementSpendingTarget) === handoff.monthlySpending);
    }

    /**
     * Normalize local Phase 1 keys into canonical calculator + progress record.
     * @returns {{changed:boolean,handoff:object}}
     */
    function reconcileLocal() {
        var progress = readJson(PROGRESS_KEY) || {};
        var calc = readJson(CALCULATOR_KEY);
        var handoff = extractHandoff(progress, calc);
        if (!handoff.usable) {
            return { changed: false, handoff: handoff };
        }

        var changed = false;
        var records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        progress.records = records;

        if (progress['spending-goals'] !== true) {
            progress['spending-goals'] = true;
            changed = true;
        }

        if (!calculatorIsCanonical(calc, handoff)) {
            calc = buildCanonicalCalculator(handoff, calc);
            writeJson(CALCULATOR_KEY, calc);
            changed = true;
        }

        if (!progressRecordIsCanonical(records['spending-goals'], handoff)) {
            records['spending-goals'] = buildCanonicalProgressRecord(handoff, records['spending-goals']);
            changed = true;
        }

        if (changed) {
            writeJson(PROGRESS_KEY, progress);
        }

        return { changed: changed, handoff: extractHandoff(progress, calc) };
    }

    function getHandoff() {
        var progress = readJson(PROGRESS_KEY) || {};
        var calc = readJson(CALCULATOR_KEY);
        return extractHandoff(progress, calc);
    }

    function getSummaryRecord() {
        reconcileLocal();
        var calc = readJson(CALCULATOR_KEY) || {};
        var handoff = getHandoff();
        if (!handoff.usable) return null;
        return {
            completionStatus: 'completed',
            lastUpdated: calc.lastUpdated || handoff.lastUpdated,
            outputs: {
                monthlyRetirementSpendingTarget: handoff.monthlySpending,
                annualRetirementSpendingTarget: handoff.annualSpending,
                monthlyOtherRegularRetirementIncome: handoff.monthlyOther
            }
        };
    }

    function mergeLocalPhase1IntoCloud(cloudProgress, localProgress, localCalc, localHandoff) {
        var merged = cloudProgress && typeof cloudProgress === 'object'
            ? JSON.parse(JSON.stringify(cloudProgress))
            : {};
        merged.records = merged.records && typeof merged.records === 'object' ? merged.records : {};
        merged['spending-goals'] = true;
        if (localProgress && localProgress.records && localProgress.records['spending-goals']) {
            merged.records['spending-goals'] = localProgress.records['spending-goals'];
        } else {
            merged.records['spending-goals'] = buildCanonicalProgressRecord(localHandoff, null);
        }
        return {
            progress: merged,
            calc: localCalc && typeof localCalc === 'object'
                ? localCalc
                : buildCanonicalCalculator(localHandoff, null),
            handoff: localHandoff,
            keptLocal: true
        };
    }

    /**
     * Choose the better Phase 1 slice when merging cloud → local.
     * Canonical cloud wins. Otherwise a usable local Phase 1 beats thin/legacy cloud.
     */
    function preferUsablePhase1(cloudProgress, cloudCalc, localProgress, localCalc) {
        var cloudHandoff = extractHandoff(cloudProgress, cloudCalc);
        var localHandoff = extractHandoff(localProgress, localCalc);

        if (cloudHandoff.usable && cloudHandoff.canonical) {
            return {
                progress: cloudProgress,
                calc: cloudCalc,
                handoff: cloudHandoff,
                keptLocal: false
            };
        }
        if (localHandoff.usable && (localHandoff.canonical || !cloudHandoff.usable)) {
            return mergeLocalPhase1IntoCloud(cloudProgress, localProgress, localCalc, localHandoff);
        }
        if (cloudHandoff.usable) {
            return {
                progress: cloudProgress,
                calc: cloudCalc,
                handoff: cloudHandoff,
                keptLocal: false
            };
        }
        if (localHandoff.usable) {
            return mergeLocalPhase1IntoCloud(cloudProgress, localProgress, localCalc, localHandoff);
        }
        return {
            progress: cloudProgress,
            calc: cloudCalc,
            handoff: cloudHandoff,
            keptLocal: false
        };
    }

    window.rbJourneyPhase1 = {
        PROGRESS_KEY: PROGRESS_KEY,
        CALCULATOR_KEY: CALCULATOR_KEY,
        extractHandoff: extractHandoff,
        reconcileLocal: reconcileLocal,
        getHandoff: getHandoff,
        getSummaryRecord: getSummaryRecord,
        preferUsablePhase1: preferUsablePhase1,
        buildCanonicalCalculator: buildCanonicalCalculator,
        buildCanonicalProgressRecord: buildCanonicalProgressRecord
    };
})();
