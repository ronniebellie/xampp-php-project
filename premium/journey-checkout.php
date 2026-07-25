<?php
/**
 * Journey Premium Checkout Session creator (Milestone 3).
 * POST only. Maps plan → configured Journey Price IDs. Does not grant entitlement.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/stripe_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_flow_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/csrf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/journey_checkout.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method Not Allowed';
    exit;
}

$plan = strtolower(trim((string) ($_POST['plan'] ?? '')));
$cancelPlan = in_array($plan, ['monthly', 'annual'], true) ? $plan : '';

if (!isset($_SESSION['user_id'])) {
    $return = '/premium/journey.php';
    if ($cancelPlan !== '') {
        $return .= '?plan=' . rawurlencode($cancelPlan);
    }
    rb_auth_redirect_to_login($return, 'journey_trial');
}

if (!rb_csrf_validate(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
    header('Location: /premium/journey.php?error=csrf' . ($cancelPlan !== '' ? '&plan=' . rawurlencode($cancelPlan) : ''));
    exit;
}

if (!journey_is_allowed_checkout_plan($plan)) {
    header('Location: /premium/journey.php?error=invalid_plan');
    exit;
}

if (!journey_stripe_checkout_config_ready()) {
    header('Location: /premium/journey.php?error=catalog');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || empty($user['email'])) {
    rb_auth_redirect_to_login('/premium/journey.php', 'journey_trial');
}

if (has_journey_premium_access($conn, $userId)) {
    header('Location: /premium/journey.php');
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'ronbelisle.com';
$baseUrl = $scheme . '://' . $host;

$successUrl = $baseUrl . '/premium/journey-success.php?session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/premium/journey.php?canceled=1&plan=' . rawurlencode($plan);

$customerId = journey_lookup_stripe_customer_id($conn, $userId);
$built = journey_build_checkout_session_params(
    $userId,
    $plan,
    (string) $user['email'],
    $successUrl,
    $cancelUrl,
    $customerId
);

if (empty($built['ok']) || empty($built['params'])) {
    $err = (string) ($built['error'] ?? 'stripe');
    $redir = 'invalid_plan';
    if ($err === 'catalog_not_configured') {
        $redir = 'catalog';
    } elseif ($err !== 'invalid_plan') {
        $redir = 'stripe';
    }
    header('Location: /premium/journey.php?error=' . rawurlencode($redir) . '&plan=' . rawurlencode($plan));
    exit;
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $session = \Stripe\Checkout\Session::create($built['params']);
    if (empty($session->url)) {
        throw new RuntimeException('missing_checkout_url');
    }
    header('Location: ' . $session->url);
    exit;
} catch (Throwable $e) {
    error_log('journey-checkout: ' . $e->getMessage());
    header('Location: /premium/journey.php?error=stripe&plan=' . rawurlencode($plan));
    exit;
}
