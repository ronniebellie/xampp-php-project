(function () {
    var storageKey = 'rbJourneyProgressV1';
    var phases = [
        { key: 'spending-goals', title: 'Spending & Goals', href: '/phases/spending-goals.php' },
        { key: 'social-security', title: 'Social Security', href: '/phases/social-security.php' },
        { key: 'build-your-plan', title: 'Build Your Plan', href: '/#build-your-plan' },
        { key: 'stress-test', title: 'Stress Test', href: '/#stress-test' },
        { key: 'tax-strategy', title: 'Tax Strategy', href: '/#tax-strategy' },
        { key: 'survivor-legacy', title: 'Survivor & Legacy', href: '/#survivor-legacy' }
    ];

    function readProgress() {
        try {
            var parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function writeProgress(progress) {
        localStorage.setItem(storageKey, JSON.stringify(progress));
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

    function renderSummary(progress) {
        var summary = document.querySelector('[data-journey-progress-summary]');
        if (!summary) return;

        var completed = completedPhases(progress);
        var recommended = recommendedPhase(progress);
        var count = summary.querySelector('[data-journey-completed-count]');
        var completedList = summary.querySelector('[data-journey-completed-list]');
        var recommendedLabel = summary.querySelector('[data-journey-recommended-phase]');
        var recommendedLink = summary.querySelector('[data-journey-recommended-link]');
        var progressBar = summary.querySelector('[data-journey-progress-bar]');
        var progressFill = summary.querySelector('[data-journey-progress-fill]');

        if (count) {
            count.textContent = completed.length + ' of ' + phases.length + ' phases completed';
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
            recommendedLabel.textContent = recommended ? recommended.title : 'Journey complete';
        }

        if (recommendedLink) {
            recommendedLink.href = recommended ? recommended.href : '/';
            recommendedLink.textContent = recommended ? 'Continue Your Journey' : 'Review Journey';
        }
    }

    function renderPhaseStates(progress) {
        document.querySelectorAll('[data-journey-phase]').forEach(function (element) {
            var key = element.getAttribute('data-journey-phase');
            var complete = progress[key] === true;
            element.classList.toggle('is-complete', complete);
            element.setAttribute('data-journey-complete', complete ? 'true' : 'false');
        });
    }

    function render(progress) {
        renderSummary(progress);
        renderPhaseStates(progress);
    }

    function markComplete(key) {
        if (!key) return;
        var progress = readProgress();
        progress[key] = true;
        writeProgress(progress);
        render(progress);
    }

    document.addEventListener('click', function (event) {
        var completeTrigger = event.target.closest('[data-journey-complete-phase]');
        if (completeTrigger) {
            markComplete(completeTrigger.getAttribute('data-journey-complete-phase'));
            return;
        }

        var resetTrigger = event.target.closest('[data-journey-reset]');
        if (resetTrigger) {
            localStorage.removeItem(storageKey);
            render({});
        }
    });

    render(readProgress());
})();
