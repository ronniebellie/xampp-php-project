<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'NPV and IRR (For Investment Decisions)',
    'back' => '← Back to Business Calculators for Japanese Students',
    'p1' => 'This calculator helps you decide if an investment project is good.',
    'p2' => 'You enter the cost today and the cash you expect to receive in future years. The calculator shows NPV and IRR.',
    'small' => 'If NPV is positive, the project usually adds value. If NPV is negative, the project usually destroys value.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'NPV = today’s value of all future cash flows minus the initial cost. IRR = the discount rate that makes NPV equal to zero.',
    'step1' => 'Step 1 – Enter your numbers',
    'hint' => 'Choose your currency. Use a negative number for the initial investment (money you pay out today).',
    'currency' => 'Currency',
    'initial_label' => 'Initial investment (year 0)',
    'initial_help' => 'Use a negative number, e.g. -500000 means you pay ¥500,000 today.',
    'rate_label' => 'Discount rate (% per year)',
    'rate_help' => 'Example: if your required return is 8%, type 8.',
    'cf_year' => 'Cash flow in Year ',
    'cf_year_suffix' => '',
    'btn_calc' => 'Calculate NPV and IRR',
    'step2' => 'Step 2 – See results',
    'result_npv' => 'Net Present Value (NPV)',
    'result_npv_positive' => 'If NPV > 0, the project usually adds value.',
    'result_npv_negative' => 'If NPV < 0, the project usually destroys value.',
    'result_npv_zero' => 'NPV = 0 means you are exactly at the break-even discount rate.',
    'result_irr' => 'Internal Rate of Return (IRR)',
    'result_irr_note' => 'IRR is the discount rate that makes NPV = 0. If IRR is higher than your required return, the project is usually good.',
    'table_title' => 'Cash flows (summary)',
    'table_year' => 'Year',
    'table_cf' => 'Cash flow',
    'table_pv' => 'Present value',
    'example_title' => 'Example (new machine)',
    'example_p1' => 'A company pays ¥500,000 today for a machine. It expects to receive ¥200,000 at the end of each of the next 3 years. The required return is 8%.',
    'example_li1' => 'Initial investment: -500,000',
    'example_li2' => 'Discount rate: 8%',
    'example_li3' => 'Year 1: 200,000 · Year 2: 200,000 · Year 3: 200,000',
    'example_p2' => 'In this example, if NPV is positive, the machine is a good investment. If IRR is above 8%, the project adds value.',
    'keywords_title' => 'Key words',
    'kw_npv' => 'NPV (Net Present Value)',
    'kw_npv_dd' => 'Today’s value of all future cash flows minus the initial cost. Positive NPV means the project adds value.',
    'kw_irr' => 'IRR (Internal Rate of Return)',
    'kw_irr_dd' => 'The discount rate that makes NPV equal to zero. If IRR is higher than your required return, the project is usually good.',
    'kw_discount' => 'Discount rate',
    'kw_discount_dd' => 'The rate you use to bring future cash back to today’s value. It reflects risk and opportunity cost.',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => 'NPV・IRR（投資判断のための計算）',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => '投資プロジェクトが採算に合うかを判断するための計算ツールです。',
    'p2' => '今日の投資額と、今後受け取るキャッシュフローを入力すると、NPV（正味現在価値）とIRR（内部収益率）が表示されます。',
    'small' => 'NPVがプラスならプロジェクトは価値を生み、マイナスなら価値を損ないます。',
    'key_idea' => 'ポイント',
    'key_idea_text' => 'NPV＝将来のキャッシュフローの現在価値から初期投資を引いたもの。IRR＝NPVをゼロにする割引率です。',
    'step1' => 'ステップ1 – 数値を入力',
    'hint' => '通貨を選び、初期投資はマイナスで入力してください（今日支払う金額）。',
    'currency' => '通貨',
    'initial_label' => '初期投資（0年目）',
    'initial_help' => 'マイナスで入力。例：-500000 は今日 ¥500,000 を支払う意味。',
    'rate_label' => '割引率（年率 %）',
    'rate_help' => '例：必要収益率が8%なら 8 と入力。',
    'cf_year' => '第',
    'cf_year_suffix' => '年目のキャッシュフロー',
    'btn_calc' => 'NPVとIRRを計算',
    'step2' => 'ステップ2 – 結果を確認',
    'result_npv' => '正味現在価値（NPV）',
    'result_npv_positive' => 'NPV > 0 なら、通常はプロジェクトは価値を生みます。',
    'result_npv_negative' => 'NPV < 0 なら、通常はプロジェクトは価値を損ないます。',
    'result_npv_zero' => 'NPV = 0 は割引率が損益分岐点であることを示します。',
    'result_irr' => '内部収益率（IRR）',
    'result_irr_note' => 'IRRはNPVを0にする割引率です。必要収益率よりIRRが高ければ、通常は採算が取れます。',
    'table_title' => 'キャッシュフロー（概要）',
    'table_year' => '年',
    'table_cf' => 'キャッシュフロー',
    'table_pv' => '現在価値',
    'example_title' => '例（新規機械の購入）',
    'example_p1' => '会社が今日 ¥500,000 で機械を購入し、今後3年間は毎年末に ¥200,000 を受け取るとします。必要収益率は8%。',
    'example_li1' => '初期投資：-500,000',
    'example_li2' => '割引率：8%',
    'example_li3' => '1年目：200,000・2年目：200,000・3年目：200,000',
    'example_p2' => 'この例でNPVがプラスなら投資は妥当。IRRが8%を上回っていればプロジェクトは価値を生みます。',
    'keywords_title' => '用語',
    'kw_npv' => 'NPV（正味現在価値）',
    'kw_npv_dd' => '将来のキャッシュフローの現在価値から初期投資を引いたもの。NPVがプラスなら価値を生む。',
    'kw_irr' => 'IRR（内部収益率）',
    'kw_irr_dd' => 'NPVをゼロにする割引率。必要収益率より高ければ通常は採算が取れる。',
    'kw_discount' => '割引率',
    'kw_discount_dd' => '将来のキャッシュを現在価値に換算するときに使う率。リスクと機会費用を反映する。',
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
  <meta name="description" content="<?php echo $lang === 'ja' ? 'NPV・IRRの計算。日本の大学生向け。' : 'Net Present Value and Internal Rate of Return in simple English. For Japanese college students studying finance.'; ?>">
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
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 18px; }
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
    <div class="lang-toggle">
      <a href="/npv-irr/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/npv-irr/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
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

    <form id="npvForm">
      <div class="grid">
        <div>
          <label for="currencySymbol"><?php echo htmlspecialchars($s['currency']); ?></label>
          <select id="currencySymbol" class="currency-select">
            <option value="¥" selected>¥ (yen)</option>
            <option value="$">$ (dollars)</option>
          </select>
        </div>
        <div>
          <label for="initialInv"><?php echo htmlspecialchars($s['initial_label']); ?></label>
          <input type="number" id="initialInv" step="1" value="-500000">
          <small class="help"><?php echo htmlspecialchars($s['initial_help']); ?></small>
        </div>
        <div>
          <label for="discountRate"><?php echo htmlspecialchars($s['rate_label']); ?></label>
          <input type="number" id="discountRate" min="0" max="100" step="0.5" value="8">
          <small class="help"><?php echo htmlspecialchars($s['rate_help']); ?></small>
        </div>
      </div>
      <div class="grid">
        <div>
          <label for="cf1"><?php echo htmlspecialchars($lang === 'ja' ? $s['cf_year'].'1'.$s['cf_year_suffix'] : $s['cf_year'].'1'); ?></label>
          <input type="number" id="cf1" step="1" value="200000">
        </div>
        <div>
          <label for="cf2"><?php echo htmlspecialchars($lang === 'ja' ? $s['cf_year'].'2'.$s['cf_year_suffix'] : $s['cf_year'].'2'); ?></label>
          <input type="number" id="cf2" step="1" value="200000">
        </div>
        <div>
          <label for="cf3"><?php echo htmlspecialchars($lang === 'ja' ? $s['cf_year'].'3'.$s['cf_year_suffix'] : $s['cf_year'].'3'); ?></label>
          <input type="number" id="cf3" step="1" value="200000">
        </div>
        <div>
          <label for="cf4"><?php echo htmlspecialchars($lang === 'ja' ? $s['cf_year'].'4'.$s['cf_year_suffix'] : $s['cf_year'].'4'); ?></label>
          <input type="number" id="cf4" step="1" value="0">
        </div>
        <div>
          <label for="cf5"><?php echo htmlspecialchars($lang === 'ja' ? $s['cf_year'].'5'.$s['cf_year_suffix'] : $s['cf_year'].'5'); ?></label>
          <input type="number" id="cf5" step="1" value="0">
        </div>
      </div>
      <div class="center">
        <button type="submit" class="button-primary"><?php echo htmlspecialchars($s['btn_calc']); ?></button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2><?php echo htmlspecialchars($s['step2']); ?></h2>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_npv']); ?></div>
        <div id="resultNpv" class="result-value"></div>
        <div id="resultNpvNote" class="result-note"></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_irr']); ?></div>
        <div id="resultIrr" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_irr_note']); ?></div>
      </div>
      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;"><?php echo htmlspecialchars($s['table_title']); ?></h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="cf-table" id="cfTable">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars($s['table_year']); ?></th>
                <th><?php echo htmlspecialchars($s['table_cf']); ?></th>
                <th><?php echo htmlspecialchars($s['table_pv']); ?></th>
              </tr>
            </thead>
            <tbody id="cfTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="example-box">
      <h3><?php echo htmlspecialchars($s['example_title']); ?></h3>
      <p><?php echo htmlspecialchars($s['example_p1']); ?></p>
      <ul>
        <li><?php echo htmlspecialchars($s['example_li1']); ?></li>
        <li><?php echo htmlspecialchars($s['example_li2']); ?></li>
        <li><?php echo htmlspecialchars($s['example_li3']); ?></li>
      </ul>
      <p><?php echo htmlspecialchars($s['example_p2']); ?></p>
    </div>

    <div class="keywords-box">
      <h3><?php echo htmlspecialchars($s['keywords_title']); ?></h3>
      <dl>
        <dt><?php echo htmlspecialchars($s['kw_npv']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_npv_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_irr']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_irr_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_discount']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_discount_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    var npvNotes = {
      positive: <?php echo json_encode($s['result_npv_positive']); ?>,
      negative: <?php echo json_encode($s['result_npv_negative']); ?>,
      zero: <?php echo json_encode($s['result_npv_zero']); ?>
    };
    var tableYear = <?php echo json_encode($s['table_year']); ?>;

    function formatCurrency(value, symbol) {
      if (!isFinite(value)) return '—';
      var rounded = Math.round(value);
      var s = rounded.toLocaleString('en-US');
      return symbol === '¥' ? '¥' + s : '$' + s;
    }

    function npv(rate, cfs) {
      var sum = 0;
      for (var t = 0; t < cfs.length; t++) {
        sum += cfs[t] / Math.pow(1 + rate, t);
      }
      return sum;
    }

    function irr(cfs, guess) {
      guess = guess || 0.1;
      var r = guess;
      for (var i = 0; i < 100; i++) {
        var v = npv(r, cfs);
        if (Math.abs(v) < 1) return r;
        var dr = 0.0001;
        var v2 = npv(r + dr, cfs);
        var slope = (v2 - v) / dr;
        r = r - v / slope;
        if (r < -0.99) r = -0.99;
        if (r > 10) r = 10;
      }
      return r;
    }

    document.getElementById('npvForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var symbol = document.getElementById('currencySymbol').value || '¥';
      var initial = parseFloat(document.getElementById('initialInv').value) || 0;
      var rate = parseFloat(document.getElementById('discountRate').value) / 100 || 0;
      var cf1 = parseFloat(document.getElementById('cf1').value) || 0;
      var cf2 = parseFloat(document.getElementById('cf2').value) || 0;
      var cf3 = parseFloat(document.getElementById('cf3').value) || 0;
      var cf4 = parseFloat(document.getElementById('cf4').value) || 0;
      var cf5 = parseFloat(document.getElementById('cf5').value) || 0;
      var cfs = [initial, cf1, cf2, cf3, cf4, cf5];
      var npvVal = npv(rate, cfs);
      var irrVal = null;
      try { irrVal = irr(cfs); } catch (err) { irrVal = null; }

      document.getElementById('resultNpv').textContent = formatCurrency(npvVal, symbol);
      var npvNoteEl = document.getElementById('resultNpvNote');
      npvNoteEl.textContent = npvVal > 0 ? npvNotes.positive : (npvVal < 0 ? npvNotes.negative : npvNotes.zero);

      if (irrVal !== null && isFinite(irrVal) && irrVal >= -0.99 && irrVal <= 10) {
        document.getElementById('resultIrr').textContent = (irrVal * 100).toFixed(2) + '%';
      } else {
        document.getElementById('resultIrr').textContent = '—';
      }

      var tbody = document.getElementById('cfTableBody');
      tbody.innerHTML = '';
      for (var t = 0; t < cfs.length; t++) {
        var pv = cfs[t] / Math.pow(1 + rate, t);
        var tr = document.createElement('tr');
        var yearLabel = tableYear === '年' ? t + tableYear : tableYear + ' ' + t;
        tr.innerHTML = '<td>' + yearLabel + '</td><td>' + formatCurrency(cfs[t], symbol) + '</td><td>' + formatCurrency(pv, symbol) + '</td>';
        tbody.appendChild(tr);
      }

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
