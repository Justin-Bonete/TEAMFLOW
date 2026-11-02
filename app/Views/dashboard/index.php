<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard"><span class="brand-mark small">T</span> TEAMFLOW</a>
        <p class="side-label">WORKSPACE</p>
        <a class="side-link active" href="#">Overview</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">My tasks</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar">Calendar</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Notifications</a>
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
        <header class="topbar"><div><span class="eyebrow">WORKSPACE OVERVIEW</span><h1>Good to have you here, <?= e($name) ?>.</h1></div><span class="role-pill"><?= e($role) ?></span></header>
        <section class="welcome-card"><div><span class="eyebrow light">YOUR TEAM WORKSPACE</span><h2>Make progress visible.</h2><p>Organize projects, build your team, and track task progress from one workspace.</p></div><div class="welcome-orbit">T</div></section>
        <section class="stats-grid">
            <article class="stat-card"><span>Active projects</span><strong><?= $activeProjects ?></strong><small>Projects you can access</small></article>
            <article class="stat-card"><span>Tasks assigned to me</span><strong><?= $taskStats['assigned'] ?></strong><small>Across your projects</small></article>
            <article class="stat-card"><span>Completed tasks</span><strong><?= $taskStats['completed'] ?></strong><small>Assigned tasks marked done</small></article>
            <article class="stat-card"><span>Overdue tasks</span><strong><?= $taskStats['overdue'] ?></strong><small>Assigned tasks past due</small></article>
        </section>
        <section class="panel development-status"><div><span class="eyebrow">DEVELOPMENT STATUS</span><h2>Phase 4 — Collaboration</h2><p>Project and task management are live. Kanban boards, comments, private attachments, in-app notifications, deadlines calendar, and project activity history are now available. Analytics and reporting are next.</p></div><span class="status-tag">PHASE 4</span></section>
    </main>
</body>
</html>
