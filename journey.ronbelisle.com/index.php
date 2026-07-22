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
        <section class="hero-section" aria-labelledby="journey-title">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Guided retirement planning</p>
                    <h1 id="journey-title">Your Retirement Planning Journey</h1>
                    <div class="intro-copy">
                        <p>Retirement planning is a series of connected decisions about spending, Social Security, investments, taxes, and protecting your family.</p>
                        <p>This guided six-phase Journey helps you build your initial retirement plan one decision at a time.</p>
                        <p>When your initial plan is complete, you can return to review it and keep it current as your life changes.</p>
                    </div>
                    <a class="primary-action" href="/phases/spending-goals.php" data-journey-start-link>Begin Your Journey</a>
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
                        <p class="eyebrow">Your progress</p>
                        <h2 id="progress-title">Your Progress</h2>
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
                    <p class="eyebrow">Your planning process</p>
                    <h2 id="phases-title">Build your initial retirement plan in six guided phases.</h2>
                </div>

                <div class="phase-grid">
                    <article class="phase-card is-available" id="spending-goals" data-journey-phase="spending-goals">
                        <span class="phase-number">1</span>
                        <h3>Spending &amp; Goals</h3>
                        <p>Start by clarifying the lifestyle, expenses, and priorities your plan needs to support.</p>
                        <a href="/phases/spending-goals.php">Start this phase</a>
                    </article>

                    <article class="phase-card is-available" id="social-security" data-journey-phase="social-security">
                        <span class="phase-number">2</span>
                        <h3>Social Security</h3>
                        <p>Think through claiming age, household benefits, and how Social Security fits your income plan.</p>
                        <a href="/phases/social-security.php">Start this phase</a>
                    </article>

                    <article class="phase-card" id="build-your-plan" data-journey-phase="build-your-plan">
                        <span class="phase-number">3</span>
                        <h3>Build Your Plan</h3>
                        <p>Combine income, withdrawals, taxes, and investment assumptions into a coordinated plan.</p>
                        <span class="phase-note">Planned</span>
                    </article>

                    <article class="phase-card" id="stress-test" data-journey-phase="stress-test">
                        <span class="phase-number">4</span>
                        <h3>Stress Test</h3>
                        <p>Check whether the plan can handle market downturns, longevity, inflation, and spending changes.</p>
                        <span class="phase-note">Planned</span>
                    </article>

                    <article class="phase-card" id="tax-strategy" data-journey-phase="tax-strategy">
                        <span class="phase-number">5</span>
                        <h3>Tax Strategy</h3>
                        <p>Review Roth conversions, RMDs, taxable income timing, and tax-aware withdrawal choices.</p>
                        <span class="phase-note">Planned</span>
                    </article>

                    <article class="phase-card" id="survivor-legacy" data-journey-phase="survivor-legacy">
                        <span class="phase-number">6</span>
                        <h3>Survivor &amp; Legacy</h3>
                        <p>Consider survivor income, beneficiary planning, estate questions, and family protection.</p>
                        <span class="phase-note">Planned</span>
                    </article>
                </div>

                <div class="phase-overview-cta">
                    <a class="primary-action" href="/phases/spending-goals.php">Begin Your Journey</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
