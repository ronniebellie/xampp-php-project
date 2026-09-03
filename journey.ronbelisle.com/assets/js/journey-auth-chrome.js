/**
 * Journey account chrome — fetches ronbelisle.com status with credentials.
 * Save-state lines are driven by journey-sync.js when present (M5 P3).
 */
(function () {
    'use strict';

    var STATUS_URL = 'https://ronbelisle.com/premium/journey-status.php';
    var DEFAULT_HOME = 'https://journey.ronbelisle.com/';
    var DEFAULT_LOGIN = 'https://ronbelisle.com/auth/login.php';
    var DEFAULT_REGISTER = 'https://ronbelisle.com/auth/register.php';
    var DEFAULT_LOGOUT = 'https://ronbelisle.com/auth/logout.php';
    var DEFAULT_CHECKOUT = 'https://ronbelisle.com/premium/journey.php';
    var DEFAULT_ACCOUNT = 'https://ronbelisle.com/account.php';

    var latestStatus = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatSavedAt(iso) {
        if (!iso || typeof iso !== 'string') return '';
        var date = new Date(iso);
        if (Number.isNaN(date.getTime())) return '';
        try {
            return new Intl.DateTimeFormat('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }).format(date);
        } catch (error) {
            return date.toLocaleString();
        }
    }

    function withReturn(baseUrl, returnUrl) {
        var joiner = baseUrl.indexOf('?') === -1 ? '?' : '&';
        return baseUrl + joiner + 'return=' + encodeURIComponent(returnUrl || DEFAULT_HOME);
    }

    function currentReturnUrl() {
        try {
            if (window.location && /^https:\/\/journey\.ronbelisle\.com/i.test(window.location.href)) {
                return window.location.origin + '/';
            }
        } catch (error) {
            /* ignore */
        }
        return DEFAULT_HOME;
    }

    function applyAuthAwareSections(status) {
        var authenticated = !!(status && status.authenticated);
        var premium = !!(status && status.hasAccess);
        document.querySelectorAll('[data-journey-anon-only]').forEach(function (el) {
            el.hidden = authenticated;
        });
        document.querySelectorAll('[data-journey-auth-only]').forEach(function (el) {
            el.hidden = !authenticated;
        });
        document.querySelectorAll('[data-journey-premium-only]').forEach(function (el) {
            el.hidden = !premium;
        });
        document.querySelectorAll('[data-journey-free-auth-only]').forEach(function (el) {
            el.hidden = !(authenticated && !premium);
        });
        // Anonymous + signed-in free users (not Journey Premium).
        document.querySelectorAll('[data-journey-non-premium-only]').forEach(function (el) {
            el.hidden = premium;
        });
    }

    function updateSaveLines(detail) {
        var savedEl = document.querySelector('[data-journey-last-saved]');
        var stateEl = document.querySelector('[data-journey-save-state]');
        if (!savedEl && !stateEl) return;

        var planSavedAt = detail && detail.planSavedAt;
        var savedLabel = formatSavedAt(planSavedAt);
        if (savedEl) {
            if (savedLabel) {
                savedEl.hidden = false;
                savedEl.textContent = 'Last saved ' + savedLabel;
            } else if (!savedEl.textContent) {
                savedEl.hidden = true;
            }
        }

        if (stateEl) {
            var message = (detail && detail.saveMessage) || '';
            var code = detail && detail.saveState;
            // Never imply a successful account save merely because a plan was loaded.
            if (code === 'saved' && message.indexOf('Saved to your Journey account') === 0) {
                // keep as-is (real save)
            } else if (!message && code === 'readonly') {
                message = 'Reviewing saved account plan';
            } else if (!message && code === 'loaded') {
                message = 'Your Journey progress will now be saved automatically to your account.';
            }
            if (message) {
                stateEl.hidden = false;
                stateEl.textContent = message;
            } else {
                stateEl.textContent = '';
                stateEl.hidden = true;
            }
        }
    }

    function renderAnonymous(root, status) {
        var returnUrl = currentReturnUrl();
        var loginUrl = (status && status.loginUrl) || withReturn(DEFAULT_LOGIN, returnUrl);
        var registerUrl = withReturn(DEFAULT_REGISTER, returnUrl);
        root.innerHTML =
            '<div class="journey-account-stack is-anonymous">' +
            '<p class="journey-account-actions">' +
            '<a class="journey-account-link" href="' + escapeHtml(loginUrl) + '">Sign in</a>' +
            '</p>' +
            '<p class="journey-account-actions">' +
            '<a class="journey-account-link is-secondary" href="' +
            escapeHtml(registerUrl) +
            '">Create free account</a>' +
            '</p>' +
            '</div>';
    }

    function renderUnavailable(root) {
        root.innerHTML =
            '<div class="journey-account-stack is-unavailable">' +
            '<p class="journey-account-line">Account status unavailable</p>' +
            '</div>';
    }

    function renderAuthenticated(root, status) {
        var firstName = status.firstName || status.userName || 'there';
        var email = status.userEmail || '';
        var logoutUrl = status.logoutUrl || withReturn(DEFAULT_LOGOUT, DEFAULT_HOME);
        var logoutCsrfToken = status.logoutCsrfToken || '';
        var workspaceUrl = status.workspaceUrl || DEFAULT_HOME;
        var accountUrl = status.accountUrl || DEFAULT_ACCOUNT;
        var checkoutUrl = status.checkoutUrl || DEFAULT_CHECKOUT;
        var savedLabel = formatSavedAt(status.planSavedAt);
        var mode = status.accessMode || (status.hasAccess ? 'premium' : 'free');
        var subStatus = status.subscriptionStatus || status.entitlementStatus || '';
        var lines = [];
        var actions = [];

        lines.push(
            '<p class="journey-account-welcome">Welcome, ' + escapeHtml(firstName) + '</p>'
        );

        if (mode === 'premium' || status.hasAccess) {
            var badgeText = subStatus === 'trialing' ? 'Premium Trial' : 'Journey Premium';
            lines.push('<p class="journey-account-badge">' + escapeHtml(badgeText) + '</p>');
            if (email) {
                lines.push(
                    '<p class="journey-account-meta">Signed in as ' + escapeHtml(email) + '</p>'
                );
            }
            lines.push(
                '<p class="journey-account-saved" data-journey-last-saved' +
                    (savedLabel ? '' : ' hidden') +
                    '>' +
                    (savedLabel ? 'Last saved ' + escapeHtml(savedLabel) : '') +
                    '</p>'
            );
            lines.push(
                '<p class="journey-account-save-state" data-journey-save-state hidden></p>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(workspaceUrl) +
                    '">Open Journey Premium</a>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(accountUrl) +
                    '">Account</a>'
            );
        } else if (mode === 'readonly' || (status.canCloudRead && !status.canCloudWrite)) {
            if (email) {
                lines.push(
                    '<p class="journey-account-meta">Signed in as ' + escapeHtml(email) + '</p>'
                );
            }
            lines.push(
                '<p class="journey-account-readonly">Saved Journey available to review</p>'
            );
            lines.push(
                '<p class="journey-account-hint">Read-only · cloud updates require active Journey Premium access</p>'
            );
            lines.push(
                '<p class="journey-account-saved" data-journey-last-saved' +
                    (savedLabel ? '' : ' hidden') +
                    '>' +
                    (savedLabel ? 'Last saved ' + escapeHtml(savedLabel) : '') +
                    '</p>'
            );
            lines.push(
                '<p class="journey-account-save-state" data-journey-save-state>Reviewing saved account plan</p>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(accountUrl) +
                    '">Account</a>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(checkoutUrl) +
                    '?return=' +
                    encodeURIComponent(DEFAULT_HOME) +
                    '">Restore access</a>'
            );
        } else {
            if (email) {
                lines.push(
                    '<p class="journey-account-meta">Signed in as ' + escapeHtml(email) + '</p>'
                );
            }
            lines.push(
                '<p class="journey-account-hint">Continue with the free Journey, or start Journey Premium to save your progress across browsers and devices.</p>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(accountUrl) +
                    '">Account</a>'
            );
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(checkoutUrl) +
                    '?return=' +
                    encodeURIComponent(DEFAULT_HOME) +
                    '">Start Journey Premium trial</a>'
            );
        }

        if (logoutCsrfToken) {
            actions.push('<form method="POST" action="' + escapeHtml(logoutUrl) + '"><input type="hidden" name="csrf_token" value="' + escapeHtml(logoutCsrfToken) + '"><input type="hidden" name="return" value="' + escapeHtml(returnUrl) + '"><button type="submit" class="journey-account-link is-signout">Sign out</button></form>');
        }

        root.innerHTML =
            '<div class="journey-account-stack is-' +
            escapeHtml(mode) +
            '">' +
            lines.join('') +
            '<p class="journey-account-actions">' +
            actions.join('<span class="journey-account-sep" aria-hidden="true"> · </span>') +
            '</p>' +
            '</div>';
    }

    function render(root, status) {
        latestStatus = status;
        root.removeAttribute('aria-busy');
        if (!status || typeof status !== 'object') {
            renderUnavailable(root);
            applyAuthAwareSections(null);
            return;
        }
        if (!status.authenticated) {
            renderAnonymous(root, status);
        } else {
            renderAuthenticated(root, status);
        }
        applyAuthAwareSections(status);

        if (window.rbJourneySync && typeof window.rbJourneySync.getState === 'function') {
            updateSaveLines(window.rbJourneySync.getState());
        }
    }

    function init() {
        var root = document.querySelector('[data-journey-account-chrome]');
        if (!root) return;

        window.addEventListener('rb-journey-sync-state', function (event) {
            updateSaveLines(event.detail || {});
        });

        window.addEventListener('rb-journey-status', function (event) {
            if (!event.detail || latestStatus) return;
            render(root, event.detail);
        });

        var syncStatus = window.rbJourneySync && window.rbJourneySync.getStatus
            ? window.rbJourneySync.getStatus()
            : null;
        if (syncStatus) {
            render(root, syncStatus);
            return;
        }

        if (window.rbJourneySync && typeof window.rbJourneySync.whenReady === 'function') {
            window.rbJourneySync.whenReady().then(function () {
                var readyStatus = window.rbJourneySync.getStatus();
                if (readyStatus) {
                    render(root, readyStatus);
                    return;
                }
                fetchStatus(root);
            }).catch(function () {
                fetchStatus(root);
            });
            window.setTimeout(function () {
                if (!latestStatus) fetchStatus(root);
            }, 4000);
            return;
        }

        fetchStatus(root);
    }

    function fetchStatus(root) {
        if (latestStatus) return;
        if (typeof fetch !== 'function') {
            renderUnavailable(root);
            return;
        }
        fetch(STATUS_URL, {
            method: 'GET',
            credentials: 'include',
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('status ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (!latestStatus) render(root, data);
            })
            .catch(function () {
                if (!latestStatus) renderUnavailable(root);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
