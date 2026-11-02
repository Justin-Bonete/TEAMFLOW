<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

final class TasksController
{
    public function mine(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $userModel = new User();
        $tasks = (new Task())->assignedToUser($userId);
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        require BASE_PATH . '/app/Views/tasks/mine.php';
    }

    public function create(): void
    {
        verify_csrf();
        $projectId = $this->projectId($_POST['project_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $projectController = new ProjectsController();
        $projectController->requireVisible($projectId, $userId);
        $this->requireProjectManager($projectId, $userId, 'tasks.create');

        $data = $this->validatedTask($_POST, $projectId);
        if ($data === null) {
            redirect('/projects/view?project_id=' . $projectId);
        }
        try {
            (new Task())->create($projectId, $userId, $data);
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/projects/view?project_id=' . $projectId);
        }
        $_SESSION['flash_success'] = 'Task created.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function update(): void
    {
        verify_csrf();
        $taskId = $this->positiveId($_POST['task_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $taskModel = new Task();
        $task = $taskModel->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = (new ProjectsController())->requireVisible($projectId, $userId);
        $this->ensureProjectOpen($project);
        $this->requireProjectManager($projectId, $userId, 'tasks.create', 'tasks.assign');

        $data = $this->validatedTask($_POST, $projectId);
        if ($data === null) {
            redirect('/projects/view?project_id=' . $projectId);
        }
        try {
            $taskModel->update($taskId, $userId, $data);
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/projects/view?project_id=' . $projectId);
        }
        $_SESSION['flash_success'] = 'Task updated.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function status(): void
    {
        verify_csrf();
        $taskId = $this->positiveId($_POST['task_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $taskModel = new Task();
        $task = $taskModel->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = (new ProjectsController())->requireVisible($projectId, $userId);
        $this->ensureProjectOpen($project);

        $status = $_POST['status'] ?? null;
        if (!is_string($status) || !in_array($status, Task::STATUSES, true)) {
            $_SESSION['flash_error'] = 'Choose a valid task status.';
            redirect('/projects/view?project_id=' . $projectId);
        }
        $canManage = (new Project())->canManage($projectId, $userId);
        $canUpdateOwn = (new User())->hasPermission($userId, 'tasks.update_own') && $taskModel->isAssigned($taskId, $userId);
        if (!$canManage && !$canUpdateOwn) {
            $this->deny();
        }

        $taskModel->updateStatus($taskId, $status);
        $_SESSION['flash_success'] = 'Task status updated.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function addChecklist(): void
    {
        verify_csrf();
        $taskId = $this->positiveId($_POST['task_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $taskModel = new Task();
        $task = $taskModel->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = (new ProjectsController())->requireVisible($projectId, $userId);
        $this->ensureProjectOpen($project);
        $canManage = (new Project())->canManage($projectId, $userId);
        $canUpdateOwn = (new User())->hasPermission($userId, 'tasks.update_own') && $taskModel->isAssigned($taskId, $userId);
        if (!$canManage && !$canUpdateOwn) {
            $this->deny();
        }

        $title = $_POST['title'] ?? null;
        if (!is_string($title) || trim($title) === '' || strlen(trim($title)) > 220) {
            $_SESSION['flash_error'] = 'Checklist items must have a title of 1 to 220 characters.';
            redirect('/projects/view?project_id=' . $projectId);
        }
        $taskModel->addChecklistItem($taskId, trim($title));
        $_SESSION['flash_success'] = 'Checklist item added.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function toggleChecklist(): void
    {
        verify_csrf();
        $itemId = $this->positiveId($_POST['item_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $taskModel = new Task();
        $item = $taskModel->checklistItem($itemId);
        if (!$item) {
            $this->notFound();
        }
        $taskId = (int) $item['task_id'];
        $task = $taskModel->projectTask($taskId);
        if (!$task) {
            $this->notFound();
        }
        $projectId = (int) $task['project_id'];
        $project = (new ProjectsController())->requireVisible($projectId, $userId);
        $this->ensureProjectOpen($project);
        $canManage = (new Project())->canManage($projectId, $userId);
        $canUpdateOwn = (new User())->hasPermission($userId, 'tasks.update_own') && $taskModel->isAssigned($taskId, $userId);
        if (!$canManage && !$canUpdateOwn) {
            $this->deny();
        }

        $completedInput = $_POST['completed'] ?? null;
        if (!is_string($completedInput) || !in_array($completedInput, ['0', '1'], true)) {
            $_SESSION['flash_error'] = 'Choose a valid checklist state.';
            redirect('/projects/view?project_id=' . $projectId);
        }
        $completed = $completedInput === '1';
        $taskModel->toggleChecklist($itemId, $userId, $completed);
        redirect('/projects/view?project_id=' . $projectId);
    }

    private function requireProjectManager(int $projectId, int $userId, string ...$permissions): void
    {
        $userModel = new User();
        $project = (new ProjectsController())->requireVisible($projectId, $userId);
        $this->ensureProjectOpen($project);
        $hasAllPermissions = true;
        foreach ($permissions as $permission) {
            $hasAllPermissions = $hasAllPermissions && $userModel->hasPermission($userId, $permission);
        }
        if (!(new Project())->canManage($projectId, $userId) || !$hasAllPermissions) {
            $this->deny();
        }
    }

    private function ensureProjectOpen(array $project): void
    {
        if (in_array($project['status'], ['completed', 'archived'], true)) {
            $_SESSION['flash_error'] = 'This project is closed. Reopen it before updating tasks or checklists.';
            redirect('/projects/view?project_id=' . (int) $project['id']);
        }
    }

    private function validatedTask(array $input, int $projectId): ?array
    {
        $titleInput = $input['title'] ?? null;
        $descriptionInput = $input['description'] ?? '';
        $status = $input['status'] ?? 'todo';
        $priority = $input['priority'] ?? 'medium';
        if (
            !is_string($titleInput) || !is_string($descriptionInput)
            || !is_string($status) || !is_string($priority)
        ) {
            $_SESSION['flash_error'] = 'Enter valid task details.';
            return null;
        }
        $title = trim($titleInput);
        $description = trim($descriptionInput);
        $startDate = $this->validatedDate($input['start_date'] ?? '');
        $dueDate = $this->validatedDate($input['due_date'] ?? '');
        if (
            $title === '' || strlen($title) > 200 || strlen($description) > 20000
            || !in_array($status, Task::STATUSES, true) || !in_array($priority, Task::PRIORITIES, true)
            || $startDate === false || $dueDate === false
            || ($startDate !== null && $dueDate !== null && $startDate > $dueDate)
        ) {
            $_SESSION['flash_error'] = 'Enter a task title, valid dates in order, and valid priority and status.';
            return null;
        }

        $assignees = $this->validatedIds($input['assignees'] ?? []);
        $checklists = $this->validatedChecklistLines($input['checklist_lines'] ?? '');
        if ($assignees === null || $checklists === null) {
            $_SESSION['flash_error'] = 'Choose valid assignees and checklist items (each up to 220 characters).';
            return null;
        }
        $parentId = null;
        if (isset($input['parent_task_id']) && $input['parent_task_id'] !== '') {
            $parentId = $this->positiveId($input['parent_task_id']);
            $parent = (new Task())->projectTask($parentId);
            if (!$parent || (int) $parent['project_id'] !== $projectId) {
                $_SESSION['flash_error'] = 'A subtask must belong to the same project as its parent task.';
                return null;
            }
        }

        return [
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'status' => $status,
            'priority' => $priority,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'assignees' => $assignees,
            'checklists' => $checklists,
            'parent_task_id' => $parentId,
        ];
    }

    private function validatedIds(mixed $values): ?array
    {
        if (!is_array($values)) {
            return null;
        }
        $ids = [];
        foreach ($values as $value) {
            if (!is_string($value) && !is_int($value)) {
                return null;
            }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                return null;
            }
            $ids[] = (int) $id;
        }
        return array_values(array_unique($ids));
    }

    private function validatedChecklistLines(mixed $value): ?array
    {
        if (!is_string($value)) {
            return null;
        }
        $lines = preg_split('/\R/', $value) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (strlen($line) > 220) {
                return null;
            }
            $items[] = $line;
        }
        return count($items) > 50 ? null : $items;
    }

    private function validatedDate(mixed $value): string|null|false
    {
        if (!is_string($value) || $value === '') {
            return $value === '' ? null : false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (
            !$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            return false;
        }
        return $value;
    }

    private function projectId(mixed $value): int
    {
        return $this->positiveId($value);
    }

    private function positiveId(mixed $value): int
    {
        if (!is_string($value) && !is_int($value)) {
            $this->notFound();
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->notFound();
        }
        return (int) $id;
    }

    private function deny(): never
    {
        http_response_code(403);
        require BASE_PATH . '/app/Views/errors/403.php';
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require BASE_PATH . '/app/Views/errors/404.php';
        exit;
    }
}
