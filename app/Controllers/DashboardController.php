<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{
    Request,
    Response,
    Session,
    View
};
use App\Repositories\DashboardRepository;

final class DashboardController
{
    public function index(Request $r): void
    {
        $role = Session::user()['role'];
        Response::redirect(match ($role) { 'admin' => '/admin', 'staff' => '/staff', default => '/cliente'});
    }

    public function client(Request $r): void
    {
        View::render('pages/client/dashboard', $this->data('Dashboard', 'dashboard') + (new DashboardRepository())->client((int) Session::user()['id']));
    }
    public function staff(Request $r): void
    {
        View::render('pages/staff/dashboard', $this->data('Dashboard', 'dashboard') + (new DashboardRepository())->staff((int) Session::user()['id']));
    }
    public function admin(Request $r): void
    {
        View::render('pages/admin/dashboard', $this->data('Dashboard', 'dashboard') + (new DashboardRepository())->admin());
    }
    private function data(string $title, string $active): array
    {
        return compact('title', 'active') + ['user' => Session::user()];
    }
}
