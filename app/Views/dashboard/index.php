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
        <a class="side-link" href="#">My tasks <span class="soon">SOON</span></a>
        <a class="side-link" href="#">Projects <span class="soon">SOON</span></a>
        <a class="side-link" href="#">Calendar <span class="soon">SOON</span></a>
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
        <section class="welcome-card"><div><span class="eyebrow light">YOUR TEAM WORKSPACE</span><h2>Make progress visible.</h2><p>Your TEAMFLOW foundation is ready. Project, task, and collaboration modules will be added in the next development phases.</p></div><div class="welcome-orbit">T</div></section>
        <section class="stats-grid">
            <article class="stat-card"><span>Active projects</span><strong>—</strong><small>Project module pending</small></article>
            <article class="stat-card"><span>Tasks assigned to me</span><strong>—</strong><small>Task module pending</small></article>
            <article class="stat-card"><span>Completed tasks</span><strong>—</strong><small>Task module pending</small></article>
            <article class="stat-card"><span>Overdue tasks</span><strong>—</strong><small>Task module pending</small></article>
        </section>
        <section class="panel"><div><span class="eyebrow">DEVELOPMENT STATUS</span><h2>Foundation successfully initialized</h2><p>Authentication, session handling, database connectivity, MVC routing, and initial administrator setup are included in this starter build.</p></div><span class="status-tag">FOUNDATION</span></section>
    </main>
</body>
</html>
