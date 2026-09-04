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
            http_response_code(419);
            exit('Sessão do formulário expirada. Recarregue a página.');
        }
    }
}
