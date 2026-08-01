/**
 * Journey GA4 funnel helpers (shared property G-3NB2DLYQFZ).
 * Safe params only — never send names, emails, IDs, or financial values.
 */
(function () {
    'use strict';

    var STORAGE_PREFIX = 'rbJourneyAnalyticsV1:';
    var PHASES = {
        1: { key: 'spending-goals', name: 'spending_goals' },
        2: { key: 'social-security', name: 'social_security' },
        3: { key: 'build-your-plan', name: 'build_your_plan' },
        4: { key: 'stress-test', name: 'stress_test' },
        5: { key: 'tax-strategy', name: 'tax_strategy' },
        6: { key: 'survivor-planning', name: 'survivor_planning' }
    };

    function storageGet(key) {
        try {
            return window.sessionStorage.getItem(STORAGE_PREFIX + key);
        } catch (error) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            window.sessionStorage.setItem(STORAGE_PREFIX + key, value);
        } catch (error) {
            /* ignore */
        }
    }

    function localGet(key) {
        try {
            return window.localStorage.getItem(STORAGE_PREFIX + key);
        } catch (error) {
            return null;
        }
    }

    function localSet(key, value) {
        try {
            window.localStorage.setItem(STORAGE_PREFIX + key, value);
        } catch (error) {
            /* ignore */
        }
    }

    function readProgress() {
        try {
            var parsed = JSON.parse(window.localStorage.getItem('rbJourneyProgressV1') || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function statusSnapshot() {
        var status = null;
        if (window.rbJourneySync && typeof window.rbJourneySync.getStatus === 'function') {
            status = window.rbJourneySync.getStatus();
        }
        var accountState = 'anonymous';
        var storageMode = 'browser';
        if (status && status.authenticated === true) {
            if (status.hasAccess === true) {
                accountState = (status.subscriptionStatus === 'trialing' || status.entitlementStatus === 'trialing')
                    ? 'premium_trial'
                    : 'premium';
                storageMode = status.canCloudRead === true ? 'account' : 'browser';
            } else {
                accountState = 'free';
                storageMode = 'browser';
            }
        }
        return {
            account_state: accountState,
            storage_mode: storageMode
        };
    }

    function journeyStatus(progress) {
        var keys = Object.keys(PHASES).map(function (n) { return PHASES[n].key; });
        var completed = keys.filter(function (key) { return progress[key] === true; }).length;
        if (completed >= keys.length) return 'completed';
        if (completed > 0) return 'incomplete';
        return 'started';
    }

    function alreadyFired(eventName, dedupeKey) {
        var key = eventName + ':' + (dedupeKey || 'once');
        if (storageGet(key) === '1') return true;
        storageSet(key, '1');
        return false;
    }

    function track(eventName, params, options) {
        var settings = options || {};
        var dedupeKey = settings.dedupeKey || 'once';
        if (settings.dedupe !== false && alreadyFired(eventName, dedupeKey)) {
            return false;
        }
        var base = statusSnapshot();
        var progress = readProgress();
        var payload = {
            account_state: base.account_state,
            storage_mode: base.storage_mode,
            journey_status: journeyStatus(progress),
            source_page: (window.location && window.location.pathname) || ''
        };
        if (params && typeof params === 'object') {
            Object.keys(params).forEach(function (key) {
                var value = params[key];
                if (value === undefined || value === null || value === '') return;
                // Block accidental PII / financial payloads.
                if (/email|name|user|balance|spend|income|benefit|ssn|password/i.test(key)) return;
                if (typeof value === 'number' && !/phase_number/.test(key)) return;
                payload[key] = String(value);
            });
        }
        if (typeof window.rbTrack === 'function') {
            window.rbTrack(eventName, payload);
            return true;
        }
        return false;
    }

    function trackBegin(source) {
        if (localGet('journey_begin') === '1') return false;
        localSet('journey_begin', '1');
        return track('journey_begin', {
            source_page: source || ((window.location && window.location.pathname) || '')
        }, { dedupe: false });
    }

    function trackPhaseComplete(phaseNumber) {
        var meta = PHASES[phaseNumber];
        if (!meta) return false;
        var fired = track('phase_' + phaseNumber + '_complete', {
            phase_number: String(phaseNumber),
            phase_name: meta.name
        }, { dedupeKey: meta.key });
        if (fired) {
            var progress = readProgress();
            var allDone = Object.keys(PHASES).every(function (n) {
                return progress[PHASES[n].key] === true;
            });
            if (allDone && localGet('journey_complete') !== '1') {
                localSet('journey_complete', '1');
                track('journey_complete', {}, { dedupe: false });
            }
        }
        return fired;
    }

    function trackPdfDownload() {
        return track('journey_pdf_download', {}, {
            dedupeKey: 'download:' + String(Math.floor(Date.now() / 60000))
        });
    }

    function trackFreeAccountStart() {
        return track('free_account_start', {}, { dedupeKey: 'session' });
    }

    function trackFreeAccountComplete() {
        return track('free_account_complete', {}, { dedupeKey: 'lifetime' });
    }

    function trackPremiumTrialStart() {
        return track('journey_premium_trial_start', {}, { dedupeKey: 'lifetime' });
    }

    function trackSignIn() {
        return track('journey_sign_in', {}, { dedupeKey: 'session' });
    }

    function trackReturnVisit() {
        if (!localGet('has_visited')) {
            localSet('has_visited', '1');
            return false;
        }
        return track('journey_return_visit', {}, { dedupeKey: 'session' });
    }

    function bindBeginCtas() {
        document.querySelectorAll('[data-journey-home-cta], [data-journey-analytics-begin]').forEach(function (el) {
            el.addEventListener('click', function () {
                trackBegin(el.getAttribute('href') || window.location.pathname);
            }, { capture: true });
        });
    }

    function bindFreeAccountLinks() {
        document.querySelectorAll('[data-journey-analytics-free-account-start]').forEach(function (el) {
            el.addEventListener('click', function () {
                trackFreeAccountStart();
            }, { capture: true });
        });
    }

    function observeAuth() {
        function handleStatus(status) {
            if (!status || status.authenticated !== true) return;
            trackSignIn();
            try {
                var params = new URLSearchParams(window.location.search);
                if (params.get('from') === 'account') {
                    trackFreeAccountComplete();
                }
            } catch (error) {
                /* ignore */
            }
        }

        window.addEventListener('rb-journey-status', function (event) {
            handleStatus(event.detail);
        });

        if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
            window.rbJourneySync.afterReady(function () {
                handleStatus(window.rbJourneySync.getStatus());
            });
        } else if (window.rbJourneySync && typeof window.rbJourneySync.getStatus === 'function') {
            handleStatus(window.rbJourneySync.getStatus());
        }
    }

    function boot() {
        trackReturnVisit();
        bindBeginCtas();
        bindFreeAccountLinks();
        observeAuth();

        // Phase 1 completion signal after calculator redirect.
        try {
            if (new URLSearchParams(window.location.search).get('spendingPlan') === 'saved') {
                trackPhaseComplete(1);
            }
        } catch (error) {
            /* ignore */
        }

        // First open of Phase 1 planner counts as begin if not already tracked.
        if (/\/calculators\/retirement-spending-plan\/?/.test(window.location.pathname)) {
            trackBegin(window.location.pathname);
        }
    }

    window.rbJourneyAnalytics = {
        track: track,
        trackBegin: trackBegin,
        trackPhaseComplete: trackPhaseComplete,
        trackPdfDownload: trackPdfDownload,
        trackFreeAccountStart: trackFreeAccountStart,
        trackFreeAccountComplete: trackFreeAccountComplete,
        trackPremiumTrialStart: trackPremiumTrialStart,
        trackSignIn: trackSignIn,
        trackReturnVisit: trackReturnVisit
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());
