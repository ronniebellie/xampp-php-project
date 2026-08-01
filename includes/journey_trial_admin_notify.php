<?php
/**
 * Administrator email when a Journey Premium 30-day trial is confirmed via Stripe webhook.
 *
 * Entitlement sync must already have succeeded. Delivery failure never reverses Premium.
 * Idempotent per stripe_subscription_id across related Stripe events / retries.
 */

if (defined('JOURNEY_TRIAL_ADMIN_NOTIFY_LOADED')) {
    return;
}
define('JOURNEY_TRIAL_ADMIN_NOTIFY_LOADED', 1);

require_once __DIR__ . '/send_email.php';

/**
 * Resolve the admin recipient from email config (never expose in customer HTML/JS).
 */
function journey_trial_notification_recipient(): ?string
{
    $configPath = __DIR__ . '/email_config.php';
    if (!is_file($configPath)) {
        return null;
    }
    $config = require $configPath;
    if (!is_array($config)) {
        return null;
    }
    $email = trim((string) ($config['journey_trial_notification_email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $email;
}

/**
 * Format a unix timestamp for admin notification copy (America/Chicago).
 */
function journey_trial_notify_format_datetime(?int $ts, bool $dateOnly = false): string
{
    if ($ts === null || $ts <= 0) {
        return 'n/a';
    }
    try {
        $dt = (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone('America/Chicago'));
    } catch (Throwable $e) {
        return 'n/a';
    }
    return $dateOnly ? $dt->format('F j, Y') : $dt->format('F j, Y \a\t g:i A T');
}

/**
 * @param object|array $subscription
 * @return array{email:?string,full_name:?string}
 */
function journey_trial_notify_lookup_user(mysqli $conn, int $userId): array
{
    $out = ['email' => null, 'full_name' => null];
    if ($userId <= 0) {
        return $out;
    }
    try {
        $stmt = $conn->prepare('SELECT email, full_name FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return $out;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (is_array($row)) {
            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['full_name'] ?? ''));
            $out['email'] = $email !== '' ? $email : null;
            $out['full_name'] = $name !== '' ? $name : null;
        }
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: user lookup failed user_id=' . $userId);
    }
    return $out;
}

/**
 * Claim exclusive right to send admin trial notification for this subscription.
 *
 * @return string claimed|already_sent|already_claimed|reclaimed|error
 */
function journey_trial_admin_notification_claim(
    mysqli $conn,
    string $stripeSubscriptionId,
    ?string $stripeEventId,
    ?int $userId
): string {
    if ($stripeSubscriptionId === '' || strpos($stripeSubscriptionId, 'sub_') !== 0) {
        return 'error';
    }

    $eventId = $stripeEventId !== null && $stripeEventId !== '' ? $stripeEventId : null;
    $uid = $userId !== null && $userId > 0 ? $userId : null;

    $escSub = $conn->real_escape_string($stripeSubscriptionId);
    $eventSql = $eventId === null ? 'NULL' : ("'" . $conn->real_escape_string($eventId) . "'");
    $userSql = $uid === null ? 'NULL' : (string) (int) $uid;

    try {
        $ok = $conn->query(
            "INSERT INTO journey_admin_trial_notifications (
                stripe_subscription_id, stripe_event_id, user_id, delivery_status
             ) VALUES (
                '{$escSub}', {$eventSql}, {$userSql}, 'sending'
             )"
        );
        if ($ok) {
            return 'claimed';
        }
    } catch (Throwable $e) {
        // Duplicate key → check existing row below.
    }

    try {
        $res = $conn->query(
            "SELECT delivery_status FROM journey_admin_trial_notifications
             WHERE stripe_subscription_id = '{$escSub}' LIMIT 1"
        );
        $row = $res ? $res->fetch_assoc() : null;
        if (!is_array($row)) {
            return 'error';
        }
        $status = (string) ($row['delivery_status'] ?? '');
        if ($status === 'sent' || $status === 'skipped') {
            return 'already_sent';
        }
        if ($status === 'failed') {
            $upd = $conn->query(
                "UPDATE journey_admin_trial_notifications
                 SET delivery_status = 'sending',
                     stripe_event_id = {$eventSql},
                     user_id = COALESCE({$userSql}, user_id),
                     delivery_error = NULL
                 WHERE stripe_subscription_id = '{$escSub}'
                   AND delivery_status = 'failed'"
            );
            return ($upd && $conn->affected_rows > 0) ? 'reclaimed' : 'already_sent';
        }
        // sending — another attempt in progress or crashed mid-send; do not double-send.
        return 'already_claimed';
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: claim lookup failed sub=' . $stripeSubscriptionId);
        return 'error';
    }
}

/**
 * @param string $deliveryStatus sent|failed|skipped
 */
function journey_trial_admin_notification_mark(
    mysqli $conn,
    string $stripeSubscriptionId,
    string $deliveryStatus,
    ?string $deliveryError = null
): void {
    $allowed = ['sent', 'failed', 'skipped'];
    if (!in_array($deliveryStatus, $allowed, true)) {
        return;
    }
    $escSub = $conn->real_escape_string($stripeSubscriptionId);
    $errSql = $deliveryError === null || $deliveryError === ''
        ? 'NULL'
        : ("'" . $conn->real_escape_string(substr($deliveryError, 0, 255)) . "'");
    try {
        $conn->query(
            "UPDATE journey_admin_trial_notifications
             SET delivery_status = '{$deliveryStatus}',
                 delivery_error = {$errSql}
             WHERE stripe_subscription_id = '{$escSub}'"
        );
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: mark failed sub=' . $stripeSubscriptionId);
    }
}

/**
 * Build plain-text admin body (no card data, no planning inputs, no secrets).
 *
 * @param array{
 *   full_name?:?string,
 *   email?:?string,
 *   trial_start?:?int,
 *   trial_end?:?int,
 *   user_id?:?int,
 *   stripe_customer_id?:?string,
 *   stripe_subscription_id?:?string
 * } $info
 */
function journey_trial_admin_notification_build_body(array $info): string
{
    $name = trim((string) ($info['full_name'] ?? ''));
    if ($name === '') {
        $name = 'n/a';
    }
    $email = trim((string) ($info['email'] ?? ''));
    if ($email === '') {
        $email = 'n/a';
    }
    $started = journey_trial_notify_format_datetime(
        isset($info['trial_start']) ? (int) $info['trial_start'] : null
    );
    $ends = journey_trial_notify_format_datetime(
        isset($info['trial_end']) ? (int) $info['trial_end'] : null,
        true
    );
    $userId = isset($info['user_id']) && (int) $info['user_id'] > 0
        ? (string) (int) $info['user_id']
        : 'n/a';
    $cus = trim((string) ($info['stripe_customer_id'] ?? ''));
    if ($cus === '') {
        $cus = 'n/a';
    }
    $sub = trim((string) ($info['stripe_subscription_id'] ?? ''));
    if ($sub === '') {
        $sub = 'n/a';
    }

    return "New Journey Premium Trial Started\n\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "Trial started: {$started}\n"
        . "Trial ends: {$ends}\n"
        . "Journey account user ID: {$userId}\n"
        . "Stripe customer: {$cus}\n"
        . "Stripe subscription: {$sub}\n\n"
        . "The user's 30-day Journey Premium trial is now active.\n";
}

/**
 * After a confirmed Journey Premium sync, optionally email the administrator.
 *
 * Safe to call after entitlement commit. Never throws; never affects Premium access.
 *
 * @param object|array $subscription
 * @param array $syncResult from journey_sync_subscription_row()
 * @param array{
 *   send_email?: callable(string,string,string):bool,
 *   recipient?: string,
 *   now?: int
 * } $options
 * @return array{attempted:bool,result:string,detail:string}
 */
function journey_maybe_notify_admin_of_trial(
    mysqli $conn,
    $subscription,
    array $syncResult,
    ?string $stripeEventId = null,
    array $options = []
): array {
    $skip = static function (string $detail): array {
        return ['attempted' => false, 'result' => 'skipped', 'detail' => $detail];
    };

    try {
        if (($syncResult['ok'] ?? false) !== true) {
            return $skip('sync_not_ok');
        }
        if (($syncResult['reason'] ?? '') !== 'synced') {
            return $skip((string) ($syncResult['reason'] ?? 'not_synced'));
        }
        if (($syncResult['product'] ?? '') !== 'journey') {
            return $skip('non_journey');
        }
        if (($syncResult['entitlement_status'] ?? '') !== 'trialing') {
            return $skip('not_trialing');
        }

        $arr = function_exists('journey_stripe_object_to_array')
            ? journey_stripe_object_to_array($subscription)
            : (array) $subscription;

        $subId = (string) ($syncResult['stripe_subscription_id'] ?? ($arr['id'] ?? ''));
        if ($subId === '' || strpos($subId, 'sub_') !== 0) {
            return $skip('missing_subscription_id');
        }

        $stripeStatus = (string) ($arr['status'] ?? '');
        if ($stripeStatus !== '' && $stripeStatus !== 'trialing') {
            return $skip('stripe_status_not_trialing');
        }

        $userId = isset($syncResult['user_id']) ? (int) $syncResult['user_id'] : 0;
        if ($userId <= 0 && !empty($arr['metadata']['user_id']) && is_numeric($arr['metadata']['user_id'])) {
            $userId = (int) $arr['metadata']['user_id'];
        }

        $customerId = $syncResult['stripe_customer_id'] ?? ($arr['customer'] ?? '');
        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? '';
        }
        $customerId = is_string($customerId) ? $customerId : '';

        $trialStart = null;
        $trialEnd = null;
        if (function_exists('journey_parse_time_value')) {
            $trialStart = journey_parse_time_value($arr['trial_start'] ?? null);
            $trialEnd = journey_parse_time_value($arr['trial_end'] ?? null);
        } else {
            if (isset($arr['trial_start']) && is_numeric($arr['trial_start'])) {
                $trialStart = (int) $arr['trial_start'];
            }
            if (isset($arr['trial_end']) && is_numeric($arr['trial_end'])) {
                $trialEnd = (int) $arr['trial_end'];
            }
        }
        if ($trialStart === null && isset($options['now'])) {
            $trialStart = (int) $options['now'];
        }

        $claim = journey_trial_admin_notification_claim($conn, $subId, $stripeEventId, $userId > 0 ? $userId : null);
        if ($claim === 'already_sent' || $claim === 'already_claimed') {
            return ['attempted' => false, 'result' => 'duplicate', 'detail' => $claim];
        }
        if ($claim === 'error') {
            error_log('journey_trial_admin_notify: claim error sub=' . $subId);
            return ['attempted' => false, 'result' => 'claim_error', 'detail' => 'claim_failed'];
        }

        $recipient = isset($options['recipient']) ? trim((string) $options['recipient']) : '';
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $recipient = (string) (journey_trial_notification_recipient() ?? '');
        }
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            journey_trial_admin_notification_mark($conn, $subId, 'skipped', 'recipient_unconfigured');
            error_log('journey_trial_admin_notify: recipient unconfigured; trial active sub=' . $subId);
            return ['attempted' => false, 'result' => 'skipped', 'detail' => 'recipient_unconfigured'];
        }

        $user = $userId > 0
            ? journey_trial_notify_lookup_user($conn, $userId)
            : ['email' => null, 'full_name' => null];

        $subject = 'New Journey Premium Trial Started';
        $body = journey_trial_admin_notification_build_body([
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'trial_start' => $trialStart,
            'trial_end' => $trialEnd,
            'user_id' => $userId > 0 ? $userId : null,
            'stripe_customer_id' => $customerId,
            'stripe_subscription_id' => $subId,
        ]);

        // Guard: never include known sensitive planning keywords (defense in depth).
        $sensitive = ['portfolio', 'social security', 'vanguard', 'password', 'card number', 'cvv'];
        $bodyLower = strtolower($body);
        foreach ($sensitive as $needle) {
            if (strpos($bodyLower, $needle) !== false) {
                journey_trial_admin_notification_mark($conn, $subId, 'failed', 'body_content_guard');
                error_log('journey_trial_admin_notify: body content guard tripped sub=' . $subId);
                return ['attempted' => true, 'result' => 'failed', 'detail' => 'body_content_guard'];
            }
        }

        $mailer = $options['send_email'] ?? null;
        if (!is_callable($mailer)) {
            $mailer = 'send_email_smtp';
        }

        $sent = false;
        try {
            $sent = (bool) $mailer($recipient, $subject, $body);
        } catch (Throwable $e) {
            $sent = false;
            error_log('journey_trial_admin_notify: mailer exception sub=' . $subId);
        }

        if ($sent) {
            journey_trial_admin_notification_mark($conn, $subId, 'sent');
            error_log('journey_trial_admin_notify: sent sub=' . $subId . ' user_id=' . ($userId > 0 ? $userId : 0));
            return ['attempted' => true, 'result' => 'sent', 'detail' => 'ok'];
        }

        $err = function_exists('rb_send_email_last_error') ? rb_send_email_last_error() : null;
        $errCode = is_string($err) && $err !== '' ? $err : 'send_failed';
        journey_trial_admin_notification_mark($conn, $subId, 'failed', $errCode);
        error_log(
            'journey_trial_admin_notify: delivery failed sub=' . $subId
            . ' err=' . $errCode
            . ' (Premium entitlement unchanged)'
        );
        return ['attempted' => true, 'result' => 'failed', 'detail' => $errCode];
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: unexpected failure (Premium entitlement unchanged)');
        return ['attempted' => false, 'result' => 'error', 'detail' => 'unexpected_exception'];
    }
}
