<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\Collaboration;
use App\Models\Report;

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
        $report = new Report();
        $summary = $report->summary($userId);
        $dashboardProjects = array_slice($report->projects($userId), 0, 5);
        $assignedTasks = (new Task())->assignedToUser($userId);
        $dashboardBoard = ['todo' => [], 'in_progress' => [], 'done' => []];
        foreach ($assignedTasks as $task) {
            if (isset($dashboardBoard[$task['status']])) {
                $dashboardBoard[$task['status']][] = $task;
            }
        }
        $dashboardBoardCounts = array_map('count', $dashboardBoard);
        foreach ($dashboardBoard as &$columnTasks) {
            $columnTasks = array_slice($columnTasks, 0, 4);
        }
        unset($columnTasks);
        $collaboration = new Collaboration();
        $recentNotifications = $collaboration->notifications($userId, 4);
        $canUpdateOwnTasks = $userModel->hasPermission($userId, 'tasks.update_own');
        require BASE_PATH . '/app/Views/dashboard/index.php';
    }
}
