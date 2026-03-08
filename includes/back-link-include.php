<?php
$backHref = isset($back_link_default_href) ? $back_link_default_href : '../';
$backText = isset($back_link_default_text) ? $back_link_default_text : '← Return to home page';
if (!empty($_GET['return_url']) && preg_match('#^https?://#', $_GET['return_url'])) {
    $backHref = $_GET['return_url'];
    $backText = '← Return to home page';
}
?>
<p style="margin-bottom: 20px;"><a href="<?php echo htmlspecialchars($backHref); ?>" style="text-decoration: none; color: #1d4ed8;"><?php echo htmlspecialchars($backText); ?></a></p>
