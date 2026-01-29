<?php
require_once __DIR__ . '/_includes/header.php';

require_once __DIR__ . '/_includes/functions.php';

// If PHP error display is off, force it on locally for troubleshooting
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!function_exists('future_value') || !function_exists('present_value')) {
    die('ERROR: Calculator functions not loaded.');
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['type'] ?? 'fv';
    $amount = (float) ($_POST['amount'] ?? 0);
    $rate   = (float) ($_POST['rate'] ?? 0) / 100;
    $years  = (int) ($_POST['years'] ?? 0);

    if ($type === 'pv') {
        $result = present_value($amount, $rate, $years);
    } else {
        $result = future_value($amount, $rate, $years);
    }
}
?>

<h2>Single Amount (PV / FV)</h2>

<p>This calculator handles one lump-sum amount.</p>

<form method="post">
    <p>
        <label>
            Calculate:
            <select name="type">
                <option value="fv" <?php echo (($_POST['type'] ?? 'fv') === 'fv') ? 'selected' : ''; ?>>Future Value (given Present Value)</option>
                <option value="pv" <?php echo (($_POST['type'] ?? 'fv') === 'pv') ? 'selected' : ''; ?>>Present Value (given Future Value)</option>
            </select>
        </label>
    </p>

    <p>
        <label>
            Amount:
            <input type="number" step="0.01" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? '', ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <label>
            Interest Rate (% per year):
            <input type="number" step="0.01" name="rate" value="<?php echo htmlspecialchars($_POST['rate'] ?? '', ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <label>
            Years:
            <input type="number" name="years" value="<?php echo htmlspecialchars($_POST['years'] ?? '', ENT_QUOTES); ?>" required>
        </label>
    </p>

    <p>
        <button type="submit">Calculate</button>
    </p>
</form>

<?php if ($result !== null): ?>
    <h3>Result</h3>
    <p><strong><?php echo number_format($result, 2); ?></strong></p>
<?php endif; ?>

<?php require_once __DIR__ . '/_includes/footer.php'; ?>