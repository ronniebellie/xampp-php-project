/**
 * Journey account chrome — fetches ronbelisle.com status with credentials.
 * Presentation only (Milestone 5 / R1 — P2). Does not hydrate or autosave plans.
 */
(function () {
    'use strict';

    var STATUS_URL = 'https://ronbelisle.com/premium/journey-status.php';
    var DEFAULT_HOME = 'https://journey.ronbelisle.com/';
    var DEFAULT_LOGIN = 'https://ronbelisle.com/auth/login.php';
    var DEFAULT_LOGOUT = 'https://ronbelisle.com/auth/logout.php';
    var DEFAULT_CHECKOUT = 'https://ronbelisle.com/premium/journey.php';
    var DEFAULT_ACCOUNT = 'https://ronbelisle.com/account.php';

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
    }

    function renderAnonymous(root, status) {
        var loginUrl = (status && status.loginUrl) || withReturn(DEFAULT_LOGIN, currentReturnUrl());
        root.innerHTML =
            '<div class="journey-account-stack is-anonymous">' +
            '<p class="journey-account-line">Not signed in</p>' +
            '<p class="journey-account-actions">' +
            '<a class="journey-account-link" href="' + escapeHtml(loginUrl) + '">Sign in</a>' +
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
        var workspaceUrl = status.workspaceUrl || DEFAULT_HOME;
        var accountUrl = status.accountUrl || DEFAULT_ACCOUNT;
        var checkoutUrl = status.checkoutUrl || DEFAULT_CHECKOUT;
        var savedLabel = formatSavedAt(status.planSavedAt);
        var mode = status.accessMode || (status.hasAccess ? 'premium' : 'free');
        var lines = [];
        var actions = [];
        var note = '';

        lines.push(
            '<p class="journey-account-welcome">Welcome, ' + escapeHtml(firstName) + '</p>'
        );

        if (mode === 'premium' || status.hasAccess) {
            lines.push('<p class="journey-account-badge">Journey Premium</p>');
            if (email) {
                lines.push(
                    '<p class="journey-account-meta">Signed in as ' + escapeHtml(email) + '</p>'
                );
            }
            if (savedLabel) {
                lines.push(
                    '<p class="journey-account-saved">Last saved ' + escapeHtml(savedLabel) + '</p>'
                );
            }
            note =
                'You are signed in to Journey Premium. Cloud plan saving will be connected in the next implementation step; your current planning records still remain in this browser for now.';
            actions.push(
                '<a class="journey-account-link" href="' +
                    escapeHtml(workspaceUrl) +
                    '">Open Premium Workspace</a>'
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
            if (savedLabel) {
                lines.push(
                    '<p class="journey-account-saved">Last saved ' + escapeHtml(savedLabel) + '</p>'
                );
            }
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
                    '">Restore Premium</a>'
            );
        } else {
            if (email) {
                lines.push(
                    '<p class="journey-account-meta">Signed in as ' + escapeHtml(email) + '</p>'
                );
            }
            lines.push(
                '<p class="journey-account-hint">Cloud Journey saving requires Journey Premium</p>'
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
                    '">Journey Premium</a>'
            );
        }

        actions.push(
            '<a class="journey-account-link is-signout" href="' +
                escapeHtml(logoutUrl) +
                '">Sign out</a>'
        );

        root.innerHTML =
            '<div class="journey-account-stack is-' +
            escapeHtml(mode) +
            '">' +
            lines.join('') +
            (note
                ? '<p class="journey-account-note">' + escapeHtml(note) + '</p>'
                : '') +
            '<p class="journey-account-actions">' +
            actions.join('<span class="journey-account-sep" aria-hidden="true"> · </span>') +
            '</p>' +
            '</div>';
    }

    function render(root, status) {
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
    }

    function init() {
        var root = document.querySelector('[data-journey-account-chrome]');
        if (!root) return;

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
                render(root, data);
            })
            .catch(function () {
                renderUnavailable(root);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
