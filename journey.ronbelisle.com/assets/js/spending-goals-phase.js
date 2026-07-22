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
            essentialMonthly: outputs.monthlyEssentialSpending,
            flexibleMonthly: outputs.monthlyFlexibleSpending,
            otherIncomeMonthly: outputs.monthlyOtherRegularRetirementIncome,
            lastUpdated: record.lastUpdated
        };

        Object.keys(fields).forEach(function (key) {
            var target = element.querySelector('[data-spending-plan-field="' + key + '"]');
            if (!target) return;
            target.textContent = key === 'lastUpdated' ? dateLabel(fields[key]) : money(Number(fields[key]) || 0);
        });
    }

    function render() {
        var record = readRecord();
        var hasSavedPlan = record.completionStatus === 'completed' &&
            record.outputs &&
            Number(record.outputs.monthlyRetirementSpendingTarget) > 0;

        document.querySelectorAll('[data-spending-plan-summary]').forEach(function (element) {
            element.hidden = !hasSavedPlan;
            if (hasSavedPlan) fillSummary(element, record);
        });

        document.querySelectorAll('[data-spending-plan-empty]').forEach(function (element) {
            element.hidden = hasSavedPlan;
        });
    }

    render();
})();
