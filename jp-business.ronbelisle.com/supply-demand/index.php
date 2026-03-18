<?php
require_once __DIR__ . '/../includes/lang.php';

$strings = [
  'en' => [
    'title' => 'Supply and Demand Equilibrium',
    'back' => '← Back to Business Calculators',
    'p1' => 'This calculator finds the equilibrium price and quantity in a simple market.',
    'p2' => 'You enter a linear demand equation and a linear supply equation. Where they cross is the equilibrium.',
    'small' => 'At equilibrium, quantity demanded equals quantity supplied.',
    'key_idea' => 'Key idea',
    'key_idea_text' => 'Demand: Qd = a − b×P (quantity demanded falls when price rises). Supply: Qs = c + d×P (quantity supplied rises when price rises). Equilibrium is where Qd = Qs.',
    'step1' => 'Step 1 – Enter your equations',
    'hint' => 'Use the form Qd = a − b×P and Qs = c + d×P. Enter the numbers for a, b, c, and d.',
    'demand_label' => 'Demand: Qd = a − b×P',
    'demand_a' => 'Demand intercept (a)',
    'demand_a_help' => 'When price is 0, quantity demanded = a. Example: 120.',
    'demand_b' => 'Demand slope (b)',
    'demand_b_help' => 'When price goes up by 1, quantity demanded goes down by b. Example: 2.',
    'supply_label' => 'Supply: Qs = c + d×P',
    'supply_c' => 'Supply intercept (c)',
    'supply_c_help' => 'When price is 0, quantity supplied = c. Example: 10.',
    'supply_d' => 'Supply slope (d)',
    'supply_d_help' => 'When price goes up by 1, quantity supplied goes up by d. Example: 1.',
    'btn_calc' => 'Find Equilibrium',
    'step2' => 'Step 2 – See results',
    'result_p' => 'Equilibrium price (P*)',
    'result_p_note' => 'At this price, quantity demanded equals quantity supplied.',
    'result_q' => 'Equilibrium quantity (Q*)',
    'result_q_note' => 'This is the quantity traded in the market at equilibrium.',
    'graph_title' => 'Supply and demand graph',
    'graph_caption' => 'The lines show demand and supply. The intersection is the equilibrium point (P*, Q*).',
    'no_equilibrium' => 'No equilibrium found. Check that demand slopes down (b > 0) and supply slopes up (d > 0), and that the lines cross in the positive quadrant.',
    'example_title' => 'Example',
    'example_p1' => 'Demand: Qd = 120 − 2×P. Supply: Qs = 10 + 1×P. So a = 120, b = 2, c = 10, d = 1.',
    'example_p2' => 'The calculator finds the price and quantity where 120 − 2×P = 10 + 1×P. That is the equilibrium.',
    'keywords_title' => 'Key words',
    'kw_demand' => 'Demand',
    'kw_demand_dd' => 'How much buyers want to buy at each price. Usually when price rises, quantity demanded falls.',
    'kw_supply' => 'Supply',
    'kw_supply_dd' => 'How much sellers want to sell at each price. Usually when price rises, quantity supplied rises.',
    'kw_equilibrium' => 'Equilibrium',
    'kw_equilibrium_dd' => 'The price and quantity where quantity demanded equals quantity supplied. No shortage, no surplus.',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => '需要と供給の均衡',
    'back' => '← ビジネス計算ツール一覧に戻る',
    'p1' => '需要曲線と供給曲線が交わる均衡価格と均衡数量を求めます。',
    'p2' => '一次の需要式と供給式を入力すると、交点が均衡になります。',
    'small' => '均衡では、需要量と供給量が一致します。',
    'key_idea' => 'ポイント',
    'key_idea_text' => '需要：Qd = a − b×P（価格が上がると需要量は減る）。供給：Qs = c + d×P（価格が上がると供給量は増える）。Qd = Qs となる点が均衡です。',
    'step1' => 'ステップ1 – 式を入力',
    'hint' => 'Qd = a − b×P と Qs = c + d×P の形で、a・b・c・d の値を入力してください。',
    'demand_label' => '需要：Qd = a − b×P',
    'demand_a' => '需要の切片 (a)',
    'demand_a_help' => '価格が0のときの需要量。例：120。',
    'demand_b' => '需要の傾き (b)',
    'demand_b_help' => '価格が1上がると需要量は b 減る。例：2。',
    'supply_label' => '供給：Qs = c + d×P',
    'supply_c' => '供給の切片 (c)',
    'supply_c_help' => '価格が0のときの供給量。例：10。',
    'supply_d' => '供給の傾き (d)',
    'supply_d_help' => '価格が1上がると供給量は d 増える。例：1。',
    'btn_calc' => '均衡を求める',
    'step2' => 'ステップ2 – 結果を確認',
    'result_p' => '均衡価格 (P*)',
    'result_p_note' => 'この価格で需要量と供給量が一致します。',
    'result_q' => '均衡数量 (Q*)',
    'result_q_note' => '均衡時の取引数量です。',
    'graph_title' => '需要と供給のグラフ',
    'graph_caption' => '需要曲線と供給曲線の交点が均衡点 (P*, Q*) です。',
    'no_equilibrium' => '均衡が見つかりません。需要は右下がり（b > 0）、供給は右上がり（d > 0）で、両方が正の範囲で交わるか確認してください。',
    'example_title' => '例',
    'example_p1' => '需要：Qd = 120 − 2×P。供給：Qs = 10 + 1×P。つまり a=120, b=2, c=10, d=1。',
    'example_p2' => '120 − 2×P = 10 + 1×P となる価格と数量が均衡です。',
    'keywords_title' => '用語',
    'kw_demand' => '需要',
    'kw_demand_dd' => '各価格で買い手が購入したい数量。通常、価格が上がると需要量は減る。',
    'kw_supply' => '供給',
    'kw_supply_dd' => '各価格で売り手が販売したい数量。通常、価格が上がると供給量は増える。',
    'kw_equilibrium' => '均衡',
    'kw_equilibrium_dd' => '需要量と供給量が一致する価格と数量。過不足なし。',
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
  <meta name="description" content="<?php echo $lang === 'ja' ? '需要・供給の均衡を計算。日本の大学生向け。' : 'Supply and demand equilibrium in simple English. For Japanese college students studying microeconomics.'; ?>">
  <title><?php echo htmlspecialchars($s['title']); ?></title>
  <?php
    $og_title = $ld_name = $s['title'];
    $og_description = $ld_description = ($lang === 'ja')
      ? '需要・供給の均衡を計算。日本の大学生向け。'
      : 'Supply and demand equilibrium calculator for students.';
    include(__DIR__ . '/../../includes/og-twitter-meta.php');
    include(__DIR__ . '/../../includes/json-ld-softwareapp.php');
  ?>
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
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 18px; }
    label { display: block; margin-bottom: 4px; font-size: 14px; font-weight: 600; color: #111827; }
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
    .graph-wrap { margin: 16px 0; padding: 12px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
    .graph-wrap canvas { display: block; max-width: 100%; height: auto; }
    .example-box, .keywords-box { margin-top: 20px; padding: 14px 14px 12px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.7); background: #fff; font-size: 14px; color: #111827; }
    .example-box h3, .keywords-box h3 { margin: 0 0 8px; font-size: 15px; font-weight: 700; }
    .keywords-box dt { font-weight: 600; margin-top: 4px; }
    .keywords-box dd { margin: 0 0 4px 0; font-size: 13px; color: #374151; }
    @media (max-width: 640px) { header { padding: 18px 14px 16px; border-radius: 14px; } header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <?php if (!$isBusinessSite): ?>
    <div class="lang-toggle">
      <a href="/supply-demand/?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="/supply-demand/?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
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

    <form id="sdForm">
      <p style="font-weight: 600; margin-bottom: 8px;"><?php echo htmlspecialchars($s['demand_label']); ?></p>
      <div class="grid">
        <div>
          <label for="paramA"><?php echo htmlspecialchars($s['demand_a']); ?></label>
          <input type="number" id="paramA" step="1" value="120">
          <small class="help"><?php echo htmlspecialchars($s['demand_a_help']); ?></small>
        </div>
        <div>
          <label for="paramB"><?php echo htmlspecialchars($s['demand_b']); ?></label>
          <input type="number" id="paramB" step="0.1" value="2">
          <small class="help"><?php echo htmlspecialchars($s['demand_b_help']); ?></small>
        </div>
      </div>
      <p style="font-weight: 600; margin: 16px 0 8px;"><?php echo htmlspecialchars($s['supply_label']); ?></p>
      <div class="grid">
        <div>
          <label for="paramC"><?php echo htmlspecialchars($s['supply_c']); ?></label>
          <input type="number" id="paramC" step="1" value="10">
          <small class="help"><?php echo htmlspecialchars($s['supply_c_help']); ?></small>
        </div>
        <div>
          <label for="paramD"><?php echo htmlspecialchars($s['supply_d']); ?></label>
          <input type="number" id="paramD" step="0.1" value="1">
          <small class="help"><?php echo htmlspecialchars($s['supply_d_help']); ?></small>
        </div>
      </div>
      <div class="center">
        <button type="submit" class="button-primary"><?php echo htmlspecialchars($s['btn_calc']); ?></button>
      </div>
    </form>

    <div id="results" class="results" aria-live="polite">
      <h2><?php echo htmlspecialchars($s['step2']); ?></h2>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_p']); ?></div>
        <div id="resultP" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_p_note']); ?></div>
      </div>
      <div class="result-row">
        <div class="result-label"><?php echo htmlspecialchars($s['result_q']); ?></div>
        <div id="resultQ" class="result-value"></div>
        <div class="result-note"><?php echo htmlspecialchars($s['result_q_note']); ?></div>
      </div>
      <div class="result-row">
        <h3 style="margin: 12px 0 6px; font-size: 15px;"><?php echo htmlspecialchars($s['graph_title']); ?></h3>
        <div class="graph-wrap">
          <canvas id="graphCanvas" width="500" height="340" aria-label="<?php echo htmlspecialchars($s['graph_caption']); ?>"></canvas>
        </div>
        <p class="result-note" style="margin-top: 8px;"><?php echo htmlspecialchars($s['graph_caption']); ?></p>
      </div>
    </div>

    <div class="example-box">
      <h3><?php echo htmlspecialchars($s['example_title']); ?></h3>
      <p><?php echo htmlspecialchars($s['example_p1']); ?></p>
      <p><?php echo htmlspecialchars($s['example_p2']); ?></p>
    </div>

    <div class="keywords-box">
      <h3><?php echo htmlspecialchars($s['keywords_title']); ?></h3>
      <dl>
        <dt><?php echo htmlspecialchars($s['kw_demand']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_demand_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_supply']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_supply_dd']); ?></dd>
        <dt><?php echo htmlspecialchars($s['kw_equilibrium']); ?></dt>
        <dd><?php echo htmlspecialchars($s['kw_equilibrium_dd']); ?></dd>
      </dl>
    </div>
  </div>

  <script>
    var noEquilibriumMsg = <?php echo json_encode($s['no_equilibrium']); ?>;
    var defaultPNote = <?php echo json_encode($s['result_p_note']); ?>;

    function drawGraph(canvas, a, b, c, d, pStar, qStar) {
      var w = canvas.width;
      var h = canvas.height;
      var pad = { left: 48, right: 24, top: 24, bottom: 44 };
      var plotW = w - pad.left - pad.right;
      var plotH = h - pad.top - pad.bottom;

      var qMax = Math.max(a, qStar, 10) * 1.15;
      var pMax = Math.max(a / b, pStar, 5) * 1.15;
      var pMin = 0;
      var qMin = 0;
      if (c < 0) qMin = Math.min(0, c * 1.1);
      if (d > 0 && -c / d > 0) pMax = Math.max(pMax, -c / d * 1.1);

      function qToX(q) { return pad.left + ((q - qMin) / (qMax - qMin)) * plotW; }
      function pToY(p) { return pad.top + plotH - (p / pMax) * plotH; }

      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, w, h);

      ctx.strokeStyle = '#94a3b8';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(pad.left, pad.top);
      ctx.lineTo(pad.left, pad.top + plotH);
      ctx.lineTo(pad.left + plotW, pad.top + plotH);
      ctx.stroke();

      ctx.fillStyle = '#64748b';
      ctx.font = '12px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('Q', pad.left + plotW / 2, h - 8);
      ctx.save();
      ctx.translate(14, pad.top + plotH / 2);
      ctx.rotate(-Math.PI / 2);
      ctx.fillText('P', 0, 0);
      ctx.restore();

      if (b <= 0 || d <= 0) return;
      var pDemandZero = a / b;
      var qSupplyZero = c;
      if (c < 0) qMin = Math.min(qMin, c);

      var x0 = qToX(0);
      var xA = qToX(a);
      var y0 = pToY(0);
      var yPD = pToY(pDemandZero);

      ctx.strokeStyle = '#2563eb';
      ctx.lineWidth = 2.5;
      ctx.setLineDash([]);
      ctx.beginPath();
      if (pDemandZero <= pMax && pDemandZero >= 0) {
        ctx.moveTo(x0, pToY(pDemandZero));
        ctx.lineTo(qToX(a), pToY(0));
      } else {
        ctx.moveTo(qToX(0), pToY(Math.min(pDemandZero, pMax)));
        ctx.lineTo(qToX(Math.min(a, qMax)), pToY(0));
      }
      ctx.stroke();

      var pSupplyAtZero = -c / d;
      ctx.strokeStyle = '#dc2626';
      ctx.beginPath();
      if (pSupplyAtZero < 0) {
        ctx.moveTo(qToX(c), pToY(0));
        ctx.lineTo(qToX(Math.min(c + d * pMax, qMax)), pToY(pMax));
      } else {
        ctx.moveTo(qToX(0), pToY(pSupplyAtZero));
        ctx.lineTo(qToX(c + d * pMax), pToY(pMax));
      }
      ctx.stroke();

      if (pStar >= 0 && pStar <= pMax && qStar >= 0 && qStar <= qMax) {
        var ex = qToX(qStar);
        var ey = pToY(pStar);
        ctx.fillStyle = '#1d4ed8';
        ctx.beginPath();
        ctx.arc(ex, ey, 6, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 1;
        ctx.stroke();
      }
    }

    document.getElementById('sdForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var a = parseFloat(document.getElementById('paramA').value) || 0;
      var b = parseFloat(document.getElementById('paramB').value) || 0;
      var c = parseFloat(document.getElementById('paramC').value) || 0;
      var d = parseFloat(document.getElementById('paramD').value) || 0;

      var denom = b + d;
      var pStar = null;
      var qStar = null;
      if (denom > 0 && b > 0) {
        pStar = (a - c) / denom;
        qStar = a - b * pStar;
        if (qStar < 0) {
          pStar = null;
          qStar = null;
        }
      }

      var resultsEl = document.getElementById('results');
      if (pStar === null || qStar === null || pStar < 0) {
        document.getElementById('resultP').textContent = '—';
        document.getElementById('resultQ').textContent = '—';
        document.getElementById('resultP').nextElementSibling.textContent = noEquilibriumMsg;
        resultsEl.style.display = 'block';
        resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }

      document.getElementById('resultP').textContent = pStar.toFixed(2);
      document.getElementById('resultQ').textContent = qStar.toFixed(2);
      document.getElementById('resultP').nextElementSibling.textContent = defaultPNote;

      var canvas = document.getElementById('graphCanvas');
      drawGraph(canvas, a, b, c, d, pStar, qStar);

      resultsEl.style.display = 'block';
      resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>
