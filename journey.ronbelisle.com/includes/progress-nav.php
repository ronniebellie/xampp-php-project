<?php
$journey_phases = [
    [
        'key' => 'spending-goals',
        'number' => '1',
        'title' => 'Spending & Goals',
        'href' => '/phases/spending-goals.php',
        'status' => 'available',
    ],
    [
        'key' => 'social-security',
        'number' => '2',
        'title' => 'Social Security',
        'href' => '/phases/social-security.php',
        'status' => 'available',
    ],
    [
        'key' => 'build-your-plan',
        'number' => '3',
        'title' => 'Build Your Plan',
        'href' => '/phases/build-your-plan.php',
        'status' => 'available',
    ],
    [
        'key' => 'stress-test',
        'number' => '4',
        'title' => 'Stress Test',
        'href' => '/phases/stress-test.php',
        'status' => 'available',
    ],
    [
        'key' => 'tax-strategy',
        'number' => '5',
        'title' => 'Tax Strategy',
        'href' => '/phases/tax-strategy.php',
        'status' => 'available',
    ],
    [
        'key' => 'survivor-planning',
        'number' => '6',
        'title' => 'Survivor Planning',
        'href' => '/phases/survivor-planning.php',
        'status' => 'available',
    ],
];

$active_phase = $active_phase ?? '';
?>
<nav class="journey-progress" aria-label="Retirement planning journey progress">
    <ol>
        <?php foreach ($journey_phases as $phase): ?>
            <?php
            $is_active = $phase['key'] === $active_phase;
            $class_names = ['journey-step', 'is-' . $phase['status']];
            if ($is_active) {
                $class_names[] = 'is-active';
            }
            ?>
            <li class="<?php echo htmlspecialchars(implode(' ', $class_names)); ?>" data-journey-phase="<?php echo htmlspecialchars($phase['key']); ?>">
                <a href="<?php echo htmlspecialchars($phase['href']); ?>" <?php echo $is_active ? 'aria-current="step"' : ''; ?>>
                    <span class="step-number"><?php echo htmlspecialchars($phase['number']); ?></span>
                    <span class="step-label">
                        <span class="step-title"><?php echo htmlspecialchars($phase['title']); ?></span>
                        <span class="step-record-status" data-journey-record-status hidden></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<script src="/assets/js/journey-records.js?v=20260801-logged-in-polish" defer></script>
<script src="/assets/js/journey-progress.js?v=20260801-logged-in-polish" defer></script>
