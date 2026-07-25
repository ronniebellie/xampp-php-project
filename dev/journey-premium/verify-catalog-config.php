<?php
/**
 * Verify Journey Stripe catalog IDs are loaded and classified correctly.
 *
 * Usage:
 *   php dev/journey-premium/verify-catalog-config.php
 *   php dev/journey-premium/verify-catalog-config.php \
 *     --expect-product=prod_... --expect-monthly=price_... --expect-annual=price_...
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/includes/journey_stripe_sync.php';

// Clear any accidental overrides from prior includes.
journey_price_id_overrides_set(null);

$opts = getopt('', ['expect-product::', 'expect-monthly::', 'expect-annual::']);
$passed = 0;
$failed = 0;

$check = static function (string $name, bool $ok, string $detail = '') use (&$passed, &$failed): void {
    if ($ok) {
        echo "PASS {$name}\n";
        $passed++;
        return;
    }
    echo "FAIL {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    $failed++;
};

$product = journey_stripe_product_id();
$monthly = journey_stripe_monthly_price_id();
$annual = journey_stripe_annual_price_id();

$check('product_id_set', $product !== '' && strpos($product, 'prod_') === 0, 'value missing or malformed');
$check('monthly_price_set', $monthly !== '' && strpos($monthly, 'price_') === 0, 'value missing or malformed');
$check('annual_price_set', $annual !== '' && strpos($annual, 'price_') === 0, 'value missing or malformed');
$check('checkout_config_ready', journey_stripe_checkout_config_ready() === true);

if (!empty($opts['expect-product'])) {
    $check('product_matches_expect', hash_equals((string) $opts['expect-product'], $product));
}
if (!empty($opts['expect-monthly'])) {
    $check('monthly_matches_expect', hash_equals((string) $opts['expect-monthly'], $monthly));
}
if (!empty($opts['expect-annual'])) {
    $check('annual_matches_expect', hash_equals((string) $opts['expect-annual'], $annual));
}

$check('monthly_classifies_journey', journey_classify_price_id($monthly) === 'journey');
$check('annual_classifies_journey', journey_classify_price_id($annual) === 'journey');

$consumerMonthly = defined('STRIPE_PRICE_MONTHLY') ? (string) STRIPE_PRICE_MONTHLY : '';
$consumerAnnual = defined('STRIPE_PRICE_ANNUAL') ? (string) STRIPE_PRICE_ANNUAL : '';
$cfaMonthly = defined('CALCFORADVISORS_PRICE_MONTHLY') ? (string) CALCFORADVISORS_PRICE_MONTHLY : '';
$cfaAnnual = defined('CALCFORADVISORS_PRICE_ANNUAL') ? (string) CALCFORADVISORS_PRICE_ANNUAL : '';

$check('consumer_monthly_set', $consumerMonthly !== '');
$check('consumer_annual_set', $consumerAnnual !== '');
$check('cfa_monthly_set', $cfaMonthly !== '');
$check('cfa_annual_set', $cfaAnnual !== '');

$check('consumer_monthly_not_journey', journey_classify_price_id($consumerMonthly) === 'consumer');
$check('consumer_annual_not_journey', journey_classify_price_id($consumerAnnual) === 'consumer');
$check('cfa_monthly_not_journey', journey_classify_price_id($cfaMonthly) === 'cfa');
$check('cfa_annual_not_journey', journey_classify_price_id($cfaAnnual) === 'cfa');
$check('unknown_not_journey', journey_classify_price_id('price_unknown_not_configured') === 'unknown');

// Ensure Journey IDs are distinct from other products.
$check('journey_monthly_distinct', $monthly !== $consumerMonthly && $monthly !== $cfaMonthly);
$check('journey_annual_distinct', $annual !== $consumerAnnual && $annual !== $cfaAnnual);

echo json_encode([
    'passed' => $passed,
    'failed' => $failed,
    'product_prefix' => $product !== '' ? substr($product, 0, 8) . '…' : '',
    'monthly_prefix' => $monthly !== '' ? substr($monthly, 0, 12) . '…' : '',
    'annual_prefix' => $annual !== '' ? substr($annual, 0, 12) . '…' : '',
], JSON_PRETTY_PRINT) . "\n";

exit($failed > 0 ? 1 : 0);
