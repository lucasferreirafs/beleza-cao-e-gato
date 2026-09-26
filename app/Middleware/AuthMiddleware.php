<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{
    Request,
    Response,
    Env,
    Session
};


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

        $fresh = (new \App\Repositories\UserRepository())->findById((int) Session::user()['id']);
        if (!$fresh || $fresh['status'] !== 'active'
            || (int) $fresh['auth_version'] !== (int) (Session::user()['auth_version'] ?? 0)) {
            (new \App\Services\RememberMeService())->forget();
            Session::logout();
            Response::redirect('/login');
        }
        $remembered = (int) ($_SESSION['remember_until'] ?? 0) > time();
        $idleLimit = $remembered ? 60 * 60 * 24 * 30 : (int) Env::get('SESSION_IDLE_TIMEOUT', 7200);

        if ((isset($_SESSION['remember_until']) && !$remembered)
            || time() - (int) ($_SESSION['last_activity'] ?? 0) > $idleLimit) {
            (new \App\Services\RememberMeService())->forget();
            Session::logout();
            Response::redirect('/login');
        }
        
        Session::refreshUser($fresh);
    }
}
