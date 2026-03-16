<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'Payback and Discounted Payback',
    'back' => '← Back to Business Calculators',
    'p1' => 'This calculator shows how many years it takes to recover your initial investment.',
    'p2' => 'Payback uses undiscounted cash flows. Discounted payback uses the same discount rate as NPV to find when the project’s present value has “paid back” the initial cost. Pairs with the NPV/IRR calculator.',
    'small' => 'Shorter payback means faster recovery. Discounted payback is always longer than (or equal to) payback because it values later cash less.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'Payback = years until cumulative cash flow covers the initial investment. Discounted payback = years until cumulative present value covers the initial investment.',
    'step1' => 'Step 1 – Enter your numbers',
    'hint' => 'Use the same inputs as in the NPV/IRR calculator: initial investment (negative), discount rate, and cash flows by year.',
    'currency' => 'Currency',
    'initial_label' => 'Initial investment (year 0)',
    'initial_help' => 'Use a negative number, e.g. -500000 means you pay ¥500,000 today.',
    'rate_label' => 'Discount rate (% per year)',
    'rate_help' => 'Used for discounted payback only. Example: 8.',
    'cf_year' => 'Cash flow in Year ',
    'cf_year_suffix' => '',
    'btn_calc' => 'Calculate Payback',
    'step2' => 'Step 2 – See results',
    'result_payback' => 'Payback period',
    'result_payback_note' => 'Years to recover the initial investment (undiscounted cash flows).',
    'result_dpayback' => 'Discounted payback period',
    'result_dpayback_note' => 'Years to recover the initial investment using present values at your discount rate.',
    'never' => 'Never',
    'years' => 'years',
    'table_title' => 'Cumulative cash flow and present value',
    'table_year' => 'Year',
    'table_cf' => 'Cash flow',
    'table_cum' => 'Cumulative',
    'table_pv' => 'Present value',
    'table_cumpv' => 'Cumulative PV',
    'example_title' => 'Example (same as NPV/IRR)',
    'example_p1' => 'Initial investment ¥500,000; cash flows ¥200,000 per year for 3 years; discount rate 8%. Payback = 2.5 years (500/200). Discounted payback is longer because later cash is worth less in today’s terms.',
    'keywords_title' => 'Key words',
    'kw_payback' => 'Payback period',
    'kw_payback_dd' => 'Time in years until the sum of (undiscounted) cash flows equals or exceeds the initial investment.',
    'kw_dpayback' => 'Discounted payback',
    'kw_dpayback_dd' => 'Time in years until the sum of present values of cash flows equals or exceeds the initial investment. Uses the same discount rate as NPV.',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => '回収期間と割引回収期間',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => '初期投資を何年で回収できるかを計算するツールです。',
    'p2' => '回収期間は割引しないキャッシュフローで計算します。割引回収期間はNPVと同じ割引率を使い、現在価値の累計が初期投資を回収するまでの年数を示します。NPV・IRRの計算ツールと一緒に使えます。',
    'small' => '回収期間が短いほど早く回収できます。割引回収期間は、後のキャッシュの価値が下がるため、通常は回収期間より長くなります。',
    'key_idea' => 'ポイント',
    'key_idea_text' => '回収期間＝累計キャッシュフローが初期投資に達するまでの年数。割引回収期間＝累計現在価値が初期投資に達するまでの年数。',
    'step1' => 'ステップ1 – 数値を入力',
    'hint' => 'NPV・IRR計算と同じ入力：初期投資（マイナス）、割引率、各年のキャッシュフロー。',
    'currency' => '通貨',
    'initial_label' => '初期投資（0年目）',
    'initial_help' => 'マイナスで入力。例：-500000 は今日 ¥500,000 を支払う意味。',
    'rate_label' => '割引率（年率 %）',
    'rate_help' => '割引回収期間の計算に使用。例：8。',
    'cf_year' => '第',
    'cf_year_suffix' => '年目のキャッシュフロー',
    'btn_calc' => '回収期間を計算',
    'step2' => 'ステップ2 – 結果を確認',
    'result_payback' => '回収期間',
    'result_payback_note' => '初期投資を回収するまでの年数（割引なしのキャッシュフロー）。',
    'result_dpayback' => '割引回収期間',
    'result_dpayback_note' => '割引率で現在価値に直したキャッシュフローの累計が初期投資に達するまでの年数。',
    'never' => '回収不可',
    'years' => '年',
    'table_title' => '累計キャッシュフローと現在価値',
    'table_year' => '年',
    'table_cf' => 'キャッシュフロー',
    'table_cum' => '累計',
    'table_pv' => '現在価値',
    'table_cumpv' => '累計PV',
    'example_title' => '例（NPV・IRRと同じ設定）',
    'example_p1' => '初期投資 ¥500,000、毎年 ¥200,000 のキャッシュフローが3年、割引率8%。回収期間＝2.5年（500/200）。割引回収期間は後のキャッシュの現在価値が小さいため、より長くなります。',
    'keywords_title' => '用語',
    'kw_payback' => '回収期間',
    'kw_payback_dd' => '割引しないキャッシュフローの累計が初期投資に達するまでの年数。',
    'kw_dpayback' => '割引回収期間',
    'kw_dpayback_dd' => 'キャッシュフローの現在価値の累計が初期投資に達するまでの年数。NPVと同じ割引率を使用。',
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
  <meta name="description" content="<?php echo $lang === 'ja' ? '回収期間・割引回収期間の計算。日本の大学生向け。' : 'Payback and discounted payback period. For Japanese college students studying finance.'; ?>">
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
    .keywords-box dt { font-weight: 600; margin-top: 4px; }
    .keywords-box dd { margin: 0 0 4px 0; font-size: 13px; color: #374151; }
    .pb-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
    .pb-table th, .pb-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    .pb-table th { font-weight: 600; color: #374151; }
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="lang-toggle">
      <a href="/payback/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/payback/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
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

    <form id="paybackForm">
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
        <div class="result-label"><?php echo htmlspecialchars($s['result_payback']); ?></div>
        <div id="resultPayback" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_payback_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_dpayback']); ?></div>
        <div id="resultDPayback" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_dpayback_note']); ?></div>
      </div>
      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;"><?php echo htmlspecialchars($s['table_title']); ?></h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="pb-table" id="pbTable">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars($s['table_year']); ?></th>
                <th><?php echo htmlspecialchars($s['table_cf']); ?></th>
                <th><?php echo htmlspecialchars($s['table_cum']); ?></th>
                <th><?php echo htmlspecialchars($s['table_pv']); ?></th>
                <th><?php echo htmlspecialchars($s['table_cumpv']); ?></th>
              </tr>
            </thead>
            <tbody id="pbTableBody"></tbody>
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
        <dt><?php echo htmlspecialchars($s['kw_payback']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_payback_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_dpayback']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_dpayback_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    var neverLabel = <?php echo json_encode($s['never']); ?>;
    var yearsLabel = <?php echo json_encode($s['years']); ?>;
    var tableYear = <?php echo json_encode($s['table_year']); ?>;

    function formatCurrency(value, symbol) {
      if (!isFinite(value)) return '—';
      var rounded = Math.round(value);
      var s = rounded.toLocaleString('en-US');
      return symbol === '¥' ? '¥' + s : '$' + s;
    }

    function paybackYears(initial, cfs) {
      var outlay = Math.abs(initial);
      if (outlay <= 0) return 0;
      var cum = 0;
      for (var t = 1; t < cfs.length; t++) {
        var prev = cum;
        cum += cfs[t];
        if (cum >= outlay) {
          if (Math.abs(cfs[t]) < 1e-9) return null;
          return (t - 1) + (outlay - prev) / cfs[t];
        }
      }
      return null;
    }

    function discountedPaybackYears(initial, cfs, rate) {
      var outlay = Math.abs(initial);
      if (outlay <= 0) return 0;
      var cumPv = 0;
      for (var t = 1; t < cfs.length; t++) {
        var pv = cfs[t] / Math.pow(1 + rate, t);
        var prevPv = cumPv;
        cumPv += pv;
        if (cumPv >= outlay) {
          if (Math.abs(pv) < 1e-9) return null;
          return (t - 1) + (outlay - prevPv) / pv;
        }
      }
      return null;
    }

    document.getElementById('paybackForm').addEventListener('submit', function (e) {
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

      var pb = paybackYears(initial, cfs);
      var dpb = discountedPaybackYears(initial, cfs, rate);

      document.getElementById('resultPayback').textContent = pb !== null
        ? pb.toFixed(2) + ' ' + yearsLabel
        : neverLabel;
      document.getElementById('resultDPayback').textContent = dpb !== null
        ? dpb.toFixed(2) + ' ' + yearsLabel
        : neverLabel;

      var tbody = document.getElementById('pbTableBody');
      tbody.innerHTML = '';
      var cum = 0;
      var cumPv = 0;
      for (var t = 0; t < cfs.length; t++) {
        cum += cfs[t];
        var pv = cfs[t] / Math.pow(1 + rate, t);
        cumPv += pv;
        var tr = document.createElement('tr');
        var yearLabel = tableYear === '年' ? t + tableYear : tableYear + ' ' + t;
        tr.innerHTML = '<td>' + yearLabel + '</td><td>' + formatCurrency(cfs[t], symbol) + '</td><td>' + formatCurrency(cum, symbol) + '</td><td>' + formatCurrency(pv, symbol) + '</td><td>' + formatCurrency(cumPv, symbol) + '</td>';
        tbody.appendChild(tr);
      }

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
