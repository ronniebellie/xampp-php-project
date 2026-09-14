<?php
/**
 * Shared Journey footer with subtle feedback link.
 */
$feedbackFrom = '';
if (!empty($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
    $host = 'journey.ronbelisle.com';
    $feedbackFrom = $scheme . '://' . $host . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
}
$feedbackHref = '/feedback.php';
if ($feedbackFrom !== '') {
    $feedbackHref .= '?from=' . rawurlencode($feedbackFrom);
}
if (!empty($active_phase) && is_string($active_phase)) {
    $feedbackHref .= (strpos($feedbackHref, '?') === false ? '?' : '&')
        . 'phase=' . rawurlencode($active_phase);
}
?>
<footer class="site-footer">
    <div class="container site-footer-inner">
        <a class="site-footer-feedback" href="<?php echo htmlspecialchars($feedbackHref, ENT_QUOTES, 'UTF-8'); ?>">
            Need help or found a problem?
        </a>
    </div>
</footer>
