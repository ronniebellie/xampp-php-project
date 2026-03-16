<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'Break-even Point and Profit (For Small Business)',
    'back' => '← Back to Business Calculators',
    'p1' => 'This calculator shows how many units you need to sell to break even (no profit, no loss).',
    'p2' => 'It also shows profit or loss at your expected sales volume.',
    'small' => 'Useful for coffee shops, cafés, online shops, and other small businesses.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'Break-even point = sales level where total revenue = total cost.',
    'step1' => 'Step 1 – Enter your numbers',
    'hint' => 'Choose your currency and enter the numbers. The examples below use yen.',
    'currency' => 'Currency',
    'currency_help' => 'This symbol will be used in all money results.',
    'price_label' => 'Selling price per unit',
    'price_help' => 'Example: You sell one drink for ¥500.',
    'variable_label' => 'Variable cost per unit',
    'variable_help' => 'Example: Ingredients and cup cost ¥200 per drink.',
    'fixed_label' => 'Total fixed costs per month',
    'fixed_help' => 'Example: Rent, salary, and utilities are ¥300,000 per month.',
    'expected_label' => 'Expected units sold per month',
    'expected_help' => 'Example: You expect to sell 1,200 drinks per month.',
    'btn_calc' => 'Calculate Break-even and Profit',
    'step2' => 'Step 2 – See results',
    'result_contribution' => 'Contribution per unit',
    'result_contribution_note' => 'Contribution per unit = Selling price − Variable cost. This amount helps to cover fixed costs and profit.',
    'result_cm_ratio' => 'Contribution margin ratio',
    'result_cm_ratio_note' => 'Contribution margin ratio = Contribution per unit ÷ Selling price.',
    'result_be_units' => 'Break-even units',
    'result_be_units_note' => 'You must sell at least this many units to have zero profit and zero loss.',
    'result_be_sales' => 'Break-even sales',
    'result_be_sales_note' => 'This is the sales amount at the break-even point.',
    'result_profit' => 'Profit (or loss) at expected sales',
    'result_note_profit' => 'Positive number means profit at your expected sales level.',
    'result_note_loss' => 'Negative number means loss at your expected sales level.',
    'result_note_zero' => 'Zero means you are exactly at the break-even point.',
    'units' => ' units',
    'example_title' => 'Example (café)',
    'example_p1' => 'In this example, a small café sells coffee.',
    'example_li1' => 'Selling price per unit: ¥500',
    'example_li2' => 'Variable cost per unit: ¥200',
    'example_li3' => 'Total fixed costs per month: ¥300,000',
    'example_li4' => 'Expected units sold per month: 1,200',
    'example_p2' => 'The calculator shows how many coffees the café must sell each month to break even and how much profit it makes if it sells 1,200 coffees.',
    'keywords_title' => 'Key words',
    'kw_fixed' => 'Fixed cost',
    'kw_fixed_dd' => 'Cost that does not change with sales (rent, basic salary).',
    'kw_variable' => 'Variable cost',
    'kw_variable_dd' => 'Cost that changes with sales (ingredients, packaging).',
    'kw_contribution' => 'Contribution per unit',
    'kw_contribution_dd' => 'Selling price − Variable cost.',
    'kw_be' => 'Break-even point',
    'kw_be_dd' => 'Sales level where profit is zero (total revenue = total cost).',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
    'validation_msg' => 'Please enter non-negative numbers. Selling price must be greater than 0.',
  ],
  'ja' => [
    'title' => '損益分岐点と利益（小規模ビジネス向け）',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => 'この計算ツールでは、損益分岐（利益も損失もない状態）に必要な販売数量がわかります。',
    'p2' => '想定売上での利益または損失も表示します。',
    'small' => 'コーヒーショップ、カフェ、ネットショップなどの例に使えます。',
    'key_idea' => 'ポイント',
    'key_idea_text' => '損益分岐点 ＝ 売上高と総費用が等しくなる販売水準です。',
    'step1' => 'ステップ1 – 数値を入力',
    'hint' => '通貨を選び、数値を入力してください。下の例では円を使っています。',
    'currency' => '通貨',
    'currency_help' => '金額の表示にこの記号を使います。',
    'price_label' => '単価（販売価格）',
    'price_help' => '例：1杯 ¥500 で販売する場合。',
    'variable_label' => '単位あたり変動費',
    'variable_help' => '例：材料とカップで1杯あたり ¥200。',
    'fixed_label' => '月間固定費の合計',
    'fixed_help' => '例：家賃・人件費・光熱費で月 ¥300,000。',
    'expected_label' => '月間の想定販売数量',
    'expected_help' => '例：月に1,200杯売れる想定の場合。',
    'btn_calc' => '損益分岐点と利益を計算',
    'step2' => 'ステップ2 – 結果を確認',
    'result_contribution' => '単位あたり貢献利益',
    'result_contribution_note' => '貢献利益 ＝ 販売価格 − 変動費。固定費と利益をまかなう部分です。',
    'result_cm_ratio' => '貢献利益率',
    'result_cm_ratio_note' => '貢献利益率 ＝ 単位あたり貢献利益 ÷ 販売価格。',
    'result_be_units' => '損益分岐点の数量',
    'result_be_units_note' => '利益も損失も出さないために、少なくともこの数量を販売する必要があります。',
    'result_be_sales' => '損益分岐点の売上高',
    'result_be_sales_note' => '損益分岐点での売上高です。',
    'result_profit' => '想定売上での利益（または損失）',
    'result_note_profit' => 'プラスは利益、マイナスは損失を表します。',
    'result_note_loss' => '想定売上では損失になります。',
    'result_note_zero' => '損益分岐点ちょうどです。',
    'units' => ' 単位',
    'example_title' => '例（カフェ）',
    'example_p1' => '小さなカフェがコーヒーを販売する例です。',
    'example_li1' => '単価：¥500',
    'example_li2' => '単位あたり変動費：¥200',
    'example_li3' => '月間固定費：¥300,000',
    'example_li4' => '月間想定販売数：1,200杯',
    'example_p2' => '月に何杯売れば損益分岐するか、1,200杯売った場合の利益がわかります。',
    'keywords_title' => '用語',
    'kw_fixed' => '固定費',
    'kw_fixed_dd' => '売上に関係なくかかる費用（家賃、基本給など）。',
    'kw_variable' => '変動費',
    'kw_variable_dd' => '売上に応じて変わる費用（材料、包装など）。',
    'kw_contribution' => '単位あたり貢献利益',
    'kw_contribution_dd' => '販売価格 − 変動費。',
    'kw_be' => '損益分岐点',
    'kw_be_dd' => '利益がゼロになる販売水準（売上高＝総費用）。',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
    'validation_msg' => '0以上の数を入力してください。販売価格は0より大きい必要があります。',
  ],
];
$s = $strings[$lang];
$langParam = $lang === 'ja' ? '?lang=ja' : '';
$baseUrl = '/';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'ja' ? 'ja' : 'en'; ?>">
<head>
  <?php if (file_exists(__DIR__ . '/../../includes/analytics.php')) { include __DIR__ . '/../../includes/analytics.php'; } ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo $lang === 'ja' ? '損益分岐点と利益を計算。日本の大学生向け。' : 'Find the break-even point and profit in simple English. Good for Japanese college students studying business.'; ?>">
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
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-bottom: 18px; }
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
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="lang-toggle">
      <a href="/breakeven-profit/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/breakeven-profit/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
    </div>
    <a href="<?php echo htmlspecialchars($baseUrl . $langParam); ?>" class="back-link"><?php echo htmlspecialchars($s['back']); ?></a>

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

    <form id="cvpForm">
      <div class="grid">
        <div>
          <label for="currencySymbol"><?php echo htmlspecialchars($s['currency']); ?></label>
          <select id="currencySymbol" class="currency-select">
            <option value="¥" selected>¥ (yen)</option>
            <option value="$">$ (dollars)</option>
          </select>
          <small class="help"><?php echo htmlspecialchars($s['currency_help']); ?></small>
        </div>
        <div>
          <label for="pricePerUnit"><?php echo htmlspecialchars($s['price_label']); ?></label>
          <input type="number" id="pricePerUnit" min="0" step="1" value="500">
          <small class="help"><?php echo htmlspecialchars($s['price_help']); ?></small>
        </div>
        <div>
          <label for="variableCostPerUnit"><?php echo htmlspecialchars($s['variable_label']); ?></label>
          <input type="number" id="variableCostPerUnit" min="0" step="1" value="200">
          <small class="help"><?php echo htmlspecialchars($s['variable_help']); ?></small>
        </div>
        <div>
          <label for="fixedCosts"><?php echo htmlspecialchars($s['fixed_label']); ?></label>
          <input type="number" id="fixedCosts" min="0" step="1000" value="300000">
          <small class="help"><?php echo htmlspecialchars($s['fixed_help']); ?></small>
        </div>
        <div>
          <label for="expectedUnits"><?php echo htmlspecialchars($s['expected_label']); ?></label>
          <input type="number" id="expectedUnits" min="0" step="10" value="1200">
          <small class="help"><?php echo htmlspecialchars($s['expected_help']); ?></small>
        </div>
      </div>
      <div class="center">
        <button type="submit" class="button-primary"><?php echo htmlspecialchars($s['btn_calc']); ?></button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2><?php echo htmlspecialchars($s['step2']); ?></h2>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_contribution']); ?></div>
        <div id="resultContribution" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_contribution_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_cm_ratio']); ?></div>
        <div id="resultCmRatio" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_cm_ratio_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_be_units']); ?></div>
        <div id="resultBeUnits" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_be_units_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_be_sales']); ?></div>
        <div id="resultBeSales" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_be_sales_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_profit']); ?></div>
        <div id="resultProfit" class="result-value"></div>
        <div id="resultProfitNote" class="result-note"></div>
      </div>
    </div>

    <div class="example-box">
      <h3><?php echo htmlspecialchars($s['example_title']); ?></h3>
      <p><?php echo htmlspecialchars($s['example_p1']); ?></p>
      <ul>
        <li><?php echo htmlspecialchars($s['example_li1']); ?></li>
        <li><?php echo htmlspecialchars($s['example_li2']); ?></li>
        <li><?php echo htmlspecialchars($s['example_li3']); ?></li>
        <li><?php echo htmlspecialchars($s['example_li4']); ?></li>
      </ul>
      <p><?php echo htmlspecialchars($s['example_p2']); ?></p>
    </div>

    <div class="keywords-box">
      <h3><?php echo htmlspecialchars($s['keywords_title']); ?></h3>
      <dl>
        <dt><?php echo htmlspecialchars($s['kw_fixed']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_fixed_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_variable']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_variable_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_contribution']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_contribution_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_be']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_be_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    var resultNotes = {
      profit: <?php echo json_encode($s['result_note_profit']); ?>,
      loss: <?php echo json_encode($s['result_note_loss']); ?>,
      zero: <?php echo json_encode($s['result_note_zero']); ?>
    };
    var unitsLabel = <?php echo json_encode($s['units']); ?>;
    var validationMsg = <?php echo json_encode($s['validation_msg']); ?>;

    function formatCurrency(value, symbol) {
      if (!isFinite(value)) return '—';
      var rounded = Math.round(value);
      return symbol + rounded.toLocaleString('en-US');
    }

    document.getElementById('cvpForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var symbol = document.getElementById('currencySymbol').value || '¥';
      var price = parseFloat(document.getElementById('pricePerUnit').value) || 0;
      var variable = parseFloat(document.getElementById('variableCostPerUnit').value) || 0;
      var fixed = parseFloat(document.getElementById('fixedCosts').value) || 0;
      var expectedUnits = parseFloat(document.getElementById('expectedUnits').value) || 0;

      if (price <= 0 || variable < 0 || fixed < 0 || expectedUnits < 0) {
        alert(validationMsg);
        return;
      }

      var contribution = price - variable;
      var cmRatio = price > 0 ? (contribution / price) : 0;
      var beUnits = null, beSales = null;
      if (contribution > 0) {
        beUnits = fixed / contribution;
        beSales = beUnits * price;
      }
      var revenueExpected = price * expectedUnits;
      var variableTotal = variable * expectedUnits;
      var profit = revenueExpected - variableTotal - fixed;

      document.getElementById('resultContribution').textContent = formatCurrency(contribution, symbol);
      document.getElementById('resultCmRatio').textContent = isFinite(cmRatio) ? (cmRatio * 100).toFixed(1) + '%' : '—';
      if (beUnits !== null && isFinite(beUnits)) {
        document.getElementById('resultBeUnits').textContent = beUnits.toFixed(1) + unitsLabel;
        document.getElementById('resultBeSales').textContent = formatCurrency(beSales, symbol);
      } else {
        document.getElementById('resultBeUnits').textContent = '—';
        document.getElementById('resultBeSales').textContent = '—';
      }
      document.getElementById('resultProfit').textContent = formatCurrency(profit, symbol);
      var noteEl = document.getElementById('resultProfitNote');
      noteEl.textContent = profit > 0 ? resultNotes.profit : (profit < 0 ? resultNotes.loss : resultNotes.zero);

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
