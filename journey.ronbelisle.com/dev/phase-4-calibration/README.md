# Phase 4 Calibration Harness (development only)

Local review tool for provisional Stress Test mathematics. **Not** the Phase 4 product UI.
**Not linked** from Journey navigation. Meta: `noindex,nofollow`.

## Status

**Phase 4 Calibration Complete — Provisional Baseline**
Hybrid Round 2 is the approved provisional baseline. Numeric assumptions remain provisional until Phase 4 implementation review.

## Open (browser)

From the Journey web root:

`/dev/phase-4-calibration/`

Example on local XAMPP:

`http://localhost/journey.ronbelisle.com/dev/phase-4-calibration/`

## Scope

- Run Mild / Central / Strict / **Hybrid Round 2** against fixtures A–F
- Configure **Plan G** privately (see below)
- Does **not** auto-read `localStorage`
- Does **not** hard-code personal household figures
- Does **not** modify Journey navigation, Premium, Stripe, or production UX

## Plan G — how to run locally (private)

Required Phase 3-aligned fields:

- `monthlySpending`
- `monthlySocialSecurity`
- `monthlyOtherIncome` (use `0` if none)
- `monthlyFromSavings` (optional; else computed as spending − SS − other)
- `savingsBalance`

Implied withdrawal rate and Phase 3 assessment are computed by the engine.

### Method A — ignored local JSON + CLI (recommended)

```bash
cd journey.ronbelisle.com/dev/phase-4-calibration
cp plan-g.local.json.example plan-g.local.json
# edit plan-g.local.json with real Phase 3 values
jsc run-plan-g.js
# or: jsc run-plan-g.js /path/to/other-phase3.json
```

`plan-g.local.json` is **gitignored** and must never be committed.

### Method B — harness UI paste / fields

Open the harness page → Plan G panel → type values or paste Phase 3-like JSON → Apply → enable Plan G → Run calibration.

No localStorage read.

## Classifier tests

```bash
jsc test-classifier-edge-cases.js
```

Covers same-year / later / earlier depletion vs base, base-lasts+scenario-depletes, both-last, zero-W / zero-balance, Hybrid A–F expectations, repeatability.

## Docs

- Framework / milestone: `docs/PHASE_4_CALIBRATION.md`
- Round 1 report: `CALIBRATION_REPORT_ROUND1.md`
- Round 2 report: `CALIBRATION_REPORT_ROUND2.md`
- Validation pass: `CALIBRATION_VALIDATION_PASS.md`
- Official Phase 4 design (product direction; numbers still provisional): `docs/PHASE_4_STRESS_TEST.md`

Default harness pack: **Hybrid Round 2**.
