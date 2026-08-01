/**
 * Phase 5 Tax Strategy page controller (review build).
 * Requires a complete saved Phase 3 plan. Does not auto-run on load.
 * Does not calculate federal tax.
 */
(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var recordKey = 'tax-strategy';
    var phase3Key = 'build-your-plan';
    var phase4Key = 'stress-test';
    var recordTools = window.rbJourneyRecords;
    var engine = window.Phase5TaxEngine;
    var priorities = window.Phase5Priorities;

    var state = {
        phase3: null,
        phase3Ready: false,
        phase4Context: null,
        lastResult: null,
        selectedPriorityId: null,
        selectedPriorityLabel: null,
        strategyChoicesShown: [],
        answersAtLastRun: { savingsMix: '', rmdTiming: '' },
        resultsStale: false,
        savedRecord: null
    };

    var assessmentLabels = {
        workable: 'Looks workable on these assumptions',
        close: 'Looks close and may need adjustment',
        difficult: 'Looks difficult on these assumptions'
    };

    // Visitor-facing presentation copy only — does not affect issue selection.
    var ISSUE_COPY = {
        tax_deferred_pressure: {
            title: 'Your plan may rely heavily on tax-deferred withdrawals.',
            body: 'Much of what you take from Traditional IRAs or 401(k)s may count as taxable income, so the gross Phase 3 picture may overstate spendable cash.'
        },
        gross_vs_spendable: {
            title: 'Taxes may require larger withdrawals than your spending goal alone suggests.',
            body: 'If withdrawals are taxable, you may need to withdraw more than your spending goal in order to have enough after taxes.'
        },
        rmd_attention: {
            title: 'Required minimum distributions deserve attention.',
            body: 'Required withdrawals can increase taxable income even if your spending needs don’t change.'
        },
        roth_review: {
            title: 'Roth planning may be worth a closer look.',
            body: 'When tax-deferred savings do much of the work as required withdrawals approach, reviewing Roth options later may be useful. This is not a recommendation to convert.'
        },
        ss_income_interaction: {
            title: 'Social Security and other income may interact with withdrawals.',
            body: 'Looking at those pieces together may help before you rely on the withdrawal plan.'
        },
        account_mix_unclear: {
            title: 'Confirm how your retirement savings are divided among account types.',
            body: 'Traditional, Roth, and taxable accounts can affect taxable income differently. Confirming your mix would make future tax-planning decisions more useful.'
        },
        none_dominant: {
            title: 'No single tax issue stands out strongly from these answers.',
            body: 'An annual review may be enough for now. Revisit this when balances, Social Security, or tax rules change.'
        }
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

    function renderPhase4Context(ctx) {
        var section = $('phase4ContextSection');
        if (!ctx) {
            section.hidden = true;
            return;
        }
        $('phase4ResilienceLine').textContent = ctx.overallResilienceLabel
            ? ('Overall resilience: ' + ctx.overallResilienceLabel)
            : '';
        $('phase4PressureLine').textContent = ctx.pressureSentence || '';
        var adj = $('phase4AdjustmentLine');
        if (ctx.nextAdjustmentLabel) {
            adj.textContent = 'Selected Phase 4 direction: ' + ctx.nextAdjustmentLabel;
            adj.hidden = false;
        } else {
            adj.hidden = true;
        }
        section.hidden = false;
    }

    function markResultsStaleIfNeeded() {
        if (!state.lastResult) return;
        var mix = selectedRadio('savingsMix');
        var rmd = selectedRadio('rmdTiming');
        if (mix !== state.answersAtLastRun.savingsMix || rmd !== state.answersAtLastRun.rmdTiming) {
            state.resultsStale = true;
            $('resultsStaleNote').hidden = false;
            $('savePriorityBtn').disabled = true;
        }
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
            input.name = 'phase5-priority';
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

    function issuePresentation(id, result) {
        var copy = ISSUE_COPY[id] || ISSUE_COPY.none_dominant;
        var title = copy.title;
        var body = copy.body;
        // Prefer the timing-specific RMD note as the priority title when RMD is featured.
        if (id === 'rmd_attention' && result && result.rmdNote && result.rmdNote.text) {
            title = result.rmdNote.text;
            body = ISSUE_COPY.rmd_attention.body;
        }
        return { id: id, title: title, body: body };
    }

    function renderMainIssues(result) {
        var host = $('mainIssueList');
        var eyebrow = $('mainIssueEyebrow');
        var live = $('mainIssueLive');
        host.innerHTML = '';

        var mode = result.pressureMode;
        var ids = result.mainIssueIds || [];
        var items = [];

        if (mode === 'none' || (ids.length === 1 && ids[0] === 'none_dominant')) {
            eyebrow.textContent = 'Main tax-planning priority';
            items = [issuePresentation('none_dominant', result)];
        } else if (mode === 'tied' && ids.length >= 2) {
            eyebrow.textContent = 'Main tax-planning priorities';
            items = [
                issuePresentation(ids[0], result),
                issuePresentation(ids[1], result)
            ];
        } else {
            eyebrow.textContent = 'Main tax-planning priority';
            items = [issuePresentation(ids[0] || 'none_dominant', result)];
        }

        items.forEach(function (item) {
            var title = document.createElement('p');
            title.innerHTML = '<strong>' + item.title + '</strong>';
            var body = document.createElement('p');
            body.className = 'supporting-note';
            body.textContent = item.body;
            host.appendChild(title);
            host.appendChild(body);
        });

        live.textContent = eyebrow.textContent + '. ' +
            items.map(function (item) { return item.title + ' ' + item.body; }).join(' ');

        // Keep a readable summary on the result object for save/reload display.
        result.displayMainIssueSummary = items.map(function (item) { return item.title; }).join(' ');
        result.displayWhatThisMeans = items.map(function (item) { return item.body; }).join(' ');
        return items;
    }

    function showResults(result) {
        renderMainIssues(result);
        $('taxDragGuidance').textContent = result.taxDragGuidance.text;
        $('rmdNote').textContent = result.rmdNote.text;
        // When RMD is already shown as a main priority title, avoid repeating the same sentence below.
        var rmdAsPriority = (result.mainIssueIds || []).indexOf('rmd_attention') !== -1;
        $('rmdNote').hidden = rmdAsPriority;
        var roth = $('rothSignal');
        if (result.rothReviewFlag && result.rothReviewText) {
            roth.textContent = result.rothReviewText;
            roth.hidden = false;
        } else {
            roth.hidden = true;
        }
        renderPriorities(result);
        state.lastResult = result;
        state.resultsStale = false;
        $('resultsStaleNote').hidden = true;
        $('resultsSection').hidden = false;
        $('phase6Handoff').hidden = true;
        $('saveConfirm').hidden = true;
        $('savedReviewSection').hidden = true;
        var heading = $('tax-results-title');
        heading.focus();
    }

    function validateAnswers() {
        var mix = selectedRadio('savingsMix');
        var rmd = selectedRadio('rmdTiming');
        var msg = $('questionValidation');
        if (!mix || !rmd) {
            msg.textContent = 'Choose an answer for both questions before reviewing your tax picture.';
            msg.hidden = false;
            if (!mix) {
                $('savingsMixFieldset').scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                $('rmdTimingFieldset').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return null;
        }
        msg.hidden = true;
        return { savingsMix: mix, rmdTiming: rmd };
    }

    function runReview() {
        if (!state.phase3Ready) return;
        var answers = validateAnswers();
        if (!answers) return;
        var result = engine.runTaxPicture(state.phase3, answers.savingsMix, answers.rmdTiming);
        state.answersAtLastRun = {
            savingsMix: answers.savingsMix,
            rmdTiming: answers.rmdTiming
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
            decisionStatement: 'This is the tax-planning priority I want to keep in mind before I rely on my withdrawal plan.',
            companionExplanation: 'I’ve reviewed how taxes may affect my Phase 3 plan. I’m keeping one priority to revisit—not a finished tax strategy.',
            phase3Snapshot: snapshotPhase3(phase3),
            phase4Context: state.phase4Context,
            assumptions: {
                savingsMix: state.answersAtLastRun.savingsMix,
                rmdTiming: state.answersAtLastRun.rmdTiming
            },
            result: {
                pressureMode: result.pressureMode,
                mainIssueIds: result.mainIssueIds.slice(),
                mainIssueStatement: result.displayMainIssueSummary || result.mainIssueStatement,
                whatThisMeans: result.displayWhatThisMeans || result.whatThisMeans,
                taxDragGuidance: result.taxDragGuidance,
                rmdNote: result.rmdNote,
                rothReviewFlag: result.rothReviewFlag === true,
                rothReviewText: result.rothReviewText,
                traditionalHeavy: result.traditionalHeavy === true,
                strategyChoicesShown: state.strategyChoicesShown.slice()
            },
            mainIssueIds: result.mainIssueIds.slice(),
            nextPriorityId: state.selectedPriorityId,
            nextPriorityLabel: state.selectedPriorityLabel,
            educationalNonAdvice: true,
            notAFinishedTaxStrategy: true
        };
    }

    function savePriority() {
        var payload = buildSavePayload();
        if (!payload) return;
        var progress = readProgress();
        var oldRecord = progress.records && progress.records[recordKey]
            ? recordTools.normalizeTaxStrategyRecord(progress.records[recordKey], progress[recordKey] === true)
            : {};
        var record = recordTools.createTaxStrategyRecord(payload, {
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
        renderSavedSummary(record);
        setSavedTaxUi(true);
        $('saveConfirm').focus();
        if (window.rbJourneyAnalytics && typeof window.rbJourneyAnalytics.trackPhaseComplete === 'function') {
            window.rbJourneyAnalytics.trackPhaseComplete(5);
        }
    }

    function renderSavedSummary(record) {
        var el = $('savedReviewSummary');
        var result = record.result || {};
        var ids = result.mainIssueIds || record.mainIssueIds || [];
        var issueHtml = '';
        if (ids.length && !(ids.length === 1 && ids[0] === 'none_dominant')) {
            var mode = result.pressureMode || (ids.length > 1 ? 'tied' : 'single');
            var heading = mode === 'tied' ? 'Main tax-planning priorities' : 'Main tax-planning priority';
            issueHtml = '<p class="eyebrow">' + heading + '</p>';
            ids.slice(0, 2).forEach(function (id) {
                var item = issuePresentation(id, result);
                issueHtml += '<p><strong>' + item.title + '</strong></p><p>' + item.body + '</p>';
            });
        } else {
            var noneItem = issuePresentation('none_dominant', result);
            issueHtml = '<p class="eyebrow">Main tax-planning priority</p>' +
                '<p><strong>' + (result.mainIssueStatement || noneItem.title) + '</strong></p>' +
                '<p>' + (result.whatThisMeans || noneItem.body) + '</p>';
        }
        var priority = record.nextPriorityLabel
            ? ('<p><strong>Priority to revisit:</strong> ' + record.nextPriorityLabel + '</p>')
            : '';
        el.innerHTML = issueHtml +
            '<p>' + (record.decisionStatement || '') + '</p>' +
            '<p class="supporting-note">' + (record.companionExplanation || '') + '</p>' +
            priority;
    }

    function setSavedTaxUi(hasSaved) {
        var reviewBtn = $('reviewTaxPictureBtn');
        var lede = $('phase5PageLede');
        if (reviewBtn) {
            reviewBtn.hidden = !!hasSaved;
        }
        if (lede) {
            lede.textContent = hasSaved
                ? 'Review your saved tax picture, update it if your plan changed, or continue to Phase 6 when you are ready.'
                : 'How do taxes affect the retirement plan you have built, and what should you consider next?';
        }
        if (hasSaved) {
            $('savedReviewSection').hidden = false;
            $('phase6Handoff').hidden = false;
        }
    }

    function init() {
        if (!recordTools || !engine || !priorities) {
            return;
        }

        var progress = readProgress();
        var phase3 = loadPhase3(progress);
        state.phase3 = phase3;
        state.phase3Ready = phase3IsReady(phase3);

        var saved = progress.records && progress.records[recordKey]
            ? recordTools.normalizeTaxStrategyRecord(progress.records[recordKey], false)
            : null;
        state.savedRecord = saved && saved.saved ? saved : null;

        if (!state.phase3Ready) {
            $('phase3IncompleteBanner').hidden = false;
            $('phase3RecapSection').hidden = true;
            $('phase4ContextSection').hidden = true;
            $('taxCharacterSection').hidden = true;
            $('questionsSection').hidden = true;
            return;
        }

        $('phase3IncompleteBanner').hidden = true;
        $('phase3RecapSection').hidden = false;
        $('taxCharacterSection').hidden = false;
        $('questionsSection').hidden = false;
        renderRecap(phase3);

        state.phase4Context = loadPhase4Context(progress, phase3);
        renderPhase4Context(state.phase4Context);

        if (state.savedRecord && state.savedRecord.phase3Snapshot) {
            if (phase3ChangedSinceSnapshot(phase3, state.savedRecord.phase3Snapshot)) {
                $('phase3ChangedBanner').hidden = false;
            }
            if (state.savedRecord.assumptions) {
                setRadio('savingsMix', state.savedRecord.assumptions.savingsMix);
                setRadio('rmdTiming', state.savedRecord.assumptions.rmdTiming);
            }
            renderSavedSummary(state.savedRecord);
            setSavedTaxUi(true);
        }

        $('reviewTaxPictureBtn').addEventListener('click', runReview);
        $('savePriorityBtn').addEventListener('click', savePriority);

        document.querySelectorAll('input[name="savingsMix"], input[name="rmdTiming"]').forEach(function (input) {
            input.addEventListener('change', markResultsStaleIfNeeded);
        });

        var revisit = $('revisitBtn');
        if (revisit) {
            revisit.addEventListener('click', function () {
                $('savedReviewSection').hidden = true;
                var reviewBtn = $('reviewTaxPictureBtn');
                if (reviewBtn) reviewBtn.hidden = true;
                $('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                runReview();
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
