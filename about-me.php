<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/includes/db_config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$is_premium = false;
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sub = null;
    $stmt->bind_result($sub);
    $user = $stmt->fetch() ? ['subscription_status' => $sub] : null;
    $stmt->close();
    $is_premium = ($user && $user['subscription_status'] === 'premium');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/analytics.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="About Ron Belisle. Financial calculators for retirement planning, Social Security, Roth conversions, and more.">
  <title>About RB – Ron Belisle Financial Calculators</title>
  <?php $og_title = 'About RB – Ron Belisle Financial Calculators'; $og_description = 'About Ron Belisle. Financial calculators for retirement planning, Social Security, Roth conversions, and more.'; include(__DIR__ . '/includes/og-twitter-meta.php'); ?>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .about-wrap { max-width: 680px; margin: 0 auto; padding: 28px 18px 40px; }
    .about-wrap h1 { font-size: 28px; margin: 0 0 24px; letter-spacing: -0.02em; }
    .about-wrap .prose { font-size: 17px; line-height: 1.65; color: #334155; }
    .about-wrap .prose p { margin: 0 0 1em; }
    .about-wrap .prose p:last-child { margin-bottom: 0; }
    .about-wrap .back { display: inline-block; margin-bottom: 20px; color: #1d4ed8; text-decoration: none; font-weight: 600; }
    .about-wrap .back:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="about-wrap">
    <p style="margin-bottom: 20px;"><a href="/" class="back">← Return to home page</a></p>
    <h1>About RB</h1>
    <div class="prose">
      <p>My name is Ron B. I'm not backed by a fintech company, just a (sort-of) retired university teacher who saw a need for straightforward, no-nonsense retirement tools and decided to build them myself.</p>
      <p>My degrees are in History (BA) and Applied Linguistics (MA), both from Washington State University, not computer science. But I bring something more relevant to this project: four decades of explaining complex topics in plain English, real-world experience in financial planning, and a practical understanding of how AI can help people make better-informed decisions.</p>
      <p>I spent most of my 40-year teaching career with Mukogawa Women's University, a Japanese university near Kobe, teaching some in Japan but mostly at its U.S. branch campus in Washington state. I retired in 2024, and this fall I'm returning to teach an online course on generative AI for the university's business majors, helping students learn to use AI thoughtfully and responsibly.</p>
      <p>Before teaching, I spent six years as a life insurance agent with Northwestern Mutual Life, beginning on a college agent contract while attending Washington State University. In 1983, I ranked among the top 10 college agents nationwide, out of roughly 300 to 400. That experience gave me a lasting foundation in long-term financial planning and in helping people think clearly about their goals.</p>
      <p>Throughout my career, from financial planning to university teaching to AI, I've built a reputation for making complicated things simple. That's exactly what I set out to do with these calculators: practical tools, plain English, no jargon, and free to use.</p>
      <p>For those who want to go deeper, <strong><a href="https://ronbelisle.com/premium.html">Premium</a></strong> adds features like saved scenarios, PDF and CSV exports, longer projections where supported, and <strong>AI-generated explanations</strong> tailored to <em>your</em> results. My goal isn't simply to show you numbers. It's to help you understand what they mean so you can make more confident retirement decisions. The core tools will always remain free, and I hope you find them valuable.</p>
    </div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
