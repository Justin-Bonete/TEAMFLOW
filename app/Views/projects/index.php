<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Projects · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-page dark-dashboard">
    <?php $currentPage = 'projects'; require BASE_PATH . '/app/Views/layouts/workspace_start.php'; ?>
    <main class="dashboard-main">
        <header class="topbar">
            <div><span class="eyebrow">YOUR WORKSPACE</span><h1>Projects</h1></div>
            <span class="role-pill"><?= count($projects) ?> projects</span>
        </header>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>

        <?php if ($canCreate): ?>
            <section class="panel project-create-panel">
                <div class="section-heading"><div><span class="eyebrow">GET THE TEAM STARTED</span><h2>Create a project</h2></div></div>
                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/create" class="project-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <label>Project name<input name="name" maxlength="180" required></label>
                    <label>Status<select name="status"><option value="planning">Planning</option><option value="active">Active</option><option value="on_hold">On hold</option></select></label>
                    <label>Start date<input type="date" name="start_date"></label>
                    <label>Due date<input type="date" name="due_date"></label>
                    <label class="project-description">Description<textarea name="description" rows="3" maxlength="20000"></textarea></label>
                    <fieldset class="project-members-fieldset">
                        <legend>Initial team members</legend>
                        <?php if (!$activeUsers): ?><p class="form-hint">No other active users are available yet.</p><?php else: ?>
                            <div class="member-choice-grid">
                                <?php foreach ($activeUsers as $activeUser): ?>
                                    <?php if ((int) $activeUser['id'] !== (int) $_SESSION['user_id']): ?>
                                        <label class="member-choice"><input type="checkbox" name="members[]" value="<?= (int) $activeUser['id'] ?>"><span><?= e($activeUser['first_name'] . ' ' . $activeUser['last_name']) ?><small><?= e($activeUser['role_name']) ?></small></span></label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </fieldset>
                    <button class="button-primary" type="submit">Create project <span>→</span></button>
                </form>
            </section>
        <?php endif; ?>

        <section class="project-list-section">
            <div class="section-heading"><div><span class="eyebrow">TEAM WORK</span><h2>Accessible projects</h2></div></div>
            <?php if (!$projects): ?>
                <div class="panel empty-state"><?= $canCreate ? 'No projects yet. Create one to get started.' : 'You have not been added to any projects yet.' ?></div>
            <?php else: ?>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                        <a class="panel project-card" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $project['id'] ?>">
                            <div class="project-card-top"><span class="project-key"><?= e($project['project_key']) ?></span><span class="project-status status-<?= e($project['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($project['status']))) ?></span></div>
                            <h3><?= e($project['name']) ?></h3>
                            <p><?= e($project['description'] ?: 'No description provided.') ?></p>
                            <div class="project-card-meta"><span>Lead: <?= e($project['owner_name']) ?></span><span><?= (int) $project['task_count'] ?> tasks</span></div>
                            <?php if ($project['due_date']): ?><small class="project-due">Due <?= e(date('M j, Y', strtotime($project['due_date']))) ?></small><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php require BASE_PATH . '/app/Views/layouts/workspace_end.php'; ?>
</body>
</html>
