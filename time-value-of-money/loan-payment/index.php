<?php
// Loan Payment

session_start();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$errors = [];

// Start blank on page load.
$principal = '';
$rate = '';
$years = '';
$compound = '12';

$result = null;

// Show the most recent result once (then clear it), so refresh clears the page.
if (isset($_SESSION['loan_payment_result'])) {
    $result = $_SESSION['loan_payment_result'];
    unset($_SESSION['loan_payment_result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal = $_POST['principal'] ?? '';
    $rate = $_POST['rate'] ?? '';
    $years = $_POST['years'] ?? '';
    $compound = $_POST['compound'] ?? '12';

    $principalNum = filter_var($principal, FILTER_VALIDATE_FLOAT);
    $rateNum = filter_var($rate, FILTER_VALIDATE_FLOAT);
    $yearsNum = filter_var($years, FILTER_VALIDATE_FLOAT);
    $compoundNum = filter_var($compound, FILTER_VALIDATE_INT);

    if ($principalNum === false || $principalNum <= 0) $errors[] = 'Loan Amount must be a number greater than 0.';
    if ($rateNum === false || $rateNum < 0) $errors[] = 'Annual Rate must be a number (0 or more).';
    if ($yearsNum === false || $yearsNum <= 0) $errors[] = 'Years must be a number greater than 0.';
    if ($compoundNum === false || $compoundNum <= 0) $errors[] = 'Payments per year must be a whole number (1 or more).';

    if (!$errors) {
        $pv = $principalNum;
        $r = $rateNum / 100.0;
        $m = (float) $compoundNum;
        $t = $yearsNum;

        $n = $m * $t;
        $i = ($m > 0) ? ($r / $m) : 0.0;

        if ($n <= 0) {
            $errors[] = 'Total number of payments must be greater than 0.';
        } else {
            // Payment for an amortizing loan
            if (abs($i) < 1e-12) {
                $pmt = $pv / $n;
            } else {
                $pmt = $pv * ($i) / (1 - pow(1 + $i, -$n));
            }

            $_SESSION['loan_payment_result'] = $pmt;
            header('Location: /time-value-of-money/loan-payment/');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loan Payment</title>

    <style>
        :root {
            --max-width: 980px;
            --bg: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --border: #e5e7eb;
            --card: #f9fafb;
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

        h1 {
            margin: 0 0 6px;
            font-size: 28px;
            letter-spacing: -0.02em;
        }

        .sub {
            margin: 0 0 18px;
            color: var(--muted);
            max-width: 70ch;
        }

        .card {
            border: 1px solid var(--border);
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 720px) {
            .row { grid-template-columns: 1fr 1fr; }
        }

        label {
            display: block;
            font-weight: 600;
            margin: 0 0 6px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            background: #ffffff;
        }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 10px;
            background: var(--link);
            color: #ffffff;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            line-height: 1;
        }

        .btn:hover {
            background: var(--link-hover);
            text-decoration: none;
        }

        .errors {
            margin: 0 0 12px;
            padding: 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            border-radius: 12px;
            color: #7f1d1d;
        }

        .result {
            margin-top: 14px;
            padding: 12px;
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            border-radius: 12px;
        }

        .result .big {
            font-size: 22px;
            font-weight: 800;
            margin-top: 6px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
    </style>
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a class="btn" href="/time-value-of-money/">← Back to Calculators</a>
    </div>

    <h1>Loan Payment</h1>
    <p class="sub">Compute the periodic payment for an amortizing loan.</p>

    <div class="card">

        <?php if ($errors): ?>
            <div class="errors"><?php echo h(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="row">
                <div>
                    <label for="principal">Loan Amount</label>
                    <input id="principal" name="principal" inputmode="decimal" value="<?php echo h($principal); ?>" placeholder="e.g., 250000">
                </div>

                <div>
                    <label for="rate">Annual Interest Rate (%)</label>
                    <input id="rate" name="rate" inputmode="decimal" value="<?php echo h($rate); ?>" placeholder="e.g., 6.5">
                </div>

                <div>
                    <label for="years">Loan Term (Years)</label>
                    <input id="years" name="years" inputmode="decimal" value="<?php echo h($years); ?>" placeholder="e.g., 30">
                </div>

                <div>
                    <label for="compound">Payments per Year</label>
                    <select id="compound" name="compound">
                        <?php
                        $options = [
                            12 => 'Monthly',
                            26 => 'Biweekly (26)',
                            52 => 'Weekly (52)',
                            1  => 'Annually'
                        ];
                        foreach ($options as $val => $label) {
                            $sel = ((string)$val === (string)$compound) ? 'selected' : '';
                            echo '<option value="' . h((string)$val) . '" ' . $sel . '>' . h($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="margin-top:14px;">
                <button class="btn" type="submit">Calculate</button>
            </div>

        </form>

        <?php if ($result !== null): ?>
            <div class="result">
                <div>Periodic Payment</div>
                <div class="big"><?php echo h('$' . number_format((float)$result, 2)); ?></div>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
