<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;

final class AuthMiddleware
{
    public function handle(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            redirect('/login');
        }

        if (!(new User())->isActive($userId)) {
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user_name']);
            $_SESSION['flash_error'] = 'Your account is inactive. Contact an administrator for access.';
            redirect('/login');
        }
    }
}
