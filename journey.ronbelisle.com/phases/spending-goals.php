<?php
$active_phase = 'spending-goals';
$page_title = 'Spending & Goals | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260729-approaching-retirement">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="/" aria-label="Retirement Planning Journey home">
            <span class="brand-mark" aria-hidden="true">RB</span>
            <span>Retirement Planning Journey</span>
        </a>
    </header>

    <main>
        <section class="page-hero" aria-labelledby="phase-title">
            <div class="container phase-template-grid">
                <div class="phase-main-column">
                    <div class="phase-intro">
                        <p class="eyebrow">Phase 1</p>
                        <h1 id="phase-title">Spending &amp; Goals</h1>
                        <p class="page-lede">Use this phase to shape the spending plan you expect to need in retirement. You’ll estimate your current household spending, think through your retirement lifestyle goals, and create a monthly spending target for your initial retirement plan.</p>
                        <p class="phase-audience-note">This Journey is designed for people approaching retirement.</p>
                    </div>

                    <section class="current-record-overview" aria-labelledby="spending-plan-summary-title" data-spending-plan-summary hidden>
                        <p class="eyebrow">Phase 1 complete</p>
                        <div class="journey-success-message" role="status" data-spending-plan-success hidden>
                            <strong>Spending plan saved ✓</strong>
                        </div>
                        <div class="record-overview-heading">
                            <h2 id="spending-plan-summary-title">Phase 1 complete</h2>
                            <span class="record-status-badge is-current">Complete</span>
                        </div>
                        <p class="phase-complete-lede">You’ve created the monthly spending target that the rest of your retirement plan will build on.</p>
                        <div class="result-metrics compact-results">
                            <div class="result-metric is-primary">
                                <span>Monthly target</span>
                                <strong data-spending-plan-field="monthlyTarget">$0</strong>
                            </div>
                            <div class="result-metric">
                                <span>Annual target</span>
                                <strong data-spending-plan-field="annualTarget">$0</strong>
                            </div>
                            <div class="result-metric">
                                <span>Other regular income</span>
                                <strong data-spending-plan-field="otherIncomeMonthly">$0</strong>
                            </div>
                            <div class="result-metric">
                                <span>Last updated</span>
                                <strong data-spending-plan-field="lastUpdated">Not saved yet</strong>
                            </div>
                        </div>
                        <div class="record-overview-actions">
                            <a class="primary-action" href="/phases/continue-to-phase-2.php">Continue to Phase 2: Social Security</a>
                            <a class="secondary-action" href="/calculators/retirement-spending-plan/">Update Spending Plan</a>
                        </div>
                    </section>

                    <article class="planning-panel">
                        <h2>Why this step matters</h2>
                        <p>Your spending target is the foundation for the rest of your retirement plan. Before you estimate Social Security timing, withdrawals, taxes, or investment risk, it helps to know what lifestyle those income sources need to support.</p>

                        <h2>What you'll accomplish</h2>
                        <p>You will estimate a realistic monthly spending target for retirement and note the lifestyle goals you want your plan to support.</p>

                        <h2>What to bring</h2>
                        <div class="prompt-list" aria-label="What to bring before using the Retirement Spending Planner">
                            <div>
                                <h3>Current household spending</h3>
                                <p>Don’t worry if you don’t know your exact monthly household spending. The Retirement Spending Planner will help you estimate it. Focus on lifestyle costs—not income taxes, retirement savings, or investment contributions.</p>
                            </div>
                            <div>
                                <h3>Retirement lifestyle goals</h3>
                                <p>Notes about travel, hobbies, family support, charitable giving, or other priorities you want your retirement plan to support.</p>
                            </div>
                            <div>
                                <h3>Other retirement income</h3>
                                <p>Any pension, annuity payment, or other regular income you expect before adding Social Security. Do not include planned withdrawals from IRAs, 401(k)s, or other investment accounts yet.</p>
                            </div>
                        </div>

                        <h2>Before you continue</h2>
                        <p>Open the Retirement Spending Planner, estimate your household spending, and save your monthly target. You will use that number in the next phase when thinking about Social Security.</p>

                        <h2>Continue to the next phase</h2>
                        <div data-spending-plan-empty>
                            <p>After saving your Retirement Spending Plan, return here to review your spending summary and continue to Phase 2: Social Security.</p>
                        </div>
                        <div class="completion-message phase-completion-panel" data-spending-plan-summary hidden>
                            <p><strong>Phase 1 is complete.</strong> Continue to Phase 2: Social Security when you are ready.</p>
                            <a class="primary-action" href="/phases/continue-to-phase-2.php">Continue to Phase 2: Social Security</a>
                        </div>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>

                    <div class="next-step-card" aria-labelledby="next-step-title">
                        <p class="eyebrow">Recommended Tool</p>
                        <h2 id="next-step-title">Your Retirement Spending Plan</h2>
                        <p>Estimate the monthly spending your retirement lifestyle will require, then save that target for the rest of your Journey.</p>
                        <a class="primary-action" href="/calculators/retirement-spending-plan/">Launch Retirement Spending Planner</a>
                    </div>
                </aside>
            </div>
        </section>
    </main>
    <script src="/assets/js/spending-goals-phase.js?v=20260729-approaching-retirement" defer></script>
</body>
</html>
