<?php
/** Advisor lifecycle endpoint. No browser return page grants entitlement. */
declare(strict_types=1);
ini_set('display_errors', '0');
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/stripe_config.php';
require_once CALCFORADVISORS_INCLUDES . '/db_config.php';
require_once CALCFORADVISORS_INCLUDES . '/cfa_subscription_sync.php';
require_once CALCFORADVISORS_VENDOR . '/autoload.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$payload = file_get_contents('php://input', false, null, 0, 1048577);
if (!is_string($payload) || strlen($payload) > 1048576) { http_response_code(413); exit; }
if (!defined('STRIPE_WEBHOOK_SECRET') || !STRIPE_WEBHOOK_SECRET || STRIPE_WEBHOOK_SECRET === 'whsec_xxx') { http_response_code(500); exit; }
try {
    $event = \Stripe\Webhook::constructEvent($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', STRIPE_WEBHOOK_SECRET);
} catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); exit;
}
try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $prices = [];
    foreach (['CALCFORADVISORS_PRICE_MONTHLY' => 'monthly', 'CALCFORADVISORS_PRICE_ANNUAL' => 'annual'] as $key => $plan) {
        if (defined($key) && constant($key) && constant($key) !== 'price_xxx') $prices[constant($key)] = $plan;
    }
    if (!$prices) throw new RuntimeException('Advisor prices not configured');
    echo cfa_process_subscription_event(new CfaSqlSubscriptionStore($conn), $event->toArray(),
        static fn(string $id): array => \Stripe\Subscription::retrieve($id)->toArray(), $prices);
    http_response_code(200);
} catch (Throwable $e) {
    error_log('Advisor lifecycle retry required: ' . $e->getMessage());
    http_response_code(500); echo 'retry';
}
