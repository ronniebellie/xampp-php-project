<?php
/**
 * Record confirmed Journey Premium trial activations for the admin dashboard.
 *
 * Idempotent per stripe_subscription_id. Does NOT send administrator email
 * (SendGrid / paid email is intentionally unused). Entitlement is unaffected.
 */

if (defined('JOURNEY_TRIAL_ADMIN_NOTIFY_LOADED')) {
    return;
}
define('JOURNEY_TRIAL_ADMIN_NOTIFY_LOADED', 1);

/**
 * @deprecated Email delivery removed; kept only so older config readers do not fatals.
 */
function journey_trial_notification_recipient(): ?string
{
    return null;
}

/**
 * Claim / record a confirmed trial activation row (no email).
 *
 * @return string recorded|duplicate|error
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

    try {
        // Build insert with nullable event/user via discrete prepared statements.
        if ($eventId !== null && $uid !== null) {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, stripe_event_id, user_id, delivery_status, delivery_error, viewed_at)
                 VALUES (?, ?, ?, \'recorded\', NULL, NULL)'
            );
            if (!$stmt) {
                return 'error';
            }
            $stmt->bind_param('ssi', $stripeSubscriptionId, $eventId, $uid);
        } elseif ($eventId !== null) {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, stripe_event_id, user_id, delivery_status, delivery_error, viewed_at)
                 VALUES (?, ?, NULL, \'recorded\', NULL, NULL)'
            );
            if (!$stmt) {
                return 'error';
            }
            $stmt->bind_param('ss', $stripeSubscriptionId, $eventId);
        } elseif ($uid !== null) {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, stripe_event_id, user_id, delivery_status, delivery_error, viewed_at)
                 VALUES (?, NULL, ?, \'recorded\', NULL, NULL)'
            );
            if (!$stmt) {
                return 'error';
            }
            $stmt->bind_param('si', $stripeSubscriptionId, $uid);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO journey_admin_trial_notifications
                    (stripe_subscription_id, stripe_event_id, user_id, delivery_status, delivery_error, viewed_at)
                 VALUES (?, NULL, NULL, \'recorded\', NULL, NULL)'
            );
            if (!$stmt) {
                return 'error';
            }
            $stmt->bind_param('s', $stripeSubscriptionId);
        }

        if ($stmt->execute()) {
            $stmt->close();
            return 'recorded';
        }
        $errno = (int) $stmt->errno;
        $stmt->close();
        if ($errno === 1062) {
            // Fall through to duplicate handling.
        }
    } catch (Throwable $e) {
        // Duplicate key → already recorded.
    }

    try {
        $stmt = $conn->prepare(
            'SELECT id, delivery_status FROM journey_admin_trial_notifications
             WHERE stripe_subscription_id = ? LIMIT 1'
        );
        if (!$stmt) {
            return 'error';
        }
        $stmt->bind_param('s', $stripeSubscriptionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (is_array($row)) {
            // Normalize legacy email-era rows to recorded (no further delivery attempts).
            $status = (string) ($row['delivery_status'] ?? '');
            if (in_array($status, ['sending', 'failed', 'sent', 'skipped'], true)) {
                $upd = $conn->prepare(
                    'UPDATE journey_admin_trial_notifications
                     SET delivery_status = \'recorded\', delivery_error = NULL
                     WHERE stripe_subscription_id = ?
                       AND delivery_status IN (\'sending\', \'failed\', \'sent\', \'skipped\')'
                );
                if ($upd) {
                    $upd->bind_param('s', $stripeSubscriptionId);
                    $upd->execute();
                    $upd->close();
                }
            }
            return 'duplicate';
        }
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: claim lookup failed');
    }
    return 'error';
}

/**
 * @deprecated No-op retained for older callers/tests; email delivery removed.
 */
function journey_trial_admin_notification_mark(
    mysqli $conn,
    string $stripeSubscriptionId,
    string $deliveryStatus,
    ?string $deliveryError = null
): void {
    // Intentionally empty — delivery statuses are no longer meaningful.
}

/**
 * After a confirmed Journey Premium trial sync, record the activation for admin UI.
 *
 * Never throws; never affects Premium access; never sends email.
 *
 * @param object|array $subscription
 * @param array $syncResult from journey_sync_subscription_row()
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

        // Ignore obsolete email options if callers still pass them.
        unset($options['send_email'], $options['recipient']);

        $claim = journey_trial_admin_notification_claim(
            $conn,
            $subId,
            $stripeEventId,
            $userId > 0 ? $userId : null
        );

        if ($claim === 'duplicate') {
            return ['attempted' => false, 'result' => 'duplicate', 'detail' => 'already_recorded'];
        }
        if ($claim === 'error') {
            error_log('journey_trial_admin_notify: record error sub=' . $subId);
            return ['attempted' => false, 'result' => 'claim_error', 'detail' => 'record_failed'];
        }

        error_log('journey_trial_admin_notify: recorded trial sub=' . $subId . ' user_id=' . ($userId > 0 ? $userId : 0));
        return ['attempted' => true, 'result' => 'recorded', 'detail' => 'ok'];
    } catch (Throwable $e) {
        error_log('journey_trial_admin_notify: unexpected failure (Premium entitlement unchanged)');
        return ['attempted' => false, 'result' => 'error', 'detail' => 'unexpected_exception'];
    }
}
