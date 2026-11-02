<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;

final class SetupController
{
    public function show(): void
    {
        if ((new User())->countAll() > 0) {
            http_response_code(403);
            exit('Initial setup is disabled because an account already exists.');
        }
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        require BASE_PATH . '/app/Views/auth/setup_admin.php';
    }

    public function create(): void
    {
        verify_csrf();

        $userModel = new User();
        if ($userModel->countAll() > 0) {
            http_response_code(403);
            exit('Initial setup is disabled because an account already exists.');
        }

        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $password !== $confirm) {
            $_SESSION['flash_error'] = 'Complete all fields. Use a password of at least 12 characters and ensure both passwords match.';
            redirect('/setup-admin');
        }

        $roleId = (int) Database::connection()->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if (!$roleId) throw new \RuntimeException('Administrator role is missing from the database.');

        $userModel->create([
            'role_id' => $roleId,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
        ]);

        redirect('/login');
    }
}
