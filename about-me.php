<?php
session_start();
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
  <title>About RB – Ron Belisle Financial Calculators</title>
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
      <p>My name is Ron B. I’m not with a big fintech company—just a retired university teacher who saw a need for straightforward, no-nonsense online retirement tools, so I decided to build some myself. My background isn’t technical; I have a BA in History and an MA in Applied Linguistics. Before teaching, I was a life insurance agent with Northwestern Mutual Life, and that experience taught me a lot about long-term planning.</p>
      <p>When teaching others about tech and financial matters, I have a reputation for using simple, easy-to-understand English. That was one of my goals in creating these calculators: plain English and useful tools—all free. Even though the core tools are free, Premium adds saving scenarios, PDF exports, and longer projections if you want them. I hope you find them useful.</p>
    </div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
