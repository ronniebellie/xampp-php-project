const RT_API_BASE = (function() {
  const path = window.location.pathname;
  const match = path.match(/^(.*\/)retirement-timeline\/?/);
  const basePath = (match ? match[1] : '/').replace(/\/?$/, '/');
  return window.location.origin + basePath;
})();

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('timelineForm');
  const resultsEl = document.getElementById('results');
  const summaryLine = document.getElementById('summaryLine');
  const timelineContainer = document.getElementById('timelineContainer');

  if (!form) return;

  function parseDate(value) {
    if (!value) return null;
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
  }

  function formatDate(d) {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '—';
    return d.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  function calculateAgeOn(birth, onDate) {
    if (!birth || !onDate) return null;
    let age = onDate.getFullYear() - birth.getFullYear();
    const m = onDate.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && onDate.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  }

  function addMonths(date, months) {
    const d = new Date(date.getTime());
    const day = d.getDate();
    d.setMonth(d.getMonth() + months);
    if (d.getDate() < day) {
      d.setDate(0);
    }
    return d;
  }

  function buildTasks(birth, retireDate) {
    const sections = [];

    const retireAge = calculateAgeOn(birth, retireDate);

    const phase1Tasks = [
      {
        id: 'big-picture-spending',
        label: 'Clarify your retirement lifestyle and spending',
        desc: 'Rough in what retirement might look like (housing, travel, work, hobbies) and estimate your monthly spending using your current budget as a starting point.'
      },
      {
        id: 'check-savings-track',
        label: 'Check if you’re on track',
        desc: 'Use tools like “How Much Do I Need? Nest Egg Target” and “401(k) / IRA On Track?” to see whether your savings and contributions match your income goals.'
      },
      {
        id: 'debt-and-cash',
        label: 'Tidy up debt and emergency fund',
        desc: 'Make a plan to pay down consumer debt and build an emergency fund so you enter retirement on firmer footing.'
      }
    ];
    sections.push({
      id: 'phase-5plus',
      title: '5+ years before retirement',
      when: 'Long-range prep',
      tasks: phase1Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -12 * 10),
        age: calculateAgeOn(birth, addMonths(retireDate, -12 * 10))
      }))
    });

    const phase2Tasks = [
      {
        id: 'refine-income-plan',
        label: 'Refine income plan and withdrawal strategy',
        desc: 'Rough out how Social Security, pensions, portfolio withdrawals, and part-time work will fit together in your first 10 years of retirement.'
      },
      {
        id: 'tax-and-accounts',
        label: 'Review tax and account structure',
        desc: 'Consider whether Roth conversions or shifting assets between accounts might reduce future tax drag and RMD pressure.'
      }
    ];
    sections.push({
      id: 'phase-2to3',
      title: '2–3 years before retirement',
      when: 'Dial in the plan',
      tasks: phase2Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -12 * 30),
        age: calculateAgeOn(birth, addMonths(retireDate, -12 * 30))
      }))
    });

    const phase3Tasks = [
      {
        id: 'health-insurance-plan',
        label: 'Health insurance & Medicare gameplan',
        desc: 'Decide how you will bridge health insurance from retirement to Medicare (if applicable) and whether you’ll use Medigap or Medicare Advantage.'
      },
      {
        id: 'social-security-window',
        label: 'Choose a Social Security claiming window',
        desc: 'Compare claiming ages (e.g., 62 vs full retirement age vs 70) and decide a likely target, even if you keep some flexibility.'
      }
    ];
    sections.push({
      id: 'phase-12to18',
      title: '12–18 months before retirement',
      when: 'Lock in key decisions',
      tasks: phase3Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -15),
        age: calculateAgeOn(birth, addMonths(retireDate, -15))
      }))
    });

    const phase4Tasks = [
      {
        id: 'notify-employer',
        label: 'Discuss retirement date with employer',
        desc: 'Talk with your manager/HR about your target date, transition plan, and any benefits that might be affected.'
      },
      {
        id: 'update-beneficiaries',
        label: 'Update beneficiaries and estate documents',
        desc: 'Review and update your will, powers of attorney, and beneficiaries on retirement accounts and insurance policies.'
      },
      {
        id: 'pension-forms',
        label: 'Request pension / benefit estimates (if applicable)',
        desc: 'If you have a pension or similar benefit, request updated estimates and paperwork so there are no surprises.'
      }
    ];
    sections.push({
      id: 'phase-6to12',
      title: '6–12 months before retirement',
      when: 'Paperwork and logistics',
      tasks: phase4Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -9),
        age: calculateAgeOn(birth, addMonths(retireDate, -9))
      }))
    });

    const phase5Tasks = [
      {
        id: 'file-social-security',
        label: 'File for Social Security (if timing fits)',
        desc: 'If you plan to start Social Security around your retirement date, file your application (many people file 2–3 months before benefits start).'
      },
      {
        id: 'final-budget',
        label: 'Finalize first‑year retirement budget',
        desc: 'Translate your income plan into a concrete monthly budget, including taxes, healthcare, and irregular expenses.'
      }
    ];
    sections.push({
      id: 'phase-3months',
      title: 'Around 3 months before retirement',
      when: 'Final confirmations',
      tasks: phase5Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -3),
        age: calculateAgeOn(birth, addMonths(retireDate, -3))
      }))
    });

    const phase6Tasks = [
      {
        id: 'last-day-logistics',
        label: 'Confirm last‑day logistics',
        desc: 'Confirm your final paycheck, unused vacation, benefits end date, and any return‑of‑equipment steps with HR.'
      },
      {
        id: 'celebrate-transition',
        label: 'Plan a small celebration',
        desc: 'Plan a simple way to mark the transition—dinner with friends or family, a short trip, or another meaningful ritual.'
      }
    ];
    sections.push({
      id: 'phase-last-month',
      title: 'Last month at work',
      when: 'Close things out well',
      tasks: phase6Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, -1),
        age: calculateAgeOn(birth, addMonths(retireDate, -1))
      }))
    });

    const phase7Tasks = [
      {
        id: 'first-year-checkin',
        label: 'First‑year spending check‑in',
        desc: 'Compare your actual spending and withdrawals to your plan after 6–12 months. Adjust your budget or withdrawals if needed.'
      },
      {
        id: 'routine-and-purpose',
        label: 'Build your new rhythm',
        desc: 'Experiment with routines, hobbies, volunteering, part‑time work, or creative projects that give your days structure and meaning.'
      }
    ];
    sections.push({
      id: 'phase-first-year',
      title: 'First year of retirement',
      when: 'Adjust and refine',
      tasks: phase7Tasks.map(t => ({
        ...t,
        date: addMonths(retireDate, 6),
        age: calculateAgeOn(birth, addMonths(retireDate, 6))
      }))
    });

    return sections;
  }

  function taskStorageKey(taskId, dateIso) {
    return `rtimeline_${taskId}_${dateIso}`;
  }

  function renderTimeline(sections, birth, retireDate) {
    timelineContainer.innerHTML = '';

    sections.forEach(section => {
      const sectionDiv = document.createElement('section');
      sectionDiv.style.marginBottom = '20px';

      const heading = document.createElement('h3');
      heading.textContent = section.title;
      heading.style.margin = '0 0 6px';
      heading.style.fontSize = '16px';
      heading.style.letterSpacing = '-0.01em';

      const whenLine = document.createElement('p');
      whenLine.textContent = section.when;
      whenLine.style.margin = '0 0 8px';
      whenLine.style.color = '#6b7280';
      whenLine.style.fontSize = '13px';

      sectionDiv.appendChild(heading);
      sectionDiv.appendChild(whenLine);

      section.tasks.forEach(task => {
        const item = document.createElement('div');
        item.style.display = 'flex';
        item.style.alignItems = 'flex-start';
        item.style.gap = '8px';
        item.style.marginBottom = '8px';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.style.marginTop = '3px';

        const dateIso = retireDate.toISOString().slice(0, 10);
        const storageKey = taskStorageKey(task.id, dateIso);
        checkbox.checked = localStorage.getItem(storageKey) === '1';

        checkbox.addEventListener('change', () => {
          if (checkbox.checked) {
            localStorage.setItem(storageKey, '1');
          } else {
            localStorage.removeItem(storageKey);
          }
        });

        const textWrap = document.createElement('div');

        const titleLine = document.createElement('div');
        titleLine.style.fontWeight = '600';
        titleLine.style.fontSize = '14px';
        titleLine.textContent = task.label;

        const metaLine = document.createElement('div');
        metaLine.style.fontSize = '12px';
        metaLine.style.color = '#6b7280';
        const dateStr = formatDate(task.date);
        const ageStr = task.age != null ? `${task.age} yrs` : '';
        metaLine.textContent = `${dateStr}${ageStr ? '  •  around age ' + ageStr : ''}`;

        const descLine = document.createElement('div');
        descLine.style.fontSize = '13px';
        descLine.style.color = '#374151';
        descLine.textContent = task.desc;

        textWrap.appendChild(titleLine);
        textWrap.appendChild(metaLine);
        textWrap.appendChild(descLine);

        item.appendChild(checkbox);
        item.appendChild(textWrap);

        sectionDiv.appendChild(item);
      });

      timelineContainer.appendChild(sectionDiv);
    });
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const birthVal = document.getElementById('birthdate').value;
    const retireVal = document.getElementById('retirementDate').value;

    const birth = parseDate(birthVal);
    const retireDate = parseDate(retireVal);

    const errors = [];
    if (!birth) errors.push('birthdate');
    if (!retireDate) errors.push('retirement date');

    if (birth && retireDate && retireDate <= birth) {
      errors.push('retirement date must be after birthdate');
    }

    if (errors.length) {
      alert('Please check: ' + errors.join(', ') + '.');
      return;
    }

    const retireAge = calculateAgeOn(birth, retireDate);
    summaryLine.textContent =
      `You’ll be about ${retireAge} years old on ` +
      `${formatDate(retireDate)}. Here’s a rough timeline of tasks to consider along the way.`;

    const sections = buildTasks(birth, retireDate);
    renderTimeline(sections, birth, retireDate);

    window.lastRetirementTimelineResult = { birth: birthVal, retireDate: retireVal, retireAge, sections, summaryText: summaryLine.textContent };

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  const explainBtn = document.getElementById('explainResultsBtnInResults');
  if (explainBtn) explainBtn.addEventListener('click', explainResults);
});

function escapeHtml(s) {
  const div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function explainResults() {
  const res = window.lastRetirementTimelineResult;
  if (!res) {
    alert('Please build your checklist first.');
    return;
  }
  let summary = 'Retirement Timeline & Checklist.\n\n';
  summary += res.summaryText + '\n\n';
  summary += 'Phases and tasks: ';
  res.sections.forEach((sec, i) => {
    summary += sec.title + ': ' + sec.tasks.map(t => t.label).join('; ') + '. ';
  });

  const btn = document.getElementById('explainResultsBtnInResults');
  const origText = btn ? btn.textContent : '';
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Loading…';
  }

  fetch(RT_API_BASE + 'api/explain_results.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({
      calculator_type: 'retirement-timeline',
      results_summary: summary
    })
  })
  .then(r => r.text())
  .then(text => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    let resp;
    try { resp = JSON.parse(text); } catch (e) {
      throw new Error('Server returned an unexpected response. Try logging out and back in.');
    }
    if (resp.error) throw new Error(resp.error);
    showExplainModal(resp.explanation);
  })
  .catch(err => {
    if (btn) { btn.disabled = false; btn.textContent = origText; }
    alert('Explain results: ' + err.message);
  });
}

function showExplainModal(explanation) {
  let overlay = document.getElementById('explainResultsModalOverlay');
  if (overlay) overlay.remove();
  overlay = document.createElement('div');
  overlay.id = 'explainResultsModalOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) overlay.remove();
  });
  const box = document.createElement('div');
  box.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:560px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;';
  box.addEventListener('click', function(e) { e.stopPropagation(); });
  box.innerHTML = '<div style="padding:24px 24px 16px;">' +
    '<h2 style="margin:0 0 16px 0;font-size:1.25rem;color:#1f2937;">🤖 AI Explanation</h2>' +
    '<div style="color:#374151;line-height:1.7;white-space:pre-wrap;overflow-y:auto;max-height:50vh;">' + escapeHtml(explanation) + '</div>' +
    '</div>' +
    '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">' +
    '<p style="margin:0 0 12px 0;font-size:12px;color:#6b7280;">This is an AI-generated explanation for educational purposes. Not financial or legal advice.</p>' +
    '<button type="button" id="explainModalCloseBtn" style="padding:10px 24px;border:none;border-radius:8px;background:#0d9488;color:#fff;cursor:pointer;font-weight:600;">Close</button>' +
    '</div>';
  overlay.appendChild(box);
  document.body.appendChild(overlay);
  document.getElementById('explainModalCloseBtn').addEventListener('click', function() { overlay.remove(); });
}

