<?php
$active_phase = 'build-your-plan';
$page_title = 'Build Your Plan | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260729-m5-p2">
</head>
<body>
    <?php include __DIR__ . '/../includes/site-header.php'; ?>

    <main>
        <section class="page-hero" aria-labelledby="phase-title">
            <div class="container phase-template-grid">
                <div class="phase-main-column">
                    <div class="phase-intro">
                        <p class="eyebrow">Phase 3</p>
                        <h1 id="phase-title">Build Your Plan</h1>
                        <p class="page-lede">Connect your spending goal, dependable income, Social Security assumption, and retirement savings into one base-case income picture.</p>
                        <p class="phase-reassurance">This phase answers whether your retirement savings can support the lifestyle you’ve planned under your current assumptions. It is not a stress test.</p>
                    </div>

                    <section class="current-record-overview" id="current-record-overview" aria-labelledby="current-record-title" data-returning-record hidden>
                        <p class="eyebrow">Your saved planning record</p>
                        <div class="record-overview-heading">
                            <h2 id="current-record-title">Your retirement income plan</h2>
                            <span class="record-status-badge" data-phase3-record-status hidden></span>
                        </div>
                        <div class="assumption-statement" data-phase3-summary>
                            <p>Save your retirement income plan to create a short summary for later phases.</p>
                        </div>
                        <button type="button" class="secondary-action journey-button" data-revise-plan hidden>Revise My Plan</button>
                    </section>

                    <article class="planning-panel">
                        <section class="phase-content-section" aria-labelledby="bridge-title">
                            <h2 id="bridge-title">What your Journey already knows</h2>
                            <p id="phase3BridgeCopy">Phase 3 uses the spending target and Social Security assumption you already saved, then asks how much you have saved for retirement.</p>

                            <div class="coach-response" id="phase1IncompleteBanner" hidden>
                                <p><strong>Phase 3 needs your retirement spending target.</strong></p>
                                <p>Return to Phase 1 to create or save a spending plan before building this income picture.</p>
                                <a class="secondary-action" href="/phases/spending-goals.php">Return to Phase 1: Spending &amp; Goals</a>
                            </div>

                            <div class="result-metrics compact-results" id="knownAmounts" hidden>
                                <div class="result-metric is-primary">
                                    <span>Spending goal</span>
                                    <strong id="knownSpending">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Social Security</span>
                                    <strong id="knownSocialSecurity">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Other dependable income</span>
                                    <strong id="knownOtherIncome">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Needed from savings</span>
                                    <strong id="knownSavingsNeed">$0</strong>
                                </div>
                            </div>
                            <p class="supporting-note" id="ssAssumptionNote" hidden>Phase 3 uses your current saved Social Security planning assumption before taxes or other deductions, unless you entered a different amount in Phase 2.</p>
                        </section>

                        <section class="phase-content-section" id="temporarySsSection" aria-labelledby="temporary-ss-title" hidden>
                            <h2 id="temporary-ss-title">Social Security amount needed</h2>
                            <p id="temporarySsExplain">Your Social Security planning amount is not complete yet. Return to Phase 2 to finish it, or enter a temporary estimate so you can preview this income plan.</p>
                            <div class="inline-choices" style="margin-bottom: 14px;">
                                <a class="secondary-action" href="/phases/social-security.php">Return to Phase 2</a>
                            </div>
                            <div class="field-group">
                                <label for="temporaryMonthlySs">Temporary monthly Social Security estimate</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="temporaryMonthlySs" name="temporaryMonthlySs" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>This estimate is used only for this Phase 3 preview. It will not replace your saved Phase 2 planning record.</small>
                            </div>
                            <button type="button" class="secondary-action journey-button" id="useTemporarySsButton">Use a temporary monthly estimate</button>
                        </section>

                        <form id="phase3RecordForm" novalidate>
                            <section class="phase-content-section" id="savingsQuestionSection" aria-labelledby="savings-title" hidden>
                                <h2 id="savings-title">How much have you saved for retirement?</h2>
                                <p>Enter one combined household total for the savings you intend to use to support retirement.</p>

                                <div class="error-summary" id="phase3ErrorSummary" role="alert" tabindex="-1" hidden>
                                    <h3>Please review the following</h3>
                                    <ul></ul>
                                </div>

                                <div class="field-group">
                                    <label for="retirementSavingsBalance">Retirement savings balance</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="retirementSavingsBalance" name="retirementSavingsBalance" type="number" min="0" step="1" inputmode="decimal" required></div>
                                    <small>Include retirement accounts and other savings you intend to use to support retirement. An approximate total is fine.</small>
                                </div>
                            </section>

                            <section class="phase-content-section" id="incomePictureSection" aria-labelledby="picture-title" hidden>
                                <div class="record-overview-heading">
                                    <h2 id="picture-title">Your retirement income picture</h2>
                                    <span class="record-status-badge is-temporary" id="temporaryEstimateBadge" hidden>Temporary Social Security estimate</span>
                                </div>

                                <div class="result-metrics compact-results">
                                    <div class="result-metric is-primary">
                                        <span>Monthly spending goal</span>
                                        <strong id="pictureSpending">$0</strong>
                                    </div>
                                    <div class="result-metric">
                                        <span>Social Security</span>
                                        <strong id="pictureSs">$0</strong>
                                    </div>
                                    <div class="result-metric">
                                        <span>Other dependable income</span>
                                        <strong id="pictureOther">$0</strong>
                                    </div>
                                    <div class="result-metric">
                                        <span>Total dependable income</span>
                                        <strong id="pictureDependable">$0</strong>
                                    </div>
                                    <div class="result-metric">
                                        <span>Monthly needed from retirement savings</span>
                                        <strong id="pictureMonthlyNeed">$0</strong>
                                    </div>
                                    <div class="result-metric">
                                        <span>Annual needed from retirement savings</span>
                                        <strong id="pictureAnnualNeed">$0</strong>
                                    </div>
                                </div>

                                <div class="coach-response assessment-card" id="assessmentCard" hidden>
                                    <p class="eyebrow">Base-case assessment</p>
                                    <h3 id="assessmentLabel"></h3>
                                    <p id="assessmentDetail"></p>
                                    <p class="withdrawal-rate-note" id="withdrawalRateNote"></p>
                                    <p class="trust-note">This is a base-case planning snapshot, not a stress test or guarantee.</p>
                                </div>

                                <div class="coach-response" id="assessmentBlockedCard" hidden>
                                    <p><strong>Assessment not ready yet.</strong></p>
                                    <p id="assessmentBlockedDetail">Enter the missing information above before Phase 3 can show a base-case assessment.</p>
                                </div>

                                <p class="decision-statement"><strong>This is the retirement income plan I want to carry forward.</strong></p>
                                <button type="submit" class="primary-action journey-button" id="savePhase3Button">Save My Retirement Income Plan</button>
                                <div class="save-confirmation" id="phase3SaveConfirmation" role="status" tabindex="-1" hidden>
                                    <strong>Your retirement income plan has been saved in this browser.</strong>
                                    <span>This is a working base-case plan you can revisit and change later.</span>
                                </div>
                            </section>
                        </form>

                        <section class="phase-content-section phase-completion" aria-labelledby="continue-title">
                            <h2 id="continue-title">What’s next</h2>
                            <p>You now have a working retirement income plan. Phase 4 tests how it may hold up if markets grow more slowly, decline early, or retirement lasts longer than expected.</p>
                            <button type="button" class="primary-action journey-button" id="completePhase3Button">Save Phase 3 Progress</button>
                            <a class="secondary-action" href="/phases/stress-test.php" id="continueToPhase4Link">Continue to Phase 4</a>
                            <a class="secondary-action" href="/">Return to My Journey</a>
                            <div class="completion-message" id="phase3CompletionMessage" role="status" tabindex="-1" hidden></div>
                            <p class="trust-note">Completing Phase 3 records your base-case income plan in this browser. It does not run a stress test.</p>
                        </section>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>

                    <div class="next-step-card" aria-labelledby="next-step-title">
                        <p class="eyebrow">This phase</p>
                        <h2 id="next-step-title">Build a base-case income plan</h2>
                        <p>See how spending, Social Security, other dependable income, and retirement savings fit together before you stress-test the plan.</p>
                        <p class="action-note">Optional deeper exploration later: the Retirement Plan Builder on ronbelisle.com. It is not required to complete Phase 3.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <script src="/assets/js/build-your-plan-phase.js?v=20260729-claiming-age-only" defer></script>
</body>
</html>
