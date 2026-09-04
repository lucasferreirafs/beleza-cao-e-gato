<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Env;
use App\Core\Session;


final class AuthMiddleware
{
    public function handle(Request $request, ?string $argument = null): void
    {
        if (
            Env::get('APP_ENV') === 'development' &&
            Env::bool('AUTH_BYPASS')
        ) {
            if (!Session::user()) {
                Session::login([
                    'id' => 1,
                    'name' => 'Usuário de Desenvolvimento',
                    'email' => 'dev@local.test',
                    'role' => Env::get('AUTH_BYPASS_ROLE', 'client'),
                    'status' => 'active',
                ]);
            }

            return;
        }

        if (!Session::user()) {
            Session::flash('error', 'Faça login para continuar.');
            Response::redirect('/login');
        }

        if (time() - (int) ($_SESSION['last_activity'] ?? 0) > 7200) {
            Session::logout();
            Response::redirect('/login');
        }
        
        $_SESSION['last_activity'] = time();
    }
}
