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

    public function activeWorkspaceProfile(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT first_name, last_name, email, updated_at,
                    CASE
                        WHEN avatar_source = \'upload\' AND profile_image_path IS NOT NULL THEN 1
                        WHEN avatar_source = \'github\' AND github_avatar_path IS NOT NULL THEN 1
                        ELSE 0
                    END AS has_profile_avatar
             FROM users WHERE id = :user_id AND status = \'active\' LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function profileForSettings(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT first_name, last_name, email, password_hash, avatar_source,
                    profile_image_path, github_id, github_login, github_avatar_path
             FROM users WHERE id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function profileAvatarPath(int $userId): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT CASE avatar_source
                        WHEN \'upload\' THEN profile_image_path
                        WHEN \'github\' THEN github_avatar_path
                        ELSE NULL
                    END
             FROM users WHERE id = :user_id AND status = \'active\' LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $path = $stmt->fetchColumn();
        return is_string($path) && $path !== '' ? $path : null;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $sql = 'UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email';
        if (isset($data['password_hash'])) {
            $sql .= ', password_hash = :password_hash';
        }
        $sql .= ' WHERE id = :user_id AND status = \'active\'';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            ...isset($data['password_hash']) ? ['password_hash' => $data['password_hash']] : [],
        ]);
        if ($stmt->rowCount() === 0 && !$this->isActive($userId)) {
            throw new RuntimeException('The active account could not be found.');
        }
    }

    public function updateUploadedAvatar(int $userId, ?string $path): ?string
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'SELECT profile_image_path FROM users WHERE id = :user_id AND status = \'active\' FOR UPDATE'
            );
            $stmt->execute(['user_id' => $userId]);
            $previousPath = $stmt->fetchColumn();
            if ($previousPath === false) {
                throw new RuntimeException('The active account could not be found.');
            }

            if ($path !== null) {
                $stmt = $connection->prepare(
                    'UPDATE users SET profile_image_path = :path, avatar_source = \'upload\' WHERE id = :user_id'
                );
                $stmt->execute(['user_id' => $userId, 'path' => $path]);
            } else {
                $stmt = $connection->prepare(
                    'UPDATE users SET profile_image_path = NULL,
                        avatar_source = IF(github_id IS NULL OR github_avatar_path IS NULL, \'initials\', \'github\')
                     WHERE id = :user_id'
                );
                $stmt->execute(['user_id' => $userId]);
            }
            $connection->commit();
            return is_string($previousPath) ? $previousPath : null;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function setAvatarSource(int $userId, string $source): void
    {
        if (!in_array($source, ['initials', 'upload', 'github'], true)) {
            throw new RuntimeException('Choose a valid profile photo source.');
        }
        $profile = $this->profileForSettings($userId);
        if (!$profile) {
            throw new RuntimeException('The active account could not be found.');
        }
        $available = match ($source) {
            'initials' => true,
            'upload' => $profile['profile_image_path'] !== null,
            'github' => $profile['github_id'] !== null && $profile['github_avatar_path'] !== null,
        };
        if (!$available) {
            throw new RuntimeException('That profile photo is not available.');
        }
        $stmt = Database::connection()->prepare(
            'UPDATE users SET avatar_source = :source WHERE id = :user_id AND status = \'active\''
        );
        $stmt->execute(['user_id' => $userId, 'source' => $source]);
    }

    public function connectGithub(int $userId, int $githubId, string $githubLogin, string $avatarPath): ?string
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'SELECT id FROM users WHERE github_id = :github_id AND id <> :user_id LIMIT 1'
            );
            $stmt->execute(['github_id' => $githubId, 'user_id' => $userId]);
            if ($stmt->fetchColumn()) {
                throw new RuntimeException('That GitHub account is already connected to another TEAMFLOW account.');
            }

            $stmt = $connection->prepare(
                'SELECT github_avatar_path FROM users WHERE id = :user_id AND status = \'active\' FOR UPDATE'
            );
            $stmt->execute(['user_id' => $userId]);
            $previousPath = $stmt->fetchColumn();
            if ($previousPath === false) {
                throw new RuntimeException('The active account could not be found.');
            }

            $stmt = $connection->prepare(
                'UPDATE users SET github_id = :github_id, github_login = :github_login, github_avatar_path = :avatar_path,
                    avatar_source = \'github\' WHERE id = :user_id'
            );
            $stmt->execute([
                'user_id' => $userId,
                'github_id' => $githubId,
                'github_login' => $githubLogin,
                'avatar_path' => $avatarPath,
            ]);
            $connection->commit();
            return is_string($previousPath) ? $previousPath : null;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
    }

    public function disconnectGithub(int $userId): ?string
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                'SELECT github_avatar_path FROM users WHERE id = :user_id AND status = \'active\' FOR UPDATE'
            );
            $stmt->execute(['user_id' => $userId]);
            $previousPath = $stmt->fetchColumn();
            if ($previousPath === false) {
                throw new RuntimeException('The active account could not be found.');
            }

            $stmt = $connection->prepare(
                'UPDATE users SET github_id = NULL, github_login = NULL, github_avatar_path = NULL,
                    avatar_source = IF(avatar_source = \'github\',
                        IF(profile_image_path IS NULL, \'initials\', \'upload\'), avatar_source)
                 WHERE id = :user_id'
            );
            $stmt->execute(['user_id' => $userId]);
            $connection->commit();
            return is_string($previousPath) ? $previousPath : null;
        } catch (\Throwable $error) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $error;
        }
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
