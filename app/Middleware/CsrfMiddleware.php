<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Request;
use App\Security\Csrf;

final class CsrfMiddleware
{
    public function handle(Request $request, ?string $argument = null): void
    {
        $limit = trim((string) ini_get('post_max_size'));
        $bytes = (int) $limit * match (strtolower(substr($limit, -1))) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
        if ($bytes > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $bytes && empty($_POST)) {
            http_response_code(413);
            \App\Core\View::render('pages/errors/upload', ['title' => 'Arquivo muito grande'], \App\Core\Session::user() ? 'app' : 'auth');
            exit;
        }
        if (!is_string($request->input('_token'))) {
            http_response_code(419);
            exit('Sessão do formulário expirada. Recarregue a página.');
        }
        if (!Csrf::validate((string) $request->input('_token'))) {
            $protocol = preg_match(
                '/^HTTP\/\d(?:\.\d)?$/',
                (string) ($_SERVER['SERVER_PROTOCOL'] ?? '')
            ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

            header($protocol . ' 419 Page Expired', true);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('Sessão do formulário expirada. Recarregue a página.');
        }
    }
}
