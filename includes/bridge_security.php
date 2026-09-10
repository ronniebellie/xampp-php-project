<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/csrf.php';

function rb_bridge_redirect($redirect): string
{
    // Paths only. Reject encoded separators, dot segments, controls and protocol-relative URLs.
    if (!is_string($redirect) || !preg_match('~^/(?!/)[A-Za-z0-9/_-]*/?$~D', $redirect)) return '/rmd-impact/';
    return $redirect;
}

function rb_bridge_authenticate(int $id, string $plan): void
{
    $previousId = session_id();
    rb_session_regenerate_for_auth();
    if (session_status() !== PHP_SESSION_ACTIVE || session_id() === $previousId) {
        throw new RuntimeException('Authentication session could not be renewed.');
    }
    rb_csrf_rotate();
    $_SESSION['calcforadvisors_subscriber_id'] = $id;
    $_SESSION['calcforadvisors_plan'] = $plan;
}
