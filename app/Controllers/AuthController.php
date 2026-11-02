<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) redirect('/dashboard');
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        require BASE_PATH . '/app/Views/auth/login.php';
    }

    public function login(): void
    {
        verify_csrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $_SESSION['flash_error'] = 'Enter a valid email address and password.';
            redirect('/login');
        }

        $user = (new User())->findByEmail($email);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            usleep(250000);
            $_SESSION['flash_error'] = 'Invalid credentials or inactive account.';
            redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

        redirect('/dashboard');
    }

    public function logout(): void
    {
        verify_csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('/login');
    }
}
