<?php
// Present Value (Single Amount)

session_start();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$errors = [];

// Always start with blank fields on page load.
$fv = '';
$rate = '';
$years = '';
$compound = '12';

$result = null;

// Show the most recent result once (then clear it), so a browser refresh clears the page.
if (isset($_SESSION['pv_single_result'])) {
    $result = $_SESSION['pv_single_result'];
    unset($_SESSION['pv_single_result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fv = $_POST['fv'] ?? '';
    $rate = $_POST['rate'] ?? '';
    $years = $_POST['years'] ?? '';
    $compound = $_POST['compound'] ?? '12';

    $fvNum = filter_var($fv, FILTER_VALIDATE_FLOAT);
    $rateNum = filter_var($rate, FILTER_VALIDATE_FLOAT);
    $yearsNum = filter_var($years, FILTER_VALIDATE_FLOAT);
    $compoundNum = filter_var($compound, FILTER_VALIDATE_INT);

    if ($fvNum === false || $fvNum < 0) $errors[] = 'Future Value must be a number (0 or more).';
    if ($rateNum === false) $errors[] = 'Annual Rate must be a number.';
    if ($yearsNum === false || $yearsNum < 0) $errors[] = 'Years must be a number (0 or more).';
    if ($compoundNum === false || $compoundNum <= 0) $errors[] = 'Compounds per year must be a whole number (1 or more).';

    if (!$errors) {
        $r = $rateNum / 100.0;
        $m = (float) $compoundNum;
        $t = $yearsNum;

        $pvComputed = $fvNum / pow(1 + ($r / $m), $m * $t);

        // Store result for one-time display, then redirect to clear POST and form fields.
        $_SESSION['pv_single_result'] = $pvComputed;
        header('Location: /time-value-of-money/present-value/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Present Value (Single Amount)</title>
    <link rel="stylesheet" href="/css/styles.css" />
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <a class="btn" href="/time-value-of-money/">← Back to Calculators</a>
    </div>

    <h1>Present Value (Single Amount)</h1>
    <p class="sub">Compute the present value of a future amount discounted with periodic compounding.</p>

    <div class="card">

        <?php if ($errors): ?>
            <div class="errors"><?php echo h(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="row">
                <div>
                    <label for="fv">Future Value</label>
                    <input id="fv" name="fv" inputmode="decimal" value="<?php echo h($fv); ?>" placeholder="e.g., 10000">
                </div>

                <div>
                    <label for="rate">Annual Interest Rate (%)</label>
                    <input id="rate" name="rate" inputmode="decimal" value="<?php echo h($rate); ?>" placeholder="e.g., 6.5">
                </div>

                <div>
                    <label for="years">Years</label>
                    <input id="years" name="years" inputmode="decimal" value="<?php echo h($years); ?>" placeholder="e.g., 10">
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

        <?php if ($result !== null): ?>
            <div class="result">
                <div>Present Value</div>
                <div class="big"><?php echo h('$' . number_format((float)$result, 2)); ?></div>
            </div>
        <?php endif; ?>

    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</div>

</body>
</html>
