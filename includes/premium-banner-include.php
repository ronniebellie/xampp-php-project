<!-- Premium Banner Component -->
<style>
.premium-banner {
    background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
    color: white;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    border-radius: 8px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.premium-banner h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
    font-weight: 600;
}

.premium-banner p {
    margin: 0 0 15px 0;
    font-size: 16px;
    opacity: 0.95;
    line-height: 1.5;
}

.premium-banner.coming-soon {
    background: linear-gradient(135deg, #805ad5 0%, #9f7aea 100%);
}

.premium-banner.premium-active {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

@media (max-width: 768px) {
    .premium-banner h3 {
        font-size: 20px;
    }
    .premium-banner p {
        font-size: 14px;
    }
}
</style>

<?php if (isset($isPremium) && $isPremium): ?>
<!-- Premium User - Show active status -->
<div class="premium-banner premium-active">
    <h3>✓ Premium Active</h3>
    <p>You have full access to all premium features including save scenarios, PDF exports, and advanced projections.</p>
</div>
<?php else: ?>
<!-- Free User - Invite to premium -->
<div class="premium-banner coming-soon">
    <h3>✨ Premium Features Available</h3>
    <p>Save and compare scenarios, export PDF and CSV reports, and access advanced projections. <a href="<?php echo (isset($isLoggedIn) && $isLoggedIn) ? '/subscribe.php' : '/auth/register.php'; ?>" style="color: white; text-decoration: underline; font-weight: 600;"><?php echo (isset($isLoggedIn) && $isLoggedIn) ? 'Upgrade' : 'Sign up'; ?></a> to unlock. Free tools remain free forever.</p>
</div>
<?php endif; ?>