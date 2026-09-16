<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{
    Request,
    Session,
    View,
    Response
};
use App\Repositories\ManagementRepository;
final class StaffController
{
    public function page(Request $r): void
    {
        $section = $r->param('section') ?? 'agenda';
        $allowed = ['agenda','atendimentos','clientes','pets','historico','perfil'];
        if (!in_array($section, $allowed, true)) $section = 'agenda';
        $rows = (new ManagementRepository())->staffRows($section, (int) Session::user()['id']);
        View::render('pages/staff/page', ['title' => ucfirst($section), 'active' => $section, 'section' => $section, 'rows' => $rows, 'user' => Session::user()]);
    }
    public function appointmentStatus(Request $r): void
    {
        $ok = (new ManagementRepository())->setStaffAppointmentStatus((int) $r->param('id'), (int) Session::user()['id'], (string) $r->input('status'));
        Session::flash($ok ? 'success' : 'error', $ok ? 'Atendimento atualizado.' : 'Atualização inválida ou não autorizada.');
        Response::redirect('/staff/agenda');
    }
}
