# Phase 3 statutory and survivor models

Authoritative offline basis: IRS Publication 590-B (2025) extracts, SSA survivor extracts, and exact SSA 2021 period-life-table fixtures in the supplied Phase 3 reference package. No statutory value is interpolated or inferred.

## Supported scope

- Federal income tax: fixed 2026 ordinary brackets and base standard deductions. Other years are unsupported statutory years; projection screens may reuse 2026 only when clearly identified as an assumption.
- Survivor Gap: finite, level monthly survivor pension replacement for an explicit 1–40 year period, using an effective-annual-to-monthly discount conversion. It is not an insurance quote or mortality model.
- RMD tables: exact supplied Single Life Table I values for ages 60–120+ (120+ is 1.0), Uniform Lifetime Table III values for ages 73–120, and the supplied Table II owner-age-73/spouse-age-62/sole-beneficiary combination (27.2 divisor). Applicable ages are 70½ before July 1, 1949; 72 from July 1, 1949 through 1950; 73 for 1951–1958; and 75 for 1959 or later. Owner calculations use the prior-December-31 balance. Missing cells never fall back to another table.
- Longevity: the complete supplied SSA 2021 period-life table supports every integer age 0–119 exactly. Ages outside that range are unsupported; there is no interpolation, extrapolation, or substitution.
- Social Security survivor: regular aged-survivor claims from age 60 through survivor FRA use the supplied DOB/FRA bands and exact monthly POMS reduction fraction, reaching 100% at survivor FRA. The result is compared with, not added to, the survivor's own retirement benefit. If the worker dies before the selected retirement claim age, the nonzero benefit basis is the worker's modeled benefit at that selected age; no pre-death COLA is added.
- Inherited IRA: pre-RBD 10-year treatment for a noneligible designated beneficiary and an eligible designated beneficiary electing it has no required annual distribution before year 10. For a noneligible designated beneficiary after the owner's RBD, annual years 1–9 RMDs use the longer of the beneficiary's Table I expectancy or the owner's remaining expectancy, reduced by one annually, with full distribution by the end of year 10.

## Deliberately unsupported

- Table III ages below 73 or beyond 120 and every qualifying Table II combination except 73/62. Historical commencement ages are supported cohort determinations, not permission to invent missing Table III cells for historical calculations.
- SSA disabled claims at 50–59, child-care eligibility, remarriage, family maximum, dual-entitlement edge cases, railroad interaction, and other specialized eligibility details.
- Post-RBD inherited-IRA treatment for eligible designated beneficiaries, non-designated beneficiaries, trusts/estates, spouse-special branches, and any category the UI cannot reliably identify.
- SSA longevity ages outside 0–119 and noninteger ages.

## Blocker corrections and timing contract

- Survivor annual income is the sum of 12 monthly entitlement calculations. A claim at 61 years 6 months receives six payments in its age-61 row, not zero or twelve. Annual-average monthly chart/table figures, cumulative totals, discounted totals, comparisons, and exported yearly data use this same ledger. Charts retain every annual data point.
- Rows remain **age-year planning buckets**, labeled by the year that age is attained; they are not exact DOB-month calendar cash ledgers. Death occurs at the end of the selected age-year, with survivor entitlement no earlier than the following age-month. The exact supplied SSA monthly reduction uses the actual modeled entitlement age. COLA increases on modeled benefit anniversaries. Payment-processing lag and exact death dates are not modeled.
- Survivor-record summary amounts now include the claiming-age reduction and are labeled as amounts at entitlement, not as unreduced worker benefits paid immediately at death. No survivor amount is reported when the modeled survivor dies before claiming. Own-record/survivor comparisons remain maximum-of-two, never stacking.
- The deceased worker's selected-claim-age benefit remains a **planning basis assumption**, including when death precedes that selected claim age. It is not a determination of an actual SSA award or entitlement to unearned delayed credits. Specialized worker-record cases require separate review.
- Inherited classification requires a literal Boolean RBD confirmation. `true` means before RBD; `false` means **on or after** RBD. The form starts with an unknown placeholder and cannot infer the branch from an owner's age. Missing/malformed RBD status, unknown beneficiary branches, and missing Table I ages fail explicitly.
- Inherited distributions are modeled at the beginning of each distribution year; the undistributed remainder grows into the next year. Both branches fully distribute the remaining balance in year 10, and that amount is also shown as required. Annual minimums in years 1–9 apply only to the supported post-RBD branch. This is a withdrawal-timing assumption, not a statutory mandate to withdraw on January 1.
- Owner/heir ages cannot be replaced with form defaults; an entered 0% return stays 0%. A special joint-life owner case remains outside the inherited calculator's explicitly disclosed Table III owner-projection scope.
- RMD and survivor birth dates undergo Gregorian month/day/leap-year validation. Missing spouse age/status cannot select another RMD table. Roth's forward-only 2026 projection recognizes pre-1951 owners as already subject to RMDs without guessing the 1949 birth-date split, and no longer floors or clamps unsupported RMD ages.
- Longevity rejects null, undefined, blank, nonnumeric, noninteger, negative and over-119 ages, including through live/saved calculator adapters. Exact numeric integer ages and nonblank numeric integer strings 0–119 are accepted; unknown sex is unsupported. Remaining expectancy is the supplied period-table value; planning death age is rounded age plus expectancy, not a guaranteed lifespan or cohort forecast.
- Invalid survivor/inherited reruns clear prior displayed/exportable results. Legacy survivor saves missing full dates or survivor claiming ages require those inputs again rather than borrowing another scenario's values; retirement-plan saves and RMD URL loads also clear missing dates.

## Verification added for blocker corrections

`dev/test-phase3-statutory-survivor.js` covers five required survivor percentage/dollar fixtures (60, 60½, 61½, FRA minus one month, FRA), every claim month across five DOB/FRA bands, first/subsequent-year amounts, COLA/discount reconciliation, either spouse surviving, own-benefit maximum, no entitlement before death, and actual chart data. It also covers invalid dates/ages/statuses, all 120 longevity ages for both sexes, live/saved input adapters, inherited pre/post-RBD distribution ledgers, year-10 required liquidation, and zero-return preservation. `roth-conv/engine.test.js` covers invalid owner ages and no age-120 substitution. Existing 2026 base tax and Survivor Gap fixtures are retained.

## Known answers

- Table II: $1,000,000 / 27.2 = $36,764.705882..., displayed as $36,764.71.
- SSA age 90 remaining life expectancy: male 3.90 years; female 4.65 years.
- Survivor Gap: $3,000 single-life / $2,700 joint-life, 100% continuation, 20 years, 0% discount = $648,000; monthly option cost = $300. At 50% continuation the capital is $324,000.

Version identifiers are exposed by the statutory modules so saved or tested results can identify their rule/data basis.
