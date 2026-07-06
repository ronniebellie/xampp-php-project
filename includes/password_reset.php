<?php
/**
 * Password reset tokens for ronbelisle.com users (HMAC-signed, no DB storage).
 */
require_once __DIR__ . '/config_bootstrap.php';
require_once __DIR__ . '/stripe_config.php';

$cfg = rb_config();
$auth = $cfg['auth'] ?? [];

rb_define('RB_PASSWORD_RESET_SECRET', $auth['password_reset_secret'] ?? rb_env('RB_PASSWORD_RESET_SECRET'));

function rb_password_reset_secret(): string {
    if (defined('RB_PASSWORD_RESET_SECRET') && RB_PASSWORD_RESET_SECRET !== '') {
        return RB_PASSWORD_RESET_SECRET;
    }
    if (defined('CALCFORADVISORS_AUTH_SECRET')
        && CALCFORADVISORS_AUTH_SECRET !== ''
        && CALCFORADVISORS_AUTH_SECRET !== 'replace-with-random-secret-32chars') {
        return CALCFORADVISORS_AUTH_SECRET;
    }
    return '';
}

function rb_password_reset_configured(): bool {
    return rb_password_reset_secret() !== '';
}

function rb_password_reset_create_token(string $email, int $ttlSeconds = 86400): string {
    $expiry = time() + $ttlSeconds;
    $payload = base64_encode($email) . '.' . base64_encode((string) $expiry);
    $sig = hash_hmac('sha256', $payload, rb_password_reset_secret());
    return $payload . '.' . $sig;
}

/** @return string|false email on success */
function rb_password_reset_verify_token(string $token) {
    if (!rb_password_reset_configured()) {
        return false;
    }
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    [$encEmail, $encExpiry, $sig] = $parts;
    $payload = $encEmail . '.' . $encExpiry;
    $expected = hash_hmac('sha256', $payload, rb_password_reset_secret());
    if (!hash_equals($expected, $sig)) {
        return false;
    }
    $email = base64_decode($encEmail, true);
    $expiry = (int) base64_decode($encExpiry, true);
    if ($email === false || $expiry < time()) {
        return false;
    }
    return $email;
}

function rb_auth_base_url(): string {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    if ($host === 'www.ronbelisle.com' || $host === 'ronbelisle.com') {
        return 'https://ronbelisle.com';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
