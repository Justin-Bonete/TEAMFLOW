<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User management · TEAMFLOW</title>
    <link rel="stylesheet" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/css/app.css">
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
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users">People</a>
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
            <div><span class="eyebrow">ADMINISTRATION</span><h1>People and access</h1></div>
            <span class="role-pill"><?= count($users) ?> accounts</span>
        </header>

        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>

        <section class="panel user-create-panel">
            <div class="section-heading">
                <div><span class="eyebrow">ADD TO YOUR WORKSPACE</span><h2>Create a user account</h2></div>
            </div>
            <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users" class="user-form create-user-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>First name<input name="first_name" maxlength="80" required></label>
                <label>Last name<input name="last_name" maxlength="80" required></label>
                <label>Work email<input type="email" name="email" maxlength="190" required></label>
                <label>Initial password<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
                <label>Role<select name="role_id" required><?php foreach ($roles as $availableRole): ?><?php if ($canManageAdministrators || $availableRole['name'] !== 'Administrator'): ?><option value="<?= (int) $availableRole['id'] ?>"><?= e($availableRole['name']) ?></option><?php endif; ?><?php endforeach; ?></select></label>
                <label>Account status<select name="status" required><option value="active">Active</option><option value="pending">Pending</option><option value="suspended">Suspended</option><option value="archived">Archived</option></select></label>
                <button class="button-primary" type="submit">Create account <span>→</span></button>
            </form>
            <p class="form-hint">Share the initial password with the user securely. Passwords must be at least 12 characters.</p>
        </section>

        <section class="people-section">
            <div class="section-heading">
                <div><span class="eyebrow">WORKSPACE DIRECTORY</span><h2>Manage accounts</h2></div>
                <span class="muted"><?= count($users) ?> total</span>
            </div>
            <?php if (!$users): ?>
                <div class="panel empty-state">No user accounts found.</div>
            <?php else: ?>
                <div class="people-list">
                    <?php foreach ($users as $user): ?>
                        <article class="panel person-card">
                            <div class="person-heading">
                                <span class="avatar"><?= e(strtoupper(substr($user['first_name'], 0, 1))) ?></span>
                                <div><h3><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h3><p><?= e($user['email']) ?></p></div>
                                <span class="account-status status-<?= e($user['status']) ?>"><?= e(ucfirst($user['status'])) ?></span>
                            </div>
                            <?php if ($canManageAdministrators || $user['role_name'] !== 'Administrator'): ?>
                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/users/update" class="user-form edit-user-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <label>First name<input name="first_name" maxlength="80" value="<?= e($user['first_name']) ?>" required></label>
                                    <label>Last name<input name="last_name" maxlength="80" value="<?= e($user['last_name']) ?>" required></label>
                                    <label>Work email<input type="email" name="email" maxlength="190" value="<?= e($user['email']) ?>" required></label>
                                    <label>Role<select name="role_id" required><?php foreach ($roles as $availableRole): ?><?php if ($canManageAdministrators || $availableRole['name'] !== 'Administrator'): ?><option value="<?= (int) $availableRole['id'] ?>" <?= (int) $user['role_id'] === (int) $availableRole['id'] ? 'selected' : '' ?>><?= e($availableRole['name']) ?></option><?php endif; ?><?php endforeach; ?></select></label>
                                    <label>Status<select name="status" required><?php foreach (['active', 'pending', 'suspended', 'archived'] as $status): ?><option value="<?= e($status) ?>" <?= $user['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
                                    <label>Reset password <span class="optional-label">(optional)</span><input type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Leave blank to keep current"></label>
                                    <button class="button-primary" type="submit">Save changes <span>→</span></button>
                                </form>
                            <?php else: ?>
                                <p class="protected-role-note">Only an active Administrator can manage Administrator accounts.</p>
                            <?php endif; ?>
                            <p class="account-meta">Added <?= e(date('M j, Y', strtotime($user['created_at']))) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
