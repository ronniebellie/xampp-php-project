# Phase 2: Social Security — Design Document

**Status:** Architectural direction approved; not yet implemented
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)
**Depends on:** Phase 1 Spending & Goals
**Last updated:** 2026-07-23

---

## 1. Purpose of Phase 2

Phase 2 helps the user choose a **working Social Security assumption** for the rest of their retirement plan.

It is not a filing guide.
It is not a complete Social Security rules engine.
It is not merely a break-even calculator.

### Broader product insight

Phase 2 is the next step in answering:

> **Where will my retirement income come from?**

Phase 1 established how much the household expects to spend.
Phase 2 determines how much dependable Social Security income the plan should assume, and when that income is expected to begin.

Everything that remains after Social Security becomes the investment-withdrawal problem for later phases.

### Critical framing (repeated throughout the experience)

> **This is a plan assumption, not a Social Security filing action.**

Saving a claiming age in the Journey does not apply for benefits, lock in a filing date, or replace official Social Security guidance.

---

## 2. Core planning question

> **When should I claim Social Security, and what monthly benefit should my retirement plan assume?**

Supporting questions (secondary):

- How much of my Phase 1 spending need could Social Security cover?
- What remains for investments to fund?
- If I wait for a larger benefit, how will I cover spending in the meantime?
- What still needs verification before I rely on these numbers?

What Phase 2 is **not** asking first:

- Which age produces the highest lifetime total?
- What is my break-even age?
- What is my Primary Insurance Amount?

Those can appear as supporting clues, not as the decision itself.

---

## 3. How Phase 2 builds on Phase 1

Phase 1 produces the spending foundation. Phase 2 places Social Security into that foundation.

### Inputs carried forward from Phase 1

| Phase 1 output | Role in Phase 2 |
|---|---|
| Expected monthly retirement spending | The household spending target the plan must support |
| Other dependable retirement income (pensions, annuities, rental income) | Income already expected before Social Security and investments |
| Remaining monthly need before Social Security and investments | The gap Phase 2 and later investment phases must address |

### Amounts Phase 2 should make visible

Throughout Phase 2, keep these relationships clear:

1. **Expected monthly retirement spending** (from Phase 1)
2. **Other dependable retirement income** (from Phase 1)
3. **Remaining monthly need before Social Security and investments**
   `= spending − other dependable income`
4. After a Social Security assumption is chosen:
   **Remaining monthly need after Social Security for investments to cover**
   `= remaining before SS − assumed monthly Social Security benefit`
   (not below zero)

### Opening bridge copy (recommended)

> In Phase 1, you estimated needing about **$X per month** in retirement.
> About **$Y** may already come from pensions or similar income.
> That leaves about **$Z per month** for Social Security and investments to cover.

Then Phase 2 asks how much of that remaining need Social Security should provide, and when.

If Phase 1 is incomplete, allow continuation with temporary estimates, but gently prompt the user to finish Phase 1 so the income picture stays coherent.

---

## 4. Design principles

1. **Spending need comes first.** Social Security is evaluated against the Phase 1 target, not in isolation.
2. **Retiring and claiming are separate decisions.** Stop-work age and claim age may differ.
3. **Waiting for a larger benefit requires funding the delay.** Cash flow matters as much as lifetime totals.
4. **Break-even age is a clue, not the decision.**
5. **Longevity, cash flow, spouse considerations, and survivor income matter.**
6. **Phase 2 creates a working assumption for the plan**, not a final irrevocable filing choice.
7. **Use plain language.** Avoid leading with FRA, PIA, or other jargon.
8. **One decision at a time.** Prefer a short guided flow over a long article + form dump.
9. **Do not create false precision** when the user lacks a usable benefit estimate.
10. **Free must be genuinely useful.** The core comparison and decision belong in the free experience.

---

## 5. Language guidance

### Prefer

- “Monthly benefit at the age Social Security considers full retirement”
- “Your benefit amount from your Social Security statement”
- “Claim earlier / claim later”
- “Plan assumption”
- “Estimated monthly benefit”

### Avoid leading with

- FRA (unless later explained in plain language)
- PIA / Primary Insurance Amount
- Actuarial reduction / delayed retirement credits as unexplained labels
- “Best option” as if it were advice

### Internal vs user-facing

The system may use Full Retirement Age internally for calculations.
User-facing copy should first explain the idea in everyday words, then optionally introduce the official term.

Example:

> Social Security has a reference age, often called your **full retirement age**, based on your birth year. Your statement usually shows the monthly benefit you would receive if you claimed at that age. Claiming earlier reduces the monthly amount. Waiting can increase it.

---

## 6. Proposed user paths

### Path A — Already receiving benefits (short path)

Triggered by Step 1: **Yes, I am already receiving Social Security benefits.**

Flow:

1. Enter current monthly benefit (before optional Medicare deductions if the user knows the gross amount; otherwise use the amount they actually receive and note uncertainty).
2. Optionally note start age / year if known.
3. Save as the planning assumption.
4. Show how this benefit fits the Phase 1 spending picture.
5. Continue to the next Journey phase.

No claiming-age comparison required.

### Path B — Not yet receiving; has a usable estimate (comparison path)

Triggered by Step 1: **No**, then readiness: **I have a usable benefit estimate.**

Flow:

1. Enter statement-based estimate and birth year.
2. Enter expected retirement / stop-work age and a likely claiming age.
3. Compare personalized claiming options.
4. Choose the age/benefit to test in the plan.
5. Capture tradeoff + verification notes lightly.
6. Save and continue.

### Path C — Not yet receiving; no usable estimate (return-later path)

Triggered by readiness: **I do not have a usable estimate yet.**

Flow:

1. Explain that comparison needs a real estimate to avoid false precision.
2. Show how to find an estimate on [ssa.gov](https://www.ssa.gov) (my Social Security / statement).
3. Allow the user to save a “needs information” status and return later.
4. Do not invent a benefit amount.

Optional light exploration with clearly labeled placeholders may be considered later, but v1 should prefer honesty over fake numbers.

---

## 7. Proposed step sequence

### Phase hub (brief)

- State the planning question.
- Restate: plan assumption, not a filing action.
- Show Phase 1 bridge numbers when available.
- Begin with Step 1.

### Step 1 — Are you already receiving Social Security benefits?

- **Yes** → short path (Path A)
- **No** → continue

If No, ask a gentle readiness question:

- I have a recent benefit estimate I can use
- I have an estimate, but it needs verification
- I do not have a usable estimate yet → Path C

### Step 2 — Tell us what your statement shows

For Path B:

**Required**

- Birth year
- Estimated monthly benefit if claimed at the Social Security full-retirement reference age shown on the statement

**Optional / recommended**

- Current age (derivable from birth year + today’s date, or asked directly if clearer)
- Expected retirement / stop-work age
- A claiming age the user is currently considering

Plain-language coaching around the statement amount belongs here.

### Step 3 — Compare claiming options

Show a small set of personalized alternatives (see Section 8).

For each option, emphasize:

- Estimated monthly benefit
- Share of Phase 1 spending covered by Social Security
- Remaining monthly need after Social Security for investments
- If stop-work age is earlier than claim age: how many years of spending may need another source while waiting

Supporting clues (secondary):

- Simple lifetime illustration through a chosen age
- Break-even age between two options, labeled as a clue only

### Step 4 — Choose the assumption to test

User selects:

- Claim at age __
- Or remains “not ready / needs more information”

Then lightly capture:

- Why this option stands out
- Main tradeoff
- What still needs verification

### Step 5 — Confirm and save the planning record

Summary only. No second interactive comparison.

Include:

- Claiming age or already-receiving status
- Assumed monthly Social Security benefit
- Phase 1 spending target
- Other dependable income
- Remaining need before Social Security
- Remaining need after Social Security for investments
- Main tradeoff
- Verification / status notes
- Reminder: plan assumption, not a filing action

Primary CTA: **Save and continue**

---

## 8. Comparison scenarios

### Design goal

Help the user see **earlier, likely, and later** choices in context — not force every user through an abstract 62 / full retirement age / 70 grid if a more personal set would teach better.

### Default comparison (useful baseline)

When personalization inputs are thin, default to:

| Option | Role |
|---|---|
| Age 62 | Earliest common claiming age; income sooner; smaller monthly benefit |
| Full-retirement reference age | Statement baseline; plain-language explanation required |
| Age 70 | Latest common maximizing age; largest monthly benefit; longest wait |

This default remains valuable because it matches common public comparisons and many users’ mental model.

### Personalized comparison (preferred when inputs exist)

Build three (sometimes four) options from:

- **Current age** (or next eligible claiming age if already 62+)
- **Expected retirement / stop-work age**
- **Likely claiming age** the user is considering
- **Earlier alternative**
- **Later alternative**

#### Suggested construction rules (v1 draft)

1. Always include the user’s **likely claiming age** when provided.
2. Include an **earlier alternative** when possible (often 62, or current age if already eligible and earlier than the likely age).
3. Include a **later alternative** when possible (often 70, or a later age than the likely choice, capped at 70).
4. Include the **statement reference age** (full retirement age) when it is not already one of the above and when it helps orient the user to the statement.
5. Avoid duplicate ages.
6. Prefer **3 options** on the main screen. Offer “compare more ages” as progressive disclosure rather than a dense table up front.

#### Examples

**Example 1**

- Current age 58
- Plans to stop work at 65
- Considering claiming at 67

Possible comparison set:

- 62 (earlier)
- 65 (aligned with stop-work)
- 67 (likely)
- Optional deeper: 70

**Example 2**

- Current age 64
- Already eligible
- Considering claiming soon

Possible comparison set:

- 64 (now / soon)
- Full-retirement reference age
- 70

**Example 3**

- Sparse inputs only

Use default:

- 62 / reference age / 70

### What each comparison row should answer

In plain language:

1. What monthly benefit would my plan assume?
2. How much of my spending target does that cover?
3. What remains for investments?
4. If I retire before I claim, how long might I need to bridge?

### Explicit non-goals for comparison v1

- Full dual-spouse optimization
- Exact earnings-test month-by-month simulation
- Taxability of benefits
- Medicare premium interactions as a primary decision driver

Those may be mentioned as “what this doesn’t decide yet.”

---

## 9. Required and optional inputs

### Required (by path)

| Input | Path A | Path B | Path C |
|---|---|---|---|
| Already receiving? | Yes | No | No |
| Current monthly benefit | Required | — | — |
| Birth year | Optional | Required | Optional |
| Statement benefit at reference age | — | Required | — |
| Selected claim age to test | — | Required to complete as “provisional” | — |
| Estimated monthly benefit at selected age | Derived or entered | Required to save provisional | — |

### Recommended optional inputs

- Current age (if not derived)
- Expected retirement / stop-work age
- Likely claiming age before comparison
- Spouse/household awareness flag (“I also need to think about a spouse later”)
- Verification checklist items
- Short notes

### Not required in Phase 2

- Spouse’s full statement (may be encouraged for later review)
- Exact filing paperwork details
- Tax filing status
- Portfolio details (Phase 3+)

---

## 10. Educational guidance

Embed coaching beside decisions, not as a separate textbook.

### Core teachings

1. **Spending need comes first.**
   Social Security is one source of income toward a known target.

2. **Retiring and claiming are separate decisions.**
   You can stop working before or after Social Security begins.

3. **Waiting requires funding the delay.**
   A larger future benefit is only helpful if the household can cover spending meanwhile.

4. **Break-even is a clue, not the decision.**
   It does not know health, market sequence, spouse needs, or peace of mind.

5. **Longevity and survivor needs matter.**
   Especially for couples, the longer-lived spouse’s income security can change the answer.

6. **This creates a working assumption.**
   The Journey can revise it when a new statement arrives or priorities change.

### Coach’s tips (examples)

- “A higher lifetime total is interesting, but only if you can comfortably get there.”
- “If your spending target is $6,000 and Social Security would provide $2,400, your plan still needs about $3,600 from other sources.”
- “Choosing an age here does not file for benefits. It only tells your retirement plan what to test.”

---

## 11. Saved outputs for later phases

Phase 2 should save a durable planning record that later phases can consume.

### Decision fields

- `alreadyReceiving` (boolean)
- `decisionStatus` (`provisional` | `already-receiving` | `needs-information`)
- `claimAge` (nullable)
- `monthlySocialSecurityBenefit` (assumed benefit for the plan)
- `statementReferenceBenefit` (statement amount at full-retirement reference age; nullable for Path A)
- `birthYear` (nullable)
- `expectedStopWorkAge` (nullable)
- `mainTradeoff`
- `verificationNeeded[]` / `verificationPriority`
- `notes` (optional)

### Derived fields for downstream phases

Using Phase 1 + Phase 2:

- `monthlySpendingTarget` (from Phase 1)
- `monthlyOtherDependableIncome` (from Phase 1)
- `monthlyNeedBeforeSocialSecurityAndInvestments`
- `monthlyNeedAfterSocialSecurityForInvestments`

### Record metadata

- schema version
- created / updated timestamps
- source tool identity (Journey-native Phase 2)
- downstream readiness flag
- reminder: assumption, not filing action

### Existing prototype note

An earlier Phase 2 coaching page and record schema already exist in the Journey codebase and currently lean on the external Claiming Analyzer. This design document is the target architecture. Implementation may migrate or replace that prototype carefully so existing browser records do not break.

---

## 12. Free versus Premium boundaries

### Free (must be complete and useful)

- Full Phase 2 guided flow
- Personalized or default claiming comparison
- Phase 1 bridge calculations
- Choosing and viewing a plan assumption
- Local save/progress within the Journey prototype model already used in Phase 1
- Clear educational coaching

### Premium (later enhancements; not required to finish the decision)

- Sync / save across devices
- Revisit and update decisions over time in a member workspace
- Side-by-side alternate claiming assumptions saved as scenarios
- Update workflow after a new Social Security statement
- Possibly richer household/survivor explorations later

Premium must not make the free path feel crippled. A first-time user should leave Phase 2 with a usable Social Security assumption for free.

---

## 13. Relationship to the existing Claiming Analyzer

Recommended direction:

- Make Phase 2 **Journey-native** for the core comparison and decision.
- Keep the main-site Claiming Analyzer as an **optional deeper tool**, not the required center of the experience.

Rationale:

- Preserves continuity with Phase 1
- Allows live use of Phase 1 spending numbers
- Avoids copy/paste and context loss
- Better supports “plan assumption” coaching

Transitional implementation option (if needed):

- Journey collects context and frames the decision first
- Analyzer remains available for deeper exploration
- Journey still owns the saved assumption

The native experience remains the target.

---

## 14. Success criteria

Phase 2 succeeds when a user can say:

> “My plan will test claiming at age __, for about $__/month.
> That covers about __% of my retirement spending target.
> I’ll still need about $__/month from investments.
> The main tradeoff I’m accepting is __.
> I understand this is a planning assumption, not a filing action.”

Additional success checks:

- Already-receiving users can finish quickly without irrelevant comparison.
- Users without estimates are guided to ssa.gov without fake numbers.
- Break-even is never presented as the verdict.
- Phase 1 numbers remain visible and meaningful throughout.
- The saved record is sufficient for later income/investment planning.

---

## 15. Unresolved design questions

These require discussion before implementation:

1. **Native vs transitional analyzer dependency**
   Ship Journey-native comparison in v1, or allow a short transitional handoff to the existing Claiming Analyzer?

2. **Benefit estimate basis**
   Should the comparison engine approximate benefits from the statement reference amount using standard early/late claiming adjustments, and how explicitly should methodology/limits be disclosed?

3. **Personalized comparison algorithm details**
   Finalize exact rules for choosing the 3–4 ages when current age, stop-work age, and likely claim age conflict or cluster tightly.

4. **Gross vs net benefit entry**
   How should we ask for “current monthly benefit” or statement amounts when Medicare premiums may already be deducted?

5. **Couples / survivor scope in Phase 2**
   Keep Phase 2 individual-first with a “spouse later” flag only, or include a lightweight survivor awareness screen in v1?

6. **Longevity age for lifetime illustrations**
   Default to a fixed age (e.g., 85/90), ask the user, or hide lifetime totals unless requested?

7. **Phase naming / handoff destination**
   Progress nav currently labels later phases differently from the intended Journey model (Investments / Taxes / Protecting Your Family / Ongoing Review). What should Phase 2’s “continue” destination say in the near term?

8. **Record migration**
   How should existing Phase 2 prototype browser records map into the refined schema without data loss or confusing status badges?

9. **Placeholder exploration without estimates**
   Strictly block comparison until an estimate exists, or allow a clearly marked educational demo mode?

10. **Stop-work vs claim-age bridge math**
    When showing years to fund while waiting, should v1 assume level Phase 1 spending with no inflation, and how is that communicated?

---

## 16. Implementation readiness checklist (future)

Do not implement until approved. When approved, implementation should:

1. Preserve Phase 1 architecture patterns (guided steps, plain language, local Journey records).
2. Avoid unrelated refactors.
3. Keep free comparison fully usable.
4. Repeat the “plan assumption, not filing action” framing at key moments.
5. Validate PHP / JS / CSS and deploy only after explicit approval.

---

## Document history

- **2026-07-23:** Initial design document created from approved Phase 2 architectural direction, incorporating opening-flow, plain-language, personalized comparison, Phase 1 bridge, free/Premium, and “income source” framing refinements.
