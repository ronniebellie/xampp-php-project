<?php
/**
 * Shared session bootstrap for CalcForAdvisors.
 * Keeps the existing PHP session name until the planned cookie-name migration.
 */
if (defined('CALCFORADVISORS_SESSION_BOOTSTRAP_LOADED')) {
    return;
}
define('CALCFORADVISORS_SESSION_BOOTSTRAP_LOADED', 1);

function calcforadvisors_session_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function calcforadvisors_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => calcforadvisors_session_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
