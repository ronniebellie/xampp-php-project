<?php
// Time Value of Money Calculators – Index
// Edit the $calculators array to add/remove calculators or adjust labels/paths.

$siteTitle = 'Time Value of Money Calculators';

$calculators = [
    [
        'title' => 'Future Value (Single Amount)',
        'path'  => 'future-value/',
        'desc'  => 'Find the future value of a single deposit given rate and time.'
    ],
    [
        'title' => 'Present Value (Single Amount)',
        'path'  => 'present-value/',
        'desc'  => 'Find the value today of a future amount given rate and time.'
    ],
    [
        'title' => 'Future Value of an Annuity',
        'path'  => 'future-value-annuity/',
        'desc'  => 'Find the future value of a series of equal payments.'
    ],
    [
        'title' => 'Present Value of an Annuity',
        'path'  => 'present-value-annuity/',
        'desc'  => 'Find the present value of a series of equal payments.'
    ],
    [
        'title' => 'Loan Payment',
        'path'  => 'loan-payment/',
        'desc'  => 'Compute the periodic payment for a loan (amortized loan payment).' 
    ],
    [
        'title' => 'Number of Periods',
        'path'  => 'number-of-periods/',
        'desc'  => 'Solve for how long it takes to reach a value given rate and payments.'
    ],
    [
        'title' => 'Interest Rate',
        'path'  => 'interest-rate/',
        'desc'  => 'Solve for the rate needed to reach a target future or present value.'
    ],
    [
        'title' => 'Growing Annuity (PV or FV)',
        'path'  => 'growing-annuity/',
        'desc'  => 'Value payments that grow by a fixed rate each period.'
    ],
];

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($siteTitle); ?></title>

    <style>
        :root {
            --max-width: 980px;
            --bg: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --border: #e5e7eb;
            --card-bg: #f9fafb;
            --link: #1d4ed8;
            --link-hover: #1e40af;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.45;
        }

        .wrap {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 28px 18px 40px;
        }

        header {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: -0.02em;
        }

        .sub {
            margin: 0;
            color: var(--muted);
            max-width: 70ch;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        @media (min-width: 720px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .card {
            border: 1px solid var(--border);
            background: var(--card-bg);
            border-radius: 12px;
            padding: 16px;
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .card p {
            margin: 0 0 12px;
            color: var(--muted);
        }

        a.button {
            display: inline-block;
            text-decoration: none;
            color: #ffffff;
            background: var(--link);
            padding: 9px 12px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 14px;
        }

        a.button:hover {
            background: var(--link-hover);
        }

        footer.site-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
            color: var(--muted);
            font-size: 13px;
        }

        .donate-text {
            max-width: 52ch;
        }

        a.donate-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f3f4f6;
            color: var(--text);
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }

        a.donate-btn:hover {
            background: #e5e7eb;
        }

        .donate-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #93c5fd;
            display: inline-block;
        }

    </style>
</head>
<body>

<div class="wrap">

    <header>
        <h1><?php echo h($siteTitle); ?></h1>
        <p class="sub">Select the calculator that matches your problem. Each calculator focuses on one common time-value-of-money scenario.</p>
    </header>

    <main class="grid" aria-label="Calculator list">
        <?php foreach ($calculators as $c): ?>
            <section class="card">
                <h2><?php echo h($c['title']); ?></h2>
                <p><?php echo h($c['desc']); ?></p>
                <a class="button" href="<?php echo h($c['path']); ?>">Open calculator</a>
            </section>
        <?php endforeach; ?>
    </main>

    <footer class="site-footer" aria-label="Support">
        <div class="donate-text">If these tools are useful, please consider supporting future development.</div>
        <a class="donate-btn" href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" rel="noopener noreferrer">
            <span class="donate-dot" aria-hidden="true"></span>
            Donate
        </a>
    </footer>


</div>

</body>
</html>