<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (role_id, first_name, last_name, email, password_hash, status)
             VALUES (:role_id, :first_name, :last_name, :email, :password_hash, :status)'
        );
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }
}
