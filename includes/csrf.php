<?php
/**
 * Lightweight CSRF tokens for form POSTs (session-backed).
 */

if (defined('RB_CSRF_LOADED')) {
    return;
}
define('RB_CSRF_LOADED', 1);

function rb_csrf_token(): string
{
    if (empty($_SESSION['rb_csrf_token']) || !is_string($_SESSION['rb_csrf_token'])) {
        $_SESSION['rb_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['rb_csrf_token'];
}

function rb_csrf_rotate(): string
{
    $_SESSION['rb_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['rb_csrf_token'];
}

function rb_csrf_field(): string
{
    $token = htmlspecialchars(rb_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function rb_csrf_validate(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }
    $expected = $_SESSION['rb_csrf_token'] ?? '';
    if (!is_string($expected) || $expected === '') {
        return false;
    }
    return hash_equals($expected, $token);
}
