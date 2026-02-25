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
  <?php include("../includes/analytics.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Future Value Calculator</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .calculator-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            flex-wrap: wrap;
        }
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.2s;
        }
        .tab-button:hover {
            color: #1d4ed8;
        }
        .tab-button.active {
            color: #1d4ed8;
            border-bottom-color: #1d4ed8;
        }
        .calculator-content {
            display: none;
        }
        .calculator-content.active {
            display: block;
        }
    </style>
</head>
<body>

    <!-- Premium Banner -->
    <?php include('../includes/premium-banner-include.php'); ?>
    <div class="wrap">
        <p style="margin-bottom: 20px;"><a href="../" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

        <header>
            <h1>Future Value Calculator</h1>
            <p class="sub">Calculate present value, future value, annuities, and required payments to reach your financial goals</p>
        </header>

        <div class="info-box-blue" style="margin-bottom: 30px;">
            <h2>Understanding Time Value of Money</h2>
            <p>The time value of money is a fundamental financial concept: a dollar today is worth more than a dollar tomorrow because of its earning potential. These calculators help you understand how money grows over time through compound interest, and how to plan for future financial goals.</p>
        </div>

<?php if ($isPremium): ?>
<div class="premium-features" style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #22543d;">💾 Premium Features</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <button type="button" id="saveScenarioBtn" class="btn-primary" style="background: #48bb78;" title="Store your current inputs and results for later">Save Scenario</button>
        <button type="button" id="loadScenarioBtn" class="btn-secondary" title="Open a previously saved scenario">Load Scenario</button>
        <button type="button" id="compareScenariosBtn" class="btn-primary" style="background: #f59e0b; color: white;" title="Side-by-side comparison of two saved scenarios">⚖️ Compare Scenarios</button>
        <button type="button" id="downloadPdfBtn" class="btn-primary" style="background: #e53e3e; color: white;" title="Full report with charts (PDF)">📄 Download PDF</button>
        <button type="button" id="downloadCsvBtn" class="btn-primary" style="background: #3182ce; color: white;" title="Year-by-year data for Excel or spreadsheets">📊 Export CSV</button>
        <span id="saveStatus" style="color: #22543d; font-weight: 600;"></span>
    </div>
    <p style="margin: 12px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
        <strong>Save</strong> / <strong>Load</strong> — Store and recall scenarios. <strong>Compare</strong> — See two scenarios side-by-side. <strong>PDF</strong> — Full report with charts. <strong>CSV</strong> — Spreadsheet data.
    </p>
</div>
<?php endif; ?>

        <!-- Calculator Type Tabs -->
        <div class="calculator-tabs">
            <button class="tab-button active" onclick="switchCalculator('single')">Single Amount</button>
            <button class="tab-button" onclick="switchCalculator('target')">Target Future Value</button>
            <button class="tab-button" onclick="switchCalculator('annuity')">Annuity Future Value</button>
            <button class="tab-button" onclick="switchCalculator('guided')">Guided Mode</button>
        </div>

        <!-- Single Amount Calculator -->
        <div id="single-calculator" class="calculator-content active">
            <h3>Single Amount (Present Value ⇄ Future Value)</h3>
            <p>Calculate what a lump sum will be worth in the future, or what you need to invest today to reach a future goal.</p>
            
            <form id="singleForm">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label for="singleType" style="display: block; margin-bottom: 5px; font-weight: 600;">Calculate:</label>
                        <select id="singleType" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <option value="fv">Future Value (I have money today)</option>
                            <option value="pv">Present Value (I need money in future)</option>
                        </select>
                    </div>
                    <div>
                        <label for="singleAmount" style="display: block; margin-bottom: 5px; font-weight: 600;">Amount ($)</label>
                        <input type="number" id="singleAmount" step="0.01" value="10000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;" id="singleAmountLabel">Starting amount today</small>
                    </div>
                    <div>
                        <label for="singleRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Interest Rate (%)</label>
                        <input type="number" id="singleRate" step="0.01" value="7" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Expected annual return</small>
                    </div>
                    <div>
                        <label for="singleYears" style="display: block; margin-bottom: 5px; font-weight: 600;">Number of Years</label>
                        <input type="number" id="singleYears" value="10" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Time horizon</small>
                    </div>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
                </div>
            </form>
            <div id="singleResults" style="display: none;"></div>
        </div>

        <!-- Target Future Value Calculator -->
        <div id="target-calculator" class="calculator-content">
            <h3>Target Future Value (Required Monthly Payment)</h3>
            <p>Calculate how much you need to save each month to reach a specific financial goal.</p>
            
            <form id="targetForm">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label for="targetGoal" style="display: block; margin-bottom: 5px; font-weight: 600;">Target Future Value ($)</label>
                        <input type="number" id="targetGoal" step="0.01" value="100000" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Your financial goal</small>
                    </div>
                    <div>
                        <label for="targetPresent" style="display: block; margin-bottom: 5px; font-weight: 600;">Current Savings ($)</label>
                        <input type="number" id="targetPresent" step="0.01" value="0" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Amount you already have</small>
                    </div>
                    <div>
                        <label for="targetRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Interest Rate (%)</label>
                        <input type="number" id="targetRate" step="0.01" value="7" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Expected annual return</small>
                    </div>
                    <div>
                        <label for="targetYears" style="display: block; margin-bottom: 5px; font-weight: 600;">Number of Years</label>
                        <input type="number" id="targetYears" value="10" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Time to reach goal</small>
                    </div>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
                </div>
            </form>
            <div id="targetResults" style="display: none;"></div>
        </div>

        <!-- Annuity Future Value Calculator -->
        <div id="annuity-calculator" class="calculator-content">
            <h3>Annuity Future Value</h3>
            <p>Calculate how much regular monthly contributions will grow to over time.</p>
            
            <form id="annuityForm">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label for="annuityPayment" style="display: block; margin-bottom: 5px; font-weight: 600;">Monthly Payment ($)</label>
                        <input type="number" id="annuityPayment" step="0.01" value="500" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Amount you'll contribute each month</small>
                    </div>
                    <div>
                        <label for="annuityRate" style="display: block; margin-bottom: 5px; font-weight: 600;">Annual Interest Rate (%)</label>
                        <input type="number" id="annuityRate" step="0.01" value="7" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">Expected annual return</small>
                    </div>
                    <div>
                        <label for="annuityYears" style="display: block; margin-bottom: 5px; font-weight: 600;">Number of Years</label>
                        <input type="number" id="annuityYears" value="10" required style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <small style="color: #666;">How long you'll contribute</small>
                    </div>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <button type="submit" class="button" style="font-size: 1.1em; padding: 12px 30px;">Calculate</button>
                </div>
            </form>
            <div id="annuityResults" style="display: none;"></div>
        </div>

        <!-- Guided Calculator -->
        <div id="guided-calculator" class="calculator-content">
            <h3>Guided Mode</h3>
            <div class="info-box-blue">
                <p><strong>Not sure which calculator to use?</strong> Answer these questions:</p>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li><strong>Do you have a lump sum today?</strong> → Use <em>Single Amount (FV)</em></li>
                    <li><strong>Do you need to know what to invest today for a future goal?</strong> → Use <em>Single Amount (PV)</em></li>
                    <li><strong>Are you making regular monthly contributions?</strong> → Use <em>Annuity Future Value</em></li>
                    <li><strong>Do you have a specific goal and want to know monthly payments?</strong> → Use <em>Target Future Value</em></li>
                </ol>
            </div>
        </div>

        <?php $share_title = 'Future Value Calculator'; $share_text = 'Check out the Future Value calculator at ronbelisle.com — present value, future value, annuities, and required payments.'; include(__DIR__ . '/../includes/share-results-block.php'); ?>

        <?php if (!$isPremium): ?>
        <?php
        $premium_upsell_headline = 'Unlock Premium Features';
        $premium_upsell_text = 'Upgrade to Premium to save and compare scenarios and export PDF and CSV reports.';
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/share-results.js"></script>
    <script src="../js/compare-scenarios-modal.js"></script>
    <script>
    const isPremiumUser = <?php echo $isPremium ? 'true' : 'false'; ?>;
    </script>
    <script src="calculator.js"></script>
</body>
</html>