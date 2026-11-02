<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class User
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.role_id, u.first_name, u.last_name, u.email, u.password_hash, u.status, r.name AS role_name
             FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function countAll(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function allWithRoles(): array
    {
        return Database::connection()->query(
            'SELECT u.id, u.role_id, u.first_name, u.last_name, u.email, u.status, u.created_at, r.name AS role_name
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC, u.id DESC'
        )->fetchAll();
    }

    public function roles(): array
    {
        return Database::connection()->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM users u
             INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :user_id AND u.status = \'active\' AND p.permission_key = :permission
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'permission' => $permission]);
        return (bool) $stmt->fetchColumn();
    }

    public function isActive(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM users WHERE id = :user_id AND status = \'active\' LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function isAdministrator(int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :user_id AND u.status = \'active\' AND r.name = \'Administrator\'
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function hasRole(int $userId, string $roleName): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :user_id AND r.name = :role_name
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'role_name' => $roleName]);
        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptUserId !== null) {
            $sql .= ' AND id <> :user_id';
            $params['user_id'] = $exceptUserId;
        }

        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (role_id, first_name, last_name, email, password_hash, status)
             VALUES (:role_id, :first_name, :last_name, :email, :password_hash, :status)'
        );
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateManagedAccount(int $userId, array $data, bool $allowAdministratorChanges): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $activeAdmins = $connection->query(
                'SELECT u.id
                 FROM users u INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.status = \'active\' AND r.name = \'Administrator\'
                 ORDER BY u.id FOR UPDATE'
            )->fetchAll();

            $stmt = $connection->prepare(
                'SELECT u.status, r.name AS role_name
                 FROM users u INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $userId]);
            $current = $stmt->fetch();
            if (!$current) {
                throw new RuntimeException('The selected account no longer exists.');
            }

            if (
                !$allowAdministratorChanges
                && ($current['role_name'] === 'Administrator' || $data['role_name'] === 'Administrator')
            ) {
                throw new RuntimeException('Only an active Administrator can manage Administrator accounts.');
            }

            if (
                $current['status'] === 'active'
                && $current['role_name'] === 'Administrator'
                && ($data['status'] !== 'active' || $data['role_name'] !== 'Administrator')
                && count($activeAdmins) <= 1
            ) {
                throw new RuntimeException('The last active administrator cannot be deactivated or demoted.');
            }

            $sql = 'UPDATE users
                    SET role_id = :role_id, first_name = :first_name, last_name = :last_name,
                        email = :email, status = :status';
            if (isset($data['password_hash'])) {
                $sql .= ', password_hash = :password_hash';
            }
            $sql .= ' WHERE id = :id';

            $stmt = $connection->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'role_id' => $data['role_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'status' => $data['status'],
                ...isset($data['password_hash']) ? ['password_hash' => $data['password_hash']] : [],
            ]);
            $connection->commit();
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }
}
