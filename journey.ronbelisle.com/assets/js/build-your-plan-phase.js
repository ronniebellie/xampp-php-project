(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var calculatorKey = 'rbJourneyCalculator:retirementSpendingPlan:v1';
    var recordKey = 'build-your-plan';
    var form = document.getElementById('phase3RecordForm');
    var recordTools = window.rbJourneyRecords;
    if (!form || !recordTools) return;

    var state = {
        phase1Usable: false,
        monthlySpending: 0,
        monthlyOther: 0,
        phase2SsUsable: false,
        phase2SsMonthly: null,
        phase2SsReason: 'missing',
        useTemporarySs: false,
        temporarySsMonthly: null,
        savingsBalance: null
    };

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

    function canCloudWrite() {
        var sync = window.rbJourneySync;
        if (!sync) return false;
        var status = typeof sync.getStatus === 'function' ? sync.getStatus() : null;
        var syncState = typeof sync.getState === 'function' ? sync.getState() : null;
        if (syncState && syncState.canWrite === true && syncState.readOnly !== true) {
            return true;
        }
        return !!(status && status.hasAccess && status.canCloudWrite !== false);
    }

    function persistCloudNow(reason) {
        if (!canCloudWrite() || !window.rbJourneySync || typeof window.rbJourneySync.saveNow !== 'function') {
            return Promise.resolve({ localOnly: true });
        }
        return window.rbJourneySync.saveNow(reason || 'phase').then(function (result) {
            return result && typeof result === 'object' ? result : {};
        }).catch(function () {
            return { error: true };
        });
    }

    function setSaveConfirmationMessage(cloudResult) {
        var confirmation = document.getElementById('phase3SaveConfirmation');
        if (!confirmation) return;
        var strong = confirmation.querySelector('strong');
        var span = confirmation.querySelector('span');
        var cloudOk = canCloudWrite() &&
            cloudResult &&
            !cloudResult.localOnly &&
            !cloudResult.error &&
            !cloudResult.skipped &&
            !cloudResult.offline;

        if (strong) {
            strong.textContent = cloudOk
                ? 'Your retirement income plan has been saved to your Journey account.'
                : 'Your retirement income plan has been saved in this browser.';
        }
        if (span) {
            if (cloudOk) {
                span.textContent = 'You can return on this browser or another device and continue from where you left off.';
            } else if (canCloudWrite() && cloudResult && (cloudResult.offline || cloudResult.error)) {
                span.textContent = 'Cloud save will retry when your connection is available.';
            } else {
                span.textContent = 'This is a working base-case plan you can revisit and change later.';
            }
        }
    }

    function numberOrNull(value) {
        if (value === '' || value === null || value === undefined) return null;
        var number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function currency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function readPhase1() {
        if (window.rbJourneyPhase1 && typeof window.rbJourneyPhase1.reconcileLocal === 'function') {
            window.rbJourneyPhase1.reconcileLocal();
        }
        if (window.rbJourneySync && typeof window.rbJourneySync.getPhase1Handoff === 'function') {
            var synced = window.rbJourneySync.getPhase1Handoff();
            if (synced && synced.usable) {
                return {
                    usable: true,
                    monthlySpending: synced.monthlySpending,
                    monthlyOther: Math.max(0, Number(synced.monthlyOther) || 0)
                };
            }
        }
        if (window.rbJourneyPhase1 && typeof window.rbJourneyPhase1.getHandoff === 'function') {
            var handoff = window.rbJourneyPhase1.getHandoff();
            if (handoff && handoff.usable) {
                return {
                    usable: true,
                    monthlySpending: handoff.monthlySpending,
                    monthlyOther: Math.max(0, Number(handoff.monthlyOther) || 0)
                };
            }
        }

        try {
            var calc = JSON.parse(localStorage.getItem(calculatorKey) || '{}');
            if (calc && calc.completionStatus === 'completed' && calc.outputs) {
                var monthly = Number(calc.outputs.monthlyRetirementSpendingTarget);
                if (monthly > 0) {
                    return {
                        usable: true,
                        monthlySpending: monthly,
                        monthlyOther: Math.max(0, Number(calc.outputs.monthlyOtherRegularRetirementIncome) || 0)
                    };
                }
            }
        } catch (error) {
            // fall through
        }

        var progress = readProgress();
        var record = progress.records && progress.records['spending-goals'];
        var data = record && record.result && record.result.dataForLaterPhases;
        if (data && Number(data.monthlyRetirementSpendingTarget) > 0) {
            return {
                usable: true,
                monthlySpending: Number(data.monthlyRetirementSpendingTarget),
                monthlyOther: Math.max(0, Number(data.monthlyOtherRegularRetirementIncome) || 0)
            };
        }

        return { usable: false, monthlySpending: 0, monthlyOther: 0 };
    }

    function readPhase2SocialSecurity() {
        var progress = readProgress();
        var raw = progress.records && progress.records['social-security'];
        if (!raw || typeof raw !== 'object') {
            return { usable: false, monthly: null, decisionStatus: '', claimAge: null, reason: 'missing' };
        }

        var record = recordTools.normalizeSocialSecurityRecord(raw, progress['social-security'] === true);
        var source = null;

        if (record.saved === true && record.lastSavedPlanning && typeof record.lastSavedPlanning === 'object') {
            // Prefer the last successful Save My Claiming Choice snapshot so drafts cannot leak.
            source = record.lastSavedPlanning;
        } else if (record.saved === true && record.hasUnsavedChanges !== true) {
            source = record;
        } else if (record.saved !== true) {
            return {
                usable: false,
                monthly: null,
                decisionStatus: record.decisionStatus || '',
                claimAge: record.claimAge === undefined ? null : record.claimAge,
                reason: 'not-saved'
            };
        } else {
            return {
                usable: false,
                monthly: null,
                decisionStatus: record.decisionStatus || '',
                claimAge: record.claimAge === undefined ? null : record.claimAge,
                reason: 'unsaved-changes'
            };
        }

        var status = source.decisionStatus || '';
        var monthly = null;

        if (status === 'need-more-information') {
            return {
                usable: false,
                monthly: null,
                decisionStatus: status,
                claimAge: null,
                reason: 'not-ready'
            };
        }

        // Obsolete Journey path: do not reuse a legacy current-benefit amount as a claiming-age benefit.
        if (status === 'already-receiving') {
            return {
                usable: false,
                monthly: null,
                decisionStatus: status,
                claimAge: null,
                reason: 'needs-review'
            };
        }

        if (status === 'provisional') {
            monthly = Number(source.estimatedMonthlyBenefit);
            // FRA-only safety net for older records: never use FRA amount for a different claim age.
            if (
                !(monthly > 0) &&
                Number(source.benefitAtFra) > 0 &&
                Number(source.claimAge) &&
                Number(source.estimatedMonthlyBenefit) === Number(source.benefitAtFra)
            ) {
                monthly = Number(source.benefitAtFra);
            }
        } else {
            return {
                usable: false,
                monthly: null,
                decisionStatus: status,
                claimAge: source.claimAge === undefined ? null : source.claimAge,
                reason: 'not-saved'
            };
        }

        return {
            usable: monthly > 0,
            monthly: monthly > 0 ? monthly : null,
            decisionStatus: status,
            claimAge: source.claimAge === undefined ? null : source.claimAge,
            reason: monthly > 0 ? 'ok' : 'missing-amount'
        };
    }

    function temporarySsExplanation(reason) {
        if (reason === 'not-ready') {
            return 'In Phase 2 you indicated you are not ready to select a claiming age. Return to Phase 2 when you are ready, or enter a temporary estimate so you can preview this income plan.';
        }
        if (reason === 'needs-review') {
            return 'Your Phase 2 Social Security record needs an update. Return to Phase 2 to choose a claiming age to test, or enter a temporary estimate so you can preview this income plan.';
        }
        if (reason === 'not-saved' || reason === 'unsaved-changes' || reason === 'missing') {
            return 'Phase 2 has not been completed and saved yet. Return to Phase 2 to save your claiming choice, or enter a temporary estimate so you can preview this income plan.';
        }
        if (reason === 'missing-amount') {
            return 'Phase 2 does not have a usable monthly Social Security planning amount yet. Return to Phase 2 to add one, or enter a temporary estimate so you can preview this income plan.';
        }
        return 'Your Social Security planning amount is not complete yet. Return to Phase 2 to finish it, or enter a temporary estimate so you can preview this income plan.';
    }

    function existingRecord(progress) {
        if (!progress.records || typeof progress.records !== 'object') return {};
        var record = progress.records[recordKey];
        return record && typeof record === 'object'
            ? recordTools.normalizeBuildYourPlanRecord(record, progress[recordKey] === true)
            : {};
    }

    function effectiveSocialSecurityMonthly() {
        if (state.phase2SsUsable) return state.phase2SsMonthly;
        if (state.useTemporarySs && state.temporarySsMonthly !== null && state.temporarySsMonthly >= 0) {
            return state.temporarySsMonthly;
        }
        return null;
    }

    function incomePictureComplete() {
        return state.phase1Usable && effectiveSocialSecurityMonthly() !== null;
    }

    function monthlyNeededFromSavings() {
        if (!incomePictureComplete()) return null;
        return Math.max(0, state.monthlySpending - effectiveSocialSecurityMonthly() - state.monthlyOther);
    }

    function assessBaseCase(annualNeed, balance) {
        if (annualNeed === 0) {
            return {
                code: 'workable',
                label: 'Looks workable on these assumptions',
                detail: 'On these assumptions, no retirement-savings withdrawal is currently needed to meet your entered spending goal. Dependable income covers the spending target.',
                ratePct: 0,
                rateDisplay: null
            };
        }

        if (!(balance > 0)) {
            return {
                code: 'difficult',
                label: 'Looks difficult on these assumptions',
                detail: 'Your plan currently needs withdrawals from retirement savings, but the entered retirement-savings balance is $0. Enter a savings balance greater than zero to estimate how demanding those withdrawals may be.',
                ratePct: null,
                rateDisplay: null
            };
        }

        var ratePct = (annualNeed / balance) * 100;
        var code;
        var label;
        var detail;

        if (ratePct <= 4) {
            code = 'workable';
            label = 'Looks workable on these assumptions';
            detail = 'Based on your spending goal, dependable income, and retirement savings, the initial withdrawal demand looks workable as an educational base-case check.';
        } else if (ratePct <= 5) {
            code = 'close';
            label = 'May need adjustment';
            detail = 'Based on your spending goal, dependable income, and retirement savings, the initial withdrawal demand may need adjustment.';
        } else {
            code = 'difficult';
            label = 'Looks difficult on these assumptions';
            detail = 'Based on your spending goal, dependable income, and retirement savings, the initial withdrawal demand looks difficult under these assumptions.';
        }

        return {
            code: code,
            label: label,
            detail: detail,
            ratePct: ratePct,
            rateDisplay: ratePct
        };
    }

    function buildRecord(saved) {
        var ssMonthly = effectiveSocialSecurityMonthly();
        var monthlyNeed = monthlyNeededFromSavings();
        var annualNeed = monthlyNeed === null ? null : monthlyNeed * 12;
        var balance = state.savingsBalance;
        var assessment = null;
        var assessmentStatus = 'incomplete';

        if (!state.phase1Usable) {
            assessmentStatus = 'incomplete-phase1';
        } else if (ssMonthly === null) {
            assessmentStatus = 'incomplete-social-security';
        } else if (balance === null || balance < 0) {
            assessmentStatus = 'incomplete-savings';
        } else {
            assessment = assessBaseCase(annualNeed, balance);
            assessmentStatus = 'complete';
        }

        return {
            monthlyRetirementSpendingGoal: state.phase1Usable ? state.monthlySpending : null,
            annualRetirementSpendingGoal: state.phase1Usable ? state.monthlySpending * 12 : null,
            monthlySocialSecurityAssumption: ssMonthly,
            socialSecuritySource: state.phase2SsUsable ? 'phase2' : (state.useTemporarySs ? 'temporary-estimate' : ''),
            temporarySocialSecurityEstimateUsed: !state.phase2SsUsable && state.useTemporarySs === true,
            monthlyOtherDependableIncome: state.phase1Usable ? state.monthlyOther : null,
            monthlyNeededFromRetirementSavings: monthlyNeed,
            annualNeededFromRetirementSavings: annualNeed,
            retirementSavingsBalance: balance,
            impliedInitialWithdrawalRate: assessment && assessment.ratePct !== null ? assessment.ratePct / 100 : null,
            baseCaseAssessment: assessment ? assessment.code : '',
            assessmentStatus: assessmentStatus,
            planningRecordStatus: assessment ? assessment.code : 'needs-information',
            baseCaseOnly: true,
            saved: saved === true
        };
    }

    function persistDraft(saved) {
        var progress = readProgress();
        var oldRecord = existingRecord(progress);
        var record = buildRecord(saved === true);
        record.saved = saved === true || oldRecord.saved === true;
        record.hasUnsavedChanges = saved === false
            ? true
            : (saved === true ? false : oldRecord.hasUnsavedChanges === true);
        if (oldRecord.completedAt) record.completedAt = oldRecord.completedAt;
        record = recordTools.createBuildYourPlanRecord(record, {
            oldRecord: oldRecord,
            journeyComplete: progress[recordKey] === true,
            reviewed: saved === true
        });
        progress.records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        progress.records[recordKey] = record;
        writeProgress(progress);
        return record;
    }

    function showErrors(errors) {
        var summary = document.getElementById('phase3ErrorSummary');
        var list = summary.querySelector('ul');
        list.innerHTML = '';
        errors.forEach(function (error) {
            var item = document.createElement('li');
            item.textContent = error;
            list.appendChild(item);
        });
        summary.hidden = errors.length === 0;
        if (errors.length) summary.focus();
    }

    function validateForSave(record) {
        var errors = [];
        if (!state.phase1Usable) {
            errors.push('Phase 3 requires a retirement spending target from Phase 1.');
        }
        if (effectiveSocialSecurityMonthly() === null) {
            errors.push('Enter a temporary Social Security estimate or finish Phase 2 before saving a completed plan.');
        }
        if (record.retirementSavingsBalance === null || record.retirementSavingsBalance < 0) {
            errors.push('Enter how much you have saved for retirement. Use zero only if you currently have no retirement savings.');
        }
        if (record.assessmentStatus !== 'complete') {
            errors.push('Complete the income picture before saving your retirement income plan.');
        }
        return errors;
    }

    function updateBridgeCopy() {
        var bridge = document.getElementById('phase3BridgeCopy');
        if (!state.phase1Usable) {
            bridge.textContent = 'Phase 3 connects your spending goal, dependable income, Social Security assumption, and retirement savings. A Phase 1 spending target is required first.';
            return;
        }

        var ssText = state.phase2SsUsable
            ? ('In Phase 2, you assumed about ' + currency(state.phase2SsMonthly) + ' per month from Social Security.')
            : (state.useTemporarySs && state.temporarySsMonthly !== null
                ? ('You are using a temporary Social Security estimate of about ' + currency(state.temporarySsMonthly) + ' per month.')
                : 'Your Social Security planning amount is not complete yet.');

        var need = monthlyNeededFromSavings();
        var needText = need === null
            ? 'The amount needed from retirement savings will appear once Social Security is available.'
            : ('That leaves about ' + currency(need) + ' per month for your retirement savings to provide.');

        bridge.innerHTML =
            'In Phase 1, you estimated spending about <strong>' + currency(state.monthlySpending) + '</strong> per month. ' +
            ssText + ' ' +
            'You also expect about <strong>' + currency(state.monthlyOther) + '</strong> per month from pensions, annuities, or rental income. ' +
            needText;
    }

    function renderKnownAmounts() {
        var panel = document.getElementById('knownAmounts');
        var incomplete = document.getElementById('phase1IncompleteBanner');
        var ssNote = document.getElementById('ssAssumptionNote');
        var tempSection = document.getElementById('temporarySsSection');
        var tempExplain = document.getElementById('temporarySsExplain');
        var savingsSection = document.getElementById('savingsQuestionSection');

        incomplete.hidden = state.phase1Usable;
        panel.hidden = !state.phase1Usable;
        ssNote.hidden = !state.phase1Usable || !state.phase2SsUsable;
        tempSection.hidden = !state.phase1Usable || state.phase2SsUsable;
        if (tempExplain && !state.phase2SsUsable) {
            tempExplain.textContent = temporarySsExplanation(state.phase2SsReason);
        }
        savingsSection.hidden = !state.phase1Usable;

        if (!state.phase1Usable) {
            document.getElementById('incomePictureSection').hidden = true;
            return;
        }

        document.getElementById('knownSpending').textContent = currency(state.monthlySpending);
        document.getElementById('knownOtherIncome').textContent = currency(state.monthlyOther);

        var ssMonthly = effectiveSocialSecurityMonthly();
        document.getElementById('knownSocialSecurity').textContent = ssMonthly === null ? 'Not set' : currency(ssMonthly);

        var need = monthlyNeededFromSavings();
        document.getElementById('knownSavingsNeed').textContent = need === null ? 'Pending' : currency(need);
    }

    function renderIncomePicture() {
        var section = document.getElementById('incomePictureSection');
        var balanceInput = document.getElementById('retirementSavingsBalance');
        var balance = numberOrNull(balanceInput.value);
        if (balance !== null && balance < 0) balance = null;
        state.savingsBalance = balance;

        if (!state.phase1Usable || balance === null) {
            section.hidden = true;
            return;
        }

        section.hidden = false;
        var ssMonthly = effectiveSocialSecurityMonthly();
        var other = state.monthlyOther;
        var dependable = ssMonthly === null ? null : ssMonthly + other;
        var monthlyNeed = monthlyNeededFromSavings();
        var annualNeed = monthlyNeed === null ? null : monthlyNeed * 12;
        var temporary = !state.phase2SsUsable && state.useTemporarySs;

        document.getElementById('temporaryEstimateBadge').hidden = !temporary;
        document.getElementById('pictureSpending').textContent = currency(state.monthlySpending);
        document.getElementById('pictureDependable').textContent = dependable === null ? 'Pending' : currency(dependable);
        document.getElementById('pictureSs').textContent = ssMonthly === null ? 'Not set' : currency(ssMonthly);
        document.getElementById('pictureOther').textContent = currency(other);
        document.getElementById('pictureMonthlyNeed').textContent = monthlyNeed === null ? 'Pending' : currency(monthlyNeed);
        document.getElementById('pictureAnnualNeed').textContent = annualNeed === null ? 'Pending' : currency(annualNeed);

        var assessmentCard = document.getElementById('assessmentCard');
        var blockedCard = document.getElementById('assessmentBlockedCard');
        var saveButton = document.getElementById('savePhase3Button');

        if (!incomePictureComplete()) {
            assessmentCard.hidden = true;
            blockedCard.hidden = false;
            document.getElementById('assessmentBlockedDetail').textContent =
                'A Social Security amount is needed before Phase 3 can show a complete base-case assessment.';
            saveButton.disabled = true;
            return;
        }

        blockedCard.hidden = true;
        var assessment = assessBaseCase(annualNeed, balance);
        assessmentCard.hidden = false;
        assessmentCard.className = 'coach-response assessment-card is-' + assessment.code;
        document.getElementById('assessmentLabel').textContent = assessment.label;
        document.getElementById('assessmentDetail').textContent = assessment.detail;

        var rateNote = document.getElementById('withdrawalRateNote');
        if (assessment.rateDisplay === null) {
            rateNote.textContent = '';
            rateNote.hidden = true;
        } else if (assessment.ratePct === 0) {
            rateNote.hidden = false;
            rateNote.textContent = 'No initial retirement-savings withdrawal rate applies while dependable income covers the spending goal.';
        } else {
            rateNote.hidden = false;
            rateNote.textContent = 'Your plan would initially require withdrawals equal to about ' +
                assessment.ratePct.toFixed(1).replace(/\.0$/, '') +
                '% of your retirement savings each year.';
        }

        saveButton.disabled = false;
        section.classList.toggle('uses-temporary-ss', temporary);
    }

    function renderAll() {
        updateBridgeCopy();
        renderKnownAmounts();
        renderIncomePicture();
    }

    function renderSavedSummary(record) {
        var statements = document.querySelectorAll('[data-phase3-summary]');
        var reviseButtons = document.querySelectorAll('[data-revise-plan]');
        var statusBadges = document.querySelectorAll('[data-phase3-record-status]');
        var status = record && record.saved ? record.planningRecordStatus : '';
        var statusLabel = recordTools.statusLabel(status) || (status ? status : '');

        statusBadges.forEach(function (badge) {
            badge.textContent = statusLabel;
            badge.className = 'record-status-badge' + (status ? ' is-' + status : '');
            badge.hidden = !status;
        });

        if (!record || !record.saved) {
            statements.forEach(function (statement) {
                statement.innerHTML = '<p>Save your retirement income plan to create a short summary for later phases.</p>';
            });
            reviseButtons.forEach(function (button) { button.hidden = true; });
            return;
        }

        var assessmentLabels = {
            workable: 'Looks workable on these assumptions',
            close: 'May need adjustment',
            difficult: 'Looks difficult on these assumptions'
        };
        var rateText = record.impliedInitialWithdrawalRate === null || record.impliedInitialWithdrawalRate === undefined
            ? ''
            : (' Implied initial withdrawal rate: about <strong>' +
                (record.impliedInitialWithdrawalRate * 100).toFixed(1).replace(/\.0$/, '') +
                '%</strong>.');
        var temporaryNote = record.temporarySocialSecurityEstimateUsed
            ? '<p>This saved plan used a temporary Social Security estimate and did not replace your Phase 2 record.</p>'
            : '';

        var html =
            '<p><strong>My retirement income plan</strong></p>' +
            '<p>Spending goal about <strong>' + currency(record.monthlyRetirementSpendingGoal) + '</strong> per month. ' +
            'Social Security about <strong>' + currency(record.monthlySocialSecurityAssumption) + '</strong>, ' +
            'other dependable income about <strong>' + currency(record.monthlyOtherDependableIncome) + '</strong>, ' +
            'and about <strong>' + currency(record.monthlyNeededFromRetirementSavings) + '</strong> from retirement savings.</p>' +
            '<p>Retirement savings balance: <strong>' + currency(record.retirementSavingsBalance) + '</strong>. ' +
            'Base-case assessment: <strong>' + (assessmentLabels[record.baseCaseAssessment] || record.baseCaseAssessment) + '</strong>.' +
            rateText + '</p>' +
            temporaryNote +
            '<p>This is a base-case planning snapshot, not a stress test or guarantee.</p>';

        statements.forEach(function (statement) { statement.innerHTML = html; });
        reviseButtons.forEach(function (button) { button.hidden = false; });
    }

    function setPhase3CompletionUi(isComplete) {
        var completeButton = document.getElementById('completePhase3Button');
        var indicator = document.getElementById('phase3CompleteIndicator');
        var continueLink = document.getElementById('continueToPhase4Link');
        if (completeButton) {
            completeButton.hidden = !!isComplete;
            if (!isComplete) {
                completeButton.disabled = false;
                completeButton.textContent = 'Save Phase 3 Progress';
            }
        }
        if (indicator) {
            indicator.hidden = !isComplete;
        }
        if (continueLink) {
            if (isComplete) {
                continueLink.classList.add('primary-action');
                continueLink.classList.remove('secondary-action');
            } else {
                continueLink.classList.add('secondary-action');
                continueLink.classList.remove('primary-action');
            }
        }
    }

    function restoreRecord(record) {
        if (!record || !record.saved) return;
        if (record.retirementSavingsBalance !== null && record.retirementSavingsBalance !== undefined) {
            document.getElementById('retirementSavingsBalance').value = record.retirementSavingsBalance;
            state.savingsBalance = Number(record.retirementSavingsBalance);
        }
        if (record.temporarySocialSecurityEstimateUsed && !state.phase2SsUsable &&
            record.monthlySocialSecurityAssumption !== null && record.monthlySocialSecurityAssumption !== undefined) {
            state.useTemporarySs = true;
            state.temporarySsMonthly = Number(record.monthlySocialSecurityAssumption);
            document.getElementById('temporaryMonthlySs').value = state.temporarySsMonthly;
        }
    }

    function saveRecord(event) {
        event.preventDefault();
        state.savingsBalance = numberOrNull(document.getElementById('retirementSavingsBalance').value);
        if (state.savingsBalance !== null && state.savingsBalance < 0) {
            state.savingsBalance = null;
        }
        var record = buildRecord(true);
        var errors = validateForSave(record);
        showErrors(errors);
        if (errors.length) return;

        record = persistDraft(true);
        renderSavedSummary(record);
        document.querySelector('[data-returning-record]').hidden = false;
        var confirmation = document.getElementById('phase3SaveConfirmation');
        var saveButton = document.getElementById('savePhase3Button');
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = canCloudWrite() ? 'Saving…' : 'Save My Retirement Income Plan';
        }
        persistCloudNow('phase').then(function (cloudResult) {
            setSaveConfirmationMessage(cloudResult);
            confirmation.hidden = false;
            confirmation.focus();
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.textContent = 'Save My Retirement Income Plan';
            }
            setPhase3CompletionUi(readProgress()[recordKey] === true);
        });
    }

    function completePhase() {
        var progress = readProgress();
        var record = existingRecord(progress);
        var errors = validateForSave(record);
        showErrors(errors);

        if (errors.length || !record.saved || record.hasUnsavedChanges) {
            document.getElementById('savings-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (!record.saved && !errors.length) {
                showErrors(['Save your retirement income plan before marking Phase 3 complete.']);
            } else if (record.hasUnsavedChanges && !errors.length) {
                showErrors(['Save your updated retirement income plan before marking Phase 3 complete.']);
            }
            return;
        }

        progress[recordKey] = true;
        progress.records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        record.completedAt = new Date().toISOString();
        record.journeyCompletionStatus = 'completed';
        progress.records[recordKey] = record;
        writeProgress(progress);

        document.querySelectorAll('[data-journey-phase="' + recordKey + '"]').forEach(function (element) {
            element.classList.add('is-complete');
            element.setAttribute('data-journey-complete', 'true');
        });

        var message = document.getElementById('phase3CompletionMessage');
        var completeButton = document.getElementById('completePhase3Button');
        if (completeButton) {
            completeButton.disabled = true;
            completeButton.textContent = canCloudWrite() ? 'Saving…' : 'Save Phase 3 Progress';
        }
        persistCloudNow('phase').then(function (cloudResult) {
            var cloudOk = canCloudWrite() &&
                cloudResult &&
                !cloudResult.localOnly &&
                !cloudResult.error &&
                !cloudResult.skipped &&
                !cloudResult.offline;
            message.innerHTML = cloudOk
                ? '<strong>Phase 3 progress saved to your Journey account.</strong>' +
                  '<span>You now have a working retirement income plan. When you are ready, continue to Phase 4 to stress-test it.</span>'
                : '<strong>Phase 3 progress saved.</strong>' +
                  '<span>You now have a working retirement income plan. When you are ready, continue to Phase 4 to stress-test it.</span>';
            message.hidden = false;
            message.focus();
            setPhase3CompletionUi(true);
            if (window.rbJourneyAnalytics && typeof window.rbJourneyAnalytics.trackPhaseComplete === 'function') {
                window.rbJourneyAnalytics.trackPhaseComplete(3);
            }
        });
    }

    document.getElementById('useTemporarySsButton').addEventListener('click', function () {
        var value = numberOrNull(document.getElementById('temporaryMonthlySs').value);
        if (value === null || value < 0) {
            showErrors(['Enter a temporary monthly Social Security estimate of zero or more.']);
            return;
        }
        showErrors([]);
        state.useTemporarySs = true;
        state.temporarySsMonthly = value;
        persistDraft(false);
        renderAll();
    });

    document.getElementById('temporaryMonthlySs').addEventListener('input', function () {
        if (!state.useTemporarySs) return;
        var value = numberOrNull(this.value);
        if (value === null || value < 0) return;
        state.temporarySsMonthly = value;
        persistDraft(false);
        renderAll();
    });

    document.getElementById('retirementSavingsBalance').addEventListener('input', function () {
        var value = numberOrNull(this.value);
        if (value !== null && value < 0) {
            this.value = '';
            value = null;
        }
        state.savingsBalance = value;
        persistDraft(false);
        document.getElementById('phase3SaveConfirmation').hidden = true;
        document.getElementById('phase3CompletionMessage').hidden = true;
        renderIncomePicture();
    });

    form.addEventListener('submit', saveRecord);
    document.getElementById('completePhase3Button').addEventListener('click', completePhase);
    document.querySelectorAll('[data-revise-plan]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('savings-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('retirementSavingsBalance').focus({ preventScroll: true });
        });
    });

    function bootPhase3() {
        var phase1 = readPhase1();
        state.phase1Usable = phase1.usable;
        state.monthlySpending = phase1.monthlySpending;
        state.monthlyOther = phase1.monthlyOther;

        var phase2 = readPhase2SocialSecurity();
        state.phase2SsUsable = phase2.usable;
        state.phase2SsMonthly = phase2.monthly;
        state.phase2SsReason = phase2.reason || 'missing';

        var progressAtLoad = readProgress();
        var record = existingRecord(progressAtLoad);
        if (record.saved === true) {
            document.querySelector('[data-returning-record]').hidden = false;
            restoreRecord(record);
        }
        setPhase3CompletionUi(
            progressAtLoad[recordKey] === true &&
            record.saved === true &&
            record.hasUnsavedChanges !== true
        );

        renderAll();
        renderSavedSummary(record);
    }

    if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
        window.rbJourneySync.afterReady(bootPhase3);
    } else {
        bootPhase3();
    }
})();
