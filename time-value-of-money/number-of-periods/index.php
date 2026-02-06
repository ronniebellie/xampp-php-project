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
    <link rel="stylesheet" href="/css/styles.css" />
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

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</div>

</body>
</html>
