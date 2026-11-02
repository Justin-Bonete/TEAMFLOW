<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\Collaboration;
use App\Models\Task;
use App\Models\User;

final class ProjectsController
{
    private const STATUSES = ['planning', 'active', 'on_hold', 'completed', 'archived'];

    public function index(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $userModel = new User();
        $projects = (new Project())->allVisibleTo($userId);
        $canCreate = $userModel->hasPermission($userId, 'projects.create');
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        $activeUsers = $canCreate ? (new Project())->activeUsers() : [];
        require BASE_PATH . '/app/Views/projects/index.php';
    }

    public function create(): void
    {
        verify_csrf();
        $userId = (int) $_SESSION['user_id'];
        if (!(new User())->hasPermission($userId, 'projects.create')) {
            $this->deny();
        }

        $data = $this->validatedProject($_POST);
        if ($data === null) {
            redirect('/projects');
        }
        try {
            $projectId = (new Project())->create($data, $userId);
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/projects');
        }

        (new Collaboration())->projectUpdated($projectId, $userId, 'project.created', ['name' => $data['name']]);
        $_SESSION['flash_success'] = 'Project created.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function view(): void
    {
        $projectId = $this->projectId($_GET['project_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $projectModel = new Project();
        $project = $projectModel->findVisible($projectId, $userId);
        if (!$project) {
            $this->notFound();
        }

        $userModel = new User();
        $canManage = $projectModel->canManage($projectId, $userId);
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $canCreateTasks = $canManage && $userModel->hasPermission($userId, 'tasks.create');
        $canAssignTasks = $canManage
            && $userModel->hasPermission($userId, 'tasks.create')
            && $userModel->hasPermission($userId, 'tasks.assign');
        $canEditProject = $canManage && $userModel->hasPermission($userId, 'projects.manage');
        $isTaskMember = in_array($project['member_role'] ?? '', ['member', 'manager'], true);
        $canCollaborate = $canManage || $isTaskMember;
        $members = $projectModel->members($projectId);
        $tasks = (new Task())->forProject($projectId);
        $checklists = (new Task())->checklistsForProject($projectId);
        $collaboration = new Collaboration();
        $comments = $collaboration->commentsForProject($projectId);
        $attachments = $collaboration->attachmentsForProject($projectId);
        $activities = $collaboration->activities($projectId);
        $availableMembers = $projectModel->activeUsers();
        $activeUsers = array_values(array_filter(
            $members,
            static fn (array $member): bool => $member['status'] === 'active'
                && in_array($member['project_role'], ['manager', 'member'], true)
        ));
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        require BASE_PATH . '/app/Views/projects/view.php';
    }

    public function update(): void
    {
        verify_csrf();
        $projectId = $this->projectId($_POST['project_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $projectModel = new Project();
        if (!$projectModel->findVisible($projectId, $userId)) {
            $this->notFound();
        }
        if (!$projectModel->canManage($projectId, $userId) || !(new User())->hasPermission($userId, 'projects.manage')) {
            $this->deny();
        }

        $data = $this->validatedProject($_POST);
        if ($data === null) {
            redirect('/projects/view?project_id=' . $projectId);
        }
        $projectModel->update($projectId, $data);
        (new Collaboration())->projectUpdated($projectId, $userId, 'project.updated', ['name' => $data['name']]);
        $_SESSION['flash_success'] = 'Project details updated.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function members(): void
    {
        verify_csrf();
        $projectId = $this->projectId($_POST['project_id'] ?? null);
        $userId = (int) $_SESSION['user_id'];
        $projectModel = new Project();
        if (!$projectModel->findVisible($projectId, $userId)) {
            $this->notFound();
        }
        if (!$projectModel->canManage($projectId, $userId) || !(new User())->hasPermission($userId, 'projects.manage')) {
            $this->deny();
        }

        $members = $this->validatedIds($_POST['members'] ?? []);
        $managers = $this->validatedIds($_POST['managers'] ?? []);
        if ($members === null || $managers === null) {
            $_SESSION['flash_error'] = 'Choose valid active project members.';
            redirect('/projects/view?project_id=' . $projectId);
        }
        try {
            $projectModel->setMembers($projectId, $members, $managers, $userId);
        } catch (\RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            redirect('/projects/view?project_id=' . $projectId);
        }
        (new Collaboration())->projectUpdated($projectId, $userId, 'project.members_updated', ['members' => count($members)]);
        $_SESSION['flash_success'] = 'Project membership updated.';
        redirect('/projects/view?project_id=' . $projectId);
    }

    public function canManage(int $projectId, int $userId): bool
    {
        return (new Project())->canManage($projectId, $userId);
    }

    public function requireVisible(int $projectId, int $userId): array
    {
        $project = (new Project())->findVisible($projectId, $userId);
        if (!$project) {
            $this->notFound();
        }
        return $project;
    }

    private function validatedProject(array $input): ?array
    {
        $nameInput = $input['name'] ?? null;
        $descriptionInput = $input['description'] ?? '';
        if (!is_string($nameInput) || !is_string($descriptionInput)) {
            $_SESSION['flash_error'] = 'Enter valid project details.';
            return null;
        }
        $name = trim($nameInput);
        $description = trim($descriptionInput);
        $startDate = $this->validatedDate($input['start_date'] ?? '');
        $dueDate = $this->validatedDate($input['due_date'] ?? '');
        $status = $input['status'] ?? 'planning';
        if (
            $name === '' || strlen($name) > 180 || strlen($description) > 20000
            || $startDate === false || $dueDate === false
            || !is_string($status) || !in_array($status, self::STATUSES, true)
            || ($startDate !== null && $dueDate !== null && $startDate > $dueDate)
        ) {
            $_SESSION['flash_error'] = 'Enter a project name, valid dates in order, and a valid project status.';
            return null;
        }

        $members = $this->validatedIds($input['members'] ?? []);
        if ($members === null) {
            $_SESSION['flash_error'] = 'Choose valid active users for project membership.';
            return null;
        }
        return [
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'status' => $status,
            'members' => $members,
        ];
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

    private function projectId(mixed $value): int
    {
        if (!is_string($value) && !is_int($value)) {
            $this->notFound();
        }
        $projectId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($projectId === false) {
            $this->notFound();
        }
        return (int) $projectId;
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
