<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $this->routes[$method][$this->normalize($path)] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method][$this->normalize($path)] ?? null;
        if (!$route) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }

        foreach ($route['middleware'] as $middlewareClass) {
            (new $middlewareClass())->handle();
        }

        $handler = $route['handler'];
        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$class, $action] = $handler;
        (new $class())->$action();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
