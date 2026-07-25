# Phase 4: Stress Test — Design Document

**Status:** Design direction approved; **not yet implemented**
**Calibration (provisional):** Phase 4 calibration complete — **Hybrid Round 2** is the approved provisional numeric/classification baseline for future implementation. Values remain provisional until Phase 4 implementation review. See `PHASE_4_CALIBRATION.md` and `dev/phase-4-calibration/`. This note does not change the approved product direction in this document.
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)
**Access intent:** First Premium Journey phase (gating not implemented in this design phase)
**Depends on:** Saved Phase 3 base-case retirement income plan
**Leads to:** Phase 5 Tax Strategy
**Last updated:** 2026-07-25

---

## 1. Purpose and central question

Phase 4 answers:

> **How well might my retirement plan hold up when real life is less favorable than the base case?**

| Phase | Question |
|---|---|
| Phase 3 | Do the pieces fit under the current assumptions? |
| Phase 4 | How resilient is that fit? |

### What Phase 4 is

- A Journey-native resilience review of the **exact** Phase 3 plan
- Three named, deterministic educational stresses
- Calm coaching toward at most one next adjustment
- A saved resilience review for later phases

### What Phase 4 is not

- A rebuild of spending, Social Security, or savings inputs
- A Monte Carlo-first probability dashboard (v1)
- Tax, Roth, RMD, or Medicare strategy (Phase 5+)
- A guarantee or prediction of markets

### Critical framing

> These tests are educational. They do not predict markets or guarantee outcomes.

**Forbidden result words:** safe, guaranteed, certain, failure, doomed, successful (as a definitive verdict).

---

## 2. Relationship to Phase 3

Phase 3 produces a base-case retirement income plan. Phase 4 **must use that saved plan** and must not ask the user to re-enter:

- Retirement spending
- Social Security
- Other dependable income
- Retirement-savings balance
- Annual withdrawal need
- Initial withdrawal rate

Opening coaching line:

> This is the plan you built in Phase 3. Now we’ll test how sensitive it may be to less favorable conditions.

If Phase 3 is missing or incomplete, return the user to Phase 3. Do not invent a plan.

---

## 3. Journey-native architecture

- Planned route intent: `/phases/stress-test.php`
- Progress key intent: `stress-test`
- Calculation may **reuse proven helpers** from existing tools where practical
- The Retirement Plan Builder and Plan Success / Monte Carlo tools must **not** be required external handoffs for completing Phase 4
- Optional deeper Monte Carlo may appear later as Premium depth — not in the first-time v1 experience

---

## 4. Approved three-scenario Version 1 scope

Exactly three core tests:

1. **Weaker long-term investment growth**
2. **A market decline early in retirement while withdrawals continue**
3. **A longer retirement**

### Explicitly not in Version 1

- Separate “10% higher spending” scenario
- Large one-time expenses
- Lower Social Security / other income cuts
- Complex inflation modeling as a fourth hero test

Spending changes can be explored by revising Phase 1. Phase 4 stays focused on resilience of the plan already built.

### Later enhancements (documented only)

Higher spending, large expenses, lower income, optional Monte Carlo, and other stresses may be considered after v1.

---

## 5. First-time visitor flow

1. Opening — purpose and calm framing
2. Concise Phase 3 plan recap
3. Explain the three tests (no settings form)
4. **Test My Plan** button (deliberate transition; **do not auto-run** on load)
5. Results — overall label, three scenario cards, what mattered most
6. Up to three adjustment directions (if sensitive); choose at most one
7. Save resilience-review decision
8. Phase 5 handoff explanation

---

## 6. Phase 3 data inherited

| Field | Use |
|---|---|
| Monthly / annual spending goal | Recap; context |
| Monthly Social Security assumption | Recap; already reflected in withdrawal need |
| Monthly other dependable income | Recap |
| Monthly / annual amount needed from retirement savings | Fixed withdrawal demand \(W\) for scenarios |
| Retirement-savings balance | Starting balance \(B\) |
| Implied initial withdrawal rate | Recap / secondary context |
| Base-case assessment | Recap |
| Temporary-SS flag | Honesty in provenance if present |

---

## 7. Required and optional inputs

### Required new inputs for a first-time visitor with complete Phase 3

**None.**

### Optional later / not first screen

- Custom horizon lengths
- Custom early-decline size
- Custom weaker-growth gap
- Notes

### Longevity / age rule (locked)

- Do **not** require age or birth year solely for Phase 4.
- If reliable age exists from an earlier phase, it **may** be used to express horizons as ages.
- If age is unavailable, define horizons in **years** (base period vs base + extension).
- Do **not** invent a user age or imply a specific life expectancy.

---

## 8. Test My Plan interaction

- Show the Phase 3 recap and explain the three tests first.
- Primary action: **Test My Plan**
- Clicking runs the three deterministic scenarios and reveals results.
- Do **not** auto-run on page load.

---

## 9. Scenario calculation concepts

### Shared v1 model (conceptual)

Work in **today’s dollars** for educational clarity:

- Annual withdrawal need \(W\) stays constant (Phase 3 annual savings need).
- Starting balance \(B\) = Phase 3 retirement-savings balance.
- Each year: withdraw \(W\) (or remaining balance if smaller), then apply that scenario’s growth rate to the remainder.
- No RMD engine, no tax engine, no COLA complexity in v1.

This is intentionally simpler than the full Plan Builder year-by-year engine (which includes inflation, COLA, RMDs, and taxes).

### Scenario 1 — Weaker long-term growth

- Project \(B\) over the **base horizon** with constant \(W\).
- Use a **lower** constant growth rate than the base growth assumption.
- Compare ending balance / depletion timing to a **base-growth reference path** using the same horizon and \(W\).

### Scenario 2 — Early market decline

- At the start (year 0 / before ongoing withdrawals, or as year-1 shock — exact ordering configurable): apply a one-time **percentage decline** to \(B\).
- Then continue withdrawals \(W\) over the base horizon with **base** growth afterward (not the weaker-growth rate), unless a later decision ties them together.
- Educate: early drop + continued withdrawals can shrink recovery room.

### Scenario 3 — Longer retirement

- Same \(B\), \(W\), and **base** growth as the base-growth reference.
- Extend the horizon by additional years.
- Compare whether savings still last and how much thinner the ending cushion is.

### Base-growth reference path

An internal reference (may be shown lightly) using base horizon + base growth + constant \(W\), used to classify how much worse each stress is.

---

## 10. Result presentation

1. Compact reminder of the Phase 3 plan
2. One overall resilience assessment
3. Separate result for each of the three stresses
4. Short explanation of which stress mattered most
5. Up to three relevant adjustment directions (when sensitive)
6. One saved resilience-review decision

Always include:

> These tests are educational. They do not predict markets or guarantee outcomes.

---

## 11. Overall and per-scenario labels

### Overall working labels (locked wording; thresholds proposed in §18)

- **Holds up reasonably well in these tests**
- **Sensitive to one or more risks**
- **Needs meaningful adjustment before relying on it**

### Per-scenario working impact labels (proposed)

- **Little change** relative to the base-growth reference
- **Noticeable strain**
- **Severe strain**

---

## 12. Adjustment coaching

When overall result is sensitive or needs adjustment, show **no more than three** relevant choices, prioritized by which stresses mattered most.

### Candidate pool

- Reduce planned spending
- Delay retirement or withdrawals
- Revisit the Social Security assumption
- Increase retirement savings
- Temporarily reduce spending after a market decline
- Keep the Phase 3 plan as-is and revisit it later

### Rules

- User may record **at most one** possible next adjustment (including “keep as-is”).
- Do **not** automatically rewrite Phases 1–3.
- Links to revise Phase 1/2/3 are optional follow-through, not silent overwrites.

---

## 13. Saved record structure

### Core decision (locked)

> I’ve reviewed how sensitive my Phase 3 plan is, and I’m carrying this resilience review forward.

Optional: one next adjustment the user may explore.

### Fields to save

| Field | Notes |
|---|---|
| Phase 3 assumption snapshot | Copy key Phase 3 fields at test time |
| Scenario parameters | Horizons, growth rates, decline %, today’s-dollars flag |
| Individual scenario results | Impact label + key metrics (ending balance, depleted year/index) |
| Overall resilience label | One of the three overall labels |
| Most important stress | Which scenario mattered most |
| Optional next adjustment | Single choice or none |
| Optional notes | User text |
| Completion timestamp | ISO time |
| Educational / non-guarantee status | Explicit flag |
| `phaseId` | `stress-test` |
| `schemaVersion` | Start at 1 |

---

## 14. Premium value and implementation boundary

### Premium value (product story)

Phase 4 is the first Premium Journey phase because it pressure-tests **the user’s own Phase 3 plan** for weaker growth, an early decline, and a longer retirement — then coaches a single next adjustment.

### Implementation boundary (locked for now)

Do **not** implement in this design phase:

- Premium gating
- Authentication
- Stripe / checkout
- 30-day trial
- Subscription-state behavior

Those ship only when:

1. Phase 4 itself is complete and usable
2. Account and plan-continuity architecture is ready
3. Journey-specific 30-day Stripe trial is verified

Phase 4 should first be **reviewable safely during development** without live Premium enforcement.

See also: `FREE_TO_PREMIUM_TRANSITION.md`.

---

## 15. Phase 5 handoff

**Phase 4:** How resilient is the plan?
**Phase 5:** How might taxes affect what the user keeps and how withdrawals are managed?

Closing copy intent:

> You’ve reviewed how sensitive your retirement income plan may be under less favorable conditions. Next, Phase 5 looks at how taxes may affect the same income and withdrawal plan.

Do **not** introduce Roth conversions, tax brackets, RMD calculations, or Medicare tax details into Phase 4.

---

## 16. Text wireframe

```text
============================================================
PHASE 4 · Stress Test
============================================================

This is the plan you built in Phase 3. Now we’ll test how
sensitive it may be to less favorable conditions.

These tests are educational. They do not predict markets
or guarantee outcomes.

------------------------------------------------------------
Your Phase 3 plan
------------------------------------------------------------
Spending goal ........................ $X / mo
Social Security + other income ....... $Y / mo
Needed from retirement savings ....... $W / mo ($annual / yr)
Retirement savings ................... $B
Initial withdrawal rate .............. R%
Base-case assessment ................. [Workable / Close / Difficult]

------------------------------------------------------------
What we’ll test
------------------------------------------------------------
1. Weaker long-term investment growth
2. A market decline early while withdrawals continue
3. A longer retirement

[ Test My Plan ]

------------------------------------------------------------
Your resilience picture
------------------------------------------------------------
Overall: [Holds up reasonably well / Sensitive /
Needs meaningful adjustment]

Most important in these tests: [scenario name]

Weaker growth ........ [Little change / Noticeable / Severe]
Early market decline . [Little change / Noticeable / Severe]
Longer retirement .... [Little change / Noticeable / Severe]

------------------------------------------------------------
If you want to improve resilience
------------------------------------------------------------
Choose at most one next direction (examples prioritized
by results):
( ) …
( ) …
( ) Keep the Phase 3 plan as-is and revisit it later

------------------------------------------------------------
Your Phase 4 decision
------------------------------------------------------------
I’ve reviewed how sensitive my Phase 3 plan is, and I’m
carrying this resilience review forward.

[ Save My Resilience Review ]

------------------------------------------------------------
What’s next
------------------------------------------------------------
Phase 5 examines how taxes may affect this same retirement
income and withdrawal plan.
============================================================
```

---

## 17. Existing reusable calculation logic

### Code inspected

| Location | Role |
|---|---|
| `retirement-plan/plan-engine.js` | Deterministic year-by-year projection; depletion age; nest-egg status bands |
| `retirement-plan/calculator.js` / `index.php` | Default inputs for returns, inflation, plan end age, withdrawal rate, volatility |
| `retirement-plan/monte-carlo-engine.js` | Random-return stress test; success rate; not for v1 first-time UX |
| `plan-success/calculator.js` / `index.php` | Standalone Monte Carlo defaults and education copy |
| `js/lib/finance-core.js` | Shared FRA / formatting helpers (limited direct Phase 4 need) |
| Phase 3 Journey record (`build-your-plan`) | Source plan constants for \(B\), \(W\), rate, assessment |

### Defaults found in existing tools

| Assumption | Retirement Plan Builder default | Plan Success default | Notes |
|---|---|---|---|
| Plan through age | **95** | User “years” slider | Builder is age-based |
| Retirement return | **5%** | Expected return tip **5–7%**; UI default **6%** | Inconsistent across tools |
| Pre-retirement return | **6%** | n/a | Accumulation phase |
| Inflation (spending) | **2.5%** | Withdrawal inflation **2.7%** | Slight inconsistency |
| SS COLA | **2.5%** | n/a | |
| On-track withdrawal rate | **4%** | n/a | Aligns with Phase 3 educational rate |
| MC volatility | **10%** | **12%** | Inconsistent |
| MC simulations | **1000** | **1000** | Aligned |
| Default illustrative ages | Birth year ≈ now−62; retire **67** | Years chosen by user | ≈ 28 years from 67→95 |

### What can be reused

- **Conceptual** spending-gap withdrawal idea: withdrawals fund the gap after dependable income (Phase 3 already computed \(W\)).
- **Depletion detection** pattern from `plan-engine.js` (balance hits ~0 before horizon).
- **Status banding spirit** from nest-egg ratios (±10% style thinking) — adapted, not copied wholesale.
- Later: `monte-carlo-engine.js` for optional deeper Premium analysis.
- Currency formatting / clamps from shared finance helpers.

### What should not be reused in Phase 4 v1 first-time UX

- Full RMD + federal tax path from Plan Builder
- Monte Carlo success % / percentile language on the first screen
- Required deep-link handoff to Plan Builder or Plan Success
- Plan Builder copy that uses “guaranteed income” as a comfort verdict in Phase 4 results
- Forcing age-based longevity when age is unknown

### Inconsistencies requiring awareness

1. Retirement return defaults differ (**5%** Builder vs **6%** Plan Success).
2. Inflation defaults differ (**2.5%** vs **2.7%**).
3. Volatility defaults differ (**10%** vs **12%**).
4. Builder projects **nominal** dollars with rising spending; Phase 4 v1 proposes **today’s dollars** for clarity — a deliberate simplification, not a silent copy of Builder math.

### Formulas needing additional validation before coding

- Order of operations for early decline (drop then withdraw vs withdraw then drop in year 1).
- Whether growth is applied before or after withdrawal each year (Builder applies withdrawal then growth on remainder).
- Exact classification thresholds in §18 after prototype review with real Phase 3 examples.

---

## 18. Proposed numeric defaults and thresholds (for approval — not locked)

All values below are **configurable design proposals**. Do not implement until approved.

### Horizon

| Parameter | Proposal | Source / rationale | Limitations |
|---|---|---|---|
| Base planning period | **30 years** | New Journey default when age unknown; roughly consistent with retiring near mid-60s and planning into the mid-90s (Builder often ends at 95) | Not personalized longevity |
| Longer-retirement extension | **+5 years** → **35 years** total | New proposal; material but not extreme | Arbitrary; revisit after prototypes |
| If reliable age exists | Base end ≈ min(age+30, 95) or plan-to-95; longer = +5 years of age | Uses Builder’s common **95** endpoint as a soft ceiling | Must not invent age if missing |

### Growth and decline

| Parameter | Proposal | Source / rationale | Limitations |
|---|---|---|---|
| Base investment-growth assumption | **2.5% per year in today’s dollars** | Approx. Builder **5%** retirement return minus **2.5%** inflation — keeps Phase 4 in real/today’s dollars | Not a forecast; portfolio mix ignored |
| Weaker-growth assumption | **0.5% per year in today’s dollars** | New educational gap (~2 pp below base real return); shows slower growth without implying zero forever | Arbitrary gap |
| Early-market-decline percentage | **−20%** once at the start | Common educational sequence illustration (not from a single hard-coded Builder constant) | One path only; not a distribution |
| Return after early decline | Resume **base** today’s-dollars growth (2.5%) | Isolates sequence pressure from the separate weaker-growth test | Real crashes can cluster |
| Withdrawal treatment | Constant Phase 3 annual need \(W\) each year; cannot withdraw more than remaining balance | Matches Phase 3 “amount needed from savings”; simpler than Builder RMD/tax path | Ignores flexible spending responses |
| Inflation / today’s-dollars | **Today’s dollars** (no rising nominal spending in v1) | Reduces jargon; Phase 3 amounts are already user-entered current dollars | Understates nominal illusion; COLA on SS ignored (already netted into \(W\)) |

### Year step rule (proposed)

Align with Builder’s retirement step spirit:

1. Start year with balance
2. Withdraw `min(W, balance)`
3. Apply annual growth to the remainder

For early decline: apply −20% to starting balance **before** the first withdrawal year begins (configurable if validation prefers mid-year shock).

### Per-scenario classification (proposed)

Compare each stress path to the **base-growth reference** (base horizon, base growth, same \(W\), same \(B\)).

Let:

- \(E_b\) = ending balance of base-growth reference (0 if depleted)
- \(E_s\) = ending balance of scenario
- \(D_b\), \(D_s\) = year index of depletion (null if never)

| Impact | Proposed rule |
|---|---|
| **Little change** | Scenario lasts the full scenario horizon, and \(E_s ≥ 0.70 × E_b\) when \(E_b > 0\); or both last with similarly small balances |
| **Noticeable strain** | Scenario lasts, but \(E_s < 0.70 × E_b\); **or** scenario depletes only in the final 20% of its horizon while base does not |
| **Severe strain** | Scenario depletes when base does not; **or** depletes at least **5 years earlier** than base; **or** cannot fund the first year’s withdrawal after the early drop |

If base itself depletes early, classify stresses relative to how much *worse* they are (earlier depletion / lower years funded), and bias overall label toward caution.

### Overall resilience aggregation (proposed)

| Overall label | Proposed aggregation |
|---|---|
| Holds up reasonably well in these tests | No **severe** impacts; at most **one** noticeable |
| Sensitive to one or more risks | Exactly **one severe**, or **two+ noticeable** with no more than one severe |
| Needs meaningful adjustment before relying on it | **Two or more severe**; or Phase 3 assessment was **difficult** and **any severe** appears |

**Most important stress:** the severe scenario with the earliest depletion / worst ending-balance ratio; if none severe, the worst noticeable; if all little change, state that no single stress dominated.

### Why these numbers are appropriate for an educational stress test

- They create **visible separation** between base and stress without requiring Monte Carlo literacy.
- They stay near existing site defaults (5% / 2.5% inflation / plan-to-95 / 4% withdrawal culture).
- They avoid false precision from success percentages on the first screen.

### Explicit limitations

- Not personalized capital-market assumptions
- Not tax-aware
- Not a substitute for a full Plan Builder projection
- Thresholds (0.70 ending ratio, 5-year earlier depletion, −20% drop) need prototype calibration with real Phase 3 examples before lock-in

---

## 19. Acceptance criteria

1. Journey-native Phase 4 page loads the Phase 3 plan without re-entry of core fields.
2. Incomplete Phase 3 redirects or blocks with a clear path back.
3. Exactly three v1 stresses; no +10% spending hero scenario.
4. No new required inputs for complete Phase 3 users.
5. **Test My Plan** required; no auto-run on load.
6. Results show overall label, three scenario results, most-important stress, disclaimer.
7. Forbidden words absent from result copy.
8. ≤3 adjustment options; ≤1 recorded next adjustment; no silent Phase 1–3 rewrites.
9. Saved record matches §13.
10. Phase 5 handoff copy present; no tax engine in Phase 4.
11. No Premium/Stripe/auth gating required for internal review builds.
12. No required handoff to Plan Builder / Plan Success.
13. Numeric defaults loaded from configuration once approved — not scattered magic numbers without documentation.

---

## 20. Remaining blockers before implementation

1. **Approve or revise §18 numeric defaults** (horizons, growth rates, −20% drop, classification thresholds).
2. Confirm early-decline ordering (before first withdrawal vs during year 1).
3. Confirm age-available vs years-only display copy.
4. Prototype calibration against several real Phase 3 plans (workable / close / difficult).
5. Decide development preview access (ungated) vs any temporary staff-only route.
6. Phase 5 not required to start Phase 4 coding, but Premium trial still blocked until Phase 4 is usable (see Free→Premium doc).
7. Account/plan continuity and Stripe 30-day trial remain separate launch blockers for Premium activation — not for beginning Phase 4 UI development.

---

## Document history

| Date | Change |
|---|---|
| 2026-07-24 | Official design document created from approved Phase 4 direction; deterministic three-scenario v1; proposed numeric defaults pending approval |
| 2026-07-25 | Status note only: calibration complete — Hybrid Round 2 provisional baseline for future implementation; product direction unchanged; §18 numbers still provisional |
