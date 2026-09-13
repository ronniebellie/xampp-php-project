# Phase 4: remaining numerical defects

Baseline: `ac49f0838bfbae228e16a006b6ce6e8808721922` on `main`.
Status: implementation and offline regression review complete; awaiting approval
to create a checkpoint. No commit, push, deployment, live-site/browser access,
or Phase 5 work is part of this change. Phase 3 statutory data and scope remain
unchanged.

## Corrections and model conventions

| Area | Corrected method |
| --- | --- |
| Pension versus lump sum | Discount each annual end-of-year pension payment to the current-age valuation date; compare with the lump sum available on that same date. The horizon is plan-to age minus current age. Nominal payments are separate. No comparison with a compounded, unspent lump sum and no promise of permanent superiority after crossover. |
| Social Security claiming | Discount every strategy to age 62, using exponent `age - 62 + 1`. Each modeled age-year pays at year end, including the selected final age. Nominal monthly amounts and existing claiming factors are preserved. Summaries, charts, tables and exports carry the same cumulative PV. |
| Savings/annuity/required payment | Nominal annual rate divided by 12 for both existing principal and monthly contributions. Ordinary end-month payments are the default; the annuity tab also supports beginning-month payments. The same timing feeds the summary and ledger/charts. Zero-rate factor is exactly the number of payments. |
| PHP PV/FV and related TVM | Validate positive compounding bases and finite results; use explicit zero branches and stable logarithm/exponential formulas. Annuities require whole payment periods. Loan-payment and growing-annuity domain checks are included. |
| Number of periods | Reject a target moving opposite the entered growth rate, rather than returning a negative duration. An equal starting/target amount returns zero time; a different target at zero rate has no solution. |
| IRR | Scale cash flows, bracket/bisect roots with relative residual checks, remove leading/trailing zero periods, distinguish one-sign no-IRR and all-zero indeterminate cases, and report multiple roots/ambiguity instead of a guessed bound. |
| Student loans | Bounded iteration; a single fixed-payment loan whose payment is no greater than interest is immediately reported non-amortizing. Other unresolved paths are identified as beyond the modeled horizon. |
| Debt payoff | Keep the original aggregate minimum-plus-extra household budget. Released minimums and partial-payoff remainders roll into other debts in the same month. A 720-month cutoff is not labeled mathematical impossibility. |
| Debt versus saving | Both strategies spend the same external budget each month. All unused debt payments flow into savings in both strategies, including the partial payoff month and subsequent months. Interest precedes end-month payment/deposit. |
| Safe withdrawal rate | Test zero before searching; never initialize a successful rate to an untested 0.5%. Explicitly report no successful tested positive rate. A successful 15% result is the imposed ceiling, not an established maximum. |
| Retirement timeline | Parse actual local calendar dates rather than UTC date strings; retain dates in birthdays, monthly milestones, and checklist storage keys. Impossible calendar dates are rejected. |
| 401(k)/IRA on track | Preserve 0% instead of replacing it with 6%; use the exact zero-return contribution factor and finite bounded projections. A zero withdrawal rate cannot silently become 4% when deriving an income target. |

## Additional same-class defects found in final review

- January 1 claiming-analyzer birth dates could shift to the previous year in
  US time zones. Shared form/saved-scenario date parsing now validates calendar
  dates without that UTC conversion.
- Claiming scenarios entered out of age order could omit early chart/table/PDF
  rows. The displayed/exported age coverage now includes all strategies.
- A planning horizon before a strategy starts now gives zero benefits rather
  than an empty-array failure. Invalid rate bases fail explicitly.
- Zero PIA no longer produces NaN percentage comparisons; saved comparison
  calculations no longer replace a zero PIA with $3,000.
- Tiny cash values could be mistaken for a zero implied interest rate. The
  PHP interest-rate calculation uses exact equality and stable log differences.
- Small residual NPVs at extreme rates/leading zero cash flows could appear
  to be IRR roots. Residuals are now relative to discounted absolute cash flow;
  unsupported floating-point scaling is rejected.
- Growing-annuity near-equal rates use a stable relative-rate expression,
  checked against independently accumulated payment streams.
- Existing principal now appears in all target-savings contribution rows,
  and its interest uses monthly compounding. Already-funded targets require
  zero additional savings and report actual modeled interest.
- Zero-principal growth percentages/multiples show N/A instead of NaN/Infinity.
- Saved annuities preserve explicit timing; legacy records use ordinary timing.
- Single-amount fractional years are rejected rather than truncated by the
  form while the chart uses whole years. Monthly savings accept whole-month
  horizons, including harmless decimal representation error within 1e-8 month.
- Invalid/overflowing model results are rejected and stale results cleared in
  the affected calculation handlers. Bounded debt budgets and cumulative totals
  are checked for overflow.

## Known-answer evidence

| Fixture | Verified result |
| --- | --- |
| $30,000 annual pension, 25 years, 5%, end-year | $422,818.34 PV |
| $100 monthly, 12% nominal, 12 months, end-month | $1,268.250301 |
| Same, beginning-month | $1,280.932804 |
| $100 monthly, zero rate, 12 payments | $1,200 |
| $12,000 target, $2,000 existing, 10 months, zero rate | $1,000/month |
| Common-date SS equivalent flows | Age-62 $1,200 annual flow and age-70 $1,200 × 1.05^8 flow both PV to $1,200/1.05 at age 62 |
| $1,000 toward $2,000 at -5% | Explicit no solution |
| IRR [-100,110] / [-1,1] | 10% / 0% |
| IRR [100,100] | No IRR |
| IRR [-100,230,-132] | Multiple IRRs: 10%, 20% |
| $1,000 student loan, 12% APR, $10/month | Non-amortizing at month zero |
| $1,000 zero-rate debt, $1/month | $280 remains at month 720; outside horizon, not Never |
| $50 + $500 debts; $50 minimum each; no interest | Fixed $100 monthly budget; payoff in 6 months |
| Both saving policies, $1,000 debt, zero rates, $150 budget, 24 months | $2,600 invested under both policies |
| Date 2026-09-10 in America/Chicago | September 10, 2026; birthday and checklist dates preserved |
| $2,000 retirement balance + $1,000/year, 10 years, zero return | $12,000 |

## Regression verification

New suites: `dev/test-phase4-numerical.js` and
`dev/test-phase4-numerical.php`. Tests use independent ledgers/discounted sums,
actual JavaScript form handlers with an offline DOM/Chart harness, actual PHP
POST-controller code in isolated CLI processes, and actual CSV/PDF table
formatting sections. Fetch functions used in payload/load tests are mocks and
make no requests.

| Suite | Result |
| --- | --- |
| Phase 4 JavaScript | PASS: 852 numeric assertions plus domain, solver, termination, UI/export, saved-timing and date checks |
| Phase 4 time zones | PASS: America/Chicago, UTC, Asia/Tokyo, Pacific/Kiritimati |
| Phase 4 PHP numerical | PASS: 46 checks |
| Phase 1 HTTP guards | PASS: 18 checks against a temporary 127.0.0.1 server only; no application DB |
| Phase 1 browser-security harness | PASS: 64 checks; Node harness, no browser |
| Phase 1 containment | PASS: 105 checks |
| Phase 2 cash flow | PASS: all 1,719 numeric assertions plus behavioral checks |
| Phase 3 statutory/model | PASS: statutory, longevity, survivor monthly dollars, invalid inputs, inherited IRA, tax and Survivor Gap fixtures |
| Roth engine | PASS |
| Managed versus Vanguard projection | PASS: 8 checks |
| Advisor premium claim | PASS: 64 checks |
| CalcForAdvisors audit v2 | PASS: 39 checks |
| CalcForAdvisors entitlement | PASS: 74 checks |
| CalcForAdvisors migration | PASS: 67 checks |
| CalcForAdvisors portal | PASS: 126 checks |
| Calculator catalog | PASS: 217 checks |
| Managed versus Vanguard contract | PASS: 34 checks |
| Roth PDF generation | PASS |
| Existing auth/session suite | PASS: 69 checks |
| Production security baseline | PASS: 22 checks |
| Scenario-name compatibility | PASS: 181 checks |
| Affected syntax | PASS: 11 JavaScript files, 19 PHP files, 7 embedded JS blocks |
| Repository PHP syntax | PASS: 227 files, excluding vendor |
| Whitespace | PASS: git diff --check |

All top-level `dev/test-*.php` suites were executed. Complete tracked diff and
new source/tests were reviewed for zero-value defaults, division by zero,
NaN/Infinity, loop bounds, timing/valuation/budget consistency, date-only
parsing, misleading result labels and solver root validation.

## Assumptions and remaining limitations

- This remains educational modeling, not individualized financial advice.
- Pension payments are aggregated annually at year end, not monthly. Taxes,
  survivor/guarantee options, mortality probabilities, and legacy assets are
  not modeled by the PV comparison.
- The claiming analyzer remains an annual age-year model with its existing
  claiming-factor and post-claim COLA conventions. It is not a new statutory
  expansion or a calendar-month SSA entitlement engine.
- TVM supports positive periodic compounding bases. Annuities/debt-saving
  ledgers are bounded to 12,000 whole periods; pension/on-track/SWR helpers to
  their documented bounded horizons. Unsupported/nonfinite calculations fail
  rather than displaying NaN/Infinity. Displayed currency is rounded.
- Loan/debt results assume fixed rates/payments and no new borrowing. The
  payoff horizon is 720 months; less than half a cent is treated as paid off.
  Multiple unresolved debts are conservatively labeled outside the horizon,
  not individually proven mathematically non-amortizing.
- The IRR search is bounded to log(1+r) in [-13.8,13.8]. A scan cannot guarantee
  discovery of all roots or tangencies. Multiple cash-flow sign changes remain
  explicitly ambiguous unless multiple roots are found; detected roots do not
  establish that no other roots exist. Extremely disparate unsupported scales
  fail explicitly.
- SWR is the existing illustrative deterministic/path model. Success retains
  a positive ending balance. Its 0–15% search ceiling and baseline compression
  are not a mathematical or market guarantee.
- Existing placeholder exports in tools without real export implementations
  remain placeholders. Actual SS export payloads and formatting are covered;
  no production browser or authenticated website workflow was exercised.
- No new statutory table, rule, interpolation or fallback was introduced;
  Phase 3 supported and deliberately unsupported cases remain as approved.

## Files changed (31)

Shared math: `js/lib/numerical-core.js`, `includes/numerical_math.php`.

Calculator integrations:

- `401k-on-track/calculator.js`
- `debt-payoff/calculator.js`, `debt-payoff/index.php`
- `debt-vs-saving/calculator.js`, `debt-vs-saving/index.php`
- `future-value-app/calculator.js`, `future-value-app/index.php`
- `jp-business.ronbelisle.com/npv-irr/index.php`
- `pension-vs-lump-sum/calculator.js`, `pension-vs-lump-sum/index.php`
- `retirement-timeline/calculator.js`
- `social-security-claiming-analyzer/calculator.js`, `social-security-claiming-analyzer/index.php`
- `student-loan-payoff/calculator.js`, `student-loan-payoff/index.php`
- `swr-fee-impact/calculator.js`
- `time-value-of-money/future-value-annuity/index.php`
- `time-value-of-money/growing-annuity/index.php`
- `time-value-of-money/interest-rate/index.php`
- `time-value-of-money/loan-payment/index.php`
- `time-value-of-money/number-of-periods/index.php`
- `time-value-of-money/present-value-annuity/index.php`
- `time-value-of-money/present-value/index.php`

Export consistency: `api/export_ss_csv.php`, `api/generate_ss_pdf.php`,
`api/generate_ss_summary_pdf.php`.

Tests/documentation: `dev/test-phase4-numerical.js`,
`dev/test-phase4-numerical.php`, `docs/PHASE4_NUMERICAL_MODELS.md`.

Git: 26 modified tracked files and 5 new files, all unstaged Phase 4 changes.
HEAD remains the approved Phase 3 commit. No unrelated local files are included.

Recommendation: **GO for a Phase 4 Git checkpoint within this documented
model scope**, subject to user approval. This does not authorize deployment.
