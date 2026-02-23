<!DOCTYPE html>
<html lang="en">
<head>
  <?php include("includes/analytics.php"); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About — How We Calculate | Ron Belisle Financial Calculators</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .about-content p { margin-bottom: 15px; color: #4a5568; line-height: 1.8; }
    .about-content ul { margin-left: 25px; margin-bottom: 15px; color: #4a5568; }
    .about-content li { margin-bottom: 10px; }
    .about-content strong { color: #2c5282; }
    .about-content .info-box-blue { margin-bottom: 25px; }
    .about-content .info-box-blue h2 { color: #2196F3; margin-top: 0; font-size: 22px; }
    .back-link {
      display: inline-block;
      margin-top: 30px;
      padding: 12px 24px;
      background: #3182ce;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background 0.2s;
    }
    .back-link:hover { background: #2c5282; }
  </style>
</head>
<body>
  <div class="wrap">
    <p style="margin-bottom: 20px;"><a href="index.php" style="text-decoration: none; color: #1d4ed8;">← Return to home page</a></p>

    <header>
      <h1>About</h1>
      <p class="sub">How we calculate — methodology and influences</p>
    </header>

    <div class="about-content">
      <div class="info-box-blue">
        <h2>What This Site Is</h2>
        <p>This site offers free and premium financial calculators for retirement planning and for building a solid financial foundation. The tools are built to be transparent, educational, and useful for exploring your own scenarios. They are not a substitute for professional advice.</p>
      </div>

      <div class="info-box-blue">
        <h2>How We Build Our Tools</h2>
        <p>We use <strong>standard, well-established concepts</strong> from retirement and financial planning. These ideas are part of the shared intellectual commons—used by researchers, planners, and other tools. We do not claim to have invented them. Examples include:</p>
        <ul>
          <li>Present and future value of money, annuities, and required payments</li>
          <li>Required Minimum Distribution (RMD) rules and tax implications</li>
          <li>Social Security benefit formulas and claiming-age tradeoffs</li>
          <li>Roth conversion tax treatment and multi-year planning</li>
          <li>Scenario analysis and what-if comparisons</li>
          <li>Safe withdrawal and sequence-of-returns concepts (where applicable)</li>
        </ul>
        <p>We <strong>implement these concepts ourselves</strong> with our own assumptions, design choices, and explanatory framing. Our code, user experience, and how we combine multiple calculators into one suite are original to this site.</p>
      </div>

      <div class="info-box-blue">
        <h2>Influences and Transparency</h2>
        <p>We are inspired by the broader ecosystem of retirement planning—academic work, practitioner tools, and the many people who have contributed to how we think about savings, Social Security, taxes, and spending in retirement. We do not copy any one product’s code, interface, or proprietary methods. If we add methods such as Monte Carlo simulation or lifetime tax projection in the future, we will continue to use published approaches and our own implementation.</p>
        <p>You are not required to take our word for it: our calculators are designed so you can see the inputs and outputs and use them to inform conversations with qualified professionals.</p>
      </div>

      <div class="info-box-blue">
        <h2>Disclaimer</h2>
        <p>Results from these calculators are estimates based on the information you provide and assumptions about future conditions. For the full legal disclaimer, see <a href="disclaimer.php" style="color: #3182ce; font-weight: 600;">Disclaimer</a>.</p>
      </div>

      <div style="text-align: center;">
        <a href="index.php" class="back-link">← Back to Calculators</a>
      </div>
    </div>
  </div>

  <?php include("includes/footer_simple.php"); ?>
</body>
</html>
