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
        <section class="page-hero calculator-hero" aria-labelledby="calculator-title">
            <div class="container phase-template-grid">
                <div class="phase-main-column">
                    <div class="phase-intro">
                        <p class="eyebrow">Phase 1 calculator</p>
                        <h1 id="calculator-title">Your Retirement Spending Plan</h1>
                        <p class="page-lede">Estimate a practical retirement spending target you can carry into the rest of your Journey. Use estimates where needed. You can return and update this plan later.</p>
                    </div>

                    <form class="planning-panel journey-calculator" id="retirementSpendingPlanForm" novalidate>
                        <div class="error-summary" id="spendingPlanErrorSummary" role="alert" tabindex="-1" hidden>
                            <h2>Please review the following</h2>
                            <ul></ul>
                        </div>

                        <section class="calculator-section" aria-labelledby="start-method-title">
                            <p class="eyebrow">Step 1</p>
                            <h2 id="start-method-title">Choose how to begin</h2>
                            <p>Pick the starting point that feels easiest. The guided worksheet is recommended if you do not already know your household spending.</p>
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
                                        <strong>I know my monthly spending</strong>
                                        <small>Enter one monthly estimate.</small>
                                    </span>
                                </label>
                                <label class="method-card">
                                    <input type="radio" name="startingMethod" value="annual_estimate">
                                    <span>
                                        <strong>I know my annual spending</strong>
                                        <small>Enter one annual estimate.</small>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="guided_categories" aria-labelledby="categories-title">
                            <p class="eyebrow">Step 2</p>
                            <h2 id="categories-title">Estimate current household spending</h2>
                            <p>Use monthly estimates. Leave a category at zero if it does not apply or you are not sure yet.</p>
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
                                    <label for="otherSpending">Other spending</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="otherSpending" name="otherSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Any regular spending that does not fit the categories above.</small>
                                </div>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="monthly_estimate" hidden aria-labelledby="monthly-title">
                            <p class="eyebrow">Step 2</p>
                            <h2 id="monthly-title">Enter your monthly estimate</h2>
                            <div class="field-group field-group-wide">
                                <label for="currentMonthlySpending">Current monthly household spending</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="currentMonthlySpending" name="currentMonthlySpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>Use your best estimate of what your household spends in a typical month. The number does not need to be perfect.</small>
                            </div>
                        </section>

                        <section class="calculator-section" data-method-section="annual_estimate" hidden aria-labelledby="annual-title">
                            <p class="eyebrow">Step 2</p>
                            <h2 id="annual-title">Enter your annual estimate</h2>
                            <div class="field-group field-group-wide">
                                <label for="currentAnnualSpending">Current annual household spending</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="currentAnnualSpending" name="currentAnnualSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>Use your best estimate of what your household spends in a typical year.</small>
                            </div>
                        </section>

                        <section class="calculator-section" aria-labelledby="adjustments-title">
                            <p class="eyebrow">Step 3</p>
                            <h2 id="adjustments-title">Expected monthly household spending in retirement</h2>
                            <p>Enter your best estimate of what your household will spend in a typical month after you retire. Consider expenses that may end, decrease, increase, or begin—but you do not need to calculate each change separately.</p>
                            <div class="field-group field-group-wide">
                                <label for="expectedMonthlyRetirementSpending">Expected monthly household spending in retirement</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="expectedMonthlyRetirementSpending" name="expectedMonthlyRetirementSpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>This becomes your monthly retirement spending target.</small>
                            </div>
                            <details class="calculator-help">
                                <summary>Need help estimating this?</summary>
                                <ul>
                                    <li>Commuting or payroll contributions may end.</li>
                                    <li>Debt payments may end or decrease.</li>
                                    <li>Healthcare or insurance may increase.</li>
                                    <li>Travel, hobbies, family support, or home maintenance may change.</li>
                                </ul>
                            </details>
                        </section>

                        <section class="calculator-section" aria-labelledby="split-title">
                            <p class="eyebrow">Step 4</p>
                            <h2 id="split-title">Separate essential and flexible spending</h2>
                            <p>Essential spending is what you would try hardest to protect. Flexible spending is what could be adjusted if conditions change.</p>
                            <div class="journey-form-grid">
                                <div class="field-group">
                                    <label for="essentialMonthlySpending">Monthly essential spending</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="essentialMonthlySpending" name="essentialMonthlySpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Housing, food, utilities, healthcare, insurance, and required debt payments.</small>
                                </div>
                                <div class="field-group">
                                    <label for="flexibleMonthlySpending">Monthly flexible spending</label>
                                    <div class="money-input"><span aria-hidden="true">$</span><input id="flexibleMonthlySpending" name="flexibleMonthlySpending" type="number" min="0" step="1" inputmode="decimal"></div>
                                    <small>Travel, hobbies, restaurants, gifts, giving, and lifestyle choices.</small>
                                </div>
                            </div>
                        </section>

                        <section class="calculator-section" aria-labelledby="income-title">
                            <p class="eyebrow">Step 5</p>
                            <h2 id="income-title">Add other regular retirement income</h2>
                            <div class="field-group field-group-wide">
                                <label for="monthlyOtherRegularRetirementIncome">Monthly other regular retirement income</label>
                                <div class="money-input"><span aria-hidden="true">$</span><input id="monthlyOtherRegularRetirementIncome" name="monthlyOtherRegularRetirementIncome" type="number" min="0" step="1" inputmode="decimal"></div>
                                <small>Include pensions, annuity payments, rental income, or other regular income. Do not include Social Security, IRA or 401(k) withdrawals, or other investment withdrawals yet.</small>
                            </div>
                            <div class="field-group field-group-wide">
                                <label for="spendingNotes">Notes about your estimate <span class="optional-label">(optional)</span></label>
                                <textarea id="spendingNotes" name="spendingNotes" maxlength="2000" rows="4"></textarea>
                                <small>Use notes to remember what you included or want to revisit later.</small>
                            </div>
                        </section>

                        <section class="calculator-section calculator-results" id="spendingPlanResults" aria-labelledby="results-title" aria-live="polite" hidden>
                            <p class="eyebrow">Your result</p>
                            <h2 id="results-title">Review your retirement spending target</h2>
                            <div class="result-metrics">
                                <div class="result-metric is-primary">
                                    <span>Monthly retirement spending target</span>
                                    <strong data-result="monthlyTarget">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Annual retirement spending target</span>
                                    <strong data-result="annualTarget">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Essential monthly spending</span>
                                    <strong data-result="essentialMonthly">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Flexible monthly spending</span>
                                    <strong data-result="flexibleMonthly">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Other regular monthly income</span>
                                    <strong data-result="otherIncomeMonthly">$0</strong>
                                </div>
                                <div class="result-metric">
                                    <span>Remaining monthly need</span>
                                    <strong data-result="remainingMonthly">$0</strong>
                                </div>
                            </div>
                            <div class="assumption-statement" data-result="assumptions"></div>
                        </section>

                        <div class="calculator-actions">
                            <button type="button" class="secondary-action button-action" id="calculateSpendingPlan">Calculate My Spending Target</button>
                            <button type="submit" class="primary-action button-action">Save and Return to Phase 1</button>
                        </div>
                        <p class="save-status" id="spendingPlanSaveStatus" aria-live="polite">Draft inputs are saved in this browser for the prototype.</p>
                    </form>
                </div>

                <aside class="phase-sidebar-column">
                    <?php include __DIR__ . '/../../includes/progress-nav.php'; ?>

                    <div class="next-step-card">
                        <p class="eyebrow">Phase context</p>
                        <h2>What this saves</h2>
                        <p>This calculator saves one active retirement spending plan for Phase 1. You can update it later as your assumptions become clearer.</p>
                        <a class="secondary-action" href="/phases/spending-goals.php">Return to Spending &amp; Goals</a>
                    </div>
                </aside>
            </div>
        </section>
    </main>
    <script src="/assets/js/retirement-spending-plan.js" defer></script>
</body>
</html>
