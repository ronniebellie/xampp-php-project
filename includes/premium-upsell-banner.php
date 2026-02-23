<?php
// Premium upsell banner (gradient, lock, CTA). Include inside <?php if (!$isPremium): ?> block.
// Optional: $premium_upsell_headline, $premium_upsell_text (defaults below).
$premium_upsell_headline = isset($premium_upsell_headline) ? $premium_upsell_headline : 'See Your Complete Retirement Timeline';
$premium_upsell_text = isset($premium_upsell_text) ? $premium_upsell_text : 'Upgrade to Premium to see year-by-year projections from age 73 to 100, plus save unlimited scenarios.';
?>
<div class="premium-upsell-banner" style="margin-top: 24px; margin-bottom: 24px; padding: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); background-color: #667eea; color: #ffffff; border-radius: 12px; text-align: center;">
    <h3 style="margin: 0 0 12px 0; font-size: 1.25rem; color: #ffffff; font-weight: 600;">🔒 <?php echo htmlspecialchars($premium_upsell_headline); ?></h3>
    <p style="margin: 0 0 16px 0; opacity: 0.95; font-size: 0.95rem; color: #ffffff;"><?php echo htmlspecialchars($premium_upsell_text); ?></p>
    <a href="../premium.html" style="display: inline-block; background: white; color: #667eea; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700;">Upgrade to Premium</a>
</div>
