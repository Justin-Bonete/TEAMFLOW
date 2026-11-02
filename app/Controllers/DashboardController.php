<?php
declare(strict_types=1);

namespace App\Controllers;

final class DashboardController
{
    public function index(): void
    {
        $name = $_SESSION['user_name'] ?? 'Team member';
        $role = $_SESSION['role'] ?? 'User';
        require BASE_PATH . '/app/Views/dashboard/index.php';
    }
}
