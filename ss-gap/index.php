<?php
$isPost = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');

function post_str(string $key, string $default=''): string {
  if (!isset($_POST[$key])) return $default;
  return trim((string)$_POST[$key]);
}
function post_float(string $key) {
  $v = post_str($key, '');
  if ($v === '') return '';
  $v = str_replace(',', '', $v);
  if (!is_numeric($v)) return '';
  return (float)$v;
}

$household = $isPost ? post_str('household', 'married') : 'married';
$targetMonthly = $isPost ? post_float('target_monthly_spending') : '';
$ssMonthly = $isPost ? post_float('ss_monthly_income') : '';
$withdrawRatePct = $isPost ? post_float('withdraw_rate_pct') : 4.7;

$errors = [];
$result = null;

if ($isPost) {
  if ($targetMonthly === '' || $targetMonthly < 0) $errors[] = 'Enter a valid Target Monthly Spending.';
  if ($ssMonthly === '' || $ssMonthly < 0) $errors[] = 'Enter a valid Social Security Monthly Income.';
  if ($withdrawRatePct === '' || $withdrawRatePct <= 0) $errors[] = 'Enter a valid Withdrawal Rate (%).';

  if (!$errors) {
    $monthlyGap = max(0, (float)$targetMonthly - (float)$ssMonthly);
    $annualGap = $monthlyGap * 12;

    $withdrawRate = ((float)$withdrawRatePct) / 100;
    $portfolioNeeded = ($withdrawRate > 0) ? ($annualGap / $withdrawRate) : 0;

    $result = [
      'monthly_gap' => $monthlyGap,
      'annual_gap' => $annualGap,
      'portfolio_needed' => $portfolioNeeded,
    ];
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Social Security + Spending Gap Calculator</title>
  <link rel="stylesheet" href="/css/styles.css?v=3">
</head>
<body>
  <div class="page container" style="max-width: 900px; margin: 0 auto; padding: 24px 24px 40px;">
    <div class="top-nav" style="margin-bottom: 12px;">
      <a class="home-btn" href="/" style="display:inline-flex; align-items:center; padding:8px 14px; border-radius:999px; border:1px solid #e5e7eb; background:#ffffff; color:#111827; text-decoration:none; font-weight:600; line-height:1; white-space:nowrap;">
        Return to home page
      </a>
    </div>
    <h1 style="text-align:center; margin: 0 0 18px 0;">Social Security + Spending Gap Calculator</h1>

    <p class="intro">
      Enter your target monthly spending and your expected monthly Social Security income. The calculator estimates your spending gap and the portfolio size needed to cover that gap using a starting withdrawal rate.
    </p>

    <form method="post" action="">
      <label>
        Household:
        <select name="household">
          <option value="single"  <?= $household === 'single' ? 'selected' : '' ?>>Single</option>
          <option value="married" <?= $household === 'married' ? 'selected' : '' ?>>Married</option>
        </select>
      </label>

      <br><br>

      <label>
        Target Monthly Spending ($):
        <input type="text" inputmode="decimal" name="target_monthly_spending" value="<?= htmlspecialchars($targetMonthly === '' ? '' : (string)$targetMonthly) ?>">
      </label>

      <br><br>

      <label>
        Social Security Monthly Income ($):
        <input type="text" inputmode="decimal" name="ss_monthly_income" value="<?= htmlspecialchars($ssMonthly === '' ? '' : (string)$ssMonthly) ?>">
      </label>

      <br><br>

      <label>
        Starting Withdrawal Rate (%):
        <input type="text" inputmode="decimal" name="withdraw_rate_pct" value="<?= htmlspecialchars($withdrawRatePct === '' ? '' : (string)$withdrawRatePct) ?>">
      </label>

      <br><br>

      <?php if ($errors): ?>
        <div style="margin: 8px 0 18px 0; padding: 10px 12px; border: 1px solid #d33; background: #fff5f5;">
          <?= htmlspecialchars(implode(' ', $errors)) ?>
        </div>
      <?php endif; ?>

      <button type="submit">Calculate</button>
    </form>

    <?php if ($result): ?>
      <hr style="margin: 24px 0;">

      <h2 style="margin: 0 0 12px 0;">Results</h2>
      <p>Monthly Spending Gap: $<?= number_format((float)$result['monthly_gap'], 0) ?></p>
      <p>Annual Spending Gap: $<?= number_format((float)$result['annual_gap'], 0) ?></p>
      <p>Portfolio Needed (at <?= number_format((float)$withdrawRatePct, 2) ?>%): $<?= number_format((float)$result['portfolio_needed'], 0) ?></p>
    <?php endif; ?>

    <hr class="footer-divider" style="margin: 24px 0 0; border: 0; border-top: 1px solid #d0d0d0;">

    <?php
      $footerPath = __DIR__ . '/../includes/footer.php';
      if (file_exists($footerPath)) {
        include $footerPath;
      }
    ?>
  </div>
</body>
</html>
