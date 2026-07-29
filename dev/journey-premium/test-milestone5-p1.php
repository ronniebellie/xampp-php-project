<?php
/**
 * Journey Premium Milestone 5 / R1 — P1 API and store checks.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone5-p1.php
 *
 * Exit code 0 on success; 1 on failure.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/includes/session_bootstrap.php';
rb_session_start();
require_once $root . '/includes/db_config.php';
require_once $root . '/includes/journey_plan_store.php';

$passed = [];
$failed = [];

function expect(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

expect('cloud save enabled by default', journey_cloud_save_enabled() === true);

$bad = journey_plan_normalize_payload('nope');
expect('reject non-object payload', empty($bad['ok']) && ($bad['error'] ?? '') === 'invalid_payload');

$tooNew = journey_plan_normalize_payload(['schemaVersion' => 99, 'progress' => [], 'calculators' => []]);
expect('reject unsupported schema', empty($tooNew['ok']) && ($tooNew['error'] ?? '') === 'unsupported_schema');

$ok = journey_plan_normalize_payload([
    'schemaVersion' => 1,
    'progress' => ['spending-goals' => true],
    'calculators' => [
        'retirementSpendingPlan' => ['schemaVersion' => 2, 'completionStatus' => 'completed'],
    ],
]);
expect('accept valid payload', !empty($ok['ok']) && isset($ok['payload']['progress']['spending-goals']));
expect('payload byte count present', !empty($ok['bytes']) && $ok['bytes'] > 20);

$parsed = journey_plan_parse_client_updated_at('2026-07-29T12:00:00.000Z');
expect('parse client updated at', is_string($parsed) && strpos($parsed, '2026-07-29') === 0);
expect('reject bad client updated at', journey_plan_parse_client_updated_at('not-a-date') === null);

expect('tables ready after migration', journey_plan_tables_ready($conn) === true);

$anonymousWrite = journey_plan_save($conn, 0, $ok['payload'], gmdate('c'), 'autosave', false);
expect(
    'anonymous cannot write',
    empty($anonymousWrite['ok']) && ($anonymousWrite['error'] ?? '') === 'premium_required'
);

// Use a high unused user id that should not have Journey Premium.
$ghostUserId = 999999001;
$ghostWrite = journey_plan_save($conn, $ghostUserId, $ok['payload'], gmdate('c'), 'autosave', false);
expect(
    'non-premium user cannot write',
    empty($ghostWrite['ok']) && ($ghostWrite['error'] ?? '') === 'premium_required'
);

$ghostImport = journey_plan_import($conn, $ghostUserId, $ok['payload'], gmdate('c'));
expect(
    'non-premium user cannot import',
    empty($ghostImport['ok']) && ($ghostImport['error'] ?? '') === 'premium_required'
);

expect('ghost user cannot read without plan', journey_plan_can_read($conn, $ghostUserId) === false);

// Direct insert simulates a former Premium user's saved plan (read-only path).
$payloadJson = json_encode($ok['payload'], JSON_UNESCAPED_SLASHES);
$insert = $conn->prepare(
    'INSERT INTO journey_plans (user_id, schema_version, payload, client_updated_at)
     VALUES (?, 1, ?, UTC_TIMESTAMP(3))
     ON DUPLICATE KEY UPDATE payload = VALUES(payload), schema_version = 1'
);
if ($insert) {
    $insert->bind_param('is', $ghostUserId, $payloadJson);
    $inserted = $insert->execute();
    $insert->close();
    expect('direct insert for read-only fixture', $inserted === true);

    expect('former premium can read existing plan', journey_plan_can_read($conn, $ghostUserId) === true);
    expect('former premium still cannot write', journey_plan_can_write($conn, $ghostUserId) === false);

    $fetched = journey_plan_fetch($conn, $ghostUserId);
    expect('fetch returns payload', is_array($fetched) && !empty($fetched['payload']['progress']['spending-goals']));

    $blockedSave = journey_plan_save($conn, $ghostUserId, $ok['payload'], gmdate('c'), 'autosave', false);
    expect(
        'read-only user blocked from cloud save',
        empty($blockedSave['ok']) && ($blockedSave['error'] ?? '') === 'premium_required'
    );

    $blockedImport = journey_plan_import($conn, $ghostUserId, $ok['payload'], gmdate('c'));
    expect(
        'import refuses when cloud plan already exists for non-writer path',
        empty($blockedImport['ok']) && in_array(($blockedImport['error'] ?? ''), ['premium_required', 'already_exists'], true)
    );

    // Cleanup fixture rows.
    $cleanup = $conn->prepare('DELETE FROM journey_plans WHERE user_id = ?');
    if ($cleanup) {
        $cleanup->bind_param('i', $ghostUserId);
        $cleanup->execute();
        $cleanup->close();
    }
    expect('fixture cleaned', journey_plan_has_cloud_row($conn, $ghostUserId) === false);
} else {
    expect('direct insert prepare', false, $conn->error);
}

echo "Passed: " . count($passed) . "\n";
foreach ($passed as $name) {
    echo "  OK  {$name}\n";
}
echo "Failed: " . count($failed) . "\n";
foreach ($failed as $name) {
    echo "  FAIL {$name}\n";
}

exit(count($failed) === 0 ? 0 : 1);
