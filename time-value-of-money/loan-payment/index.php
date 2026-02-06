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
    <link rel="stylesheet" href="/css/styles.css" />
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

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</div>

</body>
</html>
