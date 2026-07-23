<?php
$active_phase = 'social-security';
$page_title = 'Social Security | Retirement Planning Journey';
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
                        <p class="eyebrow">Phase 2</p>
                        <h1 id="phase-title">Social Security</h1>
                        <p class="page-lede">You have estimated what retirement may cost. Now you’ll compare Social Security claiming ages and choose an age to test in your retirement plan.</p>
                        <p class="phase-reassurance">This phase will help you use the Social Security Claiming Analyzer, understand the results, and decide what to verify before you rely on them. You can revisit your choice as your circumstances or priorities change.</p>
                        <div class="phase-status-note" role="status">
                            <p><strong>Your Phase 1 spending plan stays in this browser.</strong> The full guided Social Security planner is still being built. This page is the current Phase 2 workspace: coaching, claiming comparison guidance, and a place to record the assumption you want to test next.</p>
                        </div>
                        <p class="phase-time">Allow about 15 minutes, including time in the Claiming Analyzer.</p>
                    </div>

                    <section class="current-record-overview" id="current-record-overview" aria-labelledby="current-record-title" data-returning-record hidden>
                        <p class="eyebrow">Your saved planning record</p>
                        <div class="record-overview-heading">
                            <h2 id="current-record-title">Your current Social Security position</h2>
                            <span class="record-status-badge" data-phase2-record-status></span>
                        </div>
                        <p>Welcome back. Review what your retirement plan currently uses, update anything that changed, or return to the guidance and analyzer when you need them.</p>
                        <div class="assumption-statement" data-phase2-summary></div>
                        <div class="review-guidance">
                            <h3>When to review this again</h3>
                            <p>Review this record during your annual plan review, or sooner if your retirement date, claiming age, Social Security statement, earnings record, spouse’s plan, or benefit status changes.</p>
                        </div>
                        <div class="record-overview-actions">
                            <a class="primary-action" href="#record-title">Update My Record</a>
                            <a class="secondary-action" href="#question-title">Review Phase 2 Guidance</a>
                            <a class="secondary-action" href="https://ronbelisle.com/social-security-claiming-analyzer/" target="_blank" rel="noopener">Open Claiming Analyzer</a>
                        </div>
                    </section>

                    <article class="planning-panel phase-2-panel">
                        <section class="phase-content-section" aria-labelledby="question-title">
                            <p class="eyebrow">The Question</p>
                            <h2 id="question-title">When should I claim Social Security, and what would that choice mean for my dependable monthly income?</h2>
                            <p>There isn’t one “correct” claiming age. The goal is to find the age that fits your retirement plan best.</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="why-title">
                            <h2 id="why-title">Why this step matters</h2>
                            <p>Social Security can provide income for the rest of your life. The age at which you claim affects when that income begins and how much you receive each month.</p>
                            <p>Claiming earlier usually means smaller monthly payments that begin sooner. Waiting usually means larger monthly payments that begin later, but you must have another way to support your spending while you wait.</p>
                            <p>The useful question is not simply, “Which age produces the largest total?” It is, “Which tradeoff fits my retirement plan best?”</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="accomplish-title">
                            <h2 id="accomplish-title">What you’ll accomplish</h2>
                            <ul class="coach-list">
                                <li>Compare a few realistic claiming ages.</li>
                                <li>See how claiming age changes your estimated monthly benefit.</li>
                                <li>Understand what the break-even age and lifetime totals mean—and what they don’t.</li>
                                <li>Record the claiming age you want to test in your retirement plan.</li>
                                <li>Note the main tradeoff and anything you still need to verify.</li>
                                <li>Carry your Social Security choice into Phase 3.</li>
                            </ul>
                            <p class="supporting-note">This is a starting point. You may revise it when your work, health, household, or retirement plan changes.</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="bring-title">
                            <h2 id="bring-title">What to bring</h2>
                            <p>Gather the following before opening the Claiming Analyzer. You can explore with estimates, but verify official information before you act.</p>
                            <div class="prompt-list">
                                <div>
                                    <h3>Your Social Security statement</h3>
                                    <p>Find your estimated monthly retirement benefit at Full Retirement Age. The analyzer calls this your “Monthly Benefit at Full Retirement Age.”</p>
                                </div>
                                <div>
                                    <h3>Your birth date</h3>
                                    <p>The analyzer uses your birth year to find your Full Retirement Age.</p>
                                </div>
                                <div>
                                    <h3>Your retirement spending target</h3>
                                    <p>Keep the monthly or annual spending estimate from Phase 1 nearby. The Claiming Analyzer will not ask for it, but it will help you judge how much of your spending the benefit could cover.</p>
                                </div>
                                <div>
                                    <h3>When you expect to retire</h3>
                                    <p>Think about when you expect to stop working. Your retirement date and Social Security claiming date do not have to be the same.</p>
                                </div>
                                <div>
                                    <h3>Your spouse’s information, if applicable</h3>
                                    <p>If you are married, bring both Social Security statements. The analyzer compares one person at a time, so survivor questions may require another calculator.</p>
                                </div>
                            </div>

                            <fieldset class="journey-fieldset" data-field-group="estimateReadiness">
                                <legend>Do you have a benefit estimate at Full Retirement Age?</legend>
                                <label class="choice-row"><input type="radio" name="estimateReadiness" value="recent"> <span>I have a recent benefit estimate.</span></label>
                                <label class="choice-row"><input type="radio" name="estimateReadiness" value="verify"> <span>I have an estimate, but it needs verification.</span></label>
                                <label class="choice-row"><input type="radio" name="estimateReadiness" value="missing"> <span>I do not have a usable estimate yet.</span></label>
                            </fieldset>
                            <div class="coach-response" id="estimateReadinessResponse" hidden>
                                <p>You can continue, but the analyzer needs a benefit estimate to compare claiming ages. First, review your official Social Security statement and earnings record.</p>
                            </div>
                        </section>

                        <section class="phase-content-section" aria-labelledby="before-analyzer-title">
                            <h2 id="before-analyzer-title">Before you compare claiming ages</h2>
                            <p>Keep these three ideas in mind as you review the results.</p>
                            <div class="prompt-list">
                                <div>
                                    <h3>Retirement and claiming are separate decisions</h3>
                                    <p>You may stop working before or after you begin Social Security. Do not assume the two dates must be the same.</p>
                                </div>
                                <div>
                                    <h3>A larger lifetime total isn’t the whole answer</h3>
                                    <p>Lifetime totals depend on how long you live and on the numbers you enter. They do not show your spending needs, taxes, investment risk, household needs, or whether you can afford to wait.</p>
                                </div>
                                <div>
                                    <h3>Couples should think beyond two individual comparisons</h3>
                                    <p>One spouse’s claiming choice may affect future survivor income. The primary analyzer does not compare both spouses together.</p>
                                </div>
                            </div>
                            <p class="decision-frame">Use the analyzer to compare claiming ages. Use your full retirement plan to decide which choice you can afford.</p>
                        </section>

                        <section class="phase-content-section" aria-labelledby="notice-title">
                            <h2 id="notice-title">What to notice inside the analyzer</h2>
                            <p>You do not need to study every number. Focus on these five results.</p>
                            <ol class="notice-list">
                                <li><strong>Monthly benefit at each claiming age.</strong> How much dependable monthly income does each option provide?</li>
                                <li><strong>The cost of claiming sooner.</strong> How much smaller is the monthly benefit at the earliest age?</li>
                                <li><strong>The cost of waiting.</strong> How many years must you cover your spending before the later benefit begins? Phase 3 will show whether your savings can cover that time.</li>
                                <li><strong>Break-even age.</strong> At about what age do total benefits from waiting catch up with the earlier option? Treat this as one clue, not a lifespan prediction.</li>
                                <li><strong>The result you want to test in Phase 3.</strong> Which claiming age do you want to test in your retirement plan?</li>
                            </ol>
                            <p class="supporting-note">Write down the claiming age you want to test and its estimated monthly benefit. You will record both when you return.</p>
                        </section>

                        <section class="phase-content-section return-section" id="return-from-analyzer" aria-labelledby="return-title">
                            <p class="eyebrow">After the analyzer</p>
                            <h2 id="return-title">Welcome back. Let’s make sense of what you found.</h2>
                            <p>Now consider how the benefit amounts fit your retirement.</p>

                            <fieldset class="journey-fieldset" data-field-group="interest">
                                <legend>Which claiming age do you want to test in Phase 3?</legend>
                                <div class="compact-choice-grid">
                                    <?php for ($age = 62; $age <= 70; $age++): ?>
                                        <label class="choice-row"><input type="radio" name="interest" value="<?php echo $age; ?>"> <span>Age <?php echo $age; ?></span></label>
                                    <?php endfor; ?>
                                </div>
                                <label class="choice-row"><input type="radio" name="interest" value="receiving"> <span>I am already receiving benefits.</span></label>
                                <label class="choice-row"><input type="radio" name="interest" value="not-ready"> <span>I am not ready to select an age.</span></label>
                            </fieldset>

                            <fieldset class="journey-fieldset" data-field-group="rationale">
                                <legend>What made this option stand out?</legend>
                                <label class="choice-row"><input type="radio" name="rationale" value="sooner"> <span>It provides income sooner.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="later"> <span>It provides a larger dependable benefit later.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="balance"> <span>It appears to balance earlier income and later security.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="lifetime"> <span>It produced the highest estimated lifetime total.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="retirement"> <span>It aligns with when I expect to retire.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="starting-point"> <span>I selected it as a starting point, not a final choice.</span></label>
                                <label class="choice-row"><input type="radio" name="rationale" value="not-ready"> <span>I am not ready to choose.</span></label>
                            </fieldset>
                            <div class="coach-response" id="rationaleResponse" aria-live="polite" hidden></div>
                        </section>

                        <form id="phase2RecordForm" novalidate>
                            <section class="phase-content-section" aria-labelledby="record-title">
                                <h2 id="record-title">Record the claiming age you want to test</h2>
                                <p>Save the numbers you want to use in Phase 3. This does not apply for benefits.</p>

                                <div class="error-summary" id="phase2ErrorSummary" role="alert" tabindex="-1" hidden>
                                    <h3>Please review the following</h3>
                                    <ul></ul>
                                </div>

                                <div class="journey-form-grid">
                                    <div class="field-group">
                                        <label for="birthYear">Birth year <span class="optional-label">(optional)</span></label>
                                        <input id="birthYear" name="birthYear" type="number" min="1920" max="<?php echo date('Y'); ?>" step="1" inputmode="numeric">
                                        <small>Saved for Phase 3 when you provide it.</small>
                                    </div>

                                    <div class="field-group">
                                        <label for="decisionStatus">Where are you now?</label>
                                        <select id="decisionStatus" name="decisionStatus">
                                            <option value="">Choose a status</option>
                                            <option value="provisional">Claiming age to test</option>
                                            <option value="need-more-information">Need more information</option>
                                            <option value="already-receiving">Already receiving benefits</option>
                                        </select>
                                        <small id="decisionStatusHelp">Choose the option that best describes your decision today.</small>
                                    </div>

                                    <div class="field-group" id="claimAgeGroup">
                                        <label for="claimAge">Claiming age to test</label>
                                        <select id="claimAge" name="claimAge">
                                            <option value="">Not selected</option>
                                            <?php for ($age = 62; $age <= 70; $age++): ?>
                                                <option value="<?php echo $age; ?>">Age <?php echo $age; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <small>The age you currently want to test in your retirement plan.</small>
                                    </div>

                                    <div class="field-group" id="benefitAtFraGroup">
                                        <label for="benefitAtFra">Monthly benefit at Full Retirement Age</label>
                                        <div class="money-input"><span aria-hidden="true">$</span><input id="benefitAtFra" name="benefitAtFra" type="number" min="0" step="1" inputmode="decimal"></div>
                                        <small>Use the amount from your Social Security statement before Medicare deductions.</small>
                                    </div>

                                    <div class="field-group">
                                        <label for="selectedMonthlyBenefit" id="selectedMonthlyBenefitLabel">Estimated monthly benefit at the selected age</label>
                                        <div class="money-input"><span aria-hidden="true">$</span><input id="selectedMonthlyBenefit" name="selectedMonthlyBenefit" type="number" min="0" step="1" inputmode="decimal"></div>
                                        <small id="selectedMonthlyBenefitHelp">Use the amount shown by the Claiming Analyzer for the age you selected.</small>
                                    </div>
                                </div>

                                <fieldset class="journey-fieldset" data-field-group="mainTradeoff">
                                    <legend>Main tradeoff</legend>
                                    <p class="fieldset-help">Complete this thought: “With this choice, I gain ___, but I give up or must provide ___.”</p>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="income-sooner"> <span>Income sooner, but a smaller monthly benefit for life.</span></label>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="larger-later"> <span>A larger later benefit, but I must cover my expenses while I wait.</span></label>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="balance"> <span>A balance between earlier income and later security.</span></label>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="lifetime-assumption"> <span>A higher estimated lifetime total, but the result depends more on how long I live.</span></label>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="unresolved"> <span>I have not decided which tradeoff I prefer.</span></label>
                                    <label class="choice-row"><input type="radio" name="mainTradeoff" value="other"> <span>Another tradeoff.</span></label>
                                    <div class="field-group nested-field" id="otherTradeoffGroup" hidden>
                                        <label for="otherTradeoff">Describe the other tradeoff</label>
                                        <input id="otherTradeoff" name="otherTradeoff" type="text" maxlength="240">
                                    </div>
                                </fieldset>

                                <fieldset class="journey-fieldset" data-field-group="verificationNeeded">
                                    <legend>What do you still need to check?</legend>
                                    <p class="fieldset-help">Choose all that apply, then select the most important next step.</p>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="earnings-record"> <span>My earnings record</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="fra-benefit"> <span>My benefit at Full Retirement Age</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="early-exit"> <span>The effect of stopping work early</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="survivor"> <span>How this choice affects my spouse or survivor</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="delay-affordability"> <span>Whether I can afford to delay</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="current-rules"> <span>Current Social Security rules</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="nothing-yet"> <span>Nothing yet</span></label>
                                    <label class="choice-row"><input type="checkbox" name="verificationNeeded" value="other"> <span>Another item</span></label>

                                    <div class="field-group nested-field">
                                        <label for="verificationPriority">Most important next step</label>
                                        <select id="verificationPriority" name="verificationPriority">
                                            <option value="">Choose one</option>
                                            <option value="earnings-record">My earnings record</option>
                                            <option value="fra-benefit">My benefit at Full Retirement Age</option>
                                            <option value="early-exit">The effect of stopping work early</option>
                                            <option value="survivor">How this choice affects my spouse or survivor</option>
                                            <option value="delay-affordability">Whether I can afford to delay</option>
                                            <option value="current-rules">Current Social Security rules</option>
                                            <option value="nothing-yet">Nothing yet</option>
                                            <option value="other">Another item</option>
                                        </select>
                                    </div>
                                </fieldset>

                                <button type="submit" class="primary-action journey-button">Save My Claiming Choice</button>
                                <div class="save-confirmation" id="phase2SaveConfirmation" role="status" tabindex="-1" hidden>
                                    <strong>Your current Social Security planning record has been saved in this browser.</strong>
                                    <span>Your retirement plan can use this choice in Phase 3. You can review and update it later.</span>
                                </div>
                            </section>
                        </form>

                        <section class="phase-content-section" id="companion-section" aria-labelledby="companion-title">
                            <h2 id="companion-title">Would one more calculator help?</h2>
                            <p>Answer these three questions. We’ll point you to no more than one additional calculator.</p>

                            <fieldset class="journey-fieldset">
                                <legend>Could stopping work before you claim lower the benefit shown on your statement?</legend>
                                <div class="inline-choices">
                                    <label class="choice-row"><input type="radio" name="earlyExitAnswer" value="yes"> <span>Yes</span></label>
                                    <label class="choice-row"><input type="radio" name="earlyExitAnswer" value="no"> <span>No</span></label>
                                    <label class="choice-row"><input type="radio" name="earlyExitAnswer" value="not-sure"> <span>Not sure</span></label>
                                </div>
                            </fieldset>

                            <fieldset class="journey-fieldset">
                                <legend>Would you like to see how one spouse’s death could change the Social Security income left for the survivor?</legend>
                                <div class="inline-choices">
                                    <label class="choice-row"><input type="radio" name="survivorAnswer" value="yes"> <span>Yes</span></label>
                                    <label class="choice-row"><input type="radio" name="survivorAnswer" value="no"> <span>No</span></label>
                                    <label class="choice-row"><input type="radio" name="survivorAnswer" value="not-applicable"> <span>Not applicable</span></label>
                                </div>
                            </fieldset>

                            <fieldset class="journey-fieldset">
                                <legend>Would you like to see how much spending remains after Social Security and other dependable income?</legend>
                                <div class="inline-choices">
                                    <label class="choice-row"><input type="radio" name="spendingGapAnswer" value="yes"> <span>Yes</span></label>
                                    <label class="choice-row"><input type="radio" name="spendingGapAnswer" value="no"> <span>No</span></label>
                                </div>
                            </fieldset>

                            <div class="companion-result" id="companionResult" aria-live="polite">
                                <h3>Complete the three questions above</h3>
                                <p>Your answers will show whether another calculator could help.</p>
                            </div>
                        </section>

                        <section class="phase-content-section" id="assumption-section" aria-labelledby="assumption-title" data-first-visit-summary>
                            <div class="record-overview-heading">
                                <h2 id="assumption-title">Your Social Security planning record</h2>
                                <span class="record-status-badge" data-phase2-record-status hidden></span>
                            </div>
                            <div class="assumption-statement" data-phase2-summary>
                                <p>Save your claiming choice to create a short summary for Phase 3.</p>
                            </div>
                            <div class="review-guidance" data-phase2-review-guidance hidden>
                                <h3>When to review this again</h3>
                                <p>Review this record during your annual plan review, or sooner if your retirement date, claiming age, Social Security statement, earnings record, spouse’s plan, or benefit status changes.</p>
                            </div>
                            <button type="button" class="secondary-action journey-button" data-revise-assumption hidden>Revise My Record</button>
                        </section>

                        <section class="phase-content-section" aria-labelledby="before-continue-title">
                            <h2 id="before-continue-title">Before you continue</h2>
                            <p>Make sure you can answer these questions:</p>
                            <ul class="coach-list">
                                <li>Which claiming age or current benefit should Phase 3 use?</li>
                                <li>What monthly benefit should Phase 3 use?</li>
                                <li>What is the main tradeoff?</li>
                                <li>What still needs verification?</li>
                                <li>Is this a claiming age to test, a decision that still needs work, or an existing benefit?</li>
                            </ul>
                            <p class="trust-note">Completing Phase 2 does not file for Social Security or make your choice permanent. Verify official benefit information and current rules before acting.</p>
                        </section>

                        <section class="phase-content-section phase-completion" aria-labelledby="continue-title">
                            <h2 id="continue-title">Continue to Phase 3: Build Your Plan</h2>
                            <p>You now have a claiming age to test alongside your retirement spending, savings, and other income.</p>
                            <p>In Phase 3, you will combine it with your spending, savings, and other income to see how your plan works year by year.</p>
                            <p><strong>You’re ready to bring the major pieces together.</strong></p>
                            <button type="button" class="primary-action journey-button" id="completePhase2Button">Save and Continue to Phase 3</button>
                            <a class="secondary-action" href="/">Return to My Journey</a>
                            <div class="completion-message" id="phase2CompletionMessage" role="status" tabindex="-1" hidden></div>
                            <p class="trust-note">Completing Phase 2 records your progress through the Journey. Your Social Security planning record remains separate and can still need information, verification, or a later review.</p>
                        </section>
                    </article>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../includes/progress-nav.php'; ?>

                    <div class="next-step-card" aria-labelledby="next-step-title">
                        <p class="eyebrow">Recommended Tool</p>
                        <h2 id="next-step-title">Social Security Claiming Analyzer</h2>
                        <p>Compare three claiming ages for one person. The analyzer estimates monthly benefits, total benefits over time, and break-even ages.</p>

                        <h3>What it shows</h3>
                        <p>It estimates how your monthly benefit changes if you claim at different ages.</p>

                        <h3>What it doesn’t tell you</h3>
                        <p>It doesn’t tell you which claiming age is best for your overall retirement plan. It also doesn’t show whether you can afford to wait, combined spouse benefits, survivor income, taxes, Medicare effects, the earnings test, or how leaving work earlier could change your benefit.</p>

                        <div class="analyzer-clarification">
                            <strong>About “Best Option”</strong>
                            <p>This means only that the age produced the highest estimated total through the age you entered. It isn’t advice to file at that age.</p>
                        </div>

                        <a class="primary-action" href="https://ronbelisle.com/social-security-claiming-analyzer/" target="_blank" rel="noopener">Open Social Security Claiming Analyzer</a>
                        <p class="action-note">Opens in a separate tab. When you finish comparing ages, return to this Phase 2 page.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <script src="/assets/js/social-security-phase.js" defer></script>
</body>
</html>
