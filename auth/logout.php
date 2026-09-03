<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_flow_helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if (!rb_csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit;
}

$redirect = '/';
if (!empty($_POST['return'])) {
    $redirect = rb_auth_safe_redirect_target((string) $_POST['return']);
}

rb_session_destroy();

if ($redirect !== '' && $redirect[0] === '/' && strpos($redirect, '//') !== 0) {
    // Relative same-site path — resolve from /auth/ to site root.
    header('Location: ..' . ($redirect === '/' ? '/' : $redirect));
    exit();
}

header('Location: ' . $redirect);
exit();
