(function () {
    // Central list of Journey-owned localStorage keys. Add future Journey calculator
    // keys here so Clear Journey data remains a complete browser reset.
    var JOURNEY_STORAGE_KEYS = [
        'rbJourneyProgressV1',
        'rbJourneyCalculator:retirementSpendingPlan:v1'
    ];
    var storageKey = JOURNEY_STORAGE_KEYS[0];
    var phases = [
        { key: 'spending-goals', title: 'Spending & Goals', href: '/phases/spending-goals.php', available: true },
        { key: 'social-security', title: 'Social Security', href: '/phases/social-security.php', available: true },
        { key: 'build-your-plan', title: 'Build Your Plan', href: '/phases/build-your-plan.php', available: true },
        { key: 'stress-test', title: 'Stress Test', href: '/phases/stress-test.php', available: true },
        { key: 'tax-strategy', title: 'Tax Strategy', href: '/phases/tax-strategy.php', available: true },
        { key: 'survivor-planning', title: 'Survivor Planning', href: '/phases/survivor-planning.php', available: true }
    ];

    function journeyStorageKeys() {
        return JOURNEY_STORAGE_KEYS.slice();
    }

    function clearJourneyLocalStorage() {
        journeyStorageKeys().forEach(function (key) {
            localStorage.removeItem(key);
        });
    }

    function hasJourneyCalculatorData() {
        return JOURNEY_STORAGE_KEYS.some(function (key) {
            if (key === storageKey) return false;
            try {
                var raw = localStorage.getItem(key);
                return Boolean(raw && raw !== '{}' && raw !== 'null');
            } catch (error) {
                return false;
            }
        });
    }

    window.rbJourneyStorage = {
        keys: journeyStorageKeys,
        clear: clearJourneyLocalStorage
    };

    function migrateLegacyKeys(progress) {
        if (!progress || typeof progress !== 'object') return progress;
        // Safe fallback: Phase 6 was never publicly open under survivor-legacy,
        // but migrate an unexpected old progress flag if present.
        if (progress['survivor-legacy'] === true && progress['survivor-planning'] !== true) {
            progress['survivor-planning'] = true;
        }
        if (progress.records && typeof progress.records === 'object' &&
            progress.records['survivor-legacy'] && !progress.records['survivor-planning']) {
            progress.records['survivor-planning'] = progress.records['survivor-legacy'];
        }
        return progress;
    }

    function readProgress() {
        try {
            var parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return migrateLegacyKeys(parsed && typeof parsed === 'object' ? parsed : {});
        } catch (error) {
            return {};
        }
    }

    function writeProgress(progress) {
        localStorage.setItem(storageKey, JSON.stringify(progress));
        if (window.rbJourneySync && typeof window.rbJourneySync.scheduleSave === 'function') {
            window.rbJourneySync.scheduleSave('progress');
        }
    }

    function completedPhases(progress) {
        return phases.filter(function (phase) {
            return progress[phase.key] === true;
        });
    }

    function recommendedPhase(progress) {
        return phases.find(function (phase) {
            return progress[phase.key] !== true;
        }) || null;
    }

    function recommendedAvailablePhase(progress) {
        return phases.find(function (phase) {
            return phase.available && progress[phase.key] !== true;
        }) || null;
    }

    function lastAvailablePhase() {
        var available = phases.filter(function (phase) {
            return phase.available;
        });
        return available[available.length - 1] || phases[0];
    }

    function progressStatus() {
        if (window.rbJourneySync && typeof window.rbJourneySync.getStatus === 'function') {
            return window.rbJourneySync.getStatus() || null;
        }
        return null;
    }

    /**
     * True when progress is stored only in this browser (signed out, or signed in
     * without Journey Premium cloud sync). False when cloud-backed Premium sync applies.
     */
    function isBrowserOnlyProgress() {
        var status = progressStatus();
        if (!status || status.authenticated !== true) {
            return true;
        }
        return status.canCloudRead !== true;
    }

    function homepageCtaTarget(progress) {
        var started = hasJourneyData(progress);
        var recommended = recommendedAvailablePhase(progress);
        var journeyComplete = completedPhases(progress).length === phases.length;
        var browserOnly = isBrowserOnlyProgress();
        if (!started) {
            return {
                href: phases[0].href,
                label: 'Begin Your Journey'
            };
        }
        if (journeyComplete) {
            return {
                href: '/phases/build-your-plan.php',
                label: browserOnly ? 'Review Plan in This Browser' : 'Review Your Plan'
            };
        }
        if (recommended) {
            return {
                href: recommended.href,
                label: 'Continue Your Journey'
            };
        }
        return {
            href: lastAvailablePhase().href,
            label: 'Continue Your Journey'
        };
    }

    function hasJourneyData(progress) {
        var hasPhaseState = phases.some(function (phase) {
            return Object.prototype.hasOwnProperty.call(progress, phase.key);
        });
        var hasToolLaunches = progress.toolLaunches &&
            typeof progress.toolLaunches === 'object' &&
            Object.keys(progress.toolLaunches).length > 0;
        var hasRecords = progress.records &&
            typeof progress.records === 'object' &&
            Object.keys(progress.records).length > 0;
        // Include orphaned Journey calculator records so Clear remains available
        // even when rbJourneyProgressV1 was already removed.
        return hasPhaseState || hasToolLaunches || hasRecords || hasJourneyCalculatorData();
    }

    function recordStatus(progress, key) {
        if (!window.rbJourneyRecords) return '';
        return window.rbJourneyRecords.recordStatus(progress, key);
    }

    function recordStatusLabel(status) {
        if (!window.rbJourneyRecords) return '';
        return window.rbJourneyRecords.statusLabel(status);
    }

    function renderSummary(progress) {
        var summary = document.querySelector('[data-journey-progress-summary]');
        if (!summary) return;

        var completed = completedPhases(progress);
        var recommended = recommendedAvailablePhase(progress);
        var count = summary.querySelector('[data-journey-completed-count]');
        var completedList = summary.querySelector('[data-journey-completed-list]');
        var recommendedLabel = summary.querySelector('[data-journey-recommended-phase]');
        var recommendedLink = summary.querySelector('[data-journey-recommended-link]');
        var progressBar = summary.querySelector('[data-journey-progress-bar]');
        var progressFill = summary.querySelector('[data-journey-progress-fill]');
        var recordSummary = summary.querySelector('[data-journey-record-summary]');
        var recordList = summary.querySelector('[data-journey-record-list]');
        var context = summary.querySelector('[data-journey-progress-context]');
        var eyebrow = summary.querySelector('[data-journey-progress-eyebrow]');
        var heading = summary.querySelector('[data-journey-progress-heading]');
        var actions = summary.querySelector('[data-journey-progress-actions]');
        var reset = summary.querySelector('[data-journey-reset]');
        var started = hasJourneyData(progress);
        var journeyComplete = completed.length === phases.length;
        var browserOnly = isBrowserOnlyProgress();

        if (eyebrow) {
            eyebrow.textContent = browserOnly
                ? (started ? 'Progress saved in this browser' : 'Your progress')
                : 'Your progress';
        }
        if (heading) {
            heading.textContent = browserOnly
                ? (started ? 'Your Browser Progress' : 'Your Progress')
                : 'Your Progress';
        }

        if (count) {
            count.textContent = browserOnly && started
                ? completed.length + ' of ' + phases.length + ' phases completed in this browser'
                : completed.length + ' of ' + phases.length + ' phases completed';
        }

        if (progressBar) {
            progressBar.setAttribute('aria-valuenow', String(completed.length));
        }

        if (progressFill) {
            progressFill.style.width = Math.round((completed.length / phases.length) * 100) + '%';
        }

        if (completedList) {
            completedList.innerHTML = '';
            if (completed.length === 0) {
                var empty = document.createElement('li');
                empty.textContent = 'No phases completed yet.';
                completedList.appendChild(empty);
            } else {
                completed.forEach(function (phase) {
                    var item = document.createElement('li');
                    item.textContent = phase.title;
                    completedList.appendChild(item);
                });
            }
        }

        if (recommendedLabel) {
            if (journeyComplete) {
                recommendedLabel.textContent = browserOnly
                    ? 'Review your plan in this browser'
                    : 'Review your plan anytime';
            } else {
                recommendedLabel.textContent = recommended ? recommended.title : 'Review Social Security';
            }
        }

        if (recommendedLink) {
            var cta = homepageCtaTarget(progress);
            recommendedLink.href = cta.href;
            recommendedLink.textContent = cta.label;
        }

        if (context) {
            if (journeyComplete && browserOnly) {
                context.textContent = 'Your Journey is complete in this browser. Return anytime on this device to review your plan and keep it current.';
            } else if (journeyComplete) {
                context.textContent = 'Your Journey is complete. Return anytime to review your retirement plan and keep it current.';
            } else if (started && browserOnly) {
                context.textContent = 'You’re building your retirement plan one decision at a time. Progress is saved in this browser.';
            } else {
                context.textContent = 'You’re building your retirement plan one decision at a time.';
            }
        }

        if (actions) {
            actions.hidden = !started;
        }

        if (reset) {
            reset.hidden = !started;
        }

        if (recordSummary && recordList) {
            recordList.innerHTML = '';
            phases.forEach(function (phase) {
                var status = recordStatus(progress, phase.key);
                if (!status) return;
                var item = document.createElement('li');
                var link = document.createElement('a');
                var label = document.createElement('span');
                link.href = phase.href;
                link.textContent = phase.title;
                label.textContent = recordStatusLabel(status);
                label.className = 'record-status-badge is-' + status;
                item.appendChild(link);
                item.appendChild(label);
                recordList.appendChild(item);
            });
            recordSummary.hidden = recordList.children.length === 0;
        }
    }

    function renderPhaseStates(progress) {
        var recommended = recommendedAvailablePhase(progress);
        document.querySelectorAll('[data-journey-phase]').forEach(function (element) {
            var key = element.getAttribute('data-journey-phase');
            var phase = phases.find(function (item) {
                return item.key === key;
            });
            var complete = progress[key] === true;
            element.classList.toggle('is-complete', complete);
            element.classList.toggle('is-next-step', !complete && recommended && recommended.key === key);
            element.classList.toggle('is-planned', Boolean(phase && !phase.available));
            element.setAttribute('data-journey-complete', complete ? 'true' : 'false');
            var phaseStatus = element.querySelector('[data-journey-phase-status]');
            if (phaseStatus) {
                if (complete) {
                    phaseStatus.textContent = 'Completed';
                    phaseStatus.className = 'phase-status is-completed';
                } else if (recommended && recommended.key === key) {
                    phaseStatus.textContent = 'Next step';
                    phaseStatus.className = 'phase-status is-next-step';
                } else if (phase && phase.available) {
                    phaseStatus.textContent = '';
                    phaseStatus.className = 'phase-status';
                    phaseStatus.hidden = true;
                } else {
                    phaseStatus.textContent = 'Coming soon';
                    phaseStatus.className = 'phase-status is-coming-soon';
                }
                if (phaseStatus.textContent) {
                    phaseStatus.hidden = false;
                }
            }
            var statusElement = element.querySelector('[data-journey-record-status]');
            if (statusElement) {
                var status = recordStatus(progress, key);
                statusElement.textContent = recordStatusLabel(status);
                statusElement.className = 'step-record-status' + (status ? ' is-' + status : '');
                statusElement.hidden = !status;
            }
        });
    }

    function renderToolLaunchPanels(progress) {
        document.querySelectorAll('[data-journey-reveal-after-launch]').forEach(function (element) {
            var key = element.getAttribute('data-journey-reveal-after-launch');
            var launched = progress.toolLaunches &&
                typeof progress.toolLaunches === 'object' &&
                progress.toolLaunches[key] === true;
            element.hidden = !launched;
        });
    }

    function render(progress) {
        renderSummary(progress);
        renderPhaseStates(progress);
        renderToolLaunchPanels(progress);
        var ctaTarget = homepageCtaTarget(progress);
        document.querySelectorAll('[data-journey-home-cta]').forEach(function (link) {
            link.href = ctaTarget.href;
            link.textContent = ctaTarget.label;
            link.hidden = false;
        });
    }

    function markComplete(key) {
        if (!key) return;
        var progress = readProgress();
        progress[key] = true;
        writeProgress(progress);
        render(progress);
    }

    function markToolLaunched(key) {
        if (!key) return;
        var progress = readProgress();
        progress.toolLaunches = progress.toolLaunches && typeof progress.toolLaunches === 'object'
            ? progress.toolLaunches
            : {};
        progress.toolLaunches[key] = true;
        writeProgress(progress);
        render(progress);
    }

    document.addEventListener('click', function (event) {
        var toolTrigger = event.target.closest('[data-journey-launch-tool]');
        if (toolTrigger) {
            markToolLaunched(toolTrigger.getAttribute('data-journey-launch-tool'));
            return;
        }

        var completeTrigger = event.target.closest('[data-journey-complete-phase]');
        if (completeTrigger) {
            markComplete(completeTrigger.getAttribute('data-journey-complete-phase'));
            return;
        }

        var resetTrigger = event.target.closest('[data-journey-reset]');
        if (resetTrigger) {
            var confirmed = window.confirm(
                'Clear all Journey progress saved in this browser?\n\n' +
                'This removes your saved phase progress and Retirement Spending Plan from this browser. This action cannot be undone.'
            );
            if (!confirmed) {
                return;
            }
            clearJourneyLocalStorage();
            window.location.assign('/');
        }
    });

    function bootJourneyProgress() {
        render(readProgress());
    }

    if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
        window.rbJourneySync.afterReady(bootJourneyProgress);
    } else {
        bootJourneyProgress();
    }
})();
