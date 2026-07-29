<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_flow_helpers.php';

$redirect = '/';
if (!empty($_GET['return'])) {
    $redirect = rb_auth_safe_redirect_target((string) $_GET['return']);
}

rb_session_clear_remember_cookies();
session_unset();
session_destroy();

if ($redirect !== '' && $redirect[0] === '/' && strpos($redirect, '//') !== 0) {
    // Relative same-site path — resolve from /auth/ to site root.
    header('Location: ..' . ($redirect === '/' ? '/' : $redirect));
    exit();
}

header('Location: ' . $redirect);
exit();
