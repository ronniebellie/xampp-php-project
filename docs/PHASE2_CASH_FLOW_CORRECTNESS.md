# Phase 2: cash-flow correctness

Local implementation only. No production access, deployment, commits, or pushes.
Phase 1 is complete; Phase 3 has not started.

## Scope and methodology

### Roth Conversion

Every year now records beginning assets, external cash income, investment return,
funded spending, taxes actually paid, ending account balances, and explicit
spending/tax shortfalls. Requested spending is a separate field; `spending` and
`netCash` mean funded spending. A zero spending target remains zero.

RMDs and conversions precede other draws. Conversions transfer exactly the same
amount from traditional to Roth; they are not cash income. Available income and
RMD cash fund requested spending and taxes. Additional tax funding uses the chosen
account, with traditional/Roth fallback after taxable assets are exhausted, or the
existing alternative account order when Roth/traditional is selected. Converted
Roth assets can therefore fund tax if other sources are exhausted. The model does
not silently assume an external source of money.

Tax funding repeatedly recomputes the existing federal tax, gains, NIIT and IRMAA
calculations after withdrawals until the cash gap is below $0.0000001 or resources
are exhausted (128-pass guard). This includes ordinary income from traditional
withdrawals and estimated capital gains from brokerage sales. Each tax dollar is
attributed to actual income, RMD cash, or an identified account draw. If resources
are insufficient, taxes take priority over spending. Unpaid taxes are reported,
not counted as paid. This is a current-year cash model, not a model of delinquent
tax debt, interest or penalties; unpaid liabilities are not deducted from the
existing estate estimate and are disclosed alongside it.

Surplus cash is deposited into taxable assets with matching cost basis. Investment
returns apply after annual cash flows, as before. Entered investment distributions
(including tax-exempt interest) are cash income separate from account appreciation;
return assumptions must exclude those distributions to avoid double counting.

Hand-calculated single-filer, age-60, one-year fixtures use the existing 2026 Roth
tax assumptions, no other income, no IRMAA/NIIT, and a $100,000 conversion:

- Taxable brokerage with full basis: tax = $1,240 + $4,560 + $7,370 = $13,170.
- Traditional tax funding, with sufficient additional traditional assets:
  tax withdrawal = $13,170 / (1 - 22%) = $16,884.61538.
- Brokerage with zero basis, all sale gains in the 15% band:
  sale = $13,170 / (1 - 15%) = $15,494.11765.
- If all $100,000 of traditional assets are converted and no brokerage exists,
  $13,170 comes back out of Roth; ending Roth = $86,830, not $100,000.

### Retirement Plan Builder

The initial tax-deferred percentage divides assets into traditional and other
assets. Contributions use the same initial split. The actual account balances
persist thereafter; the initial percentage is no longer reapplied annually.
RMDs draw only from traditional assets. Discretionary withdrawals use other assets
first, then traditional assets. Only actual traditional draws enter the existing
simplified ordinary-income tax estimate.

The inputs do not identify Roth versus brokerage assets or brokerage cost basis.
Other assets are explicitly modeled as tax-free principal/Roth; gains on taxable
assets are not modeled. This assumption is disclosed in the page and PDF.

Taxes are paid from income and permitted withdrawals, with additional traditional
withdrawals grossed up for their own tax. Taxes take priority over spending when
resources are insufficient. Delaying discretionary withdrawals does not erase
spending needs: unmet spending/tax amounts are reported as shortfalls. Excess
income and RMD cash are saved into other assets after paying tax and spending.

Retirement cash flows occur before annual returns; accumulation contributions
occur after annual returns. Terminal-year depletion is reported, while exhausting
assets exactly after funding final-year spending is distinct from a spending
shortfall. Any actual shortfall overrides the rule-of-thumb positive status.

Monte Carlo now calls the same annual projection with sampled retirement returns.
It includes all retirement years, even before deferred withdrawals start, and
continues after an initial shortfall so ending-balance percentiles refer to the
actual plan-end year. A simulation succeeds only if every year funds spending and
taxes. Returns are floored at -100%. Explicit zero expected return and volatility
are preserved. Zero volatility gives the deterministic result at the same return.
The PDF also preserves a valid 0% success rate.

### Vanguard PAS versus Target Date Funds

The annual row now stores the balance after that year's fees and withdrawals.
Both remain calculated from the post-return, pre-fee balance, preserving the
existing timing assumption. All consumers of the rows (summary, comparison,
charts, PDF and CSV data) therefore use the same net ending balances.

Known answer: $100,000, zero return, one year, no withdrawals ends at $99,000 with
1% fees and $100,000 without fees. With 10% withdrawals and a 1% fee, the first
ending balance is $89,000; after three such years it is $70,496.90.

## Validation

New `dev/test-phase2-cash-flow.js`: 19 test groups, 1,719 numeric assertions plus
behavioral checks. Tests use hand-calculated answers, annual cash-conservation
identities, account-source reconciliation, shortfall/depletion cases, RMD excess,
IRMAA after depletion, zero-return/zero-volatility parity and terminal percentiles.
Local JavaScript VM/DOM stubs exercise actual renderer/chart/export code without a
browser or network. Export checks cover generated payloads/CSV, not PDF visual QA.

`roth-conv/engine.test.js` now tests account-source differences in an isolated
one-year case and compares requested rather than assumed-funded lifetime spending.

Passed:

- All 11 top-level `dev/test-*.php` suites.
- `roth-conv/engine.test.js`.
- `dev/test-managed-vs-vanguard-projection.js` (8 cases).
- `dev/test-phase1-browser-security.js` (64 local mocked checks; no live browser).
- New Phase 2 cash-flow suite.
- PHP syntax validation: all 224 non-vendor PHP files.
- Node syntax validation for all changed JavaScript and the new suite.
- `git diff --check` and manual review of the complete diff.

## Remaining scope boundaries

Existing simplified tax tables, Social Security tax assumptions in Retirement
Plan, RMD ages/divisors and spousal tables are unchanged. The audit's separate
accuracy findings in those areas remain for their authorized phase. State taxes,
early-distribution penalties, tax debt carryforwards and detailed brokerage basis
modeling in Retirement Plan are not introduced here. The cash accounting is
consistent with the stated inputs and existing tax assumptions; this phase does
not certify those separate tax-rule approximations.

No live-site verification was performed, as requested. PDF layout has not been
visually rendered in this phase. No unrelated calculators or shared financial
helpers were changed.
