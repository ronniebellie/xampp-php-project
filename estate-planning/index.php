<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isPremium = false;
if ($isLoggedIn) {
    require_once __DIR__ . '/../includes/db_config.php';
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
    <?php include(__DIR__ . '/../includes/analytics.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estate &amp; Legacy Planning Suite</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include(__DIR__ . '/../includes/premium-banner-include.php'); ?>

    <div class="wrap">
        <p style="margin-bottom: 20px;">
            <a href="../" style="text-decoration: none; color: #1d4ed8;">← Back to main calculators</a>
        </p>

        <header>
            <h1>Estate &amp; Legacy Planning Suite</h1>
            <p class="sub">
                Tools for modeling SECURE Act inherited IRA rules, Roth conversion strategies, and
                cross‑generation tax outcomes – built for financial advisors and estate planning attorneys.
            </p>
        </header>

        <div class="info-box-blue" style="margin: 24px 0 30px 0;">
            <h2>Built from real‑world planning questions</h2>
            <p>
                These tools grew out of a common challenge: most retirement calculators stop at your lifetime.
                Estate and legacy planning requires looking at <strong>total taxes paid across two generations</strong>,
                especially under the SECURE Act&apos;s 10‑year inherited IRA rule.
            </p>
            <p style="margin-top: 10px;">
                Use this suite to frame client conversations, compare strategies side‑by‑side, and
                communicate complex trade‑offs in plain language.
            </p>
        </div>

        <section style="margin-bottom: 32px;">
            <h2 style="margin-bottom: 10px;">Available tools</h2>
            <p class="sub" style="margin-bottom: 18px;">
                Start with the tools below; more estate‑focused calculators are on the roadmap.
            </p>

            <div class="card-grid">
                <div class="card">
                    <h3>Roth Conversion &amp; RMD Impact</h3>
                    <p>
                        Analyze when and how much to convert from traditional IRA to Roth, including
                        RMD effects and changes in marginal tax brackets over time.
                    </p>
                    <a href="../roth-conv/" class="button">Open Roth Conversion Calculator</a>
                </div>

                <div class="card">
                    <h3>RMD Impact Projection</h3>
                    <p>
                        Estimate Required Minimum Distributions from large tax‑deferred balances,
                        including how account growth, other income, and filing status interact with tax brackets.
                    </p>
                    <a href="../rmd-impact/" class="button">Open RMD Impact Calculator</a>
                </div>

                <div class="card">
                    <h3>Inherited IRA &amp; Legacy Tax Impact</h3>
                    <p>
                        Model how much tax heirs may owe on inherited traditional IRAs under the 10‑year rule,
                        and compare &ldquo;do nothing&rdquo; vs. targeted Roth conversion strategies across generations.
                    </p>
                    <a href="inherited-ira-impact/" class="button">Open calculator</a>
                </div>
            </div>
        </section>

        <section>
            <h2 style="margin-bottom: 10px;">For firms and white‑label use</h2>
            <p class="sub" style="margin-bottom: 16px;">
                Advisors and estate planning attorneys can license these tools with firm branding for use on their own sites.
            </p>
            <div class="info-box">
                <p style="margin: 0 0 10px 0;">
                    If you&apos;d like a version of this estate planning suite with your logo, colors, and custom disclosures,
                    visit <a href="https://calcforadvisors.com" target="_blank" rel="noopener">calcforadvisors.com</a>
                    or reach out directly from that page.
                </p>
                <p style="margin: 0; font-size: 0.9em; color: #4b5563;">
                    Existing Premium members can continue using these tools on ronbelisle.com as part of their subscription.
                </p>
            </div>
        </section>

        <?php if (!$isLoggedIn): ?>
            <div class="info-box" style="margin-top: 32px;">
                <h3>Optional: create a free account</h3>
                <p>
                    No account is required to use these tools. Creating a free login lets you upgrade to Premium,
                    which unlocks scenario saving, comparisons, and PDF/CSV exports across the full calculator suite.
                </p>
                <p style="margin-top: 8px;">
                    <a href="../auth/register.php" class="button">Sign up</a>
                    <a href="../auth/login.php" class="button button-secondary" style="margin-left: 10px;">Log in</a>
                </p>
            </div>
        <?php endif; ?>

        <?php include(__DIR__ . '/../includes/footer_simple.php'); ?>
    </div>
</body>
</html>

