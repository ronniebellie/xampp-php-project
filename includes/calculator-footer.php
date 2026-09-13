<?php
/** Site footer for calculator pages (disclaimer, share, Premium link). */
include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php';

// Calculator pages already have a session; never initiate one after output.
if (session_status() === PHP_SESSION_ACTIVE) {
    require_once __DIR__ . '/csrf.php';
    echo '<script>window.rbScenarioCsrfToken = ' . json_encode(rb_csrf_token()) . ';</script>';
    echo '<script src="/js/scenario-api.js"></script>';
}
?>
<script src="/js/dialog-accessibility.js"></script>
<style>
:focus-visible { outline: 3px solid #1769aa; outline-offset: 3px; }
.rb-print-context { display: none; }
@media print {
  nav, button, .premium-upsell-banner, #explainResultsModalOverlay, #compareScenariosModalOverlay { display: none !important; }
  .rb-print-context { display: block; border-top: 1px solid #999; margin-top: 1em; font-size: 10pt; }
  table { width: 100%; } thead { display: table-header-group; } tr, canvas, svg { break-inside: avoid; }
  [style*="overflow"] { overflow: visible !important; max-height: none !important; }
}
</style>
<p class="rb-print-context">RonBelisle.com — educational planning illustration. Review the displayed inputs, timing, assumptions and supported statutory scope. Results are estimates, not guaranteed outcomes or personalized advice. Retain scenario inputs with this printout.</p>
