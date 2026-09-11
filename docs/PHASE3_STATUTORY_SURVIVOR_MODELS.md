# Phase 3 statutory and survivor models

Authoritative offline basis: IRS Publication 590-B (2025) extracts, SSA survivor extracts, and exact SSA 2021 period-life-table fixtures in the supplied Phase 3 reference package. No statutory value is interpolated or inferred.

## Supported scope

- Federal income tax: fixed 2026 ordinary brackets and base standard deductions. Other years are unsupported statutory years; projection screens may reuse 2026 only when clearly identified as an assumption.
- Survivor Gap: finite, level monthly survivor pension replacement for an explicit 1–40 year period, using an effective-annual-to-monthly discount conversion. It is not an insurance quote or mortality model.
- RMD tables: exact supplied Single Life Table I values for ages 60–120+ (120+ is 1.0), Uniform Lifetime Table III values for ages 73–120, and the supplied Table II owner-age-73/spouse-age-62/sole-beneficiary combination (27.2 divisor). Owner RMDs use age 73 for birth years 1951–1959 and age 75 for 1960 or later, with the prior-December-31 balance. Missing cells never fall back to another table.
- Longevity: exact supplied SSA 2021 period-life-table fixtures only (ages 0, 22, 50, 60, 62, 65, 67, 70, 73, 75, 80, 85, 89, 90, 95, 100, 105, 110, 113, and 119). Missing ages require a user override; there is no interpolation, extrapolation, clamping, or age-60 substitution.
- Social Security survivor: regular aged-survivor eligibility beginning at age 60, modeled at the supplied 71.5% factor and compared with (not added to) the survivor's own retirement benefit. If the worker dies before the selected retirement claim age, the nonzero benefit basis is the worker's modeled benefit at that selected age; no pre-death COLA is added. COLA begins with survivor receipt.
- Inherited IRA: the pre-required-beginning-date 10-year branch for a noneligible designated beneficiary, and for an eligible designated beneficiary affirmatively electing the 10-year rule. The account must be empty by the 10th-anniversary year's December 31 deadline. Level and deadline-year withdrawals are user-selected tax scenarios, not asserted statutory annual requirements.

## Deliberately unsupported

- RMD commencement cohorts born before 1951, Table III ages beyond 120, and every Table II combination except 73/62.
- SSA survivor claims beginning after age 60 (the package does not contain the exact reduction schedule or survivor-FRA table), disability claims at 50–59, child-care eligibility, remarriage and other eligibility details.
- Inherited-IRA post-required-beginning-date annual distribution mechanics, non-designated beneficiaries, spouse life-expectancy branches, minors, disabled/chronically ill beneficiaries, and any category not explicitly listed above.
- SSA longevity ages absent from the fixture file.

## Known answers

- Table II: $1,000,000 / 27.2 = $36,764.705882..., displayed as $36,764.71.
- SSA age 90 remaining life expectancy: male 3.90 years; female 4.65 years.
- Survivor Gap: $3,000 single-life / $2,700 joint-life, 100% continuation, 20 years, 0% discount = $648,000; monthly option cost = $300. At 50% continuation the capital is $324,000.

Version identifiers are exposed by the statutory modules so saved or tested results can identify their rule/data basis.
