<?php
/**
 * Journey-aware Stripe webhook endpoint (Milestone 2).
 *
 * Route: https://ronbelisle.com/stripe/webhook.php
 *
 * Consumer lifecycle uses separate consumer storage; Journey uses
 * user_product_subscriptions. Does not modify users.subscription_status
 * or calcforadvisors_subscribers. Both configured signing secrets are accepted.
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
require_once $root . '/includes/consumer_checkout.php';
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

$payload = file_get_contents('php://input', false, null, 0, 1048577);
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
$secrets=[];
foreach(['JOURNEY_STRIPE_WEBHOOK_SECRET','STRIPE_WEBHOOK_SECRET'] as $key) {
    if(defined($key) && constant($key)!=='' && constant($key)!=='whsec_xxx')$secrets[]=(string)constant($key);
}
if(!$secrets){http_response_code(500);exit('Webhook not configured');}

if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
    http_response_code(500);
    error_log('stripe/webhook: STRIPE_SECRET_KEY not configured');
    echo 'Stripe not configured';
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$event=null;
foreach(array_unique($secrets) as $secret) {
    try{$event=\Stripe\Webhook::constructEvent($payload,$sig,$secret);break;}
    catch(\Stripe\Exception\SignatureVerificationException | \UnexpectedValueException $e){}
}
if($event===null){http_response_code(400);exit('Invalid signature or payload');}
try {
    $api=rb_consumer_stripe_api();
    cfa_process_subscription_event(new RbConsumerSubscriptionStore($conn),$event->toArray(),$api['subscription'],rb_consumer_prices());
} catch(Throwable $e){error_log('Consumer lifecycle retry required');http_response_code(500);exit('retry');}

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
