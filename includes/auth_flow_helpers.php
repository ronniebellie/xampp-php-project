<?php
/**
 * Shared helpers for login/register redirect and Premium trial signup flow.
 */

if (!function_exists('rb_auth_safe_redirect_path')) {
    function rb_auth_safe_redirect_path(string $path): string
    {
        if ($path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
            return '/';
        }

        return $path;
    }
}

if (!function_exists('rb_auth_set_trial_intent')) {
    function rb_auth_set_trial_intent(): void
    {
        $_SESSION['auth_intent'] = 'trial';
    }
}

if (!function_exists('rb_auth_is_trial_intent')) {
    function rb_auth_is_trial_intent(): bool
    {
        if (($_GET['intent'] ?? '') === 'trial') {
            return true;
        }

        if (($_SESSION['auth_intent'] ?? '') === 'trial') {
            return true;
        }

        return rb_auth_safe_redirect_path($_SESSION['redirect_after_login'] ?? '') === '/subscribe.php';
    }
}

if (!function_exists('rb_auth_capture_trial_intent_from_request')) {
    function rb_auth_capture_trial_intent_from_request(): void
    {
        if (($_GET['intent'] ?? '') === 'trial') {
            rb_auth_set_trial_intent();
        }
    }
}

if (!function_exists('rb_auth_redirect_to_login')) {
    function rb_auth_redirect_to_login(string $return_path, ?string $intent = null): void
    {
        $_SESSION['redirect_after_login'] = rb_auth_safe_redirect_path($return_path);

        if ($intent === 'trial') {
            rb_auth_set_trial_intent();
            header('Location: /auth/register.php?intent=trial');
            exit;
        }

        header('Location: /auth/login.php');
        exit;
    }
}

if (!function_exists('rb_auth_redirect_to_trial_signup')) {
    function rb_auth_redirect_to_trial_signup(?string $email = null): void
    {
        if ($email !== null && $email !== '') {
            $_SESSION['trial_signup_email'] = $email;
        }

        header('Location: /auth/register.php?intent=trial');
        exit;
    }
}

if (!function_exists('rb_auth_login_user')) {
    function rb_auth_login_user(array $user, bool $remember = false): void
    {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['subscription_status'] = $user['subscription_status'] ?? 'free';

        rb_session_set_remember($remember);
    }
}

if (!function_exists('rb_auth_redirect_after_auth')) {
    function rb_auth_redirect_after_auth(): void
    {
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = rb_auth_safe_redirect_path($_SESSION['redirect_after_login']);
            unset($_SESSION['redirect_after_login']);
        } elseif (rb_auth_is_trial_intent()) {
            $redirect = '/subscribe.php';
        } else {
            $redirect = '/';
        }

        unset($_SESSION['auth_intent']);

        header('Location: ' . $redirect);
        exit;
    }
}

if (!function_exists('rb_auth_intent_query')) {
    function rb_auth_intent_query(): string
    {
        return rb_auth_is_trial_intent() ? '?intent=trial' : '';
    }
}
