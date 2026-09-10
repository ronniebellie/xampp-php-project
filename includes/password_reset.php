<?php
/**
 * Password reset tokens for ronbelisle.com users (password-state-bound, no token table).
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

require_once __DIR__ . '/password_tokens.php';

function rb_password_reset_create_token(string $email, ?string $passwordHash, int $ttlSeconds = 86400): string {
    return rb_password_token_create($email, $passwordHash, 'consumer-password', rb_password_reset_secret(), $ttlSeconds);
}

function rb_password_reset_verify_token(string $token, ?string $passwordHash): bool {
    return rb_password_token_verify($token, $passwordHash, 'consumer-password', rb_password_reset_secret());
}

function rb_auth_base_url(): string {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    if ($host === 'www.ronbelisle.com' || $host === 'ronbelisle.com') {
        return 'https://ronbelisle.com';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
