<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'EAR vs APR',
    'back' => '← Back to Business Calculators',
    'p1' => 'Convert an annual percentage rate (APR) to an effective annual rate (EAR) based on how often interest is compounded.',
    'p2' => 'Banks and loans often quote APR. To compare offers or see the true cost of borrowing, use EAR. More compounding per year means higher EAR for the same APR.',
    'small' => 'Useful for both English and Japanese students in finance and banking.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'EAR = (1 + APR/m)^m − 1, where m is the number of compounding periods per year. APR is the stated rate; EAR is what you actually earn or pay over the year.',
    'step1' => 'Step 1 – Enter APR',
    'hint' => 'Enter the stated annual rate as a percentage (e.g. 12 for 12%). Choose how often interest is compounded per year.',
    'apr_label' => 'APR (% per year)',
    'comp_label' => 'Compounding per year',
    'btn_calc' => 'Calculate EAR',
    'step2' => 'Step 2 – See results',
    'result_ear' => 'Effective Annual Rate (EAR)',
    'result_ear_note' => 'The actual rate you earn or pay over one year with this compounding.',
    'table_title' => 'Same APR under different compounding',
    'table_comp' => 'Compounding',
    'table_ear' => 'EAR (%)',
    'example_title' => 'Example',
    'example_p1' => '12% APR compounded monthly: EAR = (1 + 0.12/12)^12 − 1 ≈ 12.68%. So a loan at 12% APR (monthly) has an effective cost of about 12.68% per year.',
    'keywords_title' => 'Key words',
    'kw_apr' => 'APR (Annual Percentage Rate)',
    'kw_apr_dd' => 'The stated annual interest rate, before compounding. Often used in loan and deposit quotes.',
    'kw_ear' => 'EAR (Effective Annual Rate)',
    'kw_ear_dd' => 'The actual annual rate after compounding. Use EAR to compare different loans or investments.',
    'comp_annual' => 'Annual (1×)',
    'comp_semiannual' => 'Semiannual (2×)',
    'comp_quarterly' => 'Quarterly (4×)',
    'comp_monthly' => 'Monthly (12×)',
    'comp_daily' => 'Daily (365×)',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => 'EARとAPR',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => '年率（APR）を、利子の複利回数に応じた実効年率（EAR）に換算します。',
    'p2' => '銀行やローンではAPRで表示されることが多いです。実質的な借入コストや運用利回りを比べるにはEARを使います。複利の回数が多いほど、同じAPRでもEARは高くなります。',
    'small' => 'ファイナンス・金融を学ぶ学生（英語・日本語）向けです。',
    'key_idea' => 'ポイント',
    'key_idea_text' => 'EAR = (1 + APR/m)^m − 1。mは年あたりの複利回数。APRは表示上の年率、EARは1年で実際に受け取りまたは支払う率です。',
    'step1' => 'ステップ1 – APRを入力',
    'hint' => '年率をパーセントで入力（例：12 は12%）。年何回複利か選びます。',
    'apr_label' => 'APR（年率 %）',
    'comp_label' => '年あたりの複利回数',
    'btn_calc' => 'EARを計算',
    'step2' => 'ステップ2 – 結果',
    'result_ear' => '実効年率（EAR）',
    'result_ear_note' => 'この複利条件で1年間に実際に受け取りまたは支払う率。',
    'table_title' => '同じAPRで複利回数を変えたときのEAR',
    'table_comp' => '複利',
    'table_ear' => 'EAR (%)',
    'example_title' => '例',
    'example_p1' => '12% APR・月利複利の場合：EAR = (1 + 0.12/12)^12 − 1 ≒ 12.68%。つまり12% APR（月複利）のローンは実質的に年約12.68%のコストです。',
    'keywords_title' => '用語',
    'kw_apr' => 'APR（年率）',
    'kw_apr_dd' => '複利を考慮する前の表示上の年利。ローンや預金の表示でよく使われる。',
    'kw_ear' => 'EAR（実効年率）',
    'kw_ear_dd' => '複利を反映した実際の年利。ローンや投資の比較に使う。',
    'comp_annual' => '年1回',
    'comp_semiannual' => '半年ごと（年2回）',
    'comp_quarterly' => '四半期ごと（年4回）',
    'comp_monthly' => '月ごと（年12回）',
    'comp_daily' => '日ごと（年365回）',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
];
$host = $_SERVER['HTTP_HOST'] ?? '';
$isBusinessSite = ($host === 'business.ronbelisle.com');
$s = $strings[$lang];
$langParam = $lang === 'ja' ? '?lang=ja' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'ja' ? 'ja' : 'en'; ?>">
<head>
  <?php if (file_exists(__DIR__ . '/../../includes/analytics.php')) { include __DIR__ . '/../../includes/analytics.php'; } ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo $lang === 'ja' ? '実効年率・APRの換算。日本の大学生向け。' : 'EAR vs APR: effective annual rate from stated rate and compounding. For students.'; ?>">
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
    select, input[type="number"] { width: 100%; padding: 9px 10px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background-color: #fff; }
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
    .ear-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
    .ear-table th, .ear-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    .ear-table th { font-weight: 600; color: #374151; }
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <?php if (!$isBusinessSite): ?>
    <div class="lang-toggle">
      <a href="/ear-apr/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/ear-apr/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
    </div>
    <?php endif; ?>
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

    <form id="earForm">
      <div class="grid">
        <div>
          <label for="apr"><?php echo htmlspecialchars($s['apr_label']); ?></label>
          <input type="number" id="apr" step="0.01" min="0" max="100" value="12">
        </div>
        <div>
          <label for="compounding"><?php echo htmlspecialchars($s['comp_label']); ?></label>
          <select id="compounding">
            <option value="1"><?php echo htmlspecialchars($s['comp_annual']); ?></option>
            <option value="2"><?php echo htmlspecialchars($s['comp_semiannual']); ?></option>
            <option value="4"><?php echo htmlspecialchars($s['comp_quarterly']); ?></option>
            <option value="12" selected><?php echo htmlspecialchars($s['comp_monthly']); ?></option>
            <option value="365"><?php echo htmlspecialchars($s['comp_daily']); ?></option>
          </select>
        </div>
      </div>
      <div class="center">
        <button type="submit" class="button-primary"><?php echo htmlspecialchars($s['btn_calc']); ?></button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2><?php echo htmlspecialchars($s['step2']); ?></h2>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_ear']); ?></div>
        <div id="resultEar" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_ear_note']); ?></div>
      </div>
      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;"><?php echo htmlspecialchars($s['table_title']); ?></h3>
        <div class="table-wrapper" style="overflow-x: auto;">
          <table class="ear-table" id="earTable">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars($s['table_comp']); ?></th>
                <th><?php echo htmlspecialchars($s['table_ear']); ?></th>
              </tr>
            </thead>
            <tbody id="earTableBody"></tbody>
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
        <dt><?php echo htmlspecialchars($s['kw_apr']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_apr_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_ear']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_ear_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    var compLabels = {
      1: <?php echo json_encode($s['comp_annual']); ?>,
      2: <?php echo json_encode($s['comp_semiannual']); ?>,
      4: <?php echo json_encode($s['comp_quarterly']); ?>,
      12: <?php echo json_encode($s['comp_monthly']); ?>,
      365: <?php echo json_encode($s['comp_daily']); ?>
    };

    function earFromApr(aprDecimal, m) {
      if (m <= 0) return aprDecimal;
      return Math.pow(1 + aprDecimal / m, m) - 1;
    }

    document.getElementById('earForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var apr = parseFloat(document.getElementById('apr').value) || 0;
      var m = parseInt(document.getElementById('compounding').value, 10) || 1;
      var r = apr / 100;
      var ear = earFromApr(r, m);
      document.getElementById('resultEar').textContent = (ear * 100).toFixed(2) + '%';

      var freqs = [1, 2, 4, 12, 365];
      var tbody = document.getElementById('earTableBody');
      tbody.innerHTML = '';
      freqs.forEach(function (f) {
        var rowEar = earFromApr(r, f);
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + compLabels[f] + '</td><td>' + (rowEar * 100).toFixed(2) + '%</td>';
        tbody.appendChild(tr);
      });

      document.getElementById('results').style.display = 'block';
      document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
