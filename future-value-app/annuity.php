<?php
require_once __DIR__ . '/_includes/header.php';
require_once __DIR__ . '/_includes/functions.php';

$result = null;

$payment  = '';
$rate_pct = '';
$years    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment  = $_POST['payment'] ?? '';
    $rate_pct = $_POST['rate'] ?? '';
    $years    = $_POST['years'] ?? '';

    $payment_num = (float) $payment;
    $rate_num    = ((float) $rate_pct) / 100;
    $years_num   = (int) $years;

    if ($years_num > 0) {
        $result = future_value_annuity($payment_num, $rate_num, $years_num);
    }
}
?>

<h2>Annuity Future Value</h2>

<p>Find the future value of end-of-year deposits.</p>

<form method="post">
    <p>
        <label>
            Deposit each year (end of year):
            <input type="number" step="0.01" name="payment" value="<?php echo htmlspecialchars($payment, ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <label>
            Annual Interest Rate (% per year):
            <input type="number" step="0.01" name="rate" value="<?php echo htmlspecialchars($rate_pct, ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <label>
            Years:
            <input type="number" name="years" value="<?php echo htmlspecialchars($years, ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <button type="submit">Calculate</button>
    </p>
</form>

<?php if ($result !== null): ?>
    <h3>Future Value</h3>
    <p><strong><?php echo number_format($result, 2); ?></strong></p>
<?php endif; ?>

<p>
    <a href="index.php">← Back to Future Value App</a>
</p>

<?php
require_once __DIR__ . '/_includes/footer.php';
?>