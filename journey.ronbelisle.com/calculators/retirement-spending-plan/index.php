<?php
$active_phase = 'spending-goals';
$page_title = 'Your Retirement Spending Plan | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260728-phase1-copy">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="/" aria-label="Retirement Planning Journey home">
            <span class="brand-mark" aria-hidden="true">RB</span>
            <span>Retirement Planning Journey</span>
        </a>
    </header>

    <main>
        <section class="page-hero calculator-hero" aria-labelledby="calculator-title">
            <div class="container phase-template-grid">
                <div class="phase-main-column">
                    <div class="phase-intro">
                        <p class="eyebrow">Phase 1 calculator</p>
                        <h1 id="calculator-title">Your Retirement Spending Plan</h1>
                        <p class="page-lede">Estimate the monthly living expenses your retirement lifestyle will need. Use estimates where needed. You can update this plan later.</p>
                        <div class="phase-status-note" role="note">
                            <p><strong>What “spending” means here:</strong> Include the costs of your household lifestyle—housing, food, transportation, healthcare, utilities, insurance, entertainment, and similar living expenses.</p>
                            <p>Do not include retirement savings, investment contributions, or income taxes unless a later step specifically asks for them.</p>
                        </div>
                    </div>

                    <form class="planning-panel journey-calculator" id="retirementSpendingPlanForm" novalidate>
                        <div class="error-summary" id="spendingPlanErrorSummary" role="alert" tabindex="-1" hidden>
                            <h2>Please review the following</h2>
                            <ul></ul>
                        </div>

                        <section class="calculator-section" aria-labelledby="retirement-status-title">
                            <p class="eyebrow">Step 1</p>
                            <h2 id="retirement-status-title">What best describes your situation today?</h2>
                            <p>This helps the planner use wording that matches your situation. It does not change the living-expense calculation itself.</p>
                            <div class="method-grid" role="radiogroup" aria-labelledby="retirement-status-title">
                                <label class="method-card">
                                    <input type="radio" name="retirementStatus" value="planning">
                                    <span>
                                        <strong>I am planning for retirement</strong>
                                        <small>I have not retired yet.</small>
                                    </span>
                                </label>
                                <label class="method-card">
                                    <input type="radio" name="retirementStatus" value="retired">
                                    <span>
                                        <strong>I am already retired</strong>
                                        <small>I am living in retirement now.</small>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="calculator-section" aria-labelledby="start-method-title">
                            <p class="eyebrow">Step 2</p>
                            <h2 id="start-method-title">Choose how to begin</h2>
                            <p>Pick the starting point that feels easiest. The guided worksheet is recommended if you do not already know your household living expenses.</p>
                            <div class="method-grid" role="radiogroup" aria-labelledby="start-method-title">
                                <label class="method-card">
                                    <input type="radio" name="startingMethod" value="guided_categories" checked>
                                    <span>
                                        <strong>Help me estimate by category</strong>
                                        <small>Recommended for most people.</small>
                                    </span>
                                </label>
                                <label class="method-card">
                                    <input type="radio" name="startingMethod" value="monthly_estimate">
                                    <span>
                                        <strong>I know my monthly living expenses</strong>
                                        <small>Enter one monthly estimate.</small>
                                    </span>
                                </label>
                                <label class="method-card">
                                    <input type="radio" name="startingMethod" value="annual_estimate">
                                    <span>
                                        <strong>I know my annual living expenses</strong>
                                        <small>Enter one annual estimate.</small>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="guided_categories" aria-labelledby="categories-title">
                            <p class="eyebrow">Step 3</p>
                            <h2 id="categories-title">Estimate current household living expenses</h2>
                            <p>Use monthly estimates for lifestyle costs. Leave a category at zero if it does not apply or you are not sure yet.</p>
                            <div class="journey-form-grid">
                                <div class="field-group">
                                    <label for="housing">Housing</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="housing" name="housing" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Mortgage or rent, property tax, insurance, utilities, repairs, and maintenance.</small>
                                </div>
                                <div class="field-group">
                                    <label for="foodHousehold">Food and household</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="foodHousehold" name="foodHousehold" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Groceries, household supplies, personal care, and routine purchases.</small>
                                </div>
                                <div class="field-group">
                                    <label for="transportation">Transportation</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="transportation" name="transportation" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Car payments, fuel, insurance, repairs, public transportation, or rideshares.</small>
                                </div>
                                <div class="field-group">
                                    <label for="healthcare">Healthcare</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="healthcare" name="healthcare" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Premiums, prescriptions, copays, dental, vision, and out-of-pocket costs.</small>
                                </div>
                                <div class="field-group">
                                    <label for="insuranceDebt">Insurance and debt</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="insuranceDebt" name="insuranceDebt" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Life insurance, long-term care insurance, loans, credit cards, and other debt payments.</small>
                                </div>
                                <div class="field-group">
                                    <label for="lifestyleGiving">Lifestyle, travel, hobbies, and giving</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="lifestyleGiving" name="lifestyleGiving" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Restaurants, entertainment, travel, hobbies, gifts, charity, and family support.</small>
                                </div>
                                <div class="field-group">
                                    <label for="otherSpending">Other living expenses</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="otherSpending" name="otherSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Any regular living expenses that do not fit the categories above.</small>
                                </div>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="monthly_estimate" hidden aria-labelledby="monthly-title">
                            <p class="eyebrow">Step 3</p>
                            <h2 id="monthly-title">Enter your current monthly household living expenses</h2>
                            <div class="field-group field-group-wide">
                                <div class="money-input"><span aria-hidden="true">$</span><input id="currentMonthlySpending" name="currentMonthlySpending" type="number" min="0" step="1" inputmode="decimal" aria-labelledby="monthly-title"></div>
                                <small>Use your best estimate of household living expenses in a typical month. The number does not need to be perfect.</small>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="annual_estimate" hidden aria-labelledby="annual-title">
                            <p class="eyebrow">Step 3</p>
                            <h2 id="annual-title">Enter your annual estimate</h2>
                            <div class="field-group field-group-wide">
                                <label for="currentAnnualSpending">Current annual household living expenses</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="currentAnnualSpending" name="currentAnnualSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>Use your best estimate of household living expenses in a typical year.</small>
                            </div>
                        </section>

                        <section class="calculator-section" aria-labelledby="adjustments-title">
                            <p class="eyebrow">Step 4</p>
                            <h2 id="adjustments-title" data-retirement-copy="title">Expected monthly household living expenses in retirement</h2>
                            <p data-retirement-copy="help">Enter your best estimate of monthly household living expenses after you retire. Consider costs that may end, decrease, increase, or begin—but you do not need to calculate each change separately.</p>
                            <div class="field-group field-group-wide">
                                <label for="expectedMonthlyRetirementSpending" data-retirement-copy="label">Expected monthly household living expenses in retirement</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="expectedMonthlyRetirementSpending" name="expectedMonthlyRetirementSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small data-retirement-copy="field-note">This becomes your monthly retirement living-expense target.</small>
                            </div>
                            <details class="calculator-help">
                                <summary>Need help estimating this?</summary>
                                <ul data-retirement-copy="tips">
                                    <li>Commuting or payroll contributions may end.</li>
                                    <li>Debt payments may end or decrease.</li>
                                    <li>Healthcare or insurance may increase.</li>
                                    <li>Travel, hobbies, family support, or home maintenance may change.</li>
                                </ul>
                            </details>
                        </section>

                        <section class="calculator-section" aria-labelledby="income-title">
                            <p class="eyebrow">Step 5</p>
                            <h2 id="income-title">Enter only retirement income from pensions, annuities, or rental income. Exclude Social Security, IRA, 401(k), and other investment withdrawals for now.</h2>
                            <div class="field-group field-group-wide">
                                <div class="money-input"><span aria-hidden="true">$</span><input id="monthlyOtherRegularRetirementIncome" name="monthlyOtherRegularRetirementIncome" type="number" min="0" step="1" inputmode="decimal" aria-labelledby="income-title"></div>
                            </div>
                            <div class="field-group field-group-wide">
                                <label for="spendingNotes">Notes about your estimate <span class="optional-label">(optional)</span></label>
                                <textarea id="spendingNotes" name="spendingNotes" maxlength="2000" rows="4"></textarea>
                                <small>Use notes to remember what you included or want to revisit later.</small>
                            </div>
                        </section>

                        <section class="calculator-section calculator-results" id="spendingPlanResults" aria-labelledby="results-title" aria-live="polite" hidden>
                            <p class="eyebrow">Your result</p>
                            <h2 id="results-title">Your retirement spending plan</h2>
                            <p>Review these living-expense numbers before saving.</p>
                            <dl class="plan-review-lines">
                                <div>
                                    <dt data-retirement-copy="result-monthly">Expected monthly retirement living expenses:</dt>
                                    <dd data-result="monthlyTarget">$0</dd>
                                </div>
                                <div>
                                    <dt>Covered by pensions, annuities, and rental income:</dt>
                                    <dd data-result="otherIncomeMonthly">$0</dd>
                                </div>
                                <div>
                                    <dt>Still to be covered by Social Security and investments:</dt>
                                    <dd data-result="remainingMonthly">$0</dd>
                                </div>
                                <div>
                                    <dt>Annual retirement living-expense target:</dt>
                                    <dd data-result="annualTarget">$0</dd>
                                </div>
                            </dl>
                            <p class="plan-review-handoff">This is the amount of living expenses your Social Security benefits and investment withdrawals will need to support in later phases of your Journey.</p>
                            <div class="assumption-statement">
                                <p data-result="assumptions"></p>
                            </div>
                        </section>

                        <div class="calculator-actions">
                            <button type="button" class="secondary-action button-action" id="calculateSpendingPlan">Calculate My Living-Expense Target</button>
                            <button type="submit" class="primary-action button-action">Save and Continue Phase 1</button>
                        </div>
                        <p class="save-status" id="spendingPlanSaveStatus" aria-live="polite">Draft inputs are saved in this browser for the prototype.</p>
                    </form>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../../includes/progress-nav.php'; ?>

                    <div class="next-step-card">
                        <p class="eyebrow">Phase context</p>
                        <h2>What this saves</h2>
                        <p>This planner saves one active retirement living-expense plan for Phase 1. You can update it later as your assumptions become clearer.</p>
                        <a class="secondary-action" href="/phases/spending-goals.php">Return to Spending &amp; Goals</a>
                    </div>
                </aside>
            </div>
        </section>
    </main>
    <script src="/assets/js/retirement-spending-plan.js?v=20260728-phase1-copy" defer></script>
</body>
</html>
