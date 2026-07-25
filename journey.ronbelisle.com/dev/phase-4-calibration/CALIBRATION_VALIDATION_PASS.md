# Phase 4 Calibration — Final Validation Pass

**Status:** Engineering validation complete; classifier edge-case **patched** 2026-07-25
**Date:** 2026-07-24 (pass) / 2026-07-25 (closeout patch)
**Subject:** Hybrid Round 2 as provisional baseline (approved)

---

## Check 1 — Formula validation summary

### Projection core — consistent

Automated checks (74 passed, 0 failed on happy-path invariants) confirm:

| Area | Result |
|---|---|
| Year step order | Start → withdraw `min(W,B)` → grow remainder — applied identically in every `projectPath` call |
| Early decline | Decline applied to starting balance **before** year-1 withdrawal; year-1 end matches hand calculation |
| Weaker growth | Same `B`, `W`, horizon as base; only growth rate differs; prefix path diverges only via growth |
| Longer retirement | First `baseHorizonYears` annual ends **match** the base path exactly; only horizon length extends; growth and decline unchanged (`startingDeclinePct=0`, growth = base) |
| Depletion flags | `depletedYear` set ⇒ `lastedFullHorizon=false`; zero-`W` paths last and grow; year-1 shortfall detected when post-shock balance `< W` |
| Non-negative balances | Ending balances ≥ 0 on all Hybrid A–F scenarios |
| Aggregation counts | `severeCount` / `noticeableCount` match per-scenario codes; Holds invariant (`severe=0`, `noticeable≤1`) holds on A/C/E |

**Verdict:** The deterministic projection engine appears **internally consistent** for the documented Hybrid Round 2 path definitions.

### Classification — mostly consistent; one edge-case defect

Documented Round 2 rules execute in a clear priority order for the common case (base lasts):

1. Year-1 shortfall → Severe
2. Depletion timing vs late window → Noticeable / Severe
3. Full-horizon cushion / ratio / absolute-cushion guard → Little / Noticeable

Aggregation order matches the Round 2 write-up: genuine-Severe Needs rules → Sensitive rules → Holds → workable near-4% cap.

**Defect found in validation (patched 2026-07-25):**

When **both** the base path and the scenario path deplete, and the scenario depletes **in the same year or later** than base (`earlierBy ≤ 0`), `classifyImpact` previously fell through to `incomplete_funding` → **Severe**.

**Corrected behavior now:**

| Case | Result | `severityKind` |
|---|---|---|
| Same depletion year as depleting base | **Little** | `same_as_base_depletion` |
| Later depletion than depleting base (or lasts while base depletes) | **Little** | `better_than_base_depletion` |
| Somewhat earlier than base (`0 < earlierBy < earlierDepletionYears`) | **Noticeable** | `somewhat_earlier_than_base` |
| Much earlier (`earlierBy ≥ earlierDepletionYears`) | **Severe** | `much_earlier_than_base` |

Covered by `test-classifier-edge-cases.js`. Hybrid A–F outcomes unchanged after the patch.

### Configurability gap (not a math error)

`earlyDeclineBeforeWithdrawal: false` is stored on the pack and echoed in `parameters`, but **does not change** the projection (always decline-first). Safe while product policy is decline-first; must not be advertised as a working switch until implemented.

---

## Check 2 — Real Phase 3 validation

**Not performed.**

No development-use Phase 3 export or representative saved record was available in the repo or harness inputs.

Constraints honored:

- Did **not** read browser `localStorage`
- Did **not** infer or hard-code personal household figures
- Did **not** fabricate Plan G inputs

**To complete later:** paste an actual Phase 3 JSON/fields into the harness Plan G panel (session-only) and re-run Hybrid Round 2.

---

## Check 3 — Baseline readiness answers

1. **Is Hybrid Round 2 the strongest calibration candidate?**
   **Yes.** Among Mild / Central / Strict / Hybrid under Round 2 classification, Hybrid best matches the special-attention expectations (A/C/E calm, B nuanced Sensitive, D Needs, F Sensitive with longevity Noticeable).

2. **Are remaining issues mathematical or presentation?**
   - **Mathematical (edge case):** same/later depletion vs a depleting base → incorrect Severe (`incomplete_funding`). Fix before shipping UI.
   - **Config gap:** unused withdraw-first flag.
   - **Presentation / product-copy:** dominant-stress tie-breaks (Weaker vs Early vs Longer) — not aggregation errors.

3. **Would more tuning materially improve understanding now?**
   **Mostly no for assumption packs.** Further pack tuning without a real Phase 3 case and without UI copy is low leverage. Fix the classification fallthrough; defer dominant-stress wording and Plan G confirmation to implementation / first UI review.

4. **Should Hybrid Round 2 become the provisional baseline?**
   **Yes**, with all numeric assumptions still provisional, and with the classification edge-case logged as a pre-implementation fix.

---

## Recommendation

### **A. Ready as provisional baseline**

Hybrid Round 2 + Round 2 classification is sound enough to be the **provisional calibration baseline** for future Phase 4 implementation work.

**Conditions (not blockers to baseline status, but required before public Phase 4 ships):**

1. Patch `classifyImpact` fallthrough when base depletes and scenario is same/later (no assumption retune required).
2. Run Plan G once a real Phase 3 export is supplied via configurable harness inputs.
3. Keep numbers provisional in the official design until those two items are done and stakeholders accept UI copy around “most important stress.”

**Not B:** An additional full calibration round of pack assumptions is not the highest-value next step.
**Not C:** Core projection math is consistent; the defect is a bounded classification edge case.

---

## Remaining concerns (short list)

1. Classification hole when base already depletes (see above).
2. Dominant-stress selection is a presentation policy, not a solvency verdict.
3. Real Phase 3 (Plan G) still unvalidated.
4. Absolute-cushion and longevity ratio floors remain judgment calls (educational, not market forecasts).

---

*Validation only. Official `PHASE_4_STRESS_TEST.md` unchanged. No commit/push/deploy. No Phase 4 UI.*
