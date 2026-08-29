<?php

declare(strict_types=1);
use App\Core\{Env, Request, Router, Session, View};

$root = dirname(__DIR__);
$uriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicFile = realpath(__DIR__ . $uriPath);

if ($uriPath !== '/' && $publicFile !== false && str_starts_with($publicFile, __DIR__) && is_file($publicFile)) {
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'svg' => 'image/svg+xml'
    ];

    header('Content-Type: ' . ($types[pathinfo($publicFile, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($publicFile);
    exit;
}

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));

ini_set('display_errors', Env::bool('APP_DEBUG') ? '1' : '0');

Session::start();

set_exception_handler(function (Throwable $e): void {
    error_log($e->__toString());
    http_response_code(500);
    View::render('pages/errors/500', ['title' => 'Erro interno'], 'auth');
});

$router = new Router();

require $root . '/routes/web.php';

$router->dispatch(new Request());

?>