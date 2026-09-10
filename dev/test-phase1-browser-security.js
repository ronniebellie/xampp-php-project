'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const path = require('path');
const root = path.resolve(__dirname, '..');
const privacy = fs.readFileSync(path.join(root, 'calcforadvisors/assets/js/analytics-privacy.js'), 'utf8');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
function context(url, referrer = '') {
    const scripts = [];
    const window = { location: new URL(url) };
    const document = { referrer, currentScript: null, createElement: () => ({}), head: { appendChild: s => scripts.push(s) } };
    vm.runInNewContext(privacy, { window, document, URL, Date });
    window.rbAnalyticsInit('G-TEST', 'ronbelisle.com');
    return { window, document, scripts };
}
for (const url of [
    'https://calcforadvisors.com/set-password.php?token=secret',
    'https://calcforadvisors.com/trial-setup.php?token=secret',
    'https://ronbelisle.com/401k-on-track/?balance=123456',
    'https://ronbelisle.com/retirement-plan/?%74oken=secret',
    'https://ronbelisle.com/retirement-plan/#balance=123456',
    'https://calcforadvisors.com/success.php?session_id=secret',
    'https://calcforadvisors.com/set-password.php',
    'https://ronbelisle.com/auth/reset-password.php'
]) {
    const c = context(url);
    c.window.rbTrack('share_click', { page_location: url });
    check(c.scripts.length === 0, 'Sensitive page must not load analytics');
    check(c.window.dataLayer.length === 0, 'Sensitive page must not queue analytics');
}
for (const ref of ['https://ronbelisle.com/?token=secret', 'https://ronbelisle.com/#balance=123456']) {
    const c = context('https://ronbelisle.com/', ref);
    check(c.scripts.length === 0 && c.window.dataLayer.length === 0, 'Sensitive referrer blocks tag');
}
const c = context('https://ronbelisle.com/retirement-plan/', 'https://calcforadvisors.com/');
check(c.scripts.length === 1, 'Clean page loads one tag');
c.window.rbAnalyticsInit('G-TEST');
check(c.scripts.length === 1, 'Duplicate initialization does not duplicate tag');
c.window.rbTrack('share_click', { method: 'copy_link', page_location: 'https://example.test/?token=secret', page_referrer: 'https://example.test/?balance=123456' });
const event = c.window.dataLayer.at(-1);
check(event[0] === 'event' && event[1] === 'share_click', 'Event name preserved');
check(event[2].method === 'copy_link', 'Non-sensitive interaction metadata preserved');
check(event[2].page_location === 'https://ronbelisle.com/retirement-plan/', 'Explicit raw page URL overridden');
check(event[2].page_referrer === 'https://calcforadvisors.com/', 'Explicit raw referrer overridden');
check(!JSON.stringify(c.window.dataLayer).includes('secret'), 'No token reaches queued events');
const count = c.window.dataLayer.length;
c.window.location = new URL('https://ronbelisle.com/retirement-plan/?balance=123456');
c.window.rbTrack('share_click');
check(c.window.dataLayer.length === count, 'Later custom events fail closed after sensitive URL change');

(async function () {
    const calls = [];
    const w = { location: new URL('https://ronbelisle.com/retirement-plan/'), rbScenarioCsrfToken: 'session-token' };
    vm.runInNewContext(fs.readFileSync(path.join(root, 'js/scenario-api.js'), 'utf8'), {
        window: w, URL, Headers, Promise, fetch: async (url, options) => { calls.push({ url, options }); return { ok: true }; }
    });
    const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{"scenario_id":1}' };
    await w.rbScenarioFetch('/api/delete_scenario.php', options);
    check(calls[0].options.headers.get('X-CSRF-Token') === 'session-token', 'CSRF header added');
    check(calls[0].options.body === options.body, 'Scenario payload unchanged');
    check(calls[0].options.credentials === 'same-origin' && calls[0].options.redirect === 'error', 'Cookie and redirect boundaries enforced');
    check(!options.headers['X-CSRF-Token'], 'Caller options not mutated');
    for (const target of ['https://evil.test/api/save_scenario.php', '//evil.test/api/delete_scenario.php', '/other.php']) {
        await assert.rejects(w.rbScenarioFetch(target, options)); checks++;
    }
    check(calls.length === 1, 'No token sent to rejected destinations');
    // Every production mutation call must use the wrapper and its page must load the footer.
    for (const dir of fs.readdirSync(root, { withFileTypes: true }).filter(d => d.isDirectory() && !['vendor', 'dev', '.git'].includes(d.name))) {
        for (const name of ['calculator.js', 'index.php']) {
            const file = path.join(root, dir.name, name);
            if (!fs.existsSync(file)) continue;
            const source = fs.readFileSync(file, 'utf8');
            if (!source.includes('api/save_scenario.php') && !source.includes('api/delete_scenario.php')) continue;
            check(!/\bfetch\([^\n]*api\/(save|delete)_scenario\.php/.test(source), file + ' must use secure wrapper');
            check(fs.readFileSync(path.join(root, dir.name, 'index.php'), 'utf8').includes('includes/calculator-footer.php'), 'Mutation page loads CSRF helper');
        }
    }
    for (const file of ['calcforadvisors/index.html', 'calcforadvisors/demos/coastal-wealth.html', 'calcforadvisors/demos/northgate.html', 'calcforadvisors/demos/riverfront.html']) {
        const text = fs.readFileSync(path.join(root, file), 'utf8');
        check(text.includes('analytics-privacy.js') && !text.includes('googletagmanager.com/gtag/js'), 'Static page uses privacy gate');
    }
    console.log(`Phase 1 browser security tests passed (${checks} checks).`);
})().catch(error => { console.error(error); process.exitCode = 1; });
