<?php
/**
 * Shared Journey site header with account-status chrome mount point (M5 P2).
 */
include __DIR__ . '/analytics.php';
?>
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
<script src="/assets/js/journey-phase1-handoff.js?v=20260730-phase1-handoff" defer></script>
<script src="/assets/js/journey-sync.js?v=20260730-phase1-handoff" defer></script>
<script src="/assets/js/journey-auth-chrome.js?v=20260801-account-actions" defer></script>
<script src="/assets/js/journey-analytics.js?v=20260801-ga4" defer></script>
