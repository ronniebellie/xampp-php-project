# Phase 4 Stress Test — Calibration Framework

**Status: Phase 4 Calibration Complete — Provisional Baseline** (2026-07-25)

**Product:** Retirement Planning Journey (`journey.ronbelisle.com`)
**Related design:** `PHASE_4_STRESS_TEST.md` (product direction unchanged; numeric §18 values remain provisional)
**Harness:** `/dev/phase-4-calibration/` (development only; not Journey navigation; not a public feature)

---

## Calibration milestone (locked decision)

1. **Hybrid Round 2 is the approved provisional baseline** for future Phase 4 implementation.
2. **Numeric assumptions remain provisional** until Phase 4 implementation review.
3. **No further assumption-calibration round is currently planned.**
4. The classifier edge-case defect (same/later depletion vs an already-depleting base → false Severe) **has been corrected** in the development engine.
5. **Plan G** remains a final real-plan validation step when actual Phase 3 values are supplied privately (never auto localStorage; never committed).
6. **Dominant-stress tie-breaking** is a presentation / copy issue, not an engine blocker.
7. **Any future tuning** should respond to a demonstrated implementation problem, not abstract refinement.

---

## Purpose

Calibrate provisional Scenario defaults and classification logic using realistic plans **before** freezing any numbers in the official Phase 4 design.

**Success standard:** Experienced retirement planners should generally find Stress Test results **reasonable and educational** — not mathematically perfect.

---

## Stance on numbers

Hybrid Round 2 dials are the working baseline for implementation, but they are **not** locked product constants until Phase 4 UI review:

- Planning horizons
- Return assumptions
- Early market decline percentages
- Resilience thresholds
- Overall classification / aggregation logic

Mild / Central / Strict remain in the harness for comparison only.

---

## Representative Hybrid Round 2 outcomes (A–F)

| Plan | Hybrid Round 2 overall | Notes |
|---|---|---|
| A | Holds | Protected from false alarm |
| B | Sensitive | Near-4% nuance; not Needs |
| C | Holds | Absolute-cushion guard |
| D | Needs | Remains appropriately vulnerable |
| E | Holds | Tiny WR calm |
| F | Sensitive | Longevity Noticeable; dominant may still be Weaker growth (presentation) |

---

## Plan G (private)

- Do **not** hard-code personal figures into committed source.
- Do **not** read browser `localStorage` automatically.
- Supply via `dev/phase-4-calibration/plan-g.local.json` (gitignored), CLI path, or harness paste/fields.
- See harness `README.md` for exact run steps.

---

## Reports

| Artifact | Path |
|---|---|
| Round 1 | `dev/phase-4-calibration/CALIBRATION_REPORT_ROUND1.md` |
| Round 2 | `dev/phase-4-calibration/CALIBRATION_REPORT_ROUND2.md` |
| Validation pass | `dev/phase-4-calibration/CALIBRATION_VALIDATION_PASS.md` |
| Classifier tests | `dev/phase-4-calibration/test-classifier-edge-cases.js` |

---

## Out of scope until Phase 4 implementation planning

- Phase 4 user interface
- Journey navigation changes
- Premium gating, auth, Stripe, 30-day trial
- Locking numeric assumptions into the official design as final

---

## Document history

| Date | Change |
|---|---|
| 2026-07-24 | Calibration framework approved; Plan G configurable |
| 2026-07-24 | Round 1 / Round 2 reports linked |
| 2026-07-25 | **Milestone: Calibration Complete — Provisional Baseline (Hybrid Round 2)**; classifier edge-case patched; Plan G local-input method documented |
