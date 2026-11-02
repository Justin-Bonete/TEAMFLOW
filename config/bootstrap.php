<?php
declare(strict_types=1);

use App\Core\Env;

Env::load(BASE_PATH . '/.env');

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('TEAMFLOW_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return $_SESSION['_csrf'] ?? '';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your session token is invalid or expired. Refresh the page and try again.');
    }
}

function redirect(string $path): never
{
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');
    header('Location: ' . $base . $path);
    exit;
}
