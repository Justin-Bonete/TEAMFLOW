<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;

final class DashboardController
{
    public function index(): void
    {
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        $userModel = new User();
        $userId = (int) $_SESSION['user_id'];
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $visibleProjects = (new Project())->allVisibleTo($userId);
        $activeProjects = count(array_filter($visibleProjects, static fn (array $project): bool => $project['status'] === 'active'));
        $taskStats = (new Task())->dashboardStats($userId);
        require BASE_PATH . '/app/Views/dashboard/index.php';
    }
}
