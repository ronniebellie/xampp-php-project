<?php
/**
 * Authoritative catalog for the public RonBelisle.com calculator suite.
 *
 * Feature flags describe verified working functionality. They intentionally do
 * not count placeholder buttons, "coming soon" handlers, or marketing-only
 * claims. `compare` means a working comparison of saved scenarios; it is not
 * inferred from `save` and does not include comparisons intrinsic to a
 * calculator's basic calculation.
 */

declare(strict_types=1);

if (defined('RB_CALCULATOR_CATALOG_LOADED')) {
    return;
}
define('RB_CALCULATOR_CATALOG_LOADED', 1);

const RB_CALCULATOR_MASTER_CATEGORIES = [
    'build-plan' => 'Build and Stress-Test Your Retirement Plan',
    'social-security' => 'Social Security',
    'income-spending' => 'Retirement Income and Spending',
    'taxes-rmd' => 'Taxes, Roth Conversions and RMDs',
    'investments-fees' => 'Investments, Fees and Growth',
    'estate-survivor' => 'Estate and Survivor Planning',
    'savings-debt-goals' => 'Savings, Debt and Major Goals',
];

const RB_CALCULATOR_ADVISOR_CATEGORIES = [
    'retirement-planning' => 'Retirement Planning',
    'social-security' => 'Social Security',
    'tax-roth-rmd' => 'Tax, Roth and RMD Planning',
    'survivor-estate' => 'Survivor and Estate Planning',
    'investment-fees' => 'Investment and Fee Analysis',
];

/**
 * @return array<string,array<string,mixed>> keyed by stable calculator ID
 */
function rb_calculator_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = [
        'retirement-plan-builder' => [
            'name' => 'Retirement Plan Builder',
            'route' => '/retirement-plan/',
            'description' => 'Build a year-by-year retirement plan combining savings, Social Security, spending, RMDs, and estimated federal taxes.',
            'master_category' => 'build-plan', 'display_order' => 10,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'retirement-planning',
        ],
        'social-security-claiming-analyzer' => [
            'name' => 'Social Security Claiming Analyzer',
            'route' => '/social-security-claiming-analyzer/',
            'description' => 'Compare claiming ages and see how monthly and lifetime Social Security benefits change over time.',
            'master_category' => 'social-security', 'display_order' => 20,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'social-security',
        ],
        'early-exit-social-security-impact' => [
            'name' => 'Early Exit Social Security Impact',
            'route' => '/ss-early-exit/',
            'description' => 'Estimate how stopping work earlier than planned can reduce the benefit assumed by an SSA statement.',
            'master_category' => 'social-security', 'display_order' => 30,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'social-security',
        ],
        'social-security-survivor-impact' => [
            'name' => 'Social Security Survivor Impact Calculator',
            'route' => '/ss-survivor-impact/',
            'description' => 'Compare couples claiming strategies, survivor income, longevity assumptions, and lifetime household benefits.',
            'master_category' => 'social-security', 'display_order' => 40,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'social-security',
        ],
        'social-security-spending-gap' => [
            'name' => 'Social Security + Spending Gap Calculator',
            'route' => '/ss-gap/',
            'description' => 'Calculate the retirement spending gap remaining after Social Security and the portfolio needed to support it.',
            'master_category' => 'social-security', 'display_order' => 50,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'social-security',
        ],
        'retirement-spending-checkup' => [
            'name' => 'Retirement Spending & On-Track Checkup',
            'route' => '/retirement-spending-checkup/',
            'description' => 'Estimate a retirement budget, guaranteed income, spending gap, and whether savings appear on track.',
            'master_category' => 'income-spending', 'display_order' => 60,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => false,
            'advisor' => true, 'advisor_category' => 'retirement-planning',
        ],
        'retirement-timeline-checklist' => [
            'name' => 'Retirement Timeline & Checklist',
            'route' => '/retirement-timeline/',
            'description' => 'Turn a target retirement date into a phased preparation checklist through the first year of retirement.',
            'master_category' => 'build-plan', 'display_order' => 70,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => false,
            'advisor' => false, 'advisor_category' => null,
        ],
        'pension-vs-lump-sum' => [
            'name' => 'Pension vs. Lump Sum Calculator',
            'route' => '/pension-vs-lump-sum/',
            'description' => 'Compare pension income with a lump sum using payback timing, investment assumptions, and life expectancy.',
            'master_category' => 'income-spending', 'display_order' => 80,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'retirement-planning',
        ],
        'future-value' => [
            'name' => 'Future Value Calculator',
            'route' => '/future-value-app/',
            'description' => 'Calculate present value, future value, annuities, rates, periods, and required payments.',
            'master_category' => 'income-spending', 'display_order' => 90,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'required-vs-desired-spending' => [
            'name' => 'Required vs. Desired Spending Calculator',
            'route' => '/required-vs-desired/',
            'description' => 'Separate essential and discretionary spending to estimate minimum and desired retirement portfolios.',
            'master_category' => 'income-spending', 'display_order' => 100,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'retirement-planning',
        ],
        'roth-conversion' => [
            'name' => 'Roth Conversion Calculator',
            'route' => '/roth-conv/',
            'description' => 'Compare conversion and no-conversion strategies including taxes, RMDs, IRMAA, NIIT, and survivor effects.',
            'master_category' => 'taxes-rmd', 'display_order' => 110,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'tax-roth-rmd',
        ],
        'rmd-impact' => [
            'name' => 'RMD Impact Calculator',
            'route' => '/rmd-impact/',
            'description' => 'Project required minimum distributions, balances, taxes, and retirement income over time.',
            'master_category' => 'taxes-rmd', 'display_order' => 120,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'tax-roth-rmd',
        ],
        'plan-success-monte-carlo' => [
            'name' => 'Plan Success (Monte Carlo)',
            'route' => '/plan-success/',
            'description' => 'Stress-test whether a portfolio may support withdrawals across randomized market-return scenarios.',
            'master_category' => 'build-plan', 'display_order' => 130,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'retirement-planning',
        ],
        'survivor-gap' => [
            'name' => 'Survivor Gap Calculator',
            'route' => '/survivor-gap/',
            'description' => 'Compare single-life and joint-life pension income and estimate insurance needed for a surviving spouse.',
            'master_category' => 'estate-survivor', 'display_order' => 140,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'survivor-estate',
        ],
        'debt-payoff' => [
            'name' => 'Debt Payoff Calculator',
            'route' => '/debt-payoff/',
            'description' => 'Compare debt payoff methods, timelines, interest, and the effect of additional payments.',
            'master_category' => 'savings-debt-goals', 'display_order' => 150,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'inherited-ira-legacy-tax-impact' => [
            'name' => 'Inherited IRA & Legacy Tax Impact Calculator',
            'route' => '/estate-planning/inherited-ira-impact/',
            'description' => 'Model owner and heir taxes, Roth conversions, and inherited IRA withdrawals under a 10-year rule.',
            'master_category' => 'estate-survivor', 'display_order' => 160,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'survivor-estate',
        ],
        'safe-withdrawal-rate-fee-impact' => [
            'name' => 'Safe Withdrawal Rate & Fee Impact',
            'route' => '/swr-fee-impact/',
            'description' => 'Estimate how advisory and fund fees affect retirement spending capacity and ending wealth.',
            'master_category' => 'investments-fees', 'display_order' => 170,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'investment-fees',
        ],
        'managed-portfolio-vs-vanguard' => [
            'name' => 'Managed Portfolio vs Vanguard Index Fund',
            'route' => '/managed-vs-vanguard/',
            'description' => 'Compare managed-portfolio fees and lost growth with a low-cost Vanguard index approach.',
            'master_category' => 'investments-fees', 'display_order' => 180,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => true, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'investment-fees',
        ],
        'vanguard-pas-vs-target-date' => [
            'name' => 'Vanguard Personal Advisor vs Target Date Funds',
            'route' => '/vanguard-pas-vs-target-date/',
            'description' => 'Compare Vanguard Personal Advisor fees with a self-managed blend of target-date funds.',
            'master_category' => 'investments-fees', 'display_order' => 190,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => true, 'compare' => false, 'pdf' => true, 'csv' => true, 'ai' => true, 'charts' => true,
            'advisor' => true, 'advisor_category' => 'investment-fees',
        ],
        'emergency-fund-builder' => [
            'name' => 'Emergency Fund Builder',
            'route' => '/emergency-fund/',
            'description' => 'Set an emergency-fund target and estimate how long it will take to reach it.',
            'master_category' => 'savings-debt-goals', 'display_order' => 200,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'debt-vs-saving' => [
            'name' => 'Debt vs Saving: Which First?',
            'route' => '/debt-vs-saving/',
            'description' => 'Compare directing extra cash to debt with investing it and estimate the resulting net worth.',
            'master_category' => 'savings-debt-goals', 'display_order' => 210,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => false,
            'advisor' => false, 'advisor_category' => null,
        ],
        'student-loan-payoff' => [
            'name' => 'Student Loan Payoff Calculator',
            'route' => '/student-loan-payoff/',
            'description' => 'Model student-loan payoff timelines, refinancing, interest, and additional payments.',
            'master_category' => 'savings-debt-goals', 'display_order' => 220,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'down-payment-house-savings' => [
            'name' => 'Down Payment / House Savings',
            'route' => '/down-payment/',
            'description' => 'Estimate the monthly saving or time needed to reach a home down-payment goal.',
            'master_category' => 'savings-debt-goals', 'display_order' => 230,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        '401k-ira-on-track' => [
            'name' => '401(k) / IRA On Track?',
            'route' => '/401k-on-track/',
            'description' => 'Project retirement-account growth and estimate whether current savings and contributions are on track.',
            'master_category' => 'build-plan', 'display_order' => 240,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'nest-egg-target' => [
            'name' => 'How Much Do I Need? Nest Egg Target',
            'route' => '/nest-egg-target/',
            'description' => 'Estimate a retirement nest-egg target from desired income, guaranteed income, and a withdrawal rate.',
            'master_category' => 'build-plan', 'display_order' => 250,
            'active' => true, 'free' => true, 'premium' => true,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => true, 'charts' => false,
            'advisor' => false, 'advisor_category' => null,
        ],
        'compound-interest' => [
            'name' => 'The Power of Compound Interest',
            'route' => '/compound-interest/',
            'description' => 'Explore how an initial balance, contributions, return, and time affect long-term growth.',
            'master_category' => 'investments-fees', 'display_order' => 260,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => true,
            'advisor' => false, 'advisor_category' => null,
        ],
        'retirement-trade-off-explorer' => [
            'name' => 'Retirement Trade-Off Explorer',
            'route' => '/trade-off-explorer/',
            'description' => 'Compare how retiring later, saving more, spending less, or earning part-time income changes retirement readiness.',
            'master_category' => 'build-plan', 'display_order' => 270,
            'active' => true, 'free' => true, 'premium' => false,
            'save' => false, 'compare' => false, 'pdf' => false, 'csv' => false, 'ai' => false, 'charts' => false,
            'advisor' => false, 'advisor_category' => null,
        ],
    ];

    foreach ($catalog as $id => &$calculator) {
        $calculator = ['id' => $id] + $calculator;
    }
    unset($calculator);

    uasort($catalog, static fn(array $a, array $b): int => $a['display_order'] <=> $b['display_order']);
    return $catalog;
}

/** @return array<string,array<string,mixed>> */
function rb_active_calculators(): array
{
    return array_filter(rb_calculator_catalog(), static fn(array $calculator): bool => $calculator['active'] === true);
}

/** @return array<string,array<string,mixed>> */
function rb_calculators_by_master_category(string $category): array
{
    return array_filter(rb_active_calculators(), static fn(array $calculator): bool => $calculator['master_category'] === $category);
}

/** @return array<string,array<string,mixed>> */
function rb_advisor_calculators(): array
{
    return array_filter(rb_active_calculators(), static fn(array $calculator): bool => $calculator['advisor'] === true);
}

/** @return array<string,array<string,array<string,mixed>>> */
function rb_advisor_calculators_grouped(): array
{
    $groups = [];
    foreach (RB_CALCULATOR_ADVISOR_CATEGORIES as $category => $label) {
        $groups[$category] = [];
    }
    foreach (rb_advisor_calculators() as $id => $calculator) {
        $groups[$calculator['advisor_category']][$id] = $calculator;
    }
    return $groups;
}

/** @return array<string,mixed>|null */
function rb_calculator_by_id(string $id): ?array
{
    $catalog = rb_calculator_catalog();
    return $catalog[$id] ?? null;
}

/** @return array<string,mixed>|null */
function rb_calculator_by_route(string $route): ?array
{
    $normalized = '/' . trim(parse_url($route, PHP_URL_PATH) ?: '', '/') . '/';
    foreach (rb_calculator_catalog() as $calculator) {
        if ($calculator['route'] === $normalized) {
            return $calculator;
        }
    }
    return null;
}

/** @return array<string,int> */
function rb_calculator_feature_totals(): array
{
    $calculators = rb_active_calculators();
    $features = ['active', 'free', 'premium', 'save', 'compare', 'pdf', 'csv', 'ai', 'charts', 'advisor'];
    $totals = [];
    foreach ($features as $feature) {
        $totals[$feature] = $feature === 'active'
            ? count($calculators)
            : count(array_filter($calculators, static fn(array $calculator): bool => $calculator[$feature] === true));
    }
    return $totals;
}
