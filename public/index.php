<?php
declare(strict_types=1);

use App\Core\{Env, Request, Router, Session, View};

$root = dirname(__DIR__);
$uriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicFile = realpath(__DIR__ . $uriPath);

if ($uriPath !== '/' && $publicFile !== false && str_starts_with($publicFile, __DIR__ . DIRECTORY_SEPARATOR) && is_file($publicFile)) {
    $types = ['css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8', 'png' => 'image/png', 'svg' => 'image/svg+xml'];
    header('Content-Type: ' . ($types[pathinfo($publicFile, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($publicFile);
    exit;
}

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
ini_set('display_errors', Env::bool('APP_DEBUG') ? '1' : '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

Session::start();

set_exception_handler(function (Throwable $e): void {
    error_log('Unhandled application error: ' . get_debug_type($e) . ' code=' . (string) $e->getCode());
    http_response_code(500);
    View::render('pages/errors/500', ['title' => 'Erro interno'], 'auth');
});

$router = new Router();
(new \App\Services\RememberMeService())->restore();
require $root . '/routes/web.php';
\App\Services\AppointmentLifecycleService::beginRequest();
try {
    $router->dispatch(new Request());
} finally {
    \App\Services\AppointmentLifecycleService::endRequest();
}
