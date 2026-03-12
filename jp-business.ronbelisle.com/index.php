<?php
// Simple standalone page for Japanese business students calculators (no login / premium required)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php
  // Reuse main analytics if available
  if (file_exists(__DIR__ . '/../includes/analytics.php')) {
      include __DIR__ . '/../includes/analytics.php';
  }
  ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Business calculators in simple English for Japanese university students: finance, accounting, and microeconomics.">
  <title>Business Calculators for Japanese College Students</title>
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
    .page-header {
      background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #38bdf8 100%);
      color: #fff;
      border-radius: 18px;
      padding: 24px 20px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.25);
      margin-bottom: 26px;
    }
    .page-header h1 {
      margin: 0 0 10px;
      font-size: 24px;
      letter-spacing: -0.02em;
    }
    .page-header p {
      margin: 4px 0;
      font-size: 15px;
      line-height: 1.5;
    }
    .page-header small {
      display: block;
      margin-top: 8px;
      font-size: 13px;
      opacity: 0.9;
    }
    .section-title {
      font-size: 18px;
      font-weight: 800;
      margin: 8px 0 4px;
      letter-spacing: -0.01em;
    }
    .section-subtitle {
      margin: 0 0 16px;
      font-size: 14px;
      color: #4b5563;
    }
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 16px;
      margin-top: 8px;
    }
    .card {
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid rgba(148, 163, 184, 0.6);
      padding: 16px 16px 14px;
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .card h2 {
      margin: 0;
      font-size: 16px;
      letter-spacing: -0.01em;
      color: #0f172a;
    }
    .card p {
      margin: 0;
      font-size: 14px;
      color: #4b5563;
      line-height: 1.5;
      flex: 1;
    }
    .card small {
      display: block;
      margin-top: 4px;
      font-size: 12px;
      color: #6b7280;
    }
    .card a.button {
      margin-top: 10px;
      align-self: flex-start;
      display: inline-block;
      padding: 8px 14px;
      border-radius: 999px;
      background: #1d4ed8;
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      border: 1px solid rgba(15, 23, 42, 0.15);
    }
    .card a.button:hover {
      background: #1e40af;
    }
    .note-box {
      margin-top: 22px;
      padding: 14px 14px 12px;
      border-radius: 12px;
      border: 1px solid rgba(148, 163, 184, 0.7);
      background: #f9fafb;
      font-size: 13px;
      color: #374151;
    }
    .note-box strong {
      display: block;
      margin-bottom: 4px;
    }
    @media (max-width: 640px) {
      .page-header {
        padding: 18px 14px;
        border-radius: 14px;
      }
      .page-header h1 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="page-header">
      <h1>Business Calculators for Japanese College Students</h1>
      <p>These calculators are designed for Japanese university students who study business in English.</p>
      <p>They focus on three important areas: <strong>finance</strong>, <strong>accounting</strong>, and <strong>microeconomics</strong>.</p>
      <small>English is simple. You can change the numbers and see how the results change.</small>
    </div>

    <div>
      <h2 class="section-title">Available calculators</h2>
      <p class="section-subtitle">Start with any calculator below. Each page explains the idea in clear English and uses yen (¥) in the examples.</p>

      <div class="cards">
        <article class="card">
          <h2>Finance – Net Present Value (NPV) and Internal Rate of Return (IRR)</h2>
          <p>Check if an investment project adds value. Enter the cost today and the cash you expect in future years. The calculator shows NPV and IRR in simple language.</p>
          <small>Useful for corporate finance and investment decisions.</small>
          <a class="button" href="/jp-business.ronbelisle.com/npv-irr/">Open calculator</a>
        </article>
        <article class="card">
          <h2>Accounting – Break-even Point and Profit (Cost-Volume-Profit)</h2>
          <p>Find how many units you must sell to break even (no profit, no loss) and see profit or loss at your expected sales. Good for cafés, online shops, and other small business examples.</p>
          <small>Useful for financial accounting and managerial accounting.</small>
          <a class="button" href="/jp-business.ronbelisle.com/breakeven-profit/">Open calculator</a>
        </article>
      </div>
    </div>

    <div class="note-box">
      <strong>For professors and students</strong>
      <p>These tools are free for students and teachers at Mukogawa Women’s University. If you are a professor at another university and are interested, please contact Ron Belisle.</p>
      <p>Email: <a href="mailto:ronbelisle@gmail.com">ronbelisle@gmail.com</a></p>
    </div>
  </div>
</body>
</html>

