<?php
/**
 * Development-only Phase 4 calibration harness.
 * Not linked from Journey navigation. Do not treat as production UX.
 */
$page_title = 'Phase 4 Calibration Harness (dev)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="calibration.css">
</head>
<body>
    <div class="wrap">
        <div class="banner" role="status">
            <strong>Development only — not the Phase 4 product UI</strong>
            Calibration complete: Hybrid Round 2 is the provisional baseline (numbers still provisional).
            Not linked from Journey navigation. Do not treat as a public feature.
            Plan G never auto-reads browser localStorage and must not be hard-coded into committed fixtures.
        </div>

        <h1>Phase 4 Stress Test — Calibration Harness</h1>
        <p class="lede">
            Compare provisional assumption packs against representative retirement plans before locking
            Phase 4 mathematics. Answers whether results feel reasonable and educational to an
            experienced planner — not whether they are mathematically perfect.
        </p>

        <div class="grid-2">
            <section class="panel" aria-labelledby="pack-title">
                <h2 id="pack-title">Assumption pack</h2>
                <div class="field">
                    <label for="packSelect">Candidate pack</label>
                    <select id="packSelect">
                        <option value="hybrid_r2" selected>Hybrid Round 2</option>
                        <option value="mild">Mild pack</option>
                        <option value="central">Central pack (§18 hypotheses)</option>
                        <option value="strict">Strict pack</option>
                        <option value="custom">Custom (edit fields)</option>
                    </select>
                </div>
                <p class="pack-meta" id="packMeta"></p>

                <div class="field-row">
                    <div class="field">
                        <label for="packBaseYears">Base horizon (years)</label>
                        <input id="packBaseYears" type="number" min="1" step="1">
                    </div>
                    <div class="field">
                        <label for="packExtensionYears">Longevity extension (years)</label>
                        <input id="packExtensionYears" type="number" min="1" step="1">
                    </div>
                    <div class="field">
                        <label for="packEarlyDecline">Early decline (%)</label>
                        <input id="packEarlyDecline" type="number" step="1">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="packBaseGrowth">Base growth (% / yr, today’s $)</label>
                        <input id="packBaseGrowth" type="number" step="0.01">
                    </div>
                    <div class="field">
                        <label for="packWeakGrowth">Weaker growth (% / yr)</label>
                        <input id="packWeakGrowth" type="number" step="0.01">
                    </div>
                    <div class="field">
                        <label for="packRatioFloor">Ending-balance ratio floor</label>
                        <input id="packRatioFloor" type="number" min="0" max="1" step="0.01">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="packEarlierYears">Earlier-depletion severe (years)</label>
                        <input id="packEarlierYears" type="number" min="1" step="1">
                    </div>
                    <div class="field">
                        <label for="packDifficultBoost">&nbsp;</label>
                        <label style="display:flex;gap:8px;align-items:center;margin-top:8px;color:inherit;font-size:0.9rem;">
                            <input id="packDifficultBoost" type="checkbox" checked>
                            Phase 3 difficult + any severe → needs adjustment
                        </label>
                    </div>
                </div>
            </section>

            <section class="panel" aria-labelledby="plan-g-title">
                <h2 id="plan-g-title">Plan G — real Phase 3 case (configurable)</h2>
                <p class="hint">
                    Paste or type the <em>actual</em> Phase 3 values for this calibration session.
                    Nothing here is written into committed source files. localStorage is not read automatically.
                </p>
                <div class="field-row">
                    <div class="field">
                        <label for="gSpending">Monthly spending</label>
                        <input id="gSpending" type="number" min="0" step="1" placeholder="from Phase 3">
                    </div>
                    <div class="field">
                        <label for="gSs">Monthly Social Security</label>
                        <input id="gSs" type="number" min="0" step="1" placeholder="from Phase 3">
                    </div>
                    <div class="field">
                        <label for="gOther">Monthly other dependable income</label>
                        <input id="gOther" type="number" min="0" step="1" placeholder="0 if none">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="gBalance">Retirement savings balance</label>
                        <input id="gBalance" type="number" min="0" step="1" placeholder="from Phase 3">
                    </div>
                    <div class="field">
                        <label for="gExpectedOverall">Expected overall (judgment)</label>
                        <select id="gExpectedOverall">
                            <option value="holds_or_sensitive" selected>Holds or Sensitive</option>
                            <option value="holds">Holds up</option>
                            <option value="sensitive">Sensitive</option>
                            <option value="needs">Needs adjustment</option>
                            <option value="sensitive_or_needs">Sensitive or Needs</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="gExpectedDominant">Expected dominant stress</label>
                        <select id="gExpectedDominant">
                            <option value="earlyDecline" selected>Early market decline</option>
                            <option value="weakerGrowth">Weaker growth</option>
                            <option value="longerRetirement">Longer retirement</option>
                            <option value="none">None dominated</option>
                            <option value="any">Any / no strong prior</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="gJson">Optional: paste Phase 3-like JSON</label>
                    <textarea id="gJson" placeholder='{"monthlySpending":0,"monthlySocialSecurity":0,"monthlyOtherIncome":0,"savingsBalance":0}'></textarea>
                </div>
                <div class="actions">
                    <button type="button" id="applyPlanGBtn">Apply Plan G fields</button>
                    <button type="button" class="secondary" id="parseJsonBtn">Parse JSON into fields</button>
                    <button type="button" class="secondary" id="clearPlanGBtn">Clear Plan G</button>
                </div>
                <p class="hint" id="gPreview"></p>
            </section>
        </div>

        <section class="panel" aria-labelledby="fixtures-title">
            <h2 id="fixtures-title">Representative plans</h2>
            <p class="hint">A–F are illustrative fixtures. G enables only after you supply Phase 3 values for this session.</p>
            <div class="fixture-list" id="fixtureList"></div>
            <div class="actions">
                <button type="button" id="runBtn">Run calibration</button>
                <button type="button" class="secondary" id="exportBtn">Export JSON results</button>
            </div>
        </section>

        <section class="panel" aria-labelledby="results-title">
            <h2 id="results-title">Results vs planner judgment</h2>
            <div id="resultsHost"></div>
        </section>

        <section class="panel" aria-labelledby="checklist-title">
            <h2 id="checklist-title">Calibration questions (auto scoreboard)</h2>
            <div id="checklistHost"></div>
        </section>

        <section class="panel">
            <h2>Notes</h2>
            <ul class="hint">
                <li>Engine implements provisional Phase 4 concepts from the design doc; labels avoid safe / guaranteed / failure language.</li>
                <li>Official Phase 4 UI, Journey nav, Premium gating, Stripe, and production deploy are intentionally out of scope here.</li>
                <li>Framework reference: <code>docs/PHASE_4_CALIBRATION.md</code></li>
            </ul>
        </section>
    </div>

    <script src="phase4-provisional-engine.js"></script>
    <script src="calibration-data.js"></script>
    <script src="calibration-harness.js"></script>
</body>
</html>
