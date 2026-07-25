/**
 * Phase 6 Survivor Planning page controller (review build).
 * Requires a complete saved Phase 3 plan. Does not auto-run on load.
 * Does not calculate survivor shortfalls or legal documents.
 */
(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var recordKey = 'survivor-planning';
    var phase3Key = 'build-your-plan';
    var phase4Key = 'stress-test';
    var phase5Key = 'tax-strategy';
    var recordTools = window.rbJourneyRecords;
    var engine = window.Phase6SurvivorEngine;
    var priorities = window.Phase6Priorities;

    var state = {
        phase3: null,
        phase3Ready: false,
        phase4Context: null,
        phase5Context: null,
        lastResult: null,
        selectedPriorityId: null,
        selectedPriorityLabel: null,
        strategyChoicesShown: [],
        answersAtLastRun: { assetRecipientReview: '', survivorIncomePreparedness: '' },
        resultsStale: false,
        savedRecord: null
    };

    var assessmentLabels = {
        workable: 'Looks workable on these assumptions',
        close: 'Looks close and may need adjustment',
        difficult: 'Looks difficult on these assumptions'
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
    }

    function money(value) {
        var n = Number(value);
        if (!Number.isFinite(n)) return '—';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(n);
    }

    function pctFromDecimal(value) {
        var n = Number(value);
        if (!Number.isFinite(n)) return '—';
        return (n * 100).toFixed(1).replace(/\.0$/, '') + '%';
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
            'annualRetirementSpendingGoal',
            'monthlySocialSecurityAssumption',
            'temporarySocialSecurityEstimateUsed',
            'monthlyOtherDependableIncome',
            'monthlyNeededFromRetirementSavings',
            'annualNeededFromRetirementSavings',
            'retirementSavingsBalance',
            'impliedInitialWithdrawalRate',
            'baseCaseAssessment',
            'assessmentStatus'
        ];
        return keys.some(function (key) {
            return current[key] !== snapshot[key];
        });
    }

    function loadPhase4Context(progress, phase3) {
        var raw = progress.records && progress.records[phase4Key];
        if (!raw || typeof raw !== 'object' || raw.saved !== true) return null;
        var record = recordTools.normalizeStressTestRecord(raw, progress[phase4Key] === true);
        if (!record.phase3Snapshot) return null;
        if (phase3ChangedSinceSnapshot(phase3, record.phase3Snapshot)) return null;
        return {
            overallResilienceCode: record.overallResilienceCode || '',
            overallResilienceLabel: record.overallResilienceLabel || '',
            pressureSentence: record.pressureSentence || '',
            nextAdjustmentId: record.nextAdjustmentId || null,
            nextAdjustmentLabel: record.nextAdjustmentLabel || null
        };
    }

    function loadPhase5Context(progress, phase3) {
        var raw = progress.records && progress.records[phase5Key];
        if (!raw || typeof raw !== 'object' || raw.saved !== true) return null;
        var record = recordTools.normalizeTaxStrategyRecord(raw, false);
        if (!record.phase3Snapshot) return null;
        if (phase3ChangedSinceSnapshot(phase3, record.phase3Snapshot)) return null;
        return {
            nextPriorityId: record.nextPriorityId || null,
            nextPriorityLabel: record.nextPriorityLabel || null,
            mainIssueIds: (record.result && record.result.mainIssueIds) || []
        };
    }

    function selectedRadio(name) {
        var el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    }

    function setRadio(name, value) {
        if (!value) return;
        var el = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
        if (el) el.checked = true;
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

    function renderPriorContext() {
        var section = $('priorPhaseContextSection');
        var p4 = $('phase4ContextLine');
        var p5 = $('phase5ContextLine');
        var show = false;

        if (state.phase4Context) {
            var parts = [];
            if (state.phase4Context.overallResilienceLabel) {
                parts.push('Phase 4 resilience: ' + state.phase4Context.overallResilienceLabel);
            }
            if (state.phase4Context.pressureSentence) {
                parts.push(state.phase4Context.pressureSentence);
            }
            if (state.phase4Context.nextAdjustmentLabel) {
                parts.push('Selected Phase 4 direction: ' + state.phase4Context.nextAdjustmentLabel);
            }
            p4.textContent = parts.join(' ');
            p4.hidden = parts.length === 0;
            show = show || parts.length > 0;
        } else {
            p4.hidden = true;
        }

        if (state.phase5Context && state.phase5Context.nextPriorityLabel) {
            p5.textContent = 'Phase 5 tax-planning priority: ' + state.phase5Context.nextPriorityLabel;
            p5.hidden = false;
            show = true;
        } else {
            p5.hidden = true;
        }

        section.hidden = !show;
    }

    function markResultsStaleIfNeeded() {
        if (!state.lastResult) return;
        var q1 = selectedRadio('assetRecipientReview');
        var q2 = selectedRadio('survivorIncomePreparedness');
        if (q1 !== state.answersAtLastRun.assetRecipientReview ||
            q2 !== state.answersAtLastRun.survivorIncomePreparedness) {
            state.resultsStale = true;
            $('resultsStaleNote').hidden = false;
            $('savePriorityBtn').disabled = true;
        }
    }

    function renderMainIssues(result) {
        var host = $('mainIssueList');
        var eyebrow = $('mainIssueEyebrow');
        var live = $('mainIssueLive');
        host.innerHTML = '';

        var mode = result.pressureMode;
        if (mode === 'tied') {
            eyebrow.textContent = 'Main survivor-planning priorities';
        } else {
            eyebrow.textContent = 'Main survivor-planning priority';
        }

        var titles = result.issueTitles || [];
        var explanations = result.issueExplanations || [];
        titles.forEach(function (title, index) {
            var titleEl = document.createElement('p');
            titleEl.innerHTML = '<strong>' + title + '</strong>';
            var bodyEl = document.createElement('p');
            bodyEl.className = 'supporting-note';
            bodyEl.textContent = explanations[index] || '';
            host.appendChild(titleEl);
            host.appendChild(bodyEl);
        });

        live.textContent = eyebrow.textContent + '. ' +
            titles.map(function (title, index) {
                return title + ' ' + (explanations[index] || '');
            }).join(' ');
    }

    function renderPriorities(result) {
        var host = $('priorityChoices');
        host.innerHTML = '';
        state.selectedPriorityId = null;
        state.selectedPriorityLabel = null;
        var options = priorities.recommendPriorities(result);
        state.strategyChoicesShown = options.map(function (opt) {
            return { id: opt.id, label: opt.label };
        });

        options.forEach(function (option) {
            var id = 'priority-' + option.id;
            var label = document.createElement('label');
            label.className = 'stress-adjustment-option';
            label.setAttribute('for', id);
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'phase6-priority';
            input.id = id;
            input.value = option.id;
            input.addEventListener('change', function () {
                if (!input.checked) return;
                state.selectedPriorityId = option.id;
                state.selectedPriorityLabel = option.label;
                $('savePriorityBtn').disabled = state.resultsStale;
            });
            var span = document.createElement('span');
            span.textContent = option.label;
            label.appendChild(input);
            label.appendChild(span);
            host.appendChild(label);
        });
        $('savePriorityBtn').disabled = true;
    }

    function showResults(result) {
        renderMainIssues(result);
        var guidance = $('guidanceText');
        if (result.guidanceText) {
            guidance.textContent = result.guidanceText;
            guidance.hidden = false;
        } else {
            guidance.hidden = true;
        }
        renderPriorities(result);
        state.lastResult = result;
        state.resultsStale = false;
        $('resultsStaleNote').hidden = true;
        $('resultsSection').hidden = false;
        $('journeyCompleteSection').hidden = true;
        $('saveConfirm').hidden = true;
        $('savedReviewSection').hidden = true;
        $('survivor-results-title').focus();
    }

    function validateAnswers() {
        var q1 = selectedRadio('assetRecipientReview');
        var q2 = selectedRadio('survivorIncomePreparedness');
        var msg = $('questionValidation');
        if (!q1 || !q2) {
            msg.textContent = 'Choose an answer for both questions before reviewing your survivor picture.';
            msg.hidden = false;
            if (!q1) {
                $('assetRecipientFieldset').scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                $('survivorPreparednessFieldset').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return null;
        }
        msg.hidden = true;
        return { assetRecipientReview: q1, survivorIncomePreparedness: q2 };
    }

    function runReview() {
        if (!state.phase3Ready) return;
        var answers = validateAnswers();
        if (!answers) return;
        var result = engine.runSurvivorPicture(
            state.phase3,
            answers.assetRecipientReview,
            answers.survivorIncomePreparedness
        );
        state.answersAtLastRun = {
            assetRecipientReview: answers.assetRecipientReview,
            survivorIncomePreparedness: answers.survivorIncomePreparedness
        };
        showResults(result);
    }

    function buildSavePayload() {
        var result = state.lastResult;
        var phase3 = state.phase3;
        if (!result || !phase3 || state.resultsStale) return null;
        if (!state.selectedPriorityId) return null;

        return {
            saved: true,
            decisionStatement: 'This is the survivor-planning priority I want to carry forward for our household plan.',
            companionExplanation: 'I’ve reviewed how our retirement income plan may change if one of us dies. I’m carrying forward one priority to revisit—not a finished estate plan.',
            phase3Snapshot: snapshotPhase3(phase3),
            phase4Context: state.phase4Context,
            phase5Context: state.phase5Context,
            assumptions: {
                assetRecipientReview: state.answersAtLastRun.assetRecipientReview,
                survivorIncomePreparedness: state.answersAtLastRun.survivorIncomePreparedness
            },
            result: {
                pressureMode: result.pressureMode,
                mainIssueIds: result.mainIssueIds.slice(),
                issueTitles: result.issueTitles.slice(),
                issueExplanations: result.issueExplanations.slice(),
                guidanceText: result.guidanceText,
                strategyChoicesShown: state.strategyChoicesShown.slice()
            },
            mainIssueIds: result.mainIssueIds.slice(),
            nextPriorityId: state.selectedPriorityId,
            nextPriorityLabel: state.selectedPriorityLabel,
            educationalNonAdvice: true,
            notAnEstatePlan: true
        };
    }

    function renderJourneyCompleteRecap(record) {
        var phase3 = state.phase3;
        var items = [
            'Retirement spending goal: ' + money(phase3.monthlyRetirementSpendingGoal) + ' / mo',
            'Social Security assumption: ' + money(phase3.monthlySocialSecurityAssumption) + ' / mo',
            'Retirement income plan: ' + (assessmentLabels[phase3.baseCaseAssessment] || phase3.baseCaseAssessment || 'Saved'),
            'Resilience review: ' +
                ((state.phase4Context && state.phase4Context.overallResilienceLabel) ||
                    (record.phase4Context && record.phase4Context.overallResilienceLabel) ||
                    'Available when Phase 4 is current'),
            'Tax-planning priority: ' +
                ((state.phase5Context && state.phase5Context.nextPriorityLabel) ||
                    (record.phase5Context && record.phase5Context.nextPriorityLabel) ||
                    'Available when Phase 5 is current'),
            'Survivor-planning priority: ' + (record.nextPriorityLabel || 'Saved')
        ];
        var list = $('journeyCompleteRecap');
        list.innerHTML = '';
        items.forEach(function (text) {
            var li = document.createElement('li');
            li.textContent = text;
            list.appendChild(li);
        });
    }

    function savePriority() {
        var payload = buildSavePayload();
        if (!payload) return;
        var progress = readProgress();
        var oldRecord = progress.records && progress.records[recordKey]
            ? recordTools.normalizeSurvivorPlanningRecord(progress.records[recordKey], false)
            : {};
        var record = recordTools.createSurvivorPlanningRecord(payload, {
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
        $('saveConfirm').hidden = false;
        renderJourneyCompleteRecap(record);
        $('journeyCompleteSection').hidden = false;
        renderSavedSummary(record);
        $('savedReviewSection').hidden = false;
        $('saveConfirm').focus();
    }

    function renderSavedSummary(record) {
        var el = $('savedReviewSummary');
        var result = record.result || {};
        var titles = result.issueTitles || [];
        var explanations = result.issueExplanations || [];
        var mode = result.pressureMode || 'single';
        var html = '<p class="eyebrow">' +
            (mode === 'tied' ? 'Main survivor-planning priorities' : 'Main survivor-planning priority') +
            '</p>';
        titles.forEach(function (title, index) {
            html += '<p><strong>' + title + '</strong></p>';
            html += '<p class="supporting-note">' + (explanations[index] || '') + '</p>';
        });
        html += '<p>' + (record.decisionStatement || '') + '</p>';
        html += '<p class="supporting-note">' + (record.companionExplanation || '');
        if (record.nextPriorityLabel) {
            html += ' Priority carried forward: ' + record.nextPriorityLabel + '.';
        }
        html += '</p>';
        el.innerHTML = html;
    }

    function init() {
        if (!recordTools || !engine || !priorities) return;

        var progress = readProgress();
        var phase3 = loadPhase3(progress);
        state.phase3 = phase3;
        state.phase3Ready = phase3IsReady(phase3);

        var saved = progress.records && progress.records[recordKey]
            ? recordTools.normalizeSurvivorPlanningRecord(progress.records[recordKey], false)
            : null;
        state.savedRecord = saved && saved.saved ? saved : null;

        if (!state.phase3Ready) {
            $('phase3IncompleteBanner').hidden = false;
            return;
        }

        $('phase3IncompleteBanner').hidden = true;
        $('phase3RecapSection').hidden = false;
        $('teachingSection').hidden = false;
        $('questionsSection').hidden = false;
        renderRecap(phase3);

        state.phase4Context = loadPhase4Context(progress, phase3);
        state.phase5Context = loadPhase5Context(progress, phase3);
        renderPriorContext();

        if (state.savedRecord && state.savedRecord.phase3Snapshot) {
            if (phase3ChangedSinceSnapshot(phase3, state.savedRecord.phase3Snapshot)) {
                $('phase3ChangedBanner').hidden = false;
            }
            if (state.savedRecord.assumptions) {
                setRadio('assetRecipientReview', state.savedRecord.assumptions.assetRecipientReview);
                setRadio('survivorIncomePreparedness', state.savedRecord.assumptions.survivorIncomePreparedness);
            }
            renderSavedSummary(state.savedRecord);
            renderJourneyCompleteRecap(state.savedRecord);
            $('savedReviewSection').hidden = false;
            $('journeyCompleteSection').hidden = false;
        }

        $('reviewSurvivorPictureBtn').addEventListener('click', runReview);
        $('savePriorityBtn').addEventListener('click', savePriority);

        document.querySelectorAll(
            'input[name="assetRecipientReview"], input[name="survivorIncomePreparedness"]'
        ).forEach(function (input) {
            input.addEventListener('change', markResultsStaleIfNeeded);
        });

        var revisit = $('revisitBtn');
        if (revisit) {
            revisit.addEventListener('click', function () {
                $('savedReviewSection').hidden = true;
                $('journeyCompleteSection').hidden = true;
                $('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                runReview();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
