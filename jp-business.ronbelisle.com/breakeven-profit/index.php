<?php
// Break-even & Profit (Cost-Volume-Profit) calculator
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
  <meta name="description" content="Find the break-even point and profit in simple English. Good for Japanese college students studying business.">
  <title>Break-even Point and Profit (For Small Business)</title>
  <link rel="stylesheet" href="/css/styles.css">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      background: #f9fafb;
      color: #0f172a;
    }
    .wrap {
      max-width: 960px;
      margin: 0 auto;
      padding: 24px 16px 40px;
    }
    a.back-link {
      display: inline-block;
      margin-bottom: 18px;
      text-decoration: none;
      color: #1d4ed8;
      font-size: 14px;
    }
    a.back-link:hover {
      text-decoration: underline;
    }
    header {
      background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #38bdf8 100%);
      color: #fff;
      border-radius: 18px;
      padding: 22px 18px 20px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.25);
      margin-bottom: 20px;
    }
    header h1 {
      margin: 0 0 8px;
      font-size: 24px;
      letter-spacing: -0.02em;
    }
    header p {
      margin: 4px 0;
      font-size: 15px;
      line-height: 1.5;
    }
    header small {
      display: block;
      margin-top: 6px;
      font-size: 13px;
      opacity: 0.95;
    }
    .info-box {
      margin: 18px 0 20px;
      padding: 14px 14px 12px;
      border-radius: 12px;
      border-left: 4px solid #f59e0b;
      background: #fffbeb;
      font-size: 14px;
      color: #78350f;
    }
    .info-box strong {
      display: block;
      margin-bottom: 4px;
    }
    h2 {
      font-size: 18px;
      margin: 18px 0 8px;
      letter-spacing: -0.01em;
    }
    .hint {
      margin: 0 0 14px;
      font-size: 14px;
      color: #4b5563;
    }
    form {
      margin-top: 8px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 16px;
      margin-bottom: 18px;
    }
    label {
      display: block;
      margin-bottom: 4px;
      font-size: 14px;
      font-weight: 600;
      color: #111827;
    }
    input[type="number"] {
      width: 100%;
      padding: 9px 10px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      font-size: 14px;
    }
    small.help {
      display: block;
      margin-top: 3px;
      font-size: 12px;
      color: #6b7280;
    }
    .button-primary {
      display: inline-block;
      padding: 10px 24px;
      border-radius: 999px;
      background: #1d4ed8;
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      border: none;
      cursor: pointer;
      box-shadow: 0 10px 22px rgba(37, 99, 235, 0.35);
    }
    .button-primary:hover {
      background: #1e40af;
    }
    .center {
      text-align: center;
      margin: 22px 0 10px;
    }
    .results {
      margin-top: 10px;
      padding: 16px 14px 14px;
      border-radius: 14px;
      border: 1px solid rgba(148, 163, 184, 0.7);
      background: #f9fafb;
      display: none;
    }
    .result-row {
      margin-bottom: 10px;
      padding-bottom: 8px;
      border-bottom: 1px solid #e5e7eb;
    }
    .result-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    .result-label {
      font-weight: 600;
      font-size: 14px;
      color: #111827;
    }
    .result-value {
      font-size: 18px;
      font-weight: 800;
      margin-top: 2px;
    }
    .result-note {
      font-size: 13px;
      color: #4b5563;
      margin-top: 2px;
    }
    .example-box, .keywords-box {
      margin-top: 20px;
      padding: 14px 14px 12px;
      border-radius: 12px;
      border: 1px solid rgba(148, 163, 184, 0.7);
      background: #ffffff;
      font-size: 14px;
      color: #111827;
    }
    .example-box h3,
    .keywords-box h3 {
      margin: 0 0 8px;
      font-size: 15px;
      font-weight: 700;
    }
    .example-box ul {
      margin: 6px 0 8px 18px;
      padding: 0;
    }
    .example-box li {
      margin-bottom: 4px;
    }
    .keywords-box dt {
      font-weight: 600;
      margin-top: 4px;
    }
    .keywords-box dd {
      margin: 0 0 4px 0;
      font-size: 13px;
      color: #374151;
    }
    @media (max-width: 640px) {
      header {
        padding: 18px 14px 16px;
        border-radius: 14px;
      }
      header h1 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <a href="/jp-business.ronbelisle.com/" class="back-link">← Back to Business Calculators for Japanese Students</a>

    <header>
      <h1>Break-even Point and Profit (For Small Business)</h1>
      <p>This calculator shows how many units you need to sell to break even (no profit, no loss).</p>
      <p>It also shows profit or loss at your expected sales volume.</p>
      <small>Useful for cafés, online shops, and other small businesses.</small>
    </header>

    <div class="info-box">
      <strong>Key idea</strong>
      <span>Break-even point = sales level where <strong>total revenue = total cost</strong>.</span>
    </div>

    <h2>Step 1 – Enter your numbers</h2>
    <p class="hint">Use yen (¥) or any currency. The examples below use yen.</p>

    <form id="cvpForm">
      <div class="grid">
        <div>
          <label for="pricePerUnit">Selling price per unit (¥)</label>
          <input type="number" id="pricePerUnit" min="0" step="1" value="500">
          <small class="help">Example: You sell one drink for ¥500.</small>
        </div>
        <div>
          <label for="variableCostPerUnit">Variable cost per unit (¥)</label>
          <input type="number" id="variableCostPerUnit" min="0" step="1" value="200">
          <small class="help">Example: Ingredients and cup cost ¥200 per drink.</small>
        </div>
        <div>
          <label for="fixedCosts">Total fixed costs per month (¥)</label>
          <input type="number" id="fixedCosts" min="0" step="1000" value="300000">
          <small class="help">Example: Rent, salary, and utilities are ¥300,000 per month.</small>
        </div>
        <div>
          <label for="expectedUnits">Expected units sold per month</label>
          <input type="number" id="expectedUnits" min="0" step="10" value="1200">
          <small class="help">Example: You expect to sell 1,200 drinks per month.</small>
        </div>
      </div>

      <div class="center">
        <button type="submit" class="button-primary">Calculate Break-even and Profit</button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2>Step 2 – See results</h2>

      <div class="result-row">
        <div class="result-label">Contribution per unit</div>
        <div id="resultContribution" class="result-value"></div>
        <div class="result-note">Contribution per unit = Selling price − Variable cost. This amount helps to cover fixed costs and profit.</div>
      </div>

      <div class="result-row">
        <div class="result-label">Contribution margin ratio</div>
        <div id="resultCmRatio" class="result-value"></div>
        <div class="result-note">Contribution margin ratio = Contribution per unit ÷ Selling price.</div>
      </div>

      <div class="result-row">
        <div class="result-label">Break-even units</div>
        <div id="resultBeUnits" class="result-value"></div>
        <div class="result-note">You must sell at least this many units to have zero profit and zero loss.</div>
      </div>

      <div class="result-row">
        <div class="result-label">Break-even sales (¥)</div>
        <div id="resultBeSales" class="result-value"></div>
        <div class="result-note">This is the sales amount in yen at the break-even point.</div>
      </div>

      <div class="result-row">
        <div class="result-label">Profit (or loss) at expected sales</div>
        <div id="resultProfit" class="result-value"></div>
        <div id="resultProfitNote" class="result-note"></div>
      </div>
    </div>

    <div class="example-box">
      <h3>Example (café)</h3>
      <p>In this example, a small café sells coffee.</p>
      <ul>
        <li>Selling price per unit: ¥500</li>
        <li>Variable cost per unit: ¥200</li>
        <li>Total fixed costs per month: ¥300,000</li>
        <li>Expected units sold per month: 1,200</li>
      </ul>
      <p>The calculator shows how many coffees the café must sell each month to break even and how much profit it makes if it sells 1,200 coffees.</p>
    </div>

    <div class="keywords-box">
      <h3>Key words</h3>
      <dl>
        <dt>Fixed cost</dt>
        <dd>Cost that does not change with sales (rent, basic salary).</dd>
        <dt>Variable cost</dt>
        <dd>Cost that changes with sales (ingredients, packaging).</dd>
        <dt>Contribution per unit</dt>
        <dd>Selling price − Variable cost.</dd>
        <dt>Break-even point</dt>
        <dd>Sales level where profit is zero (total revenue = total cost).</dd>
      </dl>
    </div>
  </div>

  <script>
    function formatCurrencyYen(value) {
      if (!isFinite(value)) return '—';
      const rounded = Math.round(value);
      return '¥' + rounded.toLocaleString('en-US');
    }

    document.getElementById('cvpForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const price = parseFloat(document.getElementById('pricePerUnit').value) || 0;
      const variable = parseFloat(document.getElementById('variableCostPerUnit').value) || 0;
      const fixed = parseFloat(document.getElementById('fixedCosts').value) || 0;
      const expectedUnits = parseFloat(document.getElementById('expectedUnits').value) || 0;

      if (price <= 0 || variable < 0 || fixed < 0 || expectedUnits < 0) {
        alert('Please enter non-negative numbers. Selling price must be greater than 0.');
        return;
      }

      const contribution = price - variable;
      const cmRatio = price > 0 ? (contribution / price) : 0;

      let beUnits = null;
      let beSales = null;
      if (contribution > 0) {
        beUnits = fixed / contribution;
        beSales = beUnits * price;
      }

      const revenueExpected = price * expectedUnits;
      const variableTotal = variable * expectedUnits;
      const profit = revenueExpected - variableTotal - fixed;

      document.getElementById('resultContribution').textContent = formatCurrencyYen(contribution);
      document.getElementById('resultCmRatio').textContent = isFinite(cmRatio) ? (cmRatio * 100).toFixed(1) + '%' : '—';

      if (beUnits !== null && isFinite(beUnits)) {
        document.getElementById('resultBeUnits').textContent = beUnits.toFixed(1) + ' units';
        document.getElementById('resultBeSales').textContent = formatCurrencyYen(beSales);
      } else {
        document.getElementById('resultBeUnits').textContent = '—';
        document.getElementById('resultBeSales').textContent = '—';
      }

      document.getElementById('resultProfit').textContent = formatCurrencyYen(profit);
      const noteEl = document.getElementById('resultProfitNote');
      if (profit > 0) {
        noteEl.textContent = 'Positive number means profit at your expected sales level.';
      } else if (profit < 0) {
        noteEl.textContent = 'Negative number means loss at your expected sales level.';
      } else {
        noteEl.textContent = 'Zero means you are exactly at the break-even point.';
      }

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>

