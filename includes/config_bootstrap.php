<?php
/**
 * Centralized configuration loader.
 *
 * Priority:
 * 1) RB_CONFIG_FILE env var (absolute path)
 * 2) /etc/ronbelisle/config.php (production)
 * 3) environment variables
 *
 * The external config file should return an array like:
 * [
 *   'db' => ['host' => '', 'name' => '', 'user' => '', 'pass' => ''],
 *   'stripe' => [...],
 *   'openai' => [...],
 *   'email' => [...],
 * ]
 */

function rb_env(string $key, $default = null) {
    $v = getenv($key);
    if ($v === false || $v === '') return $default;
    return $v;
}

function rb_config(): array {
    static $cfg = null;
    if (is_array($cfg)) return $cfg;

    $path = rb_env('RB_CONFIG_FILE');
    if (!$path) $path = '/etc/ronbelisle/config.php';

    if (is_string($path) && $path !== '' && file_exists($path)) {
        $loaded = require $path;
        if (is_array($loaded)) {
            $cfg = $loaded;
            return $cfg;
        }
    }

    $cfg = [];
    return $cfg;
}

function rb_define(string $name, $value): void {
    if (defined($name)) return;
    if ($value === null || $value === '') return;
    define($name, $value);
}

