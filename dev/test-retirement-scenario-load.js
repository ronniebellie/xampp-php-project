'use strict';
// Run the real Load handler against synthetic API responses; never use accounts.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const filename = process.argv[2] || path.join(__dirname, '../retirement-plan/calculator.js');
const source = fs.readFileSync(filename, 'utf8');

function fixture(response) {
  const nodes = new Map(), alerts = [], calls = [], trace = [];
  class Element {
    constructor(id = '') {
      this.id = id; this.type = 'number'; this.tagName = 'INPUT'; this.value = '0';
      this.checked = false; this.style = {}; this.dataset = {}; this.children = [];
      this.listeners = {}; this.classList = { toggle() {} };
    }
    set value(value) { this._value = String(value); }
    get value() { return this._value; }
    set textContent(value) { this._text = value; this.children = []; }
    get textContent() { return this._text; }
    setAttribute() {}
    addEventListener(name, callback) { this.listeners[name] = callback; }
    appendChild(child) { this.children.push(child); if (child.id) nodes.set(child.id, child); }
  }
  function get(id) { if (!nodes.has(id)) nodes.set(id, new Element(id)); return nodes.get(id); }
  get('birthYear').value = '1957';
  get('birthDate').type = 'date'; get('birthDate').value = '2026-10-29';
  get('temporaryCash').value = '60000';
  get('results').style.display = 'block';
  const context = {
    console, Date, Number, alert: message => alerts.push(message), setTimeout() {},
    location: { origin: 'https://synthetic.invalid', pathname: '/retirement-plan/' },
    RBFinance: { fraAgeFromBirthYear: () => 67 }, RBPlanEngine: {}, RBMonteCarlo: {},
    document: { getElementById: get, querySelector: () => ({ value: 'direct' }),
      createElement: () => new Element(), addEventListener() {} },
    fetch: async (url, options) => {
      calls.push({ url, options });
      assert.match(url, /\/api\/load_scenarios\.php\?calculator_type=retirement-plan$/);
      assert.equal(options, undefined, 'Loading must issue only the existing read request');
      return { json: async () => response };
    },
    loadTrace: trace
  };
  context.window = context;
  // Expose the actual private handler and observe existing validation, without
  // replacing the handler, applyFormData, or validation implementation.
  const instrumented = source.replace('  function validateInputs(inputs) {',
    "  function validateInputs(inputs) { window.loadTrace.push('validate');")
    .replace("  document.addEventListener('DOMContentLoaded'",
      "  window.loadRegression = { load: loadScenario, build: runPlan, saveData: getFormDataForSave, collect: collectInputs, prime: function () { lastResult = {}; lastInputs = {}; lastMcResult = {}; window.lastRetirementPlanResult = {}; }, cached: function () { return !!(lastResult || lastInputs || lastMcResult || window.lastRetirementPlanResult); } };\n  document.addEventListener('DOMContentLoaded'");
  assert.notEqual(instrumented, source);
  vm.runInNewContext(instrumented, context, { filename });
  return { context, get, alerts, calls, trace };
}

async function load(data) {
  const f = fixture({ success: true, scenarios: [{ scenario_name: 'June 17 synthetic plan', data }] });
  f.context.loadRegression.prime();
  f.context.loadRegression.load();
  await new Promise(setImmediate);
  assert.equal(f.trace.length, 0, 'Opening the selector must not validate the current form');
  const status = f.get('saveStatus'), select = status.children[0], button = status.children[1];
  assert.equal(button.type, 'button');
  select.value = '0'; button.listeners.click();
  return f;
}

(async () => {
  const legacy = await load({ birthYear: 1957, balance: 123456, spendingMethod: 'direct' });
  assert.equal(legacy.get('birthYear').value, '1957');
  assert.equal(legacy.get('birthDate').value, '', 'Legacy data must not inherit today\'s default date');
  assert.equal(legacy.get('balance').value, '123456');
  assert.equal(legacy.get('temporaryCash').value, '0', 'Legacy plans must clear a previous cash reserve');
  assert.deepEqual(legacy.alerts, [], 'Loading legacy inputs must not raise birth-date validation');
  assert.deepEqual(legacy.trace, [], 'Loading must not calculate or validate');
  assert.equal(legacy.get('saveStatus').textContent, 'Loaded!');
  assert.equal(legacy.context.loadRegression.cached(), false, 'Previous results must be invalidated');
  assert.equal(legacy.get('results').style.display, 'none');
  legacy.context.loadRegression.build();
  assert.deepEqual(legacy.trace, ['validate']);
  assert.match(legacy.alerts[0], /full birth date matching birth year/, 'Explicit Build must retain validation');
  assert.equal(legacy.calls.length, 1, 'Load/Build must not save or delete a scenario');

  const dated = await load({ birthYear: 1957, birthDate: '1957-04-12', balance: 765432, temporaryCash: 50000 });
  assert.equal(dated.get('birthDate').value, '1957-04-12');
  assert.equal(dated.get('balance').value, '765432');
  assert.equal(dated.get('temporaryCash').value, '50000');
  assert.equal(dated.context.loadRegression.saveData().temporaryCash, 50000);
  assert.equal(dated.context.loadRegression.collect().temporaryCash, 50000);
  assert.deepEqual(dated.alerts, []);
  assert.deepEqual(dated.trace, []);

  const denied = fixture({ success: false, error: 'Scenario access unavailable' });
  denied.context.loadRegression.load(); await new Promise(setImmediate);
  assert.deepEqual(denied.alerts, ['Scenario access unavailable']);
  assert.deepEqual(denied.trace, []);
  assert.equal(denied.get('birthDate').value, '2026-10-29', 'Failed requests must not alter inputs');
  console.log('Retirement scenario-load regression passed: legacy/dated restoration, no implicit validation, stale results cleared, explicit Build validation, read-only requests and access errors.');
})().catch(error => { console.error(error); process.exitCode = 1; });
