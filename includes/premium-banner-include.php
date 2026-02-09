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

.premium-banner-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.premium-banner-btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 16px;
    cursor: pointer;
}

.premium-banner-btn.primary {
    background: #48bb78;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.premium-banner-btn.primary:hover {
    background: #38a169;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

.premium-banner-btn.secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.premium-banner-btn.secondary:hover {
    background: rgba(255,255,255,0.1);
}

/* Coming Soon version */
.premium-banner.coming-soon {
    background: linear-gradient(135deg, #805ad5 0%, #9f7aea 100%);
}

.premium-banner.coming-soon .premium-banner-buttons {
    display: none;
}

@media (max-width: 768px) {
    .premium-banner h3 {
        font-size: 20px;
    }
    
    .premium-banner p {
        font-size: 14px;
    }
    
    .premium-banner-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .premium-banner-btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<!-- DEPLOYMENT VERSION - Coming Soon (no clickable buttons) -->
<div class="premium-banner coming-soon">
    <h3>🎯 New: Premium Features Available Soon</h3>
    <p>Save your scenarios, export professional reports, and unlock advanced projections. Free tools remain free forever.</p>
</div>