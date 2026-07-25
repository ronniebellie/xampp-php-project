# Phase 4 Stress Test — Calibration Report (Round 1)

**Status:** Analysis only — provisional engine **not** retuned
**Generated:** 2026-07-24T19:53:51.797Z
**Scope:** Plans A–F × Mild / Central / Strict
**Plan G:** **Omitted** — no session Phase 3 values were supplied; none were inferred, retrieved, or hard-coded.
**Constraints honored:** No Journey UI/nav/Premium changes; official `PHASE_4_STRESS_TEST.md` untouched; no commit/push/deploy.

---

## 1. Assumption packs

| Pack | Base horizon | Longer | Base growth | Weaker growth | Early decline | Ratio floor | Earlier-depletion severe |
|---|---:|---:|---:|---:|---:|---:|---:|
| **Mild** | 25y | 28y | 3.00% | 1.50% | -12% | 0.55 | 7y |
| **Central** | 30y | 35y | 2.50% | 0.50% | -20% | 0.7 | 5y |
| **Strict** | 35y | 42y | 2.00% | 0.00% | -30% | 0.8 | 3y |

Shared year-step (current engine): start balance → withdraw `min(W, balance)` → apply growth. Early decline currently applies **before** year-1 withdrawal.

---

## 2. Fixture definitions (A–F)

| Plan | Persona | Spending / mo | SS / mo | Other / mo | Savings | Annual need | WR | Phase 3 | Expected overall | Expected dominant |
|---|---|---:|---:|---:|---:|---:|---:|---|---|---|
| **A** | conservative | $5,000 | $3,500 | $700 | $900,000 | $9,600 | 1.07% | workable | `holds` | `none` |
| **B** | average | $7,500 | $3,200 | $800 | $1,050,000 | $42,000 | 4.00% | workable | `holds_or_sensitive` | `any` |
| **C** | aggressive_saver | $8,000 | $2,800 | $700 | $2,200,000 | $54,000 | 2.45% | workable | `holds` | `none` |
| **D** | high_wr | $10,000 | $2,500 | $500 | $1,000,000 | $84,000 | 8.40% | difficult | `sensitive_or_needs` | `earlyDecline` |
| **E** | low_wr | $6,000 | $4,800 | $1,000 | $750,000 | $2,400 | 0.32% | workable | `holds` | `none` |
| **F** | long_horizon | $6,500 | $2,000 | $500 | $1,400,000 | $48,000 | 3.43% | workable | `holds_or_sensitive` | `longerRetirement` |

---

## 3. Compact comparison matrices

### 3.1 Overall resilience by pack

| Plan | Expected | Mild | Central | Strict |
|------|----------|------|---------|--------|
| **A** | holds | Holds up reasonably well | Holds up reasonably well | Sensitive |
| **B** | holds_or_sensitive | Holds up reasonably well | Needs meaningful adjustment | Needs meaningful adjustment |
| **C** | holds | Holds up reasonably well | Sensitive | Sensitive |
| **D** | sensitive_or_needs | Needs meaningful adjustment | Needs meaningful adjustment | Needs meaningful adjustment |
| **E** | holds | Holds up reasonably well | Holds up reasonably well | Sensitive |
| **F** | holds_or_sensitive | Holds up reasonably well | Sensitive | Needs meaningful adjustment |

### 3.2 Dominant stress by pack

| Plan | Expected dominant stress | Mild | Central | Strict |
|------|--------------------------|------|---------|--------|
| **A** | None dominated | None dominated | Weaker growth | Weaker growth |
| **B** | any | Weaker growth | Weaker growth | Early decline |
| **C** | None dominated | None dominated | Weaker growth | Weaker growth |
| **D** | Early decline | Longer retirement | Longer retirement | Early decline |
| **E** | None dominated | None dominated | Weaker growth | Weaker growth |
| **F** | Longer retirement | Weaker growth | Weaker growth | Early decline |

---

## 4. Full result tables (every fixture × pack)

### Plan A — Plan A — Conservative retiree

- **Phase 3:** WR 1.07%, assessment **workable**
- **Expected:** overall `holds`; dominant `none`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,523,891 | lasts full horizon | (reference) |
| Weaker growth | $1,012,917 | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Early decline | $1,297,763 (start after shock $792,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Longer retirement | $1,634,634 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 55% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=0)
- **Dominant stress:** No single stress dominated
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Reasonable match**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,455,808 | lasts full horizon | (reference) |
| Weaker growth | $733,822 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 50.4%). |
| Early decline | $1,078,246 (start after shock $720,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 70% of the base-growth reference. |
| Longer retirement | $1,595,391 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 70% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=1)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Unexpected dominant stress**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,310,355 | lasts full horizon | (reference) |
| Weaker growth | $564,000 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 43.0%). |
| Early decline | $770,384 (start after shock $630,000) | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 58.8%). |
| Longer retirement | $1,432,389 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 80% of the base-horizon ending balance. |

- **Overall:** Sensitive (severe=0, noticeable=2)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Too harsh · Unexpected dominant stress**

### Plan B — Plan B — Average / typical Journey retiree

- **Phase 3:** WR 4.00%, assessment **workable**
- **Expected:** overall `holds_or_sensitive`; dominant `any`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $621,239 | lasts full horizon | (reference) |
| Weaker growth | $241,906 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 55% of the base-growth reference (ratio 38.9%). |
| Early decline | $357,423 (start after shock $924,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Longer retirement | $545,132 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 55% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=1)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Reasonable match**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $312,435 | lasts full horizon | (reference) |
| Weaker growth | $0 | year 27 | **Severe strain** — Scenario depletes within its horizon while the base-growth reference does not. |
| Early decline | $0 (start after shock $840,000) | year 28 | **Severe strain** — Scenario depletes within its horizon while the base-growth reference does not. |
| Longer retirement | $127,206 | lasts | **Noticeable strain** — Savings last the longer horizon, but the ending cushion is thinner than 70% of the base-horizon ending balance. |

- **Overall:** Needs meaningful adjustment (severe=2, noticeable=1)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Too harsh**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $0 | year 35 | (reference) |
| Weaker growth | $0 | year 25 | **Severe strain** — Scenario depletes at least 3 years earlier than the base-growth reference. |
| Early decline | $0 (start after shock $735,000) | year 22 | **Severe strain** — Scenario depletes at least 3 years earlier than the base-growth reference. |
| Longer retirement | $0 | year 35 | **Severe strain** — Savings already deplete within the base horizon; a longer retirement adds no recovery room. |

- **Overall:** Needs meaningful adjustment (severe=3, noticeable=0)
- **Dominant stress:** Early market decline
- **Suggested adjustments:** Temporarily reduce spending after a market decline; Reduce planned spending; Increase retirement savings
- **Comparison flag(s):** **Too harsh**

### Plan C — Plan C — Aggressive saver

- **Phase 3:** WR 2.45%, assessment **workable**
- **Expected:** overall `holds`; dominant `none`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $2,578,447 | lasts full horizon | (reference) |
| Weaker growth | $1,544,325 | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Early decline | $2,025,690 (start after shock $1,936,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Longer retirement | $2,645,623 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 55% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=0)
- **Dominant stress:** No single stress dominated
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Reasonable match**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $2,184,634 | lasts full horizon | (reference) |
| Weaker growth | $803,244 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 36.8%). |
| Early decline | $1,261,704 (start after shock $1,760,000) | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 57.8%). |
| Longer retirement | $2,180,775 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 70% of the base-horizon ending balance. |

- **Overall:** Sensitive (severe=0, noticeable=2)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Too harsh · Unexpected dominant stress**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,646,061 | lasts full horizon | (reference) |
| Weaker growth | $310,000 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 18.8%). |
| Early decline | $326,134 (start after shock $1,540,000) | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 19.8%). |
| Longer retirement | $1,481,327 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 80% of the base-horizon ending balance. |

- **Overall:** Sensitive (severe=0, noticeable=2)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Too harsh · Unexpected dominant stress**

### Plan D — Plan D — High withdrawal rate

- **Phase 3:** WR 8.40%, assessment **difficult**
- **Expected:** overall `sensitive_or_needs`; dominant `earlyDecline`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $0 | year 15 | (reference) |
| Weaker growth | $0 | year 13 | **Noticeable strain** — Scenario depletes earlier than the base-growth reference (2 year(s)). |
| Early decline | $0 (start after shock $880,000) | year 13 | **Noticeable strain** — Scenario depletes earlier than the base-growth reference (2 year(s)). |
| Longer retirement | $0 | year 15 | **Severe strain** — Savings already deplete within the base horizon; a longer retirement adds no recovery room. |

- **Overall:** Needs meaningful adjustment (severe=1, noticeable=2)
- **Dominant stress:** Longer retirement
- **Suggested adjustments:** Delay retirement or withdrawals; Reduce planned spending; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Unexpected dominant stress**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $0 | year 14 | (reference) |
| Weaker growth | $0 | year 13 | **Noticeable strain** — Scenario depletes earlier than the base-growth reference (1 year(s)). |
| Early decline | $0 (start after shock $800,000) | year 11 | **Noticeable strain** — Scenario depletes earlier than the base-growth reference (3 year(s)). |
| Longer retirement | $0 | year 14 | **Severe strain** — Savings already deplete within the base horizon; a longer retirement adds no recovery room. |

- **Overall:** Needs meaningful adjustment (severe=1, noticeable=2)
- **Dominant stress:** Longer retirement
- **Suggested adjustments:** Delay retirement or withdrawals; Reduce planned spending; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Unexpected dominant stress**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $0 | year 14 | (reference) |
| Weaker growth | $0 | year 12 | **Noticeable strain** — Scenario depletes earlier than the base-growth reference (2 year(s)). |
| Early decline | $0 (start after shock $700,000) | year 10 | **Severe strain** — Scenario depletes at least 3 years earlier than the base-growth reference. |
| Longer retirement | $0 | year 14 | **Severe strain** — Savings already deplete within the base horizon; a longer retirement adds no recovery room. |

- **Overall:** Needs meaningful adjustment (severe=2, noticeable=1)
- **Dominant stress:** Early market decline
- **Suggested adjustments:** Temporarily reduce spending after a market decline; Reduce planned spending; Delay retirement or withdrawals
- **Comparison flag(s):** **Reasonable match**

### Plan E — Plan E — Low withdrawal rate

- **Phase 3:** WR 0.32%, assessment **workable**
- **Expected:** overall `holds`; dominant `none`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,480,206 | lasts full horizon | (reference) |
| Weaker growth | $1,014,975 | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Early decline | $1,291,766 (start after shock $660,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Longer retirement | $1,609,821 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 55% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=0)
- **Dominant stress:** No single stress dominated
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Reasonable match**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,465,175 | lasts full horizon | (reference) |
| Weaker growth | $793,191 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 54.1%). |
| Early decline | $1,150,540 (start after shock $600,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 70% of the base-growth reference. |
| Longer retirement | $1,644,780 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 70% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=1)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Unexpected dominant stress**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,377,531 | lasts full horizon | (reference) |
| Weaker growth | $666,000 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 48.3%). |
| Early decline | $927,556 (start after shock $525,000) | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 80% of the base-growth reference (ratio 67.3%). |
| Longer retirement | $1,564,151 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 80% of the base-horizon ending balance. |

- **Overall:** Sensitive (severe=0, noticeable=2)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Too harsh · Unexpected dominant stress**

### Plan F — Plan F — Very long retirement emphasis

- **Phase 3:** WR 3.43%, assessment **workable**
- **Expected:** overall `holds_or_sensitive`; dominant `longerRetirement`

#### Pack: **mild**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $1,128,743 | lasts full horizon | (reference) |
| Weaker growth | $566,653 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 55% of the base-growth reference (ratio 50.2%). |
| Early decline | $776,988 (start after shock $1,232,000) | lasts | **Little change** — Scenario lasts the full horizon and ending balance is ≥ 55% of the base-growth reference. |
| Longer retirement | $1,080,594 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 55% of the base-horizon ending balance. |

- **Overall:** Holds up reasonably well (severe=0, noticeable=1)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Keep the Phase 3 plan as-is and revisit it later
- **Comparison flag(s):** **Needs human review · Unexpected dominant stress**

#### Pack: **central**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $776,582 | lasts full horizon | (reference) |
| Weaker growth | $68,772 | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 8.9%). |
| Early decline | $189,263 (start after shock $1,120,000) | lasts | **Noticeable strain** — Scenario lasts, but ending balance is below 70% of the base-growth reference (ratio 24.4%). |
| Longer retirement | $620,019 | lasts | **Little change** — Savings last the longer horizon with an ending cushion still ≥ 70% of the base-horizon ending balance. |

- **Overall:** Sensitive (severe=0, noticeable=2)
- **Dominant stress:** Weaker long-term growth
- **Suggested adjustments:** Reduce planned spending; Increase retirement savings; Temporarily reduce spending after a market decline
- **Comparison flag(s):** **Needs human review · Unexpected dominant stress**

#### Pack: **strict**

| Path | Ending balance | Depletion | Classification |
|---|---:|---|---|
| Base reference | $352,116 | lasts full horizon | (reference) |
| Weaker growth | $0 | year 30 | **Severe strain** — Scenario depletes within its horizon while the base-growth reference does not. |
| Early decline | $0 (start after shock $980,000) | year 26 | **Severe strain** — Scenario depletes within its horizon while the base-growth reference does not. |
| Longer retirement | $40,488 | lasts | **Noticeable strain** — Savings last the longer horizon, but the ending cushion is thinner than 80% of the base-horizon ending balance. |

- **Overall:** Needs meaningful adjustment (severe=2, noticeable=1)
- **Dominant stress:** Early market decline
- **Suggested adjustments:** Temporarily reduce spending after a market decline; Reduce planned spending; Increase retirement savings
- **Comparison flag(s):** **Unexpected dominant stress**

---

## 5. Special-attention fixtures

### Plans A, C, E (false-alarm watch)

| Plan | Mild | Central | Strict | Notes |
|---|---|---|---|---|
| **A** | Holds up reasonably well | Holds up reasonably well | Sensitive | STRICT → Sensitive (review) |
| **C** | Holds up reasonably well | Sensitive | Sensitive | CENTRAL → Sensitive (review); STRICT → Sensitive (review) |
| **E** | Holds up reasonably well | Holds up reasonably well | Sensitive | STRICT → Sensitive (review) |

**Finding:** Mild keeps A/C/E calm. Central false-alarms **C** (Sensitive) via two Noticeable (weaker + early) even though WR≈2.45% and ending cushions remain large in absolute dollars. Strict false-alarms **A, C, and E**.

Root cause for Central C (and Central A/E “Unexpected dominant”): the **0.70 ending-balance ratio** vs base marks Weaker growth as Noticeable whenever weak-path ending is <70% of base ending — even if both endings are still very large. That is a classification-threshold effect, not depletion.

### Plan D (false-comfort watch)

| Pack | Overall | Dominant | Flag |
|---|---|---|---|
| mild | Needs meaningful adjustment | Longer retirement | Unexpected dominant stress |
| central | Needs meaningful adjustment | Longer retirement | Unexpected dominant stress |
| strict | Needs meaningful adjustment | Early market decline | Reasonable match |

**Finding:** No pack gives Plan D false comfort. All land **Needs meaningful adjustment**. Dominant stress is often **Longer retirement** (Mild/Central) rather than Early decline — unexpected vs fixture prior, but still a vulnerable verdict.

### Plan B (near-4% nuance)

| Pack | Overall | Impacts (W / E / L) | Dominant | Flag |
|---|---|---|---|---|
| mild | **Holds up reasonably well** | noticeable / little / little | Weaker long-term growth | Reasonable match |
| central | **Needs meaningful adjustment** | severe / severe / noticeable | Weaker long-term growth | Too harsh |
| strict | **Needs meaningful adjustment** | severe / severe / severe | Early market decline | Too harsh |

**Finding:** Mild shows useful nuance (one Noticeable, overall Holds). Central jumps to **Needs** — too harsh for a Phase 3 workable 4.00% plan. Strict is also Needs (three Severe).

### Plan F (longevity importance)

| Pack | Longer impact | Dominant | Longevity material? |
|---|---|---|---|
| mild | Little change | Weaker growth | **No — flag pack** |
| central | Little change | Weaker growth | **No — flag pack** |
| strict | Noticeable strain | Early decline | Partially (not dominant) |

**Finding:** Across all packs, Longer retirement never becomes the dominant stress for Plan F. Mild: longer = Little (longevity never matters — flag). Central/Strict: longer becomes Noticeable but Weaker growth / Early decline still dominate. The longevity story is weak under current longer-retirement classification.

---

## 6. Central-pack harshness diagnosis (Plan B)

### Intermediate numbers

| Item | Value |
|---|---|
| Starting balance B | $1,050,000 |
| Annual withdrawal W | $42,000 |
| Withdrawal rate | 4.00% |
| Phase 3 | workable (workable) |
| Base path (30y @ 2.5%) ending | $312,435; lasts full horizon |
| Weaker growth (30y @ 0.5%) ending | $0; depletes year **27** |
| Early decline (−20% then 30y @ 2.5%) | start after shock $840,000; ending $0; depletes year **28** |
| Longer retirement (35y @ 2.5%) ending | $127,206; lasts |
| Weak vs base ending ratio | 0.0% |
| Early vs base ending ratio | 0.0% |
| Long end / base end | 40.7% |
| Per-scenario impacts | weaker=**severe**, early=**severe**, longer=**noticeable** |
| Overall | **Needs meaningful adjustment** |
| Dominant | Weaker long-term growth |

### Which factor produces the harsh outcome?

**Combination — primarily scenario assumptions + per-scenario severity rule + aggregation; not Phase 3 interaction; not early-decline ordering.**

1. **Scenario assumptions (major):** At exactly 4% WR, Central’s 30-year / 0.5% weaker-growth path **depletes**, and −20% early-decline also **depletes** (year 28). Mild’s softer assumptions do not.
2. **Per-scenario severity thresholds (major):** Any depletion when base lasts is labeled **Severe**, including late depletion (year 28 of 30). The “noticeable if only in final 20% of horizon” rule is **unreachable** because the “depletes when base does not → severe” rule fires first.
3. **Overall aggregation (major amplifier):** `severe ≥ 2 → Needs`. Plan B gets two Severes → worst overall label automatically.
4. **Phase 3 label interaction:** **Not involved.** Phase 3 is workable; the “difficult + any severe → Needs” boost did not fire.
5. **Early-decline ordering:** **Not the driver.** Decline-before-withdrawal vs withdrawal-before-decline both still deplete year 28 and stay Severe for Plan B (see §7).

### Counterfactuals (Plan B, no engine change yet)

| Change | Overall | Impacts W/E/L |
|---|---|---|
| Central as-is | **needs** | severe / severe / noticeable |
| Weaker growth 1.0% instead of 0.5% | **needs** | severe / severe / noticeable |
| Weaker growth 1.5% | **sensitive** | noticeable / severe / noticeable |
| Early decline -15% instead of -20% | **needs** | severe / severe / noticeable |
| Early decline -12% | **sensitive** | severe / noticeable / noticeable |
| Horizon 25y / long 30y | **sensitive** | noticeable / noticeable / noticeable |
| Ratio floor 0.55 (does not help if depleted) | **needs** | severe / severe / noticeable |
| Mild pack assumptions on Plan B | **holds** | noticeable / little / little |

| Aggregation thought experiment | Result |
|---|---|
| Current impacts + current aggregation | **needs** |
| If earlyDecline forced to noticeable only | **sensitive** |
| If weakerGrowth forced to noticeable only | **sensitive** |
| If both severe→ but aggregation = one severe is Sensitive (hypothetical) | **needs** |
| Desired rule: exactly one severe OR two+ noticeable = Sensitive; two+ severe = Needs | **sensitive** — Under this revised rule, Plan B central (2 severe + 1 noticeable) would still be Needs. To get Sensitive, need to reduce to one severe. |
| Revised rule + treat “depletes only after year 25/30” as noticeable not severe | **sensitive** — If late depletion (e.g. earlyDecline y28 of 30) were noticeable, impacts become severe+noticeable+noticeable → Sensitive. |

### Better correction direction (provisional — do not implement yet)

Prefer a **blend**, not “pick Mild wholesale”:

1. **Softening scenario assumptions slightly** (highest leverage for Plan B): e.g. weaker growth nearer **1.0–1.5%**, and/or early decline nearer **−12% to −15%**, and/or base horizon **25–28 years**.
2. **Adjust per-scenario classification** so late depletion (final ~20% of horizon) while base lasts is **Noticeable**, not Severe — unless first-year funding breaks or depletion is much earlier.
3. **Revise aggregation** so **Needs** requires clearer distress (e.g. two Severes *after* late-depletion reclassification, or Phase 3 difficult + severe, or first-year failure) — and **one Severe + Noticeables → Sensitive**, which matches the educational goal for near-4% plans.
4. **Do not** rely on preventing “a single severe → Needs” alone: under current Central math Plan B has **two** Severes, so that change alone would not fix Central Plan B.

---

## 7. Early-decline ordering comparison

Central pack parameters. Other scenarios held fixed when recomputing overall.

| Plan | Ordering | End balance | Depletion | Early classification | Overall (recomputed) |
|---|---|---:|---|---|---|
| **B** | A. Decline before first withdrawal | $0 | y28 | severe | needs |
|  | B. Withdrawal before decline | $0 | y28 | severe | needs |
| **D** | A. Decline before first withdrawal | $0 | y11 | noticeable | needs |
|  | B. Withdrawal before decline | $0 | y11 | noticeable | needs |
| **E** | A. Decline before first withdrawal | $1,150,540 | none | little | holds |
|  | B. Withdrawal before decline | $1,151,547 | none | little | holds |

### Observation

- For **B** and **D**, ordering did **not** change depletion year, early classification, or overall label under Central.
- For **E** (low WR), ending balance differs only slightly (~$1k); both remain Little change / Holds.
- Ordering is therefore a **secondary** calibration issue right now; classification harshness dominates.

### Provisional ordering recommendation

Keep **A. Market decline before the first annual withdrawal** as the educational default: it matches the sequence-risk story (“markets fall as retirement withdrawals begin”) and matches the current design note. Treat B as a sensitivity check, not the v1 headline path. **Still provisional.**

---

## 8. Edge-case / questionable outcomes

| Issue | Where | Why it matters |
|---|---|---|
| Ratio rule false-alarms strong cushions | Central/Strict A,C,E weaker growth Noticeable | Absolute ending balances still large, but ratio < 0.70 vs base |
| Late depletion → Severe | Central B early decline y28/30 | Blocks the intended “final 20% = Noticeable” nuance |
| Two Severes → Needs | Central B | Turns a workable 4% plan into worst overall label |
| Longevity rarely dominant | Plan F all packs | Longer-retirement test under-delivers its story |
| Dominant stress ≠ early decline for D | Mild/Central D | Longer retirement wins; prior expected early decline |
| Mild may be slightly soft on F | Mild F Holds with longevity Little | Educational longevity stress invisible |

---

## 9. Greatest-influence dials (this round)

1. **Weaker-growth rate + base horizon** (whether ~4% paths deplete)
2. **“Depletes when base does not ⇒ Severe”** precedence over late-depletion Noticeable
3. **Aggregation: two Severes ⇒ Needs**
4. **Ending-balance ratio floor 0.70** (creates Noticeable on low-WR plans with huge cushions)
5. Early-decline % (matters, but ordering of decline vs withdrawal did not for B/D/E)
6. Phase 3 difficult boost (not implicated for Plan B)

---

## 10. Provisional recommendation for next calibration round

**Do not declare a winning pack.** Suggested next candidate (design-only until approved):

| Layer | Borrow from | Provisional idea |
|---|---|---|
| Scenario assumptions | Between Mild and Central | Base ~28y; weaker growth ~1.0–1.25%; early decline ~−15%; longer +5y |
| Per-scenario classification | Revise Central | Late depletion in final 20% of horizon while base lasts → **Noticeable** (not Severe), unless year-1 shortfall or ≥N years earlier |
| Absolute-cushion guard | New | If scenario ending balance still > e.g. 50–100% of starting B (or > N years of W), prefer Little/Noticeable over Severe for ratio-only hits |
| Aggregation | Revise Central | Needs if: two+ Severes **after** late-depletion fix, OR Phase 3 difficult + any Severe, OR year-1 failure; else one Severe or two+ Noticeable → Sensitive |
| Longevity classification | New attention | Ensure Plan F’s longer path can become Noticeable/dominant without relying only on thinner ending ratio vs base-horizon end |

### Formulas still needing independent review

1. Late-depletion vs “any depletion while base lasts” precedence
2. Longer-retirement comparison definition (vs base-horizon ending vs same-horizon same-growth — currently special-cased)
3. Whether today’s-dollar constant-W is the right educational simplification vs real Builder nominal path
4. Absolute vs relative cushion tests for low-WR plans
5. Most-important stress tie-break (currently favors earlier depletion / worse ratio; often picks Weaker growth over Early decline)

---

## Pack verdict by fixture (glance)

| Plan | Mild | Central | Strict |
|---|---|---|---|
| A | Balanced (calm) | Mostly calm; dominant quirk | **Too harsh** |
| B | **Best nuance** | **Too harsh** | Too harsh |
| C | Balanced | **Too harsh** | Too harsh |
| D | OK severity; dominant unexpected | OK severity; dominant unexpected | OK / matches prior better |
| E | Balanced | Calm overall; dominant quirk | **Too harsh** |
| F | Too soft on longevity | Mixed; longevity not dominant | Too harsh overall |

**Bottom line:** Mild is closest to planner intuition for A–E but under-plays longevity on F and may be slightly soft as a universal default. Central is the intended “hypothesis pack” but is too harsh at the 4% boundary and false-alarms C. Strict is too harsh for calibration targets. Next round should **compose** a hybrid — not crown a pack.

---

*End of Round 1 calibration report. Engine unchanged. Awaiting approval before retuning.*