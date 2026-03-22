<?php
/**
 * Interest rate solver (lump sum): FV = PV * (1 + r/m)^(m*t)  =>  solve for annual r.
 */
session_start();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$errors = [];

$pv = $_POST['pv'] ?? '';
$fv = $_POST['fv'] ?? '';
$years = $_POST['years'] ?? '';
$compound = $_POST['compound'] ?? '12';

$resultRatePct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pvNum = filter_var($pv, FILTER_VALIDATE_FLOAT);
    $fvNum = filter_var($fv, FILTER_VALIDATE_FLOAT);
    $yearsNum = filter_var($years, FILTER_VALIDATE_FLOAT);
    $compoundNum = filter_var($compound, FILTER_VALIDATE_INT);

    if ($pvNum === false || $pvNum <= 0) {
        $errors[] = 'Present Value must be a number greater than 0.';
    }
    if ($fvNum === false || $fvNum <= 0) {
        $errors[] = 'Future Value must be a number greater than 0.';
    }
    if ($yearsNum === false || $yearsNum <= 0) {
        $errors[] = 'Years must be a number greater than 0.';
    }
    if ($compoundNum === false || $compoundNum <= 0) {
        $errors[] = 'Compounds per year must be a whole number (1 or more).';
    }

    if (!$errors) {
        $m = (float) $compoundNum;
        $t = $yearsNum;
        $n = $m * $t;
        if ($n <= 0) {
            $errors[] = 'Total number of periods must be greater than 0.';
        } elseif (abs($fvNum - $pvNum) < 1e-12) {
            $resultRatePct = 0.0;
        } else {
            $ratio = $fvNum / $pvNum;
            if ($ratio <= 0) {
                $errors[] = 'Future Value / Present Value must be greater than 0.';
            } else {
                $resultRatePct = $m * (pow($ratio, 1.0 / $n) - 1.0) * 100.0;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../includes/analytics.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Solve for interest rate in time value of money. Implied annual rate from present value, future value, years, and compounding.">
    <title>Interest Rate Solver</title>
    <?php
    $og_title = $ld_name = 'Interest Rate Solver';
    $og_description = $ld_description = 'Solve for interest rate in time value of money. Implied annual rate from present value, future value, years, and compounding.';
    include __DIR__ . '/../../includes/og-twitter-meta.php';
    include __DIR__ . '/../../includes/json-ld-softwareapp.php';
    ?>
    <link rel="stylesheet" href="/css/styles.css" />
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a class="btn" href="/time-value-of-money/">← Back to Calculators</a>
    </div>

    <h1>Interest Rate Solver</h1>
    <p class="sub">Find the <strong>nominal annual interest rate</strong> (compounded <em>m</em> times per year) that grows a present value to a future value over a whole number of years, assuming a single lump sum with compound interest: <code>FV = PV × (1 + r/m)<sup>m×t</sup></code>.</p>

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
                    <label for="years">Years (t)</label>
                    <input id="years" name="years" inputmode="decimal" value="<?php echo h($years); ?>" placeholder="e.g., 10">
                </div>
                <div>
                    <label for="compound">Compounds per Year (m)</label>
                    <select id="compound" name="compound">
                        <?php
                        $options = [
                            1   => 'Annually',
                            2   => 'Semiannually',
                            4   => 'Quarterly',
                            12  => 'Monthly',
                            365 => 'Daily',
                        ];
                        foreach ($options as $val => $label) {
                            $sel = ((string) $val === (string) $compound) ? 'selected' : '';
                            echo '<option value="' . h((string) $val) . '" ' . $sel . '>' . h($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="margin-top:14px;">
                <button class="btn" type="submit">Calculate</button>
            </div>
        </form>

        <?php if ($resultRatePct !== null && !$errors): ?>
            <div class="result">
                <div>Nominal annual rate (r)</div>
                <div class="big"><?php echo h(number_format($resultRatePct, 4)); ?>%</div>
                <div class="units">Compounded <?php echo h((string) $compound); ?> times per year over <?php echo h((string) $years); ?> year(s)</div>
            </div>
        <?php endif; ?>

    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</div>

</body>
</html>
