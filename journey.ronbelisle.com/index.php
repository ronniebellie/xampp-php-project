<?php
$active_phase = '';
$page_title = 'Your Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260801-logged-in-polish">
</head>
<body>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="hero-section" aria-labelledby="journey-title">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Guided retirement planning</p>
                    <h1 id="journey-title">Your Retirement Planning Journey</h1>
                    <div class="intro-copy" data-journey-home-intro>
                        <p data-journey-home-intro-lead>Retirement planning is a series of connected decisions about spending, Social Security, investments, taxes, and protecting your family.</p>
                        <p data-journey-home-intro-body>This <strong>six-phase Journey</strong> helps you build your initial retirement plan one decision at a time. It is designed primarily for people approaching retirement who are preparing those decisions—spending, Social Security, retirement income, taxes, and protecting their household.</p>
                    </div>
                    <p class="audience-aside">Already retired? Many of the individual planning tools may still be useful, but this six-phase Journey is currently built around preparing for retirement.</p>
                    <a class="primary-action" href="/phases/spending-goals.php" data-journey-home-cta>Begin Your Journey</a>
                </div>

                <aside class="phase-overview" aria-label="Journey phases overview">
                    <h2>Six phases to build your plan</h2>
                    <?php include __DIR__ . '/includes/progress-nav.php'; ?>
                </aside>
            </div>
        </section>

        <section class="progress-section" aria-labelledby="progress-title">
            <div class="container">
                <div class="journey-progress-summary" data-journey-progress-summary>
                    <div class="progress-card-header">
                        <p class="eyebrow" data-journey-progress-eyebrow>Your progress</p>
                        <h2 id="progress-title" data-journey-progress-heading>Your Progress</h2>
                    </div>

                    <div class="progress-card-status">
                        <p class="progress-count" data-journey-completed-count>0 of 6 phases completed</p>
                        <p class="progress-context" data-journey-progress-context>You’re building your retirement plan one decision at a time.</p>
                        <div class="progress-bar" role="progressbar" aria-label="Journey completion" aria-valuemin="0" aria-valuemax="6" aria-valuenow="0" data-journey-progress-bar>
                            <span data-journey-progress-fill></span>
                        </div>
                        <p class="progress-next">Next step: <span data-journey-recommended-phase>Spending &amp; Goals</span></p>
                    </div>

                    <div class="progress-card-completed">
                        <h3>Completed</h3>
                        <ul data-journey-completed-list>
                            <li>No phases completed yet.</li>
                        </ul>
                    </div>

                    <div class="progress-card-records" data-journey-record-summary hidden>
                        <h3>Planning records</h3>
                        <ul data-journey-record-list></ul>
                    </div>

                    <div class="progress-card-actions" data-journey-progress-actions hidden>
                        <a class="primary-action" href="/phases/spending-goals.php" data-journey-recommended-link>Continue Your Journey</a>
                        <button class="reset-action" type="button" data-journey-reset hidden>Clear Journey data</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="phase-section" aria-labelledby="phases-title">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow" data-journey-phases-eyebrow>Your planning process</p>
                    <h2 id="phases-title" data-journey-phases-heading>Build your initial retirement plan in six guided phases.</h2>
                </div>

                <div class="phase-grid">
                    <article class="phase-card is-available" id="spending-goals" data-journey-phase="spending-goals">
                        <span class="phase-number">1</span>
                        <h3>Spending &amp; Goals</h3>
                        <p>Start by clarifying the lifestyle, expenses, and priorities your plan needs to support.</p>
                        <span class="phase-status" data-journey-phase-status>Next step</span>
                    </article>

                    <article class="phase-card is-available" id="social-security" data-journey-phase="social-security">
                        <span class="phase-number">2</span>
                        <h3>Social Security</h3>
                        <p>Think through claiming age, household benefits, and how Social Security fits your income plan.</p>
                        <span class="phase-status" data-journey-phase-status hidden></span>
                    </article>

                    <article class="phase-card is-available" id="build-your-plan" data-journey-phase="build-your-plan">
                        <span class="phase-number">3</span>
                        <h3>Build Your Plan</h3>
                        <p>See whether your retirement savings can support the lifestyle you’ve planned under your current assumptions.</p>
                        <span class="phase-status" data-journey-phase-status hidden></span>
                    </article>

                    <article class="phase-card is-available" id="stress-test" data-journey-phase="stress-test">
                        <span class="phase-number">4</span>
                        <h3>Stress Test</h3>
                        <p>See how sensitive your base-case plan may be to weaker growth, an early market decline, and a longer retirement.</p>
                        <span class="phase-status" data-journey-phase-status hidden></span>
                    </article>

                    <article class="phase-card is-available" id="tax-strategy" data-journey-phase="tax-strategy">
                        <span class="phase-number">5</span>
                        <h3>Tax Strategy</h3>
                        <p>See how taxes may affect the retirement income plan you already built, then identify one issue to revisit.</p>
                        <span class="phase-status" data-journey-phase-status hidden></span>
                    </article>

                    <article class="phase-card is-available" id="survivor-planning" data-journey-phase="survivor-planning">
                        <span class="phase-number">6</span>
                        <h3>Survivor Planning</h3>
                        <p>See how the household income plan may change if one spouse dies, then identify one survivor-planning priority.</p>
                        <span class="phase-status" data-journey-phase-status hidden></span>
                    </article>
                </div>

                <div class="phase-overview-cta">
                    <a class="primary-action" href="/phases/spending-goals.php" data-journey-home-cta>Begin Your Journey</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
