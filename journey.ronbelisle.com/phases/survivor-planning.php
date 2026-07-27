<?php
$active_phase = 'survivor-planning';
$page_title = 'Survivor Planning | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260727-premium-transition">
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
                        <p class="eyebrow">Phase 6</p>
                        <h1 id="phase-title">Survivor Planning</h1>
                        <p class="page-lede">If one of us dies, what may change in our retirement plan—and what should we review next?</p>
                        <p class="phase-reassurance">This is survivor planning for your income plan—not a finished estate plan. It is educational and is not legal or financial advice.</p>
                    </div>

                    <div class="coach-response" id="phase3IncompleteBanner" hidden>
                        <p><strong>Phase 6 uses the retirement income plan created in Phase 3.</strong></p>
                        <p>Return to Phase 3 to save a complete base-case plan before reviewing your survivor picture. Values will not be invented here.</p>
                        <a class="secondary-action" href="/phases/build-your-plan.php">Return to Phase 3: Build Your Plan</a>
                    </div>

                    <div class="coach-response" id="phase3ChangedBanner" hidden>
                        <p><strong>Your Phase 3 plan has changed since this survivor-planning review.</strong></p>
                        <p>Review your updated survivor picture again before relying on these results. Your previous review is kept until you save a new one.</p>
                    </div>

                    <article class="planning-panel" id="phase6MainPanel">
                        <section class="phase-content-section" aria-labelledby="recap-title" id="phase3RecapSection" hidden>
                            <h2 id="recap-title">Your retirement income plan</h2>
                            <p>This review uses the Phase 3 plan you already saved. You do not need to rebuild it.</p>
                            <p class="supporting-note" id="temporarySsNote" hidden>This review uses the temporary Social Security estimate entered in Phase 3.</p>
                            <div class="result-metrics compact-results">
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

                        <section class="phase-content-section" aria-labelledby="context-title" id="priorPhaseContextSection" hidden>
                            <h2 id="context-title">Earlier Journey context</h2>
                            <p id="phase4ContextLine" class="supporting-note" hidden></p>
                            <p id="phase5ContextLine" class="supporting-note" hidden></p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="teach-title" id="teachingSection" hidden>
                            <h2 id="teach-title">What may change for a survivor</h2>
                            <ul class="tax-character-list">
                                <li>
                                    <strong>Social Security</strong>
                                    <span>One Social Security benefit may end or change.</span>
                                </li>
                                <li>
                                    <strong>Other dependable income</strong>
                                    <span>Some pensions, annuities, or other dependable income may continue; some may not.</span>
                                </li>
                                <li>
                                    <strong>Spending</strong>
                                    <span>One-person living costs often do not fall by half.</span>
                                </li>
                                <li>
                                    <strong>Retirement withdrawals</strong>
                                    <span>Retirement-savings withdrawals may need another review.</span>
                                </li>
                                <li>
                                    <strong>Who receives accounts and assets</strong>
                                    <span>Reviewing who would receive accounts and assets supports continuity, but this Journey does not verify beneficiary designations.</span>
                                </li>
                            </ul>
                            <p class="supporting-note">This is survivor planning for your income plan—not a finished estate plan.</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="questions-title" id="questionsSection" hidden>
                            <h2 id="questions-title">Two details for this review</h2>
                            <p id="questionValidation" class="inline-error" role="alert" hidden></p>

                            <fieldset class="journey-fieldset" id="assetRecipientFieldset">
                                <legend>Have you reviewed who would receive your retirement accounts and other financial assets?</legend>
                                <label class="choice-row" for="assetRecipient_recently">
                                    <input type="radio" name="assetRecipientReview" id="assetRecipient_recently" value="recently">
                                    <span>Yes, recently</span>
                                </label>
                                <label class="choice-row" for="assetRecipient_may_need_review">
                                    <input type="radio" name="assetRecipientReview" id="assetRecipient_may_need_review" value="may_need_review">
                                    <span>Yes, but the information may need another review</span>
                                </label>
                                <label class="choice-row" for="assetRecipient_not_yet">
                                    <input type="radio" name="assetRecipientReview" id="assetRecipient_not_yet" value="not_yet">
                                    <span>Not yet</span>
                                </label>
                                <label class="choice-row" for="assetRecipient_not_sure">
                                    <input type="radio" name="assetRecipientReview" id="assetRecipient_not_sure" value="not_sure">
                                    <span>Not sure</span>
                                </label>
                            </fieldset>

                            <fieldset class="journey-fieldset" id="survivorPreparednessFieldset">
                                <legend>If one of you died, how prepared do you feel to review the survivor’s income plan?</legend>
                                <label class="choice-row" for="survivorPreparedness_thought_through">
                                    <input type="radio" name="survivorIncomePreparedness" id="survivorPreparedness_thought_through" value="thought_through">
                                    <span>We have already thought this through</span>
                                </label>
                                <label class="choice-row" for="survivorPreparedness_discussed_review_again">
                                    <input type="radio" name="survivorIncomePreparedness" id="survivorPreparedness_discussed_review_again" value="discussed_review_again">
                                    <span>We have discussed it, but should review it again</span>
                                </label>
                                <label class="choice-row" for="survivorPreparedness_not_reviewed">
                                    <input type="radio" name="survivorIncomePreparedness" id="survivorPreparedness_not_reviewed" value="not_reviewed">
                                    <span>We have not reviewed it yet</span>
                                </label>
                                <label class="choice-row" for="survivorPreparedness_not_sure">
                                    <input type="radio" name="survivorIncomePreparedness" id="survivorPreparedness_not_sure" value="not_sure">
                                    <span>Not sure</span>
                                </label>
                            </fieldset>

                            <div class="phase-actions">
                                <button type="button" class="primary-action journey-button" id="reviewSurvivorPictureBtn">Review Our Survivor Picture</button>
                            </div>
                        </section>

                        <section class="phase-content-section" aria-labelledby="survivor-results-title" id="resultsSection" hidden>
                            <h2 id="survivor-results-title" tabindex="-1">Your survivor-planning picture</h2>
                            <p class="supporting-note" id="resultsStaleNote" hidden>Your answers changed after this review. Run Review Our Survivor Picture again before saving.</p>

                            <div class="assumption-statement" id="mainIssueBlock">
                                <p class="eyebrow" id="mainIssueEyebrow">Main survivor-planning priority</p>
                                <div id="mainIssueList"></div>
                                <p id="mainIssueLive" class="visually-hidden" aria-live="polite"></p>
                            </div>

                            <p id="guidanceText" class="supporting-note" hidden></p>

                            <section class="phase-content-section" aria-labelledby="priority-title">
                                <h3 id="priority-title">Choose one priority to carry forward</h3>
                                <p>Select one direction. This does not change your Phase 1–5 records, and it is not presented as the best option.</p>
                                <fieldset class="stress-adjustment-fieldset">
                                    <legend class="visually-hidden">Survivor-planning priority</legend>
                                    <div id="priorityChoices"></div>
                                </fieldset>
                            </section>

                            <section class="phase-content-section" aria-labelledby="save-title">
                                <h3 id="save-title">Your Phase 6 decision</h3>
                                <p><strong>This is the survivor-planning priority I want to carry forward for our household plan.</strong></p>
                                <p class="supporting-note">I’ve reviewed how our retirement income plan may change if one of us dies. I’m carrying forward one priority to revisit—not a finished estate plan.</p>
                                <div class="phase-actions">
                                    <button type="button" class="primary-action journey-button" id="savePriorityBtn" disabled>Save My Survivor-Planning Priority</button>
                                </div>
                                <p class="supporting-note" id="saveConfirm" role="status" tabindex="-1" hidden>
                                    <strong>Your survivor-planning priority is saved in this browser.</strong>
                                </p>
                            </section>
                        </section>

                        <section class="phase-content-section" id="journeyCompleteSection" hidden aria-labelledby="journey-complete-title">
                            <h2 id="journey-complete-title">Your initial Retirement Planning Journey is complete.</h2>
                            <p>You now have an initial retirement plan. The next step is to keep it current as your life changes.</p>
                            <ul class="journey-complete-recap" id="journeyCompleteRecap">
                                <li>Retirement spending goal</li>
                                <li>Social Security assumption</li>
                                <li>Retirement income plan</li>
                                <li>Resilience review</li>
                                <li>Tax-planning priority</li>
                                <li>Survivor-planning priority</li>
                            </ul>

                            <div class="premium-continuity-panel" id="premiumContinuityBlock">
                                <h3 id="premium-continuity-title">Keep your plan current with Journey Premium</h3>
                                <p>Your free Journey helped you build an initial retirement plan. Journey Premium gives you an ongoing planning workspace where you can save and update assumptions, revisit decisions, compare alternatives, and keep your plan current as your life changes.</p>
                                <p class="supporting-note">The plan you just built becomes the starting point for your ongoing workspace—you do not need to begin again.</p>

                                <div class="premium-continuity-actions" id="premiumContinuityActions">
                                    <a class="primary-action" id="premiumPrimaryCta" href="https://ronbelisle.com/premium/journey.php">Start Your 30-Day Free Trial</a>
                                    <p class="action-note" id="premiumTrialReassurance">No charge today. Cancel before the trial ends if you decide not to continue.</p>
                                    <a class="secondary-action" href="/">Return to My Journey</a>
                                </div>
                            </div>

                            <div class="journey-review-links" id="journeyReviewLinks">
                                <h3 class="journey-review-heading">Review your plan</h3>
                                <div class="phase-actions journey-review-actions">
                                    <a class="text-action" href="/phases/build-your-plan.php">Review Phase 3</a>
                                    <a class="text-action" href="/phases/stress-test.php">Review Phase 4</a>
                                    <a class="text-action" href="/phases/tax-strategy.php">Review Phase 5</a>
                                </div>
                            </div>
                        </section>

                        <section class="phase-content-section" id="savedReviewSection" hidden aria-labelledby="saved-review-title">
                            <h2 id="saved-review-title">Your saved survivor-planning priority</h2>
                            <div class="assumption-statement" id="savedReviewSummary"></div>
                            <div class="phase-actions">
                                <button type="button" class="secondary-action journey-button" id="revisitBtn">Review Our Survivor Picture Again</button>
                                <a class="secondary-action" href="/">Return to My Journey</a>
                            </div>
                        </section>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>
                    <div class="next-step-card" aria-labelledby="phase6-side-title">
                        <p class="eyebrow">This phase</p>
                        <h2 id="phase6-side-title">Close the Journey with continuity</h2>
                        <p>See how the household income plan may change if one spouse dies, then carry forward one survivor-planning priority.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <script src="/assets/js/phase6-survivor-engine.js?v=20260725-phase6-open" defer></script>
    <script src="/assets/js/phase6-priorities.js?v=20260725-phase6-open" defer></script>
    <script src="/assets/js/survivor-planning-phase.js?v=20260727-premium-transition" defer></script>
</body>
</html>
