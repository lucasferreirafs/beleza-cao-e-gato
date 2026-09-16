<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{
    Request,
    Session,
    View
};

final class RoleMiddleware
{
    public function handle(Request $request, ?string $argument = null): void
    {
        $allowed = explode(',', $argument ?? '');
        if (!in_array(Session::user()['role'] ?? '', $allowed, true)) {
            http_response_code(403);
            View::render('pages/errors/403', ['title' => 'Acesso negado'], 'auth');
            exit;
        }
    }
}
