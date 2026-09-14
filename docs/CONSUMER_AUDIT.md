# Consumer audit, September 2026

## Batch 1 — snapshot income timing

The independently reproduced age-67 primary / age-57 spouse scenario previously paid the spouse ten years before their age-67 claim, showing $36,000 household Social Security and a $350,000 target instead of $12,000 and $950,000. Spouse PIA also bypassed claiming adjustments. The snapshot now maps each spouse to their own age and birth cohort and uses `RBFinance.calculateMonthlyBenefit` for both own-record benefits. IRA beneficiary status no longer hides or discards the spouse age needed for this calculation.

The target and comparison balance use retirement-start amounts, not a later withdrawal date or retirement-year ending balance. Later other income has an explicit start age. The screen and report separate income available this year, at retirement, after later income begins, and bridge-period withdrawals/shortfalls. Future income cannot reduce earlier spending needs. Deep links preserve a real zero and use currently available income rather than future PIA; birth-date links preserve actual dates. Invalid reruns clear stale results.

The timing contract is deliberately annual: ages attained in the calendar year; retirement/claims at the start of their selected modeled age-year; twelve monthly payments per year. Claim ages and retirement horizons require whole years. Exact birthday/payment-month timing, partial retirement years, spousal top-ups, and survivor benefits are not modeled. Spouse ages are entered independently and imply birth cohort from the same planning year as the primary. Existing scenarios missing spouse age require it when projecting future spouse income. Other income defaults to retirement start for older saved scenarios; distinct income streams with different starts must not be combined. Pre-retirement RMD projections are rejected rather than omitted silently.

`dev/test-consumer-snapshot.js`: 291 independent numeric checks plus invalid-domain, serialization, zero-volatility Monte Carlo and deep-link cases. `dev/test-phase2-cash-flow.js`: cash conservation and actual UI/chart/CSV/PDF payload reconciliation retained; initial balance expectation now correctly uses the beginning-of-retirement balance. Full top-level Phase 1–7 and Journey regressions passed. PHP syntax: 243 files. Older nested DB tests require isolated schemas; legacy shell-JavaScript tests require their `load` harness (covered by the top-level legacy harness).

The actual snapshot PDF was generated without HTTP and rendered for inspection. Inputs, timing context, model version, nominal USD basis and generated date are retained. Table sections start on separate pages; shared report context now resets text contrast and avoids advisor-only wording.

No statutory data changed. Consumer presentation remains distinct. No browser, live-site, AI-provider or Stripe request was made during verification.
