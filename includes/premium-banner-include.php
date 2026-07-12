<!-- Premium Banner Component -->
<style>
.premium-banner-shell {
    max-width: 980px;
    margin: 0 auto 30px;
    padding: 0 18px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.premium-banner {
    background: linear-gradient(135deg, #2c5282 0%, #3182ce 100%);
    color: white;
    padding: 24px 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.premium-banner-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.premium-banner-text {
    flex: 1;
    min-width: 0;
}

.premium-banner h3 {
    margin: 0 0 8px 0;
    font-size: 22px;
    font-weight: 700;
}

.premium-banner p {
    margin: 0;
    font-size: 15px;
    opacity: 0.95;
    line-height: 1.5;
}

.premium-banner-pricing {
    margin-top: 8px;
    font-size: 14px;
    font-weight: 600;
}

.premium-banner-secondary {
    margin-top: 10px;
    font-size: 14px;
}

.premium-banner-secondary a {
    color: white;
    text-decoration: underline;
    font-weight: 600;
}

.premium-banner-reassurance {
    margin-top: 6px;
    font-size: 13px;
    opacity: 0.85;
}

.premium-banner-cta {
    display: inline-block;
    background: white;
    color: #553c9a;
    padding: 12px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.2s, box-shadow 0.2s;
    flex-shrink: 0;
}

.premium-banner-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

.premium-banner.coming-soon {
    background: linear-gradient(135deg, #805ad5 0%, #9f7aea 100%);
}

.premium-banner.coming-soon .premium-banner-cta {
    color: #553c9a;
}

.premium-banner.premium-active {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.premium-banner.premium-active .premium-banner-cta {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid white;
    box-shadow: none;
}

.premium-banner.premium-active .premium-banner-cta:hover {
    background: rgba(255,255,255,0.3);
}

@media (max-width: 768px) {
    .premium-banner-shell {
        padding: 0 14px;
        margin-bottom: 24px;
    }

    .premium-banner {
        padding: 20px 24px;
    }

    .premium-banner-content {
        flex-direction: column;
        align-items: stretch;
    }

    .premium-banner h3 {
        font-size: 20px;
    }

    .premium-banner p,
    .premium-banner-secondary {
        font-size: 14px;
    }

    .premium-banner-cta {
        width: 100%;
        text-align: center;
        white-space: normal;
    }
}
</style>

<?php
if (!function_exists('get_premium_upsell_url')) {
    require_once __DIR__ . '/has_premium_access.php';
}
$premiumUpsellUrl = get_premium_upsell_url(isset($isLoggedIn) && $isLoggedIn);
$premiumPricingBlurb = get_premium_pricing_blurb();
?>
<?php
$isEmbed = isset($_GET['embed']) && $_GET['embed'];
if ($isEmbed) {
    // Don't show Premium banner when embedded in white-label trial/demo
    echo '<!-- Premium banner hidden in embed mode -->';
} elseif (isset($isPremium) && $isPremium) {
    $back_link_premium_handled = true;
?>
<!-- Premium User - Show active status -->
<div class="premium-banner-shell">
    <div class="premium-banner premium-active">
        <div class="premium-banner-content">
            <div class="premium-banner-text">
                <h3>✓ Premium Active</h3>
                <p>You have full access to all Premium features across the site.</p>
            </div>
            <a href="/account.php" class="premium-banner-cta">Manage Account</a>
        </div>
    </div>
</div>
<?php } else {
    $back_link_premium_handled = true;
?>
<!-- Free User - Invite to premium -->
<div class="premium-banner-shell">
    <div class="premium-banner coming-soon">
        <div class="premium-banner-content">
            <div class="premium-banner-text">
                <h3>✨ Premium Features Available</h3>
                <p>Save and compare scenarios, export PDF and CSV reports, AI-generated plain-language explanations of your specific results, and advanced projections.</p>
                <p class="premium-banner-pricing"><?php echo htmlspecialchars($premiumPricingBlurb); ?></p>
                <p class="premium-banner-secondary">
                    <a href="<?php echo htmlspecialchars($premiumUpsellUrl); ?>" data-rb-event="premium_upsell_click" data-rb-param-location="premium_banner" data-rb-param-cta="learn">Learn about Premium</a>
                    ·
                    <a href="/premium.html#pricing" data-rb-event="premium_upsell_click" data-rb-param-location="premium_banner" data-rb-param-cta="pricing">Pricing</a>
                </p>
                <p class="premium-banner-reassurance">Free tools remain free forever.</p>
            </div>
            <a href="/subscribe.php" class="premium-banner-cta" data-rb-event="premium_upsell_click" data-rb-param-location="premium_banner" data-rb-param-cta="trial">Try Premium Free for 7 Days</a>
        </div>
    </div>
</div>
<?php } ?>
