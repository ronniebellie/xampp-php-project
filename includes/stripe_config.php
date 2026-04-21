<?php
require_once __DIR__ . '/config_bootstrap.php';

// Stripe config is loaded from /etc/ronbelisle/config.php (prod) or environment variables.
// This file is intentionally committed (no secrets inside).
$cfg = rb_config();
$stripe = $cfg['stripe'] ?? [];

rb_define('STRIPE_PUBLIC_KEY', $stripe['public_key'] ?? rb_env('RB_STRIPE_PUBLIC_KEY'));
rb_define('STRIPE_SECRET_KEY', $stripe['secret_key'] ?? rb_env('RB_STRIPE_SECRET_KEY'));

rb_define('STRIPE_PRICE_MONTHLY', $stripe['price_monthly'] ?? rb_env('RB_STRIPE_PRICE_MONTHLY'));
rb_define('STRIPE_PRICE_ANNUAL', $stripe['price_annual'] ?? rb_env('RB_STRIPE_PRICE_ANNUAL'));

rb_define('CALCFORADVISORS_PRICE_MONTHLY', $stripe['calcforadvisors_price_monthly'] ?? rb_env('RB_CALCFORADVISORS_PRICE_MONTHLY'));
rb_define('CALCFORADVISORS_PRICE_ANNUAL', $stripe['calcforadvisors_price_annual'] ?? rb_env('RB_CALCFORADVISORS_PRICE_ANNUAL'));

rb_define('STRIPE_WEBHOOK_SECRET', $stripe['webhook_secret'] ?? rb_env('RB_STRIPE_WEBHOOK_SECRET'));
rb_define('CALCFORADVISORS_AUTH_SECRET', $stripe['calcforadvisors_auth_secret'] ?? rb_env('RB_CALCFORADVISORS_AUTH_SECRET'));

rb_define('CALCFORADVISORS_BASE_URL', $stripe['calcforadvisors_base_url'] ?? rb_env('RB_CALCFORADVISORS_BASE_URL', 'https://calcforadvisors.com'));
?>
