<?php
/** Site footer for calculator pages (disclaimer, share, Premium link). */
include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php';

// Calculator pages already have a session; never initiate one after output.
if (session_status() === PHP_SESSION_ACTIVE) {
    require_once __DIR__ . '/csrf.php';
    echo '<script>window.rbScenarioCsrfToken = ' . json_encode(rb_csrf_token()) . ';</script>';
    echo '<script src="/js/scenario-api.js"></script>';
}
