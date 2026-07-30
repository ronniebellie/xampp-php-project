/**
 * Journey cloud sync (Milestone 5 / R1 — P3).
 * DB is authoritative for Premium users with a cloud plan.
 * localStorage remains the working cache and offline fallback.
 *
 * Does not implement first-import confirmation (P4) or clear-browser semantics (P5).
 */
(function () {
    'use strict';

    var STATUS_URL = 'https://ronbelisle.com/premium/journey-status.php';
    var LOAD_URL = 'https://ronbelisle.com/api/journey_plan_load.php';
    var SAVE_URL = 'https://ronbelisle.com/api/journey_plan_save.php';
    var PROGRESS_KEY = 'rbJourneyProgressV1';
    var CALCULATOR_KEY = 'rbJourneyCalculator:retirementSpendingPlan:v1';
    var PENDING_KEY = 'rbJourneySyncPendingV1';
    var SCHEMA_VERSION = 1;
    var DEBOUNCE_MS = 900;

    var state = {
        ready: false,
        status: null,
        csrfToken: null,
        canWrite: false,
        canRead: false,
        cloudExists: false,
        readOnly: false,
        needsImport: false,
        hydrated: false,
        saveState: 'idle',
        saveMessage: '',
        planSavedAt: null,
        lastError: null,
        clientUpdatedAt: null
    };

    var saveTimer = null;
    var saveInFlight = false;
    var saveQueued = false;
    var readyResolved = false;
    var readyWaiters = [];

    function nowIso() {
        return new Date().toISOString();
    }

    function emit() {
        var detail = {
            saveState: state.saveState,
            saveMessage: state.saveMessage,
            planSavedAt: state.planSavedAt,
            hydrated: state.hydrated,
            needsImport: state.needsImport,
            readOnly: state.readOnly,
            canWrite: state.canWrite,
            cloudExists: state.cloudExists,
            status: state.status,
            ready: state.ready
        };
        try {
            window.dispatchEvent(new CustomEvent('rb-journey-sync-state', { detail: detail }));
        } catch (error) {
            /* IE ignore */
        }
        try {
            if (state.status) {
                window.dispatchEvent(new CustomEvent('rb-journey-status', { detail: state.status }));
            }
        } catch (error2) {
            /* ignore */
        }
    }

    function setSaveState(code, message) {
        state.saveState = code;
        state.saveMessage = message || '';
        emit();
    }

    function resolveReady() {
        if (readyResolved) return;
        readyResolved = true;
        state.ready = true;
        readyWaiters.splice(0).forEach(function (entry) {
            try {
                entry.resolve(getPublicState());
            } catch (error) {
                /* ignore */
            }
        });
        emit();
    }

    function whenReady() {
        if (readyResolved) {
            return Promise.resolve(getPublicState());
        }
        return new Promise(function (resolve, reject) {
            readyWaiters.push({ resolve: resolve, reject: reject });
        });
    }

    function afterReady(fn) {
        whenReady().then(function () {
            fn();
        }).catch(function () {
            fn();
        });
    }

    function getPublicState() {
        return {
            ready: state.ready,
            status: state.status,
            canWrite: state.canWrite,
            canRead: state.canRead,
            cloudExists: state.cloudExists,
            readOnly: state.readOnly,
            needsImport: state.needsImport,
            hydrated: state.hydrated,
            saveState: state.saveState,
            saveMessage: state.saveMessage,
            planSavedAt: state.planSavedAt,
            lastError: state.lastError
        };
    }

    function readJson(key) {
        try {
            var parsed = JSON.parse(localStorage.getItem(key) || 'null');
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function writeJson(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function objectHasKeys(value) {
        return !!(value && typeof value === 'object' && Object.keys(value).length > 0);
    }

    function localHasMeaningfulData() {
        var progress = readJson(PROGRESS_KEY) || {};
        var phaseKeys = [
            'spending-goals',
            'social-security',
            'build-your-plan',
            'stress-test',
            'tax-strategy',
            'survivor-planning'
        ];
        if (phaseKeys.some(function (key) { return progress[key] === true; })) {
            return true;
        }
        if (progress.records && typeof progress.records === 'object' &&
            Object.keys(progress.records).length > 0) {
            return true;
        }
        if (progress.toolLaunches && typeof progress.toolLaunches === 'object' &&
            Object.keys(progress.toolLaunches).length > 0) {
            return true;
        }
        var calc = readJson(CALCULATOR_KEY);
        if (calc && (calc.completionStatus || calc.inputs || calc.outputs || calc.draftOutputs)) {
            return true;
        }
        return false;
    }

    function buildPayloadFromLocal() {
        var progress = readJson(PROGRESS_KEY) || {};
        var calculators = {};
        var calc = readJson(CALCULATOR_KEY);
        if (calc) {
            calculators.retirementSpendingPlan = calc;
        }
        return {
            schemaVersion: SCHEMA_VERSION,
            progress: progress,
            calculators: calculators
        };
    }

    function applyPayloadToLocal(payload) {
        if (!payload || typeof payload !== 'object') return false;
        var progress = payload.progress && typeof payload.progress === 'object'
            ? payload.progress
            : {};
        writeJson(PROGRESS_KEY, progress);

        var calculators = payload.calculators && typeof payload.calculators === 'object'
            ? payload.calculators
            : {};
        if (calculators.retirementSpendingPlan &&
            typeof calculators.retirementSpendingPlan === 'object') {
            writeJson(CALCULATOR_KEY, calculators.retirementSpendingPlan);
        } else {
            localStorage.removeItem(CALCULATOR_KEY);
        }
        return true;
    }

    function storePending(payload, clientUpdatedAt, reason) {
        try {
            sessionStorage.setItem(PENDING_KEY, JSON.stringify({
                payload: payload,
                clientUpdatedAt: clientUpdatedAt,
                reason: reason || 'autosave',
                queuedAt: nowIso()
            }));
        } catch (error) {
            /* ignore quota */
        }
    }

    function clearPending() {
        try {
            sessionStorage.removeItem(PENDING_KEY);
        } catch (error) {
            /* ignore */
        }
    }

    function readPending() {
        try {
            var parsed = JSON.parse(sessionStorage.getItem(PENDING_KEY) || 'null');
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function canAutosaveNow() {
        if (!state.canWrite || state.readOnly) return false;
        // Premium + existing browser data + empty cloud awaits explicit import (P4).
        if (state.needsImport) return false;
        // Otherwise allow create-or-update saves (fresh Premium users included).
        return true;
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (body) {
                return { response: response, body: body };
            });
        });
    }

    function hydrateFromPlan(plan) {
        if (!plan || !plan.payload) return false;
        applyPayloadToLocal(plan.payload);
        state.hydrated = true;
        state.cloudExists = true;
        state.clientUpdatedAt = plan.clientUpdatedAt || plan.serverUpdatedAt || nowIso();
        state.planSavedAt = plan.serverUpdatedAt || plan.updatedAt || state.planSavedAt;
        if (state.status) {
            state.status.cloudPlanExists = true;
            state.status.planSavedAt = state.planSavedAt;
        }
        return true;
    }

    function loadCloudPlan() {
        return fetchJson(LOAD_URL, {
            method: 'GET',
            credentials: 'include',
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        }).then(function (result) {
            var body = result.body || {};
            if (body.csrfToken) {
                state.csrfToken = body.csrfToken;
            }
            if (!result.response.ok || !body.success) {
                state.lastError = (body && body.error) || 'load_failed';
                return body;
            }
            state.canWrite = body.canWrite === true;
            state.readOnly = body.readOnly === true;
            if (body.exists && body.plan) {
                hydrateFromPlan(body.plan);
                if (state.readOnly) {
                    setSaveState('readonly', 'Reviewing saved account plan');
                } else {
                    // Hydration is not a new save — keep wording accurate.
                    setSaveState('loaded', 'Journey Premium is active.');
                }
            } else {
                state.cloudExists = false;
                if (state.canWrite && localHasMeaningfulData()) {
                    state.needsImport = true;
                    setSaveState(
                        'needs_import',
                        'Browser Journey ready to save to your account (confirmation next)'
                    );
                } else if (state.canWrite) {
                    setSaveState('idle', '');
                }
            }
            return body;
        });
    }

    function performSave(reason, force) {
        if (!canAutosaveNow()) {
            if (state.needsImport) {
                setSaveState(
                    'needs_import',
                    'Browser Journey ready to save to your account (confirmation next)'
                );
            } else if (state.readOnly) {
                setSaveState(
                    'readonly',
                    'Cloud updates require active Journey Premium access'
                );
            }
            return Promise.resolve({ skipped: true });
        }

        if (!navigator.onLine) {
            var offlinePayload = buildPayloadFromLocal();
            var offlineAt = nowIso();
            state.clientUpdatedAt = offlineAt;
            storePending(offlinePayload, offlineAt, reason || 'autosave');
            setSaveState('pending', 'Saved on this browser; cloud save will retry');
            return Promise.resolve({ offline: true });
        }

        if (saveInFlight) {
            saveQueued = true;
            return Promise.resolve({ queued: true });
        }

        saveInFlight = true;
        var payload = buildPayloadFromLocal();
        var clientUpdatedAt = nowIso();
        state.clientUpdatedAt = clientUpdatedAt;
        storePending(payload, clientUpdatedAt, reason || 'autosave');
        setSaveState('saving', 'Saving to your Journey account…');

        var body = {
            csrf_token: state.csrfToken,
            payload: payload,
            clientUpdatedAt: clientUpdatedAt,
            reason: reason || 'autosave',
            force: force === true
        };

        return fetchJson(SAVE_URL, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': state.csrfToken || ''
            },
            body: JSON.stringify(body)
        }).then(function (result) {
            saveInFlight = false;
            var data = result.body || {};
            if (data.csrfToken) {
                state.csrfToken = data.csrfToken;
            }

            if (result.response.ok && data.success && data.plan) {
                clearPending();
                state.cloudExists = true;
                state.needsImport = false;
                state.planSavedAt = data.plan.serverUpdatedAt || data.plan.updatedAt || clientUpdatedAt;
                state.clientUpdatedAt = data.plan.clientUpdatedAt || clientUpdatedAt;
                if (state.status) {
                    state.status.cloudPlanExists = true;
                    state.status.planSavedAt = state.planSavedAt;
                    state.status.canCloudWrite = true;
                }
                setSaveState('saved', 'Saved to your Journey account');
                if (saveQueued) {
                    saveQueued = false;
                    return performSave('autosave', false);
                }
                return data;
            }

            state.lastError = (data && data.error) || 'save_failed';
            if (result.response.status === 409 || state.lastError === 'conflict') {
                // Conflict UI is P4 — keep local, do not force overwrite.
                setSaveState(
                    'conflict',
                    'Saved on this browser; account has a newer plan'
                );
                return data;
            }
            if (result.response.status === 403 && state.lastError === 'premium_required') {
                state.canWrite = false;
                state.readOnly = true;
                setSaveState(
                    'readonly',
                    'Cloud updates require active Journey Premium access'
                );
                return data;
            }

            setSaveState('pending', 'Saved on this browser; cloud save will retry');
            if (saveQueued) {
                saveQueued = false;
                return performSave('autosave', false);
            }
            return data;
        }).catch(function () {
            saveInFlight = false;
            setSaveState('pending', 'Saved on this browser; cloud save will retry');
            return { error: 'network' };
        });
    }

    function scheduleSave(reason) {
        if (!canAutosaveNow()) {
            if (state.needsImport) {
                setSaveState(
                    'needs_import',
                    'Browser Journey ready to save to your account (confirmation next)'
                );
            }
            return;
        }
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(function () {
            performSave(reason || 'autosave', false);
        }, DEBOUNCE_MS);
    }

    function flushPending() {
        var pending = readPending();
        if (!pending || !canAutosaveNow()) return;
        performSave(pending.reason || 'retry', false);
    }

    function boot() {
        if (typeof fetch !== 'function') {
            resolveReady();
            return;
        }

        fetchJson(STATUS_URL, {
            method: 'GET',
            credentials: 'include',
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        }).then(function (result) {
            var status = result.body;
            if (!result.response.ok || !status || typeof status !== 'object') {
                resolveReady();
                return null;
            }
            state.status = status;
            state.canWrite = status.canCloudWrite === true;
            state.canRead = status.canCloudRead === true;
            state.cloudExists = status.cloudPlanExists === true;
            state.planSavedAt = status.planSavedAt || null;
            state.readOnly = state.canRead && !state.canWrite;

            emit();

            if (!status.authenticated) {
                resolveReady();
                return null;
            }

            if (!state.canRead) {
                // Free authenticated — local only.
                resolveReady();
                return null;
            }

            return loadCloudPlan().then(function () {
                if (state.canWrite) {
                    flushPending();
                }
                resolveReady();
            }).catch(function () {
                setSaveState('pending', 'Saved on this browser; cloud save will retry');
                resolveReady();
            });
        }).catch(function () {
            resolveReady();
        });
    }

    window.addEventListener('online', function () {
        flushPending();
    });

    window.rbJourneySync = {
        whenReady: whenReady,
        afterReady: afterReady,
        scheduleSave: scheduleSave,
        saveNow: function (reason) {
            window.clearTimeout(saveTimer);
            return performSave(reason || 'manual', false);
        },
        getState: getPublicState,
        getStatus: function () { return state.status; },
        buildPayloadFromLocal: buildPayloadFromLocal,
        localHasMeaningfulData: localHasMeaningfulData,
        keys: {
            progress: PROGRESS_KEY,
            calculator: CALCULATOR_KEY
        }
    };

    boot();
})();
