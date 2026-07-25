(function () {
    'use strict';

    var schemaVersion = 1;
    var statusLabels = {
        current: 'Current',
        'needs-information': 'Needs information',
        'needs-verification': 'Needs verification',
        'already-receiving': 'Already receiving benefits',
        workable: 'Looks workable',
        close: 'Looks close',
        difficult: 'Looks difficult'
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
            decisionNotes: record.decisionNotes || '',
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
                tool: 'journey-native-phase-3',
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
            'decisionNotes',
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

    function buildYourPlanResult(record) {
        return {
            monthlyRetirementSpendingGoal: record.monthlyRetirementSpendingGoal === undefined ? null : record.monthlyRetirementSpendingGoal,
            annualRetirementSpendingGoal: record.annualRetirementSpendingGoal === undefined ? null : record.annualRetirementSpendingGoal,
            monthlySocialSecurityAssumption: record.monthlySocialSecurityAssumption === undefined ? null : record.monthlySocialSecurityAssumption,
            socialSecuritySource: record.socialSecuritySource || '',
            temporarySocialSecurityEstimateUsed: record.temporarySocialSecurityEstimateUsed === true,
            monthlyOtherDependableIncome: record.monthlyOtherDependableIncome === undefined ? null : record.monthlyOtherDependableIncome,
            monthlyNeededFromRetirementSavings: record.monthlyNeededFromRetirementSavings === undefined ? null : record.monthlyNeededFromRetirementSavings,
            annualNeededFromRetirementSavings: record.annualNeededFromRetirementSavings === undefined ? null : record.annualNeededFromRetirementSavings,
            retirementSavingsBalance: record.retirementSavingsBalance === undefined ? null : record.retirementSavingsBalance,
            impliedInitialWithdrawalRate: record.impliedInitialWithdrawalRate === undefined ? null : record.impliedInitialWithdrawalRate,
            baseCaseAssessment: record.baseCaseAssessment || '',
            assessmentStatus: record.assessmentStatus || '',
            baseCaseOnly: record.baseCaseOnly !== false
        };
    }

    function createBuildYourPlanRecord(record, options) {
        var settings = options || {};
        var oldRecord = settings.oldRecord || {};
        var timestamp = settings.timestamp || now();
        var complete = settings.journeyComplete === true;
        var saved = record.saved === true;
        var firstCreated = oldRecord.createdAt || oldRecord.completedAt || (saved ? timestamp : '');
        var status = record.planningRecordStatus || record.baseCaseAssessment || 'needs-information';

        return Object.assign({}, record, {
            phaseId: 'build-your-plan',
            schemaVersion: schemaVersion,
            journeyCompletionStatus: complete ? 'completed' : 'incomplete',
            planningRecordStatus: status,
            result: buildYourPlanResult(record),
            baseCaseOnly: true,
            source: {
                type: 'journey-native',
                toolId: 'build-your-plan',
                name: 'Build Your Plan',
                url: '/phases/build-your-plan.php'
            },
            createdAt: firstCreated,
            updatedAt: saved ? timestamp : (oldRecord.updatedAt || ''),
            lastReviewedAt: settings.reviewed === true ? timestamp : (oldRecord.lastReviewedAt || ''),
            downstreamReady: saved === true && record.assessmentStatus === 'complete',
            downstreamMappings: {
                phase: 'stress-test',
                tool: 'journey-native-phase-4',
                fields: {
                    spendingGoal: 'plan.monthlyRetirementSpendingGoal',
                    socialSecurity: 'plan.monthlySocialSecurityAssumption',
                    otherIncome: 'plan.monthlyOtherDependableIncome',
                    savingsNeed: 'plan.monthlyNeededFromRetirementSavings',
                    savingsBalance: 'plan.retirementSavingsBalance'
                }
            }
        });
    }

    function normalizeBuildYourPlanRecord(record, journeyComplete) {
        if (!record || typeof record !== 'object') return {};
        var normalized = Object.assign({}, record);
        var result = record.result && typeof record.result === 'object' ? record.result : {};

        Object.keys(buildYourPlanResult({})).forEach(function (key) {
            if ((normalized[key] === undefined || normalized[key] === null) && result[key] !== undefined) {
                normalized[key] = result[key];
            }
        });

        return createBuildYourPlanRecord(normalized, {
            oldRecord: record,
            journeyComplete: journeyComplete,
            timestamp: record.updatedAt || record.lastReviewedAt || record.completedAt || now(),
            reviewed: false
        });
    }

    var stressStatusLabels = {
        holds: 'Holds up reasonably well',
        sensitive: 'Sensitive to one or more risks',
        needs: 'Needs meaningful adjustment'
    };

    function createStressTestRecord(record, options) {
        var settings = options || {};
        var oldRecord = settings.oldRecord || {};
        var timestamp = settings.timestamp || now();
        var complete = settings.journeyComplete === true;
        var saved = record.saved === true;
        var firstCreated = oldRecord.createdAt || oldRecord.completedAt || (saved ? timestamp : '');

        return Object.assign({}, record, {
            phaseId: 'stress-test',
            schemaVersion: schemaVersion,
            journeyCompletionStatus: complete ? 'completed' : 'incomplete',
            planningRecordStatus: record.overallResilienceCode || oldRecord.planningRecordStatus || '',
            decisionStatement: record.decisionStatement ||
                'I’ve reviewed how sensitive my Phase 3 plan is, and I’m carrying this resilience review forward.',
            educationalNonGuarantee: record.educationalNonGuarantee !== false,
            disclaimer: record.disclaimer ||
                'These tests are educational. They do not predict markets or guarantee outcomes.',
            source: {
                type: 'journey-native',
                toolId: 'stress-test',
                name: 'Stress Test',
                url: '/phases/stress-test.php'
            },
            createdAt: firstCreated,
            updatedAt: saved ? timestamp : (oldRecord.updatedAt || ''),
            lastReviewedAt: settings.reviewed === true ? timestamp : (oldRecord.lastReviewedAt || ''),
            downstreamReady: saved === true && !!record.overallResilienceCode,
            downstreamMappings: {
                phase: 'tax-strategy',
                tool: 'journey-native-phase-5',
                fields: {}
            }
        });
    }

    function normalizeStressTestRecord(record, journeyComplete) {
        if (!record || typeof record !== 'object') return {};
        return createStressTestRecord(Object.assign({}, record), {
            oldRecord: record,
            journeyComplete: journeyComplete,
            timestamp: record.updatedAt || record.lastReviewedAt || record.completedAt || now(),
            reviewed: false
        });
    }

    var taxIssueStatusLabels = {
        tax_deferred_pressure: 'Tax-deferred withdrawal pressure',
        gross_vs_spendable: 'Gross vs spendable withdrawals',
        rmd_attention: 'RMD attention',
        roth_review: 'Roth planning review',
        ss_income_interaction: 'Social Security income interaction',
        account_mix_unclear: 'Account mix unclear',
        none_dominant: 'No single tax issue stood out'
    };

    function createTaxStrategyRecord(record, options) {
        var settings = options || {};
        var oldRecord = settings.oldRecord || {};
        var timestamp = settings.timestamp || now();
        var complete = settings.journeyComplete === true;
        var saved = record.saved === true;
        var firstCreated = oldRecord.createdAt || oldRecord.completedAt || (saved ? timestamp : '');
        var primaryIssue = '';
        if (record.result && Array.isArray(record.result.mainIssueIds) && record.result.mainIssueIds.length) {
            primaryIssue = record.result.mainIssueIds[0];
        } else if (Array.isArray(record.mainIssueIds) && record.mainIssueIds.length) {
            primaryIssue = record.mainIssueIds[0];
        }

        return Object.assign({}, record, {
            phaseId: 'tax-strategy',
            schemaVersion: schemaVersion,
            journeyCompletionStatus: complete ? 'completed' : 'incomplete',
            planningRecordStatus: primaryIssue || oldRecord.planningRecordStatus || '',
            decisionStatement: record.decisionStatement ||
                'This is the tax-planning priority I want to carry forward before I rely on my withdrawal plan.',
            companionExplanation: record.companionExplanation ||
                'I’ve reviewed how taxes may affect my Phase 3 plan. I’m carrying forward one priority to revisit, not a finished tax strategy.',
            educationalNonAdvice: record.educationalNonAdvice !== false,
            notAFinishedTaxStrategy: record.notAFinishedTaxStrategy !== false,
            source: {
                type: 'journey-native',
                toolId: 'tax-strategy',
                name: 'Tax Strategy',
                url: '/phases/tax-strategy.php'
            },
            createdAt: firstCreated,
            updatedAt: saved ? timestamp : (oldRecord.updatedAt || ''),
            lastReviewedAt: settings.reviewed === true ? timestamp : (oldRecord.lastReviewedAt || ''),
            downstreamReady: saved === true && !!primaryIssue,
            downstreamMappings: {
                phase: 'survivor-planning',
                tool: 'journey-native-phase-6',
                fields: {}
            }
        });
    }

    function normalizeTaxStrategyRecord(record, journeyComplete) {
        if (!record || typeof record !== 'object') return {};
        return createTaxStrategyRecord(Object.assign({}, record), {
            oldRecord: record,
            journeyComplete: journeyComplete,
            timestamp: record.updatedAt || record.lastReviewedAt || record.completedAt || now(),
            reviewed: false
        });
    }

    var survivorIssueStatusLabels = {
        beneficiary_review: 'Account-recipient review',
        survivor_income_review: 'Survivor income review',
        social_security_change: 'Social Security change',
        survivor_spending_look: 'Survivor spending look',
        none_dominant: 'No single survivor issue stood out'
    };

    function createSurvivorPlanningRecord(record, options) {
        var settings = options || {};
        var oldRecord = settings.oldRecord || {};
        var timestamp = settings.timestamp || now();
        var complete = settings.journeyComplete === true;
        var saved = record.saved === true;
        var firstCreated = oldRecord.createdAt || oldRecord.completedAt || (saved ? timestamp : '');
        var primaryIssue = '';
        if (record.result && Array.isArray(record.result.mainIssueIds) && record.result.mainIssueIds.length) {
            primaryIssue = record.result.mainIssueIds[0];
        } else if (Array.isArray(record.mainIssueIds) && record.mainIssueIds.length) {
            primaryIssue = record.mainIssueIds[0];
        }

        return Object.assign({}, record, {
            phaseId: 'survivor-planning',
            schemaVersion: schemaVersion,
            journeyCompletionStatus: complete ? 'completed' : 'incomplete',
            planningRecordStatus: primaryIssue || oldRecord.planningRecordStatus || '',
            decisionStatement: record.decisionStatement ||
                'This is the survivor-planning priority I want to carry forward for our household plan.',
            companionExplanation: record.companionExplanation ||
                'I’ve reviewed how our retirement income plan may change if one of us dies. I’m carrying forward one priority to revisit—not a finished estate plan.',
            educationalNonAdvice: record.educationalNonAdvice !== false,
            notAnEstatePlan: record.notAnEstatePlan !== false,
            source: {
                type: 'journey-native',
                toolId: 'survivor-planning',
                name: 'Survivor Planning',
                url: '/phases/survivor-planning.php'
            },
            createdAt: firstCreated,
            updatedAt: saved ? timestamp : (oldRecord.updatedAt || ''),
            lastReviewedAt: settings.reviewed === true ? timestamp : (oldRecord.lastReviewedAt || ''),
            downstreamReady: saved === true && !!primaryIssue,
            downstreamMappings: {
                phase: 'premium-workspace',
                tool: 'journey-ongoing-planning',
                fields: {}
            }
        });
    }

    function normalizeSurvivorPlanningRecord(record, journeyComplete) {
        if (!record || typeof record !== 'object') return {};
        return createSurvivorPlanningRecord(Object.assign({}, record), {
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
        if (record.planningRecordStatus) {
            if (phaseId === 'stress-test') {
                return stressStatusLabels[record.planningRecordStatus] || record.planningRecordStatus;
            }
            if (phaseId === 'tax-strategy') {
                return taxIssueStatusLabels[record.planningRecordStatus] || record.planningRecordStatus;
            }
            if (phaseId === 'survivor-planning') {
                return survivorIssueStatusLabels[record.planningRecordStatus] || record.planningRecordStatus;
            }
            return record.planningRecordStatus;
        }
        if (phaseId === 'social-security') return socialSecurityStatus(record);
        if (phaseId === 'build-your-plan') return record.baseCaseAssessment || '';
        if (phaseId === 'stress-test') {
            return stressStatusLabels[record.overallResilienceCode] || record.overallResilienceCode || '';
        }
        if (phaseId === 'tax-strategy') {
            var ids = record.result && record.result.mainIssueIds;
            if (ids && ids.length) {
                return taxIssueStatusLabels[ids[0]] || ids[0];
            }
        }
        if (phaseId === 'survivor-planning') {
            var sIds = record.result && record.result.mainIssueIds;
            if (sIds && sIds.length) {
                return survivorIssueStatusLabels[sIds[0]] || sIds[0];
            }
        }
        return '';
    }

    window.rbJourneyRecords = {
        schemaVersion: schemaVersion,
        statusLabels: statusLabels,
        statusLabel: function (status) {
            return statusLabels[status] ||
                stressStatusLabels[status] ||
                taxIssueStatusLabels[status] ||
                survivorIssueStatusLabels[status] ||
                '';
        },
        socialSecurityStatus: socialSecurityStatus,
        createSocialSecurityRecord: createSocialSecurityRecord,
        normalizeSocialSecurityRecord: normalizeSocialSecurityRecord,
        createBuildYourPlanRecord: createBuildYourPlanRecord,
        normalizeBuildYourPlanRecord: normalizeBuildYourPlanRecord,
        createStressTestRecord: createStressTestRecord,
        normalizeStressTestRecord: normalizeStressTestRecord,
        createTaxStrategyRecord: createTaxStrategyRecord,
        normalizeTaxStrategyRecord: normalizeTaxStrategyRecord,
        createSurvivorPlanningRecord: createSurvivorPlanningRecord,
        normalizeSurvivorPlanningRecord: normalizeSurvivorPlanningRecord,
        recordStatus: recordStatus
    };
})();
