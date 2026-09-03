<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$expect = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$read = static function (string $path) use ($root): string {
    $source = file_get_contents($root . '/' . $path);
    if ($source === false) {
        throw new RuntimeException("Could not read {$path}");
    }
    return $source;
};

$consumerBootstrap = $read('includes/session_bootstrap.php');
foreach ([
    "ini_set('session.use_strict_mode', '1')",
    "ini_set('session.use_only_cookies', '1')",
    "'path' => '/'",
    "'domain' => ''",
    "'secure' => rb_session_is_https()",
    "'httponly' => true",
    "'samesite' => 'Lax'",
] as $required) {
    $expect(strpos($consumerBootstrap, $required) !== false, "Consumer session bootstrap is missing {$required}.");
}
$expect(strpos($consumerBootstrap, "session_name('") === false && strpos($consumerBootstrap, 'session_name("') === false, 'Consumer session cookie name changed during Phase 4A.');
$expect(strpos($consumerBootstrap, "'user_id'") === false, 'Consumer bootstrap must not alter login session keys.');
$expect(strpos($consumerBootstrap, 'function rb_session_regenerate_for_auth') !== false, 'Consumer auth regeneration helper is missing.');
$expect(strpos($consumerBootstrap, 'function rb_session_destroy') !== false, 'Consumer session-destruction helper is missing.');

$cfaBootstrap = $read('calcforadvisors/includes/session_bootstrap.php');
foreach ([
    "ini_set('session.use_strict_mode', '1')",
    "ini_set('session.use_only_cookies', '1')",
    "'path' => '/'",
    "'domain' => ''",
    "'secure' => calcforadvisors_session_is_https()",
    "'httponly' => true",
    "'samesite' => 'Lax'",
] as $required) {
    $expect(strpos($cfaBootstrap, $required) !== false, "CalcForAdvisors session bootstrap is missing {$required}.");
}
$expect(strpos($cfaBootstrap, "session_name('") === false && strpos($cfaBootstrap, 'session_name("') === false, 'CalcForAdvisors session cookie name changed during Phase 4A.');
$expect(strpos($cfaBootstrap, 'function calcforadvisors_session_regenerate_for_auth') !== false, 'CFA auth regeneration helper is missing.');
$expect(strpos($cfaBootstrap, 'function calcforadvisors_session_destroy') !== false, 'CFA session-destruction helper is missing.');

$consumerDirectStarts = [
    'ss-survivor-impact/index.php',
    'api/generate_ss_survivor_pdf.php',
    'api/export_ss_survivor_csv.php',
];
foreach ($consumerDirectStarts as $path) {
    $source = $read($path);
    $expect(strpos($source, 'rb_session_start();') !== false, "{$path} does not use the consumer session bootstrap.");
    $expect(!preg_match('/(?<!rb_)session_start\s*\(/', $source), "{$path} still directly starts a PHP session.");
}

$cfaSessionFiles = [
    'calcforadvisors/auth_helpers.php',
    'calcforadvisors/login.php',
    'calcforadvisors/logout.php',
    'calcforadvisors/register-free.php',
    'calcforadvisors/trial-setup.php',
    'calcforadvisors/get-calc-bridge-token.php',
];
foreach ($cfaSessionFiles as $path) {
    $source = $read($path);
    $expect(strpos($source, 'calcforadvisors_session_start();') !== false, "{$path} does not use the CalcForAdvisors session bootstrap.");
    $expect(!preg_match('/(?<!calcforadvisors_)session_start\s*\(/', $source), "{$path} still directly starts a PHP session.");
}

$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($phpFiles as $file) {
    $path = str_replace($root . '/', '', $file->getPathname());
    if (!str_ends_with($path, '.php') || str_starts_with($path, 'dev/') || str_starts_with($path, 'vendor/')) {
        continue;
    }
    $hasDirectSessionStart = false;
    foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
        if (is_array($token) && $token[0] === T_STRING && strtolower($token[1]) === 'session_start') {
            $hasDirectSessionStart = true;
            break;
        }
    }
    if (!$hasDirectSessionStart) {
        continue;
    }
    $expect(in_array($path, [
        'includes/session_bootstrap.php',
        'calcforadvisors/includes/session_bootstrap.php',
    ], true), "Production PHP file still directly starts a session: {$path}");
}

$loginHelpers = $read('includes/auth_flow_helpers.php');
foreach (["'user_id'", "'user_email'", "'user_name'", "'subscription_status'"] as $key) {
    $expect(strpos($loginHelpers, $key) !== false, "Consumer login key {$key} is no longer set.");
}
$expect(strpos($loginHelpers, 'rb_session_regenerate_for_auth();') !== false, 'Consumer login does not regenerate the session.');
$expect(strpos($loginHelpers, 'rb_csrf_rotate();') !== false, 'Consumer login does not rotate CSRF.');
$consumerLogout = $read('auth/logout.php');
$expect(strpos($consumerLogout, "!== 'POST'") !== false && strpos($consumerLogout, 'rb_csrf_validate') !== false, 'Consumer logout is not POST-only with CSRF validation.');
$expect(strpos($consumerLogout, 'rb_session_destroy();') !== false, 'Consumer logout does not fully destroy its session.');
$authHelpers = $read('calcforadvisors/auth_helpers.php');
foreach ([
    'calcforadvisors_subscriber_id',
    'calcforadvisors_subscriber_email',
    'calcforadvisors_subscriber_plan',
    'calcforadvisors_subscriber_status',
] as $key) {
    $expect(strpos($authHelpers, $key) !== false, "CalcForAdvisors session key {$key} is no longer used.");
}
$expect(strpos($authHelpers, 'calcforadvisors_session_regenerate_for_auth();') !== false, 'CFA authentication does not regenerate the session.');
$expect(strpos($authHelpers, 'calcforadvisors_csrf_rotate();') !== false, 'CFA authentication does not rotate CSRF.');
$cfaLogout = $read('calcforadvisors/logout.php');
$expect(strpos($cfaLogout, "!== 'POST'") !== false && strpos($cfaLogout, 'calcforadvisors_csrf_validate') !== false, 'CFA logout is not POST-only with CSRF validation.');
$expect(strpos($cfaLogout, 'calcforadvisors_session_destroy();') !== false, 'CFA logout does not fully destroy its session.');
$deploy = $read('deploy.sh');
$build = $read('scripts/build-release.sh');
$expect(strpos($build, 'COPYFILE_DISABLE=1') !== false && strpos($build, '--no-xattrs') !== false, 'Release build does not suppress macOS extended attributes.');
$expect(strpos($deploy, "-name '._*'") !== false, 'Deployment does not reject AppleDouble files after extraction.');

foreach (['api/load_scenarios.php', 'api/save_scenario.php', 'api/delete_scenario.php'] as $path) {
    $scenarioApi = $read($path);
    $expect(strpos($scenarioApi, 'get_scenario_owner') !== false, "{$path} no longer derives scenario ownership from the authenticated session.");
    $expect(!preg_match('/\$_(?:GET|POST|REQUEST)\s*\[\s*[\'\"](?:owner|owner_id|user_id|subscriber_id)[\'\"]\s*\]/', $scenarioApi), "{$path} trusts a browser-supplied owner ID.");
}

$journeyStatus = $read('premium/journey-status.php');
$expect(strpos($journeyStatus, 'rb_session_start();') !== false, 'Journey status no longer uses the consumer session bootstrap.');
$journeyHelper = $read('includes/journey_status.php');
$expect(strpos($journeyHelper, "'user_id'") !== false, 'Journey status no longer reads the consumer user session.');

$redirects = $read('includes/auth_flow_helpers.php');
$expect(strpos($redirects, 'rb_auth_safe_redirect_target') !== false, 'Consumer safe redirect helper is missing.');
$expect(strpos($redirects, 'journey.ronbelisle.com') !== false, 'Journey return URL compatibility was removed.');

if ($failures) {
    fwrite(STDERR, "Phase 4 auth/session tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Phase 4 auth/session tests passed ({$checks} checks).\n";
