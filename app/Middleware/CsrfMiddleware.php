<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Request;
use App\Security\Csrf;

final class CsrfMiddleware
{
    public function handle(Request $request, ?string $argument = null): void
    {
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
