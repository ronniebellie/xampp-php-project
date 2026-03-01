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
      <p>My name is Ron B. I'm not backed by a big fintech company—I'm a retired university teacher who saw a need for straightforward, no-nonsense retirement tools and decided to build them myself.</p>
      <p>My degrees are in History (BA) and Applied Linguistics (MA), both from Washington State University—not computer science. But I bring something more relevant to this project: four decades of explaining complex topics in plain English, plus real-world experience in financial planning.</p>
      <p>I spent most of my 40-year teaching career at a Japanese university, some in Japan, but primarily at their branch campus in Washington state. Before that, I spent six years as a life insurance agent with Northwestern Mutual Life, starting on a college agent's contract at WSU. In 1983, I ranked in the top 10 in national sales among all Northwestern Mutual college agents—out of roughly 300 to 400. That experience gave me a lasting foundation in long-term financial planning and in helping people think clearly about their goals.</p>
      <p>Throughout my career, I've built a reputation for making complicated things simple. That's exactly what I set out to do with these calculators: useful tools, plain English, no jargon—and free to use. A Premium option adds the ability to save scenarios, export PDFs, and run longer projections, but the core tools cost nothing. I hope you find them valuable.</p>
    </div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
