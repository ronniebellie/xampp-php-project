/**
 * Journey feedback form — posts to ronbelisle.com API with session credentials.
 */
(function () {
    'use strict';

    var API_URL = 'https://ronbelisle.com/api/journey_feedback.php';

    function $(sel) {
        return document.querySelector(sel);
    }

    function setStatus(el, message, isError) {
        if (!el) return;
        el.hidden = !message;
        el.textContent = message || '';
        el.classList.toggle('is-error', !!isError);
    }

    function init() {
        var form = $('[data-feedback-form]');
        if (!form) return;

        var statusEl = $('[data-feedback-status]');
        var thanksEl = $('[data-feedback-thanks]');
        var csrfEl = $('[data-feedback-csrf]');
        var emailEl = $('[data-feedback-email]');
        var tryingEl = $('[data-feedback-trying]');
        var happenedEl = $('[data-feedback-happened]');
        var pageUrlEl = $('[data-feedback-page-url]');
        var phaseEl = $('[data-feedback-phase]');
        var submitBtn = $('[data-feedback-submit]');

        // Prefer current page URL when opened directly.
        if (pageUrlEl && !pageUrlEl.value) {
            try {
                pageUrlEl.value = window.location.origin + window.location.pathname + window.location.search;
            } catch (e) {
                /* ignore */
            }
        }

        function loadBootstrap() {
            if (typeof fetch !== 'function') {
                setStatus(statusEl, 'Please use a modern browser to send feedback.', true);
                return Promise.resolve(null);
            }
            return fetch(API_URL, {
                method: 'GET',
                credentials: 'include',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('bootstrap ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    if (csrfEl && data.csrfToken) {
                        csrfEl.value = data.csrfToken;
                    }
                    if (emailEl && data.email && !emailEl.value) {
                        emailEl.value = data.email;
                    }
                    return data;
                })
                .catch(function () {
                    setStatus(
                        statusEl,
                        'We couldn’t prepare the feedback form. Please reload the page and try again.',
                        true
                    );
                    return null;
                });
        }

        loadBootstrap();

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            setStatus(statusEl, '', false);

            var trying = (tryingEl && tryingEl.value ? tryingEl.value : '').trim();
            var happened = (happenedEl && happenedEl.value ? happenedEl.value : '').trim();
            if (!trying || !happened) {
                setStatus(statusEl, 'Please complete both feedback fields.', true);
                return;
            }
            if (!csrfEl || !csrfEl.value) {
                setStatus(statusEl, 'Please reload the page and try again.', true);
                loadBootstrap();
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending…';
            }

            var payload = {
                csrf_token: csrfEl.value,
                email: emailEl ? emailEl.value.trim() : '',
                trying_to_do: trying,
                what_happened: happened,
                page_url: pageUrlEl ? pageUrlEl.value : '',
                journey_phase: phaseEl ? phaseEl.value : ''
            };

            fetch(API_URL, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (result.data && result.data.csrfToken && csrfEl) {
                        csrfEl.value = result.data.csrfToken;
                    }
                    if (!result.ok || !result.data || !result.data.success) {
                        var msg =
                            (result.data && result.data.message) ||
                            'Could not send feedback. Please try again.';
                        setStatus(statusEl, msg, true);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Send Feedback';
                        }
                        return;
                    }
                    form.hidden = true;
                    if (thanksEl) thanksEl.hidden = false;
                    setStatus(statusEl, '', false);
                })
                .catch(function () {
                    setStatus(statusEl, 'Could not send feedback. Please try again.', true);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Send Feedback';
                    }
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
