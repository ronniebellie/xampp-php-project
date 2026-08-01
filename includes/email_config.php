<?php
require_once __DIR__ . '/config_bootstrap.php';

// Email config is loaded from /etc/ronbelisle/config.php (prod) or environment variables.
// This file is intentionally committed (no secrets inside).
$cfg = rb_config();
$email = $cfg['email'] ?? [];

return [
    // SendGrid HTTP API uses the API key here.
    'smtp_pass' => $email['smtp_pass'] ?? rb_env('RB_SMTP_PASS'),

    // Optional: used for sender identity.
    'from_email' => $email['from_email'] ?? rb_env('RB_EMAIL_FROM'),
    'from_name' => $email['from_name'] ?? rb_env('RB_EMAIL_FROM_NAME'),

    // Administrator allowlist helpers (used by /admin/ auth; not exposed to browsers).
    'admin_email' => $email['admin_email'] ?? rb_env('RB_ADMIN_EMAIL'),
    'journey_trial_notification_email' => $email['journey_trial_notification_email']
        ?? rb_env('RB_JOURNEY_TRIAL_NOTIFICATION_EMAIL')
        ?? ($email['admin_email'] ?? null)
        ?? rb_env('RB_ADMIN_EMAIL'),
];
