<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'Loan Payment and Amortization',
    'back' => '← Back to Business Calculators',
    'p1' => 'This calculator shows the monthly payment, total interest, and simple amortization schedule for a loan.',
    'p2' => 'Enter the loan amount, annual interest rate, and term in years. The calculator uses monthly compounding and level payments (the same payment every month).',
    'small' => 'Understanding loan payments helps you compare car loans, student loans, and small‑business borrowing.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'Monthly payment = the fixed amount that, together with interest, repays the loan over the term. Total interest = all interest paid over the life of the loan.',
    'step1' => 'Step 1 – Enter your numbers',
    'hint' => 'Start with a simple example, then change the rate or years to see how the payment and total interest change.',
    'currency' => 'Currency',
    'loan_label' => 'Loan amount (principal)',
    'loan_help' => 'For example: 500000 for a ¥500,000 loan.',
    'rate_label' => 'Annual interest rate (%)',
    'rate_help' => 'Example: if the loan rate is 3.5%, type 3.5.',
    'years_label' => 'Term (years)',
    'years_help' => 'Number of years until the loan is fully repaid.',
    'btn_calc' => 'Calculate payment and interest',
    'step2' => 'Step 2 – See results',
    'result_payment' => 'Monthly payment',
    'result_total_interest' => 'Total interest paid',
    'result_total_paid' => 'Total of payments (principal + interest)',
    'table_title' => 'Amortization by year (summary)',
    'table_year' => 'Year',
    'table_start' => 'Starting balance',
    'table_principal' => 'Principal paid',
    'table_interest' => 'Interest paid',
    'table_end' => 'Ending balance',
    'example_title' => 'Example (car or student loan)',
    'example_p1' => 'Suppose you borrow ¥2,000,000 for 5 years at 3.0% annual interest. The calculator will show the monthly payment and how much interest you pay over the full 5 years.',
    'keywords_title' => 'Key words',
    'kw_principal' => 'Principal',
    'kw_principal_dd' => 'The amount you borrow. Each payment reduces the principal.',
    'kw_interest' => 'Interest',
    'kw_interest_dd' => 'The cost of borrowing, usually shown as an annual percentage rate.',
    'kw_amortization' => 'Amortization',
    'kw_amortization_dd' => 'A schedule that shows how each payment is split between interest and principal, and how the loan balance falls to zero.',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => 'ローン返済額と償却表',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => 'ローンの毎月の返済額、支払う総利息、および簡単な償却表（残高の推移）を計算するツールです。',
    'p2' => '元金、年利率、返済期間（年数）を入力すると、月1回の等額返済を前提に計算します。',
    'small' => '自動車ローン、奨学金、ビジネスローンなどの比較に役立ちます。',
    'key_idea' => 'ポイント',
    'key_idea_text' => '毎月返済額＝期間内にローンを完済するための一定の支払額。総利息＝返済期間を通して支払う利息の合計です。',
    'step1' => 'ステップ1 – 数値を入力',
    'hint' => 'まずは簡単な例で試し、金利や年数を変えて、返済額と総利息がどう変わるか確認してみてください。',
    'currency' => '通貨',
    'loan_label' => '借入金額（元金）',
    'loan_help' => '例：2000000 と入力すると ¥2,000,000 のローン。',
    'rate_label' => '年利率（%）',
    'rate_help' => '例：金利3.5%なら 3.5 と入力。',
    'years_label' => '返済期間（年）',
    'years_help' => 'ローンを完済するまでの年数。',
    'btn_calc' => '毎月返済額と総利息を計算',
    'step2' => 'ステップ2 – 結果を確認',
    'result_payment' => '毎月の返済額',
    'result_total_interest' => '支払う総利息',
    'result_total_paid' => '総支払額（元金＋利息）',
    'table_title' => '年ごとの償却表（サマリー）',
    'table_year' => '年',
    'table_start' => '期首残高',
    'table_principal' => '元金返済額',
    'table_interest' => '利息支払額',
    'table_end' => '期末残高',
    'example_title' => '例（自動車ローン・奨学金など）',
    'example_p1' => '元金 ¥2,000,000、年利率3.0%、返済期間5年のローンを想定します。毎月の返済額と、5年間で支払う利息の合計が表示されます。',
    'keywords_title' => '用語',
    'kw_principal' => '元金',
    'kw_principal_dd' => '借りた金額。返済するたびに元金が減っていきます。',
    'kw_interest' => '利息',
    'kw_interest_dd' => 'お金を借りるためのコスト。通常は年利率（%）で表示されます。',
    'kw_amortization' => '償却表',
    'kw_amortization_dd' => '各返済が利息と元金にどのように分かれるか、残高がゼロになるまでの推移を示した表です。',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
];
$s = $strings[$lang];
$langParam = $lang === 'ja' ? '?lang=ja' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'ja' ? 'ja' : 'en'; ?>">
<head>
  <?php if (file_exists(__DIR__ . '/../../includes/analytics.php')) { include __DIR__ . '/../../includes/analytics.php'; } ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo $lang === 'ja' ? 'ローンの毎月返済額と総利息、償却表の計算。日本の大学生向け。' : 'Loan payment and amortization: monthly payment, total interest, and yearly summary. For Japanese college students studying finance.'; ?>">
  <title><?php echo htmlspecialchars($s['title']); ?></title>
  <link rel="stylesheet" href="/css/styles.css">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background: #f9fafb; color: #0f172a; }
    .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 40px; }
    .lang-toggle { margin-bottom: 12px; font-size: 14px; }
    .lang-toggle a { color: #1d4ed8; text-decoration: none; margin-right: 8px; }
    .lang-toggle a:hover { text-decoration: underline; }
    .lang-toggle a.active { font-weight: 700; color: #0f172a; pointer-events: none; }
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
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 18px; }
    label { display: block; margin-bottom: 4px; font-size: 14px; font-weight: 600; color: #111827; }
    select.currency-select, input[type="number"] { width: 100%; padding: 9px 10px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background-color: #fff; }
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
    .keywords-box dt { font-weight: 600; margin-top: 4px; }
    .keywords-box dd { margin: 0 0 4px 0; font-size: 13px; color: #374151; }
    .amort-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
    .amort-table th, .amort-table td { padding: 8px 10px; text-align: right; border-bottom: 1px solid #e5e7eb; }
    .amort-table th:first-child, .amort-table td:first-child { text-align: left; }
    .amort-table th { font-weight: 600; color: #374151; }
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="lang-toggle">
      <a href="/loan-amortization/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/loan-amortization/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
    </div>
    <a href="/<?php echo $langParam; ?>" class="back-link"><?php echo htmlspecialchars($s['back']); ?></a>

    <header>
      <h1><?php echo htmlspecialchars($s['title']); ?></h1>
      <p><?php echo htmlspecialchars($s['p1']); ?></p>
      <p><?php echo htmlspecialchars($s['p2']); ?></p>
      <small><?php echo htmlspecialchars($s['small']); ?></small>
    </header>

    <div class="info-box">
      <strong><?php echo htmlspecialchars($s['key_idea']); ?></strong>
      <span><?php echo htmlspecialchars($s['key_idea_text']); ?></span>
    </div>

    <h2><?php echo htmlspecialchars($s['step1']); ?></h2>
    <p class="hint"><?php echo htmlspecialchars($s['hint']); ?></p>

    <form id="loanForm">
      <div class="grid">
        <div>
          <label for="currencySymbol"><?php echo htmlspecialchars($s['currency']); ?></label>
          <select id="currencySymbol" class="currency-select">
            <option value="¥" selected>¥ (yen)</option>
            <option value="$">$ (dollars)</option>
          </select>
        </div>
        <div>
          <label for="loanAmount"><?php echo htmlspecialchars($s['loan_label']); ?></label>
          <input type="number" id="loanAmount" step="1" value="2000000">
          <small class="help"><?php echo htmlspecialchars($s['loan_help']); ?></small>
        </div>
        <div>
          <label for="annualRate"><?php echo htmlspecialchars($s['rate_label']); ?></label>
          <input type="number" id="annualRate" min="0" max="100" step="0.1" value="3.0">
          <small class="help"><?php echo htmlspecialchars($s['rate_help']); ?></small>
        </div>
        <div>
          <label for="years"><?php echo htmlspecialchars($s['years_label']); ?></label>
          <input type="number" id="years" min="1" max="40" step="1" value="5">
          <small class="help"><?php echo htmlspecialchars($s['years_help']); ?></small>
        </div>
      </div>
      <div class="center">
        <button type="submit" class="button-primary"><?php echo htmlspecialchars($s['btn_calc']); ?></button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2><?php echo htmlspecialchars($s['step2']); ?></h2>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_payment']); ?></div>
        <div id="resultPayment" class="result-value"></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_total_interest']); ?></div>
        <div id="resultTotalInterest" class="result-value"></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_total_paid']); ?></div>
        <div id="resultTotalPaid" class="result-value"></div>
      </div>
      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;"><?php echo htmlspecialchars($s['table_title']); ?></h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="amort-table" id="amortTable">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars($s['table_year']); ?></th>
                <th><?php echo htmlspecialchars($s['table_start']); ?></th>
                <th><?php echo htmlspecialchars($s['table_principal']); ?></th>
                <th><?php echo htmlspecialchars($s['table_interest']); ?></th>
                <th><?php echo htmlspecialchars($s['table_end']); ?></th>
              </tr>
            </thead>
            <tbody id="amortTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="example-box">
      <h3><?php echo htmlspecialchars($s['example_title']); ?></h3>
      <p><?php echo htmlspecialchars($s['example_p1']); ?></p>
    </div>

    <div class="keywords-box">
      <h3><?php echo htmlspecialchars($s['keywords_title']); ?></h3>
      <dl>
        <dt><?php echo htmlspecialchars($s['kw_principal']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_principal_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_interest']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_interest_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_amortization']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_amortization_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    function formatCurrencyLoan(value, symbol) {
      if (!isFinite(value)) return '—';
      var rounded = Math.round(value);
      var s = rounded.toLocaleString('en-US');
      return symbol === '¥' ? '¥' + s : '$' + s;
    }

    function buildAmortization(principal, annualRatePct, years) {
      var mRate = annualRatePct / 100 / 12;
      var n = years * 12;
      if (principal <= 0 || n <= 0 || mRate < 0) return { payment: 0, schedule: [] };
      var payment;
      if (mRate === 0) {
        payment = principal / n;
      } else {
        payment = principal * mRate / (1 - Math.pow(1 + mRate, -n));
      }
      var schedule = [];
      var balance = principal;
      for (var k = 1; k <= n; k++) {
        var interest = balance * mRate;
        var principalPaid = payment - interest;
        if (principalPaid < 0) principalPaid = 0;
        var newBalance = balance - principalPaid;
        if (newBalance < 0) {
          principalPaid += newBalance;
          newBalance = 0;
        }
        schedule.push({
          month: k,
          startingBalance: balance,
          interest: interest,
          principal: principalPaid,
          endingBalance: newBalance
        });
        balance = newBalance;
      }
      return { payment: payment, schedule: schedule };
    }

    document.getElementById('loanForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var symbol = document.getElementById('currencySymbol').value || '¥';
      var principal = parseFloat(document.getElementById('loanAmount').value) || 0;
      var rate = parseFloat(document.getElementById('annualRate').value) || 0;
      var years = parseInt(document.getElementById('years').value, 10) || 0;

      if (principal <= 0 || years <= 0 || rate < 0) {
        alert('Please enter a positive loan amount and term, and a non-negative interest rate.');
        return;
      }

      var res = buildAmortization(principal, rate, years);
      var payment = res.payment;
      var schedule = res.schedule;

      var totalPaid = payment * schedule.length;
      var totalInterest = totalPaid - principal;

      document.getElementById('resultPayment').textContent = formatCurrencyLoan(payment, symbol) + ' / month';
      document.getElementById('resultTotalInterest').textContent = formatCurrencyLoan(totalInterest, symbol);
      document.getElementById('resultTotalPaid').textContent = formatCurrencyLoan(totalPaid, symbol);

      var tbody = document.getElementById('amortTableBody');
      tbody.innerHTML = '';
      var monthsPerYear = 12;
      var yearCount = Math.ceil(schedule.length / monthsPerYear);
      for (var y = 1; y <= yearCount; y++) {
        var startIndex = (y - 1) * monthsPerYear;
        var endIndex = Math.min(y * monthsPerYear, schedule.length);
        if (startIndex >= schedule.length) break;
        var startBal = schedule[startIndex].startingBalance;
        var endBal = schedule[endIndex - 1].endingBalance;
        var yearInterest = 0;
        var yearPrincipal = 0;
        for (var k = startIndex; k < endIndex; k++) {
          yearInterest += schedule[k].interest;
          yearPrincipal += schedule[k].principal;
        }
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + y + '</td>' +
          '<td>' + formatCurrencyLoan(startBal, symbol) + '</td>' +
          '<td>' + formatCurrencyLoan(yearPrincipal, symbol) + '</td>' +
          '<td>' + formatCurrencyLoan(yearInterest, symbol) + '</td>' +
          '<td>' + formatCurrencyLoan(endBal, symbol) + '</td>';
        tbody.appendChild(tr);
      }

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>

