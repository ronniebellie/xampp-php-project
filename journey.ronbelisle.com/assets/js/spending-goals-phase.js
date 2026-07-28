(function () {
    'use strict';

    var calculatorKey = 'rbJourneyCalculator:retirementSpendingPlan:v1';

    function readRecord() {
        try {
            var parsed = JSON.parse(localStorage.getItem(calculatorKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function retirementStatus(record) {
        var inputs = record && record.inputs ? record.inputs : {};
        if (inputs.retirementStatus === 'retired' || inputs.retirementStatus === 'planning') {
            return inputs.retirementStatus;
        }
        var later = record && record.journeyResult && record.journeyResult.dataForLaterPhases;
        if (later && (later.retirementStatus === 'retired' || later.retirementStatus === 'planning')) {
            return later.retirementStatus;
        }
        if (record && record.inputs && !Object.prototype.hasOwnProperty.call(record.inputs, 'retirementStatus')) {
            return 'planning';
        }
        return 'planning';
    }

    function applyRetirementCopy(record) {
        var retired = retirementStatus(record) === 'retired';
        var copy = retired ? {
            'phase1-lede': 'Use this phase to refine the living-expense target that supports your life in retirement. You’ll review household expenses, revisit lifestyle goals, and keep your plan grounded in what daily life actually costs.',
            'phase1-complete-lede': 'You’ve created the monthly living-expense target that the rest of your retirement plan will build on.'
        } : {
            'phase1-lede': 'Use this phase to shape your retirement spending plan. You’ll estimate your household living expenses, think through your retirement lifestyle goals, and gather what you need for a realistic plan.',
            'phase1-complete-lede': 'You’ve created the monthly living-expense target that the rest of your retirement plan will build on.'
        };
        document.querySelectorAll('[data-retirement-copy]').forEach(function (element) {
            var key = element.getAttribute('data-retirement-copy');
            if (copy[key]) element.textContent = copy[key];
        });
    }

    function money(value) {
        if (!Number.isFinite(value)) return '$0';
        return value.toLocaleString(undefined, {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        });
    }

    function dateLabel(value) {
        if (!value) return 'Not saved yet';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Recently saved';
        return date.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function fillSummary(element, record) {
        var outputs = record.outputs || {};
        var fields = {
            monthlyTarget: outputs.monthlyRetirementSpendingTarget,
            annualTarget: outputs.annualRetirementSpendingTarget,
            otherIncomeMonthly: outputs.monthlyOtherRegularRetirementIncome,
            lastUpdated: record.lastUpdated
        };

        Object.keys(fields).forEach(function (key) {
            var target = element.querySelector('[data-spending-plan-field="' + key + '"]');
            if (!target) return;
            target.textContent = key === 'lastUpdated' ? dateLabel(fields[key]) : money(Number(fields[key]) || 0);
        });
    }

    function freshSaveFlag() {
        try {
            return new URLSearchParams(window.location.search).get('spendingPlan') === 'saved';
        } catch (error) {
            return false;
        }
    }

    function clearFreshSaveFlag() {
        if (!window.history || typeof window.history.replaceState !== 'function') return;
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('spendingPlan');
            window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
        } catch (error) {
            return;
        }
    }

    function renderSuccessMessage(show) {
        document.querySelectorAll('[data-spending-plan-success]').forEach(function (element) {
            element.hidden = !show;
        });
    }

    function render() {
        var record = readRecord();
        applyRetirementCopy(record);
        var hasSavedPlan = record.completionStatus === 'completed' &&
            record.outputs &&
            Number(record.outputs.monthlyRetirementSpendingTarget) > 0;
        var showFreshSave = hasSavedPlan && freshSaveFlag();

        document.querySelectorAll('[data-spending-plan-summary]').forEach(function (element) {
            element.hidden = !hasSavedPlan;
            if (hasSavedPlan) fillSummary(element, record);
        });

        document.querySelectorAll('[data-spending-plan-empty]').forEach(function (element) {
            element.hidden = hasSavedPlan;
        });

        renderSuccessMessage(showFreshSave);
        if (showFreshSave) clearFreshSaveFlag();
    }

    render();
})();
