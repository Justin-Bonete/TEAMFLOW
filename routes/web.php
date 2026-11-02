<?php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SetupController;
use App\Middleware\AuthMiddleware;

$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/setup-admin', [SetupController::class, 'show']);
$router->post('/setup-admin', [SetupController::class, 'create']);
