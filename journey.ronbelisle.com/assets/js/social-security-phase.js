(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var recordKey = 'social-security';
    var form = document.getElementById('phase2RecordForm');
    var recordTools = window.rbJourneyRecords;
    if (!form || !recordTools) return;

    var labels = {
        'sooner': 'it provides income sooner',
        'later': 'it provides a larger dependable benefit later',
        'balance': 'it appears to balance earlier income and later security',
        'lifetime': 'it produced the highest estimated lifetime total',
        'retirement': 'it aligns with when I expect to retire',
        'starting-point': 'it is a useful starting point rather than a final choice',
        'not-ready': 'I am not ready to choose',
        'income-sooner': 'income sooner, but a smaller monthly benefit for life',
        'larger-later': 'a larger later benefit, but I must cover my expenses while I wait',
        'lifetime-assumption': 'a higher estimated lifetime total, but the result depends more on how long I live',
        'unresolved': 'I have not decided which tradeoff I prefer',
        'earnings-record': 'my earnings record',
        'fra-benefit': 'my benefit at Full Retirement Age',
        'early-exit': 'the effect of stopping work early',
        'survivor': 'how this choice affects my spouse or survivor',
        'delay-affordability': 'whether I can afford to delay',
        'current-rules': 'current Social Security rules',
        'nothing-yet': 'nothing yet',
        'other': 'another item'
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
    }

    function existingRecord(progress) {
        if (!progress.records || typeof progress.records !== 'object') return {};
        var record = progress.records[recordKey];
        return record && typeof record === 'object'
            ? recordTools.normalizeSocialSecurityRecord(record, progress[recordKey] === true)
            : {};
    }

    function selectedValue(name) {
        var selected = document.querySelector('input[name="' + name + '"]:checked');
        return selected ? selected.value : '';
    }

    function checkedValues(name) {
        return Array.prototype.map.call(
            document.querySelectorAll('input[name="' + name + '"]:checked'),
            function (input) { return input.value; }
        );
    }

    function numberOrNull(value) {
        if (value === '') return null;
        var number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function buildRecord() {
        var selectedBenefit = numberOrNull(document.getElementById('selectedMonthlyBenefit').value);
        var status = document.getElementById('decisionStatus').value;
        return {
            estimateReadiness: selectedValue('estimateReadiness'),
            birthYear: numberOrNull(document.getElementById('birthYear').value),
            decisionStatus: status,
            claimAge: status === 'provisional' ? numberOrNull(document.getElementById('claimAge').value) : null,
            benefitAtFra: status === 'provisional' ? numberOrNull(document.getElementById('benefitAtFra').value) : null,
            estimatedMonthlyBenefit: status === 'provisional' ? selectedBenefit : null,
            currentMonthlyBenefit: status === 'already-receiving' ? selectedBenefit : null,
            rationale: selectedValue('rationale'),
            mainTradeoff: selectedValue('mainTradeoff'),
            otherTradeoff: document.getElementById('otherTradeoff').value.trim(),
            verificationNeeded: checkedValues('verificationNeeded'),
            verificationPriority: document.getElementById('verificationPriority').value,
            companionAnswers: {
                earlyExit: selectedValue('earlyExitAnswer'),
                survivor: selectedValue('survivorAnswer'),
                spendingGap: selectedValue('spendingGapAnswer')
            },
            companionRecommendation: companionRecommendation(),
            saved: false
        };
    }

    function persistDraft(saved) {
        var progress = readProgress();
        var oldRecord = existingRecord(progress);
        var record = buildRecord();
        record.saved = saved === true || oldRecord.saved === true;
        record.hasUnsavedChanges = saved === false
            ? true
            : (saved === true ? false : oldRecord.hasUnsavedChanges === true);
        if (oldRecord.completedAt) record.completedAt = oldRecord.completedAt;
        record = recordTools.createSocialSecurityRecord(record, {
            oldRecord: oldRecord,
            journeyComplete: progress[recordKey] === true,
            reviewed: saved === true
        });
        progress.records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        progress.records[recordKey] = record;
        writeProgress(progress);
        return record;
    }

    function setRadio(name, value) {
        if (!value) return;
        var input = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
        if (input) input.checked = true;
    }

    function setChecks(name, values) {
        if (!Array.isArray(values)) return;
        values.forEach(function (value) {
            var input = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
            if (input) input.checked = true;
        });
    }

    function restoreRecord(record) {
        setRadio('estimateReadiness', record.estimateReadiness);
        setRadio('rationale', record.rationale);
        setRadio('mainTradeoff', record.mainTradeoff);
        setChecks('verificationNeeded', record.verificationNeeded);
        setRadio('earlyExitAnswer', record.companionAnswers && record.companionAnswers.earlyExit);
        setRadio('survivorAnswer', record.companionAnswers && record.companionAnswers.survivor);
        setRadio('spendingGapAnswer', record.companionAnswers && record.companionAnswers.spendingGap);

        if (record.decisionStatus) document.getElementById('decisionStatus').value = record.decisionStatus;
        if (record.birthYear !== null && record.birthYear !== undefined) document.getElementById('birthYear').value = record.birthYear;
        if (record.claimAge !== null && record.claimAge !== undefined) document.getElementById('claimAge').value = String(record.claimAge);
        if (record.benefitAtFra !== null && record.benefitAtFra !== undefined) document.getElementById('benefitAtFra').value = record.benefitAtFra;
        var benefit = record.decisionStatus === 'already-receiving' ? record.currentMonthlyBenefit : record.estimatedMonthlyBenefit;
        if (benefit !== null && benefit !== undefined) document.getElementById('selectedMonthlyBenefit').value = benefit;
        if (record.otherTradeoff) document.getElementById('otherTradeoff').value = record.otherTradeoff;
        if (record.verificationPriority) document.getElementById('verificationPriority').value = record.verificationPriority;
    }

    function syncInterest() {
        var interest = selectedValue('interest');
        var status = document.getElementById('decisionStatus');
        var claimAge = document.getElementById('claimAge');
        if (/^\d+$/.test(interest)) {
            claimAge.value = interest;
            status.value = 'provisional';
        } else if (interest === 'receiving') {
            status.value = 'already-receiving';
        } else if (interest === 'not-ready') {
            status.value = 'need-more-information';
        }
        updateStatusFields();
    }

    function updateEstimateResponse() {
        var response = document.getElementById('estimateReadinessResponse');
        var missing = selectedValue('estimateReadiness') === 'missing';
        response.hidden = !missing;
        if (missing && !document.getElementById('decisionStatus').value) {
            document.getElementById('decisionStatus').value = 'need-more-information';
            updateStatusFields();
        }
    }

    function updateRationaleResponse() {
        var value = selectedValue('rationale');
        var response = document.getElementById('rationaleResponse');
        var messages = {
            lifetime: 'That result is useful, but it isn’t the whole answer. Before relying on it, Phase 3 should test whether you can comfortably support your spending while waiting.',
            retirement: 'Remember that retirement and claiming do not have to occur at the same age. Phase 3 can test whether separating those dates improves or weakens your plan.',
            sooner: 'Earlier income may be practical when it supports a real need. The tradeoff is a permanently smaller monthly retirement benefit.',
            later: 'A larger later benefit can help if you live a long time. You will need income or savings to cover your expenses while you wait.',
            'not-ready': 'It is okay not to choose yet. Record what you still need to learn below.'
        };
        if (!messages[value]) {
            response.hidden = true;
            response.textContent = '';
            return;
        }
        response.textContent = messages[value];
        response.hidden = false;
    }

    function updateStatusFields() {
        var status = document.getElementById('decisionStatus').value;
        var claimAgeGroup = document.getElementById('claimAgeGroup');
        var fraGroup = document.getElementById('benefitAtFraGroup');
        var label = document.getElementById('selectedMonthlyBenefitLabel');
        var help = document.getElementById('selectedMonthlyBenefitHelp');

        claimAgeGroup.hidden = status === 'already-receiving';
        fraGroup.hidden = status === 'already-receiving';

        if (status === 'already-receiving') {
            label.textContent = 'Current gross monthly Social Security benefit';
            help.textContent = 'Enter the gross amount before Medicare is deducted.';
        } else if (status === 'need-more-information') {
            label.textContent = 'Estimated monthly benefit at the selected age';
            help.textContent = 'Leave blank if you do not yet have a selected claiming age.';
        } else {
            label.textContent = 'Estimated monthly benefit at the selected age';
            help.textContent = 'Use the amount shown by the Claiming Analyzer for the age you selected.';
        }
    }

    function updateTradeoffField() {
        document.getElementById('otherTradeoffGroup').hidden = selectedValue('mainTradeoff') !== 'other';
    }

    function companionRecommendation() {
        var early = selectedValue('earlyExitAnswer');
        var survivor = selectedValue('survivorAnswer');
        var gap = selectedValue('spendingGapAnswer');
        if (!early || !survivor || !gap) return '';
        if (early === 'yes' || early === 'not-sure') return 'early-exit';
        if (survivor === 'yes') return 'survivor';
        if (gap === 'yes') return 'spending-gap';
        return 'none';
    }

    function updateCompanionResult() {
        var result = document.getElementById('companionResult');
        var recommendation = companionRecommendation();
        var content = {
            'early-exit': {
                title: 'Next check: Early Exit Social Security Impact',
                text: 'Your Social Security estimate may assume that you keep working at about the same pay. This calculator shows how leaving work earlier could lower your benefit.',
                href: 'https://ronbelisle.com/ss-early-exit/',
                action: 'Check the Impact of Leaving Work Early'
            },
            survivor: {
                title: 'Next check: Social Security Survivor Impact',
                text: 'The Claiming Analyzer compares one person at a time. Because survivor income matters to your household, review how the higher benefit may affect the spouse who lives longer.',
                href: 'https://ronbelisle.com/ss-survivor-impact/',
                action: 'Review Survivor Income'
            },
            'spending-gap': {
                title: 'Next check: Social Security + Spending Gap',
                text: 'Compare your expected Social Security and other dependable income with your retirement spending target to estimate what may still need to come from savings.',
                href: 'https://ronbelisle.com/ss-gap/',
                action: 'Review My Social Security Spending Gap'
            }
        };

        if (!recommendation) {
            result.innerHTML = '<h3>Complete the three questions above</h3><p>Your answers will show whether another calculator could help.</p>';
        } else if (recommendation === 'none') {
            result.innerHTML = '<h3>You are ready to continue</h3><p>You do not need another Social Security calculator for this phase. Phase 3 will combine your claiming choice with spending, savings, and other income.</p>';
        } else {
            var item = content[recommendation];
            result.innerHTML = '<p class="eyebrow">One useful next step</p><h3>' + item.title + '</h3><p>' + item.text + '</p><a class="secondary-action" target="_blank" rel="noopener" href="' + item.href + '">' + item.action + '</a><p class="action-note">Optional. You can complete Phase 2 without it. Opens in a separate tab.</p>';
        }
    }

    function validateRecord(record) {
        var errors = [];
        if (!record.decisionStatus) errors.push('Choose a decision status.');
        var currentYear = new Date().getFullYear();
        if (record.birthYear !== null && (record.birthYear < 1920 || record.birthYear > currentYear)) {
            errors.push('Enter a birth year between 1920 and ' + currentYear + '.');
        }

        if (record.decisionStatus === 'provisional') {
            if (!record.claimAge) errors.push('Choose a claiming age to test.');
            if (!(record.benefitAtFra > 0)) errors.push('Enter your monthly benefit at Full Retirement Age.');
            if (!(record.estimatedMonthlyBenefit > 0)) errors.push('Enter the estimated monthly benefit at your selected age.');
            if (!record.mainTradeoff) errors.push('Choose the main tradeoff.');
            if (record.mainTradeoff === 'other' && !record.otherTradeoff) errors.push('Describe the other tradeoff.');
            if (!record.verificationNeeded.length) errors.push('Select what needs verification.');
            if (!record.verificationPriority) errors.push('Choose the most important next step.');
        }

        if (record.decisionStatus === 'need-more-information') {
            if (!record.verificationNeeded.length) errors.push('Select at least one item that needs verification.');
            if (!record.verificationPriority) errors.push('Choose the most important next step.');
        }

        if (record.decisionStatus === 'already-receiving') {
            if (!(record.currentMonthlyBenefit > 0)) errors.push('Enter your current gross monthly Social Security benefit.');
            if (!record.verificationNeeded.length) errors.push('Select what needs verification.');
            if (!record.verificationPriority) errors.push('Choose the most important next step.');
        }

        return errors;
    }

    function showErrors(errors) {
        var summary = document.getElementById('phase2ErrorSummary');
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

    function showIncompleteState() {
        document.getElementById('phase2SaveConfirmation').hidden = true;
        document.getElementById('phase2CompletionMessage').hidden = true;
        var complete = readProgress()[recordKey] === true;
        document.getElementById('completePhase2Button').textContent = complete
            ? 'Save Updates Before Continuing'
            : 'Save and Continue to Phase 3';
    }

    function currency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(value || 0);
    }

    function tradeoffText(record) {
        if (record.mainTradeoff === 'other') return record.otherTradeoff || 'another tradeoff';
        return labels[record.mainTradeoff] || 'not yet recorded';
    }

    function verificationText(record) {
        return labels[record.verificationPriority] || 'the information noted above';
    }

    function renderAssumption(record) {
        var statements = document.querySelectorAll('[data-phase2-summary]');
        var reviseButtons = document.querySelectorAll('[data-revise-assumption]');
        var reviewGuidance = document.querySelectorAll('[data-phase2-review-guidance]');
        var statusBadges = document.querySelectorAll('[data-phase2-record-status]');
        var status = record && record.saved ? record.planningRecordStatus : '';
        var statusLabel = recordTools.statusLabel(status);

        statusBadges.forEach(function (badge) {
            badge.textContent = statusLabel;
            badge.className = 'record-status-badge' + (status ? ' is-' + status : '');
            badge.hidden = !status;
        });

        if (!record || !record.saved) {
            statements.forEach(function (statement) {
                statement.innerHTML = '<p>Save your claiming choice to create a short summary for Phase 3.</p>';
            });
            reviseButtons.forEach(function (revise) { revise.hidden = true; });
            reviewGuidance.forEach(function (guidance) { guidance.hidden = true; });
            return;
        }

        var summary = '';
        if (record.decisionStatus === 'provisional') {
            summary =
                '<p><strong>My current Social Security position</strong></p>' +
                '<p>I will test claiming at age <strong>' + record.claimAge + '</strong>. My benefit at Full Retirement Age is approximately <strong>' + currency(record.benefitAtFra) + ' per month</strong>, and my estimated benefit at age <strong>' + record.claimAge + '</strong> is approximately <strong>' + currency(record.estimatedMonthlyBenefit) + ' per month</strong>.</p>' +
                '<p>I prefer this choice because ' + (labels[record.rationale] || 'it is a useful starting point') + '.</p>' +
                '<p>The main tradeoff is: <strong>' + tradeoffText(record) + '</strong>.</p>' +
                '<p>What remains uncertain: <strong>' + verificationText(record) + '</strong>.</p>' +
                '<p>Phase 3 will use this claiming age and monthly benefit to test how they fit with my spending, savings, and other income. This is a planning choice, not advice to file.</p>';
        } else if (record.decisionStatus === 'need-more-information') {
            summary =
                '<p><strong>My current Social Security position</strong></p>' +
                '<p>I am not ready to select a claiming age. Before choosing, I need to check <strong>' + verificationText(record) + '</strong>.</p>' +
                '<p>Phase 3 may use a temporary claiming age, but this record is not ready to rely on. I should return after I have the missing information.</p>';
        } else {
            summary =
                '<p><strong>My current Social Security position</strong></p>' +
                '<p>I am already receiving approximately <strong>' + currency(record.currentMonthlyBenefit) + ' per month</strong> in gross Social Security benefits.</p>' +
                '<p>Phase 3 should use this current benefit rather than model a future claiming age.</p>' +
                '<p>What remains uncertain: <strong>' + verificationText(record) + '</strong>.</p>';
        }
        statements.forEach(function (statement) { statement.innerHTML = summary; });
        reviseButtons.forEach(function (revise) { revise.hidden = false; });
        reviewGuidance.forEach(function (guidance) { guidance.hidden = false; });
    }

    function saveRecord(event) {
        event.preventDefault();
        var record = buildRecord();
        var errors = validateRecord(record);
        showErrors(errors);
        if (errors.length) return;

        record = persistDraft(true);
        renderAssumption(record);
        if (readProgress()[recordKey] === true) {
            document.getElementById('completePhase2Button').textContent = 'Phase 2 Complete';
        }
        var confirmation = document.getElementById('phase2SaveConfirmation');
        confirmation.hidden = false;
        confirmation.focus();
    }

    function completePhase() {
        var progress = readProgress();
        var record = existingRecord(progress);
        var errors = validateRecord(record);
        showErrors(errors);

        if (errors.length || !record.saved || record.hasUnsavedChanges) {
            document.getElementById('record-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (!record.saved && !errors.length) {
                showErrors(['Save your claiming choice before continuing.']);
            } else if (record.hasUnsavedChanges && !errors.length) {
                showErrors(['Save your updated Social Security record before continuing.']);
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

        var message = document.getElementById('phase2CompletionMessage');
        if (record.decisionStatus === 'need-more-information') {
            message.innerHTML = '<strong>Phase 2 complete. Planning-record status: Needs information.</strong><span>Your Journey progress is saved, but return when you have the information needed to update this record.</span>';
        } else if (record.planningRecordStatus === 'needs-verification') {
            message.innerHTML = '<strong>Phase 2 complete. Planning-record status: Needs verification.</strong><span>Phase 3 can test this choice, but keep the noted verification item with your plan.</span>';
        } else if (record.decisionStatus === 'already-receiving') {
            message.innerHTML = '<strong>Phase 2 complete. Planning-record status: Already receiving benefits.</strong><span>Phase 3 will use your current benefit, and you can review this record later.</span>';
        } else {
            message.innerHTML = '<strong>Phase 2 complete. Planning-record status: Current.</strong><span>Your claiming choice is ready to test in Phase 3 and can be reviewed later.</span>';
        }
        message.hidden = false;
        message.focus();
        document.getElementById('completePhase2Button').textContent = 'Phase 2 Complete';
    }

    function handleDraftChange(event) {
        if (event.target.name === 'interest') syncInterest();
        if (event.target.name === 'estimateReadiness') updateEstimateResponse();
        if (event.target.name === 'rationale') updateRationaleResponse();
        if (event.target.id === 'decisionStatus') updateStatusFields();
        if (event.target.name === 'mainTradeoff') updateTradeoffField();
        var companionOnly = event.target.name === 'earlyExitAnswer' || event.target.name === 'survivorAnswer' || event.target.name === 'spendingGapAnswer';
        if (companionOnly) {
            updateCompanionResult();
        }
        persistDraft(companionOnly ? undefined : false);
        if (!companionOnly) {
            showIncompleteState();
        }
    }

    document.addEventListener('change', handleDraftChange);
    document.addEventListener('input', function (event) {
        if (event.target.closest('#phase2RecordForm')) {
            persistDraft(false);
            showIncompleteState();
        }
    });
    form.addEventListener('submit', saveRecord);
    document.getElementById('completePhase2Button').addEventListener('click', completePhase);
    document.querySelectorAll('[data-revise-assumption]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('record-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('decisionStatus').focus({ preventScroll: true });
        });
    });

    var progressAtLoad = readProgress();
    var rawRecordAtLoad = progressAtLoad.records && progressAtLoad.records[recordKey];
    var record = existingRecord(progressAtLoad);
    if (record.saved === true && rawRecordAtLoad && rawRecordAtLoad.schemaVersion !== recordTools.schemaVersion) {
        progressAtLoad.records[recordKey] = record;
        writeProgress(progressAtLoad);
    }
    var returningMember = record.saved === true;
    if (returningMember) {
        document.querySelector('[data-returning-record]').hidden = false;
        document.querySelector('[data-first-visit-summary]').hidden = true;
    }
    if (progressAtLoad[recordKey] === true && record.saved === true && record.hasUnsavedChanges !== true) {
        document.getElementById('completePhase2Button').textContent = 'Phase 2 Complete';
    }
    restoreRecord(record);
    updateEstimateResponse();
    updateRationaleResponse();
    updateStatusFields();
    updateTradeoffField();
    updateCompanionResult();
    renderAssumption(record);
})();
