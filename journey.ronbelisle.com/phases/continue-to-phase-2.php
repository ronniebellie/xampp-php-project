<?php
$active_phase = 'spending-goals';
$page_title = 'Save Your Progress | Retirement Planning Journey';

$phase2Url = '/phases/social-security.php';
$journeyReturn = 'https://journey.ronbelisle.com/phases/social-security.php?from=account';
$freeAccountUrl = 'https://ronbelisle.com/auth/register.php?return=' . rawurlencode($journeyReturn);
$loginUrl = 'https://ronbelisle.com/auth/login.php?return=' . rawurlencode($journeyReturn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260727-continue-phase2-copy">
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
                    <p class="page-lede">You’ve created your retirement spending target. Create a free account to save your place and continue through the six free phases of the Retirement Planning Journey. After you complete your initial plan, you’ll have the option to start a 30-day Journey Premium trial to keep your plan current over time.</p>

                    <div class="transition-honesty" role="note">
                        <p><strong>Important:</strong> Your Journey plan is saved in <em>this browser</em> right now. Creating a free account does not automatically copy that plan into your account or sync it across devices.</p>
                        <p>Keep using this same browser to continue Phase 2 with your saved spending target.</p>
                    </div>

                    <div class="transition-options">
                        <section class="transition-option is-primary" aria-labelledby="free-account-title">
                            <h2 id="free-account-title">Create a free account</h2>
                            <p>A free account gives you a ronbelisle.com login so you can return, save your place, and continue through all six free Journey phases.</p>
                            <ul class="coach-list">
                                <li>Create a login for your planning work on ronbelisle.com</li>
                                <li>Continue through the six free Journey phases</li>
                                <li>Return later with a clearer sense of where your work lives</li>
                            </ul>
                            <a class="primary-action" href="<?php echo htmlspecialchars($freeAccountUrl); ?>">Create Free Account and Continue</a>
                            <p class="action-note">Opens ronbelisle.com registration, then returns you to Phase 2 in this browser. Your current Journey plan stays in this browser unless a later save step explicitly moves it.</p>
                        </section>

                        <section class="transition-option" aria-labelledby="premium-later-title">
                            <h2 id="premium-later-title">Journey Premium is optional</h2>
                            <p>All six Journey phases are free. Journey Premium is an optional ongoing planning workspace you can choose <strong>after</strong> you complete your initial plan.</p>
                            <ul class="coach-list">
                                <li>Revisit decisions and update assumptions over time</li>
                                <li>Compare alternatives as your life changes</li>
                                <li>Keep your plan current in an ongoing workspace</li>
                            </ul>
                            <p class="action-note">When your initial plan is complete, you can start a 30-day Journey Premium trial if you want that ongoing workspace. You do not need Premium to continue to Phase 2.</p>
                        </section>

                        <section class="transition-option is-quiet" aria-labelledby="browser-continue-title">
                            <h2 id="browser-continue-title">Continue using this browser</h2>
                            <p>Your Phase 1 spending plan stays in this browser’s local storage. It may not be available on another device, and it can be lost if browser data is cleared.</p>
                            <a class="text-action" href="<?php echo htmlspecialchars($phase2Url); ?>">Continue Using This Browser</a>
                        </section>
                    </div>

                    <section class="transition-login-option" aria-labelledby="existing-account-title">
                        <h2 id="existing-account-title">Already have an account?</h2>
                        <p>Log in on ronbelisle.com, then return here in this browser to continue Phase 2 with your saved spending target.</p>
                        <a class="secondary-action" href="<?php echo htmlspecialchars($loginUrl); ?>">Log in and continue your Journey</a>
                    </section>
                    <p class="transition-back"><a href="/phases/spending-goals.php">← Back to Phase 1</a></p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
