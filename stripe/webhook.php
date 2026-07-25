<?php
/**
 * Journey-aware Stripe webhook endpoint (Milestone 2).
 *
 * Route: https://ronbelisle.com/stripe/webhook.php
 *
 * Authoritative entitlement sync for product_key = journey via
 * user_product_subscriptions. Does not modify users.subscription_status
 * or calcforadvisors_subscribers.
 *
 * CFA continues to use calcforadvisors/stripe-webhook.php until a future
 * consolidation milestone.
 */

declare(strict_types=1);

// Never emit HTML/stack traces to Stripe.
ini_set('display_errors', '0');

$root = dirname(__DIR__);
require_once $root . '/includes/stripe_config.php';
require_once $root . '/includes/db_config.php';
require_once $root . '/includes/journey_stripe_sync.php';
require_once $root . '/vendor/autoload.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
if ($contentLength > 1048576) { // 1 MiB
    http_response_code(413);
    echo 'Payload Too Large';
    exit;
}

$payload = file_get_contents('php://input');
if (!is_string($payload) || $payload === '') {
    http_response_code(400);
    echo 'Empty payload';
    exit;
}
if (strlen($payload) > 1048576) {
    http_response_code(413);
    echo 'Payload Too Large';
    exit;
}

$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret = '';
if (defined('JOURNEY_STRIPE_WEBHOOK_SECRET') && JOURNEY_STRIPE_WEBHOOK_SECRET !== '' && JOURNEY_STRIPE_WEBHOOK_SECRET !== 'whsec_xxx') {
    $secret = (string) JOURNEY_STRIPE_WEBHOOK_SECRET;
} elseif (defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET !== '' && STRIPE_WEBHOOK_SECRET !== 'whsec_xxx') {
    $secret = (string) STRIPE_WEBHOOK_SECRET;
}

if ($secret === '') {
    http_response_code(500);
    error_log('stripe/webhook: webhook signing secret not configured');
    echo 'Webhook not configured';
    exit;
}

if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
    http_response_code(500);
    error_log('stripe/webhook: STRIPE_SECRET_KEY not configured');
    echo 'Stripe not configured';
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log('stripe/webhook: signature verification failed');
    echo 'Invalid signature';
    exit;
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    error_log('stripe/webhook: invalid payload');
    echo 'Invalid payload';
    exit;
}

$retrieveSubscription = static function (string $subscriptionId) {
    try {
        return \Stripe\Subscription::retrieve($subscriptionId);
    } catch (Throwable $e) {
        error_log('stripe/webhook: subscription retrieve failed');
        return null;
    }
};

$retrieveCheckoutSession = static function (string $sessionId) {
    try {
        return \Stripe\Checkout\Session::retrieve($sessionId, ['expand' => ['line_items', 'subscription']]);
    } catch (Throwable $e) {
        error_log('stripe/webhook: checkout session retrieve failed');
        return null;
    }
};

$result = journey_process_verified_stripe_event($conn, $event, [
    'retrieve_subscription' => $retrieveSubscription,
    'retrieve_checkout_session' => $retrieveCheckoutSession,
]);

http_response_code((int) ($result['http_status'] ?? 500));
// Minimal non-sensitive body for operators / Stripe retries.
echo (string) ($result['result'] ?? 'error');
exit;
