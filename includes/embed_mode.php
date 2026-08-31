<?php
declare(strict_types=1);

function rb_is_embed_mode(): bool
{
    return isset($_GET['embed']) && $_GET['embed'] === '1';
}

function rb_safe_calculator_return_url(string $value): ?string
{
    if (!filter_var($value, FILTER_VALIDATE_URL)) return null;
    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    $host = strtolower((string) parse_url($value, PHP_URL_HOST));
    $allowed = ['ronbelisle.com','www.ronbelisle.com','calcforadvisors.com','www.calcforadvisors.com','calcforadvisors.ronbelisle.com'];
    return $scheme === 'https' && in_array($host, $allowed, true) ? $value : null;
}
