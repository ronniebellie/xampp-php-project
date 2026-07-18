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
    <link rel="stylesheet" href="/assets/css/journey.css">
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
                        <p class="page-lede">Use this phase to get ready for the Retirement Spending Planner. The goal is to enter the calculator with clearer spending estimates, useful context, and a practical question you want answered.</p>
                    </div>

                    <article class="planning-panel">
                        <h2>Why this step matters</h2>
                        <p>Your spending target is the foundation for the rest of your retirement plan. Before you estimate Social Security timing, withdrawals, taxes, or investment risk, it helps to know what kind of retirement income you are trying to support.</p>

                        <h2>What you'll accomplish</h2>
                        <p>You will prepare a reasonable retirement spending target, separate essential expenses from flexible goals, and identify the assumptions you want the planner to test.</p>

                        <h2>What to bring</h2>
                        <div class="prompt-list" aria-label="What to bring before using the Retirement Spending Planner">
                            <div>
                                <h3>Current spending estimate</h3>
                                <p>A monthly or annual estimate of what you spend now, even if it is approximate.</p>
                            </div>
                            <div>
                                <h3>Retirement lifestyle goals</h3>
                                <p>Notes about travel, hobbies, family support, charitable giving, or other flexible priorities.</p>
                            </div>
                            <div>
                                <h3>Guaranteed income sources</h3>
                                <p>Any pension, annuity, or other reliable income you expect before adding Social Security.</p>
                            </div>
                        </div>

                        <h2>Before you continue</h2>
                        <p>Open the planner, work through the calculator, and make note of the spending target or gap that stands out. You will use that context in the next phase when thinking about Social Security.</p>

                        <h2>Continue to the next phase</h2>
                        <p>After completing the Retirement Spending Planner, return to the Journey and continue to Phase 2: Social Security.</p>
                        <a class="secondary-action" href="/#social-security">Continue to Social Security</a>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>

                    <div class="next-step-card" aria-labelledby="next-step-title">
                        <p class="eyebrow">Recommended Tool</p>
                        <h2 id="next-step-title">Retirement Spending Planner</h2>
                        <p>Estimate a retirement budget from your current spending, factor in guaranteed income, and see whether your savings look on track.</p>
                        <a class="primary-action" href="https://ronbelisle.com/retirement-spending-checkup/">Launch Retirement Spending Planner</a>
                    </div>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>
