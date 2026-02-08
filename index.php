<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ron's Homepage</title>
  <style>
    :root{
      --max: 980px;
      --bg: #f6f7fb;
      --paper: #ffffff;
      --text: #0f172a;
      --muted: rgba(15,23,42,.68);
      --border: rgba(15,23,42,.14);
      --accent: #1d4ed8;
      --shadow: 0 14px 34px rgba(15,23,42,.14);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
      color:var(--text);
      background:
        linear-gradient(180deg,#f9fafb 0%,var(--bg) 55%,#f3f4f6 100%),
        repeating-linear-gradient(0deg,rgba(15,23,42,.03) 0 1px,transparent 1px 34px),
        repeating-linear-gradient(90deg,rgba(15,23,42,.02) 0 1px,transparent 1px 34px);
    }
    .wrap{max-width:var(--max);margin:0 auto;padding:24px 18px 44px}
    .topbar{
      display:flex;align-items:center;justify-content:space-between;gap:12px;
      padding:22px 24px;margin-bottom:14px;border:1px solid var(--border);
      background:rgba(255,255,255,.92);border-radius:var(--radius);box-shadow:var(--shadow);
    }
    .brand{display:flex;align-items:center;gap:12px;min-width:0}
    .mark{
      width:48px;height:48px;border-radius:16px;border:1px solid var(--border);
      background:linear-gradient(135deg,rgba(29,78,216,.12),rgba(29,78,216,.05));
      display:grid;place-items:center;font-weight:850;letter-spacing:-.02em;color:var(--accent);flex:0 0 auto;
    }
    .brand-title{
      font-size:20px;font-weight:850;letter-spacing:-.01em;
      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    }
    .section{
      margin-top:18px;display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;
      padding:6px 4px 0;
    }
    h2{margin:0;font-size:18px;letter-spacing:-.01em}
    .hint{margin:0;font-size:13px;color:var(--muted)}
    .grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:14px;margin-top:14px}
    @media (min-width:720px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .card{
      border:1px solid rgba(15,23,42,.12);
      background:var(--paper);
      box-shadow:0 10px 22px rgba(15,23,42,.08);
      border-radius:var(--radius);
      padding:16px;
      display:flex;flex-direction:column;gap:10px;min-height:160px;position:relative;
      transition:transform 120ms ease, background 120ms ease;
    }
    .card:hover{transform:translateY(-2px);background:#fff}
    .card h3{margin:0;font-size:16px;letter-spacing:-.01em}
    .card p{margin:0;color:var(--muted);flex:1}
    .card::after{
      content:"";position:absolute;top:14px;right:14px;width:78px;height:26px;opacity:.22;
      background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='156' height='52' viewBox='0 0 156 52'><path d='M4 40 C20 36, 26 46, 38 38 S62 20, 76 26 S98 42, 112 30 S132 10, 152 14' fill='none' stroke='%231d4ed8' stroke-width='4' stroke-linecap='round'/></svg>");
      background-size:cover;background-repeat:no-repeat;pointer-events:none;
    }
    .btn{
      display:inline-block;text-decoration:none;font-weight:750;font-size:14px;
      padding:10px 14px;border-radius:14px;border:1px solid rgba(29,78,216,.28);
      background:rgba(29,78,216,.10);color:var(--accent);
    }
    .btn:hover{background:rgba(29,78,216,.14)}

    hr.footer-sep {
      border: 0;
      border-top: 1px solid rgba(15,23,42,.12);
      margin: 22px 0 14px;
    }

    .site-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      color: var(--muted);
      font-size: 13px;
      padding-bottom: 10px;
    }

    .footer-left { margin: 0; }

    .footer-right {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
      text-align: right;
    }

    .donate-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 8px 14px;
      border-radius: 999px;
      border: 1px solid rgba(15,23,42,.14);
      background: rgba(15,23,42,.03);
      color: var(--text);
      text-decoration: none;
      font-weight: 700;
      line-height: 1;
      white-space: nowrap;
    }

    .donate-button:hover {
      background: rgba(15,23,42,.06);
    }

    @media (max-width: 720px) {
      .footer-right {
        width: 100%;
        justify-content: flex-start;
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">

    <div class="topbar" role="banner">
      <div class="brand">
        <div class="mark" aria-hidden="true">RB</div>
        <div class="brand-title">Calculators and tools for sound financial planning</div>
      </div>
    </div>

    <div class="section" id="apps">
      <h2>Apps</h2>
      <p class="hint">Stay tuned for more apps to be added.</p>
    </div>

    <main class="grid" aria-label="App links">
      <section class="card">
        <h3>RMD Impact</h3>
        <p>Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time.</p>
        <a class="btn" href="rmd-impact/">Open</a>
      </section>

      <section class="card">
        <h3>Future Value Calculator</h3>
        <p>Calculate present value, future value, annuities, and required payments to reach your financial goals.</p>
        <a class="btn" href="future-value-app/">Open</a>
      </section>

      <section class="card">
        <h3>Social Security Claiming Analyzer</h3>
        <p>Compare claiming ages and see how lifetime Social Security benefits change over time.</p>
        <a class="btn" href="social-security-claiming-analyzer/">Open</a>
      </section>

      <section class="card">
        <h3>Social Security + Spending Gap Calculator</h3>
        <p>See how Social Security reduces the portfolio you need by identifying your real retirement spending gap.</p>
        <a class="btn" href="ss-gap/">Open</a>
      </section>

      <section class="card">
        <h3>Roth Conversion Calculator</h3>
        <p>Analyze the benefits of converting traditional IRA funds to Roth, considering current vs future tax brackets, RMDs, and Medicare IRMAA thresholds.</p>
        <a class="btn" href="roth-conv/">Open</a>
      </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

  </div>

  <script>
    document.getElementById('y').textContent = new Date().getFullYear();
  </script>
</body>
</html>