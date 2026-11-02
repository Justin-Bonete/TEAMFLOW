<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($project['name']) ?> · TEAMFLOW</title>
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
        <a class="side-link active" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/calendar">Calendar</a>
        <a class="side-link" href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/notifications">Notifications</a>
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
        <p class="breadcrumb"><a href="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects">Projects</a> / <?= e($project['project_key']) ?></p>
        <header class="topbar project-detail-topbar">
            <div><span class="eyebrow"><?= e($project['project_key']) ?> · LED BY <?= e(strtoupper($project['owner_name'])) ?></span><h1><?= e($project['name']) ?></h1></div>
            <span class="project-status status-<?= e($project['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($project['status']))) ?></span>
        </header>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>

        <section class="project-summary panel">
            <div><span class="eyebrow">PROJECT OVERVIEW</span><p><?= e($project['description'] ?: 'No project description yet.') ?></p><div class="project-date-line"><span>Starts <?= e($project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not set') ?></span><span>Due <?= e($project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'Not set') ?></span></div></div>
        </section>

        <?php if ($canEditProject): ?>
            <details class="panel collapsible-panel">
                <summary>Edit project details</summary>
                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/update" class="project-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                    <label>Project name<input name="name" maxlength="180" value="<?= e($project['name']) ?>" required></label>
                    <label>Status<select name="status"><?php foreach (['planning','active','on_hold','completed','archived'] as $status): ?><option value="<?= e($status) ?>" <?= $project['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label>
                    <label>Start date<input type="date" name="start_date" value="<?= e($project['start_date']) ?>"></label>
                    <label>Due date<input type="date" name="due_date" value="<?= e($project['due_date']) ?>"></label>
                    <label class="project-description">Description<textarea name="description" rows="3" maxlength="20000"><?= e($project['description']) ?></textarea></label>
                    <button class="button-primary" type="submit">Save project <span>→</span></button>
                </form>
            </details>
        <?php endif; ?>

        <section class="project-members-section">
            <div class="section-heading"><div><span class="eyebrow">PROJECT TEAM</span><h2>Members <span class="muted"><?= count($members) ?></span></h2></div></div>
            <?php if ($canEditProject): ?>
                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/projects/members" class="panel member-management-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                    <p class="form-hint">Select active accounts to add. Mark project leads as managers. Removing a member also removes their task assignments in this project.</p>
                    <div class="member-choice-grid">
                        <?php foreach ($members as $member): ?>
                            <?php if ((int) $member['id'] !== (int) $project['owner_id']): ?>
                                <div class="member-choice member-role-choice"><label class="member-main-choice"><input type="checkbox" name="members[]" value="<?= (int) $member['id'] ?>" <?= $member['status'] === 'active' ? 'checked' : '' ?>><span><?= e($member['first_name'] . ' ' . $member['last_name']) ?><small><?= e($member['email']) ?><?= $member['status'] !== 'active' ? ' · ' . e(ucfirst($member['status'])) : '' ?></small></span></label><label class="manager-choice"><input type="checkbox" name="managers[]" value="<?= (int) $member['id'] ?>" <?= $member['project_role'] === 'manager' ? 'checked' : '' ?> <?= !$member['can_manage_projects'] || $member['status'] !== 'active' ? 'disabled' : '' ?>> Manager</label></div>
                            <?php else: ?>
                                <div class="member-choice owner-choice"><span class="avatar"><?= e(strtoupper(substr($member['first_name'], 0, 1))) ?></span><span><?= e($member['first_name'] . ' ' . $member['last_name']) ?><small>Project owner · Manager</small></span></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php foreach ($availableMembers as $availableMember): ?>
                            <?php if (!in_array((int) $availableMember['id'], array_map('intval', array_column($members, 'id')), true)): ?>
                                <div class="member-choice member-role-choice"><label class="member-main-choice"><input type="checkbox" name="members[]" value="<?= (int) $availableMember['id'] ?>"><span><?= e($availableMember['first_name'] . ' ' . $availableMember['last_name']) ?><small><?= e($availableMember['email']) ?></small></span></label><label class="manager-choice"><input type="checkbox" name="managers[]" value="<?= (int) $availableMember['id'] ?>" <?= !$availableMember['can_manage_projects'] ? 'disabled' : '' ?>> Manager</label></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="button-primary" type="submit">Save team <span>→</span></button>
                </form>
            <?php else: ?>
                <div class="member-list panel"><?php foreach ($members as $member): ?><span class="member-chip"><span class="avatar"><?= e(strtoupper(substr($member['first_name'], 0, 1))) ?></span><span><?= e($member['first_name'] . ' ' . $member['last_name']) ?><small><?= e(ucfirst($member['project_role'])) ?></small></span></span><?php endforeach; ?></div>
            <?php endif; ?>
        </section>

        <section class="tasks-section">
            <div class="section-heading"><div><span class="eyebrow">PLAN AND TRACK</span><h2>Tasks and subtasks</h2></div><span class="muted"><?= count($tasks) ?> tasks</span></div>
            <?php if ($canCreateTasks): ?>
                <details class="panel collapsible-panel task-create-panel">
                    <summary>Create task</summary>
                    <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/create" class="task-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                        <label>Task title<input name="title" maxlength="200" required></label>
                        <label>Parent task <select name="parent_task_id"><option value="">Main task</option><?php foreach ($tasks as $task): ?><option value="<?= (int) $task['id'] ?>">Subtask of: <?= e($task['title']) ?></option><?php endforeach; ?></select></label>
                        <label>Status<select name="status"><?php foreach (['todo','in_progress','done'] as $status): ?><option value="<?= e($status) ?>"><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label>
                        <label>Priority<select name="priority"><?php foreach (['low','medium','high','urgent'] as $priority): ?><option value="<?= e($priority) ?>" <?= $priority === 'medium' ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option><?php endforeach; ?></select></label>
                        <label>Start date<input type="date" name="start_date"></label>
                        <label>Due date<input type="date" name="due_date"></label>
                        <label class="task-description">Description<textarea name="description" rows="2" maxlength="20000"></textarea></label>
                        <fieldset class="task-assignees"><legend>Assign to project members</legend><div class="member-choice-grid"><?php foreach ($activeUsers as $member): ?><label class="member-choice"><input type="checkbox" name="assignees[]" value="<?= (int) $member['id'] ?>"><span><?= e($member['first_name'] . ' ' . $member['last_name']) ?><small><?= e(ucfirst($member['project_role'])) ?></small></span></label><?php endforeach; ?></div></fieldset>
                        <label class="task-description">Checklist items <span class="optional-label">(one per line)</span><textarea name="checklist_lines" rows="3" placeholder="Add the required steps, one per line"></textarea></label>
                        <button class="button-primary" type="submit">Create task <span>→</span></button>
                    </form>
                </details>
            <?php endif; ?>
            <?php if ($boardTasks): ?>
                <section class="kanban-section" aria-label="Task Kanban board">
                    <div class="section-heading"><div><span class="eyebrow">WORKFLOW</span><h3>Kanban board</h3></div><span class="muted">Drag a card to change its status, or use its status control.</span></div>
                    <div class="kanban-board" data-kanban-board>
                        <?php foreach ($taskStatuses as $boardStatus): ?>
                            <?php $columnTasks = array_filter($boardTasks, static fn (array $boardTask): bool => $boardTask['status'] === $boardStatus); ?>
                            <section class="kanban-column" data-kanban-column="<?= e($boardStatus) ?>">
                                <h4><?= e(str_replace('_', ' ', ucfirst($boardStatus))) ?><span><?= count($columnTasks) ?></span></h4>
                                <div class="kanban-cards">
                                    <?php foreach ($columnTasks as $boardTask): ?>
                                        <?php $boardAssigned = in_array((string) $userId, explode(',', (string) $boardTask['assignee_ids']), true); $canChangeBoardStatus = $projectOpen && ($canManage || ($canUpdateOwnTasks && $boardAssigned)); ?>
                                        <article class="kanban-card" data-kanban-card="<?= (int) $boardTask['id'] ?>" draggable="<?= $canChangeBoardStatus ? 'true' : 'false' ?>">
                                            <a href="#task-<?= (int) $boardTask['id'] ?>"><?= e($boardTask['title']) ?></a>
                                            <span class="task-priority priority-<?= e($boardTask['priority']) ?>"><?= e(ucfirst($boardTask['priority'])) ?></span>
                                            <?php if ($boardTask['due_date']): ?><small>Due <?= e(date('M j', strtotime($boardTask['due_date']))) ?></small><?php endif; ?>
                                            <?php if ($canChangeBoardStatus): ?>
                                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/status" class="kanban-status-form" data-kanban-status-form>
                                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="task_id" value="<?= (int) $boardTask['id'] ?>">
                                                    <label class="sr-only" for="kanban-status-<?= (int) $boardTask['id'] ?>">Change task status</label>
                                                    <select id="kanban-status-<?= (int) $boardTask['id'] ?>" name="status"><?php foreach ($taskStatuses as $status): ?><option value="<?= e($status) ?>" <?= $boardTask['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select>
                                                    <button class="button-secondary" type="submit">Save</button>
                                                </form>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php if (!$tasks): ?>
                <div class="panel empty-state">No tasks yet<?= $canCreateTasks ? '. Create the first task above.' : '.' ?></div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <?php $taskChecklist = $checklists[(int) $task['id']] ?? []; $taskAssigned = in_array((string) $_SESSION['user_id'], explode(',', (string) $task['assignee_ids']), true); ?>
                        <article class="panel task-card" id="task-<?= (int) $task['id'] ?>">
                            <div class="task-card-heading">
                                <div><span class="task-priority priority-<?= e($task['priority']) ?>"><?= e(ucfirst($task['priority'])) ?> priority</span><h3><?= e($task['title']) ?></h3><p><?= e($task['description'] ?: 'No task description.') ?></p></div>
                                <?php if ($task['due_date']): ?><span class="task-due">Due <?= e(date('M j', strtotime($task['due_date']))) ?></span><?php endif; ?>
                            </div>
                            <div class="task-meta"><span>Assigned: <?= e($task['assignees'] ?: 'Unassigned') ?></span><?php if ($task['checklist_count']): ?><span>Checklist <?= (int) $task['checklist_done'] ?>/<?= (int) $task['checklist_count'] ?></span><?php endif; ?></div>
                            <?php if ($canManage || $taskAssigned): ?>
                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/status" class="task-status-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <label>Status<select name="status"><?php foreach (['todo','in_progress','done'] as $status): ?><option value="<?= e($status) ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label>
                                    <button class="button-secondary" type="submit">Update status</button>
                                </form>
                            <?php else: ?><span class="task-status-badge status-<?= e($task['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($task['status']))) ?></span><?php endif; ?>
                            <?php if ($canAssignTasks): ?>
                                <details class="task-edit-details"><summary>Edit task and assignments</summary>
                                    <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/update" class="task-form">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <label>Task title<input name="title" maxlength="200" value="<?= e($task['title']) ?>" required></label>
                                        <label>Status<select name="status"><?php foreach (['todo','in_progress','done'] as $status): ?><option value="<?= e($status) ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label>
                                        <label>Priority<select name="priority"><?php foreach (['low','medium','high','urgent'] as $priority): ?><option value="<?= e($priority) ?>" <?= $task['priority'] === $priority ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option><?php endforeach; ?></select></label>
                                        <label>Start date<input type="date" name="start_date" value="<?= e($task['start_date']) ?>"></label>
                                        <label>Due date<input type="date" name="due_date" value="<?= e($task['due_date']) ?>"></label>
                                        <label class="task-description">Description<textarea name="description" rows="2" maxlength="20000"><?= e($task['description']) ?></textarea></label>
                                        <fieldset class="task-assignees"><legend>Assigned members</legend><div class="member-choice-grid"><?php foreach ($activeUsers as $member): ?><label class="member-choice"><input type="checkbox" name="assignees[]" value="<?= (int) $member['id'] ?>" <?= in_array((string) $member['id'], explode(',', (string) $task['assignee_ids']), true) ? 'checked' : '' ?>><span><?= e($member['first_name'] . ' ' . $member['last_name']) ?><small><?= e(ucfirst($member['project_role'])) ?></small></span></label><?php endforeach; ?></div></fieldset>
                                        <button class="button-primary" type="submit">Save task <span>→</span></button>
                                    </form>
                                </details>
                            <?php endif; ?>
                            <?php if ($taskChecklist || $taskAssigned || $canManage): ?>
                                <div class="checklist">
                                    <?php foreach ($taskChecklist as $item): ?>
                                        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/checklist/toggle" class="checklist-item">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="completed" value="<?= $item['is_completed'] ? '0' : '1' ?>">
                                            <button type="submit" class="checklist-toggle" aria-label="<?= $item['is_completed'] ? 'Mark incomplete' : 'Mark complete' ?>" <?= !$canManage && !$taskAssigned ? 'disabled' : '' ?>><?= $item['is_completed'] ? '✓' : '' ?></button><span class="<?= $item['is_completed'] ? 'is-completed' : '' ?>"><?= e($item['title']) ?></span>
                                        </form>
                                    <?php endforeach; ?>
                                    <?php if ($canManage || $taskAssigned): ?>
                                        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/checklist/add" class="checklist-add-form">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>"><input name="title" maxlength="220" placeholder="Add checklist item" required><button class="button-secondary" type="submit">Add</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php $collaborationTaskId = (int) $task['id']; require BASE_PATH . '/app/Views/projects/_task_collaboration.php'; ?>
                            <?php foreach ($task['subtasks'] as $subtask): ?>
                                <?php $subtaskChecklist = $checklists[(int) $subtask['id']] ?? []; $subtaskAssigned = in_array((string) $_SESSION['user_id'], explode(',', (string) $subtask['assignee_ids']), true); ?>
                                <div class="subtask-card" id="task-<?= (int) $subtask['id'] ?>">
                                    <div><span class="subtask-label">SUBTASK</span><strong><?= e($subtask['title']) ?></strong><small><?= e($subtask['assignees'] ?: 'Unassigned') ?></small></div>
                                    <?php if ($canManage || $subtaskAssigned): ?>
                                        <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/status" class="task-status-form">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $subtask['id'] ?>"><label>Status<select name="status"><?php foreach (['todo','in_progress','done'] as $status): ?><option value="<?= e($status) ?>" <?= $subtask['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label><button class="button-secondary" type="submit">Update</button>
                                        </form>
                                    <?php else: ?><span class="task-status-badge status-<?= e($subtask['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($subtask['status']))) ?></span><?php endif; ?>
                                    <?php if ($subtaskChecklist || $canManage || $subtaskAssigned): ?>
                                        <div class="checklist subtask-checklist">
                                            <?php foreach ($subtaskChecklist as $item): ?>
                                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/checklist/toggle" class="checklist-item">
                                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="completed" value="<?= $item['is_completed'] ? '0' : '1' ?>">
                                                    <button type="submit" class="checklist-toggle" aria-label="<?= $item['is_completed'] ? 'Mark incomplete' : 'Mark complete' ?>" <?= !$canManage && !$subtaskAssigned ? 'disabled' : '' ?>><?= $item['is_completed'] ? '✓' : '' ?></button><span class="<?= $item['is_completed'] ? 'is-completed' : '' ?>"><?= e($item['title']) ?></span>
                                                </form>
                                            <?php endforeach; ?>
                                            <?php if ($canManage || $subtaskAssigned): ?>
                                                <form method="post" action="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/tasks/checklist/add" class="checklist-add-form">
                                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) $subtask['id'] ?>"><input name="title" maxlength="220" placeholder="Add checklist item" required><button class="button-secondary" type="submit">Add</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php $collaborationTaskId = (int) $subtask['id']; require BASE_PATH . '/app/Views/projects/_task_collaboration.php'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="activity-section">
            <div class="section-heading"><div><span class="eyebrow">PROJECT HISTORY</span><h2>Recent activity</h2></div></div>
            <?php if (!$activities): ?>
                <div class="panel empty-state">Project updates, task changes, comments, and checklist activity will appear here.</div>
            <?php else: ?>
                <ol class="activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <?php $activityDetails = json_decode((string) ($activity['details'] ?? ''), true); ?>
                        <li class="activity-item">
                            <span class="activity-dot" aria-hidden="true"></span>
                            <div><strong><?= e($activity['actor_name'] ?? 'Former user') ?></strong> <span><?= e(str_replace(['.', '_'], ' ', $activity['action'])) ?></span>
                                <?php if (is_array($activityDetails) && isset($activityDetails['title']) && is_string($activityDetails['title'])): ?><span> · <?= e($activityDetails['title']) ?></span><?php elseif (is_array($activityDetails) && isset($activityDetails['status']) && is_string($activityDetails['status'])): ?><span> · <?= e(str_replace('_', ' ', $activityDetails['status'])) ?></span><?php endif; ?>
                                <time datetime="<?= e($activity['created_at']) ?>"><?= e(date('M j, Y · g:i a', strtotime($activity['created_at']))) ?></time>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?= e(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>/assets/js/kanban.js" defer></script>
</body>
</html>
