<?php
/**
 * Shared Journey site header with account-status chrome mount point (M5 P2).
 */
include __DIR__ . '/analytics.php';
?>
<a class="journey-skip" href="#journey-main">Skip to main content</a>
<header class="site-header">
    <div class="site-header-inner">
        <a class="site-brand" href="/" aria-label="Retirement Planning Journey home">
            <span class="brand-mark" aria-hidden="true">RB</span>
            <span>Retirement Planning Journey</span>
        </a>
        <div
            class="journey-account-chrome"
            data-journey-account-chrome
            aria-live="polite"
            aria-busy="true"
        >
            <p class="journey-account-loading">Checking account…</p>
        </div>
    </div>
</header>
<noscript><p class="container">Enable JavaScript to calculate and save your Journey plan. You can still browse the six phase descriptions below.</p></noscript>
<script src="/assets/js/journey-phase1-handoff.js?v=20260914-audit" defer></script>
<script src="/assets/js/journey-sync.js?v=20260914-audit" defer></script>
<script src="/assets/js/journey-auth-chrome.js?v=20260914-audit" defer></script>
<script src="/assets/js/journey-analytics.js?v=20260914-audit" defer></script>
