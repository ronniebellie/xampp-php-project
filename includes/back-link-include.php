<?php
require_once __DIR__ . '/embed_mode.php';
if (rb_is_embed_mode()) return;

$backHref = isset($back_link_default_href) ? $back_link_default_href : '../';
$backText = isset($back_link_default_text) ? $back_link_default_text : '← Return to home page';
if (!empty($_GET['return_url']) && ($safeReturnUrl = rb_safe_calculator_return_url((string) $_GET['return_url']))) {
    $backHref = $safeReturnUrl;
    $backText = '← Return to home page';
}

$backLinkRightHref = null;
$backLinkRightText = null;
if (empty($back_link_premium_handled)) {
    if (isset($isPremium) && $isPremium) {
        $backLinkRightHref = '/account.php';
        $backLinkRightText = 'Manage account';
    } else {
        $backLinkRightHref = '/premium.html';
        $backLinkRightText = 'Premium';
    }
}
?>
<p style="margin-bottom: 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
  <a href="<?php echo htmlspecialchars($backHref); ?>" style="text-decoration: none; color: #1d4ed8;"><?php echo htmlspecialchars($backText); ?></a>
  <?php if ($backLinkRightHref): ?>
  <a href="<?php echo htmlspecialchars($backLinkRightHref); ?>" style="text-decoration: none; color: #1d4ed8; font-weight: 600;"><?php echo htmlspecialchars($backLinkRightText); ?></a>
  <?php endif; ?>
</p>
