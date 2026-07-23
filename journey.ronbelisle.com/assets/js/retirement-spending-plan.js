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

    function setTextAll(root, selector, value) {
        if (!root) return;
        root.querySelectorAll(selector).forEach(function (element) {
            element.textContent = value;
        });
    }

    function setClassState(element, state) {
        if (!element) return;
        element.classList.toggle('is-complete', state === 'complete');
        element.classList.toggle('is-negative', state === 'negative');
        element.classList.toggle('is-error', state === 'negative');
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
        categoryFields.forEach(function (field) {
            categories[field] = numberValue(field);
        });

        return {
            startingMethod: selectedMethod(),
            currentMonthlySpending: optionalNumberValue('currentMonthlySpending'),
            currentAnnualSpending: optionalNumberValue('currentAnnualSpending'),
            categories: categories,
            expectedMonthlyRetirementSpending: optionalNumberValue('expectedMonthlyRetirementSpending'),
            essentialMonthlySpending: optionalNumberValue('essentialMonthlySpending'),
            flexibleMonthlySpending: optionalNumberValue('flexibleMonthlySpending'),
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
            'essentialMonthlySpending',
            'flexibleMonthlySpending',
            'monthlyOtherRegularRetirementIncome'
        ]);

        fields.forEach(function (field) {
            var element = document.getElementById(field);
            if (!element || element.value === '') return;
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

    function splitConsistency(outputs) {
        if (!outputs) {
            return {
                isValid: false,
                splitTotal: 0,
                remainingToAllocate: 0,
                difference: 0,
                tolerance: 1,
                target: 0
            };
        }
        var splitTotal = (Number(outputs.monthlyEssentialSpending) || 0) +
            (Number(outputs.monthlyFlexibleSpending) || 0);
        var target = Number(outputs.monthlyRetirementSpendingTarget) || 0;
        var difference = splitTotal - target;
        var tolerance = 1;

        return {
            isValid: Math.abs(difference) <= tolerance,
            splitTotal: splitTotal,
            remainingToAllocate: -difference,
            difference: difference,
            tolerance: tolerance,
            target: target
        };
    }

    function consistencyText(outputs) {
        var check = splitConsistency(outputs);
        var target = money(check.target);
        var allocated = money(check.splitTotal);

        if (check.isValid) {
            return '✓ Your monthly retirement spending target is fully allocated.';
        }

        if (check.remainingToAllocate > 0) {
            return 'You have allocated ' + allocated +
                ' of your ' + target +
                ' monthly target. Allocate the remaining ' +
                money(check.remainingToAllocate) +
                ' before continuing.';
        }

        return 'You have allocated ' + allocated +
            ', which is ' + money(Math.abs(check.remainingToAllocate)) +
            ' more than your ' + target +
            ' monthly target. Adjust the amounts before continuing.';
    }

    function liveAllocationState() {
        var target = numberValue('expectedMonthlyRetirementSpending');
        var essential = numberValue('essentialMonthlySpending');
        var flexible = numberValue('flexibleMonthlySpending');
        var splitTotal = essential + flexible;
        var remaining = target - splitTotal;
        var tolerance = 1;
        var isComplete = target > 0 && Math.abs(remaining) <= tolerance;
        var isOver = remaining < -tolerance;
        var isUnder = remaining > tolerance;

        return {
            target: target,
            splitTotal: splitTotal,
            remaining: remaining,
            overage: Math.abs(Math.min(remaining, 0)),
            isComplete: isComplete,
            isOver: isOver,
            isUnder: isUnder
        };
    }

    function updateAllocationLive() {
        var panel = document.getElementById('allocationLivePanel');
        if (!panel) return;

        var state = liveAllocationState();
        var remainingEl = panel.querySelector('[data-allocation="remaining"]');
        var remainingLabel = panel.querySelector('[data-allocation="remaining-label"]');
        var messageEl = panel.querySelector('[data-allocation="message"]');

        setTextAll(panel, '[data-allocation="target"]', money(state.target));
        setTextAll(panel, '[data-allocation="allocated"]', money(state.splitTotal));

        if (remainingLabel) {
            remainingLabel.textContent = state.isOver ? 'Amount over target' : 'Remaining to allocate';
        }

        if (remainingEl) {
            if (state.isComplete) {
                remainingEl.textContent = money(0);
                setClassState(remainingEl, 'complete');
            } else if (state.isOver) {
                remainingEl.textContent = money(state.overage);
                setClassState(remainingEl, 'negative');
            } else {
                remainingEl.textContent = money(Math.max(state.remaining, 0));
                setClassState(remainingEl, '');
            }
        }

        if (!messageEl) return;

        messageEl.classList.remove('is-valid', 'is-warning', 'is-error');

        if (!state.target) {
            messageEl.textContent = 'Enter your expected monthly retirement spending in Step 3 to set the target you will allocate here.';
            messageEl.setAttribute('role', 'status');
            return;
        }

        if (state.isComplete) {
            messageEl.textContent = '✓ Your monthly retirement spending target is fully allocated.';
            messageEl.classList.add('is-valid');
            messageEl.setAttribute('role', 'status');
            return;
        }

        if (state.isOver) {
            messageEl.textContent = 'You have allocated ' + money(state.overage) +
                ' more than your monthly target.';
            messageEl.classList.add('is-error');
            messageEl.setAttribute('role', 'alert');
            return;
        }

        messageEl.textContent = 'Allocate the remaining ' + money(state.remaining) +
            ' between essential and flexible spending.';
        if (state.isUnder && state.splitTotal > 0) {
            messageEl.classList.add('is-warning');
        }
        messageEl.setAttribute('role', 'status');
    }

    function validateForSave(inputs, outputs) {
        var errors = validateForCalculation(inputs);
        if (!outputs || outputs.monthlyRetirementSpendingTarget <= 0) {
            errors.push('Calculate a retirement spending target before saving.');
        }
        if (outputs) {
            if (!splitConsistency(outputs).isValid) {
                errors.push(consistencyText(outputs));
            }
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

    function suggestedEssential(inputs, monthlyTarget) {
        if (inputs.startingMethod === 'guided_categories') {
            var categories = inputs.categories;
            var essential = (categories.housing || 0) +
                (categories.foodHousehold || 0) +
                (categories.transportation || 0) +
                (categories.healthcare || 0) +
                (categories.insuranceDebt || 0);
            return Math.min(essential, monthlyTarget);
        }
        return Math.round(monthlyTarget * 0.75);
    }

    function calculate(inputs) {
        var annualCurrent = currentAnnualSpending(inputs);
        var monthlyCurrent = annualCurrent / 12;
        var monthlyTarget = Math.max(inputs.expectedMonthlyRetirementSpending || 0, 0);
        var annualTarget = monthlyTarget * 12;

        var essential = inputs.essentialMonthlySpending;
        var flexible = inputs.flexibleMonthlySpending;
        if (essential === null && flexible === null) {
            essential = suggestedEssential(inputs, monthlyTarget);
            flexible = Math.max(monthlyTarget - essential, 0);
            setNumberValue('essentialMonthlySpending', Math.round(essential));
            setNumberValue('flexibleMonthlySpending', Math.round(flexible));
        } else if (essential !== null && flexible === null) {
            flexible = Math.max(monthlyTarget - essential, 0);
            setNumberValue('flexibleMonthlySpending', Math.round(flexible));
        } else if (essential === null && flexible !== null) {
            essential = Math.max(monthlyTarget - flexible, 0);
            setNumberValue('essentialMonthlySpending', Math.round(essential));
        }

        essential = Number(essential) || 0;
        flexible = Number(flexible) || 0;

        var monthlyOtherIncome = inputs.monthlyOtherRegularRetirementIncome || 0;
        var annualOtherIncome = monthlyOtherIncome * 12;
        var annualRemaining = Math.max(annualTarget - annualOtherIncome, 0);

        return {
            currentAnnualSpending: annualCurrent,
            currentMonthlySpending: monthlyCurrent,
            expectedMonthlyRetirementSpending: monthlyTarget,
            monthlyRetirementSpendingTarget: monthlyTarget,
            annualRetirementSpendingTarget: annualTarget,
            monthlyEssentialSpending: essential,
            annualEssentialSpending: essential * 12,
            monthlyFlexibleSpending: flexible,
            annualFlexibleSpending: flexible * 12,
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
        var consistency = splitConsistency(outputs);
        var consistencyMessage = resultsPanel.querySelector('[data-result="consistencyMessage"]');

        resultsPanel.hidden = false;
        setResultText('monthlyTarget', money(outputs.monthlyRetirementSpendingTarget));
        setResultText('allocationTargetMonthly', money(outputs.monthlyRetirementSpendingTarget));
        setResultText('annualTarget', money(outputs.annualRetirementSpendingTarget));
        setResultText('essentialMonthly', money(outputs.monthlyEssentialSpending));
        setResultText('flexibleMonthly', money(outputs.monthlyFlexibleSpending));
        setResultText('splitTotalMonthly', money(consistency.splitTotal));
        setResultText('otherIncomeMonthly', money(outputs.monthlyOtherRegularRetirementIncome));
        setResultText('remainingMonthly', money(outputs.monthlyRemainingNeedBeforeSocialSecurityAndInvestments));
        resultsPanel.querySelector('[data-result="assumptions"]').textContent =
            'Based on your ' + methodLabel(inputs.startingMethod) +
            ', expected monthly retirement spending, and pension, annuity, or rental income before Social Security.';

        if (consistencyMessage) {
            consistencyMessage.classList.toggle('is-valid', consistency.isValid);
            consistencyMessage.classList.toggle('is-warning', !consistency.isValid);
            consistencyMessage.setAttribute('role', consistency.isValid ? 'status' : 'alert');
            consistencyMessage.textContent = consistencyText(outputs);
        }

        updateAllocationLive();
    }

    function updateVisibleResults() {
        updateAllocationLive();
        if (!lastOutputs || !resultsPanel || resultsPanel.hidden) return;
        var inputs = inputsFromForm();
        if (validateForCalculation(inputs).length) return;
        lastOutputs = calculate(inputs);
        inputs = inputsFromForm();
        renderResults(inputs, lastOutputs);
    }

    function buildRecord(inputs, outputs, status) {
        var timestamp = status === 'completed' ? now() : null;
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
                    monthlyEssentialSpending: outputs.monthlyEssentialSpending,
                    monthlyFlexibleSpending: outputs.monthlyFlexibleSpending,
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
        if (saveStatus) saveStatus.textContent = 'Saved. Returning to Phase 1...';
        window.location.href = '/phases/spending-goals.php?spendingPlan=saved';
    }

    function restoreRecord(record) {
        if (!record || !record.inputs) return;
        var inputs = record.draftInputs || record.inputs;
        var methodInput = form.querySelector('input[name="startingMethod"][value="' + (inputs.startingMethod || 'guided_categories') + '"]');
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
        setNumberValue('essentialMonthlySpending', inputs.essentialMonthlySpending);
        setNumberValue('flexibleMonthlySpending', inputs.flexibleMonthlySpending);
        setNumberValue('monthlyOtherRegularRetirementIncome', inputs.monthlyOtherRegularRetirementIncome);
        if (inputs.notes) document.getElementById('spendingNotes').value = inputs.notes;
        var outputs = record.draftOutputs || record.outputs;
        if (outputs && outputs.monthlyRetirementSpendingTarget) {
            lastOutputs = outputs;
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
        updateAllocationLive();
    });

    restoreRecord(readCalculatorRecord());
    syncMethodSections();
    updateAllocationLive();
})();
