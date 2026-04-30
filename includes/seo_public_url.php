<?php
/**
 * Canonical public URL for SEO (Open Graph, JSON-LD, rel=canonical).
 *
 * - Main site (ronbelisle.com, www.ronbelisle.com): always https://ronbelisle.com + path
 * - Other hosts (subdomains, localhost): scheme from request + host + path
 *
 * Query strings are stripped so UTMs don't create duplicate canonicals.
 */
if (!function_exists('rb_seo_site_base_url')) {
    /**
     * Origin only (no path), for JSON-LD publisher URLs and sitemap-style links.
     */
    function rb_seo_site_base_url() {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'ronbelisle.com');

        if ($host === 'www.ronbelisle.com' || $host === 'ronbelisle.com') {
            return 'https://ronbelisle.com';
        }

        $proto = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $proto = 'https';
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            $proto = 'https';
        }

        return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
}

if (!function_exists('rb_seo_public_url')) {
    function rb_seo_public_url() {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'ronbelisle.com');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($uri, '?');
        if ($path === false || $path === '') {
            $path = '/';
        }
        // Normalize homepage variants so canonical URLs don't include /index.php.
        if ($path === '/index.php') {
            $path = '/';
        }

        if ($host === 'www.ronbelisle.com' || $host === 'ronbelisle.com') {
            return 'https://ronbelisle.com' . $path;
        }

        $proto = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $proto = 'https';
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            $proto = 'https';
        }

        return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
    }
}
