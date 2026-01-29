<?php
require_once __DIR__ . '/_includes/header.php';

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type  = $_POST['type'];
    $amount = (float) $_POST['amount'];
    $rate   = (float) $_POST['rate'] / 100;
    $years  = (int) $_POST['years'];

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

<p>
    <a href="index.php">← Back to Future Value App</a>
</p>

<?php require_once __DIR__ . '/_includes/footer.php'; ?>