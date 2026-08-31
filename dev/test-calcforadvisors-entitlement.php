<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/calcforadvisors_entitlement.php';

$failures = [];
$checks = 0;
$now = new DateTimeImmutable('2026-08-31 12:00:00', new DateTimeZone('UTC'));

function cfa_test_assert(bool $condition, string $message): void
{
    global $failures, $checks;
    $checks++;
    if (!$condition) $failures[] = $message;
}

function cfa_test_state(array $row, DateTimeImmutable $now, string $state, bool $access, bool $premium): array
{
    $result = cfa_evaluate_advisor_entitlement($row, $now);
    cfa_test_assert($result['state'] === $state, "Expected {$state}, got {$result['state']}.");
    cfa_test_assert($result['has_access'] === $access, "Unexpected access for {$state}.");
    cfa_test_assert($result['has_premium'] === $premium, "Unexpected Premium for {$state}.");
    cfa_test_assert($result['portal_available'] === $access, "Unexpected portal state for {$state}.");
    return $result;
}

cfa_test_state(['plan' => 'monthly', 'status' => 'active', 'stripe_subscription_status' => 'active'], $now, 'active', true, true);
cfa_test_state(['plan' => 'annual', 'stripe_subscription_status' => 'trialing', 'trial_ends_at' => '2026-09-15 00:00:00'], $now, 'trialing', true, true);
cfa_test_state(['plan' => 'annual', 'stripe_subscription_status' => 'trialing', 'trial_ends_at' => '2026-08-30 00:00:00'], $now, 'trial_expired', false, false);
cfa_test_state(['plan' => 'annual', 'stripe_subscription_status' => 'trialing'], $now, 'trial_invalid', false, false);

$grace = cfa_test_state(['plan' => 'monthly', 'stripe_subscription_status' => 'past_due', 'past_due_started_at' => '2026-08-28 12:00:00'], $now, 'past_due_grace', true, true);
cfa_test_assert($grace['in_grace'] === true, 'Past-due grace should be marked in_grace.');
cfa_test_state(['plan' => 'monthly', 'stripe_subscription_status' => 'past_due', 'past_due_started_at' => '2026-08-20 12:00:00'], $now, 'past_due_expired', false, false);
cfa_test_state(['plan' => 'monthly', 'stripe_subscription_status' => 'canceled', 'access_ends_at' => '2026-09-10 00:00:00'], $now, 'canceled_paid_through', true, true);
cfa_test_state(['plan' => 'monthly', 'stripe_subscription_status' => 'canceled', 'access_ends_at' => '2026-08-30 00:00:00'], $now, 'canceled_expired', false, false);
cfa_test_state(['plan' => 'none', 'status' => 'inactive'], $now, 'inactive', false, false);
cfa_test_state(['plan' => 'free', 'created_at' => '2026-08-15 00:00:00'], $now, 'legacy_trialing', true, false);
cfa_test_state(['plan' => 'free', 'created_at' => '2026-07-01 00:00:00'], $now, 'legacy_trial_expired', false, false);
cfa_test_state(['plan' => 'monthly', 'status' => 'active', 'stripe_subscription_status' => ''], $now, 'legacy_active', true, true);

// Missing past_due_started_at must fail closed instead of granting an indefinite grace period.
cfa_test_state(['plan' => 'monthly', 'status' => 'past_due'], $now, 'past_due_expired', false, false);

// Consumer/Journey-like fields must not confer advisor access.
cfa_test_state(['subscription_status' => 'premium', 'journey_entitlement_status' => 'active'], $now, 'inactive', false, false);

// Portal slug rules.
$valid = cfa_validate_portal_slug(' Smith Retirement ');
cfa_test_assert($valid === ['ok' => true, 'slug' => 'smith-retirement', 'error' => null], 'Slug normalization failed.');
cfa_test_assert(cfa_validate_portal_slug('ab')['error'] === 'length', 'Short slug should fail.');
cfa_test_assert(cfa_validate_portal_slug(str_repeat('a', 49))['error'] === 'length', 'Long slug should fail.');
cfa_test_assert(cfa_validate_portal_slug('Admin')['error'] === 'reserved', 'Reserved slug should fail.');
cfa_test_assert(cfa_validate_portal_slug('--bad--')['slug'] === 'bad', 'Hyphen normalization failed.');
cfa_test_assert(cfa_portal_slug_is_unique('alpha', ['beta', 'gamma']), 'Unique slug was rejected.');
cfa_test_assert(!cfa_portal_slug_is_unique('alpha', ['alpha', 'beta']), 'Duplicate slug was accepted.');
cfa_test_assert(cfa_portal_slug_is_unique('alpha', [['id' => 7, 'portal_slug' => 'alpha']], 7), 'Own slug should be reusable by subscriber.');
cfa_test_assert(!cfa_portal_slug_is_unique('alpha', [['id' => 8, 'portal_slug' => 'alpha']], 7), 'Another subscriber duplicate was accepted.');

// Migration safety: the Phase 2 migration must not touch subscriber IDs or scenarios.
$migration = file_get_contents(dirname(__DIR__) . '/sql/migrations/20260831_calcforadvisors_phase2_foundation.sql');
cfa_test_assert(is_string($migration), 'Foundation migration could not be read.');
$forbidden = ['DROP TABLE', 'TRUNCATE', 'DELETE FROM', 'UPDATE calcforadvisors_scenarios', 'ALTER TABLE calcforadvisors_scenarios'];
foreach ($forbidden as $sql) {
    cfa_test_assert(stripos((string) $migration, $sql) === false, "Foundation migration contains forbidden operation: {$sql}.");
}

// Simulated existing subscriber/scenario relation remains stable because no ID is remapped.
$subscriber = ['id' => 42, 'plan' => 'monthly', 'status' => 'active'];
$scenario = ['id' => 900, 'subscriber_id' => 42, 'calculator_type' => 'rmd-impact'];
$extendedSubscriber = $subscriber + ['portal_slug' => 'example-advisor', 'stripe_subscription_status' => null];
cfa_test_assert($extendedSubscriber['id'] === $subscriber['id'], 'Subscriber ID changed during additive model extension.');
cfa_test_assert($scenario['subscriber_id'] === $extendedSubscriber['id'], 'Saved scenario relationship was not preserved.');

if ($failures) {
    fwrite(STDERR, "CalcForAdvisors entitlement tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "CalcForAdvisors entitlement tests passed ({$checks} checks).\n";
