<?php
// Number of Periods (Single Amount)

session_start();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$errors = [];

// Start blank on page load.
$pv = '';
$fv = '';
$rate = '';
$compound = '12';

$resultYears = null;

// Show the most recent result once (then clear it), so refresh clears the page.
if (isset($_SESSION['n_periods_years_result'])) {
    $resultYears = $_SESSION['n_periods_years_result'];
    unset($_SESSION['n_periods_years_result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pv = $_POST['pv'] ?? '';
    $fv = $_POST['fv'] ?? '';
    $rate = $_POST['rate'] ?? '';
    $compound = $_POST['compound'] ?? '12';

    $pvNum = filter_var($pv, FILTER_VALIDATE_FLOAT);
    $fvNum = filter_var($fv, FILTER_VALIDATE_FLOAT);
    $rateNum = filter_var($rate, FILTER_VALIDATE_FLOAT);
    $compoundNum = filter_var($compound, FILTER_VALIDATE_INT);

    if ($pvNum === false || $pvNum <= 0) $errors[] = 'Present Value must be a number greater than 0.';
    if ($fvNum === false || $fvNum <= 0) $errors[] = 'Future Value must be a number greater than 0.';
    if ($rateNum === false) $errors[] = 'Annual Rate must be a number.';
    if ($compoundNum === false || $compoundNum <= 0) $errors[] = 'Compounds per year must be a whole number (1 or more).';

    if (!$errors) {
        $r = $rateNum / 100.0;
        $m = (float) $compoundNum;

        if ($fvNum <= 0 || $pvNum <= 0) {
            $errors[] = 'Present Value and Future Value must be greater than 0.';
        } else {
            // For a positive rate, FV must be greater than PV to produce a positive time.
            if ($r > 0 && $fvNum <= $pvNum) {
                $errors[] = 'For a positive rate, Future Value must be greater than Present Value.';
            }

            if ($errors) {
                // Stop here; display errors without computing.
            } else {
                // FV = PV * (1 + r/m)^(m*t)  =>  t = ln(FV/PV) / (m * ln(1 + r/m))
                if (abs($r) < 1e-12) {
                    if (abs($fvNum - $pvNum) < 1e-9) {
                        $years = 0.0;
                    } else {
                        $errors[] = 'With a 0% rate, Future Value must equal Present Value.';
                        $years = null;
                    }
                } else {
                    $base = 1 + ($r / $m);
                    if ($base <= 0) {
                        $errors[] = 'Rate and compounding result in an invalid growth base.';
                        $years = null;
                    } else {
                        $ratio = $fvNum / $pvNum;
                        if ($ratio <= 0) {
                            $errors[] = 'Future Value / Present Value must be greater than 0.';
                            $years = null;
                        } else {
                            $years = log($ratio) / ($m * log($base));
                        }
                    }
                }
            }

            if (!$errors && $years !== null) {
                $_SESSION['n_periods_years_result'] = $years;
                header('Location: /time-value-of-money/number-of-periods/');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Number of Periods</title>

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

        .units {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a class="btn" href="/time-value-of-money/">← Back to Calculators</a>
    </div>

    <h1>Number of Periods</h1>
    <p class="sub">Solve for the time needed for a present value to grow to a future value at a given rate and compounding frequency.</p>

    <div class="card">

        <?php if ($errors): ?>
            <div class="errors"><?php echo h(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="row">
                <div>
                    <label for="pv">Present Value</label>
                    <input id="pv" name="pv" inputmode="decimal" value="<?php echo h($pv); ?>" placeholder="e.g., 10000">
                </div>

                <div>
                    <label for="fv">Future Value</label>
                    <input id="fv" name="fv" inputmode="decimal" value="<?php echo h($fv); ?>" placeholder="e.g., 20000">
                </div>

                <div>
                    <label for="rate">Annual Interest Rate (%)</label>
                    <input id="rate" name="rate" inputmode="decimal" value="<?php echo h($rate); ?>" placeholder="e.g., 6.5">
                </div>

                <div>
                    <label for="compound">Compounds per Year</label>
                    <select id="compound" name="compound">
                        <?php
                        $options = [
                            1   => 'Annually',
                            2   => 'Semiannually',
                            4   => 'Quarterly',
                            12  => 'Monthly',
                            365 => 'Daily'
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

        <?php if ($resultYears !== null): ?>
            <div class="result">
                <div>Time Required</div>
                <div class="big"><?php echo h(number_format((float)$resultYears, 4)); ?> years</div>
                <div class="units">(Approx.)</div>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
