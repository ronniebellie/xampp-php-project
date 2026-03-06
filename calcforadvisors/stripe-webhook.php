<?php
/**
 * Stripe webhook handler for calcforadvisors subscriptions.
 *
 * Required in includes/stripe_config.php:
 *   STRIPE_WEBHOOK_SECRET  (whsec_... from Stripe Dashboard → Developers → Webhooks)
 *
 * Handles:
 *   checkout.session.completed  → Record new subscriber
 *   customer.subscription.deleted → Mark subscription canceled
 *   invoice.payment_failed     → Optional: flag for follow-up
 */
$root = dirname(__DIR__);
$includes = file_exists($root . '/includes/stripe_config.php') ? $root . '/includes' : $root . '/html/includes';
$vendor = file_exists($root . '/vendor/autoload.php') ? $root . '/vendor' : $root . '/html/vendor';

require_once $includes . '/stripe_config.php';
require_once $includes . '/db_config.php';
require_once $vendor . '/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!defined('STRIPE_WEBHOOK_SECRET') || STRIPE_WEBHOOK_SECRET === 'whsec_xxx') {
    http_response_code(500);
    error_log('calcforadvisors webhook: STRIPE_WEBHOOK_SECRET not configured');
    exit;
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig, STRIPE_WEBHOOK_SECRET);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log('calcforadvisors webhook: signature verification failed - ' . $e->getMessage());
    exit;
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    error_log('calcforadvisors webhook: invalid payload - ' . $e->getMessage());
    exit;
}

function isCalcforadvisorsPrice($priceId) {
    $monthly = defined('CALCFORADVISORS_PRICE_MONTHLY') ? CALCFORADVISORS_PRICE_MONTHLY : '';
    $annual = defined('CALCFORADVISORS_PRICE_ANNUAL') ? CALCFORADVISORS_PRICE_ANNUAL : '';
    return $priceId === $monthly || $priceId === $annual;
}

function planFromPriceId($priceId) {
    $annual = defined('CALCFORADVISORS_PRICE_ANNUAL') ? CALCFORADVISORS_PRICE_ANNUAL : '';
    return $priceId === $annual ? 'annual' : 'monthly';
}

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $customerId = $session->customer ?? $session->customer_details->email ?? null;
        $subscriptionId = $session->subscription ?? null;
        $email = $session->customer_details->email ?? $session->customer_email ?? '';

        if (!$subscriptionId || !$email) {
            error_log('calcforadvisors webhook: skipping - no subscriptionId or email');
            http_response_code(200);
            exit;
        }

        $priceId = null;
        if (isset($session->line_items) && !empty($session->line_items->data)) {
            $item = $session->line_items->data[0];
            $priceId = $item->price->id ?? ($item->price ?? null);
        }
        if (!$priceId && $session->id) {
            $fullSession = \Stripe\Checkout\Session::retrieve($session->id, ['expand' => ['line_items']]);
            if (!empty($fullSession->line_items->data)) {
                $item = $fullSession->line_items->data[0];
                $priceId = $item->price->id ?? ($item->price ?? null);
            }
        }

        if (!$priceId || !isCalcforadvisorsPrice($priceId)) {
            error_log('calcforadvisors webhook: skipping - priceId=' . ($priceId ?? 'null') . ', monthly=' . (defined('CALCFORADVISORS_PRICE_MONTHLY') ? CALCFORADVISORS_PRICE_MONTHLY : 'undef') . ', annual=' . (defined('CALCFORADVISORS_PRICE_ANNUAL') ? CALCFORADVISORS_PRICE_ANNUAL : 'undef'));
            http_response_code(200);
            exit;
        }

        $plan = planFromPriceId($priceId);
        $customerIdStr = is_string($customerId) ? $customerId : ($customerId ?? '');

        $stmt = $conn->prepare(
            'INSERT INTO calcforadvisors_subscribers (stripe_customer_id, stripe_subscription_id, email, plan, status) ' .
            'VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email=VALUES(email), plan=VALUES(plan), status=VALUES(status), updated_at=NOW()'
        );
        $status = 'active';
        $stmt->bind_param('sssss', $customerIdStr, $subscriptionId, $email, $plan, $status);
        $stmt->execute();
        $stmt->close();
        error_log('calcforadvisors webhook: inserted subscriber ' . $email);

        break;

    case 'customer.subscription.deleted':
        $sub = $event->data->object;
        $subId = $sub->id ?? null;
        if (!$subId) {
            http_response_code(200);
            exit;
        }

        $stmt = $conn->prepare('UPDATE calcforadvisors_subscribers SET status = ? WHERE stripe_subscription_id = ?');
        $status = 'canceled';
        $stmt->bind_param('ss', $status, $subId);
        $stmt->execute();
        $stmt->close();
        break;

    case 'invoice.payment_failed':
        $invoice = $event->data->object;
        $subId = $invoice->subscription ?? null;
        if ($subId) {
            $stmt = $conn->prepare('UPDATE calcforadvisors_subscribers SET status = ? WHERE stripe_subscription_id = ?');
            $status = 'past_due';
            $stmt->bind_param('ss', $status, $subId);
            $stmt->execute();
            $stmt->close();
        }
        break;

    default:
        break;
}

http_response_code(200);
