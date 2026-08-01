<?php
/**
 * Lightweight Journey product feedback form.
 */
$active_phase = 'feedback';
$page_title = 'Need help or found a problem? | Retirement Planning Journey';

$from = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$phase = isset($_GET['phase']) ? trim((string) $_GET['phase']) : '';
if ($from !== '' && !preg_match('#^https?://(journey\.ronbelisle\.com|ronbelisle\.com|www\.ronbelisle\.com)#i', $from)) {
    $from = '';
}
if ($phase !== '' && !preg_match('/^[a-z0-9][a-z0-9_-]{0,62}$/', $phase)) {
    $phase = '';
}
$backHref = $from !== '' ? $from : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css?v=20260801-feedback">
</head>
<body>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="page-hero" aria-labelledby="feedback-title">
            <div class="container feedback-wrap">
                <div class="feedback-card">
                    <p class="eyebrow">Feedback</p>
                    <h1 id="feedback-title">Need help or found a problem?</h1>
                    <p class="page-lede">
                        If something isn't working as expected, or if you have a suggestion to improve the Retirement Planning Journey, we'd love to hear from you.
                    </p>

                    <div class="feedback-status" data-feedback-status hidden role="status"></div>

                    <form class="feedback-form" data-feedback-form novalidate>
                        <input type="hidden" name="page_url" value="<?php echo htmlspecialchars($from, ENT_QUOTES, 'UTF-8'); ?>" data-feedback-page-url>
                        <input type="hidden" name="journey_phase" value="<?php echo htmlspecialchars($phase, ENT_QUOTES, 'UTF-8'); ?>" data-feedback-phase>
                        <input type="hidden" name="csrf_token" value="" data-feedback-csrf>

                        <label class="feedback-label" for="feedback-email">Your email address <span class="feedback-optional">(optional)</span></label>
                        <input class="feedback-input" type="email" id="feedback-email" name="email" autocomplete="email" data-feedback-email>

                        <label class="feedback-label" for="feedback-trying">What were you trying to do?</label>
                        <textarea class="feedback-textarea" id="feedback-trying" name="trying_to_do" required rows="4" data-feedback-trying></textarea>

                        <label class="feedback-label" for="feedback-happened">What happened instead?</label>
                        <textarea class="feedback-textarea" id="feedback-happened" name="what_happened" required rows="4" data-feedback-happened></textarea>

                        <div class="feedback-actions">
                            <button class="primary-action" type="submit" data-feedback-submit>Send Feedback</button>
                            <a class="feedback-back" href="<?php echo htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8'); ?>">Back</a>
                        </div>
                    </form>

                    <div class="feedback-thanks" data-feedback-thanks hidden>
                        <p><strong>Thank you.</strong> Your feedback was sent. It helps us improve the Journey.</p>
                        <a class="primary-action" href="<?php echo htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8'); ?>">Return</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
    <script src="/assets/js/journey-feedback.js?v=20260801-feedback" defer></script>
</body>
</html>
