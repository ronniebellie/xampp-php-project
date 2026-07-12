// Social Security Survivor Impact Calculator

const SSI_API_BASE = (function () {
    const path = window.location.pathname;
    const match = path.match(/^(.*\/)ss-survivor-impact\/?/);
    const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
    return window.location.origin + basePath;
})();

const PRESETS = {
    earlyDeathDelayLost: {
        higherBirthYear: 1958,
        higherPIA: 3890,
        higherClaimAge: 70,
        higherSex: 'male',
        lowerBirthYear: 1958,
        lowerPIA: 2480,
        lowerClaimAge: 70,
        lowerSex: 'female',
        overrideLongevity: true,
        higherDeathAge: 71,
        lowerDeathAge: 95,
        lowerEarlyCompareAge: 65,
        colaRate: 2.8
    },
    actuarialTypical: {
        higherBirthYear: 1958,
        higherPIA: 3890,
        higherClaimAge: 70,
        higherSex: 'male',
        lowerBirthYear: 1958,
        lowerPIA: 2480,
        lowerClaimAge: 62,
        lowerSex: 'female',
        overrideLongevity: false,
        lowerEarlyCompareAge: 67,
        colaRate: 2.8
    },
    longLife: {
        higherBirthYear: 1958,
        higherPIA: 3890,
        higherClaimAge: 68,
        higherSex: 'male',
        lowerBirthYear: 1958,
        lowerPIA: 2480,
        lowerClaimAge: 70,
        lowerSex: 'female',
        overrideLongevity: true,
        higherDeathAge: 95,
        lowerDeathAge: 95,
        lowerEarlyCompareAge: 65,
        colaRate: 2.8
    },
    earlyDeath: {
        higherBirthYear: 1958,
        higherPIA: 3890,
        higherClaimAge: 68,
        higherSex: 'male',
        lowerBirthYear: 1958,
        lowerPIA: 2480,
        lowerClaimAge: 70,
        lowerSex: 'female',
        overrideLongevity: true,
        higherDeathAge: 75,
        lowerDeathAge: 95,
        lowerEarlyCompareAge: 65,
        colaRate: 2.8
    }
};

var ageSyncedFromBirthYear = { higher: true, lower: true };

function formatCurrency(amount) {
    return RBFinance.formatCurrency(amount);
}

function syncFraHints() {
    ['higher', 'lower'].forEach(function (which) {
        var birthYear = parseInt(document.getElementById(which + 'BirthYear').value, 10);
        var fra = RBFinance.getFRA(birthYear);
        var hint = document.getElementById(which + 'FraHint');
        if (hint) {
            hint.textContent = 'FRA: ' + fra.years + (fra.months > 0 ? ' + ' + fra.months + ' mo' : '') +
                ' (age ' + RBFinance.fraAgeFromBirthYear(birthYear).toFixed(1).replace(/\.0$/, '') + ')';
        }
    });
    syncLowerEarlyCompareFromFra();
}

function syncLowerEarlyCompareFromFra() {
    var lowerBirthYear = parseInt(document.getElementById('lowerBirthYear').value, 10);
    var compareEl = document.getElementById('lowerEarlyCompareAge');
    if (!compareEl || compareEl.dataset.userEdited === 'true') return;
    compareEl.value = Math.round(RBFinance.fraAgeFromBirthYear(lowerBirthYear));
}

function syncCurrentAgeFromBirthYear(which) {
    if (!ageSyncedFromBirthYear[which]) return;
    var birthYearEl = document.getElementById(which + 'BirthYear');
    var ageEl = document.getElementById(which + 'CurrentAge');
    if (birthYearEl && ageEl) {
        ageEl.value = RBActuarial.ageFromBirthYear(parseInt(birthYearEl.value, 10));
    }
}

function resolveDeathAgesFromData(data) {
    var override = data.overrideLongevity === true || data.overrideLongevity === 'true' || data.overrideLongevity === 'on';
    var higherAge = parseInt(data.higherCurrentAge, 10);
    var lowerAge = parseInt(data.lowerCurrentAge, 10);
    var higherSex = data.higherSex || 'male';
    var lowerSex = data.lowerSex || 'female';

    if (override) {
        return {
            higherDeathAge: parseInt(data.higherDeathAge, 10),
            lowerDeathAge: parseInt(data.lowerDeathAge, 10),
            source: 'custom'
        };
    }
    return {
        higherDeathAge: RBActuarial.getActuarialDeathAge(higherSex, higherAge),
        lowerDeathAge: RBActuarial.getActuarialDeathAge(lowerSex, lowerAge),
        source: 'actuarial'
    };
}

function buildOptsFromSavedData(data) {
    var deaths = resolveDeathAgesFromData(data);
    return {
        higherEarner: {
            birthYear: parseInt(data.higherBirthYear, 10),
            pia: parseFloat(data.higherPIA),
            claimAge: parseInt(data.higherClaimAge, 10),
            deathAge: deaths.higherDeathAge,
            sex: data.higherSex || 'male',
            currentAge: parseInt(data.higherCurrentAge, 10)
        },
        lowerEarner: {
            birthYear: parseInt(data.lowerBirthYear, 10),
            pia: parseFloat(data.lowerPIA),
            claimAge: parseInt(data.lowerClaimAge, 10),
            deathAge: deaths.lowerDeathAge,
            sex: data.lowerSex || 'female',
            currentAge: parseInt(data.lowerCurrentAge, 10)
        },
        colaRate: parseFloat(data.colaRate) || 0,
        discountRate: parseFloat(data.discountRate) || 0,
        lowerEarlyCompareAge: parseInt(data.lowerEarlyCompareAge, 10),
        longevitySource: deaths.source
    };
}

function resolveDeathAges() {
    var override = document.getElementById('overrideLongevity').checked;
    var higherAge = parseInt(document.getElementById('higherCurrentAge').value, 10);
    var lowerAge = parseInt(document.getElementById('lowerCurrentAge').value, 10);
    var higherSex = document.getElementById('higherSex').value;
    var lowerSex = document.getElementById('lowerSex').value;

    if (override) {
        return {
            higherDeathAge: parseInt(document.getElementById('higherDeathAge').value, 10),
            lowerDeathAge: parseInt(document.getElementById('lowerDeathAge').value, 10),
            source: 'custom'
        };
    }

    return {
        higherDeathAge: RBActuarial.getActuarialDeathAge(higherSex, higherAge),
        lowerDeathAge: RBActuarial.getActuarialDeathAge(lowerSex, lowerAge),
        source: 'actuarial'
    };
}

function updateLongevityHints() {
    var higherAge = parseInt(document.getElementById('higherCurrentAge').value, 10);
    var lowerAge = parseInt(document.getElementById('lowerCurrentAge').value, 10);
    var higherSex = document.getElementById('higherSex').value;
    var lowerSex = document.getElementById('lowerSex').value;
    var override = document.getElementById('overrideLongevity').checked;

    var hRemaining = RBActuarial.getRemainingLifeExpectancy(higherSex, higherAge);
    var lRemaining = RBActuarial.getRemainingLifeExpectancy(lowerSex, lowerAge);
    var hDeath = RBActuarial.getActuarialDeathAge(higherSex, higherAge);
    var lDeath = RBActuarial.getActuarialDeathAge(lowerSex, lowerAge);

    document.getElementById('higherLongevityHint').innerHTML =
        'Life expectancy: ~' + hRemaining.toFixed(1) + ' more years → planning death age <strong>' + hDeath + '</strong>';
    document.getElementById('lowerLongevityHint').innerHTML =
        'Life expectancy: ~' + lRemaining.toFixed(1) + ' more years → planning death age <strong>' + lDeath + '</strong>';

    if (!override) {
        document.getElementById('higherDeathAge').value = hDeath;
        document.getElementById('lowerDeathAge').value = lDeath;
    }
    syncDeathLabels();
}

function getFormInputs() {
    var deaths = resolveDeathAges();
    return {
        higherEarner: {
            birthYear: parseInt(document.getElementById('higherBirthYear').value, 10),
            pia: parseFloat(document.getElementById('higherPIA').value),
            claimAge: parseInt(document.getElementById('higherClaimAge').value, 10),
            deathAge: deaths.higherDeathAge,
            sex: document.getElementById('higherSex').value,
            currentAge: parseInt(document.getElementById('higherCurrentAge').value, 10)
        },
        lowerEarner: {
            birthYear: parseInt(document.getElementById('lowerBirthYear').value, 10),
            pia: parseFloat(document.getElementById('lowerPIA').value),
            claimAge: parseInt(document.getElementById('lowerClaimAge').value, 10),
            deathAge: deaths.lowerDeathAge,
            sex: document.getElementById('lowerSex').value,
            currentAge: parseInt(document.getElementById('lowerCurrentAge').value, 10)
        },
        colaRate: parseFloat(document.getElementById('colaRate').value) || 0,
        discountRate: parseFloat(document.getElementById('discountRate').value) || 0,
        lowerEarlyCompareAge: parseInt(document.getElementById('lowerEarlyCompareAge').value, 10),
        longevitySource: deaths.source
    };
}

function applyPreset(name) {
    var p = PRESETS[name];
    if (!p) return;
    ageSyncedFromBirthYear.higher = true;
    ageSyncedFromBirthYear.lower = true;
    Object.keys(p).forEach(function (key) {
        var el = document.getElementById(key);
        if (el) {
            if (el.type === 'checkbox') {
                el.checked = !!p[key];
            } else {
                el.value = p[key];
            }
        }
    });
    syncCurrentAgeFromBirthYear('higher');
    syncCurrentAgeFromBirthYear('lower');
    syncFraHints();
    toggleOverridePanel();
    updateLongevityHints();
    document.getElementById('survivorForm').dispatchEvent(new Event('submit', { cancelable: true }));
}

function syncDeathLabels() {
    document.getElementById('higherDeathAgeLabel').textContent = document.getElementById('higherDeathAge').value;
    document.getElementById('lowerDeathAgeLabel').textContent = document.getElementById('lowerDeathAge').value;
}

function toggleOverridePanel() {
    var panel = document.getElementById('overridePanel');
    var checked = document.getElementById('overrideLongevity').checked;
    panel.classList.toggle('hidden', !checked);
    updateLongevityHints();
}

function buildLongevityNote(opts, result) {
    var h = opts.higherEarner;
    var l = opts.lowerEarner;
    var hRem = RBActuarial.getRemainingLifeExpectancy(h.sex, h.currentAge);
    var lRem = RBActuarial.getRemainingLifeExpectancy(l.sex, l.currentAge);
    var gap = l.deathAge - h.deathAge;
    var note = '';

    if (opts.longevitySource === 'actuarial') {
        note += '<li><strong>Longevity assumptions:</strong> SSA 2021 period life table — higher earner (' +
            (h.sex === 'female' ? 'female' : 'male') + ', age ' + h.currentAge + ') to age ' + h.deathAge +
            '; lower earner (' + (l.sex === 'female' ? 'female' : 'male') + ', age ' + l.currentAge + ') to age ' + l.deathAge + '.</li>';
    } else {
        note += '<li><strong>Longevity assumptions:</strong> Custom death ages — higher earner to age ' + h.deathAge +
            ', lower earner to age ' + l.deathAge + '.</li>';
    }

    if (result.firstDeathWho === 'higher' && gap > 3) {
        note += '<li><strong>Survivor years matter:</strong> The lower earner is modeled to outlive the higher earner by about ' +
            gap + ' years. Household income after the first death (' + formatCurrency(result.afterFirstDeath) +
            ' total) depends heavily on the higher earner\'s benefit as the survivor floor — a key reason planners often want the higher earner to delay.</li>';
    } else if (result.firstDeathWho === 'higher' && gap <= 3) {
        note += '<li><strong>Short survivor period:</strong> The lower earner outlives the higher earner by only ~' +
            gap + ' year(s) in this scenario — less time for an enlarged survivor benefit to compound in value.</li>';
    }

    if (h.sex === 'male' && l.sex === 'female' && result.firstDeathWho === 'higher') {
        note += '<li><strong>Typical pattern:</strong> Male higher earner dies first; female lower earner may collect his larger benefit as a survivor for many years — making his claiming age the more important decision for long-run household income.</li>';
    }

    return note;
}

function buildHeroSentence(result, opts) {
    var d = result.delayAnalysis;
    var lower = result.lower;
    var higher = result.higher;
    var firstWho = result.firstDeathWho;
    var survivorYears = Math.max(0, opts.lowerEarner.deathAge - opts.higherEarner.deathAge);

    // Lower earner claims before the comparison age (e.g. 62 vs FRA 67) — common couples strategy
    if (lower.claimAge < d.earlyCompareAge) {
        var earlyHtml = 'The lower earner claims at <strong>age ' + lower.claimAge + '</strong> — before FRA (' + d.earlyCompareAge + ') — for <strong>' + formatCurrency(lower.startMonthly) + '/month</strong> starting early. ';
        earlyHtml += 'The higher earner waits until <strong>age ' + higher.claimAge + '</strong>, raising the survivor floor to <strong>' + formatCurrency(d.higherAtDeath) + '/month</strong>. ';
        if (firstWho === 'higher' && d.higherAtDeath > lower.startMonthly) {
            earlyHtml += 'When the higher earner dies at age ' + higher.deathAge + ', the lower earner steps up from their own check to that larger survivor benefit of <strong>' + formatCurrency(d.higherAtDeath) + '/month</strong>';
            if (survivorYears > 0) {
                earlyHtml += ' for about <strong>' + survivorYears + ' year' + (survivorYears === 1 ? '' : 's') + '</strong>';
            }
            earlyHtml += '. That is why planners often focus on delaying the <em>higher</em> earner — not maximizing the lower earner\'s own benefit, which may be replaced anyway.';
        } else {
            earlyHtml += 'This matches the couples pattern many planners recommend: claim the lower benefit early, delay the higher benefit to protect the survivor.';
        }
        return earlyHtml;
    }

    if (lower.claimAge === d.earlyCompareAge) {
        return 'The lower earner claims at FRA (' + d.earlyCompareAge + '), so there is no extra wait on their own record to analyze. The key question is whether the higher earner\'s delay to age ' + higher.claimAge + ' raises the survivor floor to <strong>' + formatCurrency(d.higherAtDeath) + '/month</strong> — income that may last for the longer-lived spouse.';
    }

    var delayYears = lower.claimAge - d.earlyCompareAge;
    var delayPhrase = delayYears > 1
        ? 'during the ' + delayYears + '-year delay before claiming at age ' + lower.claimAge + ' instead of age ' + d.earlyCompareAge
        : 'by waiting until age ' + lower.claimAge + ' instead of claiming at age ' + d.earlyCompareAge;
    var html = 'The lower earner delayed receiving approximately <strong>' + formatCurrency(d.forgone) +
        '</strong> in benefits ' + delayPhrase + '. ';

    if (firstWho === 'higher' && d.higherAtDeath > lower.startMonthly) {
        html += 'Because survivor benefits replaced their own benefit when the higher earner died at age ' + higher.deathAge + ', ';
        if (d.recovered > 0 && d.netLoss > 0) {
            html += 'only <strong>' + formatCurrency(d.recovered) + '</strong> of that delay was recovered before the switch — a net loss of <strong>' + formatCurrency(d.netLoss) + '</strong> on their own record.';
        } else if (d.netLoss <= 0 && d.recovered > 0) {
            html += 'the delay recovered <strong>' + formatCurrency(d.recovered) + '</strong> in extra payments before the switch — roughly breaking even on the wait.';
        } else {
            html += 'almost none of that waiting increased long-term household income — the survivor now receives <strong>' + formatCurrency(d.higherAtDeath) + '/month</strong> from the higher earner\'s record.';
        }
        if (opts.lowerEarner.deathAge - opts.higherEarner.deathAge >= 5) {
            html += ' However, that survivor benefit may continue for ~' + (opts.lowerEarner.deathAge - opts.higherEarner.deathAge) +
                ' more years — which is why the higher earner\'s delay (not the lower earner\'s) often drives the couples strategy.';
        }
    } else if (firstWho === 'lower') {
        html += 'The lower earner died first, so their own benefit (not the survivor benefit) determined what they received.';
    } else if (firstWho === 'higher' && d.higherAtDeath <= lower.startMonthly) {
        html += 'The lower earner\'s own benefit was higher than the survivor benefit, so their delay continued to matter.';
    } else {
        html += 'Adjust longevity assumptions to see how survivor benefits affect the payoff from waiting.';
    }

    return html;
}

function buildTimeline(result) {
    var higher = result.higher;
    var lower = result.lower;
    var minAge = 62;
    var maxAge = Math.max(higher.deathAge, lower.deathAge + (higher.birthYear - lower.birthYear));
    var span = maxAge - minAge;

    function pct(age) {
        return Math.max(0, Math.min(100, ((age - minAge) / span) * 100));
    }

    var firstDeath = result.firstDeathWho;
    var higherDeathOnTimeline = higher.deathAge;
    var lowerDeathOnTimeline = lower.deathAge + (higher.birthYear - lower.birthYear);

    var html = '<h3>Income timeline</h3>';

    html += '<div class="timeline-row"><div class="timeline-label">Higher earner — claims at ' + higher.claimAge + ', dies at ' + higher.deathAge + '</div>';
    html += '<div class="timeline-bar">';
    if (higher.claimAge > minAge) {
        html += '<div class="timeline-segment seg-none" style="left:0;width:' + pct(higher.claimAge) + '%">Not claiming</div>';
    }
    html += '<div class="timeline-segment seg-own" style="left:' + pct(higher.claimAge) + '%;width:' + (pct(higher.deathAge) - pct(higher.claimAge)) + '%">Own benefit</div>';
    html += '</div><div class="timeline-axis"><span>' + minAge + '</span><span>' + maxAge + '</span></div></div>';

    html += '<div class="timeline-row"><div class="timeline-label">Lower earner — claims at ' + lower.claimAge + ', dies at ' + lower.deathAge + '</div>';
    html += '<div class="timeline-bar">';
    var lowerStartOnH = lower.claimAge + (higher.birthYear - lower.birthYear);
    var lowerEndOnH = lower.deathAge + (higher.birthYear - lower.birthYear);
    if (lowerStartOnH > minAge) {
        html += '<div class="timeline-segment seg-none" style="left:0;width:' + pct(lowerStartOnH) + '%">Not claiming</div>';
    }
    if (firstDeath === 'higher' && higher.deathAge < lowerEndOnH) {
        html += '<div class="timeline-segment seg-own" style="left:' + pct(lowerStartOnH) + '%;width:' + (pct(higher.deathAge) - pct(lowerStartOnH)) + '%">Own benefit</div>';
        html += '<div class="timeline-segment seg-survivor" style="left:' + pct(higher.deathAge) + '%;width:' + (pct(lowerEndOnH) - pct(higher.deathAge)) + '%">Survivor benefit</div>';
    } else {
        html += '<div class="timeline-segment seg-own" style="left:' + pct(lowerStartOnH) + '%;width:' + (pct(lowerEndOnH) - pct(lowerStartOnH)) + '%">Own benefit</div>';
    }
    html += '</div><div class="timeline-axis"><span>' + minAge + '</span><span>' + maxAge + '</span></div></div>';

    html += '<div class="timeline-row"><div class="timeline-label">Household (combined checks)</div>';
    html += '<div class="timeline-bar">';
    var bothStart = Math.max(higher.claimAge, lowerStartOnH);
    var bothEnd = firstDeath === 'higher' ? higher.deathAge : Math.min(higherDeathOnTimeline, lowerDeathOnTimeline);
    if (bothStart > minAge) {
        html += '<div class="timeline-segment seg-none" style="left:0;width:' + pct(bothStart) + '%"></div>';
    }
    if (firstDeath && bothEnd > bothStart) {
        html += '<div class="timeline-segment seg-both" style="left:' + pct(bothStart) + '%;width:' + (pct(bothEnd) - pct(bothStart)) + '%">Both receiving</div>';
        html += '<div class="timeline-segment seg-survivor" style="left:' + pct(bothEnd) + '%;width:' + (pct(maxAge) - pct(bothEnd)) + '%">One check — survivor rules</div>';
    } else if (bothEnd > bothStart) {
        html += '<div class="timeline-segment seg-both" style="left:' + pct(bothStart) + '%;width:' + (pct(bothEnd) - pct(bothStart)) + '%">Both receiving</div>';
    }
    html += '</div><div class="timeline-axis"><span>' + minAge + '</span><span>' + maxAge + '</span></div></div>';

    return html;
}

function dedupeStrategies(strategies) {
    var seen = {};
    return strategies.filter(function (s) {
        var key = s.result.higher.claimAge + ':' + s.result.lower.claimAge;
        if (seen[key]) return false;
        seen[key] = true;
        return true;
    });
}

function buildStrategyInsight(strategies, result) {
    if (!strategies.length || result.firstDeathWho !== 'higher') return '';

    var best = strategies.reduce(function (a, b) {
        return a.result.totalHousehold >= b.result.totalHousehold ? a : b;
    });
    var couplesRec = strategies.find(function (s) { return s.name === 'Lower early, higher at 70'; });

    var html = '<strong>Why rankings differ:</strong> Because the higher earner\'s delayed benefit becomes the survivor benefit, delaying the higher earner usually adds more to lifetime household income than delaying the lower earner — whose own benefit may be replaced when the higher earner dies first.';

    if (best.name === 'Lower early, higher at 70') {
        html += ' Here, <em>Lower early, higher at 70</em> leads the comparison for that reason.';
    } else if (couplesRec && couplesRec.result.totalHousehold >= best.result.totalHousehold - 1) {
        html += ' <em>Lower early, higher at 70</em> remains among the strongest options here for the same reason.';
    }

    return html;
}

function buildStrategyComparison(baseOpts, result) {
    var fraHigher = Math.round(RBFinance.fraAgeFromBirthYear(baseOpts.higherEarner.birthYear));
    var fraLower = Math.round(RBFinance.fraAgeFromBirthYear(baseOpts.lowerEarner.birthYear));

    var strategies = RBSSHousehold.compareStrategies(baseOpts, [
        { name: 'Your plan', description: 'Current inputs', higher: {}, lower: {} },
        { name: 'Lower early, higher at 70', description: 'Common couples recommendation', higher: { claimAge: 70 }, lower: { claimAge: fraLower } },
        { name: 'Both delay to 70', description: 'Individual max strategy', higher: { claimAge: 70 }, lower: { claimAge: 70 } },
        { name: 'Both claim at FRA', description: 'Moderate approach', higher: { claimAge: fraHigher }, lower: { claimAge: fraLower } }
    ]);

    strategies = dedupeStrategies(strategies);

    var bestTotal = -1;
    strategies.forEach(function (s) {
        if (s.result.totalHousehold > bestTotal) bestTotal = s.result.totalHousehold;
    });

    var rows = '';
    strategies.forEach(function (s) {
        var r = s.result;
        var isBest = r.totalHousehold >= bestTotal - 1;
        rows += '<tr' + (isBest ? ' class="best"' : '') + '>';
        rows += '<td><strong>' + s.name + '</strong><br><small style="color:#666">' + s.description + '</small></td>';
        rows += '<td>' + r.higher.claimAge + '</td>';
        rows += '<td>' + r.lower.claimAge + '</td>';
        rows += '<td>' + formatCurrency(r.totalHousehold) + (isBest ? ' ★' : '') + '</td>';
        rows += '<td>' + formatCurrency(r.beforeFirstDeath) + '</td>';
        rows += '<td>' + formatCurrency(r.afterFirstDeath) + '</td>';
        rows += '</tr>';
    });

    document.getElementById('strategyBody').innerHTML = rows;

    var insightEl = document.getElementById('strategyInsight');
    if (insightEl) {
        var insightHtml = buildStrategyInsight(strategies, result);
        insightEl.innerHTML = insightHtml;
        insightEl.style.display = insightHtml ? 'block' : 'none';
    }

    return strategies;
}

function createHouseholdChart(yearly) {
    var ctx = document.getElementById('householdChart');
    if (window.householdChart instanceof Chart) {
        window.householdChart.destroy();
    }

    var maxLabels = 12;
    var step = Math.max(1, Math.ceil(yearly.length / maxLabels));
    var sampled = yearly.filter(function (_, i) { return i % step === 0; });

    window.householdChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: sampled.map(function (y) { return String(y.calendarYear); }),
            datasets: [{
                label: 'Household monthly SS',
                data: sampled.map(function (y) { return y.householdMonthly; }),
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return formatCurrency(ctx.parsed.y) + '/month';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Calendar year' },
                    ticks: { maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: maxLabels }
                },
                y: {
                    title: { display: true, text: 'Household monthly income' },
                    ticks: { callback: function (v) { return formatCurrency(v); } }
                }
            }
        }
    });
}

function runAnalysis() {
    updateLongevityHints();
    var opts = getFormInputs();
    var result = RBSSHousehold.simulateHouseholdSS(opts);
    var d = result.delayAnalysis;

    var heroHtml = buildHeroSentence(result, opts);
    document.getElementById('heroSentence').innerHTML = heroHtml;

    var longevityLabel = opts.longevitySource === 'actuarial' ? 'Actuarial death ages' : 'Custom death ages';
    document.getElementById('summaryCards').innerHTML =
        '<div class="summary-card"><div class="summary-label">Lifetime household SS</div><div class="summary-value">' + formatCurrency(result.totalHousehold) + '</div></div>' +
        '<div class="summary-card"><div class="summary-label">Before first death</div><div class="summary-value">' + formatCurrency(result.beforeFirstDeath) + '</div></div>' +
        '<div class="summary-card"><div class="summary-label">After first death</div><div class="summary-value">' + formatCurrency(result.afterFirstDeath) + '</div></div>' +
        '<div class="summary-card"><div class="summary-label">' + longevityLabel + '</div><div class="summary-value">' + opts.higherEarner.deathAge + ' / ' + opts.lowerEarner.deathAge + '</div></div>' +
        '<div class="summary-card"><div class="summary-label">Survivor floor at death</div><div class="summary-value">' + formatCurrency(d.higherAtDeath) + '</div></div>' +
        '<div class="summary-card"><div class="summary-label">Survivor years (approx.)</div><div class="summary-value">' + Math.max(0, opts.lowerEarner.deathAge - opts.higherEarner.deathAge) + '</div></div>';

    document.getElementById('timelineVisual').innerHTML = buildTimeline(result);

    var interp = '<h3>What this means</h3><ul>';
    interp += buildLongevityNote(opts, result);
    interp += '<li><strong>Household lifetime Social Security:</strong> ' + formatCurrency(result.totalHousehold) + ' under your assumptions (COLA ' + opts.colaRate + '%).</li>';

    if (result.firstDeathWho === 'higher') {
        interp += '<li><strong>When the higher earner dies at ' + result.higher.deathAge + ',</strong> the lower earner steps up to ' + formatCurrency(d.higherAtDeath) + '/month if that exceeds their own benefit — their own check stops.</li>';
    }

    if (d.forgone > 0) {
        interp += '<li><strong>Lower earner delay:</strong> Waiting from age ' + d.earlyCompareAge + ' to ' + result.lower.claimAge + ' delayed about ' + formatCurrency(d.forgone) + ' in own-record income — not a permanent loss until you see whether survivor benefits replace it.</li>';
    }

    if (d.higherDelayBonusMonthly > 0) {
        interp += '<li><strong>Higher earner delay value:</strong> Delaying the higher earner added roughly ' + formatCurrency(d.higherDelayBonusMonthly) + '/month to the survivor floor compared with claiming at FRA — income that may support the longer-lived spouse for years.</li>';
    }

    interp += '<li><strong>Couples rule of thumb:</strong> In many marriages with unequal earnings, the higher earner should delay (survivor benefit for the longer-lived spouse), while the lower earner often benefits from claiming earlier.</li>';
    interp += '</ul>';
    document.getElementById('interpretation').innerHTML = interp;

    var strategies = buildStrategyComparison(opts, result);
    createHouseholdChart(result.yearly);

    var strategyInsight = buildStrategyInsight(strategies, result);

    window.lastSurvivorImpactResult = {
        opts: opts,
        result: result,
        strategies: strategies,
        heroText: heroHtml.replace(/<[^>]+>/g, ''),
        strategyInsight: strategyInsight.replace(/<[^>]+>/g, '')
    };

    document.getElementById('results').style.display = 'block';
    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function getFormDataForSave() {
    var ids = ['higherBirthYear', 'higherPIA', 'higherClaimAge', 'higherSex', 'higherCurrentAge',
        'lowerBirthYear', 'lowerPIA', 'lowerClaimAge', 'lowerSex', 'lowerCurrentAge',
        'overrideLongevity', 'higherDeathAge', 'lowerDeathAge',
        'lowerEarlyCompareAge', 'colaRate', 'discountRate'];
    var data = {};
    ids.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        data[id] = el.type === 'checkbox' ? el.checked : el.value;
    });
    return data;
}

function saveScenario() {
    var scenarioName = prompt('Enter a name for this scenario:', 'My couples SS plan');
    if (!scenarioName) return;

    fetch(SSI_API_BASE + 'api/save_scenario.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            calculator_type: 'ss-survivor-impact',
            scenario_name: scenarioName,
            scenario_data: getFormDataForSave()
        })
    })
    .then(function (res) { return res.text().then(function (text) { return { ok: res.ok, text: text }; }); })
    .then(function (_ref) {
        var data = JSON.parse(_ref.text);
        if (!data.success) throw new Error(data.error || 'Save failed');
        document.getElementById('saveStatus').textContent = 'Saved!';
        setTimeout(function () { document.getElementById('saveStatus').textContent = ''; }, 3000);
    })
    .catch(function (err) { alert('Save failed: ' + err.message); });
}

function loadScenario() {
    fetch(SSI_API_BASE + 'api/load_scenarios.php?calculator_type=ss-survivor-impact')
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (!data.success || !data.scenarios.length) {
            alert(data.scenarios && data.scenarios.length === 0 ? 'No saved scenarios yet.' : (data.error || 'Load failed'));
            return;
        }
        var message = 'Select a scenario:\n\n';
        data.scenarios.forEach(function (s, i) {
            message += (i + 1) + '. ' + s.name + '\n';
        });
        var choice = prompt(message);
        if (!choice) return;
        var index = parseInt(choice, 10) - 1;
        if (index < 0 || index >= data.scenarios.length) return;
        ageSyncedFromBirthYear.higher = false;
        ageSyncedFromBirthYear.lower = false;
        Object.keys(data.scenarios[index].data).forEach(function (key) {
            var el = document.getElementById(key);
            if (!el) return;
            if (el.type === 'checkbox') {
                el.checked = data.scenarios[index].data[key] === true || data.scenarios[index].data[key] === 'true' || data.scenarios[index].data[key] === 'on';
            } else {
                el.value = data.scenarios[index].data[key];
            }
        });
        toggleOverridePanel();
        updateLongevityHints();
        document.getElementById('survivorForm').dispatchEvent(new Event('submit', { cancelable: true }));
    });
}

function downloadPDF() {
    var stored = window.lastSurvivorImpactResult;
    if (!stored) {
        alert('Please run the analysis first.');
        return;
    }
    var chartCanvas = document.getElementById('householdChart');
    var chartImage = chartCanvas && window.householdChart ? chartCanvas.toDataURL('image/png') : null;
    var strategiesForPdf = (stored.strategies || []).map(function (s) {
        return {
            name: s.name,
            result: {
                higher: { claimAge: s.result.higher.claimAge },
                lower: { claimAge: s.result.lower.claimAge },
                totalHousehold: s.result.totalHousehold
            }
        };
    });
    var payload = {
        opts: stored.opts,
        result: stored.result,
        heroText: stored.heroText,
        strategyInsight: stored.strategyInsight || '',
        strategies: strategiesForPdf,
        yearly: stored.result.yearly,
        chartImage: chartImage
    };

    fetch(SSI_API_BASE + 'api/generate_ss_survivor_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload)
    })
    .then(function (r) {
        if (!r.ok) {
            return r.text().then(function (t) {
                try { throw new Error(JSON.parse(t).error); } catch (e) { throw new Error(t || 'PDF failed'); }
            });
        }
        var ct = r.headers.get('Content-Type') || '';
        if (ct.indexOf('application/pdf') === -1) {
            return r.text().then(function () { throw new Error('Server did not return a PDF. Log in as premium and try again.'); });
        }
        return r.blob();
    })
    .then(function (blob) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'SS_Survivor_Impact_' + new Date().toISOString().split('T')[0] + '.pdf';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(function (e) { alert('Download PDF: ' + e.message); });
}

function downloadCSV() {
    var stored = window.lastSurvivorImpactResult;
    if (!stored || !stored.result.yearly) {
        alert('Please run the analysis first.');
        return;
    }
    fetch(SSI_API_BASE + 'api/export_ss_survivor_csv.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ yearly: stored.result.yearly })
    })
    .then(function (r) {
        if (!r.ok) {
            return r.text().then(function (t) {
                try { throw new Error(JSON.parse(t).error); } catch (e) { throw new Error(t || 'Export failed'); }
            });
        }
        return r.blob();
    })
    .then(function (blob) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'SS_Survivor_Impact_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    })
    .catch(function (e) { alert('Export CSV: ' + e.message); });
}

function compareScenarios() {
    fetch(SSI_API_BASE + 'api/load_scenarios.php?calculator_type=ss-survivor-impact')
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (!data.success || !data.scenarios || data.scenarios.length < 2) {
            alert('You need at least 2 saved scenarios to compare. Save more first!');
            return;
        }
        var message = 'Select TWO scenarios to compare:\n\n';
        data.scenarios.forEach(function (s, i) {
            message += (i + 1) + '. ' + s.name + '\n';
        });
        var choice = prompt(message + '\nEnter two numbers separated by comma (e.g., "1,2"):');
        if (!choice) return;
        var parts = choice.split(',').map(function (s) { return parseInt(s.trim(), 10) - 1; });
        if (parts.length !== 2 || parts[0] < 0 || parts[0] >= data.scenarios.length ||
            parts[1] < 0 || parts[1] >= data.scenarios.length || parts[0] === parts[1]) {
            alert('Invalid selection. Enter two different numbers (e.g., "1,2").');
            return;
        }
        var s1 = data.scenarios[parts[0]];
        var s2 = data.scenarios[parts[1]];
        var r1 = RBSSHousehold.simulateHouseholdSS(buildOptsFromSavedData(s1.data));
        var r2 = RBSSHousehold.simulateHouseholdSS(buildOptsFromSavedData(s2.data));
        showScenarioComparison(s1.name, s2.name, r1, r2);
    })
    .catch(function (err) { alert('Compare failed: ' + err.message); });
}

function showScenarioComparison(name1, name2, r1, r2) {
    var resultsDiv = document.getElementById('results');
    if (resultsDiv.style.display === 'none') resultsDiv.style.display = 'block';

    var existing = document.getElementById('scenarioComparisonBanner');
    if (existing) existing.remove();

    var d1 = r1.delayAnalysis;
    var d2 = r2.delayAnalysis;
    var html = '<div id="scenarioComparisonBanner" style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">' +
        '<h2 style="margin-top: 0; color: #92400e;">Scenario Comparison</h2>' +
        '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">' +
        '<div><h3 style="color: #2563eb;">' + escapeHtml(name1) + '</h3>' +
        '<div style="font-size: 0.9em; color: #666; line-height: 1.6;">' +
        'Higher claims ' + r1.higher.claimAge + ' / Lower claims ' + r1.lower.claimAge + '<br>' +
        'Death ages ' + r1.higher.deathAge + ' / ' + r1.lower.deathAge + '<br>' +
        'Lifetime household SS: <strong>' + formatCurrency(r1.totalHousehold) + '</strong><br>' +
        'After first death: ' + formatCurrency(r1.afterFirstDeath) + '<br>' +
        'Lower delay net loss: ' + formatCurrency(d1.netLoss) +
        '</div></div>' +
        '<div><h3 style="color: #7c3aed;">' + escapeHtml(name2) + '</h3>' +
        '<div style="font-size: 0.9em; color: #666; line-height: 1.6;">' +
        'Higher claims ' + r2.higher.claimAge + ' / Lower claims ' + r2.lower.claimAge + '<br>' +
        'Death ages ' + r2.higher.deathAge + ' / ' + r2.lower.deathAge + '<br>' +
        'Lifetime household SS: <strong>' + formatCurrency(r2.totalHousehold) + '</strong><br>' +
        'After first death: ' + formatCurrency(r2.afterFirstDeath) + '<br>' +
        'Lower delay net loss: ' + formatCurrency(d2.netLoss) +
        '</div></div></div>' +
        '<p style="margin: 0; font-size: 14px; color: #78350f;"><strong>Difference:</strong> ' +
        formatCurrency(Math.abs(r1.totalHousehold - r2.totalHousehold)) +
        (r1.totalHousehold >= r2.totalHousehold ? ' more lifetime SS in scenario 1.' : ' more lifetime SS in scenario 2.') +
        '</p></div>';

    resultsDiv.insertAdjacentHTML('afterbegin', html);
    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function explainResults() {
    var stored = window.lastSurvivorImpactResult;
    if (!stored) {
        alert('Run the analysis first.');
        return;
    }
    var r = stored.result;
    var opts = stored.opts;
    var d = r.delayAnalysis;
    var summary = 'Social Security Survivor Impact Analysis.\n\n';
    summary += 'Longevity source: ' + opts.longevitySource + '.\n';
    summary += 'Higher earner: ' + opts.higherEarner.sex + ', age ' + opts.higherEarner.currentAge + ', born ' + r.higher.birthYear + ', PIA ' + formatCurrency(r.higher.pia) + ', claims at ' + r.higher.claimAge + ', dies at ' + r.higher.deathAge + '.\n';
    summary += 'Lower earner: ' + opts.lowerEarner.sex + ', age ' + opts.lowerEarner.currentAge + ', born ' + r.lower.birthYear + ', PIA ' + formatCurrency(r.lower.pia) + ', claims at ' + r.lower.claimAge + ', dies at ' + r.lower.deathAge + '.\n\n';
    summary += 'Lifetime household SS: ' + formatCurrency(r.totalHousehold) + '. Before first death: ' + formatCurrency(r.beforeFirstDeath) + '. After: ' + formatCurrency(r.afterFirstDeath) + '.\n';
    summary += 'Survivor years approx: ' + Math.max(0, opts.lowerEarner.deathAge - opts.higherEarner.deathAge) + '.\n';
    summary += 'Lower earner delayed by waiting: ' + formatCurrency(d.forgone) + '. Recovered before survivor switch: ' + formatCurrency(d.recovered) + '. Net loss on own record: ' + formatCurrency(d.netLoss) + '.\n';
    summary += 'Survivor floor at higher earner death: ' + formatCurrency(d.higherAtDeath) + '/month.';

    var btn = document.getElementById('explainResultsBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

    fetch((window.location.origin || '') + '/api/explain_results.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            calculator_type: 'ss-survivor-impact',
            results_summary: summary
        })
    })
    .then(function (res) { return res.text(); })
    .then(function (text) {
        if (btn) { btn.disabled = false; btn.textContent = 'Explain my results'; }
        var data = JSON.parse(text);
        if (data.error) throw new Error(data.error);
        showExplainModal(data.explanation, { calculatorType: 'ss-survivor-impact', resultsSummary: summary });
    })
    .catch(function (err) {
        if (btn) { btn.disabled = false; btn.textContent = 'Explain my results'; }
        alert('Explain results: ' + err.message);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('survivorForm').addEventListener('submit', function (e) {
        e.preventDefault();
        runAnalysis();
    });

    document.getElementById('higherBirthYear').addEventListener('change', function () {
        syncCurrentAgeFromBirthYear('higher');
        syncFraHints();
        updateLongevityHints();
    });
    document.getElementById('lowerBirthYear').addEventListener('change', function () {
        syncCurrentAgeFromBirthYear('lower');
        syncFraHints();
        updateLongevityHints();
    });

    document.getElementById('lowerEarlyCompareAge').addEventListener('input', function () {
        this.dataset.userEdited = 'true';
    });

    document.getElementById('higherCurrentAge').addEventListener('input', function () {
        ageSyncedFromBirthYear.higher = false;
        updateLongevityHints();
    });
    document.getElementById('lowerCurrentAge').addEventListener('input', function () {
        ageSyncedFromBirthYear.lower = false;
        updateLongevityHints();
    });

    ['higherSex', 'lowerSex'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', updateLongevityHints);
    });

    document.getElementById('overrideLongevity').addEventListener('change', toggleOverridePanel);
    document.getElementById('higherDeathAge').addEventListener('input', syncDeathLabels);
    document.getElementById('lowerDeathAge').addEventListener('input', syncDeathLabels);

    document.querySelectorAll('.preset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyPreset(btn.getAttribute('data-preset'));
        });
    });

    syncCurrentAgeFromBirthYear('higher');
    syncCurrentAgeFromBirthYear('lower');
    syncFraHints();
    toggleOverridePanel();
    updateLongevityHints();

    var saveBtn = document.getElementById('saveScenarioBtn');
    var loadBtn = document.getElementById('loadScenarioBtn');
    var compareBtn = document.getElementById('compareScenariosBtn');
    var pdfBtn = document.getElementById('downloadPdfBtn');
    var csvBtn = document.getElementById('downloadCsvBtn');
    var explainBtn = document.getElementById('explainResultsBtn');
    if (saveBtn) saveBtn.addEventListener('click', saveScenario);
    if (loadBtn) loadBtn.addEventListener('click', loadScenario);
    if (compareBtn) compareBtn.addEventListener('click', compareScenarios);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPDF);
    if (csvBtn) csvBtn.addEventListener('click', downloadCSV);
    if (explainBtn) explainBtn.addEventListener('click', explainResults);
});
