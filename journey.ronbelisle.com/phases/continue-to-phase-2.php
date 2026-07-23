<?php
$active_phase = 'spending-goals';
$page_title = 'Save Your Progress | Retirement Planning Journey';

$phase2Url = '/phases/social-security.php';
$journeyReturn = 'https://journey.ronbelisle.com/phases/social-security.php?from=account';
$freeAccountUrl = 'https://ronbelisle.com/auth/register.php?return=' . rawurlencode($journeyReturn);
$trialUrl = 'https://ronbelisle.com/auth/register.php?intent=trial&return=' . rawurlencode($journeyReturn);
$loginUrl = 'https://ronbelisle.com/auth/login.php?return=' . rawurlencode($journeyReturn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="/" aria-label="Retirement Planning Journey home">
            <span class="brand-mark" aria-hidden="true">RB</span>
            <span>Retirement Planning Journey</span>
        </a>
    </header>

    <main>
        <section class="page-hero" aria-labelledby="transition-title">
            <div class="container phase-transition-layout">
                <article class="planning-panel phase-transition-panel">
                    <p class="eyebrow">Before Phase 2</p>
                    <h1 id="transition-title">Save your progress before continuing</h1>
                    <p class="page-lede">You’ve created your retirement spending target. Create a free account so you have a home for your planning on ronbelisle.com while you continue the Journey.</p>

                    <div class="transition-honesty" role="note">
                        <p><strong>Important:</strong> Your Journey plan is saved in <em>this browser</em> right now. Creating an account does not automatically copy that plan into your account or sync it across devices yet.</p>
                        <p>Keep using this same browser to continue Phase 2 with your saved spending target.</p>
                    </div>

                    <div class="transition-options">
                        <section class="transition-option is-primary" aria-labelledby="free-account-title">
                            <h2 id="free-account-title">Create a free account</h2>
                            <p>A free account gives you a ronbelisle.com login so you can return to calculators and Premium features later. It also helps you continue the Journey with a clearer sense of where your work lives.</p>
                            <ul class="coach-list">
                                <li>Preserve a login for your planning work</li>
                                <li>Continue through the core Journey</li>
                                <li>Return later to ronbelisle.com tools without starting from scratch on that site</li>
                            </ul>
                            <a class="primary-action" href="<?php echo htmlspecialchars($freeAccountUrl); ?>">Create Free Account and Continue</a>
                            <p class="action-note">Opens ronbelisle.com registration, then returns you to Phase 2 in this browser.</p>
                        </section>

                        <section class="transition-option" aria-labelledby="premium-trial-title">
                            <h2 id="premium-trial-title">Optional: start a Premium trial</h2>
                            <p>Premium is an ongoing planning workspace on ronbelisle.com. It is <strong>not required</strong> to continue the Journey.</p>
                            <ul class="coach-list">
                                <li>Save and revisit calculator scenarios</li>
                                <li>Export reports and use AI explanations on supported tools</li>
                                <li>Keep Premium features available across sessions on ronbelisle.com</li>
                            </ul>
                            <a class="secondary-action" href="<?php echo htmlspecialchars($trialUrl); ?>">Start My 7-Day Premium Trial</a>
                            <p class="action-note">Uses the existing 7-day trial. Payment method required at checkout; you are not charged until the trial ends.</p>
                        </section>

                        <section class="transition-option is-quiet" aria-labelledby="browser-continue-title">
                            <h2 id="browser-continue-title">Continue using this browser</h2>
                            <p>Your Phase 1 spending plan stays in this browser’s local storage. It may not be available on another device, and it can be lost if browser data is cleared.</p>
                            <a class="text-action" href="<?php echo htmlspecialchars($phase2Url); ?>">Continue Using This Browser</a>
                        </section>
                    </div>

                    <p class="transition-login">Already have an account? <a href="<?php echo htmlspecialchars($loginUrl); ?>">Log in</a>, then return here to continue Phase 2 in this browser.</p>
                    <p class="transition-back"><a href="/phases/spending-goals.php">← Back to Phase 1</a></p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
