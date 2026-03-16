<?php
require_once __DIR__ . '/includes/lang.php';

$strings = [
  'en' => [
    'title' => 'Business Calculators for Japanese College Students',
    'intro_para' => 'These calculators are designed for Japanese university students who major in business. They focus on three important areas: finance, accounting, microeconomics. You can change the numbers and see how the results change.',
    'intro3' => 'The English is simple, but if you want, you can switch to Japanese at the top left.',
    'section_title' => 'Available calculators',
    'section_subtitle' => 'Each calculator below uses yen (¥) in the examples. You can switch the currency to dollars ($) anytime.',
    'card_npv_title' => 'Finance – Net Present Value (NPV) and Internal Rate of Return (IRR)',
    'card_npv_p' => 'Check if an investment project adds value. Enter the cost today and the cash you expect in future years. The calculator shows NPV and IRR in simple language.',
    'card_npv_small' => 'Useful for corporate finance and investment decisions.',
    'card_be_title' => 'Accounting – Break-even Point and Profit (Cost-Volume-Profit)',
    'card_be_p' => 'Find how many units you must sell to break even (no profit, no loss) and see profit or loss at your expected sales. Good for cafés, online shops, and other small business examples.',
    'card_be_small' => 'Useful for financial accounting and managerial accounting.',
    'card_sd_title' => 'Microeconomics – Supply and Demand Equilibrium',
    'card_sd_p' => 'Find the equilibrium price and quantity where quantity demanded equals quantity supplied. Enter simple linear demand and supply equations; the tool solves the intersection and shows a graph.',
    'card_sd_small' => 'Useful for introductory microeconomics and market analysis.',
    'card_payback_title' => 'Finance – Payback and Discounted Payback',
    'card_payback_p' => 'See how many years it takes to recover your initial investment. Payback uses undiscounted cash flows; discounted payback uses the same discount rate as NPV. Pairs with the NPV/IRR calculator.',
    'card_payback_small' => 'Useful for corporate finance and investment decisions.',
    'card_ear_apr_title' => 'Finance – EAR vs APR',
    'card_ear_apr_p' => 'Convert a stated annual rate (APR) to the effective annual rate (EAR) based on how often interest is compounded. Quick and useful for comparing loans or investments.',
    'card_ear_apr_small' => 'Useful for both EN and JA students in finance and banking.',
    'card_loan_title' => 'Finance – Loan Payment and Amortization',
    'card_loan_p' => 'See the monthly payment, total interest, and simple amortization schedule for a loan. Helpful for car loans, student loans, and small business borrowing.',
    'card_loan_small' => 'Useful for personal and business finance decisions.',
    'open_calc' => 'Open calculator',
    'note_heading' => '',
    'note_p1' => 'These calculators are free for college students and teachers. Check out Ron\'s full suite of calculators at ronbelisle.com.',
    'note_email' => '',
    'lang_en' => 'English',
    'lang_ja' => '日本語',
  ],
  'ja' => [
    'title' => '日本の大学生のためのビジネス計算ツール',
    'intro_para' => 'このサイトは、ビジネスを専攻する日本の大学生向けの計算ツールです。ファイナンス、会計、ミクロ経済学の3つの分野に対応しています。数字を変えると結果がどう変わるか確認できます。',
    'intro3' => '英語はわかりやすく書いてあります。左上で日本語に切り替えもできます。',
    'section_title' => '計算ツール一覧',
    'section_subtitle' => '下の計算ツールから選んでください。各ページでは考え方をやさしく説明し、例では円（¥）を使っています。通貨はいつでも$に切り替えできます。',
    'card_npv_title' => 'ファイナンス – 正味現在価値（NPV）と内部収益率（IRR）',
    'card_npv_p' => '投資プロジェクトが価値を生むかどうかを判断します。今日の投資額と、今後受け取るキャッシュフローを入力すると、NPVとIRRが表示されます。',
    'card_npv_small' => '企業財務・投資の意思決定に役立ちます。',
    'card_be_title' => '会計 – 損益分岐点と利益（CVP分析）',
    'card_be_p' => '損益分岐点（利益も損失もない売上）に必要な販売数量と、想定売上での利益または損失を計算します。カフェやネットショップなどの例に使えます。',
    'card_be_small' => '財務会計・管理会計に役立ちます。',
    'card_sd_title' => 'ミクロ経済 – 需要と供給の均衡',
    'card_sd_p' => '需要量と供給量が一致する均衡価格・均衡数量を求めます。一次の需要・供給式を入力すると、交点を計算しグラフで表示します。',
    'card_sd_small' => 'ミクロ経済学の入門と市場分析に役立ちます。',
    'card_payback_title' => 'ファイナンス – 回収期間と割引回収期間',
    'card_payback_p' => '初期投資を何年で回収できるかを計算します。回収期間は割引なし、割引回収期間はNPVと同じ割引率を使います。NPV・IRRの計算ツールと一緒に使えます。',
    'card_payback_small' => '企業財務・投資の意思決定に役立ちます。',
    'card_ear_apr_title' => 'ファイナンス – EARとAPR',
    'card_ear_apr_p' => '表示上の年率（APR）を、複利回数に応じた実効年率（EAR）に換算します。ローンや投資の比較に便利です。',
    'card_ear_apr_small' => 'ファイナンス・金融を学ぶ学生（英語・日本語）向け。',
    'card_loan_title' => 'ファイナンス – ローン返済額と償却表',
    'card_loan_p' => 'ローンの毎月返済額、支払う総利息、簡単な償却表を表示します。自動車ローンや奨学金、ビジネスローンの比較に役立ちます。',
    'card_loan_small' => '個人・ビジネスの資金計画に役立ちます。',
    'open_calc' => '計算ツールを開く',
    'note_heading' => '教員・学生の皆さんへ',
    'note_p1' => 'これらの計算ツールは大学生と教員の方に無料で提供しています。Ron の他の計算ツールも ronbelisle.com でぜひご覧ください。',
    'note_email' => '',
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
  <?php
  if (file_exists(__DIR__ . '/../includes/analytics.php')) {
      include __DIR__ . '/../includes/analytics.php';
  }
  ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo $lang === 'ja' ? '日本の大学生向けビジネス計算ツール（ファイナンス・会計・ミクロ経済）。' : 'Business calculators in simple English for Japanese university students: finance, accounting, and microeconomics.'; ?>">
  <title><?php echo htmlspecialchars($s['title']); ?></title>
  <link rel="stylesheet" href="/css/styles.css">
  <style>
    body { margin: 0; background: #f9fafb; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 40px; }
    .lang-toggle { margin-bottom: 12px; font-size: 14px; }
    .lang-toggle a { color: #1d4ed8; text-decoration: none; margin-right: 8px; }
    .lang-toggle a:hover { text-decoration: underline; }
    .lang-toggle a.active { font-weight: 700; color: #0f172a; pointer-events: none; }
    .page-header { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #38bdf8 100%); color: #fff; border-radius: 18px; padding: 24px 20px; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.25); margin-bottom: 26px; }
    .page-header h1 { margin: 0 0 10px; font-size: 24px; letter-spacing: -0.02em; }
    .page-header p { margin: 4px 0; font-size: 15px; line-height: 1.5; }
    .page-header small { display: block; margin-top: 8px; font-size: 13px; opacity: 0.9; }
    .section-title { font-size: 18px; font-weight: 800; margin: 8px 0 4px; letter-spacing: -0.01em; }
    .section-subtitle { margin: 0 0 16px; font-size: 14px; color: #4b5563; }
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-top: 8px; }
    .card { background: #fff; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.6); padding: 16px 16px 14px; box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08); display: flex; flex-direction: column; gap: 8px; }
    .card h2 { margin: 0; font-size: 16px; letter-spacing: -0.01em; color: #0f172a; }
    .card p { margin: 0; font-size: 14px; color: #4b5563; line-height: 1.5; flex: 1; }
    .card small { display: block; margin-top: 4px; font-size: 12px; color: #6b7280; }
    .card a.button { margin-top: 10px; align-self: flex-start; display: inline-block; padding: 8px 14px; border-radius: 999px; background: #1d4ed8; color: #fff; font-size: 14px; font-weight: 700; text-decoration: none; border: 1px solid rgba(15, 23, 42, 0.15); }
    .card a.button:hover { background: #1e40af; }
    .note-box { margin-top: 22px; padding: 14px 14px 12px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.7); background: #f9fafb; font-size: 13px; color: #374151; }
    .note-box strong { display: block; margin-bottom: 4px; }
    @media (max-width: 640px) { .page-header { padding: 18px 14px; border-radius: 14px; } .page-header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="lang-toggle">
      <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_en']); ?></a>
      <span aria-hidden="true">|</span>
      <a href="?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['lang_ja']); ?></a>
    </div>

    <div class="page-header">
      <h1><?php echo htmlspecialchars($s['title']); ?></h1>
      <p><?php echo htmlspecialchars($s['intro_para']); ?></p>
      <small><?php echo htmlspecialchars($s['intro3']); ?></small>
    </div>

    <div>
      <h2 class="section-title"><?php echo htmlspecialchars($s['section_title']); ?></h2>
      <p class="section-subtitle"><?php echo htmlspecialchars($s['section_subtitle']); ?></p>

      <div class="cards">
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_npv_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_npv_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_npv_small']); ?></small>
          <a class="button" href="/npv-irr/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_be_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_be_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_be_small']); ?></small>
          <a class="button" href="/breakeven-profit/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_sd_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_sd_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_sd_small']); ?></small>
          <a class="button" href="/supply-demand/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_payback_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_payback_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_payback_small']); ?></small>
          <a class="button" href="/payback/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_ear_apr_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_ear_apr_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_ear_apr_small']); ?></small>
          <a class="button" href="/ear-apr/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
        <article class="card">
          <h2><?php echo htmlspecialchars($s['card_loan_title']); ?></h2>
          <p><?php echo htmlspecialchars($s['card_loan_p']); ?></p>
          <small><?php echo htmlspecialchars($s['card_loan_small']); ?></small>
          <a class="button" href="/loan-amortization/<?php echo $langParam; ?>"><?php echo htmlspecialchars($s['open_calc']); ?></a>
        </article>
      </div>
    </div>

    <div class="note-box">
      <p><?php echo htmlspecialchars($s['note_p1']); ?>
        <a href="https://ronbelisle.com" target="_blank" rel="noopener noreferrer">ronbelisle.com</a>.
      </p>
    </div>
  </div>
</body>
</html>
