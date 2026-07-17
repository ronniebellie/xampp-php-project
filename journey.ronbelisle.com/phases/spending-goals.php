<?php
$active_phase = 'spending-goals';
$page_title = 'Spending & Goals | Retirement Planning Journey';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/journey.css">
</head>
<body>
    <header class="site-header">
        <a class="site-brand" href="/" aria-label="Retirement Planning Journey home">
            <span class="brand-mark" aria-hidden="true">RB</span>
            <span>Retirement Planning Journey</span>
        </a>
    </header>

    <main>
        <section class="page-hero" aria-labelledby="phase-title">
            <div class="container page-grid">
                <div>
                    <p class="eyebrow">Phase 1</p>
                    <h1 id="phase-title">Spending &amp; Goals</h1>
                    <p class="page-lede">A retirement plan starts with the life you want it to support. This first step helps you frame the spending needs, tradeoffs, and priorities that should guide every later decision.</p>
                </div>

                <?php include __DIR__ . '/../includes/progress-nav.php'; ?>
            </div>
        </section>

        <section class="content-section">
            <div class="container content-grid">
                <article class="planning-panel">
                    <h2>Start with the retirement you are trying to fund.</h2>
                    <p>Before choosing a Social Security strategy or testing investment returns, it helps to define the spending target. Think about essentials, flexible lifestyle spending, major one-time costs, and the goals that matter most.</p>
                    <div class="prompt-list" aria-label="Spending and goals prompts">
                        <div>
                            <h3>Core spending</h3>
                            <p>Housing, food, transportation, insurance, healthcare, taxes, and other baseline costs.</p>
                        </div>
                        <div>
                            <h3>Lifestyle choices</h3>
                            <p>Travel, hobbies, family support, charitable giving, and other flexible priorities.</p>
                        </div>
                        <div>
                            <h3>Planning guardrails</h3>
                            <p>How much uncertainty you can tolerate, and where you would adjust if conditions change.</p>
                        </div>
                    </div>
                </article>

                <aside class="next-step-card" aria-labelledby="next-step-title">
                    <p class="eyebrow">Recommended Next Step</p>
                    <h2 id="next-step-title">Social Security</h2>
                    <p>Once your spending target is clear, the next major decision is when and how to claim Social Security benefits.</p>
                    <a class="secondary-action" href="/#social-security">Preview the next phase</a>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>
