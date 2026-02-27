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
    $sub = null;
    $stmt->bind_result($sub);
    $user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
    $stmt->close();
    $isPremium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("../includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Retirement Timeline &amp; Checklist</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <?php include('../includes/premium-banner-include.php'); ?>
  <div class="wrap">
    <p style="margin-bottom: 20px;">
      <a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a>
    </p>

    <header>
      <h1>Retirement Timeline &amp; Checklist</h1>
      <p class="sub">
        Turn your retirement date into a simple, age‑based checklist—from early prep to the last day at work and the
        first year of retirement.
      </p>
    </header>

    <div class="info-box-blue" style="margin-bottom: 30px;">
      <h2>How this tool works</h2>
      <p>
        Enter your birthdate and target retirement date. We estimate your age at retirement and build a timeline with
        tasks grouped into phases (years before retirement, months before, and your first year in retirement).
      </p>
      <p style="margin-top: 8px;">
        Each task gets a suggested date, a short description, and a checkbox you can mark complete. You can print the
        page (or save as PDF) to bring to meetings or keep in your planning folder.
      </p>
    </div>

    <form id="timelineForm">
      <h3>Your dates</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px;">
        <div>
          <label for="tlBirthMonth" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Your birthdate
          </label>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select id="tlBirthMonth" style="flex: 1; min-width: 80px; padding: 8px;">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo $m; ?>"<?php echo $m === 1 ? ' selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
              <?php endfor; ?>
            </select>
            <select id="tlBirthDay" style="flex: 1; min-width: 60px; padding: 8px;">
              <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?php echo $d; ?>"<?php echo $d === 1 ? ' selected' : ''; ?>><?php echo $d; ?></option>
              <?php endfor; ?>
            </select>
            <select id="tlBirthYear" style="flex: 1; min-width: 80px; padding: 8px;">
              <?php for ($y = (int)date('Y'); $y >= 1920; $y--): ?>
              <option value="<?php echo $y; ?>"<?php echo $y === 1960 ? ' selected' : ''; ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <input type="hidden" id="birthdate" value="1960-01-01">
          <small style="color: #666;">Used to calculate your age at each milestone. Pick month, day, and year—no calendar scrolling.</small>
        </div>

        <div>
          <label for="tlRetireMonth" style="display: block; margin-bottom: 5px; font-weight: 600;">
            Target retirement date (last day of work)
          </label>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select id="tlRetireMonth" style="flex: 1; min-width: 80px; padding: 8px;">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
              <?php endfor; ?>
            </select>
            <select id="tlRetireDay" style="flex: 1; min-width: 60px; padding: 8px;">
              <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
              <?php endfor; ?>
            </select>
            <select id="tlRetireYear" style="flex: 1; min-width: 80px; padding: 8px;">
              <?php for ($y = (int)date('Y'); $y <= (int)date('Y') + 30; $y++): ?>
              <option value="<?php echo $y; ?>"<?php echo $y === ((int)date('Y') + 5) ? ' selected' : ''; ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <input type="hidden" id="retirementDate" value="">
          <small style="color: #666;">Pick month, day, and year—no calendar scrolling. You can adjust this anytime and rebuild the checklist.</small>
        </div>
      </div>

      <div style="text-align: center; margin: 30px 0;">
        <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">
          Build my retirement checklist
        </button>
      </div>
    </form>

    <div id="results" style="display: none;">
      <h2>Your personalized timeline</h2>
      <p id="summaryLine" style="margin: 6px 0 16px; color: #374151;"></p>

      <div id="timelineContainer" style="border-left: 3px solid #e5e7eb; margin-left: 6px; padding-left: 16px;">
        <!-- Sections injected here -->
      </div>

      <div class="info-box-blue" style="margin-top: 16px;">
        <h3 style="margin-top: 0;">Using this checklist</h3>
        <p id="explanationText">
          These dates and tasks are based on common rules of thumb and checklists. Real planning should consider your
          employer benefits, pension rules, health coverage, and tax situation, so treat this as a conversation
          starter—not legal, tax, or investment advice.
        </p>
      </div>

      <?php
        $share_title = 'Retirement Timeline & Checklist';
        $share_text  = 'Try this Retirement Timeline & Checklist at ronbelisle.com — turn your retirement date into a simple, phased checklist.';
        include(__DIR__ . '/../includes/share-results-block.php');
      ?>
    </div>

    <?php if (!$isPremium): ?>
    <?php
      $premium_upsell_headline = 'Save and print your retirement timeline';
      $premium_upsell_text = 'Upgrade to Premium to save multiple timelines, export PDFs, and compare with other scenarios.';
      include(__DIR__ . '/../includes/premium-upsell-banner.php');
    ?>
    <footer class="site-footer">
      <span class="donate-text">If these tools are useful, please consider supporting future development.</span>
      <a href="https://www.paypal.com/paypalme/rongbelisle" target="_blank" class="donate-btn">
        <span class="donate-dot"></span>
        Donate
      </a>
    </footer>
    <?php endif; ?>
  </div>

  <script src="../js/share-results.js"></script>
  <script>const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;</script>
  <script>
  (function() {
    function daysInMonth(m, y) {
      return new Date(parseInt(y, 10), parseInt(m, 10), 0).getDate();
    }

    var bMonth = document.getElementById('tlBirthMonth');
    var bDay = document.getElementById('tlBirthDay');
    var bYear = document.getElementById('tlBirthYear');
    var bHidden = document.getElementById('birthdate');

    function syncBirthDate() {
      var m = bMonth.value;
      var y = bYear.value;
      var maxDay = daysInMonth(m, y);
      var d = Math.min(parseInt(bDay.value, 10), maxDay);
      if (parseInt(bDay.value, 10) > maxDay) bDay.value = d;
      bHidden.value = y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    if (bMonth && bDay && bYear && bHidden) {
      bMonth.addEventListener('change', syncBirthDate);
      bDay.addEventListener('change', syncBirthDate);
      bYear.addEventListener('change', syncBirthDate);
      syncBirthDate();
    }

    var rMonth = document.getElementById('tlRetireMonth');
    var rDay = document.getElementById('tlRetireDay');
    var rYear = document.getElementById('tlRetireYear');
    var rHidden = document.getElementById('retirementDate');

    function syncRetireDate() {
      var m = rMonth.value;
      var y = rYear.value;
      var maxDay = daysInMonth(m, y);
      var d = Math.min(parseInt(rDay.value, 10), maxDay);
      if (parseInt(rDay.value, 10) > maxDay) rDay.value = d;
      rHidden.value = y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    if (rMonth && rDay && rYear && rHidden) {
      rMonth.addEventListener('change', syncRetireDate);
      rDay.addEventListener('change', syncRetireDate);
      rYear.addEventListener('change', syncRetireDate);
      syncRetireDate();
    }
  })();
  </script>
  <script src="calculator.js"></script>
</body>
</html>

