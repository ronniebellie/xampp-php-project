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

if (!function_exists('rb_auth_is_allowed_journey_return')) {
    function rb_auth_is_allowed_journey_return(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        if (strtolower((string) ($parts['host'] ?? '')) !== 'journey.ronbelisle.com') {
            return false;
        }
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '' || $path[0] !== '/') {
            return false;
        }
        return true;
    }
}

if (!function_exists('rb_auth_normalize_journey_return')) {
    function rb_auth_normalize_journey_return(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return 'https://journey.ronbelisle.com' . $path . $query . $fragment;
    }
}

if (!function_exists('rb_auth_safe_redirect_target')) {
    /**
     * Allow same-site relative paths or https://journey.ronbelisle.com/... returns.
     */
    function rb_auth_safe_redirect_target(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '/';
        }
        if ($target[0] === '/' && strpos($target, '//') !== 0) {
            return rb_auth_safe_redirect_path($target);
        }
        if (rb_auth_is_allowed_journey_return($target)) {
            return rb_auth_normalize_journey_return($target);
        }
        return '/';
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

if (!function_exists('rb_auth_capture_return_from_request')) {
    /**
     * Capture ?return= for post-auth redirects.
     * Free-account returns go to redirect_after_login.
     * Trial returns are stored as redirect_after_premium so subscribe/checkout still runs first.
     */
    function rb_auth_capture_return_from_request(): void
    {
        if (!isset($_GET['return']) || $_GET['return'] === '') {
            return;
        }

        $safe = rb_auth_safe_redirect_target((string) $_GET['return']);
        if ($safe === '/') {
            return;
        }

        if (rb_auth_is_trial_intent()) {
            $_SESSION['redirect_after_premium'] = $safe;
            return;
        }

        $_SESSION['redirect_after_login'] = $safe;
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
            $redirect = rb_auth_safe_redirect_target((string) $_SESSION['redirect_after_login']);
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

if (!function_exists('rb_auth_consume_premium_return')) {
    function rb_auth_consume_premium_return(): string
    {
        if (empty($_SESSION['redirect_after_premium'])) {
            return '';
        }
        $redirect = rb_auth_safe_redirect_target((string) $_SESSION['redirect_after_premium']);
        unset($_SESSION['redirect_after_premium']);
        return $redirect === '/' ? '' : $redirect;
    }
}

if (!function_exists('rb_auth_peek_premium_return')) {
    function rb_auth_peek_premium_return(): string
    {
        if (empty($_SESSION['redirect_after_premium'])) {
            return '';
        }
        $redirect = rb_auth_safe_redirect_target((string) $_SESSION['redirect_after_premium']);
        return $redirect === '/' ? '' : $redirect;
    }
}

if (!function_exists('rb_auth_intent_query')) {
    function rb_auth_intent_query(): string
    {
        return rb_auth_is_trial_intent() ? '?intent=trial' : '';
    }
}

if (!function_exists('rb_auth_companion_query')) {
    /**
     * Preserve trial intent and Journey return when switching between login/register.
     */
    function rb_auth_companion_query(): string
    {
        $parts = [];
        if (rb_auth_is_trial_intent()) {
            $parts[] = 'intent=trial';
        }

        $return = '';
        if (!empty($_SESSION['redirect_after_premium'])) {
            $return = rb_auth_safe_redirect_target((string) $_SESSION['redirect_after_premium']);
        } elseif (!empty($_SESSION['redirect_after_login'])) {
            $candidate = rb_auth_safe_redirect_target((string) $_SESSION['redirect_after_login']);
            if (strpos($candidate, 'https://journey.ronbelisle.com') === 0) {
                $return = $candidate;
            }
        }

        if ($return !== '' && $return !== '/') {
            $parts[] = 'return=' . rawurlencode($return);
        }

        return $parts ? ('?' . implode('&', $parts)) : '';
    }
}
