<?php
/**
 * Common init for calcforadvisors PHP pages.
 * Resolves paths for local vs production (sibling dir structure).
 */
$root = dirname(dirname(__DIR__)); // project root (parent of calcforadvisors)
$includes = file_exists($root . '/includes/stripe_config.php') ? $root . '/includes' : $root . '/html/includes';
$vendor = file_exists($root . '/vendor/autoload.php') ? $root . '/vendor' : $root . '/html/vendor';

define('CALCFORADVISORS_INCLUDES', $includes);
define('CALCFORADVISORS_VENDOR', $vendor);
