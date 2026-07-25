<?php
$active_phase = 'tax-strategy';
$page_title = 'Tax Strategy | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260725-phase5">
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
                        <p class="eyebrow">Phase 5</p>
                        <h1 id="phase-title">Tax Strategy</h1>
                        <p class="page-lede">How do taxes affect the retirement plan you have built, and what should you consider next?</p>
                        <p class="phase-reassurance">This is an educational tax-planning review. It is not a tax return, not tax advice, and not a finished tax strategy.</p>
                    </div>

                    <div class="coach-response" id="phase3IncompleteBanner" hidden>
                        <p><strong>Phase 5 uses the retirement income plan created in Phase 3.</strong></p>
                        <p>Return to Phase 3 to save a complete base-case plan before reviewing your tax picture. Values will not be invented here.</p>
                        <a class="secondary-action" href="/phases/build-your-plan.php">Return to Phase 3: Build Your Plan</a>
                    </div>

                    <div class="coach-response" id="phase3ChangedBanner" hidden>
                        <p><strong>Your Phase 3 plan has changed since this tax review.</strong></p>
                        <p>Review your updated tax picture again before relying on these results. Your previous review is kept until you save a new one.</p>
                    </div>

                    <article class="planning-panel" id="phase5MainPanel">
                        <section class="phase-content-section" aria-labelledby="recap-title" id="phase3RecapSection" hidden>
                            <h2 id="recap-title">Your retirement income plan</h2>
                            <p>This review uses the Phase 3 plan you already saved. You do not need to rebuild it.</p>
                            <p class="supporting-note" id="temporarySsNote" hidden>This review uses the temporary Social Security estimate entered in Phase 3.</p>
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

                        <section class="phase-content-section" aria-labelledby="phase4-context-title" id="phase4ContextSection" hidden>
                            <h2 id="phase4-context-title">Your Phase 4 resilience review</h2>
                            <p id="phase4ResilienceLine"></p>
                            <p id="phase4PressureLine" class="supporting-note"></p>
                            <p id="phase4AdjustmentLine" class="supporting-note" hidden></p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="tax-map-title" id="taxCharacterSection" hidden>
                            <h2 id="tax-map-title">How this plan may interact with taxes</h2>
                            <ul class="tax-character-list">
                                <li>
                                    <strong>Social Security</strong>
                                    <span>Some Social Security benefits may be included in taxable income depending on other income and withdrawals.</span>
                                </li>
                                <li>
                                    <strong>Traditional retirement accounts</strong>
                                    <span>Withdrawals from Traditional IRAs and 401(k)s generally increase taxable income.</span>
                                </li>
                                <li>
                                    <strong>Roth accounts</strong>
                                    <span>Qualified Roth withdrawals are generally treated differently and usually do not increase taxable income.</span>
                                </li>
                                <li>
                                    <strong>Other dependable income</strong>
                                    <span>Pensions, annuities, rental income, and similar income may also affect taxable income.</span>
                                </li>
                                <li>
                                    <strong>Taxable savings or brokerage accounts</strong>
                                    <span>Interest, dividends, and investment gains may be taxed differently from retirement-account withdrawals. Phase 5 does not calculate those details.</span>
                                </li>
                            </ul>
                            <p class="supporting-note">Your savings mix is only one part of your household tax picture.</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="questions-title" id="questionsSection" hidden>
                            <h2 id="questions-title">Two details for this review</h2>
                            <p id="questionValidation" class="inline-error" role="alert" hidden></p>

                            <fieldset class="journey-fieldset" id="savingsMixFieldset">
                                <legend>How are most of your retirement savings held?</legend>
                                <label class="choice-row" for="savingsMix_mostly_tax_deferred">
                                    <input type="radio" name="savingsMix" id="savingsMix_mostly_tax_deferred" value="mostly_tax_deferred">
                                    <span>Mostly tax-deferred, such as Traditional IRAs or 401(k)s</span>
                                </label>
                                <label class="choice-row" for="savingsMix_mostly_roth">
                                    <input type="radio" name="savingsMix" id="savingsMix_mostly_roth" value="mostly_roth">
                                    <span>Mostly Roth</span>
                                </label>
                                <label class="choice-row" for="savingsMix_mixed">
                                    <input type="radio" name="savingsMix" id="savingsMix_mixed" value="mixed">
                                    <span>A mixture of account types</span>
                                </label>
                                <label class="choice-row" for="savingsMix_not_sure">
                                    <input type="radio" name="savingsMix" id="savingsMix_not_sure" value="not_sure">
                                    <span>Not sure</span>
                                </label>
                            </fieldset>

                            <fieldset class="journey-fieldset" id="rmdTimingFieldset">
                                <legend>Where are you with required minimum distributions?</legend>
                                <label class="choice-row" for="rmdTiming_already">
                                    <input type="radio" name="rmdTiming" id="rmdTiming_already" value="already">
                                    <span>I am already taking them</span>
                                </label>
                                <label class="choice-row" for="rmdTiming_within_about_5_years">
                                    <input type="radio" name="rmdTiming" id="rmdTiming_within_about_5_years" value="within_about_5_years">
                                    <span>I expect them within about the next 5 years</span>
                                </label>
                                <label class="choice-row" for="rmdTiming_later">
                                    <input type="radio" name="rmdTiming" id="rmdTiming_later" value="later">
                                    <span>I expect them later</span>
                                </label>
                                <label class="choice-row" for="rmdTiming_not_sure">
                                    <input type="radio" name="rmdTiming" id="rmdTiming_not_sure" value="not_sure">
                                    <span>Not sure</span>
                                </label>
                            </fieldset>

                            <div class="phase-actions">
                                <button type="button" class="primary-action journey-button" id="reviewTaxPictureBtn">Review My Tax Picture</button>
                            </div>
                        </section>

                        <section class="phase-content-section tax-results" aria-labelledby="tax-results-title" id="resultsSection" hidden>
                            <h2 id="tax-results-title" tabindex="-1">Your tax picture</h2>
                            <p class="supporting-note" id="resultsStaleNote" hidden>Your answers changed after this review. Run Review My Tax Picture again before saving.</p>

                            <div class="assumption-statement">
                                <p class="eyebrow">Main issue</p>
                                <p><strong id="mainIssueStatement" aria-live="polite">—</strong></p>
                                <p id="whatThisMeans"></p>
                            </div>

                            <div class="coach-response" id="taxDragBlock">
                                <p class="eyebrow">Guidance</p>
                                <p id="taxDragGuidance"></p>
                            </div>

                            <p id="rmdNote" class="supporting-note"></p>
                            <p id="rothSignal" class="supporting-note" hidden></p>

                            <section class="phase-content-section" aria-labelledby="priority-title" id="prioritySection">
                                <h3 id="priority-title">Choose one priority to carry forward</h3>
                                <p>Select one direction. This does not change your Phase 1–4 records, and it is not presented as the best option.</p>
                                <fieldset class="stress-adjustment-fieldset" id="priorityFieldset">
                                    <legend class="visually-hidden">Tax-planning priority</legend>
                                    <div id="priorityChoices"></div>
                                </fieldset>
                            </section>

                            <section class="phase-content-section" aria-labelledby="save-title">
                                <h3 id="save-title">Your Phase 5 decision</h3>
                                <p id="decisionStatement"><strong>This is the tax-planning priority I want to carry forward before I rely on my withdrawal plan.</strong></p>
                                <p class="supporting-note">I’ve reviewed how taxes may affect my Phase 3 plan. I’m carrying forward one priority to revisit, not a finished tax strategy.</p>
                                <div class="phase-actions">
                                    <button type="button" class="primary-action journey-button" id="savePriorityBtn" disabled>Save My Tax-Planning Priority</button>
                                </div>
                                <p class="supporting-note" id="saveConfirm" role="status" tabindex="-1" hidden>
                                    <strong>Your tax-planning priority is saved in this browser.</strong>
                                    Phase 6 is not available yet.
                                </p>
                            </section>

                            <section class="phase-content-section" aria-labelledby="next-title" id="phase6Handoff" hidden>
                                <h3 id="next-title">What’s next</h3>
                                <p>You have identified the tax-planning issue most important to your current retirement income plan. Phase 6 examines how that plan may change if one spouse dies and how income, beneficiaries, and legacy decisions carry forward.</p>
                                <p class="supporting-note">Phase 6 is not available yet.</p>
                                <a class="secondary-action" href="/">Return to My Journey</a>
                            </section>
                        </section>

                        <section class="phase-content-section" id="savedReviewSection" hidden aria-labelledby="saved-review-title">
                            <h2 id="saved-review-title">Your saved tax-planning priority</h2>
                            <div class="assumption-statement" id="savedReviewSummary"></div>
                            <div class="phase-actions">
                                <button type="button" class="secondary-action journey-button" id="revisitBtn">Review My Tax Picture Again</button>
                                <a class="secondary-action" href="/">Return to My Journey</a>
                            </div>
                        </section>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>
                    <div class="next-step-card" aria-labelledby="phase5-side-title">
                        <p class="eyebrow">This phase</p>
                        <h2 id="phase5-side-title">Identify one tax priority</h2>
                        <p>See how taxes may affect the retirement income plan you already built, then carry forward one issue to revisit.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <script src="/assets/js/phase5-tax-engine.js?v=20260725-phase5" defer></script>
    <script src="/assets/js/phase5-priorities.js?v=20260725-phase5" defer></script>
    <script src="/assets/js/tax-strategy-phase.js?v=20260725-phase5" defer></script>
</body>
</html>
