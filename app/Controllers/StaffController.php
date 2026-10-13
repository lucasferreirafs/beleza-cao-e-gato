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
use App\Support\Uuid;
use App\Services\StaffAgendaService;
use App\Services\StaffClientService;
use App\Services\StaffPetService;
use App\Services\SupabaseStorageService;
use RuntimeException;
use Throwable;
final class StaffController
{
    public function clients(Request $r): void
    {
        $this->renderClients($r, 'list', 'Clientes', (new StaffClientService())->listing($r->input('q'), $r->input('page')));
    }

    public function clientProfile(Request $r): void
    {
        $data = (new StaffClientService())->profile($r->param('clientId'));
        if ($data === null) { $this->notFound(); return; }
        $this->renderClients($r, 'profile', 'Perfil do cliente', $data);
    }

    public function clientPet(Request $r): void
    {
        $data = (new StaffClientService())->pet($r->param('clientId'), $r->param('petId'));
        if ($data === null) { $this->notFound(); return; }
        $this->renderClients($r, 'pet', 'Perfil do pet', $data);
    }

    private function renderClients(Request $r, string $clientView, string $title, array $data): void
    {
        $selection = StaffClientService::selection($r->input('q'), $r->input('page'));
        $returnQuery = http_build_query(array_filter(['q' => $data['search'] ?? $selection['search'], 'page' => $data['page'] ?? $selection['page']], static fn($v) => $v !== '' && $v !== 1));
        View::render('pages/staff/page', $data + [
            'title' => $title, 'active' => 'clientes', 'section' => 'clientes',
            'clientView' => $clientView, 'returnQuery' => $returnQuery, 'user' => Session::user(),
        ]);
    }

    public function clientPhoto(Request $r): void
    {
        $client = (new StaffClientService())->client($r->param('clientId'));
        $this->directoryPhoto(SupabaseStorageService::USER_BUCKET, $client['avatar_path'] ?? null);
    }

    public function clientPetPhoto(Request $r): void
    {
        $data = (new StaffClientService())->pet($r->param('clientId'), $r->param('petId'));
        $this->directoryPhoto(SupabaseStorageService::PET_BUCKET, $data['pet']['photo_path'] ?? null);
    }

    public function petsDirectory(Request $r): void
    {
        $data = (new StaffPetService())->listing($r->input('q'), $r->input('page'));
        $returnQuery = http_build_query(array_filter([
            'q' => $data['search'], 'page' => $data['page'],
        ], static fn($value) => $value !== '' && $value !== 1));
        View::render('pages/staff/page', $data + [
            'title' => 'Pets', 'active' => 'pets', 'section' => 'pets',
            'petView' => 'list', 'returnQuery' => $returnQuery,
            'user' => Session::user(),
        ]);
    }

    public function petProfile(Request $r): void
    {
        $data = (new StaffPetService())->profile($r->param('petId'));
        if ($data === null) { $this->notFound(); return; }
        $returnQuery = http_build_query(array_filter([
            'q' => is_string($r->input('q')) ? mb_substr(trim($r->input('q')), 0, 100) : '',
            'page' => StaffPetService::selection('', $r->input('page'))['page'],
        ], static fn($value) => $value !== '' && $value !== 1));
        View::render('pages/staff/page', $data + [
            'title' => 'Perfil do pet', 'active' => 'pets', 'section' => 'pets',
            'petView' => 'profile', 'fromPetDirectory' => true,
            'returnQuery' => $returnQuery, 'user' => Session::user(),
        ]);
    }

    public function petPhoto(Request $r): void
    {
        $data = (new StaffPetService())->profile($r->param('petId'));
        $this->directoryPhoto(SupabaseStorageService::PET_BUCKET, $data['pet']['photo_path'] ?? null);
    }

    /** Chamado somente após autorização do cliente e do vínculo tutor/pet. */
    private function directoryPhoto(string $bucket, ?string $path): void
    {
        if (!$path) { http_response_code(404); return; }
        if (PrivateImageResponse::isNotModified($bucket, $path)) return;
        try {
            $bytes = (new SupabaseStorageService())->download($bucket, $path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Imagem inválida.');
            PrivateImageResponse::send($bytes, $mime, $bucket, $path);
        } catch (Throwable) {
            header_remove('ETag');
            header('Cache-Control: no-store');
            http_response_code(503);
        }
    }

    public function page(Request $r): void
    {
        $section = $r->param('section') ?? 'agenda';
        $allowed = ['agenda','atendimentos','perfil'];
        if (!in_array($section, $allowed, true)) { $this->notFound(); return; }
        if ($section === 'agenda') {
            $agenda = (new StaffAgendaService())->data(Session::user()['id'], $r->input('date'), $r->input('view'));
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
            $pageInput = filter_var($r->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $data = (new AppointmentRepository())->staffPage(Session::user()['id'], $search, $date, $pageInput ?: 1);
            $returnQuery = http_build_query(array_filter(['q' => $search, 'date' => $date], static fn(string $value): bool => $value !== ''));
            View::render('pages/staff/page', ['title' => 'Atendimentos', 'active' => $section,
                'section' => $section, 'search' => $search, 'date' => $date,
                'returnQuery' => $returnQuery, 'user' => Session::user()] + $data);
            return;
        }
        $rows = (new ManagementRepository())->staffRows($section, Session::user()['id']);
        View::render('pages/staff/page', ['title' => ucfirst($section), 'active' => $section, 'section' => $section, 'rows' => $rows, 'user' => Session::user()]);
    }
    public function appointmentDetail(Request $r): void
    {
        $row = (new AppointmentRepository())->findForStaff(Uuid::route($r->param('id')), Session::user()['id']);
        if (!$row) { $this->notFound(); return; }
        $lastGrooming = (new AppointmentRepository())->lastGroomingForPet($row['pet_id'], $row['id']);
        View::render('pages/staff/appointment-detail', ['title' => 'Detalhes do atendimento',
            'active' => 'atendimentos', 'section' => 'atendimentos', 'appointment' => $row,
            'lastGrooming' => $lastGrooming, 'user' => Session::user()]);
    }

    public function appointmentPetPhoto(Request $r): void
    {
        $row = (new AppointmentRepository())->findForStaff(Uuid::route($r->param('id')), Session::user()['id']);
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
        $ok = (new ManagementRepository())->setStaffAppointmentStatus(Uuid::route($r->param('id')), Session::user()['id'], $status);
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
            Response::redirect('/staff/atendimentos/' . Uuid::route($r->param('id')));
        }
        $period = $r->input('period', 'today');
        Response::redirect('/staff?period=' . ($period === 'week' ? 'week' : 'today'));
    }
}
