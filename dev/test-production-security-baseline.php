<?php
declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $contents = file_get_contents($root . '/' . $path);
    check(is_string($contents), "read {$path}");
    return $contents;
};

$advisorHtaccess = $read('calcforadvisors/.htaccess');
foreach (['X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy', 'Strict-Transport-Security'] as $header) {
    check(strpos($advisorHtaccess, $header) !== false, "CalcForAdvisors sets {$header}");
}
foreach (['includes|vendor', 'sql|md', 'db_config\\.php'] as $protection) {
    check(strpos($advisorHtaccess, $protection) !== false, "CalcForAdvisors protects {$protection}");
}

$login = $read('calcforadvisors/login.php');
check(strpos($login, "isset(\$_GET['debug'])") === false, 'login has no public debug switch');
check(strpos($login, 'calcforadvisors_csrf_validate') !== false, 'login validates CSRF');
check(strpos($login, 'calcforadvisors_csrf_field') !== false, 'login emits CSRF token');

foreach (['calcforadvisors/register-free.php', 'calcforadvisors/trial-setup.php'] as $path) {
    $source = $read($path);
    check(strpos($source, 'calcforadvisors_csrf_validate') !== false, "{$path} validates CSRF");
    check(strpos($source, 'calcforadvisors_csrf_field') !== false, "{$path} emits CSRF token");
}

$helpers = $read('calcforadvisors/auth_helpers.php');
check(strpos($helpers, "parse_url(\$requestUri, PHP_URL_PATH)") !== false, 'post-login redirect stores a local path');
check(strpos($helpers, "'?debug=1'") === false, 'auth redirect does not propagate debug mode');

$home = $read('calcforadvisors/index.html');
check(substr_count($home, 'rel="canonical"') === 1, 'advisor homepage has one canonical link');

$checkout = $read('calcforadvisors/create-checkout-session.php');
check(strpos($checkout, "\$_SERVER['HTTP_HOST']") === false, 'checkout redirects do not trust the Host header');
check(strpos($checkout, "htmlspecialchars(\$e->getMessage())") === false, 'checkout does not disclose Stripe errors');

$scenarioSave = $read('api/save_scenario.php');
check(strpos($scenarioSave, "'Server error: '") === false, 'scenario API does not disclose fatal errors');
check(strpos($scenarioSave, "'Failed to save scenario' .") === false, 'scenario API does not disclose database errors');

echo "Production security baseline tests passed (22 checks).\n";
