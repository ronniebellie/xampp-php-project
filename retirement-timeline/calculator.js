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

    resultsEl.style.display = 'block';
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

