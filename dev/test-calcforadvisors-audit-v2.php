<?php
declare(strict_types=1);

$path = dirname(__DIR__) . '/sql/audit_calcforadvisors_phase2_v2.sql';
$sql = file_get_contents($path);
if ($sql === false) {
    fwrite(STDERR, "Could not read audit v2.\n");
    exit(1);
}

$withoutComments = preg_replace('/^[[:space:]]*--.*$/m', '', $sql);
$statements = array_values(array_filter(
    array_map('trim', explode(';', (string) $withoutComments)),
    static fn(string $statement): bool => $statement !== ''
));

$failures = [];
$checks = 0;
$expect = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) $failures[] = $message;
};

$expect(count($statements) === 19, 'Expected exactly 19 fixed audit statements.');
foreach ($statements as $index => $statement) {
    $expect((bool) preg_match('/^(SELECT|SHOW)\b/i', $statement), 'Statement ' . ($index + 1) . ' is not SELECT or SHOW.');
}

$forbidden = ['INSERT','UPDATE','DELETE','ALTER','DROP','CREATE','REPLACE','TRUNCATE','GRANT','REVOKE','CALL','LOAD','LOCK','UNLOCK'];
foreach ($forbidden as $keyword) {
    $expect(!preg_match('/^[[:space:]]*' . $keyword . '\b/im', (string) $withoutComments), "Forbidden {$keyword} statement found.");
}

$expect(stripos($sql, 'information_schema.TABLES') !== false, 'Missing absent-table-safe scenario discovery.');
$expect(stripos($sql, 'information_schema.COLUMNS') !== false, 'Missing absent-table-safe scenario column audit.');
$expect(stripos($sql, 'information_schema.STATISTICS') !== false, 'Missing absent-table-safe scenario index audit.');
$expect(!preg_match('/\b(?:FROM|JOIN)\s+calcforadvisors_scenarios\b/i', $sql), 'Audit directly references the optional scenario table.');
$expect(!preg_match('/SELECT\s+(?:id\s*,|email\s*,|password_hash\s*[,\n]|trial_login_token\s*[,\n]|stripe_customer_id\s*[,\n]|stripe_subscription_id\s*[,\n])/i', $sql), 'Audit appears to project a sensitive record value.');

if ($failures) {
    fwrite(STDERR, "CalcForAdvisors audit v2 tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "CalcForAdvisors audit v2 validation passed ({$checks} checks).\n";
