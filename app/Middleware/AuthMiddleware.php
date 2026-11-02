<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;

final class AuthMiddleware
{
    public function handle(): void
    {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            redirect('/login');
        }

        if (!(new User())->isActive($userId)) {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user_name']);
            session_regenerate_id(true);
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['flash_error'] = 'Your account is inactive. Contact an administrator for access.';
            redirect('/login');
        }
    }
}
