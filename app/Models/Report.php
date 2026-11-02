<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Report
{
    public function summary(int $userId): array
    {
        [$visibility, $params] = $this->projectVisibility($userId);
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT p.id) AS project_count,
                COUNT(DISTINCT CASE WHEN p.status = \'active\' THEN p.id END) AS active_projects,
                COUNT(t.id) AS task_count,
                COALESCE(SUM(CASE WHEN t.status = \'done\' THEN 1 ELSE 0 END), 0) AS completed_tasks,
                COALESCE(SUM(CASE WHEN t.status <> \'done\' AND t.due_date < CURRENT_DATE THEN 1 ELSE 0 END), 0) AS overdue_tasks,
                COALESCE(SUM(CASE WHEN t.status = \'todo\' THEN 1 ELSE 0 END), 0) AS todo_tasks,
                COALESCE(SUM(CASE WHEN t.status = \'in_progress\' THEN 1 ELSE 0 END), 0) AS in_progress_tasks
             FROM projects p
             LEFT JOIN tasks t ON t.project_id = p.id
             WHERE 1 = 1' . $visibility
        );
        $stmt->execute($params);
        $summary = $stmt->fetch() ?: [];
        $summary = array_map('intval', $summary);
        $summary['completion_rate'] = $summary['task_count'] > 0
            ? round($summary['completed_tasks'] * 100 / $summary['task_count'], 1)
            : 0.0;
        return $summary;
    }

    public function projects(int $userId): array
    {
        [$visibility, $params] = $this->projectVisibility($userId);
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.name, p.project_key, p.status, p.due_date,
                CONCAT(owner.first_name, \' \', owner.last_name) AS owner_name,
                COALESCE(task_stats.task_count, 0) AS task_count,
                COALESCE(task_stats.completed_tasks, 0) AS completed_tasks,
                COALESCE(task_stats.overdue_tasks, 0) AS overdue_tasks,
                COALESCE(member_stats.member_count, 0) AS member_count,
                CASE WHEN COALESCE(task_stats.task_count, 0) = 0 THEN 0
                     ELSE ROUND(task_stats.completed_tasks * 100 / task_stats.task_count, 1)
                END AS completion_rate
             FROM projects p
             INNER JOIN users owner ON owner.id = p.owner_id
             LEFT JOIN (
                SELECT project_id, COUNT(*) AS task_count,
                    SUM(CASE WHEN status = \'done\' THEN 1 ELSE 0 END) AS completed_tasks,
                    SUM(CASE WHEN status <> \'done\' AND due_date < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue_tasks
                FROM tasks GROUP BY project_id
             ) task_stats ON task_stats.project_id = p.id
             LEFT JOIN (
                SELECT project_id, COUNT(*) AS member_count
                FROM project_members GROUP BY project_id
             ) member_stats ON member_stats.project_id = p.id
             WHERE 1 = 1' . $visibility . '
             ORDER BY CASE WHEN p.status IN (\'completed\', \'archived\') THEN 1 ELSE 0 END,
                p.due_date IS NULL, p.due_date, p.name'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function teamPerformance(int $userId): array
    {
        [$visibility, $params] = $this->projectVisibility($userId);
        $stmt = Database::connection()->prepare(
            'SELECT u.id, CONCAT(u.first_name, \' \', u.last_name) AS user_name,
                COUNT(DISTINCT t.id) AS assigned_tasks,
                COUNT(DISTINCT CASE WHEN t.status = \'done\' THEN t.id END) AS completed_tasks,
                COUNT(DISTINCT CASE WHEN t.status <> \'done\' AND t.due_date < CURRENT_DATE THEN t.id END) AS overdue_tasks,
                ROUND(
                    100 * COUNT(DISTINCT CASE WHEN t.status = \'done\' THEN t.id END)
                    / NULLIF(COUNT(DISTINCT t.id), 0), 1
                ) AS completion_rate
             FROM task_assignments ta
             INNER JOIN tasks t ON t.id = ta.task_id
             INNER JOIN projects p ON p.id = t.project_id
             INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ta.user_id
             INNER JOIN users u ON u.id = ta.user_id AND u.status = \'active\'
             WHERE 1 = 1' . $visibility . '
             GROUP BY u.id, u.first_name, u.last_name
             ORDER BY completed_tasks DESC, assigned_tasks DESC, u.first_name, u.last_name'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function projectVisibility(int $userId): array
    {
        if ((new User())->isAdministrator($userId)) {
            return ['', []];
        }
        return [
            ' AND EXISTS (
                SELECT 1 FROM project_members visible_pm
                WHERE visible_pm.project_id = p.id AND visible_pm.user_id = :report_user_id
            )',
            ['report_user_id' => $userId],
        ];
    }
}
