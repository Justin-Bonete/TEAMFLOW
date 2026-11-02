<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Roles and permissions · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/dark-ui.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard"><span class="brand-mark small">T</span> TEAMFLOW</a>
        <p class="side-label">WORKSPACE</p>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/dashboard">Overview</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/my-tasks">My tasks</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar">Calendar</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Notifications</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/reports">Reports</a>
        <?php if ($canManageUsers): ?><a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users">People</a><?php endif; ?>
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/roles">Roles & permissions</a>
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
            <div><span class="eyebrow">ADMINISTRATION</span><h1>Roles and permissions</h1></div>
            <span class="role-pill"><?= count($roles) ?> roles</span>
        </header>

        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>

        <section class="panel role-create-panel">
            <div class="section-heading">
                <div><span class="eyebrow">DEFINE ACCESS</span><h2>Create a role</h2></div>
            </div>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/roles" class="role-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>Role name<input name="name" maxlength="50" required></label>
                <label>Description<input name="description" maxlength="255"></label>
                <fieldset class="permissions-fieldset">
                    <legend>Permissions</legend>
                    <div class="permission-grid">
                        <?php foreach ($permissions as $permission): ?>
                            <label class="permission-option">
                                <?php if ($permission['permission_key'] === 'roles.manage'): ?>
                                    <input type="checkbox" disabled>
                                <?php else: ?>
                                    <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>">
                                <?php endif; ?>
                                <span><strong><?= e($permission['permission_key']) ?></strong><small><?= e($permission['description'] ?? '') ?><?= $permission['permission_key'] === 'roles.manage' ? ' Reserved for Administrator.' : '' ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button class="button-primary" type="submit">Create role <span>→</span></button>
            </form>
        </section>

        <section class="roles-section">
            <div class="section-heading">
                <div><span class="eyebrow">ACCESS CONFIGURATION</span><h2>Existing roles</h2></div>
            </div>
            <div class="roles-list">
                <?php foreach ($roles as $existingRole): ?>
                    <article class="panel role-card">
                        <div class="role-card-heading">
                            <div><h3><?= e($existingRole['name']) ?></h3><p><?= e($existingRole['description'] ?? 'No description provided.') ?></p></div>
                            <?php if ($existingRole['name'] === 'Administrator'): ?><span class="account-status status-active">Protected</span><?php endif; ?>
                        </div>
                        <?php if ($existingRole['name'] === 'Administrator'): ?>
                            <p class="protected-role-note">The built-in Administrator role has all system permissions and cannot be changed here.</p>
                            <div class="permission-tags">
                                <?php foreach ($permissions as $permission): ?>
                                    <?php if (in_array((int) $permission['id'], $existingRole['permission_ids'], true)): ?><span><?= e($permission['permission_key']) ?></span><?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/roles/update" class="role-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="role_id" value="<?= (int) $existingRole['id'] ?>">
                                <label>Role name<input name="name" maxlength="50" value="<?= e($existingRole['name']) ?>" required></label>
                                <label>Description<input name="description" maxlength="255" value="<?= e($existingRole['description'] ?? '') ?>"></label>
                                <fieldset class="permissions-fieldset">
                                    <legend>Permissions</legend>
                                    <div class="permission-grid">
                                        <?php foreach ($permissions as $permission): ?>
                                            <label class="permission-option">
                                                <?php if ($permission['permission_key'] === 'roles.manage'): ?>
                                                    <input type="checkbox" disabled <?= in_array((int) $permission['id'], $existingRole['permission_ids'], true) ? 'checked' : '' ?>>
                                                <?php else: ?>
                                                    <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>" <?= in_array((int) $permission['id'], $existingRole['permission_ids'], true) ? 'checked' : '' ?>>
                                                <?php endif; ?>
                                                <span><strong><?= e($permission['permission_key']) ?></strong><small><?= e($permission['description'] ?? '') ?><?= $permission['permission_key'] === 'roles.manage' ? ' Reserved for Administrator.' : '' ?></small></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                                <button class="button-primary" type="submit">Save role <span>→</span></button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
