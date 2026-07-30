<?php
/**
 * Phase 1 handoff regression: calculator ↔ progress ↔ Phase 3 target.
 *
 * Usage:
 *   php dev/journey-premium/test-phase1-handoff.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expectH(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$handoffJs = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/journey-phase1-handoff.js');
$syncJs = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/journey-sync.js');
$landingJs = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/spending-goals-phase.js');
$phase3Js = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/build-your-plan-phase.js');
$plannerJs = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/retirement-spending-plan.js');
$header = (string) file_get_contents($root . '/journey.ronbelisle.com/includes/site-header.php');

expectH('handoff helper exists', strpos($handoffJs, 'window.rbJourneyPhase1') !== false);
expectH('reconcileLocal exported', strpos($handoffJs, 'reconcileLocal:') !== false || strpos($handoffJs, 'reconcileLocal: reconcileLocal') !== false);
expectH('preferUsablePhase1 keeps local over thin cloud', strpos($handoffJs, 'preferUsablePhase1') !== false && strpos($handoffJs, 'canonical') !== false);
expectH('header loads handoff before sync', strpos($header, 'journey-phase1-handoff.js') !== false && strpos($header, 'journey-phase1-handoff.js') < strpos($header, 'journey-sync.js'));
expectH('sync reconciles before build payload', strpos($syncJs, 'reconcilePhase1Local()') !== false);
expectH('sync merge uses preferUsablePhase1', strpos($syncJs, 'preferUsablePhase1') !== false);
expectH('landing uses getSummaryRecord/handoff', strpos($landingJs, 'getSummaryRecord') !== false || strpos($landingJs, 'getPhase1Handoff') !== false);
expectH('phase3 uses shared handoff', strpos($phase3Js, 'getPhase1Handoff') !== false || strpos($phase3Js, 'rbJourneyPhase1') !== false);
expectH('planner saveNow before redirect', strpos($plannerJs, "saveNow('calculator')") !== false);
expectH('planner no longer redirects immediately without save', strpos($plannerJs, "saveNow('calculator').then(returnToPhase1)") !== false);

// Node runtime simulation of the exact broken → fixed sequence.
$node = trim((string) shell_exec('command -v node 2>/dev/null'));
if ($node !== '') {
    $script = <<<'JS'
const fs = require('fs');
const path = require('path');
const root = process.argv[1];
const store = {};
global.localStorage = {
  getItem(k){ return Object.prototype.hasOwnProperty.call(store,k) ? store[k] : null; },
  setItem(k,v){ store[k]=String(v); },
  removeItem(k){ delete store[k]; }
};
global.window = global;
const handoffSrc = fs.readFileSync(path.join(root,'journey.ronbelisle.com/assets/js/journey-phase1-handoff.js'),'utf8');
eval(handoffSrc);

function assert(name, cond){ if(!cond){ console.error('FAIL '+name); process.exit(1);} console.log('OK '+name); }

// 1) Complete Phase 1 canonically
const target = 9000;
const calc = window.rbJourneyPhase1.buildCanonicalCalculator({
  usable:true, monthlySpending:target, monthlyOther:0, annualSpending:target*12, lastUpdated:'2026-07-30T12:00:00.000Z'
}, { inputs: { currentMonthlySpending: 8000, expectedMonthlyRetirementSpending: 9000 } });
localStorage.setItem('rbJourneyCalculator:retirementSpendingPlan:v1', JSON.stringify(calc));
localStorage.setItem('rbJourneyProgressV1', JSON.stringify({
  'spending-goals': true,
  records: {
    'spending-goals': window.rbJourneyPhase1.buildCanonicalProgressRecord({
      usable:true, monthlySpending:target, monthlyOther:0, annualSpending:target*12, lastUpdated:'2026-07-30T12:00:00.000Z'
    }, null)
  }
}));

// 2) Complete Phase 2 on top
const progress = JSON.parse(localStorage.getItem('rbJourneyProgressV1'));
progress['social-security'] = true;
progress.records['social-security'] = {
  saved:true, claimAge:66, estimatedMonthlyBenefit:3500, journeyCompletionStatus:'completed',
  lastSavedPlanning:{ claimAge:66, estimatedMonthlyBenefit:3500, decisionStatus:'provisional' }
};
localStorage.setItem('rbJourneyProgressV1', JSON.stringify(progress));

// 3) Persist snapshot (cloud payload)
const cloudPayload = {
  schemaVersion:1,
  progress: JSON.parse(localStorage.getItem('rbJourneyProgressV1')),
  calculators: { retirementSpendingPlan: JSON.parse(localStorage.getItem('rbJourneyCalculator:retirementSpendingPlan:v1')) }
};

// 4) Clear both keys (clean browser)
localStorage.removeItem('rbJourneyProgressV1');
localStorage.removeItem('rbJourneyCalculator:retirementSpendingPlan:v1');

// 5) Hydrate from cloud + reconcile
localStorage.setItem('rbJourneyProgressV1', JSON.stringify(cloudPayload.progress));
localStorage.setItem('rbJourneyCalculator:retirementSpendingPlan:v1', JSON.stringify(cloudPayload.calculators.retirementSpendingPlan));
const reconciled = window.rbJourneyPhase1.reconcileLocal();
assert('hydrate reconcile usable', reconciled.handoff.usable === true);
assert('hydrate target 9000', reconciled.handoff.monthlySpending === 9000);

// 6) Phase 1 landing summary
const summary = window.rbJourneyPhase1.getSummaryRecord();
assert('landing complete', !!summary && summary.outputs.monthlyRetirementSpendingTarget === 9000);

// 7) Phase 3 handoff
const handoff = window.rbJourneyPhase1.getHandoff();
assert('phase3 usable', handoff.usable === true && handoff.monthlySpending === 9000);

// 8) Phase 2 intact
const after = JSON.parse(localStorage.getItem('rbJourneyProgressV1'));
assert('phase2 remains', after['social-security'] === true && after.records['social-security'].claimAge === 66);

// Thin/legacy cloud must not beat canonical local
localStorage.setItem('rbJourneyCalculator:retirementSpendingPlan:v1', JSON.stringify(calc));
localStorage.setItem('rbJourneyProgressV1', JSON.stringify({
  'spending-goals': true,
  'social-security': true,
  records: {
    'spending-goals': window.rbJourneyPhase1.buildCanonicalProgressRecord({
      usable:true, monthlySpending:9000, monthlyOther:0, annualSpending:108000, lastUpdated:'2026-07-30T12:00:00.000Z'
    }, null),
    'social-security': after.records['social-security']
  }
}));
const preferred = window.rbJourneyPhase1.preferUsablePhase1(
  { 'spending-goals': true, records: { 'spending-goals': { monthlyTarget: 9500, marker: 'e2e' } } },
  { completionStatus: 'complete', outputs: { monthlyTarget: 9500 } },
  JSON.parse(localStorage.getItem('rbJourneyProgressV1')),
  JSON.parse(localStorage.getItem('rbJourneyCalculator:retirementSpendingPlan:v1'))
);
assert('local canonical beats thin cloud', preferred.keptLocal === true && preferred.handoff.monthlySpending === 9000);

console.log('NODE_PHASE1_HANDOFF_OK');
JS;
    $tmp = sys_get_temp_dir() . '/test-phase1-handoff.js';
    file_put_contents($tmp, $script);
    $out = [];
    $code = 0;
    exec(escapeshellarg($node) . ' ' . escapeshellarg($tmp) . ' ' . escapeshellarg($root) . ' 2>&1', $out, $code);
    $text = implode("\n", $out);
    expectH('node sequence simulation', $code === 0 && strpos($text, 'NODE_PHASE1_HANDOFF_OK') !== false, $text);
} else {
    expectH('node sequence simulation skipped (node unavailable)', true);
}

echo "Phase 1 handoff tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
