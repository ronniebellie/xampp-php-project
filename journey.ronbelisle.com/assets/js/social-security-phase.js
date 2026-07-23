(function () {
    'use strict';

    var storageKey = 'rbJourneyProgressV1';
    var recordKey = 'social-security';
    var form = document.getElementById('phase2RecordForm');
    var recordTools = window.rbJourneyRecords;
    if (!form || !recordTools) return;

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

    function numberOrNull(value) {
        if (value === '') return null;
        var number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function notesValue() {
        var element = document.getElementById('decisionNotes');
        return element ? element.value.trim() : '';
    }

    function fraFromBirthYear(birthYear) {
        if (birthYear === null || birthYear === undefined || !Number.isFinite(birthYear)) return null;
        if (birthYear <= 1937) return { years: 65, months: 0 };
        if (birthYear === 1938) return { years: 65, months: 2 };
        if (birthYear === 1939) return { years: 65, months: 4 };
        if (birthYear === 1940) return { years: 65, months: 6 };
        if (birthYear === 1941) return { years: 65, months: 8 };
        if (birthYear === 1942) return { years: 65, months: 10 };
        if (birthYear >= 1943 && birthYear <= 1954) return { years: 66, months: 0 };
        if (birthYear === 1955) return { years: 66, months: 2 };
        if (birthYear === 1956) return { years: 66, months: 4 };
        if (birthYear === 1957) return { years: 66, months: 6 };
        if (birthYear === 1958) return { years: 66, months: 8 };
        if (birthYear === 1959) return { years: 66, months: 10 };
        return { years: 67, months: 0 };
    }

    function formatFraLabel(fra) {
        if (!fra) return '';
        if (!fra.months) return String(fra.years);
        return fra.years + ' and ' + fra.months + ' months';
    }

    function isClaimingAtFra(birthYear, claimAge) {
        var fra = fraFromBirthYear(birthYear);
        if (!fra || claimAge === null || claimAge === undefined) return false;
        // Claim ages on this page are whole years; only treat as FRA when FRA is a whole year.
        return fra.months === 0 && Number(claimAge) === fra.years;
    }

    function currentBirthYear() {
        return numberOrNull(document.getElementById('birthYear').value);
    }

    function currentClaimAge() {
        return numberOrNull(document.getElementById('claimAge').value);
    }

    function planningBenefitUsesFraAmount() {
        var status = document.getElementById('decisionStatus').value;
        return status === 'provisional' && isClaimingAtFra(currentBirthYear(), currentClaimAge());
    }

    function buildRecord() {
        var status = document.getElementById('decisionStatus').value;
        var claimAge = status === 'provisional' ? currentClaimAge() : null;
        var birthYear = currentBirthYear();
        var benefitAtFra = status === 'provisional' ? numberOrNull(document.getElementById('benefitAtFra').value) : null;
        var selectedBenefit = numberOrNull(document.getElementById('selectedMonthlyBenefit').value);
        var usesFraAmount = status === 'provisional' && isClaimingAtFra(birthYear, claimAge);

        return {
            estimateReadiness: selectedValue('estimateReadiness'),
            birthYear: birthYear,
            decisionStatus: status,
            claimAge: claimAge,
            benefitAtFra: benefitAtFra,
            estimatedMonthlyBenefit: status === 'provisional'
                ? (usesFraAmount ? benefitAtFra : selectedBenefit)
                : null,
            currentMonthlyBenefit: status === 'already-receiving' ? selectedBenefit : null,
            decisionNotes: notesValue(),
            // Legacy justification fields are no longer collected. Keep empty on save so older
            // values do not keep the record stuck in "needs verification."
            rationale: '',
            mainTradeoff: '',
            otherTradeoff: '',
            verificationNeeded: [],
            verificationPriority: '',
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

    function restoreRecord(record) {
        setRadio('estimateReadiness', record.estimateReadiness);
        setRadio('earlyExitAnswer', record.companionAnswers && record.companionAnswers.earlyExit);
        setRadio('survivorAnswer', record.companionAnswers && record.companionAnswers.survivor);
        setRadio('spendingGapAnswer', record.companionAnswers && record.companionAnswers.spendingGap);

        if (record.decisionStatus) document.getElementById('decisionStatus').value = record.decisionStatus;
        if (record.birthYear !== null && record.birthYear !== undefined) document.getElementById('birthYear').value = record.birthYear;
        if (record.claimAge !== null && record.claimAge !== undefined) {
            document.getElementById('claimAge').value = String(record.claimAge);
            setRadio('interest', String(record.claimAge));
        } else if (record.decisionStatus === 'already-receiving') {
            setRadio('interest', 'receiving');
        } else if (record.decisionStatus === 'need-more-information') {
            setRadio('interest', 'not-ready');
        }
        if (record.benefitAtFra !== null && record.benefitAtFra !== undefined) document.getElementById('benefitAtFra').value = record.benefitAtFra;
        var benefit = record.decisionStatus === 'already-receiving' ? record.currentMonthlyBenefit : record.estimatedMonthlyBenefit;
        if (benefit !== null && benefit !== undefined) document.getElementById('selectedMonthlyBenefit').value = benefit;
        if (record.decisionNotes) document.getElementById('decisionNotes').value = record.decisionNotes;
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
        updateClaimingConfirmation();
    }

    function updateEstimateResponse() {
        var response = document.getElementById('estimateReadinessResponse');
        var missing = selectedValue('estimateReadiness') === 'missing';
        response.hidden = !missing;
        if (missing && !document.getElementById('decisionStatus').value) {
            document.getElementById('decisionStatus').value = 'need-more-information';
            updateStatusFields();
            updateClaimingConfirmation();
        }
    }

    function updateClaimingConfirmation() {
        var box = document.getElementById('claimingConfirmation');
        if (!box) return;

        var status = document.getElementById('decisionStatus').value;
        var claimAge = document.getElementById('claimAge').value;
        var html = '';

        if (status === 'provisional' && claimAge) {
            html =
                '<p><strong>You’ve selected age ' + claimAge + ' as the claiming age you want to test in your retirement plan.</strong></p>' +
                '<p>This is a planning assumption, not a Social Security filing action. You can revisit and change it later.</p>';
        } else if (status === 'already-receiving') {
            html =
                '<p><strong>You’ve indicated that you are already receiving Social Security benefits.</strong></p>' +
                '<p>Your plan can use your current benefit as the working assumption. This is a planning assumption, not a filing action. You can revisit and change it later.</p>';
        } else if (status === 'need-more-information') {
            html =
                '<p><strong>You’ve indicated that you are not ready to select a claiming age yet.</strong></p>' +
                '<p>That’s fine. Save what you know now and return when you have a clearer estimate. This remains a planning assumption, not a filing action.</p>';
        }

        if (!html) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }

        box.innerHTML = html;
        box.hidden = false;
    }

    function updateStatusFields() {
        var status = document.getElementById('decisionStatus').value;
        var claimAgeGroup = document.getElementById('claimAgeGroup');
        var fraGroup = document.getElementById('benefitAtFraGroup');
        var selectedGroup = document.getElementById('selectedBenefitGroup');
        var selectedInputWrap = document.getElementById('selectedMonthlyBenefitInput');
        var fraConfirmation = document.getElementById('fraBenefitConfirmation');
        var label = document.getElementById('selectedMonthlyBenefitLabel');
        var help = document.getElementById('selectedMonthlyBenefitHelp');
        var claimAge = document.getElementById('claimAge').value;
        var birthYear = currentBirthYear();
        var fra = fraFromBirthYear(birthYear);
        var usesFraAmount = planningBenefitUsesFraAmount();
        var benefitAtFra = numberOrNull(document.getElementById('benefitAtFra').value);

        claimAgeGroup.hidden = status === 'already-receiving' || status === 'need-more-information';
        fraGroup.hidden = status === 'already-receiving' || status === 'need-more-information';

        if (status === 'already-receiving') {
            selectedGroup.hidden = false;
            if (selectedInputWrap) selectedInputWrap.hidden = false;
            label.hidden = false;
            help.hidden = false;
            fraConfirmation.hidden = true;
            fraConfirmation.innerHTML = '';
            label.textContent = 'Current gross monthly Social Security benefit';
            help.textContent = 'Enter the gross amount before Medicare is deducted.';
        } else if (status === 'need-more-information') {
            selectedGroup.hidden = true;
            fraConfirmation.hidden = true;
            fraConfirmation.innerHTML = '';
        } else if (usesFraAmount) {
            selectedGroup.hidden = true;
            if (selectedInputWrap) selectedInputWrap.hidden = true;
            label.hidden = true;
            help.hidden = true;
            if (benefitAtFra !== null && benefitAtFra > 0) {
                document.getElementById('selectedMonthlyBenefit').value = String(benefitAtFra);
            }
            fraConfirmation.hidden = false;
            fraConfirmation.innerHTML =
                '<p>Because you selected your Full Retirement Age' +
                (fra ? ' (age ' + formatFraLabel(fra) + ')' : '') +
                ', your planning benefit is the same as your Full Retirement Age benefit.</p>' +
                '<p>Planning monthly benefit: <span class="benefit-amount">' +
                (benefitAtFra !== null && benefitAtFra > 0 ? currency(benefitAtFra) : 'Enter the Full Retirement Age amount above') +
                '</span></p>';
        } else {
            selectedGroup.hidden = false;
            if (selectedInputWrap) selectedInputWrap.hidden = false;
            label.hidden = false;
            help.hidden = false;
            fraConfirmation.hidden = true;
            fraConfirmation.innerHTML = '';

            if (claimAge) {
                label.textContent = 'Monthly benefit at Age ' + claimAge + ' (from the Claiming Analyzer)';
                help.textContent = 'Step 3: Enter the monthly benefit the Claiming Analyzer shows for age ' + claimAge + '.';
            } else {
                label.textContent = 'Monthly benefit shown by the Claiming Analyzer';
                help.textContent = 'Step 3: After comparing claiming ages in the Claiming Analyzer, enter the monthly benefit shown for the age you selected.';
            }

            if (fra && fra.months > 0 && claimAge) {
                help.textContent += ' Your Full Retirement Age is ' + formatFraLabel(fra) + ', so Age ' + claimAge + ' is not exact FRA—use the analyzer amount for the age you selected.';
            } else if (!birthYear && claimAge) {
                help.textContent += ' Enter your birth year above if this age is your Full Retirement Age; then this second entry can be skipped.';
            }
        }

        updateClaimingConfirmation();
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
            result.innerHTML = '<h3>You are ready to continue</h3><p>You do not need another Social Security calculator for this phase. Your saved claiming assumption can move forward with the rest of your Journey when later phases are ready.</p>';
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
            if (!(record.benefitAtFra > 0)) errors.push('Enter your monthly benefit at full retirement age.');
            if (!isClaimingAtFra(record.birthYear, record.claimAge) && !(record.estimatedMonthlyBenefit > 0)) {
                errors.push('Enter the monthly benefit shown by the Claiming Analyzer for the age you selected.');
            }
        }

        if (record.decisionStatus === 'already-receiving') {
            if (!(record.currentMonthlyBenefit > 0)) errors.push('Enter your current gross monthly Social Security benefit.');
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
            ? 'Save Updates'
            : 'Save Phase 2 Progress';
    }

    function currency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(value || 0);
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
                statement.innerHTML = '<p>Save your claiming choice to create a short summary for later phases.</p>';
            });
            reviseButtons.forEach(function (revise) { revise.hidden = true; });
            reviewGuidance.forEach(function (guidance) { guidance.hidden = true; });
            return;
        }

        var summary = '';
        if (record.decisionStatus === 'provisional') {
            var sameAsFra = isClaimingAtFra(record.birthYear, record.claimAge) ||
                (record.estimatedMonthlyBenefit !== null &&
                    record.benefitAtFra !== null &&
                    Number(record.estimatedMonthlyBenefit) === Number(record.benefitAtFra));
            if (sameAsFra) {
                summary =
                    '<p><strong>My current Social Security position</strong></p>' +
                    '<p>I will test claiming at age <strong>' + record.claimAge + '</strong>, my Full Retirement Age. My planning benefit is approximately <strong>' + currency(record.benefitAtFra) + ' per month</strong>.</p>' +
                    (record.decisionNotes ? '<p>Notes: ' + escapeHtml(record.decisionNotes) + '</p>' : '') +
                    '<p>This is a planning assumption, not advice to file. I can revisit and change it later.</p>';
            } else {
                summary =
                    '<p><strong>My current Social Security position</strong></p>' +
                    '<p>I will test claiming at age <strong>' + record.claimAge + '</strong>. My benefit at full retirement age is approximately <strong>' + currency(record.benefitAtFra) + ' per month</strong>, and the Claiming Analyzer amount I recorded for age <strong>' + record.claimAge + '</strong> is approximately <strong>' + currency(record.estimatedMonthlyBenefit) + ' per month</strong>.</p>' +
                    (record.decisionNotes ? '<p>Notes: ' + escapeHtml(record.decisionNotes) + '</p>' : '') +
                    '<p>This is a planning assumption, not advice to file. I can revisit and change it later.</p>';
            }
        } else if (record.decisionStatus === 'need-more-information') {
            summary =
                '<p><strong>My current Social Security position</strong></p>' +
                '<p>I am not ready to select a claiming age yet.</p>' +
                (record.decisionNotes ? '<p>Notes: ' + escapeHtml(record.decisionNotes) + '</p>' : '') +
                '<p>I should return after I have a clearer estimate. This remains a planning assumption, not a filing action.</p>';
        } else {
            summary =
                '<p><strong>My current Social Security position</strong></p>' +
                '<p>I am already receiving approximately <strong>' + currency(record.currentMonthlyBenefit) + ' per month</strong> in gross Social Security benefits.</p>' +
                (record.decisionNotes ? '<p>Notes: ' + escapeHtml(record.decisionNotes) + '</p>' : '') +
                '<p>My plan can use this current benefit as the working assumption. This is a planning assumption, not a filing action.</p>';
        }
        statements.forEach(function (statement) { statement.innerHTML = summary; });
        reviseButtons.forEach(function (revise) { revise.hidden = false; });
        reviewGuidance.forEach(function (guidance) { guidance.hidden = false; });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
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
                showErrors(['Save your claiming choice before marking Phase 2 complete.']);
            } else if (record.hasUnsavedChanges && !errors.length) {
                showErrors(['Save your updated Social Security record before marking Phase 2 complete.']);
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
            message.innerHTML = '<strong>Phase 2 progress saved. Status: Needs information.</strong><span>Return when you have enough information to choose a claiming age to test.</span>';
        } else if (record.decisionStatus === 'already-receiving') {
            message.innerHTML = '<strong>Phase 2 progress saved. Status: Already receiving benefits.</strong><span>Your current benefit is recorded as the working assumption.</span>';
        } else {
            message.innerHTML = '<strong>Phase 2 progress saved.</strong><span>Your claiming-age assumption is ready for later phases when they become available.</span>';
        }
        message.hidden = false;
        message.focus();
        document.getElementById('completePhase2Button').textContent = 'Phase 2 Complete';
    }

    function handleDraftChange(event) {
        if (event.target.name === 'interest') syncInterest();
        if (event.target.name === 'estimateReadiness') updateEstimateResponse();
        if (
            event.target.id === 'decisionStatus' ||
            event.target.id === 'claimAge' ||
            event.target.id === 'birthYear' ||
            event.target.id === 'benefitAtFra'
        ) {
            updateStatusFields();
        }
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
            if (
                event.target.id === 'claimAge' ||
                event.target.id === 'birthYear' ||
                event.target.id === 'benefitAtFra'
            ) {
                updateStatusFields();
            }
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
    updateStatusFields();
    updateCompanionResult();
    renderAssumption(record);
})();
