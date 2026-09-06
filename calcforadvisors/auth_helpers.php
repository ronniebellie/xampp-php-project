<?php
/**
 * Auth helpers for calcforadvisors subscriber login.
 * Include this in any page that requires a logged-in subscriber.
 */
require_once __DIR__ . '/includes/session_bootstrap.php';
calcforadvisors_session_start();
require_once __DIR__ . '/includes/csrf.php';

function calcforadvisors_authenticate_subscriber(array $subscriber): void {
    $_SESSION['calcforadvisors_subscriber_id'] = (int) $subscriber['id'];
    $_SESSION['calcforadvisors_subscriber_email'] = (string) $subscriber['email'];
    $_SESSION['calcforadvisors_subscriber_plan'] = (string) $subscriber['plan'];
    $_SESSION['calcforadvisors_subscriber_status'] = (string) $subscriber['status'];
    calcforadvisors_session_regenerate_for_auth();
    calcforadvisors_csrf_rotate();
}

function calcforadvisors_require_login() {
    if (empty($_SESSION['calcforadvisors_subscriber_id'])) {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/account.php');
        $path = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
            $path = '/account.php';
        }
        $_SESSION['calcforadvisors_redirect_after_login'] = $path;
        header('Location: login.php');
        exit;
    }
}

function calcforadvisors_get_subscriber() {
    if (empty($_SESSION['calcforadvisors_subscriber_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['calcforadvisors_subscriber_id'],
        'email' => $_SESSION['calcforadvisors_subscriber_email'] ?? '',
        'plan' => $_SESSION['calcforadvisors_subscriber_plan'] ?? 'monthly',
        'status' => $_SESSION['calcforadvisors_subscriber_status'] ?? 'active',
    ];
}
