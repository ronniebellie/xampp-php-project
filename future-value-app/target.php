<?php
// Debug mode (only when ?debug=1 is present)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/_includes/header.php';
require_once __DIR__ . '/_includes/functions.php';

$result = null;

$target_fv = '';
$rate_pct  = '';
$years     = '';
$present   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_fv = $_POST['target_fv'] ?? '';
    $rate_pct  = $_POST['rate'] ?? '';
    $years     = $_POST['years'] ?? '';
    $present   = $_POST['present_value'] ?? '';

    $target_fv_num = (float) $target_fv;
    $rate_num      = ((float) $rate_pct) / 100;
    $years_num     = (int) $years;
    $present_num   = (float) $present;

    if ($years_num > 0) {
        $result = required_payment_for_target($target_fv_num, $rate_num, $years_num, $present_num);
    }
}
?>

<h2>Target Future Value</h2>

<p>Find the required end-of-year deposit to reach a future value.</p>

<form method="post">
    <p>
        <label>
            Target Future Value:
            <input type="number" step="0.01" name="target_fv" value="<?php echo htmlspecialchars($target_fv, ENT_QUOTES); ?>" required>
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
        <label>
            Already Saved Today (optional):
            <input type="number" step="0.01" name="present_value" value="<?php echo htmlspecialchars($present, ENT_QUOTES); ?>">
        </label>
    </p>

    <p>
        <button type="submit">Calculate</button>
    </p>
</form>

<?php if ($result !== null): ?>
    <h3>Required Deposit (end of each year)</h3>
    <p><strong><?php echo number_format($result, 2); ?></strong></p>
<?php endif; ?>

<p>
    <a href="index.php">← Back to Future Value App</a>
</p>

<?php require_once __DIR__ . '/_includes/footer.php'; ?>