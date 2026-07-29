/**
 * Phase 4 Stress Test page controller (review build).
 * Requires a complete saved Phase 3 plan. Does not auto-run on load.
 */
(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var recordKey = 'stress-test';
    var phase3Key = 'build-your-plan';
    var recordTools = window.rbJourneyRecords;
    var engine = window.Phase4StressEngine;
    var config = window.Phase4Config;
    var adjustments = window.Phase4Adjustments;

    var state = {
        phase3: null,
        phase3Ready: false,
        lastRun: null,
        selectedAdjustmentId: null,
        savedRecord: null
    };

    var assessmentLabels = {
        workable: 'Looks workable on these assumptions',
        close: 'Looks close and may need adjustment',
        difficult: 'Looks difficult on these assumptions'
    };

    var scenarioWhat = {
        weakerGrowth: 'What if your retirement savings grow more slowly than the base case assumes?',
        earlyDecline: 'What if markets decline early in retirement while withdrawals continue?',
        longerRetirement: 'What if your retirement savings need to support five additional years?'
    };

    function $(id) {
        return document.getElementById(id);
    }

    function readProgress() {
        try {
            var parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function writeProgress(progress) {
        localStorage.setItem(storageKey, JSON.stringify(progress));
        if (window.rbJourneySync && typeof window.rbJourneySync.scheduleSave === 'function') {
            window.rbJourneySync.scheduleSave('phase');
        }
    }

    function money(n) {
        if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
        return Number(n).toLocaleString('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        });
    }

    function pctFromDecimal(rate) {
        if (rate === null || rate === undefined || !Number.isFinite(Number(rate))) return '—';
        return (Number(rate) * 100).toFixed(2) + '%';
    }

    function yowText(years) {
        if (years === null || years === undefined || !Number.isFinite(Number(years))) return null;
        if (!Number.isFinite(years) || years > 500) return null;
        return Number(years).toFixed(1);
    }

    function loadPhase3(progress) {
        var raw = progress.records && progress.records[phase3Key];
        if (!raw || typeof raw !== 'object') return null;
        return recordTools.normalizeBuildYourPlanRecord(raw, progress[phase3Key] === true);
    }

    function phase3IsReady(record) {
        if (!record || record.saved !== true) return false;
        if (record.assessmentStatus !== 'complete') return false;
        if (record.monthlyRetirementSpendingGoal === null || record.monthlyRetirementSpendingGoal === undefined) return false;
        if (record.monthlySocialSecurityAssumption === null || record.monthlySocialSecurityAssumption === undefined) return false;
        if (record.monthlyOtherDependableIncome === null || record.monthlyOtherDependableIncome === undefined) return false;
        if (record.retirementSavingsBalance === null || record.retirementSavingsBalance === undefined) return false;
        if (record.retirementSavingsBalance < 0) return false;
        if (record.annualNeededFromRetirementSavings === null ||
            record.annualNeededFromRetirementSavings === undefined) {
            if (record.monthlyNeededFromRetirementSavings === null ||
                record.monthlyNeededFromRetirementSavings === undefined) {
                return false;
            }
        }
        if (!record.baseCaseAssessment) return false;
        return true;
    }

    function snapshotPhase3(record) {
        return {
            monthlyRetirementSpendingGoal: record.monthlyRetirementSpendingGoal,
            annualRetirementSpendingGoal: record.annualRetirementSpendingGoal,
            monthlySocialSecurityAssumption: record.monthlySocialSecurityAssumption,
            socialSecuritySource: record.socialSecuritySource || '',
            temporarySocialSecurityEstimateUsed: record.temporarySocialSecurityEstimateUsed === true,
            monthlyOtherDependableIncome: record.monthlyOtherDependableIncome,
            monthlyNeededFromRetirementSavings: record.monthlyNeededFromRetirementSavings,
            annualNeededFromRetirementSavings: record.annualNeededFromRetirementSavings,
            retirementSavingsBalance: record.retirementSavingsBalance,
            impliedInitialWithdrawalRate: record.impliedInitialWithdrawalRate,
            baseCaseAssessment: record.baseCaseAssessment,
            assessmentStatus: record.assessmentStatus,
            schemaVersion: record.schemaVersion || 1,
            updatedAt: record.updatedAt || record.lastReviewedAt || record.completedAt || '',
            createdAt: record.createdAt || ''
        };
    }

    function phase3ChangedSinceSnapshot(current, snapshot) {
        if (!current || !snapshot) return false;
        var keys = [
            'monthlyRetirementSpendingGoal',
            'monthlySocialSecurityAssumption',
            'monthlyOtherDependableIncome',
            'monthlyNeededFromRetirementSavings',
            'annualNeededFromRetirementSavings',
            'retirementSavingsBalance',
            'baseCaseAssessment',
            'temporarySocialSecurityEstimateUsed'
        ];
        return keys.some(function (key) {
            return current[key] !== snapshot[key];
        });
    }

    function toEnginePlan(record) {
        var monthlyNeed = record.monthlyNeededFromRetirementSavings;
        if (monthlyNeed === null || monthlyNeed === undefined) {
            monthlyNeed = Math.max(
                0,
                Number(record.monthlyRetirementSpendingGoal) -
                Number(record.monthlySocialSecurityAssumption) -
                Number(record.monthlyOtherDependableIncome)
            );
        }
        return {
            id: 'phase3',
            name: 'Phase 3 plan',
            monthlySpending: Number(record.monthlyRetirementSpendingGoal),
            monthlySocialSecurity: Number(record.monthlySocialSecurityAssumption),
            monthlyOtherIncome: Number(record.monthlyOtherDependableIncome),
            monthlyFromSavings: Number(monthlyNeed),
            savingsBalance: Number(record.retirementSavingsBalance)
        };
    }

    function metricLine(scenario, horizonYears) {
        var path = scenario.path;
        var parts = [];
        if (path.cannotFundFirstYear) {
            parts.push('The plan cannot fund the first year’s withdrawal under this stress.');
        } else if (path.depletedYear !== null) {
            parts.push('Savings run out around year ' + path.depletedYear + ' of the ' + horizonYears + '-year test.');
        } else {
            parts.push('About ' + money(path.endingBalance) + ' remains after ' + horizonYears + ' years.');
            var yow = yowText(path.yearsOfWithdrawals);
            if (yow) {
                parts.push('That is roughly ' + yow + ' years of withdrawals left.');
            }
        }
        return parts.join(' ');
    }

    function renderRecap(record) {
        $('recapSpending').textContent = money(record.monthlyRetirementSpendingGoal) + ' / mo';
        $('recapSs').textContent = money(record.monthlySocialSecurityAssumption) + ' / mo';
        $('recapOther').textContent = money(record.monthlyOtherDependableIncome) + ' / mo';
        $('recapNeedMonthly').textContent = money(record.monthlyNeededFromRetirementSavings) + ' / mo';
        $('recapNeedAnnual').textContent = money(record.annualNeededFromRetirementSavings) + ' / yr';
        $('recapBalance').textContent = money(record.retirementSavingsBalance);
        $('recapRate').textContent = pctFromDecimal(record.impliedInitialWithdrawalRate);
        $('recapAssessment').textContent = assessmentLabels[record.baseCaseAssessment] || record.baseCaseAssessment;
        $('temporarySsNote').hidden = record.temporarySocialSecurityEstimateUsed !== true;
    }

    function renderScenarioCards(run) {
        var host = $('scenarioCards');
        host.innerHTML = '';
        engine.SCENARIO_IDS.forEach(function (id) {
            var s = run.scenarios[id];
            var article = document.createElement('article');
            article.className = 'stress-scenario-card impact-' + s.impact.code;
            article.setAttribute('aria-label', s.name + ': ' + s.impact.label);

            var title = document.createElement('h3');
            title.textContent = s.name;

            var what = document.createElement('p');
            what.className = 'scenario-what';
            what.textContent = scenarioWhat[id];

            var badge = document.createElement('p');
            badge.className = 'impact-badge';
            badge.innerHTML = '<span class="impact-text">' + s.impact.label + '</span>';

            var metrics = document.createElement('p');
            metrics.className = 'scenario-metrics';
            metrics.textContent = metricLine(s, s.horizonYears);

            var why = document.createElement('p');
            why.className = 'scenario-why';
            why.textContent = s.impact.reason;

            article.appendChild(title);
            article.appendChild(what);
            article.appendChild(badge);
            article.appendChild(metrics);
            article.appendChild(why);
            host.appendChild(article);
        });
    }

    function renderAdjustments(run, phase3) {
        var section = $('adjustmentSection');
        var host = $('adjustmentChoices');
        host.innerHTML = '';
        state.selectedAdjustmentId = null;

        if (run.overall.code === 'holds') {
            section.hidden = true;
            return;
        }

        var options = adjustments.recommendAdjustments(run, phase3);
        options.forEach(function (option, index) {
            var id = 'adj-' + option.id;
            var label = document.createElement('label');
            label.className = 'stress-adjustment-option';
            label.setAttribute('for', id);
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'phase4-adjustment';
            input.id = id;
            input.value = option.id;
            input.addEventListener('change', function () {
                if (input.checked) state.selectedAdjustmentId = option.id;
            });
            var span = document.createElement('span');
            span.textContent = option.label;
            label.appendChild(input);
            label.appendChild(span);
            host.appendChild(label);
            if (index === 0) {
                // no default selection required
            }
        });
        section.hidden = false;
    }

    function showResults(run) {
        $('overallLabel').textContent = run.overall.label;
        $('pressureSentence').textContent = run.pressure.sentence;
        renderScenarioCards(run);
        renderAdjustments(run, state.phase3);
        $('resultsSection').hidden = false;
        $('phase5Handoff').hidden = true;
        $('saveConfirm').hidden = true;
        $('savedReviewSection').hidden = true;
        var heading = $('results-title');
        heading.focus();
    }

    function runTest() {
        if (!state.phase3Ready || !state.phase3) return;
        var plan = toEnginePlan(state.phase3);
        var pack = Object.assign({}, config, { id: config.configId, name: config.name });
        var run = engine.runStressTest(plan, pack);
        state.lastRun = run;
        state.selectedAdjustmentId = null;
        showResults(run);
    }

    function buildSavePayload() {
        var run = state.lastRun;
        var phase3 = state.phase3;
        if (!run || !phase3) return null;

        var selected = null;
        if (state.selectedAdjustmentId && adjustments.OPTIONS) {
            Object.keys(adjustments.OPTIONS).forEach(function (key) {
                var opt = adjustments.OPTIONS[key];
                if (opt.id === state.selectedAdjustmentId) selected = opt;
            });
        }
        // Also match from rendered recommendations
        if (!selected && state.selectedAdjustmentId) {
            selected = {
                id: state.selectedAdjustmentId,
                label: state.selectedAdjustmentId
            };
            var checked = document.querySelector('input[name="phase4-adjustment"]:checked');
            if (checked && checked.parentElement) {
                var span = checked.parentElement.querySelector('span');
                if (span) selected.label = span.textContent;
            }
        }

        function slimScenario(s) {
            return {
                id: s.id,
                name: s.name,
                horizonYears: s.horizonYears,
                growthRate: s.growthRate,
                startingDeclinePct: s.startingDeclinePct,
                impactCode: s.impact.code,
                impactLabel: s.impact.label,
                impactReason: s.impact.reason,
                severityKind: s.impact.severityKind,
                endingBalance: s.path.endingBalance,
                depletedYear: s.path.depletedYear,
                yearsFunded: s.path.yearsFunded,
                yearsOfWithdrawals: s.path.yearsOfWithdrawals,
                lastedFullHorizon: s.path.lastedFullHorizon,
                cannotFundFirstYear: s.path.cannotFundFirstYear,
                endingRatio: s.endingRatio
            };
        }

        return {
            saved: true,
            decisionStatement: 'I’ve reviewed how sensitive my Phase 3 plan is, and I’m carrying this resilience review forward.',
            phase3Snapshot: snapshotPhase3(phase3),
            configId: config.configId,
            configVersion: config.configVersion,
            classificationVersion: config.classificationVersion,
            scenarioParameters: run.parameters,
            baseReference: run.baseReference,
            scenarioResults: {
                weakerGrowth: slimScenario(run.scenarios.weakerGrowth),
                earlyDecline: slimScenario(run.scenarios.earlyDecline),
                longerRetirement: slimScenario(run.scenarios.longerRetirement)
            },
            overallResilienceCode: run.overall.code,
            overallResilienceLabel: run.overall.label,
            pressureMode: run.pressure.mode,
            dominantStressIds: run.pressure.ids.slice(),
            dominantStressLabel: run.pressure.displayName,
            pressureSentence: run.pressure.sentence,
            nextAdjustmentId: selected ? selected.id : null,
            nextAdjustmentLabel: selected ? selected.label : null,
            educationalNonGuarantee: true,
            notes: ''
        };
    }

    function saveReview() {
        if (!state.lastRun) return;
        var progress = readProgress();
        var oldRecord = progress.records && progress.records[recordKey]
            ? recordTools.normalizeStressTestRecord(progress.records[recordKey], progress[recordKey] === true)
            : {};
        var payload = buildSavePayload();
        var record = recordTools.createStressTestRecord(payload, {
            oldRecord: oldRecord,
            journeyComplete: true,
            reviewed: true,
            timestamp: new Date().toISOString()
        });
        progress.records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        progress.records[recordKey] = record;
        progress[recordKey] = true;
        writeProgress(progress);
        state.savedRecord = record;
        document.querySelectorAll('[data-journey-phase="' + recordKey + '"]').forEach(function (element) {
            element.classList.add('is-complete');
            element.classList.remove('is-next-step', 'is-planned');
            element.setAttribute('data-journey-complete', 'true');
            var status = element.querySelector('[data-journey-phase-status]');
            if (status) {
                status.textContent = 'Completed';
                status.className = 'phase-status is-completed';
                status.hidden = false;
            }
            var recordStatus = element.querySelector('[data-journey-record-status]');
            if (recordStatus) {
                recordStatus.textContent = 'Saved';
                recordStatus.className = 'step-record-status is-saved';
                recordStatus.hidden = false;
            }
        });
        $('saveConfirm').hidden = false;
        $('phase5Handoff').hidden = false;
        $('savedReviewSection').hidden = false;
        renderSavedSummary(record);
        $('saveConfirm').focus();
    }

    function renderSavedSummary(record) {
        var el = $('savedReviewSummary');
        var adj = record.nextAdjustmentLabel
            ? (' Next direction: ' + record.nextAdjustmentLabel + '.')
            : '';
        el.innerHTML = '<p><strong>' + (record.overallResilienceLabel || '') + '</strong></p>' +
            '<p>' + (record.pressureSentence || record.dominantStressLabel || '') + '</p>' +
            '<p>' + (record.decisionStatement || '') + adj + '</p>';
    }

    function init() {
        var progress = readProgress();
        var phase3 = loadPhase3(progress);
        state.phase3 = phase3;
        state.phase3Ready = phase3IsReady(phase3);

        var saved = progress.records && progress.records[recordKey]
            ? recordTools.normalizeStressTestRecord(progress.records[recordKey], progress[recordKey] === true)
            : null;
        state.savedRecord = saved && saved.saved ? saved : null;

        if (!state.phase3Ready) {
            $('phase3IncompleteBanner').hidden = false;
            $('phase3RecapSection').hidden = true;
            $('testsSection').hidden = true;
            return;
        }

        $('phase3IncompleteBanner').hidden = true;
        $('phase3RecapSection').hidden = false;
        $('testsSection').hidden = false;
        renderRecap(phase3);

        if (state.savedRecord && state.savedRecord.phase3Snapshot) {
            if (phase3ChangedSinceSnapshot(phase3, state.savedRecord.phase3Snapshot)) {
                $('phase3ChangedBanner').hidden = false;
            }
            renderSavedSummary(state.savedRecord);
            $('savedReviewSection').hidden = false;
            $('phase5Handoff').hidden = false;
        }

        $('testMyPlanBtn').addEventListener('click', runTest);
        $('saveReviewBtn').addEventListener('click', saveReview);
        var retest = $('retestBtn');
        if (retest) {
            retest.addEventListener('click', function () {
                $('savedReviewSection').hidden = true;
                runTest();
                $('testsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    function startPhase() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    }

    if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
        window.rbJourneySync.afterReady(startPhase);
    } else {
        startPhase();
    }
}());
