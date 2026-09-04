<?php
declare(strict_types= 1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;


final class DashboardController
{
    public function index(Request $request): void
    {
        $role = Session::user()['role'];
        Response::redirect(match ($role) { 
            'admin' => '/admin',
            'staff' => '/staff',
            default => '/cliente'
        });
    }

    public function client(Request $r): void
    {
        View::render(
            'pages/client/dashboard', 
            $this->data('Dashboard', 'dashboard')
        );
    }

    private function data(string $title, string $active): array
    {
        return compact('title', 'active') + ['user' => Session::user()];
    }
}