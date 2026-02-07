# RMD Impact Calculator Installation

## Quick Start

1. **Copy the `rmd-impact` folder** to your `htdocs` directory
2. **Add the CSS** from `CSS_ADDITIONS.css` to your shared `includes/styles.css`
3. **Update your homepage** to link to `/rmd-impact/`

## File Structure

After installation, your structure should look like:

```
htdocs/
├── rmd-impact/                    ← NEW FOLDER
│   ├── index.php                  ← Main calculator page
│   ├── calculator.js              ← Calculator logic
│   └── CSS_ADDITIONS.css          ← Copy these styles to includes/styles.css
├── includes/
│   ├── header.php                 ← Already exists (shared)
│   ├── footer.php                 ← Already exists (shared)
│   └── styles.css                 ← Add CSS_ADDITIONS.css content here
├── retirement-app/                ← Your other apps
├── social-security-claiming-analyzer/
├── ss-gap/
└── time-value-of-money/
```

## Installation Steps

### Step 1: Copy Folder
Copy the entire `rmd-impact` folder into your `htdocs` directory alongside your other app folders.

### Step 2: Add CSS to Shared Stylesheet
Open `CSS_ADDITIONS.css`, copy ALL the CSS code, and paste it at the end of your `includes/styles.css` file.

The CSS includes:
- Summary card grid and styling
- Chart section styling
- Blue info box for interpretation
- Data table styling
- Responsive mobile styles

### Step 3: Update Your Homepage
Add the RMD Impact calculator to your homepage where you currently have "Coming soon!":

```html
<div class="app-card">
    <h3>RMD Impact</h3>
    <p>Estimate how Required Minimum Distributions interact with your portfolio, taxes, and retirement income over time.</p>
    <a href="/rmd-impact/" class="btn">Open</a>
</div>
```

### Step 4: Test
Visit `http://localhost/rmd-impact/` (or your local development URL) to test the calculator.

## Features

✅ Uses your shared header.php and footer.php  
✅ Integrates with your shared styles.css  
✅ Matches design of your other calculators  
✅ Fully responsive mobile design  
✅ Interactive Chart.js visualizations  
✅ Personalized recommendations  

## Dependencies

- Chart.js (loaded via CDN in index.php)
- Your existing includes/header.php
- Your existing includes/footer.php
- Your existing includes/styles.css (with additions)

## Technical Details

- **RMD Calculations**: Uses IRS Uniform Lifetime Table
- **Tax Brackets**: 2026 estimated brackets (update annually)
- **Projections**: Shows data through age 100
- **Growth**: Accounts for portfolio growth between withdrawals

## Troubleshooting

**Calculator doesn't display properly?**
- Make sure you added CSS_ADDITIONS.css to your shared styles.css

**Page shows PHP errors?**
- Verify header.php and footer.php paths are correct
- Check that the includes folder is one level up (../)

**Chart doesn't show?**
- Verify Chart.js CDN is loading (check browser console)
- Make sure calculator.js is in the same folder as index.php

## Support

Questions? Check your other working apps for reference, they all use the same structure!
