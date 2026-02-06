<?php
// Growing Annuity (PV or FV)


session_start();

// Clear form/result
if (isset($_GET['reset']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['growing_annuity_result'], $_SESSION['growing_annuity_inputs']);
    header('Location: /time-value-of-money/growing-annuity/');
    exit;
}

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$errors = [];

// Start blank on page load.
$pmt1 = '';
$rate = '';
$growth = '';
$years = '';
$compound = '12';
$mode = 'fv';

$result = null;

// Preload last inputs (so user can switch PV/FV without retyping)
if (isset($_SESSION['growing_annuity_inputs']) && is_array($_SESSION['growing_annuity_inputs'])) {
    $in = $_SESSION['growing_annuity_inputs'];
    $pmt1 = $in['pmt1'] ?? $pmt1;
    $rate = $in['rate'] ?? $rate;
    $growth = $in['growth'] ?? $growth;
    $years = $in['years'] ?? $years;
    $compound = $in['compound'] ?? $compound;
    $mode = $in['mode'] ?? $mode;
}

// Show the most recent result once (then clear it), so refresh clears the page.
if (isset($_SESSION['growing_annuity_result'])) {
    $result = $_SESSION['growing_annuity_result'];
    unset($_SESSION['growing_annuity_result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pmt1 = $_POST['pmt1'] ?? '';
    $rate = $_POST['rate'] ?? '';
    $growth = $_POST['growth'] ?? '';
    $years = $_POST['years'] ?? '';
    $compound = $_POST['compound'] ?? '12';
    $mode = $_POST['mode'] ?? 'fv';

    $pmt1Num = filter_var($pmt1, FILTER_VALIDATE_FLOAT);
    $rateNum = filter_var($rate, FILTER_VALIDATE_FLOAT);
    $growthNum = filter_var($growth, FILTER_VALIDATE_FLOAT);
    $yearsNum = filter_var($years, FILTER_VALIDATE_FLOAT);
    $compoundNum = filter_var($compound, FILTER_VALIDATE_INT);

    if ($pmt1Num === false || $pmt1Num < 0) $errors[] = 'First Payment must be a number (0 or more).';
    if ($rateNum === false) $errors[] = 'Annual Interest Rate must be a number.';
    if ($growthNum === false) $errors[] = 'Annual Growth Rate must be a number.';
    if ($yearsNum === false || $yearsNum <= 0) $errors[] = 'Years must be a number greater than 0.';
    if ($compoundNum === false || $compoundNum <= 0) $errors[] = 'Periods per year must be a whole number (1 or more).';
    if ($mode !== 'pv' && $mode !== 'fv') $errors[] = 'Mode must be PV or FV.';

    if (!$errors) {
        $m = (float) $compoundNum;
        $t = $yearsNum;
        $n = $m * $t; // number of periods

        if ($n <= 0) {
            $errors[] = 'Total number of periods must be greater than 0.';
        } else {
            // Convert annual rates to per-period rates.
            $i = ($rateNum / 100.0) / $m;
            $g = ($growthNum / 100.0) / $m;

            // Ordinary growing annuity: payments occur at end of each period.
            // Payment in period 1 is PMT1.

            $eps = 1e-12;
            $value = null;

            if ($mode === 'pv') {
                if (abs($i - $g) < $eps) {
                    // PV when i == g: PV = PMT1 * n / (1 + i)
                    $value = $pmt1Num * $n / (1 + $i);
                } else {
                    // PV = PMT1 * [1 - ((1+g)/(1+i))^n] / (i - g)
                    $ratio = (1 + $g) / (1 + $i);
                    $value = $pmt1Num * (1 - pow($ratio, $n)) / ($i - $g);
                }

                $_SESSION['growing_annuity_inputs'] = [
                    'pmt1' => $pmt1,
                    'rate' => $rate,
                    'growth' => $growth,
                    'years' => $years,
                    'compound' => $compound,
                    'mode' => $mode,
                ];
                $_SESSION['growing_annuity_result'] = [
                    'label' => 'Present Value of Growing Annuity',
                    'value' => $value
                ];
                header('Location: /time-value-of-money/growing-annuity/');
                exit;
            }

            if ($mode === 'fv') {
                if (abs($i - $g) < $eps) {
                    // FV when i == g: FV = PMT1 * n * (1 + i)^(n - 1)
                    $value = $pmt1Num * $n * pow(1 + $i, $n - 1);
                } else {
                    // FV = PMT1 * [ (1+i)^n - (1+g)^n ] / (i - g)
                    $value = $pmt1Num * (pow(1 + $i, $n) - pow(1 + $g, $n)) / ($i - $g);
                }

                $_SESSION['growing_annuity_inputs'] = [
                    'pmt1' => $pmt1,
                    'rate' => $rate,
                    'growth' => $growth,
                    'years' => $years,
                    'compound' => $compound,
                    'mode' => $mode,
                ];
                $_SESSION['growing_annuity_result'] = [
                    'label' => 'Future Value of Growing Annuity',
                    'value' => $value
                ];
                header('Location: /time-value-of-money/growing-annuity/');
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
    <title>Growing Annuity (PV or FV)</title>
    <link rel="stylesheet" href="/css/styles.css" />
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a class="btn" href="/time-value-of-money/">← Back to Calculators</a>
        <a class="btn" href="/time-value-of-money/growing-annuity/?reset=1">Clear</a>
    </div>

    <h1>Growing Annuity</h1>
    <p class="sub">Compute the present value or future value of a series of equal payments that grow by a fixed percentage each period (ordinary growing annuity: end-of-period payments). Enter the first payment amount (paid at the end of period 1), then the annual interest rate and annual growth rate.</p>

    <div class="card">

        <?php if ($errors): ?>
            <div class="errors"><?php echo h(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="row">
                <div>
                    <label for="pmt1">First Payment (end of period 1)</label>
                    <input id="pmt1" name="pmt1" inputmode="decimal" value="<?php echo h($pmt1); ?>" placeholder="e.g., 500">
                </div>

                <div>
                    <label for="mode">Calculate</label>
                    <select id="mode" name="mode">
                        <option value="fv" <?php echo ($mode === 'fv') ? 'selected' : ''; ?>>Future Value</option>
                        <option value="pv" <?php echo ($mode === 'pv') ? 'selected' : ''; ?>>Present Value</option>
                    </select>
                </div>

                <div>
                    <label for="rate">Annual Interest Rate (%)</label>
                    <input id="rate" name="rate" inputmode="decimal" value="<?php echo h($rate); ?>" placeholder="e.g., 6.5">
                </div>

                <div>
                    <label for="growth">Annual Growth Rate (%)</label>
                    <input id="growth" name="growth" inputmode="decimal" value="<?php echo h($growth); ?>" placeholder="e.g., 2">
                </div>

                <div>
                    <label for="years">Years</label>
                    <input id="years" name="years" inputmode="decimal" value="<?php echo h($years); ?>" placeholder="e.g., 10">
                </div>

                <div>
                    <label for="compound">Periods per Year</label>
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

        <?php if ($result !== null && isset($result['label'], $result['value'])): ?>
            <div class="result">
                <div><?php echo h((string)$result['label']); ?></div>
                <div class="big"><?php echo h('$' . number_format((float)$result['value'], 2)); ?></div>
                <div class="units">(Ordinary growing annuity; first payment at end of period 1)</div>
            </div>
        <?php endif; ?>

    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</div>

</body>
</html>
