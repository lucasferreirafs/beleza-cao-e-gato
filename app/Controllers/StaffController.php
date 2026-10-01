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
use App\Support\AppointmentStatus;
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
        $status = (string) $r->input('status');
        $ok = (new ManagementRepository())->setStaffAppointmentStatus((int) $r->param('id'), (int) Session::user()['id'], $status);
        if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            Response::json([
                'ok' => $ok,
                'status' => $status,
                'label' => $ok ? AppointmentStatus::label($status) : null,
                'message' => $ok ? null : 'O atendimento mudou ou ultrapassou o prazo de início. Atualize a agenda.',
            ], $ok ? 200 : 409);
        }
        Session::flash($ok ? 'success' : 'error', $ok ? 'Atendimento atualizado.' : 'Atualização inválida ou não autorizada.');
        $period = $r->input('period', 'today');
        Response::redirect('/staff?period=' . ($period === 'week' ? 'week' : 'today'));
    }
}
