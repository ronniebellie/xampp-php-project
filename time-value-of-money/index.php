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

    <link rel="stylesheet" href="/css/styles.css">

</head>
<body class="tvm-page">

<div class="wrap">

    <div class="top-nav" style="margin: 0 0 12px 0;">
        <a class="home-btn" href="/" style="display:inline-flex; align-items:center; padding:8px 14px; border-radius:999px; border:1px solid #e5e7eb; background:#ffffff; color:#111827; text-decoration:none; font-weight:600; line-height:1; white-space:nowrap;">
            Return to home page
        </a>
    </div>

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

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</div>

</body>
</html>