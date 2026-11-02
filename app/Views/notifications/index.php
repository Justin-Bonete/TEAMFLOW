<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications · TEAMFLOW</title>
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
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar">Calendar</a>
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Notifications <?php if ($unreadCount): ?><span class="notification-count"><?= $unreadCount ?></span><?php endif; ?></a>
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
            <div><span class="eyebrow">TEAM UPDATES</span><h1>Notifications</h1></div>
            <span class="role-pill"><?= $unreadCount ?> unread</span>
        </header>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>
        <?php if ($unreadCount): ?>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications/read" class="mark-all-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="mark_all" value="1">
                <button class="button-secondary" type="submit">Mark all as read</button>
            </form>
        <?php endif; ?>
        <?php if (!$notifications): ?>
            <div class="panel empty-state">You’re all caught up. Updates from your projects will appear here.</div>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <article class="panel notification-card <?= $notification['read_at'] ? 'notification-read' : 'notification-unread' ?>">
                        <div class="notification-card-content">
                            <span class="notification-type"><?= e(str_replace('.', ' · ', $notification['type'])) ?></span>
                            <h2><?= e($notification['title']) ?></h2>
                            <p><?= e($notification['message'] ?? '') ?></p>
                            <time datetime="<?= e($notification['created_at']) ?>"><?= e(date('M j, Y · g:i a', strtotime($notification['created_at']))) ?></time>
                        </div>
                        <div class="notification-actions">
                            <?php if (isset($notification['project_id'])): ?><a class="button-secondary" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/view?project_id=<?= (int) $notification['project_id'] ?>">Open project</a><?php endif; ?>
                            <?php if (!$notification['read_at']): ?>
                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications/read">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                                    <button class="button-secondary" type="submit">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
