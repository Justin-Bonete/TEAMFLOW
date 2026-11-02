<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090c14">
    <title>Dashboard · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-page dark-dashboard">
    <header class="app-topbar">
        <a class="app-brand" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard">
            <span class="brand-mark small">T</span><span>TEAMFLOW</span>
        </a>
        <div class="topbar-context"><span>Workspace</span><span aria-hidden="true">/</span><strong>Overview</strong></div>
        <div class="topbar-actions">
            <a class="topbar-icon" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications" aria-label="Notifications">Notifications<?php if ($unreadCount): ?><span class="topbar-badge"><?= (int) $unreadCount ?></span><?php endif; ?></a>
            <span class="topbar-user"><span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></span><span><?= e($name) ?></span></span>
        </div>
    </header>
    <div class="dashboard-workspace">
        <aside class="sidebar">
            <p class="side-label">WORKSPACE</p>
            <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard"><span class="nav-glyph" aria-hidden="true">▦</span>Dashboard</a>
            <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects"><span class="nav-glyph" aria-hidden="true">◇</span>Projects</a>
            <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks"><span class="nav-glyph" aria-hidden="true">✓</span>My tasks</a>
            <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar"><span class="nav-glyph" aria-hidden="true">▤</span>Calendar</a>
            <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports"><span class="nav-glyph" aria-hidden="true">▥</span>Reports</a>
            <?php if ($canManageUsers): ?><a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users"><span class="nav-glyph" aria-hidden="true">♧</span>People</a><?php endif; ?>
            <?php if ($canManageRoles): ?><a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/roles"><span class="nav-glyph" aria-hidden="true">⚙</span>Roles & permissions</a><?php endif; ?>
            <div class="sidebar-bottom">
                <div class="sidebar-user"><span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></span><span><strong><?= e($name) ?></strong><small><?= e($role) ?></small></span></div>
                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/logout">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <button class="logout-button" type="submit">Sign out</button>
                </form>
            </div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-heading">
                <div><p class="breadcrumb">Workspace <span>/</span> Dashboard</p><h1>Good to have you here, <?= e($name) ?>.</h1><p class="dashboard-subtitle">A clear view of your projects, tasks, and team activity.</p></div>
                <a class="button-primary dashboard-primary-action" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">View my tasks <span>→</span></a>
            </header>

            <section class="dashboard-layout">
                <div class="dashboard-primary-column">
                    <section class="dashboard-panel board-panel">
                        <div class="dashboard-panel-heading"><div><p class="panel-kicker">YOUR WORKFLOW</p><h2>My task board</h2></div><a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">View all tasks <span aria-hidden="true">↗</span></a></div>
                        <div class="dashboard-board" data-kanban-board>
                            <?php foreach (['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'] as $status => $label): ?>
                                <?php $columnTasks = $dashboardBoard[$status]; ?>
                                <section class="dashboard-board-column" data-kanban-column="<?= e($status) ?>">
                                        <h3><span class="column-indicator indicator-<?= e($status) ?>"></span><?= e($label) ?><span class="column-count"><?= (int) $dashboardBoardCounts[$status] ?></span></h3>
                                    <div class="dashboard-board-cards">
                                        <?php if (!$columnTasks): ?><p class="board-empty">Nothing here yet</p><?php endif; ?>
                                        <?php foreach ($columnTasks as $task): ?>
                                            <?php $canChangeStatus = $canUpdateOwnTasks && !in_array($task['project_status'], ['completed', 'archived'], true); ?>
                                            <article class="dashboard-task-card" data-kanban-card="<?= (int) $task['id'] ?>" draggable="<?= $canChangeStatus ? 'true' : 'false' ?>">
                                                <a class="dashboard-task-title" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $task['project_id'] ?>#task-<?= (int) $task['id'] ?>"><?= e($task['title']) ?></a>
                                                <span class="task-project-label"><?= e($task['project_key']) ?> · <?= e($task['project_name']) ?></span>
                                                <div class="dashboard-task-meta"><span class="task-priority priority-<?= e($task['priority']) ?>"><?= e(ucfirst($task['priority'])) ?></span><?php if ($task['due_date']): ?><time datetime="<?= e($task['due_date']) ?>">Due <?= e(date('M j', strtotime($task['due_date']))) ?></time><?php endif; ?></div>
                                                <?php if ($canChangeStatus): ?>
                                                    <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/status" class="dashboard-status-form" data-kanban-status-form>
                                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                                        <label class="sr-only" for="dashboard-status-<?= (int) $task['id'] ?>">Change task status</label>
                                                        <select id="dashboard-status-<?= (int) $task['id'] ?>" name="status"><?php foreach (['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'] as $value => $statusLabel): ?><option value="<?= e($value) ?>" <?= $task['status'] === $value ? 'selected' : '' ?>><?= e($statusLabel) ?></option><?php endforeach; ?></select>
                                                        <button type="submit" aria-label="Save status for <?= e($task['title']) ?>">↗</button>
                                                    </form>
                                                <?php endif; ?>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="dashboard-panel workload-panel">
                        <div class="dashboard-panel-heading"><div><p class="panel-kicker">DELIVERY</p><h2>Project progress</h2></div><a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports">Full report <span aria-hidden="true">↗</span></a></div>
                        <?php if (!$dashboardProjects): ?>
                            <p class="dashboard-empty">Projects you can access will appear here.</p>
                        <?php else: ?>
                            <div class="project-progress-list">
                                <?php foreach ($dashboardProjects as $project): ?>
                                    <?php $progress = min(100, max(0, (float) $project['completion_rate'])); ?>
                                    <a class="project-progress-row" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $project['id'] ?>">
                                        <span class="project-progress-name"><strong><?= e($project['name']) ?></strong><small><?= e($project['project_key']) ?> · <?= e(str_replace('_', ' ', ucfirst($project['status']))) ?></small></span>
                                        <progress class="project-progress-track" aria-label="<?= e($project['name']) ?> completion" max="100" value="<?= e((string) $progress) ?>"><?= e((string) $progress) ?>%</progress>
                                        <span class="project-progress-value"><?= e((string) $project['completion_rate']) ?>%</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="dashboard-rail">
                    <section class="dashboard-panel status-panel">
                        <div class="dashboard-panel-heading"><div><p class="panel-kicker">OVERVIEW</p><h2>Task status</h2></div><span class="panel-menu" aria-hidden="true">···</span></div>
                        <?php
                            $totalTasks = (int) $summary['task_count'];
                            $completionPercent = $totalTasks > 0 ? round((int) $summary['completed_tasks'] * 100 / $totalTasks, 1) : 0;
                            $inProgressPercent = $totalTasks > 0 ? round((int) $summary['in_progress_tasks'] * 100 / $totalTasks, 1) : 0;
                            $todoPercent = max(0, 100 - $completionPercent - $inProgressPercent);
                        ?>
                        <?php $donutCircumference = round(2 * M_PI * 48, 2); $doneArc = round($donutCircumference * $completionPercent / 100, 2); $progressArc = round($donutCircumference * $inProgressPercent / 100, 2); ?>
                        <div class="status-chart-row">
                            <div class="status-donut <?= $totalTasks === 0 ? 'status-donut-empty' : '' ?>" role="img" aria-label="<?= (int) $summary['completed_tasks'] ?> of <?= $totalTasks ?> tasks complete">
                                <svg viewBox="0 0 120 120" aria-hidden="true">
                                    <circle class="status-donut-track" cx="60" cy="60" r="48"></circle>
                                    <circle class="status-donut-progress" cx="60" cy="60" r="48" stroke-dasharray="<?= e((string) $progressArc) ?> <?= e((string) $donutCircumference) ?>" stroke-dashoffset="<?= e((string) -$doneArc) ?>"></circle>
                                    <circle class="status-donut-done" cx="60" cy="60" r="48" stroke-dasharray="<?= e((string) $doneArc) ?> <?= e((string) $donutCircumference) ?>"></circle>
                                </svg>
                                <span><strong><?= $totalTasks ?></strong><small>tasks</small></span>
                            </div>
                            <div class="status-legend">
                                <span><i class="legend-dot legend-done"></i>Done<strong><?= (int) $summary['completed_tasks'] ?></strong></span>
                                <span><i class="legend-dot legend-progress"></i>In progress<strong><?= (int) $summary['in_progress_tasks'] ?></strong></span>
                                <span><i class="legend-dot legend-todo"></i>To do<strong><?= (int) $summary['todo_tasks'] ?></strong></span>
                            </div>
                        </div>
                        <div class="status-footnote"><span>Completion rate</span><strong><?= e((string) $summary['completion_rate']) ?>%</strong></div>
                        <span class="sr-only"><?= e((string) $todoPercent) ?>% of tasks are to do.</span>
                    </section>

                    <section class="dashboard-panel notification-panel">
                        <div class="dashboard-panel-heading"><div><p class="panel-kicker">UPDATES</p><h2>Recent notifications</h2></div><a class="panel-menu" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications" aria-label="All notifications">···</a></div>
                        <?php if (!$recentNotifications): ?>
                            <p class="dashboard-empty">You're all caught up. New project updates will show here.</p>
                        <?php else: ?>
                            <ul class="dashboard-notifications">
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <?php $notificationIcon = str_contains((string) $notification['type'], 'comment') ? '✎' : (str_contains((string) $notification['type'], 'attachment') ? '↥' : '•'); ?>
                                    <li class="<?= $notification['read_at'] ? '' : 'notification-is-unread' ?>">
                                        <span class="notification-symbol" aria-hidden="true"><?= e($notificationIcon) ?></span>
                                        <span class="notification-copy"><strong><?= e($notification['title']) ?></strong><small><?= e($notification['message'] ?? '') ?></small><time datetime="<?= e($notification['created_at']) ?>"><?= e(date('M j · g:i a', strtotime($notification['created_at']))) ?></time></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <a class="notification-all-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Open notification center <span aria-hidden="true">→</span></a>
                    </section>

                    <section class="dashboard-panel priority-panel">
                        <p class="panel-kicker">NEEDS ATTENTION</p>
                        <div class="priority-count"><?= (int) $summary['overdue_tasks'] ?><span>overdue tasks</span></div>
                        <a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports">Review overdue work <span aria-hidden="true">↗</span></a>
                    </section>
                </aside>
            </section>

            <section class="development-status"><span class="panel-kicker">DEVELOPMENT STATUS</span><strong>Phase 6 — Security and deployment</strong><span>Analytics and reporting are live. Production hardening and deployment readiness are underway.</span></section>
        </main>
    </div>
    <script src="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/js/kanban.js" defer></script>
</body>
</html>
