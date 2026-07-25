# Phase 4 Stress Test — Calibration Report (Round 2)

**Status:** Analysis only — provisional engine revised for Round 2 testing; **not** product-approved
**Generated:** 2026-07-25T01:25:17.524Z
**Scope:** Plans A–F × Mild / Central / Strict / **Hybrid Round 2**
**Plan G:** **Omitted** — no session Phase 3 values supplied; none inferred or hard-coded.
**Constraints:** No public Phase 4 UI; no Journey nav/Premium/Stripe/production changes; official `PHASE_4_STRESS_TEST.md` untouched; no commit/push/deploy.

---

## 1. Hybrid Round 2 assumptions

| Parameter | Hybrid Round 2 | Mild | Central | Why chosen (provisional) |
|---|---:|---:|---:|---|
| Base planning horizon | **28y** | 25y | 30y | Midpoint; long enough for sequence risk, shorter than Central’s harsh 30y@0.5% combo |
| Longer-retirement extension | **+5y → 33y** | +3→28 | +5→35 | Keep Central’s +5y educational stretch on a 28y base |
| Base real growth | **2.75%** | 3.00% | 2.50% | Halfway between Mild and Central |
| Weaker real growth | **1.00%** | 1.50% | 0.50% | Round 1 counterfactuals: 1.0–1.5% moved Plan B off Needs once classification improved |
| Early market decline | **-15%** | −12% | −20% | Mid stress; Round 1 −12% was soft, −20% harsh with old rules |
| Post-decline growth | **Resume base growth** | same | same | Isolates sequence risk from the weaker-growth test |
| Early-decline ordering | **Decline before first withdrawal** | same | same | Kept from Round 1; configurable (`earlyDeclineBeforeWithdrawal`) |
| Ending-balance ratio floor | **0.65** | 0.55 | 0.70 | Between Mild/Central for thin-cushion Noticeable calls |
| Longevity ratio floor | **0.88** | 0.55 | 0.70 | Stricter than general floor so +5y thinning can register (Plan F) |
| Earlier-than-base severe gap | **6y** | 7y | 5y | Intermediate |
| Late-depletion window | **final 20%** | 20% | 20% | Late depletion → Noticeable, not Severe |
| Absolute cushion (years of W) | **≥ 8y → Little** | same R2 | same R2 | Guards strong plans from ratio-only alarms |
| Absolute cushion (% of start) | **≥ 30% → Little** | same R2 | same R2 | Second absolute measure |

All values remain **provisional**.

---

## 2. Old versus revised classification rules

### 2.1 Per-scenario impact (Round 1 → Round 2)

| Topic | Round 1 (too blunt) | Round 2 (tested now) |
|---|---|---|
| Depletion while base lasts | **Always Severe** | **Timing-sensitive:** if depletion year ≥ late-window start (`floor(horizon×(1−lateFraction))+1`, default final **20%**) → **Noticeable**; earlier → **Severe** |
| Year-1 shortfall after decline | Severe | Severe (`severityKind=year1_shortfall`) |
| Much earlier than base | ≥N years earlier → Severe | Unchanged idea; configurable `earlierDepletionYears` |
| Lasts full horizon, low ratio vs base | Noticeable if ratio < floor | Same, **unless** absolute-cushion guard upgrades to Little |
| Absolute-cushion guard | None | If lasts full horizon and (ending/W ≥ `cushionYearsForLittle` **or** ending/start ≥ `cushionPctOfStartForLittle`) → **Little** even if ratio is low |
| Longer retirement | Ratio vs base-horizon end; longevity often invisible | Adds `longevityRatioFloor` (Hybrid 0.88), longevity cushion-short signal, late/early depletion timing, absolute cushion |

### 2.2 Exact Round 2 Severe conditions

Severe strain only when one of:

1. Cannot fund the first required withdrawal (after shock if any).
2. Depletes **before** the late-depletion window while the base path lasts (material early breakdown).
3. Depletes ≥ `earlierDepletionYears` earlier than the base path when base also depletes.
4. Longer-retirement path depletes well before the end of the longer horizon (not only in the final late window), or base already depletes with no recovery room.

**Not Severe:** reaching $0 only in the final ~20% of a long test while base lasts → **Noticeable**.

### 2.3 Overall aggregation (Round 2)

| Overall label | Rule |
|---|---|
| Holds up reasonably well | No Severe and at most one Noticeable |
| Sensitive to one or more risks | Exactly one Severe **or** two+ Noticeable |
| Needs meaningful adjustment | Two or more **genuine** Severes; or Phase 3 **difficult** + ≥1 genuine Severe |
| Workable near-4% guard | If Phase 3 workable, WR in [3.5%, 4.5%], and Needs would be driven without genuine early breakdown → cap at **Sensitive** |

`genuine` Severe excludes late-depletion-tagged Severes (late depletion is classified Noticeable in Round 2, so this is mainly a safety net).

### 2.4 Absolute-cushion recommendation

**Clearest educational measure: ending balance in years of annual withdrawals (`ending / W`).** Households understand “about 10 years of withdrawals left” better than “42% of the base ending balance.” Hybrid also uses % of starting savings as a second guard. Ratio-to-base remains useful for moderate cushions but no longer overrides a strong absolute cushion.

---

## 3. Compact overall-label matrix

| Plan | Expected | Mild | Central | Strict | **Hybrid R2** |
|------|----------|------|---------|--------|---------------|
| **A** | Holds up reasonably well | Holds up reasonably well | Holds up reasonably well | Holds up reasonably well | **Holds up reasonably well** |
| **B** | Holds or Sensitive | Holds up reasonably well | Sensitive | Needs meaningful adjustment | **Sensitive** |
| **C** | Holds up reasonably well | Holds up reasonably well | Holds up reasonably well | Sensitive | **Holds up reasonably well** |
| **D** | Sensitive or Needs | Needs meaningful adjustment | Needs meaningful adjustment | Needs meaningful adjustment | **Needs meaningful adjustment** |
| **E** | Holds up reasonably well | Holds up reasonably well | Holds up reasonably well | Holds up reasonably well | **Holds up reasonably well** |
| **F** | Holds or Sensitive | Holds up reasonably well | Sensitive | Sensitive | **Sensitive** |

---

## 4. Compact dominant-stress matrix

| Plan | Expected dominant | Mild | Central | Strict | **Hybrid R2** |
|------|-------------------|------|---------|--------|---------------|
| **A** | None dominated | None dominated | None dominated | None dominated | **None dominated** |
| **B** | Any | Weaker growth | Weaker growth | Early decline | **Weaker growth** |
| **C** | None dominated | None dominated | None dominated | Weaker growth | **None dominated** |
| **D** | Early decline | Longer retirement | Longer retirement | Early decline | **Longer retirement** |
| **E** | None dominated | None dominated | None dominated | None dominated | **None dominated** |
| **F** | Longer retirement | None dominated | Weaker growth | Early decline | **Weaker growth** |

---

## 5. Detailed Plan B comparison (near-4% boundary)

Phase 3: WR **4.00%**, workable. Round 1 Central → **Needs** (too harsh).

### Pack `mild` → **Holds up reasonably well** (dominant: Weaker long-term growth)

| Path | Ending | Years of W | Depletion | Classification | Kind |
|---|---:|---:|---|---|---|
| Base | $621,239 | 14.79 | lasts | reference | — |
| Weaker | $241,906 | 5.76 | lasts | **Noticeable strain** | `thin_relative_cushion` |
| Early | $357,423 | 8.51 | lasts | **Little change** | `absolute_cushion_guard` |
| Longer | $545,132 | 12.98 | lasts | **Little change** | `absolute_cushion_guard` |

- Flags: **Reasonable match**

### Pack `central` → **Sensitive** (dominant: Weaker long-term growth)

| Path | Ending | Years of W | Depletion | Classification | Kind |
|---|---:|---:|---|---|---|
| Base | $312,435 | 7.44 | lasts | reference | — |
| Weaker | $0 | 0.00 | year 27 | **Noticeable strain** | `late_depletion` |
| Early | $0 | 0.00 | year 28 | **Noticeable strain** | `late_depletion` |
| Longer | $127,206 | 3.03 | lasts | **Noticeable strain** | `thin_relative_cushion` |

- Flags: **Reasonable match**

### Pack `hybrid_r2` → **Sensitive** (dominant: Weaker long-term growth)

| Path | Ending | Years of W | Depletion | Classification | Kind |
|---|---:|---:|---|---|---|
| Base | $459,365 | 10.94 | lasts | reference | — |
| Weaker | $24,439 | 0.58 | lasts | **Noticeable strain** | `thin_relative_cushion` |
| Early | $122,721 | 2.92 | lasts | **Noticeable strain** | `thin_relative_cushion` |
| Longer | $298,125 | 7.10 | lasts | **Noticeable strain** | `thin_relative_cushion` |

- Flags: **Reasonable match**

### Pack `strict` → **Needs meaningful adjustment** (dominant: Early market decline)

| Path | Ending | Years of W | Depletion | Classification | Kind |
|---|---:|---:|---|---|---|
| Base | $0 | 0.00 | year 35 | reference | — |
| Weaker | $0 | 0.00 | year 25 | **Severe strain** | `much_earlier_than_base` |
| Early | $0 | 0.00 | year 22 | **Severe strain** | `much_earlier_than_base` |
| Longer | $0 | 0.00 | year 35 | **Severe strain** | `early_depletion` |

- Flags: **Too harsh**

**Verdict:** Under Hybrid Round 2, Plan B is **Sensitive** via three Noticeable stresses (thin cushions / no late $0 Severes). It does **not** become Needs solely from years 27–28 style late depletion. **More reasonable than Round 1 Central.**

---

## 6. Detailed Plan C comparison (false-alarm watch)

Round 1 Central → Sensitive (false alarm). Round 2:

### Pack `central` → **Holds up reasonably well**

| Path | Ending | Years of W | % of start | Classification | Kind |
|---|---:|---:|---:|---|---|
| Weaker | $803,244 | 14.87 | 37% | **Little change** | `absolute_cushion_guard` |
| Early | $1,261,704 | 23.36 | 57% | **Little change** | `absolute_cushion_guard` |
| Longer | $2,180,775 | 40.38 | 99% | **Little change** | `absolute_cushion_guard` |

- Flags: **Reasonable match**

### Pack `hybrid_r2` → **Holds up reasonably well**

| Path | Ending | Years of W | % of start | Classification | Kind |
|---|---:|---:|---:|---|---|
| Weaker | $1,154,519 | 21.38 | 53% | **Little change** | `absolute_cushion_guard` |
| Early | $1,702,074 | 31.52 | 77% | **Little change** | `absolute_cushion_guard` |
| Longer | $2,464,051 | 45.63 | 112% | **Little change** | `absolute_cushion_guard` |

- Flags: **Reasonable match**

**Diagnosis:** Absolute-cushion guard classifies lasting paths with ≥8 years of withdrawals (or ≥30% of start) as Little despite lower ratios vs an even larger base ending. Hybrid Plan C → **Holds** with no dominant stress. Strict remains Sensitive (harsh assumptions).

---

## 7. Plan D remains vulnerable

| Pack | Overall | W / E / L | Dominant | Flag |
|---|---|---|---|---|
| mild | **Needs meaningful adjustment** | noticeable / noticeable / severe | Longer retirement | Unexpected dominant stress |
| central | **Needs meaningful adjustment** | noticeable / noticeable / severe | Longer retirement | Unexpected dominant stress |
| strict | **Needs meaningful adjustment** | noticeable / severe / severe | Early market decline | Reasonable match |
| hybrid_r2 | **Needs meaningful adjustment** | noticeable / noticeable / severe | Longer retirement | Unexpected dominant stress |

**Confirmation:** No pack gives Plan D false comfort. Hybrid → **Needs** with Longer retirement Severe (`early_depletion` at year 15 of 33) plus Noticeable weaker/early paths. Dominant still often Longer rather than Early — remaining intuition mismatch, not false comfort.

---

## 8. Absolute-cushion guard analysis

| Plan (Hybrid) | Weak years of W | Early years of W | Guard fired? | Overall |
|---|---:|---:|---|---|
| A | 91.42 | 127.83 | Yes | Holds up reasonably well |
| B | 0.58 | 2.92 | No | Sensitive |
| C | 21.38 | 31.52 | Yes | Holds up reasonably well |
| D | 0.00 | 0.00 | No | Needs meaningful adjustment |
| E | 380.45 | 525.26 | Yes | Holds up reasonably well |
| F | 6.09 | 10.49 | Yes | Sensitive |

Guard protects A/C/E. Plan B still shows Noticeable thin cushions (years of W well below 8). Plan D never gets a lasting-path cushion pass.

---

## 9. Edge-case results & special-attention checks

| Expectation | Hybrid result | Status |
|---|---|---|
| A remains Holds | Holds | **Pass** |
| B Holds or Sensitive; not Needs from late depletion | Sensitive | **Pass** |
| C remains Holds unless clear distress | Holds | **Pass** |
| D Needs or clearly Sensitive | Needs | **Pass** |
| E remains Holds | Holds | **Pass** |
| F longevity useful sensitivity | Sensitive; Longer = Noticeable | **Pass (partial)** — longevity matters, but dominant is still Weaker growth |

### Full Hybrid fixture rows

#### Plan A — Holds up reasonably well

- Phase 3 WR 1.07%, workable
- Expected: `holds` / dominant `none`
- Dominant: No single stress dominated
- Flags: Reasonable match

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $1,515,699 | 157.89 | lasts | ref |
| Weaker | $877,638 | 91.42 | lasts | Little change |
| Early | $1,227,147 | 127.83 | lasts | Little change |
| Longer | $1,683,782 | 175.39 | lasts | Little change |

#### Plan B — Sensitive

- Phase 3 WR 4.00%, workable
- Expected: `holds_or_sensitive` / dominant `any`
- Dominant: Weaker long-term growth
- Flags: Reasonable match

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $459,365 | 10.94 | lasts | ref |
| Weaker | $24,439 | 0.58 | lasts | Noticeable strain |
| Early | $122,721 | 2.92 | lasts | Noticeable strain |
| Longer | $298,125 | 7.10 | lasts | Noticeable strain |

#### Plan C — Holds up reasonably well

- Phase 3 WR 2.45%, workable
- Expected: `holds` / dominant `none`
- Dominant: No single stress dominated
- Flags: Reasonable match

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $2,407,425 | 44.58 | lasts | ref |
| Weaker | $1,154,519 | 21.38 | lasts | Little change |
| Early | $1,702,074 | 31.52 | lasts | Little change |
| Longer | $2,464,051 | 45.63 | lasts | Little change |

#### Plan D — Needs meaningful adjustment

- Phase 3 WR 8.40%, difficult
- Expected: `sensitive_or_needs` / dominant `earlyDecline`
- Dominant: Longer retirement
- Flags: Unexpected dominant stress

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $0 | 0.00 | year 15 | ref |
| Weaker | $0 | 0.00 | year 13 | Noticeable strain |
| Early | $0 | 0.00 | year 12 | Noticeable strain |
| Longer | $0 | 0.00 | year 15 | Severe strain |

#### Plan E — Holds up reasonably well

- Phase 3 WR 0.32%, workable
- Expected: `holds` / dominant `none`
- Dominant: No single stress dominated
- Flags: Reasonable match

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $1,501,074 | 625.45 | lasts | ref |
| Weaker | $913,087 | 380.45 | lasts | Little change |
| Early | $1,260,613 | 525.26 | lasts | Little change |
| Longer | $1,706,113 | 710.88 | lasts | Little change |

#### Plan F — Sensitive

- Phase 3 WR 3.43%, workable
- Expected: `holds_or_sensitive` / dominant `longerRetirement`
- Dominant: Weaker long-term growth
- Flags: Unexpected dominant stress

| Path | End | YoW | Depletion | Class |
|---|---:|---:|---|---|
| Base | $952,474 | 19.84 | lasts | ref |
| Weaker | $292,189 | 6.09 | lasts | Noticeable strain |
| Early | $503,615 | 10.49 | lasts | Little change |
| Longer | $830,302 | 17.30 | lasts | Noticeable strain |

---

## 10. New unintended consequences

1. **Dominant stress still favors Weaker growth** for B/F even when Early/Longer are also Noticeable (tie-break by earlier depletion / worse ratio).
2. **Plan D dominant = Longer retirement** under Hybrid/Mild/Central — contradicts fixture prior (Early decline), though overall severity is appropriate.
3. **Strict + Round 2 rules:** A/E now Holds (cushion guard), but B remains Needs and C Sensitive — Strict scenario assumptions still extreme.
4. **Absolute cushion can mask relative stress** on very large portfolios if thresholds are too loose; current 8 years / 30% of start appears OK for A/C/E vs B.
5. **LongevityRatioFloor 0.88** is Hybrid-specific tuning that made Plan F’s longer path Noticeable — still provisional.

---

## 11. Round 1 vs Round 2 comparison

| Plan | R1 Central overall | R2 Central (new rules, old assumptions) | R2 Hybrid |
|---|---|---|---|
| A | Holds | Holds | Holds |
| B | **Needs** | **Sensitive** | **Sensitive** |
| C | **Sensitive** | **Holds** | **Holds** |
| D | Needs | Needs | Needs |
| E | Holds | Holds | Holds |
| F | Sensitive | Sensitive | Sensitive |

Classification-rule changes alone fixed Central B/C harshness. Hybrid adds intermediate scenario dials and stronger longevity sensitivity for F.

---

## 12. Provisional recommendation for the next step

**Do not declare final assumptions approved.**

Recommended direction: **treat Hybrid Round 2 + Round 2 classification as the leading candidate for independent formula review**, not yet as locked product math.

| Decision option | Guidance |
|---|---|
| Approve Hybrid as working baseline for implementation prototyping | Reasonable if stakeholders accept remaining dominant-stress quirks |
| Revise again (Round 3) | Only if Plan F must have Longer as dominant, or D must prefer Early decline as dominant |
| Independently validate formulas | **Recommended next** — review late-window math, cushion thresholds, longevityRatioFloor, and aggregation against a few real Phase 3 exports (Plan G when supplied) |

### Suggested next actions (awaiting approval)

1. Supply Plan G from a real Phase 3 snapshot into the harness (manual/configurable).
2. Independently validate Round 2 formulas (especially late-depletion window and absolute-cushion years).
3. Optionally tune most-important tie-break before UI copy depends on it.
4. Only after approval: freeze numbers into `PHASE_4_STRESS_TEST.md` and begin Phase 4 UI.

---

*End of Round 2 calibration report. No Round 3 auto-started. Engine revised for testing only.*