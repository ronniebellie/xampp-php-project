<?php
// NPV & IRR calculator for business students
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php
  if (file_exists(__DIR__ . '/../../includes/analytics.php')) {
      include __DIR__ . '/../../includes/analytics.php';
  }
  ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Net Present Value and Internal Rate of Return in simple English. For Japanese college students studying finance.">
  <title>NPV and IRR (For Investment Decisions)</title>
  <link rel="stylesheet" href="/css/styles.css">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background: #f9fafb; color: #0f172a; }
    .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 40px; }
    a.back-link { display: inline-block; margin-bottom: 18px; text-decoration: none; color: #1d4ed8; font-size: 14px; }
    a.back-link:hover { text-decoration: underline; }
    header { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #38bdf8 100%); color: #fff; border-radius: 18px; padding: 22px 18px 20px; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.25); margin-bottom: 20px; }
    header h1 { margin: 0 0 8px; font-size: 24px; letter-spacing: -0.02em; }
    header p { margin: 4px 0; font-size: 15px; line-height: 1.5; }
    header small { display: block; margin-top: 6px; font-size: 13px; opacity: 0.95; }
    .info-box { margin: 18px 0 20px; padding: 14px 14px 12px; border-radius: 12px; border-left: 4px solid #f59e0b; background: #fffbeb; font-size: 14px; color: #78350f; }
    .info-box strong { display: block; margin-bottom: 4px; }
    h2 { font-size: 18px; margin: 18px 0 8px; letter-spacing: -0.01em; }
    .hint { margin: 0 0 14px; font-size: 14px; color: #4b5563; }
    form { margin-top: 8px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 18px; }
    label { display: block; margin-bottom: 4px; font-size: 14px; font-weight: 600; color: #111827; }
    select.currency-select { width: 100%; padding: 9px 10px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background-color: #fff; }
    input[type="number"] { width: 100%; padding: 9px 10px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; }
    small.help { display: block; margin-top: 3px; font-size: 12px; color: #6b7280; }
    .button-primary { display: inline-block; padding: 10px 24px; border-radius: 999px; background: #1d4ed8; color: #fff; font-size: 15px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 10px 22px rgba(37, 99, 235, 0.35); }
    .button-primary:hover { background: #1e40af; }
    .center { text-align: center; margin: 22px 0 10px; }
    .results { margin-top: 10px; padding: 16px 14px 14px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.7); background: #f9fafb; display: none; }
    .result-row { margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .result-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .result-label { font-weight: 600; font-size: 14px; color: #111827; }
    .result-value { font-size: 18px; font-weight: 800; margin-top: 2px; }
    .result-note { font-size: 13px; color: #4b5563; margin-top: 2px; }
    .example-box, .keywords-box { margin-top: 20px; padding: 14px 14px 12px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.7); background: #fff; font-size: 14px; color: #111827; }
    .example-box h3, .keywords-box h3 { margin: 0 0 8px; font-size: 15px; font-weight: 700; }
    .example-box ul { margin: 6px 0 8px 18px; padding: 0; }
    .keywords-box dt { font-weight: 600; margin-top: 4px; }
    .keywords-box dd { margin: 0 0 4px 0; font-size: 13px; color: #374151; }
    .cf-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
    .cf-table th, .cf-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    .cf-table th { font-weight: 600; color: #374151; }
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <a href="/" class="back-link">← Back to Business Calculators for Japanese Students</a>

    <header>
      <h1>Net Present Value (NPV) and Internal Rate of Return (IRR)</h1>
      <p>This calculator helps you decide if an investment project is good.</p>
      <p>You enter the cost today and the cash you expect to receive in future years. The calculator shows NPV and IRR.</p>
      <small>If NPV is positive, the project usually adds value. If NPV is negative, the project usually destroys value.</small>
    </header>

    <div class="info-box">
      <strong>Key idea</strong>
      <span>NPV = today’s value of all future cash flows minus the initial cost. IRR = the discount rate that makes NPV equal to zero.</span>
    </div>

    <h2>Step 1 – Enter your numbers</h2>
    <p class="hint">Choose your currency. Use a negative number for the initial investment (money you pay out today).</p>

    <form id="npvForm">
      <div class="grid">
        <div>
          <label for="currencySymbol">Currency</label>
          <select id="currencySymbol" class="currency-select">
            <option value="¥" selected>¥ (yen)</option>
            <option value="$">$ (dollars)</option>
          </select>
        </div>
        <div>
          <label for="initialInv">Initial investment (year 0)</label>
          <input type="number" id="initialInv" step="1" value="-500000">
          <small class="help">Use a negative number, e.g. -500000 means you pay ¥500,000 today.</small>
        </div>
        <div>
          <label for="discountRate">Discount rate (% per year)</label>
          <input type="number" id="discountRate" min="0" max="100" step="0.5" value="8">
          <small class="help">Example: if your required return is 8%, type 8.</small>
        </div>
      </div>
      <div class="grid">
        <div>
          <label for="cf1">Cash flow in Year 1</label>
          <input type="number" id="cf1" step="1" value="200000">
        </div>
        <div>
          <label for="cf2">Cash flow in Year 2</label>
          <input type="number" id="cf2" step="1" value="200000">
        </div>
        <div>
          <label for="cf3">Cash flow in Year 3</label>
          <input type="number" id="cf3" step="1" value="200000">
        </div>
        <div>
          <label for="cf4">Cash flow in Year 4</label>
          <input type="number" id="cf4" step="1" value="0">
        </div>
        <div>
          <label for="cf5">Cash flow in Year 5</label>
          <input type="number" id="cf5" step="1" value="0">
        </div>
      </div>

      <div class="center">
        <button type="submit" class="button-primary">Calculate NPV and IRR</button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2>Step 2 – See results</h2>

      <div class="result-row">
        <div class="result-label">Net Present Value (NPV)</div>
        <div id="resultNpv" class="result-value"></div>
        <div id="resultNpvNote" class="result-note"></div>
      </div>

      <div class="result-row">
        <div class="result-label">Internal Rate of Return (IRR)</div>
        <div id="resultIrr" class="result-value"></div>
        <div class="result-note">IRR is the discount rate that makes NPV = 0. If IRR is higher than your required return, the project is usually good.</div>
      </div>

      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;">Cash flows (summary)</h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="cf-table" id="cfTable">
            <thead>
              <tr><th>Year</th><th>Cash flow</th><th>Present value</th></tr>
            </thead>
            <tbody id="cfTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="example-box">
      <h3>Example (new machine)</h3>
      <p>A company pays ¥500,000 today for a machine. It expects to receive ¥200,000 at the end of each of the next 3 years. The required return is 8%.</p>
      <ul>
        <li>Initial investment: -500,000</li>
        <li>Discount rate: 8%</li>
        <li>Year 1: 200,000 · Year 2: 200,000 · Year 3: 200,000</li>
      </ul>
      <p>In this example, if NPV is positive, the machine is a good investment. If IRR is above 8%, the project adds value.</p>
    </div>

    <div class="keywords-box">
      <h3>Key words</h3>
      <dl>
        <dt>NPV (Net Present Value)</dt>
        <dd>Today’s value of all future cash flows minus the initial cost. Positive NPV means the project adds value.</dd>
        <dt>IRR (Internal Rate of Return)</dt>
        <dd>The discount rate that makes NPV equal to zero. If IRR is higher than your required return, the project is usually good.</dd>
        <dt>Discount rate</dt>
        <dd>The rate you use to bring future cash back to today’s value. It reflects risk and opportunity cost.</dd>
      </dl>
    </div>
  </div>

  <script>
    function formatCurrency(value, symbol) {
      if (!isFinite(value)) return '—';
      const rounded = Math.round(value);
      const s = rounded.toLocaleString('en-US');
      return symbol === '¥' ? '¥' + s : '$' + s;
    }

    function npv(rate, cfs) {
      let sum = 0;
      for (let t = 0; t < cfs.length; t++) {
        sum += cfs[t] / Math.pow(1 + rate, t);
      }
      return sum;
    }

    function irr(cfs, guess) {
      guess = guess || 0.1;
      let r = guess;
      for (let i = 0; i < 100; i++) {
        const v = npv(r, cfs);
        if (Math.abs(v) < 1) return r;
        const dr = 0.0001;
        const v2 = npv(r + dr, cfs);
        const slope = (v2 - v) / dr;
        r = r - v / slope;
        if (r < -0.99) r = -0.99;
        if (r > 10) r = 10;
      }
      return r;
    }

    document.getElementById('npvForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const symbol = document.getElementById('currencySymbol').value || '¥';
      const initial = parseFloat(document.getElementById('initialInv').value) || 0;
      const rate = parseFloat(document.getElementById('discountRate').value) / 100 || 0;
      const cf1 = parseFloat(document.getElementById('cf1').value) || 0;
      const cf2 = parseFloat(document.getElementById('cf2').value) || 0;
      const cf3 = parseFloat(document.getElementById('cf3').value) || 0;
      const cf4 = parseFloat(document.getElementById('cf4').value) || 0;
      const cf5 = parseFloat(document.getElementById('cf5').value) || 0;

      const cfs = [initial, cf1, cf2, cf3, cf4, cf5];
      const npvVal = npv(rate, cfs);
      let irrVal = null;
      try {
        irrVal = irr(cfs);
      } catch (err) {
        irrVal = null;
      }

      document.getElementById('resultNpv').textContent = formatCurrency(npvVal, symbol);
      const npvNote = document.getElementById('resultNpvNote');
      if (npvVal > 0) {
        npvNote.textContent = 'If NPV > 0, the project usually adds value.';
      } else if (npvVal < 0) {
        npvNote.textContent = 'If NPV < 0, the project usually destroys value.';
      } else {
        npvNote.textContent = 'NPV = 0 means you are exactly at the break-even discount rate.';
      }

      if (irrVal !== null && isFinite(irrVal) && irrVal >= -0.99 && irrVal <= 10) {
        document.getElementById('resultIrr').textContent = (irrVal * 100).toFixed(2) + '%';
      } else {
        document.getElementById('resultIrr').textContent = '—';
      }

      // Cash flow table
      const tbody = document.getElementById('cfTableBody');
      tbody.innerHTML = '';
      for (let t = 0; t < cfs.length; t++) {
        const pv = cfs[t] / Math.pow(1 + rate, t);
        const tr = document.createElement('tr');
        tr.innerHTML = '<td>Year ' + t + '</td><td>' + formatCurrency(cfs[t], symbol) + '</td><td>' + formatCurrency(pv, symbol) + '</td>';
        tbody.appendChild(tr);
      }

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
