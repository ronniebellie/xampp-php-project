(function () {
    'use strict';

    var calculatorKey = 'rbJourneyCalculator:retirementSpendingPlan:v1';
    var progressKey = 'rbJourneyProgressV1';
    var form = document.getElementById('retirementSpendingPlanForm');
    if (!form) return;

    var saveStatus = document.getElementById('spendingPlanSaveStatus');
    var resultsPanel = document.getElementById('spendingPlanResults');
    var errorSummary = document.getElementById('spendingPlanErrorSummary');
    var lastOutputs = null;
    var lastPlanningMethod = 'guided_categories';

    var categoryFields = [
        'housing',
        'foodHousehold',
        'transportation',
        'healthcare',
        'insuranceDebt',
        'lifestyleGiving',
        'otherSpending'
    ];

    var copyByStatus = {
        planning: {
            currentTitle: 'Current monthly household spending',
            currentHelp: 'Use your best estimate of what your household spends in a typical month. The number does not need to be perfect.',
            title: 'Expected monthly household spending in retirement',
            help: 'Enter your best estimate of what your household will spend in a typical month after you retire. Consider expenses that may end, decrease, increase, or begin—but you do not need to calculate each change separately.',
            label: 'Expected monthly household spending in retirement',
            fieldNote: 'This becomes your monthly retirement spending target.',
            resultMonthly: 'Expected monthly retirement spending:',
            assumptionsPhrase: 'expected monthly retirement spending',
            validationMissing: 'Enter expected monthly household spending in retirement.',
            tipsHtml:
                '<li>Commuting or payroll contributions may end.</li>' +
                '<li>Debt payments may end or decrease.</li>' +
                '<li>Healthcare or insurance may increase.</li>' +
                '<li>Travel, hobbies, family support, or home maintenance may change.</li>'
        },
        retired: {
            currentTitle: 'Current monthly household spending in retirement',
            currentHelp: 'Enter your best estimate of what your household spends in a typical month during retirement. The number does not need to be perfect.',
            title: 'Current monthly household spending in retirement',
            help: 'Enter your best estimate of what your household spends in a typical month during retirement. The number does not need to be perfect.',
            label: 'Current monthly household spending in retirement',
            fieldNote: 'This becomes your monthly retirement spending target.',
            resultMonthly: 'Current monthly retirement spending:',
            assumptionsPhrase: 'current monthly retirement spending',
            validationMissing: 'Enter current monthly household spending in retirement.',
            tipsHtml:
                '<li>Debt payments may end or decrease.</li>' +
                '<li>Healthcare or insurance may change.</li>' +
                '<li>Travel, hobbies, family support, or home maintenance may change.</li>' +
                '<li>Some work-related costs may already have ended.</li>'
        }
    };

    function now() {
        return new Date().toISOString();
    }

    function money(value) {
        if (!Number.isFinite(value)) return '$0';
        return value.toLocaleString(undefined, {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        });
    }

    function numberValue(id) {
        var element = document.getElementById(id);
        if (!element || element.value === '') return 0;
        var value = Number(element.value);
        return Number.isFinite(value) ? value : 0;
    }

    function optionalNumberValue(id) {
        var element = document.getElementById(id);
        if (!element || element.value === '') return null;
        var value = Number(element.value);
        return Number.isFinite(value) ? value : null;
    }

    function setNumberValue(id, value) {
        var element = document.getElementById(id);
        if (!element || value === null || value === undefined) return;
        element.value = value;
    }

    function getInput(id) {
        return document.getElementById(id);
    }

    function selectedMethod() {
        var selected = form.querySelector('input[name="startingMethod"]:checked');
        return selected ? selected.value : 'guided_categories';
    }

    function normalizeRetirementStatus(value) {
        return value === 'retired' ? 'retired' : (value === 'planning' ? 'planning' : '');
    }

    function selectedRetirementStatus() {
        var selected = form.querySelector('input[name="retirementStatus"]:checked');
        return selected ? normalizeRetirementStatus(selected.value) : '';
    }

    function inferRetirementStatus(record, inputs) {
        var fromInputs = normalizeRetirementStatus(inputs && inputs.retirementStatus);
        if (fromInputs) return fromInputs;

        var recordInputs = record && record.inputs && typeof record.inputs === 'object' ? record.inputs : null;
        var fromRecord = normalizeRetirementStatus(recordInputs && recordInputs.retirementStatus);
        if (fromRecord) return fromRecord;

        var fromLater = normalizeRetirementStatus(
            record &&
            record.journeyResult &&
            record.journeyResult.dataForLaterPhases &&
            record.journeyResult.dataForLaterPhases.retirementStatus
        );
        if (fromLater) return fromLater;

        // Pre-change records never stored retirementStatus — default safely to planning.
        if (recordInputs && !Object.prototype.hasOwnProperty.call(recordInputs, 'retirementStatus')) {
            return 'planning';
        }

        // New/incomplete drafts may leave the choice blank until the user selects one.
        return '';
    }

    function copyForStatus(status) {
        return copyByStatus[status === 'retired' ? 'retired' : 'planning'];
    }

    function applyRetirementCopy() {
        var status = selectedRetirementStatus() || 'planning';
        var copy = copyForStatus(status);
        form.querySelectorAll('[data-current-spending-copy]').forEach(function (element) {
            var key = element.getAttribute('data-current-spending-copy');
            if (key === 'title') element.textContent = copy.currentTitle;
            else if (key === 'help') element.textContent = copy.currentHelp;
        });
        form.querySelectorAll('[data-retirement-copy]').forEach(function (element) {
            var key = element.getAttribute('data-retirement-copy');
            if (key === 'title') element.textContent = copy.title;
            else if (key === 'help') element.textContent = copy.help;
            else if (key === 'label') element.textContent = copy.label;
            else if (key === 'field-note') element.textContent = copy.fieldNote;
            else if (key === 'result-monthly') element.textContent = copy.resultMonthly;
            else if (key === 'tips') element.innerHTML = copy.tipsHtml;
        });
    }

    function readProgress() {
        try {
            var parsed = JSON.parse(localStorage.getItem(progressKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function writeProgress(progress) {
        localStorage.setItem(progressKey, JSON.stringify(progress));
    }

    function readCalculatorRecord() {
        try {
            var parsed = JSON.parse(localStorage.getItem(calculatorKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function writeCalculatorRecord(record) {
        localStorage.setItem(calculatorKey, JSON.stringify(record));
    }

    function inputsFromForm() {
        var categories = {};
        var retirementStatus = selectedRetirementStatus();
        var currentMonthlySpending = optionalNumberValue('currentMonthlySpending');
        var expectedMonthlyRetirementSpending = optionalNumberValue('expectedMonthlyRetirementSpending');
        categoryFields.forEach(function (field) {
            categories[field] = numberValue(field);
        });

        if (retirementStatus === 'retired') {
            expectedMonthlyRetirementSpending = currentMonthlySpending !== null
                ? currentMonthlySpending
                : expectedMonthlyRetirementSpending;
        }

        return {
            retirementStatus: retirementStatus,
            startingMethod: retirementStatus === 'retired' ? 'monthly_estimate' : selectedMethod(),
            currentMonthlySpending: currentMonthlySpending,
            currentAnnualSpending: optionalNumberValue('currentAnnualSpending'),
            categories: categories,
            expectedMonthlyRetirementSpending: expectedMonthlyRetirementSpending,
            monthlyOtherRegularRetirementIncome: numberValue('monthlyOtherRegularRetirementIncome'),
            notes: document.getElementById('spendingNotes').value.trim()
        };
    }

    function totalValues(values, fields) {
        return fields.reduce(function (sum, field) {
            return sum + (Number(values[field]) || 0);
        }, 0);
    }

    function nonNegativeErrors(inputs) {
        var errors = [];
        var fields = categoryFields.concat([
            'currentMonthlySpending',
            'currentAnnualSpending',
            'expectedMonthlyRetirementSpending',
            'monthlyOtherRegularRetirementIncome'
        ]);

        fields.forEach(function (field) {
            var element = document.getElementById(field);
            if (!element || element.value === '') return;
            var section = element.closest('.calculator-section');
            if (section && section.hidden) return;
            var value = Number(element.value);
            if (!Number.isFinite(value) || value < 0) {
                errors.push('Enter zero or a positive number for ' + labelText(element) + '.');
            }
        });

        if (inputs.notes.length > 2000) {
            errors.push('Notes must be 2,000 characters or fewer.');
        }

        return errors;
    }

    function labelText(element) {
        var label = form.querySelector('label[for="' + element.id + '"]');
        return label ? label.textContent.replace(/\s*\(optional\)\s*/i, '').trim() : element.name;
    }

    function validateForCalculation(inputs) {
        var errors = nonNegativeErrors(inputs);
        var method = inputs.startingMethod;
        var status = normalizeRetirementStatus(inputs.retirementStatus);
        var copy = copyForStatus(status || 'planning');

        if (!status) {
            errors.push('Choose what best describes your situation today.');
        }

        if (status === 'retired') {
            if (!inputs.currentMonthlySpending || inputs.currentMonthlySpending <= 0) {
                errors.push('Enter current monthly household spending in retirement.');
            }
        } else if (method === 'guided_categories') {
            if (totalValues(inputs.categories, categoryFields) <= 0) {
                errors.push('Enter at least one spending category, even if it is an estimate.');
            }
        } else if (method === 'monthly_estimate') {
            if (!inputs.currentMonthlySpending || inputs.currentMonthlySpending <= 0) {
                errors.push('Enter current monthly household spending.');
            }
        } else if (method === 'annual_estimate') {
            if (!inputs.currentAnnualSpending || inputs.currentAnnualSpending <= 0) {
                errors.push('Enter current annual household spending.');
            }
        } else {
            errors.push('Choose how you would like to estimate spending.');
        }
        if (!inputs.expectedMonthlyRetirementSpending || inputs.expectedMonthlyRetirementSpending <= 0) {
            errors.push(copy.validationMissing);
        }

        return errors;
    }

    function validateForSave(inputs, outputs) {
        var errors = validateForCalculation(inputs);
        if (!outputs || outputs.monthlyRetirementSpendingTarget <= 0) {
            errors.push('Calculate a retirement spending target before saving.');
        }
        return errors;
    }

    function showErrors(errors) {
        if (!errorSummary) return;
        var list = errorSummary.querySelector('ul');
        list.innerHTML = '';
        errors.forEach(function (error) {
            var item = document.createElement('li');
            item.textContent = error;
            list.appendChild(item);
        });
        errorSummary.hidden = errors.length === 0;
        if (errors.length) errorSummary.focus();
    }

    function setResultText(key, value) {
        if (!resultsPanel) return;
        resultsPanel.querySelectorAll('[data-result="' + key + '"]').forEach(function (element) {
            element.textContent = value;
        });
    }

    function currentAnnualSpending(inputs) {
        if (normalizeRetirementStatus(inputs.retirementStatus) === 'retired') {
            return (inputs.currentMonthlySpending || 0) * 12;
        }
        if (inputs.startingMethod === 'monthly_estimate') {
            return (inputs.currentMonthlySpending || 0) * 12;
        }
        if (inputs.startingMethod === 'annual_estimate') {
            return inputs.currentAnnualSpending || 0;
        }
        return totalValues(inputs.categories, categoryFields) * 12;
    }

    function legacyRetirementSpending(record, inputs) {
        var outputs = record && record.outputs && typeof record.outputs === 'object' ? record.outputs : {};
        if (Number(outputs.monthlyRetirementSpendingTarget) > 0) {
            return Number(outputs.monthlyRetirementSpendingTarget);
        }
        var adjustments = inputs && inputs.adjustments && typeof inputs.adjustments === 'object' ? inputs.adjustments : null;
        if (!adjustments) return null;
        var annualCurrent = currentAnnualSpending(inputs);
        var monthlyCurrent = annualCurrent / 12;
        var monthlyNetAdjustments =
            (Number(adjustments.expensesIncreasing) || 0) +
            (Number(adjustments.newRetirementExpenses) || 0) -
            (Number(adjustments.expensesEnding) || 0) -
            (Number(adjustments.expensesDecreasing) || 0);
        return Math.max(monthlyCurrent + monthlyNetAdjustments, 0);
    }

    function calculate(inputs) {
        var annualCurrent = currentAnnualSpending(inputs);
        var monthlyCurrent = annualCurrent / 12;
        var monthlyTarget = normalizeRetirementStatus(inputs.retirementStatus) === 'retired'
            ? Math.max(inputs.currentMonthlySpending || inputs.expectedMonthlyRetirementSpending || 0, 0)
            : Math.max(inputs.expectedMonthlyRetirementSpending || 0, 0);
        var annualTarget = monthlyTarget * 12;
        var monthlyOtherIncome = inputs.monthlyOtherRegularRetirementIncome || 0;
        var annualOtherIncome = monthlyOtherIncome * 12;
        var annualRemaining = Math.max(annualTarget - annualOtherIncome, 0);

        return {
            currentAnnualSpending: annualCurrent,
            currentMonthlySpending: monthlyCurrent,
            expectedMonthlyRetirementSpending: monthlyTarget,
            monthlyRetirementSpendingTarget: monthlyTarget,
            annualRetirementSpendingTarget: annualTarget,
            monthlyOtherRegularRetirementIncome: monthlyOtherIncome,
            annualOtherRegularRetirementIncome: annualOtherIncome,
            monthlyRemainingNeedBeforeSocialSecurityAndInvestments: annualRemaining / 12,
            annualRemainingNeedBeforeSocialSecurityAndInvestments: annualRemaining,
            retirementStatus: normalizeRetirementStatus(inputs.retirementStatus) || 'planning'
        };
    }

    function methodLabel(method) {
        var labels = {
            guided_categories: 'guided category worksheet',
            monthly_estimate: 'known monthly estimate',
            annual_estimate: 'known annual estimate'
        };
        return labels[method] || 'spending estimate';
    }

    function renderResults(inputs, outputs) {
        if (!resultsPanel) return;
        var copy = copyForStatus(inputs.retirementStatus);

        resultsPanel.hidden = false;
        setResultText('monthlyTarget', money(outputs.monthlyRetirementSpendingTarget));
        setResultText('annualTarget', money(outputs.annualRetirementSpendingTarget));
        setResultText('otherIncomeMonthly', money(outputs.monthlyOtherRegularRetirementIncome));
        setResultText('remainingMonthly', money(outputs.monthlyRemainingNeedBeforeSocialSecurityAndInvestments));
        applyRetirementCopy();
        resultsPanel.querySelector('[data-result="assumptions"]').textContent =
            'Based on your ' + methodLabel(inputs.startingMethod) +
            ', ' + copy.assumptionsPhrase +
            ', and pension, annuity, or rental income before Social Security.';
    }

    function checkedMethodInput(value) {
        return form.querySelector('input[name="startingMethod"][value="' + value + '"]');
    }

    function setStartingMethod(value) {
        var methodInput = checkedMethodInput(value);
        if (methodInput) methodInput.checked = true;
    }

    function syncRetiredSpendingValue() {
        var currentInput = getInput('currentMonthlySpending');
        var expectedInput = getInput('expectedMonthlyRetirementSpending');
        if (!currentInput || !expectedInput) return;

        if (selectedRetirementStatus() === 'retired') {
            if (currentInput.value === '' && expectedInput.value !== '') {
                currentInput.value = expectedInput.value;
            }
            return;
        }

        if (expectedInput.value === '' && currentInput.value !== '') {
            expectedInput.value = currentInput.value;
        }
    }

    function syncRetirementPath(statusChanged) {
        var status = selectedRetirementStatus();
        var startMethodSection = form.querySelector('[data-start-method-section]');
        var targetSection = form.querySelector('[data-retirement-target-section]');

        if (status === 'retired') {
            if (statusChanged) lastPlanningMethod = selectedMethod();
            setStartingMethod('monthly_estimate');
            syncRetiredSpendingValue();
            if (startMethodSection) startMethodSection.hidden = true;
            if (targetSection) targetSection.hidden = true;
        } else {
            if (statusChanged && lastPlanningMethod) setStartingMethod(lastPlanningMethod);
            syncRetiredSpendingValue();
            if (startMethodSection) startMethodSection.hidden = false;
            if (targetSection) targetSection.hidden = false;
        }

        applyRetirementCopy();
        syncMethodSections();
        renumberVisibleSteps();
    }

    function renumberVisibleSteps() {
        var step = 1;
        form.querySelectorAll('.calculator-section').forEach(function (section) {
            if (section.hidden) return;
            var eyebrow = section.querySelector(':scope > .eyebrow');
            if (!eyebrow || !/^Step\\s+\\d+/i.test(eyebrow.textContent.trim())) return;
            eyebrow.textContent = 'Step ' + step;
            step += 1;
        });
    }

    function updateVisibleResults() {
        if (!lastOutputs || !resultsPanel || resultsPanel.hidden) return;
        var inputs = inputsFromForm();
        if (validateForCalculation(inputs).length) return;
        lastOutputs = calculate(inputs);
        inputs = inputsFromForm();
        renderResults(inputs, lastOutputs);
    }

    function buildRecord(inputs, outputs, status) {
        var timestamp = status === 'completed' ? now() : null;
        var retirementStatus = normalizeRetirementStatus(inputs.retirementStatus) || 'planning';
        return {
            schemaVersion: 2,
            calculatorId: 'retirement-spending-plan',
            phaseId: 'spending-goals',
            completionStatus: status,
            lastUpdated: timestamp,
            inputs: inputs,
            outputs: outputs || {},
            journeyResult: outputs ? {
                keySummaryResult: {
                    label: 'Monthly retirement spending target',
                    value: outputs.monthlyRetirementSpendingTarget,
                    unit: 'usd_per_month'
                },
                returnDestination: '/phases/spending-goals.php',
                dataForLaterPhases: {
                    monthlyRetirementSpendingTarget: outputs.monthlyRetirementSpendingTarget,
                    annualRetirementSpendingTarget: outputs.annualRetirementSpendingTarget,
                    monthlyOtherRegularRetirementIncome: outputs.monthlyOtherRegularRetirementIncome,
                    retirementStatus: retirementStatus,
                    lastUpdated: timestamp
                }
            } : {
                keySummaryResult: null,
                returnDestination: '/phases/spending-goals.php',
                dataForLaterPhases: {}
            }
        };
    }

    function markPhaseComplete(record) {
        var progress = readProgress();
        progress['spending-goals'] = true;
        progress.records = progress.records && typeof progress.records === 'object' ? progress.records : {};
        progress.records['spending-goals'] = {
            phaseId: 'spending-goals',
            schemaVersion: 1,
            saved: true,
            planningRecordStatus: 'current',
            result: record.journeyResult,
            source: {
                type: 'journey-native-calculator',
                toolId: 'retirement-spending-plan',
                name: 'Your Retirement Spending Plan',
                url: '/calculators/retirement-spending-plan/'
            },
            updatedAt: record.lastUpdated,
            downstreamReady: true
        };
        writeProgress(progress);
    }

    function writeDraft(inputs, outputs) {
        var existing = readCalculatorRecord();
        if (existing.completionStatus === 'completed') {
            existing.draftInputs = inputs;
            existing.draftOutputs = outputs || null;
            existing.hasUnsavedChanges = true;
            writeCalculatorRecord(existing);
            return;
        }
        writeCalculatorRecord(buildRecord(inputs, outputs, 'in_progress'));
    }

    function persistDraft() {
        writeDraft(inputsFromForm(), lastOutputs);
        if (saveStatus) saveStatus.textContent = 'Draft inputs saved in this browser.';
    }

    function calculateAndRender() {
        var inputs = inputsFromForm();
        var errors = validateForCalculation(inputs);
        showErrors(errors);
        if (errors.length) return null;
        // Persist a normalized status once the user has chosen and calculated.
        inputs.retirementStatus = normalizeRetirementStatus(inputs.retirementStatus) || 'planning';
        lastOutputs = calculate(inputs);
        renderResults(inputs, lastOutputs);
        writeDraft(inputs, lastOutputs);
        if (saveStatus) saveStatus.textContent = 'Calculated. Review the result, then save when ready.';
        return { inputs: inputs, outputs: lastOutputs };
    }

    function saveAndReturn(event) {
        event.preventDefault();
        var calculated = calculateAndRender();
        if (!calculated) return;

        var errors = validateForSave(calculated.inputs, calculated.outputs);
        showErrors(errors);
        if (errors.length) return;

        var record = buildRecord(calculated.inputs, calculated.outputs, 'completed');
        writeCalculatorRecord(record);
        markPhaseComplete(record);
        if (saveStatus) saveStatus.textContent = 'Saved. Returning to Phase 1...';
        window.location.href = '/phases/spending-goals.php?spendingPlan=saved';
    }

    function restoreRecord(record) {
        if (!record || (!record.inputs && !record.draftInputs)) return;
        var inputs = record.draftInputs || record.inputs;
        if (!inputs || typeof inputs !== 'object') return;
        var status = inferRetirementStatus(record, inputs);
        if (status) {
            var statusInput = form.querySelector('input[name="retirementStatus"][value="' + status + '"]');
            if (statusInput) statusInput.checked = true;
        }

        if (inputs.startingMethod) lastPlanningMethod = inputs.startingMethod;
        var methodInput = checkedMethodInput(inputs.startingMethod || 'guided_categories');
        if (methodInput) methodInput.checked = true;
        setNumberValue('currentMonthlySpending', inputs.currentMonthlySpending);
        setNumberValue('currentAnnualSpending', inputs.currentAnnualSpending);
        categoryFields.forEach(function (field) {
            setNumberValue(field, inputs.categories && inputs.categories[field]);
        });
        if (inputs.expectedMonthlyRetirementSpending === null || inputs.expectedMonthlyRetirementSpending === undefined) {
            inputs.expectedMonthlyRetirementSpending = legacyRetirementSpending(record, inputs);
        }
        setNumberValue('expectedMonthlyRetirementSpending', inputs.expectedMonthlyRetirementSpending);
        if (status === 'retired') {
            var retiredValue = inputs.expectedMonthlyRetirementSpending || inputs.currentMonthlySpending;
            setNumberValue('currentMonthlySpending', retiredValue);
        }
        setNumberValue('monthlyOtherRegularRetirementIncome', inputs.monthlyOtherRegularRetirementIncome);
        if (inputs.notes) document.getElementById('spendingNotes').value = inputs.notes;
        syncRetirementPath(false);
        var outputs = record.draftOutputs || record.outputs;
        if (outputs && outputs.monthlyRetirementSpendingTarget) {
            // Recalculate so saved essential/flexible fields are ignored and review numbers stay current.
            lastOutputs = calculate(inputsFromForm());
            renderResults(inputsFromForm(), lastOutputs);
        }
        if (saveStatus && record.completionStatus === 'completed') {
            saveStatus.textContent = record.hasUnsavedChanges
                ? 'Draft changes are saved in this browser. Save again to update Phase 1.'
                : 'Saved plan loaded. You can update and save it again anytime.';
        }
    }

    function syncMethodSections() {
        var method = selectedMethod();
        document.querySelectorAll('[data-method-section]').forEach(function (section) {
            section.hidden = section.getAttribute('data-method-section') !== method;
        });
        renumberVisibleSteps();
    }

    document.getElementById('calculateSpendingPlan').addEventListener('click', calculateAndRender);
    form.addEventListener('submit', saveAndReturn);
    form.addEventListener('input', function () {
        updateVisibleResults();
        if (saveStatus) saveStatus.textContent = 'Saving draft...';
        window.clearTimeout(form._draftTimer);
        form._draftTimer = window.setTimeout(persistDraft, 300);
    });
    form.addEventListener('change', function (event) {
        if (event.target.name === 'startingMethod') {
            if (selectedRetirementStatus() !== 'retired') lastPlanningMethod = selectedMethod();
            syncMethodSections();
        }
        if (event.target.name === 'retirementStatus') {
            syncRetirementPath(true);
            updateVisibleResults();
            persistDraft();
        }
    });

    restoreRecord(readCalculatorRecord());
    syncRetirementPath(false);
})();
