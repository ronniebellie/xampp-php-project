<?php
$active_phase = 'stress-test';
$page_title = 'Stress Test | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260801-feedback">
</head>
<body>
    <?php include __DIR__ . '/../includes/site-header.php'; ?>

    <main>
        <section class="page-hero" aria-labelledby="phase-title">
            <div class="container phase-template-grid">
                <div class="phase-main-column">
                    <div class="phase-intro">
                        <p class="eyebrow">Phase 4</p>
                        <h1 id="phase-title">Stress Test</h1>
                        <p class="page-lede">You’ve built a base-case retirement income plan. Now you’ll see how sensitive it may be to less favorable conditions.</p>
                        <p class="phase-reassurance">These tests are educational. They do not predict markets or guarantee outcomes.</p>
                    </div>

                    <div class="coach-response" id="phase3IncompleteBanner" hidden>
                        <p><strong>The Stress Test needs your completed Phase 3 retirement income plan.</strong></p>
                        <p>Return to Phase 3 to save a complete base-case plan before running these educational tests. Values will not be invented here.</p>
                        <a class="secondary-action" href="/phases/build-your-plan.php">Return to Phase 3: Build Your Plan</a>
                    </div>

                    <div class="coach-response" id="phase3ChangedBanner" hidden>
                        <p><strong>Your Phase 3 plan has changed since this Stress Test.</strong></p>
                        <p>Test your updated plan again to refresh these results. Your previous review is kept until you save a new one.</p>
                    </div>

                    <article class="planning-panel" id="phase4MainPanel">
                        <section class="phase-content-section" aria-labelledby="recap-title" id="phase3RecapSection" hidden>
                            <h2 id="recap-title">Your Phase 3 plan</h2>
                            <p>This Stress Test uses the retirement income plan you already saved. You do not need to re-enter these amounts.</p>
                            <p class="supporting-note" id="temporarySsNote" hidden>This Stress Test uses the temporary Social Security estimate entered in Phase 3.</p>
                            <div class="result-metrics compact-results" id="phase3RecapMetrics">
                                <div class="result-metric is-primary">
                                    <span>Spending goal</span>
                                    <strong id="recapSpending">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Social Security</span>
                                    <strong id="recapSs">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Other dependable income</span>
                                    <strong id="recapOther">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Needed from savings (monthly)</span>
                                    <strong id="recapNeedMonthly">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Needed from savings (annual)</span>
                                    <strong id="recapNeedAnnual">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Retirement savings</span>
                                    <strong id="recapBalance">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Initial withdrawal rate</span>
                                    <strong id="recapRate">—</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Base-case assessment</span>
                                    <strong id="recapAssessment">—</strong>
                                </div>
                            </div>
                        </section>

                        <section class="phase-content-section" aria-labelledby="tests-title" id="testsSection" hidden>
                            <h2 id="tests-title">What we’ll test</h2>
                            <ol class="stress-test-list">
                                <li>
                                    <strong>Weaker long-term growth</strong>
                                    <span>What if your retirement savings grow more slowly than the base case assumes?</span>
                                </li>
                                <li>
                                    <strong>Early market decline</strong>
                                    <span>What if markets decline early in retirement while withdrawals continue?</span>
                                </li>
                                <li>
                                    <strong>Longer retirement</strong>
                                    <span>What if your retirement savings need to support five additional years?</span>
                                </li>
                            </ol>
                            <div class="phase-actions">
                                <button type="button" class="primary-action journey-button" id="testMyPlanBtn">Test My Plan</button>
                            </div>
                        </section>

                        <section class="phase-content-section stress-results" aria-labelledby="results-title" id="resultsSection" hidden>
                            <h2 id="results-title" tabindex="-1">Your resilience picture</h2>
                            <p class="phase-reassurance" id="resultsDisclaimer">These tests are educational. They do not predict markets or guarantee outcomes.</p>
                            <div class="coach-response assessment-card" id="overallCard">
                                <p class="eyebrow">Overall</p>
                                <p><strong id="overallLabel">—</strong></p>
                                <p id="pressureSentence" aria-live="polite"></p>
                            </div>

                            <div class="stress-scenario-grid" id="scenarioCards"></div>

                            <section class="phase-content-section" id="adjustmentSection" hidden aria-labelledby="adjustment-title">
                                <h3 id="adjustment-title">If you want to improve resilience</h3>
                                <p>Choose at most one next direction. This does not change your Phase 1–3 records.</p>
                                <fieldset class="stress-adjustment-fieldset">
                                    <legend class="visually-hidden">Possible next adjustment</legend>
                                    <div id="adjustmentChoices"></div>
                                </fieldset>
                            </section>

                            <section class="phase-content-section" aria-labelledby="save-title">
                                <h3 id="save-title">Your Phase 4 decision</h3>
                                <p id="decisionStatement">I’ve reviewed how sensitive my Phase 3 plan is, and I’m keeping this resilience review for the rest of my Journey.</p>
                                <div class="phase-actions">
                                    <button type="button" class="primary-action journey-button" id="saveReviewBtn">Save My Resilience Review</button>
                                </div>
                                <p class="supporting-note" id="saveConfirm" role="status" tabindex="-1" hidden><strong>Phase 4 is complete.</strong> Your resilience review is saved in this browser.</p>
                            </section>

                            <section class="phase-content-section" aria-labelledby="next-title" id="phase5Handoff" hidden>
                                <h3 id="next-title">What’s next</h3>
                                <p>You’ve reviewed how sensitive your plan may be to markets and longevity. Phase 5 examines how taxes may affect the same retirement income and withdrawal plan.</p>
                                <div class="phase-actions">
                                    <a class="primary-action" href="/phases/tax-strategy.php">Continue to Phase 5</a>
                                    <a class="secondary-action" href="/">Return to My Journey</a>
                                </div>
                            </section>
                        </section>

                        <section class="phase-content-section" id="savedReviewSection" hidden aria-labelledby="saved-review-title">
                            <h2 id="saved-review-title">Your saved resilience review</h2>
                            <div class="assumption-statement" id="savedReviewSummary"></div>
                            <div class="phase-actions">
                                <button type="button" class="secondary-action journey-button" id="retestBtn">Test My Plan Again</button>
                                <a class="primary-action" href="/phases/tax-strategy.php">Continue to Phase 5</a>
                                <a class="secondary-action" href="/">Return to My Journey</a>
                            </div>
                        </section>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>
                    <div class="next-step-card" aria-labelledby="phase4-side-title">
                        <p class="eyebrow">This phase</p>
                        <h2 id="phase4-side-title">Test plan resilience</h2>
                        <p>See how the base-case income plan responds to weaker growth, an early market decline, and a longer retirement.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <script src="/assets/js/phase4-config.js?v=20260725-phase4-open" defer></script>
    <script src="/assets/js/phase4-stress-engine.js?v=20260725-phase4-open" defer></script>
    <script src="/assets/js/phase4-adjustments.js?v=20260725-phase4-open" defer></script>
    <script src="/assets/js/stress-test-phase.js?v=20260801-logged-in-polish" defer></script>
    <?php include __DIR__ . '/../includes/site-footer.php'; ?>
</body>
</html>
