<?php
require_once __DIR__ . '/config_bootstrap.php';

// Stripe config is loaded from /etc/ronbelisle/config.php (prod) or environment variables.
// This file is intentionally committed (no secrets inside).
$cfg = rb_config();

// Test mode is opt-in and uses a separate environment-variable namespace.
// Production remains unchanged unless RB_STRIPE_MODE=test is explicitly set.
$stripeMode = strtolower((string) rb_env('RB_STRIPE_MODE', 'live'));
$stripeTestMode = in_array($stripeMode, ['test', 'sandbox'], true);
$stripe = $stripeTestMode
    ? (($cfg['stripe']['test'] ?? []))
    : ($cfg['stripe'] ?? []);
$stripeEnv = $stripeTestMode ? 'RB_STRIPE_TEST_' : 'RB_';

$stripeValue = static function (string $configKey, string $envKey) use ($stripe, $stripeEnv) {
    return $stripe[$configKey] ?? rb_env($stripeEnv . $envKey);
};

rb_define('STRIPE_PUBLIC_KEY', $stripeValue('public_key', 'PUBLIC_KEY'));
rb_define('STRIPE_SECRET_KEY', $stripeValue('secret_key', 'SECRET_KEY'));

rb_define('STRIPE_PRICE_MONTHLY', $stripeValue('price_monthly', 'PRICE_MONTHLY'));
rb_define('STRIPE_PRICE_ANNUAL', $stripeValue('price_annual', 'PRICE_ANNUAL'));

rb_define('CALCFORADVISORS_PRICE_MONTHLY', $stripe['calcforadvisors_price_monthly'] ?? rb_env('RB_CALCFORADVISORS_PRICE_MONTHLY'));
rb_define('CALCFORADVISORS_PRICE_ANNUAL', $stripe['calcforadvisors_price_annual'] ?? rb_env('RB_CALCFORADVISORS_PRICE_ANNUAL'));

// Journey Premium Price/Product IDs from /etc/ronbelisle/config.php (or env).
// Prefer stripe.journey_* config keys; RB_JOURNEY_* / JOURNEY_* env fallbacks also accepted.
rb_define(
    'JOURNEY_STRIPE_PRODUCT_ID',
    $stripe['journey_product_id']
        ?? rb_env('RB_JOURNEY_STRIPE_PRODUCT_ID')
        ?? rb_env('JOURNEY_STRIPE_PRODUCT_ID')
);
rb_define(
    'JOURNEY_STRIPE_MONTHLY_PRICE_ID',
    $stripe['journey_monthly_price_id']
        ?? rb_env('RB_JOURNEY_STRIPE_MONTHLY_PRICE_ID')
        ?? rb_env('JOURNEY_STRIPE_MONTHLY_PRICE_ID')
);
rb_define(
    'JOURNEY_STRIPE_ANNUAL_PRICE_ID',
    $stripe['journey_annual_price_id']
        ?? rb_env('RB_JOURNEY_STRIPE_ANNUAL_PRICE_ID')
        ?? rb_env('JOURNEY_STRIPE_ANNUAL_PRICE_ID')
);

rb_define('STRIPE_WEBHOOK_SECRET', $stripe['webhook_secret'] ?? rb_env('RB_STRIPE_WEBHOOK_SECRET'));
// Optional dedicated signing secret for /stripe/webhook.php; falls back to STRIPE_WEBHOOK_SECRET.
rb_define(
    'JOURNEY_STRIPE_WEBHOOK_SECRET',
    $stripe['journey_webhook_secret']
        ?? rb_env('RB_JOURNEY_STRIPE_WEBHOOK_SECRET')
        ?? rb_env('JOURNEY_STRIPE_WEBHOOK_SECRET')
);
rb_define('CALCFORADVISORS_AUTH_SECRET', $stripe['calcforadvisors_auth_secret'] ?? rb_env('RB_CALCFORADVISORS_AUTH_SECRET'));

rb_define('CALCFORADVISORS_BASE_URL', $stripe['calcforadvisors_base_url'] ?? rb_env('RB_CALCFORADVISORS_BASE_URL', 'https://calcforadvisors.com'));
?>
