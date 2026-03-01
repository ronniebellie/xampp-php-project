<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isPremium = false;
if ($isLoggedIn) {
    require_once __DIR__ . '/../../includes/db_config.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $isPremium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include(__DIR__ . '/../../includes/analytics.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inherited IRA &amp; Legacy Tax Impact Calculator</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .heir-row { display: grid; grid-template-columns: 1fr 80px 100px 120px 1fr; gap: 10px; align-items: end; margin-bottom: 12px; }
        @media (max-width: 700px) { .heir-row { grid-template-columns: 1fr 1fr; } .heir-row label { grid-column: 1 / -1; } }
        .info-box { background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 16px 0; }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <p style="margin-bottom: 20px;">
            <a href="../" style="text-decoration: none; color: #1d4ed8;">← Back to Estate &amp; Legacy Planning Suite</a>
        </p>

        <header>
            <h1>Inherited IRA &amp; Legacy Tax Impact Calculator</h1>
            <p class="sub">
                Model how much tax you and your heirs may pay across two generations. Compare &ldquo;do nothing&rdquo; with a Roth conversion strategy under the <a href="https://www.irs.gov/retirement-plans/plan-participant-employee/retirement-topics-beneficiary" target="_blank" rel="noopener">SECURE Act</a> 10-year rule.
            </p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 24px;">
            <h2>How it works</h2>
            <p>
                Enter your current tax-deferred and Roth balances, your planned conversion amount (if any), and your heirs&apos; shares and income. The calculator projects your balances to an assumed death age, then simulates the 10-year inherited IRA withdrawal for each heir and estimates federal income tax for both you and them. You get a side-by-side comparison: total tax with no conversions vs. with conversions.
            </p>
        </div>

        <form id="inheritedIRAForm">
            <h3>Your situation (owner)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px;">
                <div>
                    <label for="currentAge" style="display: block; margin-bottom: 4px; font-weight: 600;">Your current age</label>
                    <input type="number" id="currentAge" value="68" min="50" max="95" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="deathAge" style="display: block; margin-bottom: 4px; font-weight: 600;">Assumed age at death</label>
                    <input type="number" id="deathAge" value="90" min="70" max="105" required style="width: 100%; padding: 8px;">
                    <small style="color: #666;">Used to project balances at inheritance</small>
                </div>
                <div>
                    <label for="filingStatus" style="display: block; margin-bottom: 4px; font-weight: 600;">Filing status</label>
                    <select id="filingStatus" style="width: 100%; padding: 8px;">
                        <option value="single">Single</option>
                        <option value="married" selected>Married filing jointly</option>
                        <option value="married_separate">Married filing separately</option>
                        <option value="head">Head of household</option>
                    </select>
                </div>
                <div>
                    <label for="traditionalIRA" style="display: block; margin-bottom: 4px; font-weight: 600;">Traditional IRA balance ($)</label>
                    <input type="number" id="traditionalIRA" value="960000" min="0" step="1000" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="rothIRA" style="display: block; margin-bottom: 4px; font-weight: 600;">Roth IRA balance ($)</label>
                    <input type="number" id="rothIRA" value="150000" min="0" step="1000" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="returnRate" style="display: block; margin-bottom: 4px; font-weight: 600;">Expected return on IRA balances (%)</label>
                    <input type="number" id="returnRate" value="5" min="0" max="15" step="0.5" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="retirementIncome" style="display: block; margin-bottom: 4px; font-weight: 600;">Annual other income ($)</label>
                    <input type="number" id="retirementIncome" value="55000" min="0" step="1000" style="width: 100%; padding: 8px;">
                    <small style="color: #666;">SS, pension, etc. (excluding IRA)</small>
                </div>
            </div>

            <h3 style="margin-top: 28px;">Roth conversion plan (optional)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px;">
                <div>
                    <label for="conversionAmount" style="display: block; margin-bottom: 4px; font-weight: 600;">Annual conversion ($)</label>
                    <input type="number" id="conversionAmount" value="50000" min="0" step="5000" style="width: 100%; padding: 8px;">
                    <small style="color: #666;">0 = no conversions</small>
                </div>
                <div>
                    <label for="conversionYears" style="display: block; margin-bottom: 4px; font-weight: 600;">Years to convert</label>
                    <input type="number" id="conversionYears" value="10" min="1" max="30" style="width: 100%; padding: 8px;">
                </div>
            </div>

            <h3 style="margin-top: 28px;">Heirs (inherited IRA <a href="https://www.irs.gov/retirement-plans/plan-participant-employee/retirement-topics-beneficiary" target="_blank" rel="noopener">10-year rule</a>)</h3>
            <p class="sub" style="margin-bottom: 12px;">Enter up to 4 heirs. Share % should total 100. Each heir&apos;s &ldquo;other income&rdquo; is their existing taxable income when they take distributions.</p>
            <div id="heirsContainer">
                <div class="heir-row">
                    <label style="font-weight: 600;">Name</label>
                    <label style="font-weight: 600;">Age</label>
                    <label style="font-weight: 600;">Share %</label>
                    <label style="font-weight: 600;">Other income ($)</label>
                    <label style="font-weight: 600;">Filing</label>
                </div>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="heir-row" data-heir="<?php echo $i; ?>">
                    <input type="text" id="heirName<?php echo $i; ?>" placeholder="Heir <?php echo $i; ?>" style="padding: 8px;">
                    <input type="number" id="heirAge<?php echo $i; ?>" placeholder="Age" min="18" max="80" value="<?php echo $i === 1 ? 42 : ($i === 2 ? 40 : ($i === 3 ? 38 : 35)); ?>" style="padding: 8px;">
                    <input type="number" id="heirShare<?php echo $i; ?>" placeholder="%" min="0" max="100" value="<?php echo $i <= 3 ? (int)(100/3) : 0; ?>" style="padding: 8px;">
                    <input type="number" id="heirIncome<?php echo $i; ?>" placeholder="Income" min="0" step="1000" value="<?php echo $i === 1 ? 300000 : 80000; ?>" style="padding: 8px;">
                    <select id="heirFiling<?php echo $i; ?>" style="padding: 8px;">
                        <option value="single" <?php echo $i === 1 ? 'selected' : ''; ?>>Single</option>
                        <option value="married" <?php echo $i !== 1 ? 'selected' : ''; ?>>Married</option>
                    </select>
                </div>
                <?php endfor; ?>
            </div>
            <p style="margin-top: 8px; font-size: 13px; color: #666;">Tip: set one heir to 100% and others to 0% to model a single beneficiary.</p>

            <h3 style="margin-top: 28px;">Inherited IRA assumptions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px;">
                <div>
                    <label for="payoutStrategy" style="display: block; margin-bottom: 4px; font-weight: 600;">Withdrawal strategy</label>
                    <select id="payoutStrategy" style="width: 100%; padding: 8px;">
                        <option value="level">Level over 10 years</option>
                        <option value="year10">All in year 10</option>
                    </select>
                </div>
                <div>
                    <label for="inheritedReturnRate" style="display: block; margin-bottom: 4px; font-weight: 600;">Heir portfolio return (%)</label>
                    <input type="number" id="inheritedReturnRate" value="5" min="0" max="15" step="0.5" style="width: 100%; padding: 8px;">
                </div>
            </div>

            <div style="text-align: center; margin: 28px 0;">
                <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 28px;">Calculate legacy tax impact</button>
            </div>
        </form>

        <div id="results" class="results-container" style="display: none;">
            <h2>Legacy tax impact results</h2>
            <div id="resultsContent"></div>
        </div>

        <?php if (!$isPremium): ?>
            <?php
            $premium_upsell_headline = 'Unlock Premium for This Calculator';
            $premium_upsell_text = 'Save and compare scenarios, export PDF and CSV, and access full year-by-year tables.';
            $premium_upsell_link = '../../premium.html';
            include(__DIR__ . '/../../includes/premium-upsell-banner.php');
            ?>
        <?php endif; ?>
        <?php include(__DIR__ . '/../../includes/footer_simple.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="calculator.js"></script>
</body>
</html>
