<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Report;
use App\Models\User;

final class ReportsController
{
    public function index(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $report = new Report();
        $summary = $report->summary($userId);
        $projects = $report->projects($userId);
        $teamPerformance = $report->teamPerformance($userId);
        $userModel = new User();
        $canManageUsers = $userModel->hasPermission($userId, 'users.manage');
        $canManageRoles = $userModel->hasPermission($userId, 'roles.manage') && $userModel->isAdministrator($userId);
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        require BASE_PATH . '/app/Views/reports/index.php';
    }

    public function export(): void
    {
        $projects = (new Report())->projects((int) $_SESSION['user_id']);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="teamflow-project-report-' . date('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new \RuntimeException('The report export stream could not be opened.');
        }
        fputcsv($output, ['Project', 'Key', 'Status', 'Owner', 'Team members', 'Tasks', 'Completed tasks', 'Completion rate (%)', 'Overdue tasks', 'Due date']);
        foreach ($projects as $project) {
            fputcsv($output, [
                $this->spreadsheetSafe((string) $project['name']),
                $this->spreadsheetSafe((string) $project['project_key']),
                $this->spreadsheetSafe((string) $project['status']),
                $this->spreadsheetSafe((string) $project['owner_name']),
                (int) $project['member_count'],
                (int) $project['task_count'],
                (int) $project['completed_tasks'],
                (float) $project['completion_rate'],
                (int) $project['overdue_tasks'],
                (string) ($project['due_date'] ?? ''),
            ]);
        }
        fclose($output);
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }
}
