<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class Task
{
    public const STATUSES = ['todo', 'in_progress', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function forProject(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, GROUP_CONCAT(DISTINCT CONCAT(assignee.first_name, \' \', assignee.last_name) ORDER BY assignee.first_name SEPARATOR \', \') AS assignees,
                GROUP_CONCAT(DISTINCT ta.user_id) AS assignee_ids,
                (SELECT COUNT(*) FROM task_checklists c WHERE c.task_id = t.id) AS checklist_count,
                (SELECT COUNT(*) FROM task_checklists c WHERE c.task_id = t.id AND c.is_completed = 1) AS checklist_done
             FROM tasks t
             LEFT JOIN task_assignments ta ON ta.task_id = t.id
             LEFT JOIN users assignee ON assignee.id = ta.user_id
             WHERE t.project_id = :project_id AND t.parent_task_id IS NULL
             GROUP BY t.id
             ORDER BY FIELD(t.status, \'todo\', \'in_progress\', \'done\'), t.position, t.due_date, t.id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $tasks = $stmt->fetchAll();

        $children = $this->subtasksForProject($projectId);
        foreach ($tasks as &$task) {
            $task['subtasks'] = $children[(int) $task['id']] ?? [];
        }
        unset($task);
        return $tasks;
    }

    public function projectTask(int $taskId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tasks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $taskId]);
        return $stmt->fetch() ?: null;
    }

    public function isAssigned(int $taskId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM task_assignments WHERE task_id = :task_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function checklist(int $taskId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, CONCAT(u.first_name, \' \', u.last_name) AS completed_by_name
             FROM task_checklists c
             LEFT JOIN users u ON u.id = c.completed_by
             WHERE c.task_id = :task_id ORDER BY c.position, c.id'
        );
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function checklistsForProject(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, CONCAT(u.first_name, \' \', u.last_name) AS completed_by_name
             FROM task_checklists c
             INNER JOIN tasks t ON t.id = c.task_id
             LEFT JOIN users u ON u.id = c.completed_by
             WHERE t.project_id = :project_id
             ORDER BY c.task_id, c.position, c.id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $items = [];
        foreach ($stmt->fetchAll() as $item) {
            $items[(int) $item['task_id']][] = $item;
        }
        return $items;
    }

    public function checklistItem(int $itemId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, task_id FROM task_checklists WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $itemId]);
        return $stmt->fetch() ?: null;
    }

    public function dashboardStats(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS assigned,
                SUM(CASE WHEN t.status = \'done\' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN t.status <> \'done\' AND t.due_date < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue
             FROM task_assignments ta
             INNER JOIN tasks t ON t.id = ta.task_id
             WHERE ta.user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch() ?: [];
        return [
            'assigned' => (int) ($row['assigned'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'overdue' => (int) ($row['overdue'] ?? 0),
        ];
    }

    public function assignedToUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, p.project_key, p.name AS project_name, p.status AS project_status,
                (SELECT COUNT(*) FROM task_checklists c WHERE c.task_id = t.id) AS checklist_count,
                (SELECT COUNT(*) FROM task_checklists c WHERE c.task_id = t.id AND c.is_completed = 1) AS checklist_done
             FROM task_assignments ta
             INNER JOIN tasks t ON t.id = ta.task_id
             INNER JOIN projects p ON p.id = t.project_id
             INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ta.user_id
             WHERE ta.user_id = :user_id
             ORDER BY CASE WHEN t.status = \'done\' THEN 1 ELSE 0 END,
                t.due_date IS NULL, t.due_date, t.id'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $projectId, int $actorId, array $data): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'INSERT INTO tasks (project_id, parent_task_id, created_by, title, description, status, priority, start_date, due_date)
                 VALUES (:project_id, :parent_task_id, :created_by, :title, :description, :status, :priority, :start_date, :due_date)'
            );
            $stmt->execute([
                'project_id' => $projectId,
                'parent_task_id' => $data['parent_task_id'],
                'created_by' => $actorId,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
            ]);
            $taskId = (int) $connection->lastInsertId();
            $this->syncAssignments($taskId, $data['assignees'], $actorId);
            foreach ($data['checklists'] as $position => $title) {
                $stmt = $connection->prepare(
                    'INSERT INTO task_checklists (task_id, title, position) VALUES (:task_id, :title, :position)'
                );
                $stmt->execute(['task_id' => $taskId, 'title' => $title, 'position' => $position]);
            }
            $connection->commit();
            return $taskId;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function update(int $taskId, int $actorId, array $data): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'UPDATE tasks SET title = :title, description = :description, status = :status,
                    priority = :priority, start_date = :start_date, due_date = :due_date,
                    completed_at = CASE WHEN :completed_status = \'done\' THEN COALESCE(completed_at, CURRENT_TIMESTAMP) ELSE NULL END
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $taskId,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'completed_status' => $data['status'],
            ]);
            $this->syncAssignments($taskId, $data['assignees'], $actorId);
            $connection->commit();
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function updateStatus(int $taskId, string $status): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tasks SET status = :status,
                completed_at = CASE WHEN :completed_status = \'done\' THEN COALESCE(completed_at, CURRENT_TIMESTAMP) ELSE NULL END
             WHERE id = :id'
        );
        $stmt->execute(['id' => $taskId, 'status' => $status, 'completed_status' => $status]);
    }

    public function addChecklistItem(int $taskId, string $title): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO task_checklists (task_id, title, position)
             SELECT :task_id, :title, COALESCE(MAX(position), -1) + 1 FROM task_checklists WHERE task_id = :task_id_for_max'
        );
        $stmt->execute(['task_id' => $taskId, 'title' => $title, 'task_id_for_max' => $taskId]);
    }

    public function toggleChecklist(int $itemId, int $actorId, bool $completed): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE task_checklists SET is_completed = :completed,
                completed_by = CASE WHEN :completed_by_flag = 1 THEN :actor_id ELSE NULL END,
                completed_at = CASE WHEN :completed_at_flag = 1 THEN CURRENT_TIMESTAMP ELSE NULL END
             WHERE id = :id'
        );
        $value = $completed ? 1 : 0;
        $stmt->execute([
            'id' => $itemId,
            'completed' => $value,
            'completed_by_flag' => $value,
            'actor_id' => $actorId,
            'completed_at_flag' => $value,
        ]);
    }

    private function subtasksForProject(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, GROUP_CONCAT(DISTINCT CONCAT(u.first_name, \' \', u.last_name) ORDER BY u.first_name SEPARATOR \', \') AS assignees,
                GROUP_CONCAT(DISTINCT ta.user_id) AS assignee_ids
             FROM tasks t
             LEFT JOIN task_assignments ta ON ta.task_id = t.id
             LEFT JOIN users u ON u.id = ta.user_id
             WHERE t.project_id = :project_id AND t.parent_task_id IS NOT NULL
             GROUP BY t.id ORDER BY t.position, t.id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $result = [];
        foreach ($stmt->fetchAll() as $task) {
            $result[(int) $task['parent_task_id']][] = $task;
        }
        return $result;
    }

    private function syncAssignments(int $taskId, array $assignees, int $actorId): void
    {
        $connection = Database::connection();
        $stmt = $connection->prepare('SELECT project_id FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);
        $projectId = $stmt->fetchColumn();
        if ($projectId === false) {
            throw new RuntimeException('The task no longer exists.');
        }

        $assigneeIds = array_values(array_unique(array_filter(array_map('intval', $assignees), static fn (int $id): bool => $id > 0)));
        if ($assigneeIds) {
            $placeholders = implode(',', array_fill(0, count($assigneeIds), '?'));
            $stmt = $connection->prepare(
                "SELECT pm.user_id FROM project_members pm INNER JOIN users u ON u.id = pm.user_id
                 WHERE pm.project_id = ? AND u.status = 'active'
                   AND pm.project_role IN ('manager', 'member') AND pm.user_id IN ({$placeholders})"
            );
            $stmt->execute([$projectId, ...$assigneeIds]);
            if (count($stmt->fetchAll()) !== count($assigneeIds)) {
                throw new RuntimeException('Tasks can only be assigned to active members of this project.');
            }
        }

        $stmt = $connection->prepare('DELETE FROM task_assignments WHERE task_id = :task_id');
        $stmt->execute(['task_id' => $taskId]);
        $stmt = $connection->prepare(
            'INSERT INTO task_assignments (task_id, user_id, assigned_by) VALUES (:task_id, :user_id, :assigned_by)'
        );
        foreach ($assigneeIds as $assigneeId) {
            $stmt->execute(['task_id' => $taskId, 'user_id' => $assigneeId, 'assigned_by' => $actorId]);
        }
    }
}
