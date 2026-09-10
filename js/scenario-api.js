/* Explicit wrapper for authenticated scenario mutations; never sends CSRF tokens cross-origin. */
window.rbScenarioFetch = function (url, options) {
    var target = new URL(url, window.location.href);
    if (target.origin !== window.location.origin || !/\/api\/(save|delete)_scenario\.php$/.test(target.pathname)) {
        return Promise.reject(new Error('Invalid scenario endpoint'));
    }
    var headers = new Headers(options.headers || {});
    headers.set('X-CSRF-Token', window.rbScenarioCsrfToken || '');
    return fetch(target.href, Object.assign({}, options, { headers: headers, credentials: 'same-origin', redirect: 'error' }));
};
