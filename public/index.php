<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoload.php';
require BASE_PATH . '/config/bootstrap.php';

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH) ?: '', '/');

if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');
if ($uri === '//') $uri = '/';

try {
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<h1>Application error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        require BASE_PATH . '/app/Views/errors/500.php';
    }
}
