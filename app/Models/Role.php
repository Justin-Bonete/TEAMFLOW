<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class Role
{
    public function allWithPermissions(): array
    {
        $rows = Database::connection()->query(
            'SELECT r.id, r.name, r.description, p.id AS permission_id
             FROM roles r
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             LEFT JOIN permissions p ON p.id = rp.permission_id
             ORDER BY r.id, p.id'
        )->fetchAll();

        $roles = [];
        foreach ($rows as $row) {
            $roleId = (int) $row['id'];
            if (!isset($roles[$roleId])) {
                $roles[$roleId] = [
                    'id' => $roleId,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'permission_ids' => [],
                ];
            }
            if ($row['permission_id'] !== null) {
                $roles[$roleId]['permission_ids'][] = (int) $row['permission_id'];
            }
        }

        return array_values($roles);
    }

    public function permissions(): array
    {
        return Database::connection()->query(
            'SELECT id, permission_key, description FROM permissions ORDER BY permission_key'
        )->fetchAll();
    }

    public function create(string $name, ?string $description, array $permissionIds): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $stmt = $connection->prepare('INSERT INTO roles (name, description) VALUES (:name, :description)');
            $stmt->execute(['name' => $name, 'description' => $description]);
            $roleId = (int) $connection->lastInsertId();
            $this->replacePermissions($roleId, $permissionIds);
            $connection->commit();
            return $roleId;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function update(int $roleId, string $name, ?string $description, array $permissionIds): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $stmt = $connection->prepare('SELECT name FROM roles WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $roleId]);
            $currentName = $stmt->fetchColumn();
            if ($currentName === false) {
                throw new RuntimeException('The selected role no longer exists.');
            }
            if ($currentName === 'Administrator') {
                throw new RuntimeException('The built-in Administrator role cannot be changed.');
            }

            $stmt = $connection->prepare(
                'UPDATE roles SET name = :name, description = :description WHERE id = :id'
            );
            $stmt->execute(['id' => $roleId, 'name' => $name, 'description' => $description]);
            $this->replacePermissions($roleId, $permissionIds);
            $connection->commit();
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    private function replacePermissions(int $roleId, array $permissionIds): void
    {
        $connection = Database::connection();
        $stmt = $connection->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
        $stmt->execute(['role_id' => $roleId]);

        if (!$permissionIds) {
            return;
        }

        $stmt = $connection->prepare(
            'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
        );
        foreach ($permissionIds as $permissionId) {
            $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }
}
