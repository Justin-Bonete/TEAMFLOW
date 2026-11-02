<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My tasks · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard"><span class="brand-mark small">T</span> TEAMFLOW</a>
        <p class="side-label">WORKSPACE</p>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard">Overview</a>
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">My tasks</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a>
        <a class="side-link" href="#">Calendar <span class="soon">SOON</span></a>
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
        <header class="topbar"><div><span class="eyebrow">YOUR WORK</span><h1>My tasks</h1></div><span class="role-pill"><?= count($tasks) ?> assigned</span></header>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>
        <?php if (!$tasks): ?>
            <div class="panel empty-state">No tasks assigned to you yet. Tasks assigned to you in a project will appear here.</div>
        <?php else: ?>
            <div class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <article class="panel task-card">
                        <div class="task-card-heading">
                            <div>
                                <span class="project-key"><?= e($task['project_key']) ?> · <?= e($task['project_name']) ?></span>
                                <h3><?= e($task['title']) ?></h3>
                                <p><?= e($task['description'] ?: 'No task description.') ?></p>
                            </div>
                            <span class="task-priority priority-<?= e($task['priority']) ?>"><?= e(ucfirst($task['priority'])) ?> priority</span>
                        </div>
                        <div class="task-meta">
                            <span>Status: <?= e(str_replace('_', ' ', ucfirst($task['status']))) ?></span>
                            <?php if ($task['due_date']): ?><span>Due <?= e(date('M j, Y', strtotime($task['due_date']))) ?></span><?php endif; ?>
                            <?php if ($task['checklist_count']): ?><span>Checklist <?= (int) $task['checklist_done'] ?>/<?= (int) $task['checklist_count'] ?></span><?php endif; ?>
                        </div>
                        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/status" class="task-status-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                            <label>Update status<select name="status"><?php foreach (['todo','in_progress','done'] as $status): ?><option value="<?= e($status) ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label>
                            <button class="button-secondary" type="submit">Save status</button>
                        </form>
                        <a class="task-project-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $task['project_id'] ?>">Open project and checklist →</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
