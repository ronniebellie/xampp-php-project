<?php
if (defined('CALCFORADVISORS_CSRF_LOADED')) { return; }
define('CALCFORADVISORS_CSRF_LOADED', 1);
function calcforadvisors_csrf_token(): string { if (empty($_SESSION['calcforadvisors_csrf_token']) || !is_string($_SESSION['calcforadvisors_csrf_token'])) { $_SESSION['calcforadvisors_csrf_token'] = bin2hex(random_bytes(32)); } return $_SESSION['calcforadvisors_csrf_token']; }
function calcforadvisors_csrf_rotate(): string { $_SESSION['calcforadvisors_csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['calcforadvisors_csrf_token']; }
function calcforadvisors_csrf_validate(?string $token): bool { $expected = $_SESSION['calcforadvisors_csrf_token'] ?? ''; return is_string($token) && $token !== '' && is_string($expected) && $expected !== '' && hash_equals($expected, $token); }
function calcforadvisors_csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(calcforadvisors_csrf_token(), ENT_QUOTES, 'UTF-8') . '">'; }
