<?php
/** Journey Premium Customer Portal entry point. */

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/stripe_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_billing_portal.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: /auth/login.php?return=' . rawurlencode('/account.php'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method Not Allowed';
    exit;
}

if (!rb_csrf_validate(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
    $_SESSION['journey_billing_portal_notice'] = 'session_expired';
    header('Location: /account.php');
    exit;
}

$local = journey_manageable_subscription($conn, (int) $_SESSION['user_id']);
if ($local === null) {
    $_SESSION['journey_billing_portal_notice'] = 'not_manageable';
    header('Location: /account.php');
    exit;
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $subscription = \Stripe\Subscription::retrieve($local['stripe_subscription_id']);
    if (!journey_portal_subscription_matches($local, $subscription)) {
        throw new RuntimeException('journey_subscription_mismatch');
    }

    $session = \Stripe\BillingPortal\Session::create(
        journey_build_portal_session_params(
            $local['stripe_customer_id'],
            $local['stripe_subscription_id']
        )
    );
    if (empty($session->url)) {
        throw new RuntimeException('missing_portal_url');
    }
    header('Location: ' . $session->url);
    exit;
} catch (Throwable $e) {
    error_log('journey-billing-portal: unable to create Journey portal session');
    $_SESSION['journey_billing_portal_notice'] = 'error';
    header('Location: /account.php');
    exit;
}
