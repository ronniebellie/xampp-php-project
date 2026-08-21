<?php
if (!function_exists('get_premium_upsell_url')) require_once __DIR__ . '/has_premium_access.php';
$premium_upsell_headline = isset($premium_upsell_headline) ? $premium_upsell_headline : 'See what Premium adds to this plan';
$premium_upsell_text = isset($premium_upsell_text) ? $premium_upsell_text : 'Turn this result into a decision you can revisit: compare another path, save the scenario, export a report, and get a plain-English explanation of your specific numbers.';
if (!isset($premium_upsell_link)) {
    $premium_upsell_link = get_premium_upsell_url((isset($isLoggedIn) && $isLoggedIn) || !empty($_SESSION['calcforadvisors_subscriber_id']));
}
if (!isset($premium_upsell_link) || $premium_upsell_link === '') $premium_upsell_link = '/premium.html';
$premium_pricing_blurb = get_premium_pricing_blurb();
echo '<div class="premium-upsell-banner" style="margin-top: 24px; margin-bottom: 24px; padding: 28px; background: linear-gradient(135deg, #173f8a 0%, #1d4ed8 68%, #2563eb 100%); color: #ffffff; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,.18);">';
echo '<h3 style="margin: 0 0 12px 0; font-size: 1.25rem; color: #ffffff; font-weight: 600;">🔒 ' . htmlspecialchars($premium_upsell_headline) . '</h3>';
echo '<p style="margin: 0 0 8px 0; opacity: 0.95; font-size: 0.95rem; color: #ffffff;">' . htmlspecialchars($premium_upsell_text) . '</p>';
echo '<p style="margin: 0 0 16px 0; opacity: 0.9; font-size: 0.875rem; color: #ffffff;">' . htmlspecialchars($premium_pricing_blurb) . ' <a href="/premium.html#pricing" style="color: #ffffff; text-decoration: underline;">See pricing</a></p>';
echo '<a href="' . htmlspecialchars($premium_upsell_link) . '" data-rb-event="premium_upsell_click" data-rb-param-location="calculator_banner" data-rb-param-page="' . htmlspecialchars($premium_upsell_headline) . '" style="display: inline-block; background: white; color: #173f8a; padding: 13px 30px; border-radius: 10px; text-decoration: none; font-weight: 800;">See Calculator Premium</a>';
echo '</div>';
