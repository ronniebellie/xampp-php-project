<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php
  $analytics = __DIR__ . '/../includes/analytics.php';
  if (file_exists($analytics)) {
      include $analytics;
  }
  ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Business calculators for university students: NPV & IRR, break-even and profit, supply and demand equilibrium, payback and discounted payback, EAR vs APR, and loan payment/amortization.">
  <title>Business Calculators for University Students</title>
  <link rel="stylesheet" href="/css/styles.css">
  <style>
    body { margin: 0; background: #f9fafb; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 40px; }
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
    .note-box p { margin: 0; }
    @media (max-width: 640px) { .page-header { padding: 18px 14px; border-radius: 14px; } .page-header h1 { font-size: 20px; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="page-header">
      <h1>Business Calculators for University Students</h1>
      <p>These calculators are designed for college students majoring in business, finance, accounting, or economics.</p>
      <p>They focus on six important areas: capital budgeting, cost–volume–profit analysis, supply and demand, payback, interest rates, and loans.</p>
      <small>The examples use dollars ($), but you can adjust the numbers to match any currency.</small>
    </div>

    <div>
      <h2 class="section-title">Available calculators</h2>
      <p class="section-subtitle">Start with any calculator below. Each page explains the idea in clear business English and shows how changing the inputs affects the results.</p>

      <div class="cards">
        <article class="card">
          <h2>Finance – Net Present Value (NPV) and Internal Rate of Return (IRR)</h2>
          <p>Evaluate investment projects using NPV and IRR. Enter the initial outlay and the cash flows you expect in future years. The calculator reports NPV and IRR and explains how to interpret them.</p>
          <small>Useful for capital budgeting and investment decision-making.</small>
          <a class="button" href="/npv-irr/">Open calculator</a>
        </article>

        <article class="card">
          <h2>Accounting – Break-even Point and Profit (Cost–Volume–Profit)</h2>
          <p>Find the unit sales you need to break even (no profit, no loss) and see profit or loss at your expected sales level. Good for analyzing cafés, online stores, and other simple business models.</p>
          <small>Useful for managerial and financial accounting courses.</small>
          <a class="button" href="/breakeven-profit/">Open calculator</a>
        </article>

        <article class="card">
          <h2>Microeconomics – Supply and Demand Equilibrium</h2>
          <p>Compute the equilibrium price and quantity where quantity demanded equals quantity supplied. Enter linear demand and supply equations; the tool solves for the intersection and shows a graph.</p>
          <small>Useful for introductory microeconomics and market analysis.</small>
          <a class="button" href="/supply-demand/">Open calculator</a>
        </article>

        <article class="card">
          <h2>Finance – Payback and Discounted Payback</h2>
          <p>See how many years it takes to recover your initial investment, with and without discounting. Payback uses raw cash flows; discounted payback uses the same discount rate you might use for NPV.</p>
          <small>Useful as a bridge between simple payback and NPV/IRR.</small>
          <a class="button" href="/payback/">Open calculator</a>
        </article>

        <article class="card">
          <h2>Finance – EAR vs APR</h2>
          <p>Convert a quoted annual percentage rate (APR) to the effective annual rate (EAR) based on compounding frequency. Quickly compare loans or deposits with different compounding conventions.</p>
          <small>Useful for banking, corporate finance, and personal finance topics.</small>
          <a class="button" href="/ear-apr/">Open calculator</a>
        </article>

        <article class="card">
          <h2>Finance – Loan Payment and Amortization</h2>
          <p>Enter a loan amount, annual interest rate, and term in years to see the monthly payment, total interest paid, and a simple amortization table by year. Works well for car loans, student loans, and small business loans.</p>
          <small>Useful for time value of money and lending topics.</small>
          <a class="button" href="/loan-amortization/">Open calculator</a>
        </article>
      </div>
    </div>

    <div class="note-box">
      <p>These calculators are free for college students and instructors. For retirement and advanced planning tools, visit
        <a href="https://ronbelisle.com" target="_blank" rel="noopener noreferrer">ronbelisle.com</a>.
      </p>
    </div>
  </div>
</body>
</html>

