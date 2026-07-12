<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';
$isLoggedIn = isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']);
$isPremium = has_premium_access();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Model Social Security claiming for both spouses. See what happens if one dies early — and whether the lower earner's delay actually pays off.">
    <title>Social Security Survivor Impact Calculator</title>
    <?php
    $og_title = $ld_name = 'Social Security Survivor Impact Calculator';
    $og_description = $ld_description = 'Did waiting for a bigger Social Security check actually pay off? Model both spouses and see how survivor benefits change household income.';
    include(__DIR__ . '/../includes/og-twitter-meta.php');
    include(__DIR__ . '/../includes/json-ld-softwareapp.php');
    ?>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .preset-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; }
        .preset-btn { padding: 8px 14px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .preset-btn:hover { background: #e0e7ff; border-color: #6366f1; }
        .spouse-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .spouse-section h3 { margin-top: 0; }
        .spouse-section.higher { border-left: 4px solid #2563eb; }
        .spouse-section.lower { border-left: 4px solid #7c3aed; }
        .hero-sentence { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 24px; margin: 24px 0; font-size: 1.15em; line-height: 1.6; color: #78350f; }
        .hero-sentence strong { color: #92400e; }
        .timeline-wrap { margin: 28px 0; }
        .timeline-row { margin-bottom: 20px; }
        .timeline-label { font-weight: 700; font-size: 14px; margin-bottom: 6px; color: #374151; }
        .timeline-bar { position: relative; height: 36px; background: #e5e7eb; border-radius: 8px; overflow: hidden; }
        .timeline-segment { position: absolute; top: 0; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; }
        .seg-both { background: #059669; }
        .seg-survivor { background: #2563eb; }
        .seg-none { background: #9ca3af; }
        .seg-own { background: #7c3aed; }
        .timeline-axis { display: flex; justify-content: space-between; font-size: 12px; color: #6b7280; margin-top: 4px; }
        .strategy-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .strategy-table th, .strategy-table td { padding: 10px 12px; border: 1px solid #e5e7eb; text-align: left; }
        .strategy-table th { background: #f1f5f9; }
        .strategy-table tr.best { background: #ecfdf5; }
        .cross-link { margin-top: 16px; padding: 14px; background: #eff6ff; border-radius: 8px; font-size: 14px; }
        .cross-link a { font-weight: 600; }
        .slider-row { margin-bottom: 14px; }
        .slider-label { display: flex; justify-content: space-between; font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        input[type="range"] { width: 100%; }
        .longevity-section { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin: 24px 0; }
        .longevity-section h3 { margin-top: 0; color: #166534; }
        .longevity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .longevity-person { background: #fff; border: 1px solid #d1fae5; border-radius: 8px; padding: 16px; }
        .longevity-person h4 { margin: 0 0 12px; font-size: 15px; }
        .actuarial-hint { font-size: 13px; color: #166534; margin-top: 8px; line-height: 1.5; }
        .override-panel { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #86efac; }
        .override-panel.hidden { display: none; }
        .longevity-badge { display: inline-block; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 999px; margin-left: 8px; }
    </style>
</head>
<body>

    <?php include('../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <?php include(__DIR__ . '/../includes/back-link-include.php'); ?>

        <header>
            <h1>Social Security Survivor Impact Calculator</h1>
            <p class="sub">Did waiting for a bigger check actually pay off? Model both spouses together and see how survivor benefits change lifetime household income.</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 24px;">
            <h2>Three rules couples often miss</h2>
            <ol style="margin: 0; padding-left: 20px; line-height: 1.7;">
                <li><strong>One check after death.</strong> The survivor receives the higher of the two benefits — no stacking.</li>
                <li><strong>The higher earner's delay usually matters most.</strong> Delayed credits pass through to the survivor benefit.</li>
                <li><strong>The lower earner's delay often doesn't.</strong> Years of forgone checks may buy nothing if the survivor benefit replaces their own.</li>
                <li><strong>Longevity is the hidden variable.</strong> Wives often outlive husbands by several years — which is a key reason planners recommend the higher earner delay to 70. The larger check may fund the survivor's remaining lifetime.</li>
            </ol>
            <p style="margin: 12px 0 0 0; font-size: 14px; color: #4b5563;">This tool models retirement benefits and survivor transitions only — not spousal benefits while both are alive, taxes, or the earnings test. Survivor benefits must be applied for separately.</p>
        </div>

        <div class="cross-link">
            Planning for one person? See individual break-even ages in the
            <a href="../social-security-claiming-analyzer/">Social Security Claiming Analyzer</a>.
            Pension annuity survivor gap is different — see the
            <a href="../survivor-gap/">Survivor Gap Calculator</a>.
        </div>

        <div class="preset-bar" style="margin-top: 20px;">
            <span style="font-weight: 600; align-self: center; margin-right: 6px;">Load preset:</span>
            <button type="button" class="preset-btn" data-preset="earlyDeathDelayLost" title="Both wait until 70; he dies at 71 — her delay buys almost nothing, survivor benefit replaces her own check">Early death — delay didn't pay off</button>
            <button type="button" class="preset-btn" data-preset="actuarialTypical">Typical couple (actuarial)</button>
            <button type="button" class="preset-btn" data-preset="longLife">Both live to 95</button>
            <button type="button" class="preset-btn" data-preset="earlyDeath">Higher earner dies at 75</button>
        </div>

<?php if ($isPremium): ?>
        <div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #22543d;">Premium Features</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
                <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
                <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">Compare Scenarios</button>
                <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with key results, chart, and year-by-year table">Download PDF</button>
                <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year household income for Excel">Export CSV</button>
                <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
            </div>
            <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
                <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side.
                <strong>PDF</strong> — Full report with chart and strategy comparison. <strong>CSV</strong> — Spreadsheet data.
                <strong>Explain</strong> — AI plain-language analysis (below, after you run the analysis).
            </p>
        </div>
<?php endif; ?>

        <form id="survivorForm">
            <div class="spouse-section higher">
                <h3>Higher earner (sets survivor floor)</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label for="higherBirthYear" class="field-label">Birth year</label>
                        <select id="higherBirthYear" style="width: 100%; padding: 8px;">
                            <?php for ($y = (int)date('Y'); $y >= 1920; $y--): ?>
                            <option value="<?php echo $y; ?>"<?php echo $y === 1958 ? ' selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="higherPIA" class="field-label">Monthly benefit at FRA ($)</label>
                        <input type="number" id="higherPIA" min="0" step="any" value="3890" required style="width: 100%; padding: 8px;">
                        <small id="higherFraHint" style="color: #666;">FRA: —</small>
                    </div>
                    <div>
                        <label for="higherClaimAge" class="field-label">Claiming age</label>
                        <input type="number" id="higherClaimAge" min="62" max="70" value="70" required style="width: 100%; padding: 8px;">
                    </div>
                </div>
            </div>

            <div class="spouse-section lower">
                <h3>Lower earner</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label for="lowerBirthYear" class="field-label">Birth year</label>
                        <select id="lowerBirthYear" style="width: 100%; padding: 8px;">
                            <?php for ($y = (int)date('Y'); $y >= 1920; $y--): ?>
                            <option value="<?php echo $y; ?>"<?php echo $y === 1958 ? ' selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="lowerPIA" class="field-label">Monthly benefit at FRA ($)</label>
                        <input type="number" id="lowerPIA" min="0" step="any" value="2480" required style="width: 100%; padding: 8px;">
                        <small id="lowerFraHint" style="color: #666;">FRA: —</small>
                    </div>
                    <div>
                        <label for="lowerClaimAge" class="field-label">Claiming age</label>
                        <input type="number" id="lowerClaimAge" min="62" max="70" value="62" required style="width: 100%; padding: 8px;">
                    </div>
                </div>
            </div>

            <div class="longevity-section">
                <h3>Longevity assumptions <span class="longevity-badge">SSA 2021 period life table</span></h3>
                <p style="margin: 0 0 16px; color: #374151; font-size: 14px; line-height: 1.6;">
                    Sex and current age are used <em>only</em> for longevity modeling — not for benefit amounts.
                    Default death ages come from actuarial life expectancy. Override if your health or family history suggests a different planning horizon.
                </p>
                <div class="longevity-grid">
                    <div class="longevity-person">
                        <h4>Higher earner</h4>
                        <div style="margin-bottom: 12px;">
                            <label for="higherSex" class="field-label">Sex</label>
                            <select id="higherSex" style="width: 100%; padding: 8px;">
                                <option value="male" selected>Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label for="higherCurrentAge" class="field-label">Current age</label>
                            <input type="number" id="higherCurrentAge" min="22" max="100" value="68" style="width: 100%; padding: 8px;">
                            <small style="color: #666;">Auto-updated from birth year; edit if needed</small>
                        </div>
                        <div class="actuarial-hint" id="higherLongevityHint">Actuarial death age: —</div>
                    </div>
                    <div class="longevity-person">
                        <h4>Lower earner</h4>
                        <div style="margin-bottom: 12px;">
                            <label for="lowerSex" class="field-label">Sex</label>
                            <select id="lowerSex" style="width: 100%; padding: 8px;">
                                <option value="male">Male</option>
                                <option value="female" selected>Female</option>
                            </select>
                        </div>
                        <div>
                            <label for="lowerCurrentAge" class="field-label">Current age</label>
                            <input type="number" id="lowerCurrentAge" min="22" max="100" value="68" style="width: 100%; padding: 8px;">
                            <small style="color: #666;">Auto-updated from birth year; edit if needed</small>
                        </div>
                        <div class="actuarial-hint" id="lowerLongevityHint">Actuarial death age: —</div>
                    </div>
                </div>
                <div style="margin-top: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" id="overrideLongevity" style="margin-top: 4px;">
                        <span>Advanced: override actuarial assumptions with custom death ages</span>
                    </label>
                </div>
                <div id="overridePanel" class="override-panel hidden">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                        <div class="slider-row">
                            <div class="slider-label"><span>Higher earner death age</span><span id="higherDeathAgeLabel">84</span></div>
                            <input type="range" id="higherDeathAge" min="65" max="100" value="84">
                        </div>
                        <div class="slider-row">
                            <div class="slider-label"><span>Lower earner death age</span><span id="lowerDeathAgeLabel">86</span></div>
                            <input type="range" id="lowerDeathAge" min="65" max="100" value="86">
                        </div>
                    </div>
                    <p style="margin: 12px 0 0; font-size: 13px; color: #6b7280;">Use this for what-if scenarios (e.g., early death) or when you have a specific planning age from your advisor or family history.</p>
                </div>
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #86efac;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; align-items: start;">
                        <div>
                            <label for="colaRate" class="field-label">Annual Social Security COLA (%)</label>
                            <input type="number" id="colaRate" min="0" max="10" step="any" value="2.8" style="width: 100%; padding: 8px;">
                            <small style="color: #166534; display: block; margin-top: 6px; line-height: 1.5;">
                                Applied to both spouses while receiving benefits. After one spouse dies, the survivor’s check continues to receive COLA — so a larger delayed benefit from the higher earner compounds over a long survivor period.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Other assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label for="lowerEarlyCompareAge" class="field-label">Delay comparison age (lower earner)</label>
                    <input type="number" id="lowerEarlyCompareAge" min="62" max="70" value="67" style="width: 100%; padding: 8px;">
                    <small style="color: #666;">Only used if the lower earner waits past this age — defaults to FRA</small>
                </div>
                <div>
                    <label for="discountRate" class="field-label">Discount rate (%) — optional</label>
                    <input type="number" id="discountRate" min="0" max="10" step="any" value="0" style="width: 100%; padding: 8px;">
                    <small style="color: #666;">0 = nominal dollars</small>
                </div>
            </div>

            <div style="text-align: center; margin: 24px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Run analysis</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Household Social Security Analysis</h2>

            <div id="heroSentence" class="hero-sentence"></div>

            <div class="summary-grid" id="summaryCards"></div>

            <div class="timeline-wrap" id="timelineVisual"></div>

            <div class="chart-section">
                <h3>Household Social Security income over time</h3>
                <div class="chart-wrapper">
                    <canvas id="householdChart"></canvas>
                </div>
            </div>

            <div class="info-box info-box-blue" id="interpretation"></div>

            <div class="table-section">
                <h3>Compare claiming strategies</h3>
                <p style="color: #666; font-size: 14px;">Same longevity assumptions — different claiming ages.</p>
                <div class="table-wrapper">
                    <table class="strategy-table" id="strategyTable">
                        <thead>
                            <tr>
                                <th>Strategy</th>
                                <th>Higher claims</th>
                                <th>Lower claims</th>
                                <th>Lifetime household SS</th>
                                <th>Before first death</th>
                                <th>After first death</th>
                            </tr>
                        </thead>
                        <tbody id="strategyBody"></tbody>
                    </table>
                </div>
                <div id="strategyInsight" class="info-box info-box-blue" style="display: none; margin-top: 16px; font-size: 14px; line-height: 1.6;"></div>
            </div>

            <?php if ($isPremium): ?>
            <div class="explain-results-block" style="margin: 24px 0; padding: 24px; background: #f0fdf4; border: 2px solid #0d9488; border-radius: 12px;">
                <button type="button" id="explainResultsBtn" class="btn-primary" style="background: #0d9488; color: white; font-size: 16px; padding: 14px 28px; font-weight: 700;">Explain my results</button>
                <p style="margin: 12px 0 0 0; font-size: 15px; color: #166534;">Get AI-generated plain-language explanations of your specific results.</p>
            </div>
            <?php endif; ?>

            <?php
            $share_title = 'Social Security Survivor Impact Calculator';
            $share_text = 'See how survivor benefits affect household Social Security income — did waiting to claim actually pay off?';
            include(__DIR__ . '/../includes/share-results-block.php');
            ?>
        </div>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Save & Explain Your Couples Strategy';
        $premium_upsell_text = 'Upgrade to Premium to save scenarios, compare strategies, download PDF reports, export CSV data, and get AI-generated plain-language explanations.';
        include(__DIR__ . '/../includes/premium-upsell-banner.php');
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/lib/finance-core.js"></script>
    <script src="../js/lib/actuarial-longevity.js"></script>
    <script src="../js/lib/ss-household-core.js"></script>
    <script src="../js/share-results.js"></script>
    <script src="../js/explain-results-modal.js"></script>
    <script src="calculator.js"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/calculator-footer.php'; ?>
</body>
</html>
