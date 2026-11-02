<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calendar · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/collaboration.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard"><span class="brand-mark small">T</span> TEAMFLOW</a>
        <p class="side-label">WORKSPACE</p>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard">Overview</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">My tasks</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a>
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar">Calendar</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Notifications</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports">Reports</a>
        <?php if ($canManageUsers): ?><a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users">People</a><?php endif; ?>
        <?php if ($canManageRoles): ?><a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/roles">Roles & permissions</a><?php endif; ?>
        <div class="sidebar-bottom">
            <div class="user-chip"><span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></span><div><strong><?= e($name) ?></strong><small><?= e($role) ?></small></div></div>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/logout">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <main class="dashboard-main">
        <header class="topbar">
            <div><span class="eyebrow">PROJECT AND TASK DEADLINES</span><h1>Calendar</h1></div>
            <span class="role-pill"><?= count($events) ?> deadlines</span>
        </header>
        <section class="panel calendar-panel">
            <div class="calendar-heading">
                <a class="button-secondary" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar?month=<?= e($previousMonth) ?>">← Previous</a>
                <h2><?= e($firstDay->format('F Y')) ?></h2>
                <a class="button-secondary" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar?month=<?= e($nextMonth) ?>">Next →</a>
            </div>
            <div class="calendar-grid">
                <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday): ?><div class="calendar-weekday"><?= e($weekday) ?></div><?php endforeach; ?>
                <?php foreach ($weeks as $week): ?>
                    <?php foreach ($week as $day): ?>
                        <?php $date = $day->format('Y-m-d'); $dayEvents = $eventsByDate[$date] ?? []; ?>
                        <div class="calendar-day <?= $day->format('m') !== $firstDay->format('m') ? 'calendar-day-outside' : '' ?> <?= $date === date('Y-m-d') ? 'calendar-day-today' : '' ?>">
                            <span class="calendar-day-number"><?= $day->format('j') ?></span>
                            <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                                <?php $eventUrl = $event['event_type'] === 'project'
                                    ? rtrim($_ENV['APP_URL'] ?? '', '/') . '/projects/view?project_id=' . (int) $event['project_id']
                                    : rtrim($_ENV['APP_URL'] ?? '', '/') . '/projects/view?project_id=' . (int) $event['project_id'] . '#task-' . (int) $event['task_id']; ?>
                                <a class="calendar-event calendar-event-<?= e($event['event_type']) ?>" href="<?= e($eventUrl) ?>" title="<?= e($event['project_key'] . ' · ' . $event['title']) ?>"><span><?= e($event['event_type'] === 'project' ? 'Project' : 'Task') ?></span><?= e($event['title']) ?></a>
                            <?php endforeach; ?>
                            <?php if (count($dayEvents) > 3): ?><span class="calendar-more">+<?= count($dayEvents) - 3 ?> more</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!$events): ?><p class="calendar-empty">No project or task deadlines this month.</p><?php endif; ?>
        </section>
    </main>
</body>
</html>
