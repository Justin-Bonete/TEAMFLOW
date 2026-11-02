<?php
$appBaseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
$currentPage = $currentPage ?? 'dashboard';
$navItems = [
    ['key' => 'dashboard', 'path' => '/dashboard', 'label' => 'Dashboard', 'glyph' => '▦'],
    ['key' => 'projects', 'path' => '/projects', 'label' => 'Projects', 'glyph' => '◇'],
    ['key' => 'my-tasks', 'path' => '/my-tasks', 'label' => 'My tasks', 'glyph' => '✓'],
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
    <div class="topbar-actions">
        <button class="topbar-icon" type="button" data-quick-panel-toggle="calendar" aria-expanded="false" aria-controls="quick-panel-calendar">
            <span aria-hidden="true">▤</span><span class="topbar-action-label">Calendar</span>
        </button>
        <button class="topbar-icon" type="button" data-quick-panel-toggle="tutorial" aria-expanded="false" aria-controls="quick-panel-tutorial" aria-label="How to use TEAMFLOW">
            <span aria-hidden="true">?</span><span class="topbar-action-label">Tutorial</span>
        </button>
        <button class="topbar-icon" type="button" data-quick-panel-toggle="notifications" aria-expanded="false" aria-controls="quick-panel-notifications">
            <span aria-hidden="true">♧</span><span class="topbar-action-label">Notifications</span>
            <span class="topbar-badge" data-unread-badge hidden></span>
        </button>
        <button class="topbar-user profile-link" type="button" data-profile-settings-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="profile-settings-dialog" aria-label="Open profile and settings">
            <span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?><?php if (!empty($_SESSION['has_profile_avatar'])): ?><img class="avatar-photo" src="<?= e($appBaseUrl) ?>/profile/avatar?v=<?= (int) ($_SESSION['profile_avatar_version'] ?? 0) ?>" alt="" aria-hidden="true"><?php endif; ?></span>
            <span><?= e($name) ?></span>
        </button>
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
        <section class="quick-popover tutorial-popover" id="quick-panel-tutorial" role="dialog" aria-labelledby="tutorial-popover-title" hidden>
            <div class="quick-popover-heading">
                <div><p class="panel-kicker">QUICK START</p><h2 id="tutorial-popover-title">How to use TEAMFLOW</h2></div>
                <button class="quick-popover-close" type="button" data-quick-panel-close aria-label="Close tutorial">×</button>
            </div>
            <div class="tutorial-content">
                <ol class="tutorial-steps">
                    <li>
                        <strong>Check your dashboard</strong>
                        <p>See project progress, your tasks, and recent team updates in one place.</p>
                        <a href="<?= e($appBaseUrl) ?>/dashboard">Go to Dashboard <span aria-hidden="true">→</span></a>
                    </li>
                    <li>
                        <strong>Create a project</strong>
                        <p>Open Projects, add a name and dates, then choose the teammates who will help.</p>
                        <a href="<?= e($appBaseUrl) ?>/projects">Open Projects <span aria-hidden="true">→</span></a>
                    </li>
                    <li>
                        <strong>Add and update tasks</strong>
                        <p>Give each task an owner, priority, and due date. Use checklists or subtasks for smaller steps, then update its status as work moves along.</p>
                        <a href="<?= e($appBaseUrl) ?>/my-tasks">View My tasks <span aria-hidden="true">→</span></a>
                    </li>
                    <li>
                        <strong>Keep everyone in the loop</strong>
                        <p>Use task comments and attachments to share details. Notifications show you when the team makes updates.</p>
                    </li>
                    <li>
                        <strong>See what is on track</strong>
                        <p>Use Calendar for upcoming deadlines and Reports to review progress, completed work, and overdue tasks.</p>
                        <a href="<?= e($appBaseUrl) ?>/reports">Open Reports <span aria-hidden="true">→</span></a>
                    </li>
                </ol>
                <p class="tutorial-tip">Tip: Click your profile picture to update your name, photo, or account settings.</p>
            </div>
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
<section class="profile-settings-modal" id="profile-settings-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-settings-dialog-title" hidden>
    <div class="profile-settings-modal-panel">
        <header class="profile-settings-modal-heading">
            <h2 id="profile-settings-dialog-title">Profile & settings</h2>
            <button type="button" class="quick-popover-close" data-profile-settings-close aria-label="Close profile settings">×</button>
        </header>
        <div class="profile-settings-modal-content" data-profile-settings-content aria-live="polite">
            <p class="quick-panel-message">Loading your settings…</p>
        </div>
    </div>
</section>
<div class="dashboard-workspace">
    <aside class="sidebar">
        <nav class="workspace-nav" aria-label="Workspace">
            <?php foreach ($navItems as $item): ?>
                <a class="side-link <?= $currentPage === $item['key'] ? 'active' : '' ?>" href="<?= e($appBaseUrl . $item['path']) ?>" <?= $currentPage === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <span class="nav-glyph" aria-hidden="true"><?= e($item['glyph']) ?></span><?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <button class="sidebar-user sidebar-profile-link" type="button" data-profile-settings-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="profile-settings-dialog" aria-label="Open profile and settings">
                <span class="avatar"><?= e(strtoupper(substr($name, 0, 1))) ?><?php if (!empty($_SESSION['has_profile_avatar'])): ?><img class="avatar-photo" src="<?= e($appBaseUrl) ?>/profile/avatar?v=<?= (int) ($_SESSION['profile_avatar_version'] ?? 0) ?>" alt="" aria-hidden="true"><?php endif; ?></span>
                <span><strong><?= e($name) ?></strong><small><?= e($role) ?></small></span>
            </button>
            <form method="post" action="<?= e($appBaseUrl) ?>/logout">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">Sign out</button>
            </form>
        </div>
    </aside>
