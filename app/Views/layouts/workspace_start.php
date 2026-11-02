<?php
$appBaseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
$pageLabels = [
    'dashboard' => 'Overview',
    'projects' => 'Projects',
    'my-tasks' => 'My tasks',
    'calendar' => 'Calendar',
    'notifications' => 'Notifications',
    'reports' => 'Reports',
    'users' => 'People',
    'roles' => 'Roles & permissions',
];
$currentPage = $currentPage ?? 'dashboard';
$navItems = [
    ['key' => 'dashboard', 'path' => '/dashboard', 'label' => 'Dashboard', 'glyph' => '▦'],
    ['key' => 'projects', 'path' => '/projects', 'label' => 'Projects', 'glyph' => '◇'],
    ['key' => 'my-tasks', 'path' => '/my-tasks', 'label' => 'My tasks', 'glyph' => '✓'],
    ['key' => 'calendar', 'path' => '/calendar', 'label' => 'Calendar', 'glyph' => '▤'],
    ['key' => 'notifications', 'path' => '/notifications', 'label' => 'Notifications', 'glyph' => '♧'],
    ['key' => 'reports', 'path' => '/reports', 'label' => 'Reports', 'glyph' => '▥'],
];
if (!empty($canManageUsers) || $currentPage === 'users') {
    $navItems[] = ['key' => 'users', 'path' => '/users', 'label' => 'People', 'glyph' => '♙'];
}
if (!empty($canManageRoles) || $currentPage === 'roles') {
    $navItems[] = ['key' => 'roles', 'path' => '/roles', 'label' => 'Roles & permissions', 'glyph' => '⚙'];
}
?>
<header class="app-topbar">
    <a class="app-brand" href="<?= e($appBaseUrl) ?>/dashboard">
        <span class="brand-mark small">T</span><span>TEAMFLOW</span>
    </a>
    <div class="topbar-context"><span>Workspace</span><span aria-hidden="true">/</span><strong><?= e($pageLabels[$currentPage] ?? 'Workspace') ?></strong></div>
    <div class="topbar-actions">
        <button class="topbar-icon" type="button" data-quick-panel-toggle="calendar" aria-expanded="false" aria-controls="quick-panel-calendar">
            <span aria-hidden="true">▤</span><span class="topbar-action-label">Calendar</span>
        </button>
        <button class="topbar-icon" type="button" data-quick-panel-toggle="notifications" aria-expanded="false" aria-controls="quick-panel-notifications">
            <span aria-hidden="true">♧</span><span class="topbar-action-label">Notifications</span>
            <span class="topbar-badge" data-unread-badge hidden></span>
        </button>
        <span class="topbar-user"><span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></span><span><?= e($name) ?></span></span>
        <section class="quick-popover calendar-popover" id="quick-panel-calendar" data-preview-url="<?= e($appBaseUrl) ?>/calendar/preview" role="dialog" aria-labelledby="calendar-popover-title" hidden>
            <div class="quick-popover-heading">
                <div><p class="panel-kicker">YOUR SCHEDULE</p><h2 id="calendar-popover-title">Upcoming deadlines</h2></div>
                <button class="quick-popover-close" type="button" data-quick-panel-close aria-label="Close calendar">×</button>
            </div>
            <div class="calendar-popover-controls">
                <button type="button" data-calendar-month-shift="-1" aria-label="Previous month">‹</button>
                <strong data-calendar-month-label></strong>
                <button type="button" data-calendar-month-shift="1" aria-label="Next month">›</button>
            </div>
            <div class="quick-popover-content" data-quick-panel-content="calendar" aria-live="polite">
                <p class="quick-panel-message">Open Calendar to load deadlines.</p>
            </div>
            <a class="quick-popover-footer" href="<?= e($appBaseUrl) ?>/calendar">Open full calendar <span aria-hidden="true">→</span></a>
        </section>
        <section class="quick-popover notifications-popover" id="quick-panel-notifications" data-preview-url="<?= e($appBaseUrl) ?>/notifications/preview" role="dialog" aria-labelledby="notifications-popover-title" hidden>
            <div class="quick-popover-heading">
                <div><p class="panel-kicker">TEAM UPDATES</p><h2 id="notifications-popover-title">Notifications</h2></div>
                <button class="quick-popover-close" type="button" data-quick-panel-close aria-label="Close notifications">×</button>
            </div>
            <div class="quick-popover-content" data-quick-panel-content="notifications" aria-live="polite">
                <p class="quick-panel-message">Open Notifications to load recent updates.</p>
            </div>
            <a class="quick-popover-footer" href="<?= e($appBaseUrl) ?>/notifications">Open notification center <span aria-hidden="true">→</span></a>
        </section>
    </div>
</header>
<div class="dashboard-workspace">
    <aside class="sidebar">
        <p class="side-label">WORKSPACE</p>
        <nav class="workspace-nav" aria-label="Workspace">
            <?php foreach ($navItems as $item): ?>
                <a class="side-link <?= $currentPage === $item['key'] ? 'active' : '' ?>" href="<?= e($appBaseUrl . $item['path']) ?>" <?= $currentPage === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <span class="nav-glyph" aria-hidden="true"><?= e($item['glyph']) ?></span><?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-user"><span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?></span><span><strong><?= e($name) ?></strong><small><?= e($role) ?></small></span></div>
            <form method="post" action="<?= e($appBaseUrl) ?>/logout">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">Sign out</button>
            </form>
        </div>
    </aside>
