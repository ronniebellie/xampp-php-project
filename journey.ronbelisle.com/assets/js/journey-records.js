(function () {
    'use strict';

    var schemaVersion = 1;
    var statusLabels = {
        current: 'Current',
        'needs-information': 'Needs information',
        'needs-verification': 'Needs verification',
        'already-receiving': 'Already receiving benefits'
    };

    function now() {
        return new Date().toISOString();
    }

    function socialSecurityStatus(record) {
        if (record.decisionStatus === 'need-more-information') return 'needs-information';
        if (record.decisionStatus === 'already-receiving') return 'already-receiving';
        if (record.decisionStatus !== 'provisional') return '';

        var outstanding = Array.isArray(record.verificationNeeded) ? record.verificationNeeded : [];
        var needsVerification = outstanding.some(function (item) {
            return item && item !== 'nothing-yet';
        });
        return needsVerification ? 'needs-verification' : 'current';
    }

    function socialSecurityResult(record) {
        return {
            decisionStatus: record.decisionStatus || '',
            birthYear: record.birthYear === undefined ? null : record.birthYear,
            claimAge: record.claimAge === undefined ? null : record.claimAge,
            benefitAtFra: record.benefitAtFra === undefined ? null : record.benefitAtFra,
            estimatedMonthlyBenefit: record.estimatedMonthlyBenefit === undefined ? null : record.estimatedMonthlyBenefit,
            currentMonthlyBenefit: record.currentMonthlyBenefit === undefined ? null : record.currentMonthlyBenefit,
            rationale: record.rationale || '',
            mainTradeoff: record.mainTradeoff || '',
            otherTradeoff: record.otherTradeoff || '',
            estimateReadiness: record.estimateReadiness || '',
            companionAnswers: record.companionAnswers || {},
            companionRecommendation: record.companionRecommendation || ''
        };
    }

    function createSocialSecurityRecord(record, options) {
        var settings = options || {};
        var oldRecord = settings.oldRecord || {};
        var timestamp = settings.timestamp || now();
        var status = socialSecurityStatus(record);
        var complete = settings.journeyComplete === true;
        var saved = record.saved === true;
        var firstCreated = oldRecord.createdAt || oldRecord.completedAt || (saved ? timestamp : '');
        var legacyReviewedAt = oldRecord.schemaVersion ? '' : (oldRecord.completedAt || '');
        var reviewedAt = settings.reviewed === true
            ? timestamp
            : (oldRecord.lastReviewedAt || legacyReviewedAt);
        var outstandingItems = Array.isArray(record.verificationNeeded) ? record.verificationNeeded.slice() : [];

        return Object.assign({}, record, {
            phaseId: 'social-security',
            schemaVersion: schemaVersion,
            journeyCompletionStatus: complete ? 'completed' : 'incomplete',
            planningRecordStatus: status,
            result: socialSecurityResult(record),
            outstanding: {
                items: outstandingItems,
                priority: record.verificationPriority || ''
            },
            source: {
                type: 'calculator',
                toolId: 'social-security-claiming-analyzer',
                name: 'Social Security Claiming Analyzer',
                url: 'https://ronbelisle.com/social-security-claiming-analyzer/'
            },
            createdAt: firstCreated,
            updatedAt: saved ? timestamp : (oldRecord.updatedAt || ''),
            lastReviewedAt: reviewedAt,
            downstreamReady: status === 'current' || status === 'needs-verification' || status === 'already-receiving',
            reviewReasons: [
                'Planned retirement date changes',
                'A different claiming age is considered',
                'A new Social Security statement becomes available',
                'An earnings record or benefit estimate changes',
                'A spouse’s claiming plan changes',
                'Benefits begin',
                'Annual retirement-plan review'
            ],
            downstreamMappings: {
                phase: 'build-your-plan',
                tool: 'retirement-plan-builder',
                fields: {
                    claimAge: 'socialSecurity.claimAge',
                    benefitAtFra: 'socialSecurity.benefitAtFra',
                    monthlyBenefit: 'socialSecurity.monthlyBenefit',
                    alreadyReceiving: 'socialSecurity.alreadyReceiving'
                }
            }
        });
    }

    function normalizeSocialSecurityRecord(record, journeyComplete) {
        if (!record || typeof record !== 'object') return {};
        var normalized = Object.assign({}, record);
        var result = record.result && typeof record.result === 'object' ? record.result : {};
        var outstanding = record.outstanding && typeof record.outstanding === 'object' ? record.outstanding : {};

        [
            'decisionStatus',
            'birthYear',
            'claimAge',
            'benefitAtFra',
            'estimatedMonthlyBenefit',
            'currentMonthlyBenefit',
            'rationale',
            'mainTradeoff',
            'otherTradeoff',
            'estimateReadiness',
            'companionAnswers',
            'companionRecommendation'
        ].forEach(function (key) {
            if ((normalized[key] === undefined || normalized[key] === null) && result[key] !== undefined) {
                normalized[key] = result[key];
            }
        });

        if (!Array.isArray(normalized.verificationNeeded) && Array.isArray(outstanding.items)) {
            normalized.verificationNeeded = outstanding.items;
        }
        if (!normalized.verificationPriority && outstanding.priority) {
            normalized.verificationPriority = outstanding.priority;
        }

        return createSocialSecurityRecord(normalized, {
            oldRecord: record,
            journeyComplete: journeyComplete,
            timestamp: record.updatedAt || record.lastReviewedAt || record.completedAt || now(),
            reviewed: false
        });
    }

    function recordStatus(progress, phaseId) {
        if (!progress || !progress.records || typeof progress.records !== 'object') return '';
        var record = progress.records[phaseId];
        if (!record || typeof record !== 'object' || record.saved !== true) return '';
        if (record.planningRecordStatus) return record.planningRecordStatus;
        if (phaseId === 'social-security') return socialSecurityStatus(record);
        return '';
    }

    window.rbJourneyRecords = {
        schemaVersion: schemaVersion,
        statusLabels: statusLabels,
        statusLabel: function (status) {
            return statusLabels[status] || '';
        },
        socialSecurityStatus: socialSecurityStatus,
        createSocialSecurityRecord: createSocialSecurityRecord,
        normalizeSocialSecurityRecord: normalizeSocialSecurityRecord,
        recordStatus: recordStatus
    };
})();
