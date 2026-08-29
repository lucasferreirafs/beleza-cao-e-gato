<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Session, Response, View};

final class AuthController
{
    public function landing(Request $request): void
    {
        if(Session::user()) Response::redirect('/dashboard');

        View::render(
            'pages/landing',
            ['tilte' => 'Gestão inteligente para pet shops'],
            'auth'
        );
    }
}