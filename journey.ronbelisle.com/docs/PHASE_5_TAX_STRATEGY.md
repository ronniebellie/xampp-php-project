# Phase 5: Tax Strategy — Design Document

**Status:** Product philosophy and Version 1 direction **approved**; **not yet implemented**
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)
**Access intent:** Premium Journey phase (gating not implemented in this design phase)
**Depends on:** Saved Phase 3 base-case retirement income plan (required); Phase 4 resilience review (recommended context, not a hard gate)
**Leads to:** Phase 6 Survivor & Legacy
**Governing philosophy:** Phase 5 is a **guided planning step**, not a tax calculator
**Last updated:** 2026-07-25

> Do not implement Phase 5 from this document until an implementation plan is explicitly approved.
> Do not activate Premium gating, authentication, Stripe, or trial behavior as part of Phase 5 design work.

---

## 1. Purpose and central question

Phase 5 answers:

> **How do taxes affect the retirement plan I have built, and what should I consider next?**

| Phase | Question |
|---|---|
| Phase 3 | Do the pieces fit under the current assumptions? |
| Phase 4 | How resilient is that fit? |
| Phase 5 | How do taxes affect that plan, and what should I consider next? |
| Phase 6 | What happens if one spouse dies, and how do assets and decisions carry forward? |

### What Phase 5 is

- A Journey-native tax **awareness and priority** step applied to the exact Phase 3 plan
- A calm explanation of which parts of the plan may create taxable income
- A qualitative or broad tax-drag warning when gross withdrawals may need to exceed spendable need
- Awareness of heavy tax-deferred reliance, RMD attention, and Roth-planning opportunity
- **One** tax-planning priority carried forward — not a finished tax strategy

### What Phase 5 is not

- A tax-return preparation tool or Form 1040-style snapshot
- Legal or individualized tax advice
- A federal tax calculator with headline dollar tax bills
- A Roth-conversion optimizer or multi-year conversion scheduler
- A detailed Medicare IRMAA, capital-gains, QCD, or state-tax engine
- A substitute for a CPA or tax professional
- A rebuild of Phases 1–4 inputs

### Critical framing (repeat throughout)

> This is an educational tax-planning review. It is not a tax return, not tax advice, and not a finished tax strategy.

**Forbidden result language as verdicts:** tax efficient, optimized, best, safe, guaranteed, certain, approved, completed tax plan.

---

## 2. Locked product decisions

1. Phase 5 is a **guided planning step**, not a tax calculator.
2. Central question locked as above.
3. Single saved decision and companion explanation locked (§15).
4. Version 1 uses **Approach B (lean)** for tax-drag (§8).
5. Only **two** required new questions (§7).
6. No hero estimated federal tax bill; no Form 1040-style snapshot.
7. Results center on a **main-issue statement**, not multi-tier status grades.
8. At most **three** strategy choices; at most **one** selected priority.
9. Deeper tax machinery belongs in **separate tools**, not the core first-time path.
10. Premium gating remains deferred until account continuity and trial readiness later.

---

## 3. Relationship to Phases 3 and 4

### Phase 3 (required)

Phase 5 **must use** the saved Phase 3 retirement income plan and must not ask the user to re-enter:

- Retirement spending goal
- Social Security assumption
- Other dependable income
- Amount needed from retirement savings
- Retirement-savings balance
- Base-case assessment

If Phase 3 is missing, incomplete, unsaved, or invalid:

- Do not invent values
- Do not enable **Show my tax picture**
- Explain that Phase 5 needs the completed Phase 3 plan
- Provide **Return to Phase 3**

### Phase 4 (recommended, not a hard gate)

If a saved Phase 4 resilience review exists and still matches the current Phase 3 snapshot:

- Show a compact note: overall resilience label and any selected Phase 4 adjustment

Do **not** block Phase 5 if Phase 4 is missing.

### Change detection

If Phase 3 changed after a saved Phase 5 review:

> Your Phase 3 plan has changed since this tax review. Review your updated plan again to refresh this priority.

Do not silently overwrite a saved Phase 5 review until the user runs and saves again.

---

## 4. Version 1 scope and exclusions

### In Version 1 (core)

- Tax-character map of the current retirement income plan
- Two required category questions (savings mix + RMD timing)
- Qualitative / broad tax-drag warning when relevant
- Heavy tax-deferred reliance awareness
- Simple RMD attention signal (from the user’s answer)
- Roth-planning opportunity flag when relevant
- Main-issue statement + short “what this means”
- Up to three next directions; save one priority

### Optional Phase 5 explanation (allowed, keep short)

- One-sentence Traditional vs Roth withdrawal difference
- Simple Social Security “may be partly taxable” teaching note
- Secondary “Explore further” links **after save** to deeper tools

### Separate deeper tools (not core Journey v1)

- Detailed federal tax calculations / bracket snapshots
- Roth conversion optimization and multi-year schedules
- IRMAA analysis
- Capital-gains and tax-lot planning
- Qualified charitable distributions
- State tax comparisons
- Detailed RMD dollar projections

### Out of scope

- Tax-return preparation
- Exact capital-gains assumptions in the Journey phase
- Multi-year tax-law scenario grids
- Automatic rewriting of Phase 1–3 numbers
- Language implying a completed or optimized tax plan

---

## 5. Five-minute learning outcome

After about five minutes, the user should be able to explain in plain English:

1. Which parts of their retirement income may create taxable income
2. Why the amount withdrawn may need to be larger than the amount available for spending
3. Whether most of their retirement savings are tax-deferred
4. Whether future (or current) RMDs deserve attention
5. Whether Roth planning may be worth reviewing
6. What **one** tax-planning priority they are carrying forward

They should also understand:

- Gross plan ≠ spendable plan
- This review produces a **priority to revisit**, not a finished tax strategy
- Deeper tools or a tax professional may be appropriate next

They do **not** need to know exact federal tax dollars, exact taxable Social Security percentages, bracket tables, or provisional-income mechanics.

---

## 6. First-time user flow (seven steps)

1. **Opening** — purpose + educational disclaimer
2. **Plan recap** — Phase 3 summary; Phase 4 note if available
3. **Tax-character map** — which pieces may create taxable income
4. **Two questions** — savings mix + RMD timing
5. **Show my tax picture** — deliberate action; do not auto-run on load
6. **Results** — main issue, what it means, ≤3 directions; choose at most one
7. **Save priority** — decision statement + Phase 6 handoff (Phase 6 not ready yet)

Do not force an automatic redirect from Phase 4 into Phase 5.

---

## 7. Information inherited and minimum new inputs

### Inherited from prior phases

| Field | Source | Use |
|---|---|---|
| Monthly / annual spending goal | Phase 3 | Recap; spendable vs gross framing |
| Social Security assumption + temporary-SS flag | Phase 3 | Recap; SS teaching note |
| Other dependable income | Phase 3 | Recap; taxable-income character |
| Monthly / annual needed from savings | Phase 3 | Gross withdrawal demand context |
| Retirement-savings balance | Phase 3 | Context only (not split into accounts) |
| Base-case assessment | Phase 3 | Recap |
| Resilience overall + selected adjustment | Phase 4 | Optional context |

### Required new inputs (exactly two)

#### Question 1 — Savings mix

**Prompt:** How are most of your retirement savings held?

**Options:**

- Mostly tax-deferred, such as Traditional IRAs or 401(k)s
- Mostly Roth
- A mixture of account types
- Not sure

#### Question 2 — RMD timing

**Prompt:** Where are you with required minimum distributions?

**Options:**

- I am already taking them
- I expect them within about the next 5 years
- I expect them later
- Not sure

> Note: The product-approval message listing Question 2 options was truncated mid-line. The four options above match the approved Product Philosophy (awareness + timing band) and mirror Question 1’s structure. Confirm copy if a different fourth option was intended.

### Explicitly not required on the first-time path

- Filing status
- Exact age or birth year
- Exact Traditional / Roth / Taxable balances or percentages
- State of residence
- Self-reported tax bracket
- Annual Traditional vs Roth withdrawal amounts
- Detailed pension tax treatment worksheet

Optional “Add more detail” controls are **not** part of Version 1 core. If added later, they must not block the two-question path.

---

## 8. Approach B (lean) — tax-drag and calculation scope

### Locked approach

Version 1 uses **Approach B (lean)**:

- Explain tax character
- Identify likely tax pressure
- Give a **qualitative or broad** tax-drag warning when relevant
- **Do not** calculate or headline an estimated federal tax bill
- **Do not** present a Form 1040-style snapshot

### What “tax-drag warning” means

When the plan shows material withdrawals from savings **and** the user indicates mostly tax-deferred (or not sure with material withdrawals), results may say, in substance:

- Taxes may mean you need to withdraw somewhat more than your spending goal alone suggests, or
- Your plan relies heavily on tax-deferred withdrawals, so spendable cash may be lower than the gross Phase 3 picture

Use bands/wording such as “may,” “somewhat,” “meaningfully,” “under these assumptions.”
Do **not** show “Estimated federal tax: $X,XXX” as a hero metric.

### Separate deeper tools

Detailed federal estimates, brackets, deductions, and precise Social Security taxation worksheets belong outside the core Phase 5 first-time experience.

---

## 9. Social Security taxation treatment

**Core Version 1 level:** simple indicator + one teaching sentence.

Examples of intent:

- Some of your Social Security may be taxable depending on your other income and withdrawals.
- With your plan’s other income and withdrawals, Social Security is more / less likely to be partly taxable under common rules.

**Not in core v1:** detailed provisional-income calculation or a hero “taxable % of SS” number.

**Anti-confusion rule:**
If “up to 85%” is ever mentioned, the page must clarify that up to 85% of benefits may be **included in taxable income**, not taxed at an 85% tax rate.

---

## 10. Account-type treatment

Explain three broad types in plain language:

| Type | When taxes are generally paid | How withdrawals may affect taxable income |
|---|---|---|
| Tax-deferred (Traditional IRA / 401(k)) | Usually when money comes out | Often increases ordinary taxable income |
| Roth | Usually earlier (contributions/conversions); qualified withdrawals generally treated differently | Often does not increase taxable income the same way |
| Taxable brokerage / savings | Ongoing on interest, dividends, and realized gains; basis matters | Different from IRA ordinary-income treatment; **not modeled in detail in v1** |

Focus on **why the mix matters** for the user’s Phase 3 withdrawal need.
Avoid jargon such as “asset location” unless briefly explained.

Version 1 does **not** require exact balances by account type.

---

## 11. RMD scope

**Locked Version 1 role:** awareness + timing signal from the user’s answer.

- Core education: tax-deferred savings may eventually require withdrawals that affect taxable income.
- Timing band mirrors Question 2 (already / within ~5 years / later / not sure).
- **No** rough first-year RMD dollar estimate in core Journey v1.
- **No** full RMD projection.

Numerical RMD estimates belong in a separate RMD tool.
RMD content must not dominate the page.

---

## 12. Roth scope

**Locked Version 1 role:**

1. Short explanation of Traditional vs Roth withdrawal difference
2. Flag when Roth planning / conversions may be worth reviewing (especially mostly tax-deferred + RMD already or within ~5 years)
3. Optional secondary link to a dedicated Roth conversion tool **after save**
4. **No** conversion calculations, optimal amounts, or multi-year schedules

---

## 13. Result experience

After **Show my tax picture**, answer only:

1. What part of the plan is likely to create the most taxable income?
2. Is tax-deferred money doing most of the work?
3. Could taxes require somewhat larger gross withdrawals?
4. Are RMDs or Roth planning worth reviewing?
5. What one issue / priority should the user carry forward?

### Result elements

| Element | Version 1 |
|---|---|
| Main-issue statement | **Required — primary** |
| Short “what this means” (2–3 sentences) | **Required** |
| Qualitative / broad tax-drag warning | **When relevant** |
| Traditional-heavy awareness | **When relevant** |
| RMD attention note | **From Question 2** |
| Roth-planning opportunity flag | **When relevant** |
| Dollar federal tax estimate | **Not in core v1** |
| Multi-tier status grade | **Not used** |
| Deeper-tool links | **Secondary, after save** |

Always use language such as: estimated (only if later optional modes exist), approximate, based on the information entered, tax rules can change, state taxes and individual circumstances may differ.

---

## 14. Labels and main-issue statement

**Locked:** Do **not** use multi-tier overall labels such as “manageable / closer review” as the results hero.

Use a **single main-issue statement** instead, for example:

- In this review, the main issue is reliance on tax-deferred withdrawals.
- In this review, the main issue is that taxes may require larger gross withdrawals than your spending goal alone suggests.
- In this review, required minimum distributions deserve attention.
- In this review, Roth planning may be worth a closer look.
- In this review, no single tax issue stood out strongly; keep reviewing annually.

If two issues are similarly important, use tied wording rather than forcing a fake winner.

---

## 15. Single saved planning decision

### Decision statement (locked)

> This is the tax-planning priority I want to carry forward before I rely on my withdrawal plan.

### Companion explanation (locked)

> I’ve reviewed how taxes may affect my Phase 3 plan. I’m carrying forward one priority to revisit, not a finished tax strategy.

### How the priority is chosen

- If the user selects one of the ≤3 strategy choices, that selection **is** the saved priority.
- If the user selects none, confirm carrying forward the system main-issue statement as the priority (explicit confirmation — do not silently invent a different strategy).

Button intent: **Save My Tax-Planning Priority**

---

## 16. Strategy choices

Show **no more than three** relevant choices. User may select **at most one**.

### Candidate pool

1. Set aside part of withdrawals for taxes
2. Review whether some withdrawals should come from Roth savings
3. Explore Roth conversions before RMDs begin
4. Review future RMD exposure
5. Consult a tax professional before changing the plan
6. Keep the current approach and review annually

### Selection philosophy (coaching, not optimization)

- Mostly tax-deferred + material savings need → prefer 1 and/or 2
- RMD already or within ~5 years → prefer 4; add 3 if mostly tax-deferred
- Always allow a calm exit via 5 or 6 when space remains
- Never imply the choice is “best” or “optimal”
- Never automatically alter Phases 1–4

---

## 17. Saved Phase 5 record

Extend `rbJourneyProgressV1` with `records['tax-strategy']` when implemented later.

### Draft versioned shape

```text
records['tax-strategy'] = {
  phaseId: 'tax-strategy',
  schemaVersion: 1,
  saved: true,
  decisionStatement: 'This is the tax-planning priority I want to carry forward before I rely on my withdrawal plan.',
  companionExplanation: 'I’ve reviewed how taxes may affect my Phase 3 plan. I’m carrying forward one priority to revisit, not a finished tax strategy.',
  phase3Snapshot: { ...stable Phase 3 fields... },
  phase4Context: {
    overallCode,
    overallLabel,
    nextAdjustmentId,
    nextAdjustmentLabel
  } | null,
  assumptions: {
    savingsMix: 'mostly_tax_deferred' | 'mostly_roth' | 'mixed' | 'not_sure',
    rmdTiming: 'already' | 'within_about_5_years' | 'later' | 'not_sure'
  },
  result: {
    mainIssueId,
    mainIssueStatement,
    whatThisMeans,
    taxDragWarning: null | { level: 'somewhat' | 'meaningful', text },
    traditionalHeavy: boolean,
    rmdAttention: { code, text },
    rothReviewFlag: boolean,
    pressureMode: 'single' | 'tied' | 'none'
  },
  nextPriorityId,          // selected strategy or confirmed main issue
  nextPriorityLabel,
  educationalNonAdvice: true,
  notAFinishedTaxStrategy: true,
  createdAt,
  updatedAt,
  reviewedAt,
  source: {
    toolId: 'tax-strategy',
    url: '/phases/tax-strategy.php'
  },
  downstreamReady: true
}
```

### Architecture intent (implementation later)

| Item | Intent |
|---|---|
| Route | `/phases/tax-strategy.php` |
| Progress key | `tax-strategy` |
| Record key | `records['tax-strategy']` |

---

## 18. Premium value

Phase 5 belongs in Premium because it provides **continuity applied to the user’s plan**, not because it exposes more tax formulas:

- Applies tax insight to the **saved** Phase 3 (and Phase 4) plan
- Preserves a carried priority and unresolved issues
- Supports return visits when balances, Social Security, or tax rules change
- Links contextually to deeper specialized tools
- Carries tax context into Phase 6 Survivor & Legacy
- Avoids rebuilding the retirement plan to revisit tax questions

Do not design checkout or pricing in this document.

Premium activation remains blocked until later account continuity and Journey-specific 30-day trial readiness. See `FREE_TO_PREMIUM_TRANSITION.md`.

---

## 19. Phase 6 handoff

After save:

> You have reviewed how taxes may affect your household retirement income and chosen one priority to carry forward. Next, Phase 6 examines what happens to the plan if one spouse dies and how assets and decisions carry forward.

Boundaries:

- Phase 5 = income-tax awareness and priority for the living household plan
- Phase 6 = survivor income, beneficiaries, and legacy — not a continuation of Roth optimization

Phase 6 remains unavailable until designed and approved. Provide **Return to My Journey**. Do not add an active Phase 6 button prematurely.

---

## 20. Tax-law dependencies and annual maintenance

| Category | Examples |
|---|---|
| Stable educational concepts | Gross ≠ spendable; Traditional withdrawals often taxable; Roth often different; SS may be partly taxable; RMDs exist; mix and timing matter |
| Tax-year-specific figures | Not required for core Approach B lean path |
| Annual updates | Only if later optional estimate modes or deeper tools are added; core copy should avoid hard-coded thresholds |
| Confirm via official IRS sources before any future calculation mode | SS benefits taxation method; RMD ages/tables; brackets/deductions — **not used in core v1 hero results** |

Core Version 1 should minimize annual maintenance by avoiding headline federal tax computation.

---

## 21. Journey-vs-tool boundary (summary)

| Belongs in Phase 5 | Belongs in separate deeper tools |
|---|---|
| Tax-character map | Detailed federal tax math |
| Qualitative tax-drag warning | Bracket / deduction snapshots |
| Mix + RMD category questions | Exact account inventories |
| Main-issue + priority save | Roth conversion optimizer |
| ≤3 coaching directions | Multi-year conversion schedules |
| Optional post-save tool links | IRMAA, gains lots, QCDs, state tax, detailed RMDs |

---

## 22. Complexity red flags

Phase 5 is becoming too complex if implementation includes:

1. More than two required new inputs on the first path
2. Filing status, deductions, or brackets on the default path
3. Multiple tax years
4. Detailed bracket tables in results
5. Multi-year conversion tables
6. Long provisional-income explanations
7. Exact capital-gains assumptions
8. More than three strategy choices shown
9. Results that look like a tax return
10. Language implying optimization, advice, or a finished tax plan
11. Hero dollar “Estimated federal tax”
12. Forced deep-tool handoff to complete the phase
13. Automatic changes to Phase 1–3 numbers
14. IRMAA / NIIT / state modules in the first-time flow

---

## 23. Opening copy (draft)

**Eyebrow:** Phase 5

**Heading:** Tax Strategy

**Opening:**

> You’ve built a retirement income plan and reviewed how sensitive it may be under stress. Now look at how taxes may affect that same plan—and what one priority you should carry forward.

> This is an educational tax-planning review. It is not a tax return, not tax advice, and not a finished tax strategy.

---

## 24. Text wireframe

```text
============================================================
PHASE 5 · Tax Strategy
============================================================

How do taxes affect the retirement plan you have built,
and what should you consider next?

This is an educational tax-planning review. It is not a tax
return, not tax advice, and not a finished tax strategy.

------------------------------------------------------------
Your retirement income plan
------------------------------------------------------------
Spending · Social Security · Other income · Savings need ·
Balance · Phase 3 assessment
[Phase 4 resilience + adjustment note if available]

------------------------------------------------------------
Which parts may create taxable income?
------------------------------------------------------------
Short map:
  Traditional withdrawals · Social Security · Other income ·
  Roth (usually different) · Taxable brokerage (light note)

------------------------------------------------------------
Two details for this review
------------------------------------------------------------
1. How are most of your retirement savings held?
   ( ) Mostly tax-deferred (Traditional IRA / 401(k))
   ( ) Mostly Roth
   ( ) A mixture of account types
   ( ) Not sure

2. Where are you with required minimum distributions?
   ( ) I am already taking them
   ( ) I expect them within about the next 5 years
   ( ) I expect them later
   ( ) Not sure

            [ Show my tax picture ]

------------------------------------------------------------
Your tax picture
------------------------------------------------------------
Main issue: ________________________________

What this means:
  2–3 plain sentences.

[If relevant] Tax-drag warning:
  Taxes may mean you need to withdraw somewhat more than
  your spending goal alone suggests.

[If relevant] RMD note / Roth review flag

------------------------------------------------------------
Choose one priority to carry forward (optional but encouraged)
------------------------------------------------------------
( ) … at most three relevant choices …
( ) Keep the current approach and review annually

Decision:
“This is the tax-planning priority I want to carry forward
before I rely on my withdrawal plan.”

I’ve reviewed how taxes may affect my Phase 3 plan. I’m
carrying forward one priority to revisit, not a finished
tax strategy.

            [ Save My Tax-Planning Priority ]

Phase 6 will examine survivor income and legacy decisions.
Phase 6 is not available yet.
[ Return to My Journey ]
============================================================
```

---

## 25. Accessibility and UX requirements (for later implementation)

- Match existing Journey visual patterns
- Mobile-friendly stacked sections
- Keyboard-accessible radios in fieldsets with legends
- Do not rely on color alone for meaning
- Move focus to the results heading after **Show my tax picture**
- `aria-live` polite summary for the main-issue statement
- Reduced-motion support
- No complex chart wall

---

## 26. Implementation sequencing (after a later implementation plan)

1. This design document remains the product source of truth
2. Implementation plan (files, copy finals, tests) — separate approval
3. Review-only page before public navigation open (same pattern as early Phase 4)
4. Public Journey open
5. Premium gating only after account continuity + 30-day trial readiness

**Do not start coding until an implementation plan is approved.**

---

## 27. Open items (non-blocking copy confirmation)

1. Confirm Question 2 option wording if the truncated approval message intended different labels.
2. Exact qualitative tax-drag warning phrases (somewhat vs meaningful) — finalize in implementation plan without introducing IRS threshold tables into the hero UI.
3. Whether post-save deeper-tool links point to existing ronbelisle.com tools or future Journey-native labs.

---

## 28. Success criteria

Phase 5 design is ready for a later implementation plan when:

1. The phase remains a guided planning step, not a tax calculator
2. The central question and saved decision match the locked wording
3. Only two required new questions appear on the first-time path
4. Approach B lean is respected (no hero federal tax bill)
5. SS / RMD / Roth scopes stay minimal and non-dominating
6. Results center on main issue + meaning + ≤3 directions
7. Premium value is continuity, not more formulas
8. Complexity red flags are treated as hard constraints

---

## 29. Related documents

- `PHASE_3_BUILD_YOUR_PLAN.md` — base-case income plan contract
- `PHASE_4_STRESS_TEST.md` — resilience review; Phase 5 handoff intent
- `FREE_TO_PREMIUM_TRANSITION.md` — Premium boundary and 30-day trial strategy (not implemented here)
