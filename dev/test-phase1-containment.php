<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/password_tokens.php';
require_once __DIR__ . '/../includes/bridge_security.php';
require_once __DIR__ . '/../includes/scenario_request.php';
$checks = 0;
function expectPhase1(bool $ok, string $message): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException($message);
}

// Isolated session storage; never touch the XAMPP/production session directory.
$sessionDir = sys_get_temp_dir() . '/rb-phase1-' . bin2hex(random_bytes(8));
mkdir($sessionDir, 0700);
ini_set('session.save_path', $sessionDir);
register_shutdown_function(static function () use ($sessionDir): void {
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    foreach (glob($sessionDir . '/*') ?: [] as $file) unlink($file);
    rmdir($sessionDir);
});
rb_session_start();
$oldId = session_id();
$oldCsrf = rb_csrf_token();
$_SESSION['preserved'] = 'existing workflow';
rb_bridge_authenticate(7, 'monthly');
expectPhase1(session_id() !== $oldId, 'Bridge must regenerate session ID');
expectPhase1(!file_exists($sessionDir . '/sess_' . $oldId), 'Previous session must be deleted');
expectPhase1(!rb_csrf_validate($oldCsrf), 'Bridge must invalidate previous CSRF token');
expectPhase1($_SESSION['calcforadvisors_subscriber_id'] === 7, 'Bridge sets authenticated subscriber');
expectPhase1($_SESSION['preserved'] === 'existing workflow', 'Bridge preserves unrelated session state');

foreach (['/rmd-impact/', '/retirement-plan/', '/'] as $path) expectPhase1(rb_bridge_redirect($path) === $path, 'Allowed local path');
foreach (['//evil.test', '///evil.test', 'https://evil.test', '/\\evil.test', '/%2fevil.test', '/%252fevil.test', "/\nevil", "/x\r\nLocation: https://evil.test", '/../evil', '/x?redirect=//evil.test', ['bad'], null] as $bad) {
    expectPhase1(rb_bridge_redirect($bad) === '/rmd-impact/', 'Reject unsafe redirect');
}

$secret = 'test-only-secret-not-a-production-credential';
foreach (['advisor-password', 'consumer-password'] as $purpose) {
    $hash = password_hash('old test password', PASSWORD_DEFAULT);
    $token = rb_password_token_create('fixture@example.test', $hash, $purpose, $secret, 100);
    $expiry = (int) explode('.', $token)[2];
    expectPhase1(rb_password_token_email($token) === 'fixture@example.test', 'Token identity');
    expectPhase1(rb_password_token_verify($token, $hash, $purpose, $secret, $expiry - 1), 'Unexpired token works');
    expectPhase1(!rb_password_token_verify($token, $hash, $purpose, $secret, $expiry), 'Exact expiry is rejected');
    expectPhase1(!rb_password_token_verify($token, $hash, $purpose, $secret, $expiry + 1), 'Expired token is rejected');
    expectPhase1(!rb_password_token_verify($token, $hash, $purpose, 'wrong-secret'), 'Wrong secret is rejected');
    expectPhase1(!rb_password_token_verify($token, $hash, 'wrong-purpose', $secret), 'Cross-product token rejected');
    expectPhase1(!rb_password_token_verify($token . '0', $hash, $purpose, $secret), 'Tampering rejected');
    expectPhase1(!rb_password_token_verify($token, password_hash('new password', PASSWORD_DEFAULT), $purpose, $secret), 'Password change invalidates token');
    expectPhase1(!rb_password_token_verify($token, $hash, $purpose, ''), 'Empty secret fails closed');
}
expectPhase1(rb_password_token_email(base64_encode('fixture@example.test') . '.' . base64_encode((string) (time() + 100)) . '.' . str_repeat('a', 64)) === null, 'Legacy replayable token rejected');
$initial = rb_password_token_create('fixture@example.test', null, 'advisor-password', $secret);
expectPhase1(rb_password_token_verify($initial, null, 'advisor-password', $secret), 'Initial password setup supports NULL hash');
expectPhase1(!rb_password_token_verify($initial, 'changed', 'advisor-password', $secret), 'Initial setup link invalidated');

// Two requests can both validate the old hash. Only one may win the conditional update.
class Phase1PasswordStore {
    public ?string $hash = null;
    public int $id = 7;
    public bool $active = true;
    public function prepare(string $sql) {
        expectPhase1(strpos($sql, 'AND BINARY password_hash <=> BINARY ?') !== false, 'Compare exact password state atomically');
        return new Phase1PasswordStatement($this, strpos($sql, "status = 'active'") !== false);
    }
}
class Phase1PasswordStatement {
    public int $affected_rows = 0;
    private array $values;
    private Phase1PasswordStore $store;
    private bool $requiresActive;
    public function __construct($store, $active) { $this->store = $store; $this->requiresActive = $active; }
    public function bind_param($types, &...$values) { $this->values = $values; }
    public function execute() {
        [$newHash, $id, $oldHash] = $this->values;
        if ($id === $this->store->id && $oldHash === $this->store->hash && (!$this->requiresActive || $this->store->active)) {
            $this->store->hash = $newHash;
            $this->affected_rows = 1;
        }
    }
    public function close() {}
}
foreach (['users', 'calcforadvisors_subscribers'] as $table) {
    $db = new Phase1PasswordStore();
    expectPhase1(rb_password_token_redeem($db, $table, 7, null, 'first-hash'), 'First redemption succeeds');
    expectPhase1(!rb_password_token_redeem($db, $table, 7, null, 'second-hash'), 'Concurrent replay loses conditional update');
    expectPhase1($db->hash === 'first-hash', 'Replay cannot overwrite first password');
}
$db = new Phase1PasswordStore(); $db->active = false;
expectPhase1(!rb_password_token_redeem($db, 'calcforadvisors_subscribers', 7, null, 'hash'), 'Inactive subscriber cannot redeem');

$valid = ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json; charset=utf-8', 'HTTP_X_CSRF_TOKEN' => rb_csrf_token()];
expectPhase1(rb_scenario_request_error($valid) === null, 'Valid mutation headers accepted');
foreach ([['REQUEST_METHOD', 'GET', 405], ['CONTENT_TYPE', 'text/plain', 415], ['HTTP_X_CSRF_TOKEN', '', 403], ['HTTP_X_CSRF_TOKEN', 'wrong', 403], ['HTTP_X_CSRF_TOKEN', [], 403], ['CONTENT_LENGTH', RB_SCENARIO_MAX_BYTES + 1, 413]] as [$key, $value, $status]) {
    expectPhase1(rb_scenario_request_error(array_replace($valid, [$key => $value]))[0] === $status, 'Reject invalid request: ' . $key);
}
$missing = $valid; unset($missing['HTTP_X_CSRF_TOKEN']);
expectPhase1(rb_scenario_request_error($missing)[0] === 403, 'Missing CSRF rejected');
foreach (['null', '[]', '123', '{', '{"x":NaN}', '{"x":1e309}'] as $body) expectPhase1(rb_scenario_decode($body)[1][0] === 400, 'Malformed/non-object JSON rejected');
expectPhase1(rb_scenario_decode(str_repeat(' ', RB_SCENARIO_MAX_BYTES + 1))[1][0] === 413, 'Body length checked without Content-Length');
$scenario = ['calculator_type' => 'survivor_gap', 'scenario_name' => 'Test plan', 'scenario_data' => ['balance' => 100]];
expectPhase1(rb_scenario_data_error($scenario, 'save') === null, 'Existing calculator type and data accepted');
foreach ([['scenario_name', []], ['scenario_name', ' '], ['scenario_name', str_repeat('x', 256)], ['calculator_type', '../bad'], ['scenario_data', 'bad']] as [$key, $value]) {
    expectPhase1(rb_scenario_data_error(array_replace($scenario, [$key => $value]), 'save')[0] === 400, 'Invalid payload rejected');
}
foreach ([1, '1'] as $id) expectPhase1(rb_scenario_data_error(['scenario_id' => $id], 'delete') === null, 'Valid database ID accepted');
foreach ([0, -1, 1.5, true, '1x', '1e2', [], null, '99999999999999999999999999'] as $id) expectPhase1(rb_scenario_data_error(['scenario_id' => $id], 'delete')[0] === 400, 'Malformed ID rejected');

// Verified production limits differ between the consumer and advisor tables.
foreach (['user' => [100, 50], 'cfa' => [255, 64]] as $ownerType => [$nameLimit, $typeLimit]) {
    $boundary = ['scenario_name' => str_repeat('é', $nameLimit), 'calculator_type' => str_repeat('a', $typeLimit), 'scenario_data' => ['v' => 'fixture']];
    expectPhase1(rb_scenario_data_error($boundary, 'save') === null, 'Names use Unicode character counts, not UTF-8 bytes');
    expectPhase1(rb_scenario_storage_error($boundary, $ownerType) === null, 'Exact owner-specific name/type limits accepted');
    expectPhase1(rb_scenario_storage_error(array_replace($boundary, ['scenario_name' => $boundary['scenario_name'] . 'x']), $ownerType)[0] === 400, 'Name beyond owner storage limit rejected');
    expectPhase1(rb_scenario_storage_error(array_replace($boundary, ['calculator_type' => $boundary['calculator_type'] . 'a']), $ownerType)[0] === 400, 'Type beyond owner storage limit rejected');
    $boundary['scenario_data'] = ['v' => str_repeat('x', 65527)];
    expectPhase1(strlen(json_encode($boundary['scenario_data'])) === 65535, 'TEXT boundary fixture has exact encoded byte size');
    expectPhase1(rb_scenario_storage_error($boundary, $ownerType) === null, 'Exact TEXT capacity accepted');
    $boundary['scenario_data']['v'] .= 'x';
    expectPhase1(rb_scenario_storage_error($boundary, $ownerType)[0] === 413, 'TEXT overflow rejected before insert');
    $boundary['scenario_data'] = ['v' => str_repeat('é', 11000)];
    expectPhase1(rb_scenario_storage_error($boundary, $ownerType)[0] === 413, 'JSON escaping counts toward storage byte limit');
}

// Guard integration and unchanged ownership boundaries.
foreach (['save', 'delete'] as $op) {
    $source = file_get_contents(__DIR__ . '/../api/' . $op . '_scenario.php');
    expectPhase1(strpos($source, "rb_scenario_read_request('{$op}')") < strpos($source, 'db_config.php'), 'Reject before DB access');
    expectPhase1(strpos($source, '$owner = get_scenario_owner();') !== false, 'Owner remains authenticated');
}
echo "Phase 1 containment tests passed ({$checks} checks).\n";
