# Phase 6: Survivor Planning — Design Document

**Status:** Official design direction **approved**; **not yet implemented**  
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)  
**Phase name:** Survivor Planning  
**Access intent:** Premium Journey phase (gating not implemented in this design phase)  
**Depends on:** Saved Phase 3 base-case retirement income plan (required); Phase 4 and Phase 5 context when current (optional)  
**Concludes:** The initial Retirement Planning Journey  
**Leads to:** Ongoing Premium planning workspace (conceptual in this document)  
**Last updated:** 2026-07-25  

> Do not implement Phase 6 from this document until an implementation plan is explicitly approved.  
> Do not activate Premium gating, authentication, Stripe, or trial behavior as part of Phase 6 design work.  
> Do not use “Survivor & Legacy” as the public phase name.

---

## 1. Purpose and central question

Phase 6 answers:

> **How might our retirement income plan change if one spouse dies, and what survivor-planning priority should we carry forward?**

**Short page wording:**

> If one of us dies, what may change in our retirement plan—and what should we review next?

| Phase | Question |
|---|---|
| Phase 3 | Do the pieces fit under the current assumptions? |
| Phase 4 | How resilient is that fit? |
| Phase 5 | How do taxes affect the plan, and what should we consider next? |
| Phase 6 | If one of us dies, what may change—and what survivor-planning priority should we carry forward? |

### What Phase 6 is

- A guided **Survivor Planning** review of the existing Phase 3 household income plan  
- Educational and calm  
- Focused on what may change for a survivor’s income plan  
- Built around **one** survivor-planning priority to carry forward  
- The **final phase** of the initial Retirement Planning Journey  

### What Phase 6 is not

- An estate-planning application  
- A legal-document generator  
- A beneficiary audit  
- A survivor shortfall calculator  
- A life-insurance calculator  
- Legal or financial advice  
- A rebuild of Phases 1–5 inputs  

### Critical framing

> This is survivor planning for your income plan—not a finished estate plan.

**Forbidden result language as verdicts:** safe, unprotected, guaranteed, certain, short by $X, runs out in year Y, finished estate plan, legacy plan (as the product outcome).

---

## 2. Product philosophy

Phase 6 follows the same Journey philosophy established in Phases 3–5:

- One planning decision at a time  
- Educational, calm, plain English  
- Continuity across phases  
- No false precision  
- No overwhelming detail  
- Guided planning, not a specialized calculator disguised as a Journey step  

### Version 1 philosophy (locked)

- Based on the existing Phase 3 household income plan  
- Exactly two required awareness questions  
- No hero dollar shortfall  
- No claim that the survivor is “safe” or “unprotected”  
- Precise survivor calculations belong in deeper specialized tools  
- Emotional close: prepared and accomplished, not frightened  

### Why not “Legacy”

The word “legacy” suggests wills, trusts, inheritances, legal documents, and estate planning. Those topics are intentionally outside this Journey phase. The public name is **Survivor Planning**.

---

## 3. Single saved planning decision (locked)

**Decision statement:**

> This is the survivor-planning priority I want to carry forward for our household plan.

**Companion explanation:**

> I’ve reviewed how our retirement income plan may change if one of us dies. I’m carrying forward one priority to revisit—not a finished estate plan.

### Language rules

- Use **survivor-planning priority**  
- Do not use “legacy priority”  
- Do not imply legal completion  
- “Estate plan” may appear only to clarify that this phase is **not** one  

**Primary action intent:** **Review Our Survivor Picture**  
**Save action intent:** **Save My Survivor-Planning Priority**

---

## 4. Inherited data contract intent

### Phase 3 (required)

A complete, saved Phase 3 retirement income plan is required. Do not invent missing values.

Consume / snapshot intent:

| Field | Use |
|---|---|
| Monthly / annual retirement spending goal | Recap; survivor spending teaching |
| Monthly Social Security assumption | Recap; SS change teaching |
| Monthly other dependable income | Recap; continuity teaching |
| Monthly / annual needed from retirement savings | Recap; withdrawal revisit teaching |
| Retirement-savings balance | Recap |
| Implied initial withdrawal rate | Recap (secondary) |
| Base-case assessment | Recap |
| Temporary Social Security estimate flag | Honesty note if present |
| Social Security source / provenance | Snapshot |
| Assessment / saved / completeness status | Gate |
| Schema version and timestamps | Change detection |

If Phase 3 is missing, incomplete, unsaved, or invalid:

- Do not invent values  
- Do not enable **Review Our Survivor Picture**  
- Explain that Phase 6 uses the retirement income plan created in Phase 3  
- Provide a clear Return to Phase 3 action  

### Phase 1 (inherited via Phase 3 / provenance)

- Retirement spending goal  
- Other dependable income  

### Phase 2 (when available)

- Social Security planning assumption  
- Available household context  

Whether Phase 2 holds enough household context for a personalized survivor Social Security note without another question remains an open item (§18).

### Phase 4 (optional, when current)

If a saved Phase 4 review exists and still matches the current Phase 3 snapshot, Phase 6 may show compact context:

- Overall resilience result  
- Dominant pressure or tied pressures  
- Selected adjustment, if any  

If stale or missing: omit. Do **not** block Phase 6.

### Phase 5 (optional, when current)

If a saved Phase 5 review exists and remains relevant to current Phase 3:

- Saved tax-planning priority  
- Relevant category answers if helpful for compact context  

If stale or missing: omit. Do **not** block Phase 6.

---

## 5. Exact two-question input model (locked)

Exactly two required questions. No required ages, health information, insurance amounts, account inventories, legal documents, or detailed Social Security inputs.

### Question 1 — Who would receive accounts and assets

**Prompt:**

> Have you reviewed who would receive your retirement accounts and other financial assets?

**Choices (visitor labels → stored-value intent):**

| Label | Stored value intent |
|---|---|
| Yes, recently | `yes_recently` |
| Yes, but the information may need another review | `yes_needs_review` |
| Not yet | `not_yet` |
| Not sure | `not_sure` |

**Purpose:** awareness, not verification.  
The Journey must not imply it audited beneficiary designations or ownership records.

### Question 2 — Survivor income readiness

**Prompt:**

> If one of you died, how prepared do you feel to review the survivor’s income plan?

**Choices:**

| Label | Stored value intent |
|---|---|
| We have already thought this through | `already_thought_through` |
| We have discussed it, but should review it again | `discussed_needs_review` |
| We have not reviewed it yet | `not_reviewed_yet` |
| Not sure | `not_sure` |

Preparedness wording is intentional. Avoid “concerned” / fear-driven framing.

### Interaction rules

- Both answers required before **Review Our Survivor Picture** runs  
- Do not auto-run on page load  
- Changing either answer after a review should mark results as needing refresh before save  
- Use accessible radio-card fieldsets consistent with earlier Journey phases  

---

## 6. First-time page flow

1. Phase 6 introduction + educational disclaimer  
2. Compact Phase 3 plan recap  
3. Optional Phase 4 / Phase 5 context when current  
4. Plain-language survivor-planning teaching map  
5. Two required questions  
6. **Review Our Survivor Picture**  
7. Main issue or genuine two-way tie + short explanation  
8. Up to three possible directions; user selects one priority  
9. Save survivor-planning priority  
10. Journey-completion message + completed-Journey recap  
11. Premium continuity concept (after save only)  
12. Safe Return to My Journey / revisit paths  

Phase 6 remains unavailable in public navigation until a later approved open step (same pattern as early Phases 4–5).

---

## 7. Survivor-planning teaching content

Short plain-language section. No worksheets, brackets, or shortfall math.

Cover:

1. **Social Security** — One Social Security benefit may end or change.  
2. **Other dependable income** — Some pensions, annuities, or other income may continue; some may not.  
3. **Spending** — One-person living costs often do not fall by half.  
4. **Retirement withdrawals** — Withdrawals from retirement savings may need another review.  
5. **Who receives accounts and assets** — Reviewing who would receive accounts and assets supports continuity, but the Journey does not verify beneficiary designations.  

**Honesty line (locked):**

> This is survivor planning for your income plan—not a finished estate plan.

---

## 8. Result structure

After **Review Our Survivor Picture**:

1. One main survivor-planning issue, **or** a genuine two-way tie  
2. A brief explanation of what the issue means  
3. No more than three possible directions  
4. One saved survivor-planning priority  

### Presentation rules

- Single issue: present naturally as one priority (heading + title + short meaning)  
- Two-way tie: present as **two separate priority items**, not one compressed non-parallel sentence  
- No dominant issue: use the `none_dominant` presentation  
- Educational planning issues — not warnings or verdicts  
- Do not force an issue unsupported by the answers or inherited information  

### Version 1 must not headline

- Precise survivor-income shortfall dollars  
- “Runs out in year Y”  
- “Safe” / “unprotected”  

---

## 9. Issue categories (working)

| ID | Visitor-facing statement intent |
|---|---|
| `survivor_income_review` | Survivor income may need a closer review. |
| `social_security_change` | Social Security changes may deserve attention. |
| `survivor_spending_look` | The survivor spending goal may need another look. |
| `beneficiary_review` | Who would receive accounts and assets may need review. |
| `none_dominant` | No single issue stands out strongly from these answers. An annual review may be enough for now. |

### Meaning intent (working)

| ID | What this means (intent) |
|---|---|
| `survivor_income_review` | Some household income may stop or change. Looking at what would continue for the person who remains is a useful next step. |
| `social_security_change` | Social Security for a survivor often differs from the household picture used in Phase 3. A closer look may help before relying on today’s assumption alone. |
| `survivor_spending_look` | Living costs for one person often do not fall by half. The Phase 1 / Phase 3 spending goal may need another look as a one-person plan. |
| `beneficiary_review` | Knowing who would receive retirement accounts and other financial assets supports continuity. This Journey does not verify those designations. |
| `none_dominant` | With these answers, nothing demands an urgent redesign. Revisit survivor planning as life and accounts change. |

Exact deterministic selection rules remain for the implementation-planning step (§18).

---

## 10. Priority options (working pool)

| Working ID | Label |
|---|---|
| `review-continuing-income` | Review what income would continue for the survivor |
| `revisit-one-person-spending` | Revisit the spending goal as a one-person household |
| `review-survivor-social-security` | Review how Social Security may change for the survivor |
| `review-who-receives-assets` | Review who would receive retirement accounts and other financial assets |
| `discuss-with-professional` | Discuss the plan with a financial or estate-planning professional |
| `keep-and-review-annually` | Keep the current approach and review it annually |

### Rules (locked)

- Show no more than three  
- User selects no more than one  
- Never label one as the best option  
- Always provide a calm completion path (**Keep the current approach and review it annually** should appear among the three whenever needed so completion never forces action; preferred: always include it)  
- Saving requires exactly one selected priority  
- Do not automatically alter Phases 1–5  

Exact issue → choice mapping remains for the implementation-planning step (§18).

---

## 11. Saved-record intent

When implemented later, extend Journey progress with a Phase 6 record (display name **Survivor Planning**).

Suggested shape:

```text
records['survivor-planning'] = {
  phaseId: 'survivor-planning',   // technical key TBD at implementation if nav key differs
  schemaVersion: 1,
  saved: true,
  decisionStatement: 'This is the survivor-planning priority I want to carry forward for our household plan.',
  companionExplanation: 'I’ve reviewed how our retirement income plan may change if one of us dies. I’m carrying forward one priority to revisit—not a finished estate plan.',
  phase3Snapshot: { ... },
  phase4Context: { ... } | null,
  phase5Context: { ... } | null,
  assumptions: {
    assetRecipientReview,     // yes_recently | yes_needs_review | not_yet | not_sure
    survivorIncomePreparedness // already_thought_through | discussed_needs_review | not_reviewed_yet | not_sure
  },
  result: {
    pressureMode,             // single | tied | none
    mainIssueIds,
    mainIssueStatement / display items,
    whatThisMeans,
    strategyChoicesShown,
  },
  nextPriorityId,
  nextPriorityLabel,
  educationalNonAdvice: true,
  notAFinishedEstatePlan: true,
  createdAt, updatedAt, lastReviewedAt,
  source: { toolId, name: 'Survivor Planning', url },
  downstreamReady: true
}
```

### Notes

- No survivor shortfall dollars in the saved result (none are computed in v1)  
- Stale Phase 3 after save: require refresh before relying on results; do not silent overwrite  
- Technical progress key vs current nav key `survivor-legacy`: resolve at implementation without renaming public nav in this design-only milestone  

---

## 12. Accessibility and mobile principles

- Keyboard-accessible radio cards  
- Fieldsets and legends  
- Clear validation if answers are missing  
- Focus moved to results heading after **Review Our Survivor Picture**  
- `aria-live` polite summary for main issue / priorities  
- No reliance on color alone  
- Mobile stacked sections  
- Reduced-motion support  
- Clear money formatting in Phase 3 recap  
- No unexplained jargon  
- Save disabled until one priority is selected  
- Tie presentation as separate items (parallel wording)  

---

## 13. Scope boundaries (locked)

Phase 6 does **not**:

- Draft wills  
- Draft trusts  
- Create powers of attorney  
- Create medical directives  
- Determine inheritance percentages  
- Provide legal advice  
- Audit beneficiaries  
- Calculate estate taxes  
- Calculate life-insurance needs  
- Become an estate-planning application  

Precise survivor Social Security engines, inherited-IRA modeling, and insurance need tools belong outside the core Journey path.

---

## 14. Journey-completion experience

Phase 6 concludes the initial Retirement Planning Journey.

**Core completion message (locked):**

> You now have an initial retirement plan. The next step is not starting over—it is keeping this plan current as your life changes.

**Completed Journey recap should include:**

- A retirement spending goal  
- A Social Security assumption  
- A retirement income plan  
- A resilience review  
- A tax-planning priority  
- A survivor-planning priority  

### Emotional tone (locked intent)

- Prepared  
- Steady  
- Accomplished  
- Aware of what may need future attention  
- Invited to keep the plan current  

**Avoid:** fear, urgency, mortality shock language, legal-document pressure.

---

## 15. Premium continuity (conceptual only)

Premium remains conceptual in this design document.

**Do not include:** pricing, trial length, checkout, account creation, authentication, or Stripe implementation.

After the Phase 6 priority is saved and the initial Journey is declared complete, explain Premium as the ongoing planning workspace where users can:

- Save and revisit their plan  
- Update assumptions after major life changes  
- Review survivor assumptions and beneficiary decisions  
- Compare alternatives  
- Keep their retirement plan current over time  

Must feel like **continuity of the plan already built**, not a sales pitch.  
Always pair with a respectful non-pressure path (e.g. Return to My Journey).

---

## 16. Architecture intent (implementation later)

| Item | Intent |
|---|---|
| Display title | Survivor Planning |
| Route intent | `/phases/survivor-planning.php` (or equivalent; finalize at implementation) |
| Progress display | Survivor Planning |
| Review-first pattern | Direct unlinked page + `noindex` before public open (same pattern as early Phases 4–5) |

Do not create or modify any public Phase 6 page from this document alone.

---

## 17. Text wireframe

```text
============================================================
PHASE 6 · Survivor Planning
============================================================

If one of us dies, what may change in our retirement plan—
and what should we review next?

This is survivor planning for your income plan—not a finished
estate plan. It is educational and is not legal or financial advice.

[If Phase 3 incomplete]
  Explain + Return to Phase 3
  Primary action disabled

------------------------------------------------------------
Your retirement income plan
------------------------------------------------------------
Spending · Social Security · Other income · Savings need ·
Balance · Phase 3 assessment
[Temporary SS note if applicable]

------------------------------------------------------------
Optional context
------------------------------------------------------------
[Phase 4 resilience note if current]
[Phase 5 tax-planning priority if current]

------------------------------------------------------------
What may change for a survivor
------------------------------------------------------------
Social Security may end or change
Some other income may continue; some may not
Spending often does not fall by half
Withdrawals may need another review
Who receives accounts/assets supports continuity
(Journey does not verify designations)

This is survivor planning for your income plan—not a finished
estate plan.

------------------------------------------------------------
Two details for this review
------------------------------------------------------------
1. Have you reviewed who would receive your retirement
   accounts and other financial assets?
   ( ) Yes, recently
   ( ) Yes, but the information may need another review
   ( ) Not yet
   ( ) Not sure

2. If one of you died, how prepared do you feel to review
   the survivor’s income plan?
   ( ) We have already thought this through
   ( ) We have discussed it, but should review it again
   ( ) We have not reviewed it yet
   ( ) Not sure

            [ Review Our Survivor Picture ]

------------------------------------------------------------
Your survivor picture
------------------------------------------------------------
Main survivor-planning priority
  [title]
  [short meaning]

— or, if tied —

Main survivor-planning priorities
  [issue A title + meaning]
  [issue B title + meaning]

------------------------------------------------------------
Choose one priority to carry forward
------------------------------------------------------------
( ) … at most three …
( ) Keep the current approach and review it annually

Decision:
“This is the survivor-planning priority I want to carry
forward for our household plan.”

I’ve reviewed how our retirement income plan may change if
one of us dies. I’m carrying forward one priority to
revisit—not a finished estate plan.

            [ Save My Survivor-Planning Priority ]

------------------------------------------------------------
Your initial Journey is complete
------------------------------------------------------------
You now have an initial retirement plan. The next step is
not starting over—it is keeping this plan current as your
life changes.

You now have:
  • a retirement spending goal
  • a Social Security assumption
  • a retirement income plan
  • a resilience review
  • a tax-planning priority
  • a survivor-planning priority

[Premium continuity — conceptual, after save only]
Ongoing planning workspace to revisit and update this plan
over time — not a sales pitch. No pricing/trial/checkout here.

[ Return to My Journey ]
============================================================
```

---

## 18. Remaining open questions

1. Exact issue-selection logic using the two answers and light Phase 3 signals  
2. Whether Phase 2 contains enough household Social Security context for a personalized survivor note without another question  
3. Exact mapping from issues to the three displayed priority choices  
4. How much current Phase 4 and Phase 5 context should appear  
5. Exact placement and length of the Journey-completion and Premium-continuity sections  

---

## 19. Implementation risks

| Risk | Mitigation |
|---|---|
| Drift into estate planning | Locked name, scope list, honesty line |
| Fear-driven copy | Preparedness question; forbidden “unprotected/safe” verdicts |
| False precision | No hero shortfall; deeper tools for math |
| Implied beneficiary audit | Awareness wording; explicit non-verification |
| Thin phase | Strong teaching map + concrete priority save + Journey completion inventory |
| Premium feels like a pitch | Continuity-only framing after save; no price/trial/checkout in this design |
| Awkward tied copy | Separate priority items (Phase 5 lesson) |

---

## 20. Test scenarios (for later implementation)

- Missing / incomplete / unsaved Phase 3 → blocked  
- Complete Phase 3 → flow enabled  
- Missing Phase 4 / Phase 5 → still runs  
- Stale Phase 4 / Phase 5 → omit context  
- All 4 × 4 answer combinations produce a supported issue or `none_dominant`  
- Beneficiary “not yet” / “not sure” / “needs review” can surface `beneficiary_review` when appropriate  
- Preparedness “not reviewed” / “should review again” can surface income/spending issues when appropriate  
- “Already thought through” + “Yes, recently” can reach calm / `none_dominant` path  
- One clear issue vs two-way tie presentation  
- No hero shortfall / no “safe” / “unprotected” copy in UI  
- Save requires one priority; keep-annually available as calm path  
- Save + reload; Phase 3 changed after save  
- Journey-completion recap lists all six artifacts  
- Premium continuity appears only after save  
- Keyboard / mobile / PHP / JS / `git diff --check` (at implementation time)  
- Public nav remains closed in review-first milestone  

---

## 21. Success criteria

Phase 6 design is ready for a later implementation plan when:

1. Phase name is **Survivor Planning** everywhere in product copy  
2. Central question, short wording, and decision statements match the locked text  
3. Exactly two required questions are locked  
4. No hero shortfall; scope boundaries hold  
5. Result = issue/tie + meaning + ≤3 directions + one saved priority  
6. Journey completion message and recap are locked in intent  
7. Premium continuity is conceptual and post-completion only  
8. Open questions are only the five listed in §18  

---

## 22. Related documents

- `PHASE_3_BUILD_YOUR_PLAN.md` — base-case income plan contract  
- `PHASE_4_STRESS_TEST.md` — resilience review  
- `PHASE_5_TAX_STRATEGY.md` — tax-planning priority pattern  
- `FREE_TO_PREMIUM_TRANSITION.md` — Premium boundary (commercial mechanics not redesigned here)  

---

## 23. Implementation sequencing (after a later plan)

1. This document remains the product source of truth  
2. Implementation plan (files, deterministic rules, copy finals, tests) — separate approval  
3. Review-only page before public navigation open  
4. Public Journey open + homepage/nav rename from any legacy label  
5. Premium gating only after account continuity + trial readiness  

**Do not start coding until an implementation plan is approved.**
