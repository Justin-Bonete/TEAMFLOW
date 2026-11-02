<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class Project
{
    public function allVisibleTo(int $userId): array
    {
        if ((new User())->isAdministrator($userId)) {
            return Database::connection()->query(
                'SELECT p.*, CONCAT(u.first_name, \' \', u.last_name) AS owner_name,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.parent_task_id IS NULL) AS task_count
                 FROM projects p INNER JOIN users u ON u.id = p.owner_id
                 ORDER BY p.updated_at DESC, p.id DESC'
            )->fetchAll();
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.*, CONCAT(u.first_name, \' \', u.last_name) AS owner_name,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.parent_task_id IS NULL) AS task_count
             FROM projects p
             INNER JOIN users u ON u.id = p.owner_id
             INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :user_id
             ORDER BY p.updated_at DESC, p.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findVisible(int $projectId, int $userId): ?array
    {
        if ((new User())->isAdministrator($userId)) {
            $stmt = Database::connection()->prepare(
                'SELECT p.*, CONCAT(u.first_name, \' \', u.last_name) AS owner_name
                 FROM projects p INNER JOIN users u ON u.id = p.owner_id WHERE p.id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $projectId]);
        } else {
            $stmt = Database::connection()->prepare(
                'SELECT p.*, CONCAT(u.first_name, \' \', u.last_name) AS owner_name, pm.project_role AS member_role
                 FROM projects p
                 INNER JOIN users u ON u.id = p.owner_id
                 INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :user_id
                 WHERE p.id = :id LIMIT 1'
            );
            $stmt->execute(['user_id' => $userId, 'id' => $projectId]);
        }
        return $stmt->fetch() ?: null;
    }

    public function canManage(int $projectId, int $userId): bool
    {
        if ((new User())->isAdministrator($userId)) {
            return true;
        }
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id
             WHERE p.id = :project_id AND pm.user_id = :user_id AND pm.project_role = \'manager\'
             LIMIT 1'
        );
        $stmt->execute(['project_id' => $projectId, 'user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function activeUsers(): array
    {
        return Database::connection()->query(
            'SELECT u.id, u.first_name, u.last_name, u.email, r.name AS role_name,
                EXISTS (
                    SELECT 1 FROM role_permissions rp
                    INNER JOIN permissions p ON p.id = rp.permission_id
                    WHERE rp.role_id = u.role_id AND p.permission_key = \'projects.manage\'
                ) AS can_manage_projects
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.status = \'active\'
             ORDER BY u.first_name, u.last_name, u.id'
        )->fetchAll();
    }

    public function members(int $projectId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status, pm.project_role, pm.joined_at,
                EXISTS (
                    SELECT 1 FROM role_permissions rp
                    INNER JOIN permissions p ON p.id = rp.permission_id
                    WHERE rp.role_id = u.role_id AND p.permission_key = \'projects.manage\'
                ) AS can_manage_projects
             FROM project_members pm INNER JOIN users u ON u.id = pm.user_id
             WHERE pm.project_id = :project_id ORDER BY pm.project_role, u.first_name, u.last_name'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function create(array $data, int $ownerId): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $key = $this->uniqueKey($data['name']);
            $stmt = $connection->prepare(
                'INSERT INTO projects (name, project_key, description, owner_id, start_date, due_date, status)
                 VALUES (:name, :project_key, :description, :owner_id, :start_date, :due_date, :status)'
            );
            $stmt->execute([
                'name' => $data['name'],
                'project_key' => $key,
                'description' => $data['description'],
                'owner_id' => $ownerId,
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'status' => $data['status'],
            ]);
            $projectId = (int) $connection->lastInsertId();
            $stmt = $connection->prepare(
                'INSERT INTO project_members (project_id, user_id, project_role) VALUES (:project_id, :user_id, \'manager\')'
            );
            $stmt->execute(['project_id' => $projectId, 'user_id' => $ownerId]);
            $this->syncMembers($projectId, $data['members'], $ownerId);
            $connection->commit();
            return $projectId;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function update(int $projectId, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projects SET name = :name, description = :description, start_date = :start_date,
                due_date = :due_date, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'id' => $projectId,
            'name' => $data['name'],
            'description' => $data['description'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
        ]);
    }

    public function setMembers(int $projectId, array $members, array $managerIds, int $actorId): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare('SELECT owner_id FROM projects WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $projectId]);
            $ownerId = $stmt->fetchColumn();
            if ($ownerId === false) {
                throw new RuntimeException('The project no longer exists.');
            }
            $this->syncMembers($projectId, $members, $actorId, (int) $ownerId, $managerIds);
            $connection->commit();
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    private function syncMembers(
        int $projectId,
        array $members,
        int $actorId,
        ?int $ownerId = null,
        array $managerIds = []
    ): void
    {
        $connection = Database::connection();
        $memberIds = array_values(array_unique(array_filter(array_map('intval', $members), static fn (int $id): bool => $id > 0)));
        $managerIds = array_values(array_unique(array_filter(array_map('intval', $managerIds), static fn (int $id): bool => $id > 0)));
        if ($ownerId !== null) {
            $memberIds = array_values(array_diff($memberIds, [$ownerId]));
        }
        if (array_diff($managerIds, $memberIds)) {
            throw new RuntimeException('Project managers must also be selected as project members.');
        }

        if ($memberIds) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $stmt = $connection->prepare(
                "SELECT id FROM users WHERE status = 'active' AND id IN ({$placeholders})"
            );
            $stmt->execute($memberIds);
            if (count($stmt->fetchAll()) !== count($memberIds)) {
                throw new RuntimeException('Choose only active user accounts as project members.');
            }
            if ($managerIds) {
                $placeholders = implode(',', array_fill(0, count($managerIds), '?'));
                $stmt = $connection->prepare(
                    "SELECT u.id FROM users u
                     INNER JOIN role_permissions rp ON rp.role_id = u.role_id
                     INNER JOIN permissions p ON p.id = rp.permission_id
                     WHERE u.status = 'active' AND p.permission_key = 'projects.manage'
                       AND u.id IN ({$placeholders})"
                );
                $stmt->execute($managerIds);
                if (count($stmt->fetchAll()) !== count($managerIds)) {
                    throw new RuntimeException('Only active users with project-management permission can be project managers.');
                }
            }
        }

        if ($ownerId === null) {
            $stmt = $connection->prepare('SELECT user_id FROM project_members WHERE project_id = :project_id');
            $stmt->execute(['project_id' => $projectId]);
            $existingIds = array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
            $ownerId = $actorId;
            $memberIds = array_values(array_unique([...$memberIds, $ownerId]));
        } else {
            $stmt = $connection->prepare('SELECT user_id FROM project_members WHERE project_id = :project_id');
            $stmt->execute(['project_id' => $projectId]);
            $existingIds = array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
            $memberIds[] = $ownerId;
        }

        $removedIds = array_values(array_diff($existingIds, $memberIds));
        if ($removedIds) {
            $placeholders = implode(',', array_fill(0, count($removedIds), '?'));
            $stmt = $connection->prepare(
                "DELETE FROM task_assignments
                 WHERE user_id IN ({$placeholders})
                   AND task_id IN (SELECT id FROM tasks WHERE project_id = ?)"
            );
            $stmt->execute([...$removedIds, $projectId]);
            $stmt = $connection->prepare(
                "DELETE FROM project_members WHERE project_id = ? AND user_id IN ({$placeholders})"
            );
            $stmt->execute([$projectId, ...$removedIds]);
        }

        $stmt = $connection->prepare(
            'SELECT 1 FROM project_members WHERE project_id = :project_id AND user_id = :user_id LIMIT 1'
        );
        $update = $connection->prepare(
            'UPDATE project_members SET project_role = :project_role WHERE project_id = :project_id AND user_id = :user_id'
        );
        $insert = $connection->prepare(
            'INSERT INTO project_members (project_id, user_id, project_role) VALUES (:project_id, :user_id, :project_role)'
        );
        foreach (array_unique($memberIds) as $memberId) {
            $membership = [
                'project_id' => $projectId,
                'user_id' => $memberId,
                'project_role' => $ownerId === $memberId || in_array($memberId, $managerIds, true) ? 'manager' : 'member',
            ];
            $stmt->execute(['project_id' => $projectId, 'user_id' => $memberId]);
            ($stmt->fetchColumn() ? $update : $insert)->execute($membership);
        }
    }

    private function uniqueKey(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?? '');
        $base = substr($base !== '' ? $base : 'PRJ', 0, 14);
        $stmt = Database::connection()->prepare('SELECT 1 FROM projects WHERE project_key = :project_key LIMIT 1');
        $key = $base;
        $suffix = 2;
        while (true) {
            $stmt->execute(['project_key' => $key]);
            if (!$stmt->fetchColumn()) {
                return $key;
            }
            $ending = (string) $suffix++;
            $key = substr($base, 0, 20 - strlen($ending)) . $ending;
        }
    }
}
