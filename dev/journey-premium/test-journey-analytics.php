<?php
/**
 * Journey GA4 instrumentation static checks.
 *
 * Usage:
 *   php dev/journey-premium/test-journey-analytics.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passed = [];
$failed = [];

function expectA(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

$shared = (string) file_get_contents($root . '/includes/analytics.php');
$journeyAnalyticsPhp = (string) file_get_contents($root . '/journey.ronbelisle.com/includes/analytics.php');
$header = (string) file_get_contents($root . '/journey.ronbelisle.com/includes/site-header.php');
$js = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/journey-analytics.js');
$home = (string) file_get_contents($root . '/index.php');
$continue = (string) file_get_contents($root . '/journey.ronbelisle.com/phases/continue-to-phase-2.php');
$success = (string) file_get_contents($root . '/premium/journey-success.php');
$plan = (string) file_get_contents($root . '/premium/journey.php');
$p1 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/retirement-spending-plan.js');
$p2 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/social-security-phase.js');
$p3 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/build-your-plan-phase.js');
$p4 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/stress-test-phase.js');
$p5 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/tax-strategy-phase.js');
$p6 = (string) file_get_contents($root . '/journey.ronbelisle.com/assets/js/survivor-planning-phase.js');
$docs = (string) file_get_contents($root . '/journey.ronbelisle.com/docs/GA4_JOURNEY_ANALYTICS.md');

expectA('shared measurement id remains main site', strpos($shared, 'G-3NB2DLYQFZ') !== false);
expectA('shared does not use journey measurement id', strpos($shared, 'G-8PMXKZ60L4') === false);
expectA('shared cookie domain parent', strpos($shared, "cookie_domain: 'ronbelisle.com'") !== false);
expectA('shared config guard', strpos($shared, '__rbGtagConfigured') !== false);
expectA('shared rbTrack helper', strpos($shared, 'window.rbTrack') !== false);
expectA('journey uses dedicated measurement id', strpos($journeyAnalyticsPhp, 'G-8PMXKZ60L4') !== false);
expectA('journey removed old measurement id', strpos($journeyAnalyticsPhp, 'G-3NB2DLYQFZ') === false);
expectA('journey does not include shared analytics file', strpos($journeyAnalyticsPhp, '$rbSharedAnalytics') === false);
expectA('journey config guard', strpos($journeyAnalyticsPhp, '__journeyGtagConfigured') !== false);
expectA('site header loads analytics php', strpos($header, "include __DIR__ . '/analytics.php'") !== false);
expectA('site header loads journey-analytics.js', strpos($header, 'journey-analytics.js') !== false);

foreach ([
    'journey_begin',
    'journey_complete',
    'free_account_start',
    'free_account_complete',
    'journey_premium_trial_start',
    'journey_pdf_download',
    'journey_sign_in',
    'journey_return_visit',
] as $event) {
    expectA('helper knows ' . $event, strpos($js, "'" . $event . "'") !== false || strpos($js, '"' . $event . '"') !== false);
}
expectA('helper builds phase_N_complete', strpos($js, "phase_' + phaseNumber + '_complete") !== false);

expectA('js blocks email-like params', strpos($js, 'email|name|user|balance|spend|income|benefit|ssn|password') !== false);
expectA('homepage hero promotion tracked', strpos($home, 'journey_promotion_click') !== false && strpos($home, 'homepage_hero') !== false);
expectA('homepage nav promotion tracked', strpos($home, 'placement="navigation"') !== false || strpos($home, "data-rb-param-placement=\"navigation\"") !== false);
expectA('free account start wired', strpos($continue, 'data-journey-analytics-free-account-start') !== false);
expectA('premium success tracks trial start', strpos($success, 'journey_premium_trial_start') !== false);
expectA('premium plan page has analytics', strpos($plan, 'includes/analytics.php') !== false);
expectA('phase1 tracks complete', strpos($p1, 'trackPhaseComplete(1)') !== false);
expectA('phase2 tracks complete', strpos($p2, 'trackPhaseComplete(2)') !== false);
expectA('phase3 tracks complete', strpos($p3, 'trackPhaseComplete(3)') !== false);
expectA('phase4 tracks complete', strpos($p4, 'trackPhaseComplete(4)') !== false);
expectA('phase5 tracks complete', strpos($p5, 'trackPhaseComplete(5)') !== false);
expectA('phase6 tracks complete', strpos($p6, 'trackPhaseComplete(6)') !== false);
expectA('pdf download tracked', strpos($p6, 'trackPdfDownload') !== false);
expectA('docs mention journey measurement id', strpos($docs, 'G-8PMXKZ60L4') !== false);
expectA('docs do not claim shared journey id', strpos($docs, 'same GA4 property') === false);
expectA('docs mention hostname', strpos($docs, 'journey.ronbelisle.com') !== false);

echo "Journey GA4 analytics tests\n";
echo 'Passed: ' . count($passed) . "\n";
echo 'Failed: ' . count($failed) . "\n";
foreach ($failed as $f) {
    echo '  FAIL: ' . $f . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
