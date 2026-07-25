<?php
/**
 * One-shot operator helper: install Journey Stripe Product/Price IDs into
 * /etc/ronbelisle/config.php (or RB_CONFIG_FILE).
 *
 * Usage (as root on production):
 *   php dev/journey-premium/install-catalog-config.php \
 *     --product=prod_... --monthly=price_... --annual=price_...
 *
 * Does not create Checkout, webhooks, or public UX. Not for web invocation.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$opts = getopt('', ['product:', 'monthly:', 'annual:', 'config::', 'dry-run']);
$product = isset($opts['product']) ? trim((string) $opts['product']) : '';
$monthly = isset($opts['monthly']) ? trim((string) $opts['monthly']) : '';
$annual = isset($opts['annual']) ? trim((string) $opts['annual']) : '';
$dryRun = array_key_exists('dry-run', $opts);

if ($product === '' || strpos($product, 'prod_') !== 0) {
    fwrite(STDERR, "Invalid --product (expected prod_…)\n");
    exit(1);
}
if ($monthly === '' || strpos($monthly, 'price_') !== 0) {
    fwrite(STDERR, "Invalid --monthly (expected price_…)\n");
    exit(1);
}
if ($annual === '' || strpos($annual, 'price_') !== 0) {
    fwrite(STDERR, "Invalid --annual (expected price_…)\n");
    exit(1);
}

$path = isset($opts['config']) && is_string($opts['config']) && $opts['config'] !== ''
    ? $opts['config']
    : (getenv('RB_CONFIG_FILE') ?: '/etc/ronbelisle/config.php');

if (!is_file($path) || !is_readable($path)) {
    fwrite(STDERR, "Config not readable: {$path}\n");
    exit(1);
}

$cfg = require $path;
if (!is_array($cfg) || !isset($cfg['stripe']) || !is_array($cfg['stripe'])) {
    fwrite(STDERR, "Invalid config structure (missing stripe array)\n");
    exit(1);
}

$cfg['stripe']['journey_product_id'] = $product;
$cfg['stripe']['journey_monthly_price_id'] = $monthly;
$cfg['stripe']['journey_annual_price_id'] = $annual;

$bak = $path . '.bak.journey_catalog_' . gmdate('Ymd\THis\Z');
if ($dryRun) {
    echo "dry_run=1\n";
    echo "would_backup={$bak}\n";
    echo "would_set_product=" . substr($product, 0, 12) . "…\n";
    echo "would_set_monthly=" . substr($monthly, 0, 12) . "…\n";
    echo "would_set_annual=" . substr($annual, 0, 12) . "…\n";
    exit(0);
}

if (!copy($path, $bak)) {
    fwrite(STDERR, "Backup failed\n");
    exit(1);
}
@chmod($bak, 0640);

$out = "<?php\nreturn " . var_export($cfg, true) . ";\n";
if (file_put_contents($path, $out) === false) {
    fwrite(STDERR, "Write failed\n");
    exit(1);
}
@chown($path, 'root');
@chgrp($path, 'www-data');
@chmod($path, 0640);

echo "backup={$bak}\n";
echo "config={$path}\n";
echo "wrote_ok=1\n";
echo "journey_product_id=" . substr($product, 0, 12) . "…\n";
echo "journey_monthly_price_id=" . substr($monthly, 0, 12) . "…\n";
echo "journey_annual_price_id=" . substr($annual, 0, 12) . "…\n";
