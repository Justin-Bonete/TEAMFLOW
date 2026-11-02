<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;

final class RoleManagementMiddleware
{
    public function handle(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            redirect('/login');
        }

        $userModel = new User();
        if (
            !$userModel->hasPermission($userId, 'roles.manage')
            || !$userModel->isAdministrator($userId)
        ) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}
