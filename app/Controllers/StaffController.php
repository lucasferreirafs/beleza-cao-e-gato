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
use App\Repositories\AppointmentRepository;
use App\Support\AppointmentStatus;
use App\Support\PrivateImageResponse;
use App\Services\StaffAgendaService;
use App\Services\SupabaseStorageService;
use RuntimeException;
use Throwable;
final class StaffController
{
    public function page(Request $r): void
    {
        $section = $r->param('section') ?? 'agenda';
        $allowed = ['agenda','atendimentos','clientes','pets','historico','perfil'];
        if (!in_array($section, $allowed, true)) $section = 'agenda';
        if ($section === 'agenda') {
            $agenda = (new StaffAgendaService())->data((int) Session::user()['id'], $r->input('date'), $r->input('view'));
            View::render('pages/staff/page', ['title' => 'Minha Agenda', 'active' => 'agenda', 'section' => 'agenda', 'agenda' => $agenda, 'user' => Session::user()]);
            return;
        }
        if ($section === 'atendimentos') {
            $searchInput = $r->input('q', '');
            $search = is_string($searchInput) ? trim($searchInput) : '';
            $search = mb_substr($search, 0, 100);
            $date = $r->input('date', '');
            $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
                && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)) ? $date : '';
            $rows = (new ManagementRepository())->staffRows($section, (int) Session::user()['id']);
            $rows = array_values(array_filter($rows, static function (array $row) use ($search, $date): bool {
                if ($date !== '' && (new \DateTimeImmutable($row['scheduled_at']))->format('Y-m-d') !== $date) return false;
                return $search === '' || mb_stripos(implode(' ', [$row['pet_name'], $row['client_name'], $row['service_name']]), $search) !== false;
            }));
            View::render('pages/staff/page', ['title' => 'Atendimentos', 'active' => $section,
                'section' => $section, 'rows' => $rows, 'search' => $search, 'date' => $date, 'user' => Session::user()]);
            return;
        }
        $rows = (new ManagementRepository())->staffRows($section, (int) Session::user()['id']);
        View::render('pages/staff/page', ['title' => ucfirst($section), 'active' => $section, 'section' => $section, 'rows' => $rows, 'user' => Session::user()]);
    }
    public function appointmentDetail(Request $r): void
    {
        $row = (new AppointmentRepository())->findForStaff((int) $r->param('id'), (int) Session::user()['id']);
        if (!$row) { $this->notFound(); return; }
        $lastGrooming = (new AppointmentRepository())->lastGroomingForPet((int) $row['pet_id'], (int) $row['id']);
        View::render('pages/staff/appointment-detail', ['title' => 'Detalhes do atendimento',
            'active' => 'atendimentos', 'section' => 'atendimentos', 'appointment' => $row,
            'lastGrooming' => $lastGrooming, 'user' => Session::user()]);
    }

    public function appointmentPetPhoto(Request $r): void
    {
        $row = (new AppointmentRepository())->findForStaff((int) $r->param('id'), (int) Session::user()['id']);
        if (!$row || empty($row['pet_photo_path'])) { http_response_code(404); return; }
        $path = (string) $row['pet_photo_path'];
        if (PrivateImageResponse::isNotModified(SupabaseStorageService::PET_BUCKET, $path)) return;
        try {
            $bytes = (new SupabaseStorageService())->download(SupabaseStorageService::PET_BUCKET, $path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Imagem inválida.');
            PrivateImageResponse::send($bytes, $mime, SupabaseStorageService::PET_BUCKET, $path);
        } catch (Throwable) { http_response_code(503); }
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
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
        if ($r->input('source') === 'agenda') {
            $selection = StaffAgendaService::selection($r->input('date'), $r->input('view'));
            Response::redirect('/staff/agenda?' . http_build_query(['date' => $selection['selected']->format('Y-m-d'), 'view' => $selection['view']]));
        }
        if ($r->input('source') === 'atendimento') {
            Response::redirect('/staff/atendimentos/' . (int) $r->param('id'));
        }
        $period = $r->input('period', 'today');
        Response::redirect('/staff?period=' . ($period === 'week' ? 'week' : 'today'));
    }
}
