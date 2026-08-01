<?php
/**
 * Journey feedback store + admin helpers tests.
 *
 * Usage:
 *   php dev/journey-premium/test-journey-feedback.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_feedback.php';

$passed = [];
$failed = [];

function expectF(string $name, bool $cond, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed[] = $name;
        return;
    }
    $failed[] = $name . ($detail !== '' ? ' — ' . $detail : '');
}

expectF(
    'infer phase spending-goals',
    journey_feedback_infer_phase_from_url('https://journey.ronbelisle.com/phases/spending-goals.php') === 'spending-goals'
);
expectF(
    'infer phase home',
    journey_feedback_infer_phase_from_url('https://journey.ronbelisle.com/') === 'home'
);
expectF(
    'reject foreign page url',
    journey_feedback_sanitize_page_url('https://evil.example/x') === null
);
expectF(
    'allow journey page url',
    journey_feedback_sanitize_page_url('https://journey.ronbelisle.com/phases/tax-strategy.php') !== null
);
expectF(
    'summary truncates',
    mb_strlen(journey_feedback_summary_line(str_repeat('a', 200))) <= 120
);

$dbRan = false;
try {
    require_once $root . '/includes/db_config.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbRan = true;
        $up = file_get_contents($root . '/sql/migrations/20260801_003_journey_feedback_up.sql');
        if (is_string($up) && $conn->multi_query($up)) {
            do {
                if ($r = $conn->store_result()) {
                    $r->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        }

        $conn->query("DELETE FROM journey_feedback WHERE email LIKE '%feedback-test%@example.com' OR trying_to_do LIKE 'FEEDBACK_TEST_%'");

        $beforeNew = journey_feedback_count_new($conn);

        $r1 = journey_feedback_store($conn, [
            'trying_to_do' => 'FEEDBACK_TEST_trying',
            'what_happened' => 'FEEDBACK_TEST_happened',
            'email' => 'feedback-test@example.com',
            'page_url' => 'https://journey.ronbelisle.com/phases/spending-goals.php',
            'journey_phase' => 'spending-goals',
            'user_agent' => 'FeedbackTestAgent/1.0',
            'user_id' => 0,
            'is_signed_in' => false,
            'is_premium' => false,
        ]);
        expectF('store ok', !empty($r1['ok']), json_encode($r1));
        $id = (int) ($r1['id'] ?? 0);
        expectF('store id', $id > 0);

        $list = journey_feedback_list_recent($conn, 50);
        $found = null;
        foreach ($list as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $found = $row;
                break;
            }
        }
        expectF('appears in admin list', is_array($found));
        expectF('listed as new', is_array($found) && !empty($found['is_new']));
        expectF('new count increased', journey_feedback_count_new($conn) >= $beforeNew + 1);

        $got = journey_feedback_get($conn, $id);
        expectF('get returns row', is_array($got) && ($got['trying_to_do'] ?? '') === 'FEEDBACK_TEST_trying');
        expectF(
            'no sensitive planning keys in payload',
            is_array($got)
            && stripos(json_encode($got) ?: '', 'vanguard') === false
            && stripos(json_encode($got) ?: '', 'password') === false
        );

        expectF('mark viewed', journey_feedback_mark_viewed($conn, $id) === true);
        $got2 = journey_feedback_get($conn, $id);
        expectF('viewed status', is_array($got2) && empty($got2['is_new']) && ($got2['status_label'] ?? '') === 'Viewed');

        // Missing fields rejected
        $bad = journey_feedback_store($conn, [
            'trying_to_do' => '',
            'what_happened' => 'x',
        ]);
        expectF('missing fields rejected', empty($bad['ok']));

        // Foreign URL ignored but still stores
        $r2 = journey_feedback_store($conn, [
            'trying_to_do' => 'FEEDBACK_TEST_foreign',
            'what_happened' => 'still saved',
            'page_url' => 'https://evil.example/path',
            'email' => 'feedback-test-2@example.com',
        ]);
        expectF('foreign url still stores', !empty($r2['ok']));
        $g2 = journey_feedback_get($conn, (int) ($r2['id'] ?? 0));
        expectF('foreign url stripped', is_array($g2) && ($g2['page_url'] ?? 'x') === '');

        $conn->query("DELETE FROM journey_feedback WHERE email LIKE '%feedback-test%@example.com' OR trying_to_do LIKE 'FEEDBACK_TEST_%'");
    }
} catch (Throwable $e) {
    if ($dbRan) {
        expectF('db tests completed without exception', false, $e->getMessage());
    } else {
        fwrite(STDERR, 'NOTE: Feedback DB tests skipped: ' . $e->getMessage() . "\n");
    }
}

if (!$dbRan) {
    fwrite(STDERR, "NOTE: Feedback DB tests skipped (no local mysqli).\n");
}

echo json_encode([
    'passed' => count($passed),
    'failed' => count($failed),
    'failures' => $failed,
    'db_tests_ran' => $dbRan,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit(count($failed) > 0 ? 1 : 0);
