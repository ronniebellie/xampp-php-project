<?php
$active_phase = 'spending-goals';
$page_title = 'Continue to Phase 2 | Retirement Planning Journey';

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
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260914-audit">
</head>
<body>
    <?php include __DIR__ . '/../includes/site-header.php'; ?>

    <main id="journey-main" tabindex="-1">
        <section class="page-hero" aria-labelledby="transition-title">
            <div class="container phase-transition-layout">
                <article class="planning-panel phase-transition-panel">
                    <p class="eyebrow">Before Phase 2</p>
                    <h1 id="transition-title">Continue to Social Security</h1>

                    <div data-journey-anon-only>
                        <p class="page-lede">You’ve created your retirement spending target. All six Journey phases are free, and no account is required to complete them. After you complete your initial plan, you’ll have the option to start a 30-day Journey Premium trial to keep your plan current over time.</p>

                        <div class="transition-honesty" role="note">
                            <p><strong>Important:</strong> Your Journey plan is saved in <em>this browser</em> right now. Creating a free account does not automatically copy that plan into your account or sync it across devices.</p>
                            <p>Keep using this same browser to continue Phase 2 with your saved spending target.</p>
                        </div>
                        <p><a class="primary-action" href="<?php echo htmlspecialchars($phase2Url); ?>">Continue in This Browser to Phase 2</a></p>
                        <p class="action-note">Your Phase 1 spending plan may not be available on another device, and it can be lost if browser data is cleared.</p>
                    </div>

                    <div data-journey-free-auth-only hidden>
                        <p class="page-lede">You’re signed in. Your Journey planning records are saved in this browser. Continue to Phase 2 in this same browser to keep your spending target with you.</p>
                        <div class="transition-honesty" role="note">
                            <p><strong>Note:</strong> Free Journey phases stay in this browser. Journey Premium adds cloud saving so you can continue your plan across browsers and devices.</p>
                        </div>
                        <p><a class="primary-action" href="<?php echo htmlspecialchars($phase2Url); ?>">Continue in This Browser to Phase 2</a></p>
                    </div>

                    <div class="transition-options" data-journey-anon-only>
                        <section class="transition-option" aria-labelledby="free-account-title">
                            <h2 id="free-account-title">Want to create a free account?</h2>
                            <p>Creating an account is optional. A free account lets you sign in to account services. All six phases are available without an account; free planning records remain in this browser.</p>
                            <ul class="coach-list">
                                <li>Create your free Retirement Planning Journey account</li>
                                <li>Continue through all six free Journey phases</li>
                                <li>Sign in later when you return in this browser</li>
                            </ul>
                            <a class="secondary-action" href="<?php echo htmlspecialchars($freeAccountUrl); ?>" data-journey-analytics-free-account-start>Create Free Account and Continue</a>
                            <p class="action-note">Registration opens on ronbelisle.com, then returns you to Phase 2 in this same browser. Your Journey plan currently stays in this browser. Creating the account does not yet automatically sync that browser-stored Journey data across devices.</p>
                        </section>

                        <section class="transition-option" aria-labelledby="premium-later-title">
                            <h2 id="premium-later-title">Journey Premium is optional</h2>
                            <p>All six Journey phases are free. Journey Premium is an optional ongoing planning workspace you can choose <strong>after</strong> you complete your initial plan.</p>
                            <ul class="coach-list">
                                <li>Revisit decisions and update assumptions over time</li>
                                <li>Update your saved assumptions as your life changes</li>
                                <li>Keep your plan current in an ongoing workspace</li>
                            </ul>
                            <p class="action-note">When your initial plan is complete, you can start a 30-day Journey Premium trial if you want that ongoing workspace. You do not need Premium to continue to Phase 2.</p>
                        </section>

                    </div>

                    <section class="transition-login-option" aria-labelledby="existing-account-title" data-journey-anon-only>
                        <h2 id="existing-account-title">Already have an account?</h2>
                        <p>Log in on ronbelisle.com, then return here in this browser to continue Phase 2 with your saved spending target.</p>
                        <a class="secondary-action" href="<?php echo htmlspecialchars($loginUrl); ?>">Log in and continue your Journey</a>
                    </section>

                    <div data-journey-free-auth-only hidden>
                        <section class="transition-option" aria-labelledby="premium-later-auth-title">
                            <h2 id="premium-later-auth-title">Journey Premium is optional</h2>
                            <p>All six Journey phases remain free. After you complete your initial plan, you can choose Journey Premium for cloud saving and an ongoing planning workspace.</p>
                        </section>
                    </div>

                    <p class="transition-back"><a href="/phases/spending-goals.php">← Back to Phase 1</a></p>
                </article>
            </div>
        </section>
    </main>
    <script>
    (function () {
        var phase2Url = <?php echo json_encode($phase2Url, JSON_UNESCAPED_SLASHES); ?>;
        function redirectIfPremium(status) {
            if (status && status.hasAccess) {
                window.location.replace(phase2Url);
            }
        }
        window.addEventListener('rb-journey-status', function (event) {
            redirectIfPremium(event.detail);
        });
        if (window.rbJourneySync && typeof window.rbJourneySync.getStatus === 'function') {
            redirectIfPremium(window.rbJourneySync.getStatus());
        }
        if (window.rbJourneySync && typeof window.rbJourneySync.afterReady === 'function') {
            window.rbJourneySync.afterReady(function () {
                redirectIfPremium(window.rbJourneySync.getStatus());
            });
        }
    })();
    </script>
    <?php include __DIR__ . '/../includes/site-footer.php'; ?>
</body>
</html>
