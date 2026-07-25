# Free Journey → Premium Transition — Design Document

**Status:** Design approved for documentation; not yet implemented
**Product:** Retirement Planning Journey (`journey.ronbelisle.com`) as an independent product
**Trigger:** Immediately after successful completion of Phase 3
**Not this page:** Stripe checkout, account settings, or general Premium marketing
**Last updated:** 2026-07-24

---

## 1. Product purpose

This page answers one question:

> **Why should I continue into Premium?**

It is shown after the visitor successfully completes Phase 3 and has a personalized base-case retirement income plan.

It is **not** the subscription checkout page.
It is the coaching bridge between the free foundation (Phases 1–3) and the Premium continuation (Phases 4–6).

### Target visitor thought

> “I’ve already built my retirement plan. Now I naturally want to see how well it survives real life.”

---

## 2. Psychological goal

The visitor should feel, in order:

1. **Successful** — The free foundation is a real accomplishment.
2. **Clear** — The next planning question is resilience, not shopping.
3. **Invited** — Premium is the natural way to continue that question.
4. **Respected** — Declining is allowed without guilt or pressure.

The page must never imply that Phases 1–3 were a teaser or incomplete.

---

## 3. Locked free / Premium boundary

| Phase | Access |
|---|---|
| 1. Spending & Goals | Free |
| 2. Social Security | Free |
| 3. Build Your Plan | Free |
| 4. Stress Test | Premium |
| 5. Tax Strategy | Premium |
| 6. Survivor & Legacy | Premium |

### Locked product decisions

- Phases 1–3 are completely free and useful on their own.
- Premium is **first introduced** after successful completion of Phase 3.
- The Journey Premium trial is **30 days**.
- Do **not** preserve or recommend a separate 7-day Journey trial between Phases 1 and 2.
- The existing Phase 1→Phase 2 Premium invitation will be removed or redesigned in a later task so it does not conflict with this strategy.
- Do **not** implement checkout, Stripe, authentication, or trial behavior in this design phase.

---

## 4. Locked 30-day trial strategy

- Journey Premium begins with a **free 30-day trial**.
- This transition page introduces that trial only **after** explaining why Stress Test matters.
- The primary CTA is trial-oriented, not price-oriented.
- **Do not display pricing** on this page. Pricing belongs in checkout unless a later business decision changes that.
- Trial wording beneath the CTA must later match real Stripe behavior.
- The trial must **not begin** until the Premium experience it promises (starting with Phase 4) is genuinely usable.

---

## 5. Visitor narrative

Exact narrative sequence (locked):

1. Free foundation complete
2. Congratulations and accomplishment
3. What you built
4. The next planning question: resilience
5. Emotional bridge
6. Continue your Journey with Premium
7. Free 30-day trial CTA
8. One-line trial reassurance
9. Respectful not-now path

**Premium must not appear before step 6.**

---

## 6. Dynamic Phase 3 recap

Show a concise summary from the saved Phase 3 record (and Phase 1/2 provenance):

| Display | Source |
|---|---|
| Monthly retirement spending goal | Phase 1 / Phase 3 saved plan |
| Social Security and other dependable monthly income | Phase 2 SS + Phase 1 other income (combined for display) |
| Monthly amount needed from retirement savings | Phase 3 |
| Phase 3 base-case assessment | workable / close / difficult label |

### Required statement

> Phases 1–3 remain free and useful on their own.

Do not imply the free plan is incomplete.

---

## 7. Revised page structure and exact draft copy

### Planned route intent (implementation later)

Example: `/phases/continue-to-premium.php`
Shown only after successful Phase 3 completion (or an explicit continue action from Phase 3).

### Section 1 — Free foundation complete

**Eyebrow:** Free foundation complete

**Heading:** You’ve built your base-case retirement plan

**Opening copy:**

> Congratulations. You’ve completed the free foundation of your Retirement Planning Journey.

> You now have a coordinated retirement income plan that brings together your spending goal, Social Security assumption, other dependable income, and retirement savings.

### Section 2 — What you built

**Heading:** What you’ve already put in place

**Body / dynamic summary:**

> Your plan currently assumes about **$X** per month in retirement spending.
> About **$Y** per month comes from Social Security and other dependable income.
> About **$W** per month would need to come from retirement savings.
> Base-case assessment: **[Looks workable / Looks close / Looks difficult] on these assumptions.**

**Required line:**

> Phases 1–3 remain free and useful on their own.

### Section 3 — Next planning question (no Premium yet)

**Heading:** The next question is how resilient your plan may be

**Copy:**

> Your base-case plan shows how the pieces fit under your current assumptions. Real retirement may include weaker markets, higher inflation, or a longer life than expected.

> Phase 4: Stress Test uses the plan you just built and explores how it may hold up when conditions are less favorable.

### Section 4 — Emotional bridge (still no Premium features list)

> You’ve invested time building this retirement plan. Now you can find out how resilient it may be.

### Section 5 — Introduce Premium

**Heading:** Continue your Journey with Premium

**Copy:**

> Phase 4 and the remaining Journey phases continue in Premium. Begin with a free 30-day trial and use your own retirement income plan in Stress Test.

Keep this short. Do not add a broad list of unrelated Premium features.

One next step only:

> See how well the retirement plan you built may hold up in real life.

### Section 6 — Primary CTA

**CTA label:** Start My Free 30-Day Trial

**Rule:** Do **not** append “and Continue to Phase 4” unless Phase 4 is complete and the route genuinely works.

**Rule:** If Phase 4 is not yet available, do **not** activate the trial CTA or send users into a waiting state after beginning a paid-product trial.

### Section 7 — One-line trial reassurance

Directly beneath the CTA (quiet, not a large section):

> Includes a free 30-day trial. Cancel before the trial ends and you won’t be charged.

This wording must later be confirmed against the real Stripe configuration and checkout behavior.

Do not emphasize cancellation elsewhere on the page.

### Section 8 — Respectful not-now path

**Secondary CTA:** Not now — return to my Journey

**Supporting copy:**

> That’s fine. Your free base-case retirement income plan is complete. You can return later when you’re ready to stress-test it.

**Secondary link:** Review My Phase 3 Plan → `/phases/build-your-plan.php`

No popups, guilt language, urgency, or repeated Premium prompts.

---

## 8. CTA hierarchy

| Priority | Control | Destination intent |
|---|---|---|
| Primary | Start My Free 30-Day Trial | Auth/trial checkout only when Phase 4 is usable and trial config is verified |
| Secondary | Not now — return to my Journey | Journey homepage |
| Secondary link | Review My Phase 3 Plan | Phase 3 page |
| Conditional | Continue to Phase 4 / Log in | Returning Premium or logged-in states only |

---

## 9. Returning-user states

Preserve these concepts for later implementation (do not implement yet):

| State | Experience |
|---|---|
| Phase 3 complete, no trial / not Premium | Show this invitation page |
| Premium active or trial active | Skip the sales page; continue to Phase 4 |
| Premium / trial expired | Calm reactivation to continue Phase 4+ |
| Phase 3 incomplete | Do not show this page; return to Phase 3 |

---

## 10. Not-now behavior

If the visitor declines:

- They keep a complete, useful free base-case plan.
- Phases 1–3 remain fully accessible.
- Homepage / progress should show free foundation complete and Phase 4+ as Premium continuation (not broken).
- No nagging follow-ups on this visit.

---

## 11. Authentication, account persistence, and plan-sync requirements

### Visitor-facing rule (locked for this page)

Do **not** expose technical storage details on this transition page.

Do **not** include visitor-facing language such as:

- “Your plan is saved in this browser”
- “Continue in this same browser”

That is implementation detail and weakens confidence at the subscription decision.

### Architecture requirements (not sales copy)

Before launch, the product must support an honest, reliable account and plan-continuity experience.

Recorded requirements:

1. Define how a Journey plan is associated with an authenticated account.
2. Define how Phase 1–3 browser progress maps into that account when Premium begins.
3. Provide reliable continuity for returning Premium users into Phase 4 with their Phase 3 plan.
4. Do **not** claim cross-device storage until it exists.
5. Until continuity exists, treat account/plan sync as a **launch blocker**, not as something to paper over with browser warnings on the sales page.

---

## 12. Stripe and billing facts that must be confirmed before implementation

Before activating the CTA, confirm and document:

1. Journey has its **own verified 30-day Stripe trial** configuration (independent of any 7-day flows elsewhere).
2. Whether a payment method is required to start the trial.
3. Exact charge timing when the trial ends.
4. Exact cancellation behavior and whether “Cancel before the trial ends and you won’t be charged” is literally true.
5. Return URL behavior after registration / checkout back into the Journey.
6. How free accounts, existing Premium subscribers, and expired subscribers are routed.

Until confirmed, the reassurance sentence remains draft intent, not final legal/billing copy.

---

## 13. Phase 4 readiness requirement

Phase 4 (Stress Test) must be **genuinely usable** before:

- the trial CTA is activated, and
- visitors are invited to start a 30-day Premium trial from this page.

If Phase 4 is not ready:

- This page may exist in design/docs form.
- The live CTA must remain inactive or the page must not be the live post–Phase 3 destination yet.
- Do not start trials into a waiting room.

---

## 14. Text wireframe

```text
============================================================
Eyebrow: Free foundation complete
H1: You’ve built your base-case retirement plan
============================================================

Congratulations. You’ve completed the free foundation of
your Retirement Planning Journey.

You now have a coordinated retirement income plan that
brings together your spending goal, Social Security
assumption, other dependable income, and retirement savings.

------------------------------------------------------------
What you’ve already put in place
------------------------------------------------------------
• Monthly spending goal: $X
• Social Security + other dependable income: $Y / month
• Needed from retirement savings: $W / month
• Base-case assessment: [Workable / Close / Difficult]

Phases 1–3 remain free and useful on their own.

------------------------------------------------------------
The next question is how resilient your plan may be
------------------------------------------------------------
Your base-case plan shows how the pieces fit under your
current assumptions. Real retirement may include weaker
markets, higher inflation, or a longer life than expected.

Phase 4: Stress Test uses the plan you just built and
explores how it may hold up when conditions are less
favorable.

You’ve invested time building this retirement plan.
Now you can find out how resilient it may be.

------------------------------------------------------------
Continue your Journey with Premium
------------------------------------------------------------
Phase 4 and the remaining Journey phases continue in
Premium. Begin with a free 30-day trial and use your own
retirement income plan in Stress Test.

[ Start My Free 30-Day Trial ]

Includes a free 30-day trial. Cancel before the trial ends
and you won’t be charged.

------------------------------------------------------------
Not now — return to my Journey
------------------------------------------------------------
That’s fine. Your free base-case retirement income plan
is complete. You can return later when you’re ready to
stress-test it.

[ Not now — return to my Journey ]
[ Review My Phase 3 Plan ]
============================================================

Premium does not appear before “Continue your Journey with Premium.”
No browser-storage lecture.
No pricing.
No large cancellation section.
No unrelated Premium feature dump.
```

---

## 15. Acceptance criteria (for later implementation)

1. Page appears only after successful Phase 3 completion (or explicit continue from Phase 3).
2. Narrative order matches §5 exactly; Premium appears only at step 6+.
3. Opening celebrates accomplishment; no selling in the first screen sections.
4. Dynamic Phase 3 recap displays correctly.
5. Explicitly states Phases 1–3 remain free and useful on their own.
6. Explains Stress Test before introducing Premium.
7. Includes the emotional bridge before Premium.
8. Primary CTA is “Start My Free 30-Day Trial” and is inactive until Phase 4 is usable and trial config is verified.
9. One quiet trial reassurance line under the CTA; no pricing on page.
10. Respectful not-now path and Review Phase 3 link work.
11. No browser-storage sales copy on the page.
12. Returning Premium / active-trial users bypass this page when those states are implemented.
13. No checkout UI embedded on this page.
14. Coaching tone matches the rest of the Journey.

---

## 16. Launch blockers

Explicit blockers before this transition can go live:

1. **Phase 4 must be genuinely usable** before the trial can begin.
2. The Journey needs its **own verified 30-day Stripe trial** configuration.
3. Trial billing and cancellation wording must **match actual Stripe behavior**.
4. The account and Journey plan must have a **reliable continuity strategy** (no claim of cross-device storage until it exists).
5. The earlier Phase 1→Phase 2 **7-day trial invitation must be removed or redesigned**.
6. Returning Premium users must **bypass the transition**.

Additional confirmation items (related, not separate blockers if covered above):

- Payment-method-at-start behavior
- Post-checkout return into Phase 4
- Expired-Premium reactivation path

---

## 17. Relationship to other Journey transitions

| Transition | Role |
|---|---|
| Phase 1 → Phase 2 (`continue-to-phase-2.php`) | Must stop introducing Journey Premium / 7-day trial in a later cleanup task |
| Phase 3 → Premium (this document) | The first Premium introduction in the independent Journey product |
| Checkout / Stripe | Separate from this page |

---

## Document history

| Date | Change |
|---|---|
| 2026-07-24 | Official design document created from approved Free→Premium architecture with revised accomplishment-first narrative, locked 30-day trial, and launch blockers |
