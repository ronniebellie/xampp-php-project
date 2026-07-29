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
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260729-m5-p2">
</head>
<body>
    <?php include __DIR__ . '/../includes/site-header.php'; ?>

    <main>
        <section class="page-hero" aria-labelledby="transition-title">
            <div class="container phase-transition-layout">
                <article class="planning-panel phase-transition-panel">
                    <p class="eyebrow">Before Phase 2</p>
                    <h1 id="transition-title">Save your progress before continuing</h1>

                    <div data-journey-anon-only>
                        <p class="page-lede">You’ve created your retirement spending target. Create a free account to save your place and continue through the six free phases of the Retirement Planning Journey. After you complete your initial plan, you’ll have the option to start a 30-day Journey Premium trial to keep your plan current over time.</p>

                        <div class="transition-honesty" role="note">
                            <p><strong>Important:</strong> Your Journey plan is saved in <em>this browser</em> right now. Creating a free account does not automatically copy that plan into your account or sync it across devices.</p>
                            <p>Keep using this same browser to continue Phase 2 with your saved spending target.</p>
                        </div>
                    </div>

                    <div data-journey-auth-only hidden>
                        <p class="page-lede">You’re signed in. Your Journey planning records still live in this browser for now. Continue to Phase 2 in this same browser to keep your spending target with you.</p>
                        <div class="transition-honesty" role="note">
                            <p><strong>Note:</strong> Signing in does not yet copy this browser’s Journey into your account. Cloud Journey saving for Journey Premium is coming in the next implementation step.</p>
                        </div>
                        <p><a class="primary-action" href="<?php echo htmlspecialchars($phase2Url); ?>">Continue to Phase 2</a></p>
                    </div>

                    <div class="transition-options" data-journey-anon-only>
                        <section class="transition-option is-primary" aria-labelledby="free-account-title">
                            <h2 id="free-account-title">Create a free account</h2>
                            <p>Create a free account so you can continue through all six free Journey phases and sign in later to access your planning work.</p>
                            <ul class="coach-list">
                                <li>Create your free Retirement Planning Journey account</li>
                                <li>Continue through all six free Journey phases</li>
                                <li>Sign in later to continue where you left off</li>
                            </ul>
                            <a class="primary-action" href="<?php echo htmlspecialchars($freeAccountUrl); ?>">Create Free Account and Continue</a>
                            <p class="action-note">Registration opens on ronbelisle.com, then returns you to Phase 2 in this same browser. Your Journey plan currently stays in this browser. Creating the account does not yet automatically sync that browser-stored Journey data across devices.</p>
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

                    <section class="transition-login-option" aria-labelledby="existing-account-title" data-journey-anon-only>
                        <h2 id="existing-account-title">Already have an account?</h2>
                        <p>Log in on ronbelisle.com, then return here in this browser to continue Phase 2 with your saved spending target.</p>
                        <a class="secondary-action" href="<?php echo htmlspecialchars($loginUrl); ?>">Log in and continue your Journey</a>
                    </section>

                    <div data-journey-auth-only hidden>
                        <section class="transition-option" aria-labelledby="premium-later-auth-title">
                            <h2 id="premium-later-auth-title">Journey Premium is optional</h2>
                            <p>All six Journey phases remain free. Journey Premium is available later if you want an ongoing planning workspace after your initial plan is complete.</p>
                        </section>
                    </div>

                    <p class="transition-back"><a href="/phases/spending-goals.php">← Back to Phase 1</a></p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
