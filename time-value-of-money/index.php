<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isPremium = false;
if ($isLoggedIn) {
    require_once '../includes/db_config.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $isPremium = ($user['subscription_status'] === 'premium');
}
// Don't close PHP yet - keep variables in scope
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Time Value of Money Calculators</title>
    <link rel="stylesheet" href="/css/styles.css" />
</head>
<body>
  <!-- Premium Banner -->
<?php include('../includes/premium-banner-include.php'); ?>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <h3 style="margin-top: 0; color: #22543d;">✓ Premium Active</h3>
    <p style="margin: 0;">Save scenarios available in individual calculators.</p>
</div>
<?php endif; ?>
  <div class="wrap">
    <header>
      <h1>Time Value of Money Calculators</h1>
      <p>Select a calculator to get started.</p>
    </header>
    <main class="grid" aria-label="Calculator links">
      <section class="card">
        <h3>Future Value</h3>
        <p>Calculate the future value of a single lump sum after a specified number of periods at a given interest rate.</p>
        <a class="btn" href="future-value/">Open</a>
      </section>
      <section class="card">
        <h3>Future Value Annuity</h3>
        <p>Determine the future value of a series of equal payments made at regular intervals.</p>
        <a class="btn" href="future-value-annuity/">Open</a>
      </section>
      <section class="card">
        <h3>Growing Annuity</h3>
        <p>Compute the future value of a series of payments that grow at a constant rate each period.</p>
        <a class="btn" href="growing-annuity/">Open</a>
      </section>
      <section class="card">
        <h3>Interest Rate</h3>
        <p>Find the interest rate required for an investment to reach a specific value over a set period.</p>
        <a class="btn" href="interest-rate/">Open</a>
      </section>
      <section class="card">
        <h3>Loan Payment</h3>
        <p>Calculate the regular payment amount needed to pay off a loan over time at a given interest rate.</p>
        <a class="btn" href="loan-payment/">Open</a>
      </section>
      <section class="card">
        <h3>Number of Periods</h3>
        <p>Determine how many periods it will take for an investment to reach a desired value at a given rate.</p>
        <a class="btn" href="number-of-periods/">Open</a>
      </section>
      <section class="card">
        <h3>Present Value</h3>
        <p>Calculate the present value of a future amount based on a specified discount rate and periods.</p>
        <a class="btn" href="present-value/">Open</a>
      </section>
      <section class="card">
        <h3>Present Value of Annuity</h3>
        <p>Find the present value of a series of equal payments to be received in the future.</p>
        <a class="btn" href="present-value-annuity/">Open</a>
      </section>
      <a class="btn" href="/index.php">Return to Home</a>
    </main>
    <footer>
      <p>&copy; 2026 Ron Belisle</p>
    </footer>
  </div>
</body>
</html>
