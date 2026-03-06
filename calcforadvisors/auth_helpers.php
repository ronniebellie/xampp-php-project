<?php
/**
 * Auth helpers for calcforadvisors subscriber login.
 * Include this in any page that requires a logged-in subscriber.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function calcforadvisors_require_login() {
    if (empty($_SESSION['calcforadvisors_subscriber_id'])) {
        $_SESSION['calcforadvisors_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
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
