<?php
/**
 * Site-wide PHP session bootstrap for ronbelisle.com.
 * "Stay logged in" uses a marker cookie so every page extends the session correctly.
 */
if (defined('RB_SESSION_BOOTSTRAP_LOADED')) {
    return;
}
define('RB_SESSION_BOOTSTRAP_LOADED', 1);

const RB_SESSION_REMEMBER_LIFETIME = 60 * 60 * 24 * 30; // 30 days

function rb_session_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function rb_session_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'domain' => '',
        'secure' => rb_session_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function rb_session_remember_active(): bool
{
    return isset($_COOKIE['rb_remember']) && $_COOKIE['rb_remember'] === '1';
}

function rb_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    // Keep the existing PHP session name for compatibility, but apply the
    // same cookie-only, host-only policy to every consumer session.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $lifetime = 0;
    if (rb_session_remember_active()) {
        ini_set('session.gc_maxlifetime', (string) RB_SESSION_REMEMBER_LIFETIME);
        $lifetime = RB_SESSION_REMEMBER_LIFETIME;
    }

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => rb_session_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (rb_session_remember_active()) {
        rb_session_refresh_remember_cookies();
    }
}

function rb_session_refresh_remember_cookies(): void
{
    $expires = time() + RB_SESSION_REMEMBER_LIFETIME;
    setcookie('rb_remember', '1', rb_session_cookie_options($expires));
    setcookie(session_name(), session_id(), rb_session_cookie_options($expires));
}

function rb_session_set_remember(bool $remember): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if ($remember) {
        $_SESSION['remember_me'] = true;
        ini_set('session.gc_maxlifetime', (string) RB_SESSION_REMEMBER_LIFETIME);
        session_regenerate_id(true);
        rb_session_refresh_remember_cookies();
        return;
    }

    unset($_SESSION['remember_me']);
    rb_session_clear_remember_marker();
    rb_session_refresh_standard_session_cookie();
}

function rb_session_clear_remember_marker(): void
{
    $expired = rb_session_cookie_options(time() - 3600);
    setcookie('rb_remember', '', $expired);
}

function rb_session_refresh_standard_session_cookie(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    setcookie(session_name(), session_id(), rb_session_cookie_options(0));
}

function rb_session_clear_remember_cookies(): void
{
    rb_session_clear_remember_marker();
    if (session_status() === PHP_SESSION_ACTIVE) {
        setcookie(session_name(), '', rb_session_cookie_options(time() - 3600));
    }
}
