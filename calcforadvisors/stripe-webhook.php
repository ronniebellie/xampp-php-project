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

        $isCalcforadvisors = $priceId && isCalcforadvisorsPrice($priceId);
        if (!$isCalcforadvisors) {
            $successUrl = $session->success_url ?? '';
            $isCalcforadvisors = (strpos($successUrl, 'calcforadvisors.com') !== false);
        }
        if (!$isCalcforadvisors) {
            error_log('calcforadvisors webhook: skipping - priceId=' . ($priceId ?? 'null') . ', success_url=' . ($session->success_url ?? ''));
            http_response_code(200);
            exit;
        }

        $plan = ($priceId && isCalcforadvisorsPrice($priceId)) ? planFromPriceId($priceId) : 'monthly';
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

        // Send welcome/set-password email
        if (defined('CALCFORADVISORS_AUTH_SECRET') && CALCFORADVISORS_AUTH_SECRET !== 'replace-with-random-secret-32chars'
            && defined('CALCFORADVISORS_BASE_URL')) {
            $expiry = time() + (60 * 60 * 24); // 24 hours
            $payload = base64_encode($email) . '.' . base64_encode((string)$expiry);
            $sig = hash_hmac('sha256', $payload, CALCFORADVISORS_AUTH_SECRET);
            $token = $payload . '.' . $sig;
            $url = rtrim(CALCFORADVISORS_BASE_URL, '/') . '/set-password.php?token=' . urlencode($token);

            $subject = 'Welcome to calcforadvisors.com – set up your account';
            $body = "Hi,\n\nThank you for subscribing to calcforadvisors.com. Set your password to access your account, manage billing, and get your white-label calculators:\n\n$url\n\nThis link expires in 24 hours. If you didn't subscribe, you can ignore this email.\n\n— calcforadvisors.com";
            $headers = "From: noreply@calcforadvisors.com\r\nReply-To: support@calcforadvisors.com\r\nContent-Type: text/plain; charset=UTF-8";

            if (@mail($email, $subject, $body, $headers)) {
                error_log('calcforadvisors webhook: sent welcome email to ' . $email);
            } else {
                error_log('calcforadvisors webhook: failed to send welcome email to ' . $email);
            }
        }

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
