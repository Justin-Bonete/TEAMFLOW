<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/reporting.css">
</head>
<body class="dashboard-page dark-dashboard">
    <?php $currentPage = 'reports'; require BASE_PATH . '/app/Views/layouts/workspace_start.php'; ?>
    <main class="dashboard-main">
        <header class="topbar">
            <div><span class="eyebrow">PROJECT AND TEAM INSIGHTS</span><h1>Analytics & reports</h1></div>
            <a class="button-secondary report-export" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports/export">Export project report · CSV</a>
        </header>
        <p class="report-scope">Metrics cover projects you can access. Project completion counts completed tasks, including subtasks.</p>
        <section class="stats-grid report-stats">
            <article class="stat-card"><span>Projects</span><strong><?= (int) $summary['project_count'] ?></strong><small><?= (int) $summary['active_projects'] ?> active</small></article>
            <article class="stat-card"><span>Tasks completed</span><strong><?= (int) $summary['completed_tasks'] ?> <small>of <?= (int) $summary['task_count'] ?></small></strong><small><?= e((string) $summary['completion_rate']) ?>% overall completion</small></article>
            <article class="stat-card"><span>Overdue tasks</span><strong><?= (int) $summary['overdue_tasks'] ?></strong><small>Uncompleted tasks past their due date</small></article>
            <article class="stat-card"><span>Open tasks</span><strong><?= (int) $summary['todo_tasks'] + (int) $summary['in_progress_tasks'] ?></strong><small><?= (int) $summary['todo_tasks'] ?> to do · <?= (int) $summary['in_progress_tasks'] ?> in progress</small></article>
        </section>

        <section class="report-section">
            <div class="section-heading"><div><span class="eyebrow">DELIVERY</span><h2>Project progress</h2></div></div>
            <?php if (!$projects): ?>
                <div class="panel empty-state">No projects are available for reporting yet.</div>
            <?php else: ?>
                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Tasks</th><th>Overdue</th><th>Team</th><th>Due</th></tr></thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <?php $progress = min(100, max(0, (float) $project['completion_rate'])); ?>
                                <tr>
                                    <td><a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $project['id'] ?>"><strong><?= e($project['name']) ?></strong><small><?= e($project['project_key']) ?> · Owner <?= e($project['owner_name']) ?></small></a></td>
                                    <td><span class="report-status status-<?= e($project['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($project['status']))) ?></span></td>
                                    <td class="progress-cell"><div class="progress-label"><span><?= e((string) $project['completion_rate']) ?>%</span><small><?= (int) $project['completed_tasks'] ?>/<?= (int) $project['task_count'] ?> tasks</small></div><progress class="progress-track" aria-label="<?= e($project['name']) ?> completion" max="100" value="<?= e((string) $progress) ?>"><?= e((string) $progress) ?>%</progress></td>
                                    <td><?= (int) $project['task_count'] ?></td>
                                    <td><?= (int) $project['overdue_tasks'] ?></td>
                                    <td><?= (int) $project['member_count'] ?></td>
                                    <td><?= $project['due_date'] ? e(date('M j, Y', strtotime($project['due_date']))) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="report-section">
            <div class="section-heading"><div><span class="eyebrow">TEAM</span><h2>Team performance</h2></div></div>
            <?php if (!$teamPerformance): ?>
                <div class="panel empty-state">Team performance will appear once tasks are assigned to active project members.</div>
            <?php else: ?>
                <div class="report-table-wrap">
                    <table class="report-table team-report-table">
                        <thead><tr><th>Team member</th><th>Assigned tasks</th><th>Completed</th><th>Completion rate</th><th>Overdue</th></tr></thead>
                        <tbody>
                            <?php foreach ($teamPerformance as $member): ?>
                                <tr>
                                    <td><strong><?= e($member['user_name']) ?></strong></td>
                                    <td><?= (int) $member['assigned_tasks'] ?></td>
                                    <td><?= (int) $member['completed_tasks'] ?></td>
                                    <td><?= e((string) ($member['completion_rate'] ?? '0.0')) ?>%</td>
                                    <td><?= (int) $member['overdue_tasks'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php require BASE_PATH . '/app/Views/layouts/workspace_end.php'; ?>
</body>
</html>
