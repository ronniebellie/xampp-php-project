# Phase 3: Build Your Plan — Design Document

**Status:** First-time UX approved; v1 implementation in progress  
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)  
**Depends on:** Phase 1 Spending & Goals, Phase 2 Social Security  
**Leads to:** Phase 4 Stress Test (not yet available)  
**Last updated:** 2026-07-24

---

## 1. Purpose and central planning question

Phase 3 answers:

> **Can my retirement savings support the retirement lifestyle I’ve planned?**

It assembles a **base-case retirement income plan** from what Phases 1 and 2 already decided, plus one new fact: how much the household has saved for retirement.

### What Phase 3 is

- A native Journey coaching step (not a required handoff to another calculator)
- A clear income picture: spending, dependable income, savings need
- An initial, educational indication of how demanding planned withdrawals may be
- A single saved planning decision to carry forward

### What Phase 3 is not

- A Monte Carlo or stress test (Phase 4)
- A tax, Medicare, fee, or asset-allocation optimizer
- A guarantee that the plan will succeed
- Individualized financial advice

### Critical framing (repeat throughout)

> **This is a base-case planning snapshot, not a stress test or guarantee.**

Avoid these words in Phase 3 results: **safe**, **guaranteed**, **approved**, **successful**.

---

## 2. Relationship to Phases 1 and 2

Phase 1 established **how much** the household expects to spend and what **other dependable income** (pensions, annuities, rentals, etc.) already exists.

Phase 2 established the **Social Security planning assumption** (claiming age to test, already receiving, or not ready).

Phase 3 asks what remains for **retirement savings** to provide, and whether that initial demand looks workable, close, or difficult under current assumptions.

### Story the page must communicate

1. Here is what I plan to spend.  
2. Here is the dependable income I expect.  
3. Here is what my retirement savings need to provide.  
4. Here is whether that looks reasonable under the current assumptions.  
5. Phase 4 will test how resilient that plan is.

---

## 3. Approved first-time page flow

Journey-native page (planned path: `/phases/build-your-plan.php`).

1. **Opening** — Phase 3 purpose in one short lede.  
2. **Recap** — Dynamic coaching bridge from Phases 1 and 2, plus the four key monthly amounts.  
3. **One new question** — Retirement-savings balance.  
4. **Income picture** — Spending, dependable income, monthly/annual savings need.  
5. **Base-case assessment** — Workable / close / difficult + disclaimer.  
6. **Secondary context** — Implied initial withdrawal rate (not the headline).  
7. **Single decision** — Save the retirement income plan to carry forward.  
8. **Phase 4 handoff** — Explain stress testing; safe return to Journey (no fake Continue).

The page must feel like a continuation of the Journey, not a standalone calculator or a long financial form.

---

## 4. Exact information carried forward

### From Phase 1

| Field | Journey source (intent) | Role |
|---|---|---|
| Expected monthly retirement spending | `monthlyRetirementSpendingTarget` | Spending goal |
| Other dependable monthly income | `monthlyOtherRegularRetirementIncome` | Non-SS dependable income |

### From Phase 2

| Field | Journey source (intent) | Role |
|---|---|---|
| Monthly Social Security assumption | `estimatedMonthlyBenefit` or `currentMonthlyBenefit` | SS income in the plan |
| Decision status / claim age (if available) | `decisionStatus`, `claimAge` | Context only for v1 display/save provenance |

### Derived on the Phase 3 page

```text
monthlyNeededFromSavings =
  max(0, monthlySpending − monthlySocialSecurity − monthlyOtherDependableIncome)

annualNeededFromSavings = monthlyNeededFromSavings × 12
```

### Social Security amount rule (locked)

Use the monthly Social Security amount saved in Phase 2 **exactly as entered**.

Do **not** adjust for:

- Medicare premiums  
- income taxes  
- withholding  

Concise user wording:

> Phase 3 uses your current saved Social Security planning assumption before taxes or other deductions, unless you entered a different amount in Phase 2.

---

## 5. Required and deferred inputs

### Required new input (first version)

| Input | Notes |
|---|---|
| **How much have you saved for retirement?** | One combined **household** retirement-savings balance |

Helper intent:

> Include retirement accounts and other savings you intend to use to support retirement. An approximate total is fine.

Use plain language **retirement savings**. Avoid leading with portfolio, investments, or investable assets.

### Not required on the first-time Phase 3 experience (locked)

- Birth year  
- Current age  
- Plan end age  
- Withdrawal start age  
- Expected investment return  
- Inflation  
- Advisory or product fees  
- Asset allocation  
- Tax assumptions  

These may be reconsidered later as optional details or in Phase 4.

### Opening coaching copy (dynamic)

> In Phase 1, you estimated spending about **$X** per month.  
> In Phase 2, you assumed about **$Y** per month from Social Security.  
> You also expect about **$Z** per month from pensions, annuities, or rental income.  
> That leaves about **$W** per month for your retirement savings to provide.

---

## 6. Initial withdrawal-rate assessment method

### Formula

```text
impliedInitialWithdrawalRate =
  annualNeededFromSavings ÷ retirementSavingsBalance
```

(When balance is greater than zero.)

This is an **educational rule-of-thumb check** only. It does **not** prove the plan is sustainable.

### Special cases

| Condition | Behavior |
|---|---|
| `annualNeededFromSavings = 0` | Assessment: workable. Dependable income covers the spending goal on these assumptions. Implied rate = 0%. |
| `retirementSavingsBalance ≤ 0` and need &gt; 0 | Assessment: difficult. Do not divide by zero. |
| Incomplete income picture | Do **not** show workable / close / difficult (see §8). |

### Labels (locked wording; thresholds not finalized)

- **Looks workable on these assumptions**  
- **Looks close and may need adjustment**  
- **Looks difficult on these assumptions**  

### Educational vs advice (locked)

Present the result as:

> An initial indication of how demanding the planned withdrawals may be under your current assumptions.

Do not present it as advice, approval, or a prediction of success.

---

## 7. Result presentation

### Main result (dominant)

1. Monthly retirement spending goal  
2. Monthly dependable income (Social Security + other)  
3. Monthly amount needed from retirement savings  
4. Annual amount needed from retirement savings  
5. Plain-language assessment label  
6. Disclaimer: base-case snapshot, not a stress test or guarantee  

### Secondary context (not dominant)

Show the implied withdrawal rate as explanatory context, for example:

> Your plan would initially require withdrawals equal to about **X%** of your retirement savings each year.

This must not replace the plain-language assessment or become the hero metric.

### What must not appear on the first screen

- Return / inflation / fee inputs  
- Asset allocation  
- Tax assumptions  
- Monte Carlo probabilities  
- Complex charts  
- Multiple withdrawal strategies  
- Long data-entry forms  

---

## 8. Incomplete-data behavior

### Phase 1 incomplete

- Explain that Phase 3 needs a retirement spending target.  
- Direct the user back to Phase 1.  
- Do **not** fabricate a spending amount.  
- Do **not** offer the base-case assessment.

### Phase 2 incomplete or Social Security still unknown

- Allow the user to view Phase 3.  
- Explain that the income picture is incomplete.  
- Offer a choice:  
  - return to Phase 2, or  
  - enter a **clearly labeled temporary** monthly Social Security estimate  
- Do **not** show workable / close / difficult until the required income picture is complete.  
- A temporary estimate must be labeled temporary and must **not** silently overwrite the Phase 2 planning record.

### Assessment gate (locked)

Show the workable / close / difficult assessment only when:

1. Phase 1 spending target is available, and  
2. A Social Security monthly amount is available (saved Phase 2 assumption **or** explicit temporary estimate), and  
3. Retirement-savings balance has been entered (for rate-based assessment when need &gt; 0).

If dependable income already covers spending (`need = 0`), assessment may show workable without emphasizing savings balance, but the savings question should still be asked so the saved plan includes a balance for Phase 4.

---

## 9. Saved record structure

### Single planning decision

> **This is the retirement income plan I want to carry forward.**

### Save action intent

`Save My Retirement Income Plan` / save Phase 3 progress.

### Fields to save

| Field | Notes |
|---|---|
| `monthlyRetirementSpendingGoal` | From Phase 1 |
| `annualRetirementSpendingGoal` | Monthly × 12 |
| `monthlySocialSecurityAssumption` | Phase 2 amount or temporary estimate |
| `socialSecuritySource` | `phase2` \| `temporary-estimate` |
| `temporarySocialSecurityEstimateUsed` | boolean |
| `monthlyOtherDependableIncome` | From Phase 1 |
| `monthlyNeededFromRetirementSavings` | Derived |
| `annualNeededFromRetirementSavings` | Derived |
| `retirementSavingsBalance` | User entry |
| `impliedInitialWithdrawalRate` | Decimal or percent; null if not computable |
| `baseCaseAssessment` | `workable` \| `close` \| `difficult` (or equivalent) |
| `assessmentStatus` | e.g. `complete` \| `incomplete-phase1` \| `incomplete-social-security` |
| `planningRecordStatus` | Base-case-only |
| `createdAt` / `updatedAt` | Timestamps |
| `phaseId` | `build-your-plan` |
| `schemaVersion` | Start at 1 |

### Explicit non-requirements

Do not require additional technical decisions (return, fees, taxes, allocation, withdrawal strategy) to complete Phase 3.

### Storage pattern (intent)

Follow Phase 1/2 Journey progress patterns under `rbJourneyProgressV1` (exact key names finalized at implementation).

---

## 10. Phase 4 handoff

Phase 4 (**Stress Test**) is not yet implemented.

### After a successful Phase 3 save

1. Confirm that the base-case income plan has been recorded.  
2. Explain that Phase 4 will stress-test it.  
3. Provide a safe return to the Journey homepage.

### Recommended copy

> You now have a working retirement income plan.  
> In Phase 4, you’ll see how it holds up if markets are weaker, inflation is higher, or retirement lasts longer than expected.

### Locked constraint

Do **not** expose a working **Continue to Phase 4** button until Phase 4 is genuinely available.

---

## 11. Likely reusable Retirement Plan Builder logic

Phase 3 is **Journey-native**. The Retirement Plan Builder may later be offered as an **optional deeper exploration** tool. It must not be a required handoff or interrupt the Journey flow.

Where practical, implementation may **reuse calculation helpers** rather than duplicate formulas. No code changes in this document phase.

### Likely reuse candidates

| Area | Location | Relevance to Phase 3 v1 |
|---|---|---|
| Currency formatting / clamps | `js/lib/finance-core.js` | Shared display helpers |
| Spending − SS − other = gap | `retirement-plan/plan-engine.js` (`targetNestEggAtRetirement` gap math) | Same income-stack idea; Phase 3 stops at gap + rate |
| Default 4% withdrawal rate input | `retirement-plan/index.php`, `calculator.js` | Educational default used site-wide |
| Nest-egg status bands via balance/target ratio | `plan-engine.js` `describeStatus` | Conceptual cousin; Phase 3 uses rate directly instead |
| Gap ÷ rate → portfolio needed | `ss-gap/calculator.js`, `required-vs-desired`, `401k-on-track` | Inverse of Phase 3’s rate = gap ÷ balance |

### What not to pull into Phase 3 v1

- Full year-by-year deterministic timeline  
- Monte Carlo engine  
- RMD logic  
- Tax estimates  
- Spouse dual-claim modeling beyond Phase 2’s saved SS amount  

Optional later: deep-link to `https://ronbelisle.com/retirement-plan/` for users who want more detail after saving the Journey record.

---

## 12. Numerical assessment thresholds still requiring approval

### Existing site logic (reference — not Phase 3 thresholds)

**Retirement Plan Builder** (`retirement-plan/plan-engine.js`):

- User-entered **withdrawal rate for on-track check** defaults to **4%**.  
- Target nest egg ≈ `annual spending gap ÷ withdrawalRate`.  
- Status from `compareBalance / targetNestEgg`:  
  - **≥ 1.10** → on track (with cushion)  
  - **≥ 0.90 and &lt; 1.10** → close  
  - **&lt; 0.90** → shortfall  
  - Gap ≤ 0 → covered by guaranteed income  

So a 4% target rate with ±10% nest-egg cushion roughly corresponds to implied withdrawal rates of about:

| Nest-egg ratio | Approx. implied withdrawal rate vs 4% target |
|---|---|
| ≥ 1.10 | ≤ ~3.6% |
| 1.00 | 4.0% |
| 0.90 | ~4.4% |
| &lt; 0.90 | &gt; ~4.4% |

**Other tools:**

- Many calculators default the rule-of-thumb rate to **4%** (`ss-gap`, `required-vs-desired`, `ss-early-exit`, `401k-on-track`, `trade-off-explorer`).  
- `ss-gap/calculator.js` maps illustrative “historical success” bands (3%–6%+). Those are **not** recommended as Phase 3 labels and must not be shown as success probabilities on the Phase 3 first screen.

### Locked Phase 3 v1 thresholds

Map implied initial withdrawal rate **W** = annual savings need ÷ balance:

| Assessment label | Locked W |
|---|---|
| Looks workable on these assumptions | **W ≤ 4.0%** |
| Looks close and may need adjustment | **4.0% &lt; W ≤ 5.0%** |
| Looks difficult on these assumptions | **W &gt; 5.0%** |

Special cases:

- Annual need = 0 → workable (no savings withdrawal currently needed)
- Balance = 0 and need &gt; 0 → difficult (do not divide by zero)

### Existing site logic (reference)

**Retirement Plan Builder** (`retirement-plan/plan-engine.js`):

- User-entered **withdrawal rate for on-track check** defaults to **4%**.  
- Target nest egg ≈ `annual spending gap ÷ withdrawalRate`.  
- Status from `compareBalance / targetNestEgg`:  
  - **≥ 1.10** → on track (with cushion)  
  - **≥ 0.90 and &lt; 1.10** → close  
  - **&lt; 0.90** → shortfall  
  - Gap ≤ 0 → covered by guaranteed income  

**Other tools:**

- Many calculators default the rule-of-thumb rate to **4%** (`ss-gap`, `required-vs-desired`, `ss-early-exit`, `401k-on-track`, `trade-off-explorer`).  
- `ss-gap/calculator.js` maps illustrative “historical success” bands (3%–6%+). Those are **not** shown as success probabilities on the Phase 3 first screen.

---

## 13. Text wireframe

```text
============================================================
PHASE 3 · Build Your Plan
============================================================

You’ve estimated what retirement may cost and chosen a
Social Security assumption. Now you’ll see how much your
retirement savings need to provide — and whether that
looks workable under your current assumptions.

------------------------------------------------------------
What your Journey already knows
------------------------------------------------------------

In Phase 1, you estimated spending about $X per month.
In Phase 2, you assumed about $Y per month from Social Security.
You also expect about $Z per month from pensions, annuities,
or rental income.
That leaves about $W per month for your retirement savings
to provide.

  Spending goal (monthly) ................ $X
  Social Security (monthly) .............. $Y
  Other dependable income (monthly) ...... $Z
  Needed from retirement savings ......... $W

  Note: Social Security uses your saved Phase 2 planning
  assumption before taxes or other deductions, unless you
  entered a different amount in Phase 2.

[If Phase 1 incomplete]
  Phase 3 needs your retirement spending target.
  [ Return to Phase 1 ]

[If Social Security incomplete]
  Your income picture is incomplete without a Social Security
  amount.
  [ Return to Phase 2 ]
  — or —
  Temporary monthly Social Security estimate (optional)
  [ $ ________ ]
  Clearly labeled temporary; does not replace Phase 2.

------------------------------------------------------------
One new question
------------------------------------------------------------

How much have you saved for retirement?
[ $ ______________ ]

Include retirement accounts and other savings you intend
to use to support retirement. An approximate total is fine.

------------------------------------------------------------
Your retirement income picture
------------------------------------------------------------

  Monthly spending goal .................. $X
  Monthly dependable income .............. $(Y+Z)
  Monthly from retirement savings ........ $W
  Annual from retirement savings ......... $(W×12)

  Assessment (only when income picture is complete):
  ★ Looks workable on these assumptions
    (or: Looks close... / Looks difficult...)

  Secondary context:
  Your plan would initially require withdrawals equal to
  about X% of your retirement savings each year.

  This is a base-case planning snapshot, not a stress test
  or guarantee.

------------------------------------------------------------
Your planning decision
------------------------------------------------------------

This is the retirement income plan I want to carry forward.

[ Save My Retirement Income Plan ]

------------------------------------------------------------
What’s next
------------------------------------------------------------

You now have a working retirement income plan.
In Phase 4, you’ll see how it holds up if markets are
weaker, inflation is higher, or retirement lasts longer
than expected.

[ Save Phase 3 Progress ]
[ Return to My Journey ]

(No Continue to Phase 4 until Phase 4 exists)
============================================================
```

---

## 14. Acceptance criteria for implementation

Implementation may begin only after thresholds (or an explicit TBD config approach) are approved. When building, acceptance means:

1. Native Journey Phase 3 page exists and is linked from Journey progress when intentionally enabled.  
2. First-time flow matches §3 and the wireframe; no required Plan Builder handoff.  
3. Phase 1/2 amounts display correctly; savings need math matches §4.  
4. Only required new input is household retirement-savings balance.  
5. SS amount used exactly as saved (or as labeled temporary estimate).  
6. Incomplete Phase 1 blocks fabricated spending and directs back to Phase 1.  
7. Incomplete Phase 2 allows viewing Phase 3, offers return or temporary SS estimate, and withholds assessment until complete.  
8. Temporary SS estimate does not silently overwrite Phase 2.  
9. Result shows income picture + locked labels + base-case disclaimer.  
10. Implied withdrawal rate appears only as secondary context.  
11. Forbidden words (safe / guaranteed / approved / successful) are absent from result copy.  
12. Single save records the fields in §9.  
13. No Continue to Phase 4 until Phase 4 is real; safe Journey return works.  
14. Free path remains useful without Premium.  
15. Validate PHP/JS/CSS and deploy only after explicit approval.

---

## Document history

| Date | Change |
|---|---|
| 2026-07-24 | Initial official design document from approved first-time UX specification and locked product decisions |
| 2026-07-24 | Locked withdrawal-rate thresholds at 4.0% / 5.0%; implementation approved |
