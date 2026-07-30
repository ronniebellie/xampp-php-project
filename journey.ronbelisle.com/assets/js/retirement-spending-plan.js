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

    var categoryFields = [
        'housing',
        'foodHousehold',
        'transportation',
        'healthcare',
        'insuranceDebt',
        'lifestyleGiving',
        'otherSpending'
    ];

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

    function selectedMethod() {
        var selected = form.querySelector('input[name="startingMethod"]:checked');
        return selected ? selected.value : 'guided_categories';
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
        if (window.rbJourneySync && typeof window.rbJourneySync.scheduleSave === 'function') {
            window.rbJourneySync.scheduleSave('progress');
        }
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
        if (window.rbJourneySync && typeof window.rbJourneySync.scheduleSave === 'function') {
            window.rbJourneySync.scheduleSave('calculator');
        }
    }

    function positiveNumber(value) {
        var number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : null;
    }

    // Drop obsolete retirementStatus from newly written records while leaving old
    // browser records readable when they still contain the property.
    function sanitizeInputs(inputs) {
        if (!inputs || typeof inputs !== 'object') return {};
        var clean = {};
        Object.keys(inputs).forEach(function (key) {
            if (key === 'retirementStatus') return;
            clean[key] = inputs[key];
        });
        return clean;
    }

    function inputsFromForm() {
        var categories = {};
        categoryFields.forEach(function (field) {
            categories[field] = numberValue(field);
        });

        return sanitizeInputs({
            startingMethod: selectedMethod(),
            currentMonthlySpending: optionalNumberValue('currentMonthlySpending'),
            currentAnnualSpending: optionalNumberValue('currentAnnualSpending'),
            categories: categories,
            expectedMonthlyRetirementSpending: optionalNumberValue('expectedMonthlyRetirementSpending'),
            monthlyOtherRegularRetirementIncome: numberValue('monthlyOtherRegularRetirementIncome'),
            notes: document.getElementById('spendingNotes').value.trim()
        });
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

        if (method === 'guided_categories') {
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
            errors.push('Enter expected monthly household spending in retirement.');
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

    // Preserve usable amounts from older retirementStatus records without treating
    // incomplete legacy data as newly complete.
    function applyLegacySpendingCompatibility(record, inputs) {
        var restored = Object.assign({}, inputs || {});
        var wasRetired = restored.retirementStatus === 'retired';
        var expected = positiveNumber(restored.expectedMonthlyRetirementSpending);
        var current = positiveNumber(restored.currentMonthlySpending);
        var legacyExpected = positiveNumber(legacyRetirementSpending(record, restored));
        var usable = expected || legacyExpected || current;
        var categoryTotal = totalValues(restored.categories || {}, categoryFields);

        if (!expected && usable) {
            restored.expectedMonthlyRetirementSpending = usable;
            expected = usable;
        }

        if (wasRetired) {
            if (!current && expected) {
                restored.currentMonthlySpending = expected;
            }
            if (categoryTotal <= 0) {
                restored.startingMethod = 'monthly_estimate';
            } else if (!restored.startingMethod) {
                restored.startingMethod = 'guided_categories';
            }
        }

        delete restored.retirementStatus;
        return restored;
    }

    function calculate(inputs) {
        var annualCurrent = currentAnnualSpending(inputs);
        var monthlyCurrent = annualCurrent / 12;
        var monthlyTarget = Math.max(inputs.expectedMonthlyRetirementSpending || 0, 0);
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
            annualRemainingNeedBeforeSocialSecurityAndInvestments: annualRemaining
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

        resultsPanel.hidden = false;
        setResultText('monthlyTarget', money(outputs.monthlyRetirementSpendingTarget));
        setResultText('annualTarget', money(outputs.annualRetirementSpendingTarget));
        setResultText('otherIncomeMonthly', money(outputs.monthlyOtherRegularRetirementIncome));
        setResultText('remainingMonthly', money(outputs.monthlyRemainingNeedBeforeSocialSecurityAndInvestments));
        resultsPanel.querySelector('[data-result="assumptions"]').textContent =
            'Based on your ' + methodLabel(inputs.startingMethod) +
            ', expected monthly retirement spending, and pension, annuity, or rental income before Social Security.';
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
        var cleanInputs = sanitizeInputs(inputs);
        return {
            schemaVersion: 2,
            calculatorId: 'retirement-spending-plan',
            phaseId: 'spending-goals',
            completionStatus: status,
            lastUpdated: timestamp,
            inputs: cleanInputs,
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
        var cleanInputs = sanitizeInputs(inputs);
        if (existing.completionStatus === 'completed') {
            existing.draftInputs = cleanInputs;
            existing.draftOutputs = outputs || null;
            existing.hasUnsavedChanges = true;
            if (existing.inputs && typeof existing.inputs === 'object') {
                existing.inputs = sanitizeInputs(existing.inputs);
            }
            if (
                existing.journeyResult &&
                existing.journeyResult.dataForLaterPhases &&
                Object.prototype.hasOwnProperty.call(existing.journeyResult.dataForLaterPhases, 'retirementStatus')
            ) {
                delete existing.journeyResult.dataForLaterPhases.retirementStatus;
            }
            writeCalculatorRecord(existing);
            return;
        }
        writeCalculatorRecord(buildRecord(cleanInputs, outputs, 'in_progress'));
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
        lastOutputs = calculate(inputs);
        inputs = inputsFromForm();
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
        if (window.rbJourneyPhase1 && typeof window.rbJourneyPhase1.reconcileLocal === 'function') {
            window.rbJourneyPhase1.reconcileLocal();
        }
        if (saveStatus) saveStatus.textContent = 'Saved. Returning to Phase 1...';

        function returnToPhase1() {
            window.location.href = '/phases/spending-goals.php?spendingPlan=saved';
        }

        // Persist to cloud before navigation so hydrate cannot overwrite a completed Phase 1.
        if (window.rbJourneySync && typeof window.rbJourneySync.saveNow === 'function') {
            window.rbJourneySync.saveNow('calculator').then(returnToPhase1).catch(returnToPhase1);
            return;
        }
        returnToPhase1();
    }

    function restoreRecord(record) {
        if (!record || (!record.inputs && !record.draftInputs)) return;
        var inputs = applyLegacySpendingCompatibility(record, record.draftInputs || record.inputs);
        if (!inputs || typeof inputs !== 'object') return;

        var methodInput = form.querySelector('input[name="startingMethod"][value="' + (inputs.startingMethod || 'guided_categories') + '"]');
        if (methodInput) methodInput.checked = true;
        setNumberValue('currentMonthlySpending', inputs.currentMonthlySpending);
        setNumberValue('currentAnnualSpending', inputs.currentAnnualSpending);
        categoryFields.forEach(function (field) {
            setNumberValue(field, inputs.categories && inputs.categories[field]);
        });
        setNumberValue('expectedMonthlyRetirementSpending', inputs.expectedMonthlyRetirementSpending);
        setNumberValue('monthlyOtherRegularRetirementIncome', inputs.monthlyOtherRegularRetirementIncome);
        if (inputs.notes) document.getElementById('spendingNotes').value = inputs.notes;

        var outputs = record.draftOutputs || record.outputs;
        var formInputs = inputsFromForm();
        if (outputs && outputs.monthlyRetirementSpendingTarget && validateForCalculation(formInputs).length === 0) {
            lastOutputs = calculate(formInputs);
            renderResults(formInputs, lastOutputs);
        }
        if (saveStatus && record.completionStatus === 'completed') {
            var needsReview = validateForCalculation(formInputs).length > 0;
            if (needsReview) {
                saveStatus.textContent = 'Saved plan loaded. Review current and expected retirement spending, then calculate and save again.';
            } else {
                saveStatus.textContent = record.hasUnsavedChanges
                    ? 'Draft changes are saved in this browser. Save again to update Phase 1.'
                    : 'Saved plan loaded. You can update and save it again anytime.';
            }
        }
    }

    function syncMethodSections() {
        var method = selectedMethod();
        document.querySelectorAll('[data-method-section]').forEach(function (section) {
            section.hidden = section.getAttribute('data-method-section') !== method;
        });
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
        if (event.target.name === 'startingMethod') syncMethodSections();
    });

    function bootSpendingPlanner() {
        restoreRecord(readCalculatorRecord());
        syncMethodSections();
    }

    if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
        window.rbJourneySync.afterReady(bootSpendingPlanner);
    } else {
        bootSpendingPlanner();
    }
})();
