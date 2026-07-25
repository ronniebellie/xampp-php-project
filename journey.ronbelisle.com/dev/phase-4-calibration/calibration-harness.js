/**
 * Phase 4 calibration harness UI — development only.
 * Does not read Journey localStorage automatically.
 * Does not persist personal Plan G figures into source files.
 */
(function () {
    'use strict';

    var engine = window.Phase4ProvisionalEngine;
    var data = window.Phase4CalibrationData;

    var state = {
        fixtures: data.FIXTURES.map(function (f) {
            return Object.assign({}, f);
        }),
        packId: 'hybrid_r2',
        customPack: null,
        lastRuns: []
    };

    function $(id) {
        return document.getElementById(id);
    }

    function money(n) {
        if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
        return Number(n).toLocaleString(undefined, {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        });
    }

    function pct(n, digits) {
        if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
        return Number(n).toFixed(digits === undefined ? 2 : digits) + '%';
    }

    function ratePct(n) {
        if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
        return Number(n).toFixed(2) + '%';
    }

    function tagClass(kind, code) {
        return 'tag tag-' + code;
    }

    function currentPack() {
        if (state.packId === 'custom' && state.customPack) {
            return state.customPack;
        }
        var base = data.PACKS[state.packId] || data.PACKS.hybrid_r2;
        return readPackFields(base);
    }

    function readPackFields(fallback) {
        function num(id, key) {
            var el = $(id);
            if (!el || el.value === '') return fallback[key];
            return Number(el.value);
        }
        function pctToRate(id, key) {
            var el = $(id);
            if (!el || el.value === '') return fallback[key];
            return Number(el.value) / 100;
        }

        var baseYears = num('packBaseYears', 'baseHorizonYears');
        var extension = num('packExtensionYears', 'longerExtensionYears');

        return {
            id: state.packId === 'custom' ? 'custom' : fallback.id,
            name: state.packId === 'custom' ? 'Custom pack' : fallback.name,
            description: fallback.description || '',
            baseHorizonYears: baseYears,
            longerExtensionYears: extension,
            longerHorizonYears: baseYears + extension,
            baseGrowthRate: pctToRate('packBaseGrowth', 'baseGrowthRate'),
            weakerGrowthRate: pctToRate('packWeakGrowth', 'weakerGrowthRate'),
            earlyDeclinePct: num('packEarlyDecline', 'earlyDeclinePct'),
            endingBalanceRatioFloor: num('packRatioFloor', 'endingBalanceRatioFloor'),
            earlierDepletionYears: num('packEarlierYears', 'earlierDepletionYears'),
            lateDepletionFraction: fallback.lateDepletionFraction,
            difficultPlusAnySevereNeedsAdjustment: $('packDifficultBoost').checked
        };
    }

    function fillPackFields(pack) {
        $('packBaseYears').value = pack.baseHorizonYears;
        $('packExtensionYears').value = pack.longerExtensionYears;
        $('packBaseGrowth').value = (pack.baseGrowthRate * 100).toFixed(2);
        $('packWeakGrowth').value = (pack.weakerGrowthRate * 100).toFixed(2);
        $('packEarlyDecline').value = pack.earlyDeclinePct;
        $('packRatioFloor').value = pack.endingBalanceRatioFloor;
        $('packEarlierYears').value = pack.earlierDepletionYears;
        $('packDifficultBoost').checked = pack.difficultPlusAnySevereNeedsAdjustment !== false;
        $('packMeta').textContent = pack.description || '';
    }

    function renderFixtureList() {
        var host = $('fixtureList');
        host.innerHTML = '';
        state.fixtures.forEach(function (f, index) {
            var row = document.createElement('label');
            row.className = 'fixture-item' + (f.isConfigurable && !planGReady(f) ? ' disabled-note' : '');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = !!f.enabled && (!f.isConfigurable || planGReady(f));
            cb.disabled = f.isConfigurable && !planGReady(f);
            cb.addEventListener('change', function () {
                state.fixtures[index].enabled = cb.checked;
            });
            var text = document.createElement('div');
            var title = document.createElement('strong');
            title.textContent = f.name;
            var meta = document.createElement('span');
            if (f.isConfigurable) {
                meta.textContent = planGReady(f)
                    ? ('Configured for this session · expected ' + (f.expectedOverall || 'n/a'))
                    : 'Not configured — enter Phase 3 values below to enable';
            } else {
                var preview = engine.normalizePlan(f);
                meta.textContent = 'WR ' + ratePct(preview.phase3.ratePct) +
                    ' · Phase 3 ' + preview.phase3.code +
                    ' · ' + money(preview.annualFromSavings) + '/yr from savings';
            }
            text.appendChild(title);
            text.appendChild(meta);
            if (f.notes) {
                var note = document.createElement('span');
                note.style.display = 'block';
                note.textContent = f.notes;
                text.appendChild(note);
            }
            row.appendChild(cb);
            row.appendChild(text);
            host.appendChild(row);
            f.enabled = cb.checked;
        });
    }

    function planGReady(f) {
        return Number(f.savingsBalance) >= 0 &&
            f.monthlySpending !== '' && f.monthlySpending !== null &&
            Number.isFinite(Number(f.monthlySpending)) &&
            Number.isFinite(Number(f.monthlySocialSecurity)) &&
            Number.isFinite(Number(f.monthlyOtherIncome)) &&
            Number.isFinite(Number(f.savingsBalance));
    }

    function getPlanGIndex() {
        for (var i = 0; i < state.fixtures.length; i += 1) {
            if (state.fixtures[i].id === 'G') return i;
        }
        return -1;
    }

    function applyPlanGFromForm() {
        var idx = getPlanGIndex();
        if (idx < 0) return;
        var g = state.fixtures[idx];
        g.monthlySpending = $('gSpending').value === '' ? '' : Number($('gSpending').value);
        g.monthlySocialSecurity = $('gSs').value === '' ? '' : Number($('gSs').value);
        g.monthlyOtherIncome = $('gOther').value === '' ? '' : Number($('gOther').value);
        g.savingsBalance = $('gBalance').value === '' ? '' : Number($('gBalance').value);
        g.expectedOverall = $('gExpectedOverall').value || 'holds_or_sensitive';
        g.expectedDominant = $('gExpectedDominant').value || 'earlyDecline';
        g.enabled = planGReady(g);
        updatePlanGPreview();
        renderFixtureList();
    }

    function updatePlanGPreview() {
        var idx = getPlanGIndex();
        var el = $('gPreview');
        if (idx < 0) {
            el.textContent = '';
            return;
        }
        var g = state.fixtures[idx];
        if (!planGReady(g)) {
            el.textContent = 'Plan G inactive until spending, Social Security, other income, and savings balance are entered.';
            return;
        }
        var n = engine.normalizePlan(g);
        el.textContent = 'Session Plan G → from savings ' + money(n.monthlyFromSavings) + '/mo (' +
            money(n.annualFromSavings) + '/yr) · WR ' + ratePct(n.phase3.ratePct) +
            ' · Phase 3 ' + n.phase3.code + '. Values stay in this browser session only.';
    }

    function parsePhase3Json() {
        var raw = $('gJson').value.trim();
        if (!raw) {
            alert('Paste a Phase 3-like JSON object first.');
            return;
        }
        var obj;
        try {
            obj = JSON.parse(raw);
        } catch (err) {
            alert('JSON parse error: ' + err.message);
            return;
        }

        // Accept either flat fields or a Journey-style nested record.
        var spending = firstNumber(obj, [
            'monthlySpending',
            'monthly_spending',
            ['spending', 'monthly'],
            ['amounts', 'monthlySpending']
        ]);
        var ss = firstNumber(obj, [
            'monthlySocialSecurity',
            'socialSecurityMonthly',
            'ssMonthly',
            ['socialSecurity', 'monthly'],
            ['amounts', 'monthlySocialSecurity']
        ]);
        var other = firstNumber(obj, [
            'monthlyOtherIncome',
            'otherIncomeMonthly',
            ['otherIncome', 'monthly'],
            ['amounts', 'monthlyOtherIncome']
        ], 0);
        var balance = firstNumber(obj, [
            'savingsBalance',
            'retirementSavings',
            'balance',
            ['amounts', 'savingsBalance']
        ]);
        var monthlyNeed = firstNumber(obj, [
            'monthlyFromSavings',
            'monthlyNeededFromSavings',
            ['amounts', 'monthlyFromSavings']
        ], null);

        if (spending === null || ss === null || balance === null) {
            alert('Could not find monthlySpending, monthlySocialSecurity, and savingsBalance (or equivalents).');
            return;
        }

        $('gSpending').value = spending;
        $('gSs').value = ss;
        $('gOther').value = other === null ? 0 : other;
        $('gBalance').value = balance;

        var idx = getPlanGIndex();
        if (idx >= 0 && monthlyNeed !== null) {
            // If JSON supplies an explicit savings need, prefer reconstructing other income
            // so normalizePlan monthlyNeed matches: spending - ss - other = need
            var impliedOther = spending - ss - monthlyNeed;
            if (Number.isFinite(impliedOther)) {
                $('gOther').value = Math.max(0, impliedOther);
            }
        }

        applyPlanGFromForm();
    }

    function firstNumber(obj, paths, fallback) {
        for (var i = 0; i < paths.length; i += 1) {
            var path = paths[i];
            var value;
            if (Array.isArray(path)) {
                value = obj;
                for (var j = 0; j < path.length; j += 1) {
                    if (!value || typeof value !== 'object') {
                        value = undefined;
                        break;
                    }
                    value = value[path[j]];
                }
            } else {
                value = obj[path];
            }
            if (value !== undefined && value !== null && value !== '' && Number.isFinite(Number(value))) {
                return Number(value);
            }
        }
        return fallback === undefined ? null : fallback;
    }

    function clearPlanG() {
        $('gSpending').value = '';
        $('gSs').value = '';
        $('gOther').value = '';
        $('gBalance').value = '';
        $('gJson').value = '';
        applyPlanGFromForm();
    }

    function expectationMatch(expected, overallCode, dominantId) {
        if (!expected) return { overall: null, dominant: null };
        var overallOk = null;
        if (expected.overall === 'holds' || expected.overall === 'sensitive' || expected.overall === 'needs') {
            overallOk = expected.overall === overallCode;
        } else if (expected.overall === 'holds_or_sensitive') {
            overallOk = overallCode === 'holds' || overallCode === 'sensitive';
        } else if (expected.overall === 'sensitive_or_needs') {
            overallOk = overallCode === 'sensitive' || overallCode === 'needs';
        }

        var dominantOk = null;
        if (expected.dominant === 'none') {
            dominantOk = !dominantId;
        } else if (expected.dominant === 'any') {
            dominantOk = true;
        } else if (expected.dominant) {
            dominantOk = dominantId === expected.dominant;
        }

        return { overall: overallOk, dominant: dominantOk };
    }

    function runCalibration() {
        applyPlanGFromForm();
        var pack = currentPack();
        state.customPack = pack;
        var runs = [];
        state.fixtures.forEach(function (f) {
            if (!f.enabled) return;
            if (f.isConfigurable && !planGReady(f)) return;
            var run = engine.runStressTest(f, pack);
            run.flags = engine.judgmentFlags(run);
            run.expectation = expectationMatch(
                { overall: f.expectedOverall, dominant: f.expectedDominant },
                run.overall.code,
                run.mostImportant && run.mostImportant.id
            );
            runs.push(run);
        });
        state.lastRuns = runs;
        renderResults(runs, pack);
        renderChecklist(runs);
    }

    function scenarioCell(run, id) {
        var s = run.scenarios[id];
        var html = '<span class="' + tagClass('impact', s.impact.code) + '">' +
            escapeHtml(s.impact.label) + '</span>';
        html += '<div class="metrics">end ' + money(s.path.endingBalance);
        if (s.endingRatio !== null) {
            html += ' · vs base ' + (s.endingRatio * 100).toFixed(0) + '%';
        }
        if (s.path.depletedYear !== null) {
            html += ' · depletes y' + s.path.depletedYear;
        }
        html += '</div>';
        html += '<div class="metrics">' + escapeHtml(s.impact.reason) + '</div>';
        return html;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderResults(runs, pack) {
        var host = $('resultsHost');
        if (!runs.length) {
            host.innerHTML = '<p class="hint">No enabled plans to run. Configure Plan G or enable fixtures.</p>';
            return;
        }

        var html = '<p class="pack-meta"><strong>' + escapeHtml(pack.name) + '</strong> · base ' +
            pack.baseHorizonYears + 'y @ ' + (pack.baseGrowthRate * 100).toFixed(2) +
            '% · weak ' + (pack.weakerGrowthRate * 100).toFixed(2) +
            '% · early decline ' + pack.earlyDeclinePct +
            '% · longer ' + pack.longerHorizonYears +
            'y · ratio floor ' + pack.endingBalanceRatioFloor + '</p>';

        html += '<div style="overflow-x:auto"><table class="results"><thead><tr>';
        html += '<th>Plan</th><th>Phase 3</th><th>Overall</th><th>Most important</th>';
        html += '<th>Weaker growth</th><th>Early decline</th><th>Longer retirement</th><th>Flags</th>';
        html += '</tr></thead><tbody>';

        runs.forEach(function (run) {
            var p = run.plan;
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(p.name) + '</strong>';
            html += '<div class="metrics">Need ' + money(p.annualFromSavings) + '/yr · Bal ' +
                money(p.savingsBalance) + ' · WR ' + ratePct(p.phase3.ratePct) + '</div></td>';

            html += '<td><span class="' + tagClass('p3', p.phase3.code) + '">' +
                escapeHtml(p.phase3.code) + '</span></td>';

            html += '<td><span class="' + tagClass('overall', run.overall.code) + '">' +
                escapeHtml(run.overall.label) + '</span>';
            if (run.expectation.overall === false) {
                html += '<div class="metrics">vs expected: ' + escapeHtml(p.expectedOverall || '') + '</div>';
            }
            html += '</td>';

            var mi = run.mostImportant;
            html += '<td>' + escapeHtml(mi.name || '—');
            if (run.expectation.dominant === false) {
                html += '<div class="metrics">vs expected: ' + escapeHtml(p.expectedDominant || '') + '</div>';
            }
            html += '</td>';

            html += '<td>' + scenarioCell(run, 'weakerGrowth') + '</td>';
            html += '<td>' + scenarioCell(run, 'earlyDecline') + '</td>';
            html += '<td>' + scenarioCell(run, 'longerRetirement') + '</td>';

            html += '<td>';
            if (!run.flags.length) {
                html += '<span class="metrics">No auto flags</span>';
            } else {
                run.flags.forEach(function (flag) {
                    html += '<div class="flag flag-' + flag.severity + '">' + escapeHtml(flag.text) + '</div>';
                });
            }
            html += '</td>';

            html += '</tr>';
        });

        html += '</tbody></table></div>';
        host.innerHTML = html;
    }

    function renderChecklist(runs) {
        var host = $('checklistHost');
        if (!runs.length) {
            host.innerHTML = '';
            return;
        }

        function find(id) {
            for (var i = 0; i < runs.length; i += 1) {
                if (runs[i].plan.id === id) return runs[i];
            }
            return null;
        }

        var low = [find('A'), find('C'), find('E')].filter(Boolean);
        var high = find('D');
        var near4 = [find('B'), find('G')].filter(Boolean);
        var longF = find('F');

        var items = [];

        var lowCalm = low.length && low.every(function (r) { return r.overall.code === 'holds'; });
        items.push({
            ok: lowCalm,
            text: 'Low-withdrawal plans (A/C/E when present) are reassured (overall holds up) without using forbidden “safe/guaranteed” verdicts in the engine labels.'
        });

        items.push({
            ok: high ? (high.overall.code === 'sensitive' || high.overall.code === 'needs') : null,
            text: 'High-withdrawal Plan D is identified as vulnerable (sensitive or needs adjustment).'
        });

        var nuance = null;
        if (near4.length) {
            nuance = near4.some(function (r) {
                return r.overall.noticeableCount > 0 || r.overall.severeCount > 0 ||
                    r.overall.code === 'sensitive';
            });
        }
        items.push({
            ok: nuance,
            text: 'Plans near ~4% (B and/or configured G) show useful nuance (not all little-change + holds).'
        });

        var dominantOk = runs.filter(function (r) {
            return r.expectation.dominant !== false;
        }).length === runs.length;
        items.push({
            ok: dominantOk,
            text: 'Most-important stress generally matches fixture intuition (no dominant mismatches).'
        });

        var contradictions = runs.some(function (r) {
            return r.flags.some(function (f) {
                return f.code === 'harsh_vs_phase3' || f.code === 'soft_vs_phase3';
            });
        });
        items.push({
            ok: !contradictions,
            text: 'No harsh/soft contradictions vs Phase 3 workable/difficult.'
        });

        if (longF) {
            items.push({
                ok: longF.scenarios.longerRetirement.impact.code !== 'little' ||
                    longF.mostImportant.id === 'longerRetirement',
                text: 'Plan F longevity story: longer-retirement stress is material or dominant.'
            });
        }

        var html = '<ul class="summary-questions">';
        items.forEach(function (item) {
            var mark = item.ok === null ? '—' : (item.ok ? 'Yes' : 'No');
            var cls = item.ok === null ? '' : (item.ok ? 'tag-holds' : 'tag-needs');
            html += '<li><span class="tag ' + cls + '">' + mark + '</span> ' +
                escapeHtml(item.text) + '</li>';
        });
        html += '</ul>';
        host.innerHTML = html;
    }

    function exportJson() {
        var payload = {
            exportedAt: new Date().toISOString(),
            note: 'Calibration export for review. Plan G values included only if configured in this session.',
            pack: currentPack(),
            runs: state.lastRuns.map(function (run) {
                return {
                    planId: run.plan.id,
                    planName: run.plan.name,
                    source: run.plan.source,
                    phase3: run.plan.phase3,
                    monthlyFromSavings: run.plan.monthlyFromSavings,
                    annualFromSavings: run.plan.annualFromSavings,
                    savingsBalance: run.plan.savingsBalance,
                    withdrawalRatePct: run.plan.phase3.ratePct,
                    overall: run.overall,
                    mostImportant: run.mostImportant,
                    scenarios: run.scenarios,
                    flags: run.flags,
                    expectation: run.expectation,
                    // Include Plan G inputs only in-session export, not as committed fixtures
                    inputs: run.plan.isConfigurable ? {
                        monthlySpending: run.plan.monthlySpending,
                        monthlySocialSecurity: run.plan.monthlySocialSecurity,
                        monthlyOtherIncome: run.plan.monthlyOtherIncome,
                        savingsBalance: run.plan.savingsBalance
                    } : undefined
                };
            })
        };
        var blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'phase4-calibration-' + (currentPack().id || 'pack') + '.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    function onPackSelect() {
        var id = $('packSelect').value;
        state.packId = id;
        if (id === 'custom') {
            $('packMeta').textContent = 'Edit fields below; values stay provisional for this session.';
            return;
        }
        fillPackFields(data.PACKS[id]);
    }

    function bind() {
        $('packSelect').addEventListener('change', onPackSelect);
        $('runBtn').addEventListener('click', runCalibration);
        $('exportBtn').addEventListener('click', exportJson);
        $('applyPlanGBtn').addEventListener('click', applyPlanGFromForm);
        $('parseJsonBtn').addEventListener('click', parsePhase3Json);
        $('clearPlanGBtn').addEventListener('click', clearPlanG);
        ['gSpending', 'gSs', 'gOther', 'gBalance'].forEach(function (id) {
            $(id).addEventListener('change', applyPlanGFromForm);
        });

        // Mark pack as custom when fields edited after a preset load
        ['packBaseYears', 'packExtensionYears', 'packBaseGrowth', 'packWeakGrowth',
            'packEarlyDecline', 'packRatioFloor', 'packEarlierYears', 'packDifficultBoost'
        ].forEach(function (id) {
            $(id).addEventListener('change', function () {
                // Keep selected preset id but fields override on run via readPackFields
            });
        });
    }

    function init() {
        bind();
        $('packSelect').value = 'hybrid_r2';
        fillPackFields(data.PACKS.hybrid_r2);
        renderFixtureList();
        updatePlanGPreview();
        $('resultsHost').innerHTML = '<p class="hint">Choose a pack, optionally configure Plan G, then run calibration.</p>';
    }

    init();
}());
