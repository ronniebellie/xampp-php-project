<?php
/**
 * Private administrator authorization for /admin/*.
 *
 * Admins are logged-in consumer accounts whose email is on an allowlist
 * from /etc/ronbelisle/config.php (or env). Not exposed to public navigation.
 */

if (defined('RB_ADMIN_AUTH_LOADED')) {
    return;
}
define('RB_ADMIN_AUTH_LOADED', 1);

require_once __DIR__ . '/config_bootstrap.php';
require_once __DIR__ . '/auth_flow_helpers.php';

/**
 * @return list<string> Lowercased unique admin emails
 */
function rb_admin_allowed_emails(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $cfg = rb_config();
    $found = [];

    $adminCfg = $cfg['admin'] ?? [];
    if (is_array($adminCfg)) {
        if (!empty($adminCfg['emails']) && is_array($adminCfg['emails'])) {
            foreach ($adminCfg['emails'] as $e) {
                $found[] = (string) $e;
            }
        }
        if (!empty($adminCfg['email'])) {
            $found[] = (string) $adminCfg['email'];
        }
    }

    $emailCfg = $cfg['email'] ?? [];
    if (is_array($emailCfg)) {
        if (!empty($emailCfg['admin_email'])) {
            $found[] = (string) $emailCfg['admin_email'];
        }
        if (!empty($emailCfg['admin_emails']) && is_array($emailCfg['admin_emails'])) {
            foreach ($emailCfg['admin_emails'] as $e) {
                $found[] = (string) $e;
            }
        }
        // Reuse the previously configured trial-notification recipient as an admin allowlist entry.
        if (!empty($emailCfg['journey_trial_notification_email'])) {
            $found[] = (string) $emailCfg['journey_trial_notification_email'];
        }
    }

    $envSingle = rb_env('RB_ADMIN_EMAIL');
    if (is_string($envSingle) && $envSingle !== '') {
        $found[] = $envSingle;
    }
    $envList = rb_env('RB_ADMIN_EMAILS');
    if (is_string($envList) && $envList !== '') {
        foreach (explode(',', $envList) as $e) {
            $found[] = $e;
        }
    }

    $out = [];
    foreach ($found as $raw) {
        $e = strtolower(trim((string) $raw));
        if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $out[$e] = true;
        }
    }
    $cached = array_keys($out);
    return $cached;
}

function rb_is_admin_email(?string $email): bool
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    foreach (rb_admin_allowed_emails() as $allowed) {
        if (hash_equals($allowed, $email)) {
            return true;
        }
    }
    return false;
}

/**
 * Resolve the current session user's email (session first, DB fallback).
 */
function rb_admin_session_email(mysqli $conn): string
{
    $fromSession = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    if ($fromSession !== '' && filter_var($fromSession, FILTER_VALIDATE_EMAIL)) {
        return $fromSession;
    }
    $uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($uid <= 0) {
        return '';
    }
    try {
        $stmt = $conn->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return strtolower(trim((string) ($row['email'] ?? '')));
    } catch (Throwable $e) {
        return '';
    }
}

function rb_is_admin_user(mysqli $conn): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return rb_is_admin_email(rb_admin_session_email($conn));
}

/**
 * Require an authenticated administrator. Redirects to login or exits 403.
 */
function rb_require_admin(mysqli $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        $return = $_SERVER['REQUEST_URI'] ?? '/admin/';
        if (!is_string($return) || $return === '' || $return[0] !== '/') {
            $return = '/admin/';
        }
        // Strip query string noise; keep path for post-login return.
        $path = parse_url($return, PHP_URL_PATH);
        $returnPath = is_string($path) && $path !== '' ? $path : '/admin/';
        rb_auth_redirect_to_login($returnPath);
    }

    if (!rb_is_admin_user($conn)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>Forbidden</title></head><body>'
            . '<p>You do not have access to this administrator area.</p>'
            . '<p><a href="/account.php">Back to account</a></p>'
            . '</body></html>';
        exit;
    }
}
