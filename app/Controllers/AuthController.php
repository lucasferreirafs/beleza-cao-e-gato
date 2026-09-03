<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Session, Response, View};
use App\Services\AuthService;

final class AuthController
{
    public function landing(Request $request): void
    {
        if (Session::user())
            Response::redirect('/dashboard');

        View::render(
            'pages/landing',
            ['tilte' => 'Gestão inteligente para pet shops'],
            'auth'
        );
    }

    public function loginForm(Request $request): void
    {
        if(Session::user()) Response::redirect('/dashboard');

        View::render(
            'pages/auth/login',
            [
                'title' => 'Entrar', 
                'error' => Session::flash('error')
            ], 
            'auth'
        );
    }
    
    public function registerForm(Request $r): void
    {
        View::render(
            'pages/auth/register',
            [
                'title' => 'Criar conta'
            ], 
            'auth'
        );
    }

    public function login(Request $request): void
    {
        $email = filter_var(trim((string) $request->input('email')), FILTER_VALIDATE_EMAIL);
        $password = (string) $request->input('password');

        if (!$email || $password === '') {
            Session::flash('error', 'Informe e-mail e senha válidos.');
            Response::redirect('/login');
        }

        $user = (new AuthService())->authenticate($email, $password);

        if (!$user) {
            Session::flash('error', 'E-mail ou senha inválidos.');
            Response::redirect('/login');
        }

        Session::login($user);
        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void 
    {
        session::logout();
        Response::redirect('/login');
    }

    public function recovery(Request $request): void 
    {
        View::render(
            'pages/auth/recovery',
            [
                'title' => 'Recuperar senha'
            ], 
            'auth'
        );
    }

    public function verification(Request $r): void
    {
        View::render(
            'pages/auth/verification',
            [
                'title' => 'Verificar e-mail'
            ], 
            'auth'
        );
    }
}