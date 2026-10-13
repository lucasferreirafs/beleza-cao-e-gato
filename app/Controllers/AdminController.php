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
use App\Repositories\StaffClientRepository;
use App\Repositories\AppointmentRepository;
use App\Services\StaffPetService;
use App\Services\AdminPasswordConfirmationService;
use App\Services\SupabaseStorageService;
use App\Support\PrivateImageResponse;
use InvalidArgumentException;
use App\Support\Uuid;
use Throwable;

final class AdminController
{
    public function page(Request $r): void
    {
        $section = $r->param('section') ?? 'clientes';
        $allowed = ['clientes', 'pets', 'funcionarios', 'servicos', 'agendamentos', 'configuracoes'];

        if (!in_array($section, $allowed, true)) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
            return;
        }

        $repository = new ManagementRepository();
        if ($section === 'clientes') {
            $search = is_string($r->input('q')) ? mb_substr(trim($r->input('q')), 0, 100) : '';
            $pageInput = filter_var($r->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $data = $repository->adminClients($search, $pageInput ?: 1);
            View::render('pages/admin/list', $data + [
                'title' => 'Clientes', 'active' => $section, 'section' => $section,
                'search' => $search, 'rows' => [], 'user' => Session::user(),
            ]);
            return;
        }
        if ($section === 'pets') {
            $data = (new StaffPetService())->listing($r->input('q'), $r->input('page'));
            $returnQuery = http_build_query(array_filter([
                'q' => $data['search'], 'page' => $data['page'],
            ], static fn($value) => $value !== '' && $value !== 1));
            View::render('pages/admin/list', $data + [
                'title' => 'Pets', 'active' => $section, 'section' => $section,
                'rows' => [], 'search' => $data['search'], 'returnQuery' => $returnQuery,
                'adminPetDirectory' => true, 'embedAdminPetDirectory' => true, 'user' => Session::user(),
            ]);
            return;
        }
        if ($section === 'funcionarios') {
            $search = is_string($r->input('q')) ? mb_substr(trim($r->input('q')), 0, 100) : '';
            $pageInput = filter_var($r->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $data = $repository->adminStaff($search, $pageInput ?: 1);
            View::render('pages/admin/list', $data + [
                'title' => 'Funcionários', 'active' => $section, 'section' => $section,
                'search' => $search, 'rows' => [], 'user' => Session::user(),
                'demotionModalMessage' => Session::flash('staff_demotion_modal'),
            ]);
            return;
        }
        if ($section === 'servicos') {
            $search = is_string($r->input('q')) ? mb_substr(trim($r->input('q')), 0, 100) : '';
            $statusInput = $r->input('status');
            $status = is_string($statusInput) && in_array($statusInput, ['active', 'inactive'], true) ? $statusInput : '';
            $pageInput = filter_var($r->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $data = $repository->adminServices($search, $status, $pageInput ?: 1);
            View::render('pages/admin/list', $data + [
                'title' => 'Serviços', 'active' => $section, 'section' => $section,
                'search' => $search, 'statusFilter' => $status, 'rows' => [], 'user' => Session::user(),
            ]);
            return;
        }
        if ($section === 'agendamentos') {
            $searchInput = $r->input('q', '');
            $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 100) : '';
            $dateInput = $r->input('date', '');
            $date = is_string($dateInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dateInput)
                && checkdate((int) substr($dateInput, 5, 2), (int) substr($dateInput, 8, 2), (int) substr($dateInput, 0, 4))
                ? $dateInput : '';
            $statusInput = $r->input('status', '');
            $status = is_string($statusInput) && in_array($statusInput, array_keys(\App\Support\AppointmentStatus::editable()), true)
                ? $statusInput : '';
            $pageInput = filter_var($r->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $data = (new AppointmentRepository())->adminPage($search, $date, $status, $pageInput ?: 1);
            $returnQuery = http_build_query(array_filter([
                'q' => $search, 'date' => $date, 'status' => $status,
            ], static fn(string $value): bool => $value !== ''));
            View::render('pages/admin/list', $data + [
                'title' => 'Agendamentos', 'active' => $section, 'section' => $section,
                'search' => $search, 'dateFilter' => $date, 'statusFilter' => $status,
                'returnQuery' => $returnQuery, 'user' => Session::user(),
            ]);
            return;
        }
        $rows = $repository->adminRows($section);
        View::render(
            'pages/admin/list',
            [
                'title' => ucfirst($section),
                'active' => $section,
                'section' => $section,
                'rows' => $rows,
                'user' => Session::user()
            ]
        );
    }

    public function clientProfile(Request $r): void
    {
        $id = Uuid::parse($r->param('id'));
        $client = $id ? (new ManagementRepository())->adminClientProfile($id) : null;
        if ($client === null) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
            return;
        }
        View::render('pages/admin/client-profile', [
            'title' => 'Perfil do cliente', 'active' => 'clientes', 'section' => 'clientes',
            'user' => Session::user(), 'client' => $client,
            'pets' => (new StaffClientRepository())->pets($id),
        ]);
    }

    public function staffProfile(Request $r): void
    {
        $id = Uuid::parse($r->param('id'));
        $staff = $id ? (new ManagementRepository())->adminStaffProfile($id) : null;
        if ($staff === null) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
            return;
        }

        View::render('pages/admin/staff-profile', [
            'title' => 'Perfil do funcionário', 'active' => 'funcionarios', 'section' => 'funcionarios',
            'user' => Session::user(), 'staff' => $staff,
        ]);
    }

    public function clientPhoto(Request $r): void
    {
        $client = (new ManagementRepository())->adminClientProfile(Uuid::route($r->param('id')));
        $this->sendDirectoryPhoto(SupabaseStorageService::USER_BUCKET, $client['avatar_path'] ?? null);
    }

    public function clientPetPhoto(Request $r): void
    {
        $clientId = Uuid::parse($r->param('id'));
        $petId = Uuid::parse($r->param('petId'));
        $pet = $clientId && $petId ? (new StaffClientRepository())->pet($clientId, $petId) : null;
        $this->sendDirectoryPhoto(SupabaseStorageService::PET_BUCKET, $pet['photo_path'] ?? null);
    }

    public function petProfile(Request $r): void
    {
        $data = (new StaffPetService())->profile($r->param('id'));
        if ($data === null) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
            return;
        }
        $returnQuery = http_build_query(array_filter([
            'q' => is_string($r->input('q')) ? mb_substr(trim($r->input('q')), 0, 100) : '',
            'page' => StaffPetService::selection('', $r->input('page'))['page'],
        ], static fn($value) => $value !== '' && $value !== 1));
        View::render('pages/admin/pet-profile', $data + [
            'title' => 'Perfil do pet', 'active' => 'pets', 'section' => 'pets',
            'adminPetProfile' => true, 'fromPetDirectory' => true,
            'petProfileUrl' => '/admin/pets/' . rawurlencode((string) $data['pet']['id']),
            'ownerProfileUrl' => '/admin/clientes/' . rawurlencode((string) $data['client']['id']),
            'backUrl' => '/admin/pets', 'returnQuery' => $returnQuery,
            'user' => Session::user(),
        ]);
    }

    public function petPhoto(Request $r): void
    {
        $data = (new StaffPetService())->profile($r->param('id'));
        $this->sendDirectoryPhoto(SupabaseStorageService::PET_BUCKET, $data['pet']['photo_path'] ?? null);
    }

    public function deletePet(Request $r): void
    {
        try {
            (new AdminPasswordConfirmationService())->assertValid((string) (Session::user()['id'] ?? ''), $r->input('admin_password'));
            $deleted = (new ManagementRepository())->deleteAdminPet(Uuid::route($r->param('id')));
            if ($deleted === null) {
                Session::flash('error', 'Pet não encontrado.');
                Response::redirect('/admin/pets');
            }
            $cleanupFailed = false;
            if ($deleted['photo_path'] !== null) {
                try { (new SupabaseStorageService())->remove(SupabaseStorageService::PET_BUCKET, $deleted['photo_path']); }
                catch (Throwable) { $cleanupFailed = true; }
            }
            Session::flash($cleanupFailed ? 'error' : 'success', $cleanupFailed ? 'Pet removido, mas não foi possível limpar a imagem no Storage.' : 'Pet removido.');
            Response::redirect('/admin/pets');
        } catch (InvalidArgumentException|\DomainException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/admin/pets/' . rawurlencode((string) $r->param('id')));
        }
    }

    private function sendDirectoryPhoto(string $bucket, ?string $path): void
    {
        if (!$path) { http_response_code(404); return; }
        if (PrivateImageResponse::isNotModified($bucket, $path)) return;
        try {
            $bytes = (new SupabaseStorageService())->download($bucket, $path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new \RuntimeException('Imagem inválida.');
            PrivateImageResponse::send($bytes, $mime, $bucket, $path);
        } catch (Throwable) {
            header_remove('ETag'); header('Cache-Control: no-store'); http_response_code(503);
        }
    }

    public function promoteClient(Request $r): void
    {
        $id = Uuid::route($r->param('id'));
        try {
            (new AdminPasswordConfirmationService())->assertValid((string) (Session::user()['id'] ?? ''), $r->input('admin_password'));
            $ok = (new ManagementRepository())->promoteAdminClient($id);
            Session::flash($ok ? 'success' : 'error', $ok ? 'Cliente promovido a funcionário.' : 'Não foi possível promover este cliente. Verifique se a conta está ativa.');
        } catch (InvalidArgumentException $e) { Session::flash('error', $e->getMessage()); }
        Response::redirect('/admin/clientes');
    }

    public function promoteStaff(Request $r): void
    {
        $id = Uuid::route($r->param('id'));
        try {
            (new AdminPasswordConfirmationService())->assertValid((string) (Session::user()['id'] ?? ''), $r->input('admin_password'));
            $ok = (new ManagementRepository())->promoteAdminStaff($id);
            Session::flash($ok ? 'success' : 'error', $ok ? 'Funcionário promovido a administrador. Ele precisará entrar novamente para atualizar as permissões.' : 'Não foi possível promover este funcionário. Verifique se a conta está ativa.');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/admin/funcionarios');
    }

    public function demoteStaff(Request $r): void
    {
        $id = Uuid::parse($r->param('id'));
        if ($id === null) {
            Session::flash('error', 'Funcionário não encontrado.');
            Response::redirect('/admin/funcionarios');
        }

        try {
            (new AdminPasswordConfirmationService())->assertValid(
                (string) (Session::user()['id'] ?? ''),
                $r->input('admin_password')
            );
            $result = (new ManagementRepository())->demoteAdminStaff($id);
            if ($result === 'active_appointments') {
                Session::flash('staff_demotion_modal', 'Não é possível rebaixar este funcionário porque há atendimentos pendentes ou em andamento vinculados a ele. Cancele os pendentes e conclua os atendimentos em andamento antes.');
            } else {
                [$flashType, $message] = $result === 'demoted'
                    ? ['success', 'Funcionário rebaixado para cliente. As sessões dele serão encerradas na próxima requisição.']
                    : ['error', 'Não foi possível rebaixar este funcionário. Verifique se a conta ainda possui esse papel.'];
                Session::flash($flashType, $message);
            }
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect('/admin/funcionarios');
    }

    public function deleteClient(Request $r): void
    {
        $id = Uuid::route($r->param('id'));
        try {
            (new AdminPasswordConfirmationService())->assertValid((string) (Session::user()['id'] ?? ''), $r->input('admin_password'));
            $deleted = (new ManagementRepository())->deleteAdminClient($id);
            if ($deleted === null) { Session::flash('error', 'Cliente não encontrado.'); Response::redirect('/admin/clientes'); return; }
            $cleanupFailed = false;
            $objects = array_values(array_filter([
                $deleted['avatar_path'] === null ? null : [SupabaseStorageService::USER_BUCKET, $deleted['avatar_path']],
                ...array_map(static fn(?string $path) => $path === null ? null : [SupabaseStorageService::PET_BUCKET, $path], $deleted['pet_photo_paths']),
            ]));
            if ($objects) {
                try {
                    $storage = new SupabaseStorageService();
                    foreach ($objects as [$bucket, $path]) {
                        try { $storage->remove($bucket, $path); } catch (Throwable) { $cleanupFailed = true; }
                    }
                } catch (Throwable) { $cleanupFailed = true; }
            }
            Session::flash($cleanupFailed ? 'error' : 'success', $cleanupFailed ? 'Cliente removido, mas não foi possível limpar todos os arquivos de imagem.' : 'Cliente removido.');
        } catch (InvalidArgumentException|\DomainException $e) { Session::flash('error', $e->getMessage()); }
        Response::redirect('/admin/clientes');
    }

    public function status(Request $r): void
    {
        $section = (string) $r->param('section');
        $statusInput = $r->input('status');
        try {
            if ($section === 'servicos') {
                (new AdminPasswordConfirmationService())->assertValid(
                    (string) (Session::user()['id'] ?? ''),
                    $r->input('admin_password')
                );
            }
            $ok = (new ManagementRepository())->setAdminStatus(
                $section,
                Uuid::route($r->param('id')),
                is_string($statusInput) ? $statusInput : ''
            );
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/admin/' . rawurlencode($section));
        }

        $successMessage = $section === 'agendamentos' ? 'Agendamento cancelado.' : 'Status atualizado.';
        Session::flash($ok ? 'success' : 'error', $ok ? $successMessage : 'Alteração de status inválida.');
        Response::redirect('/admin/' . rawurlencode($section));
    }

    public function form(Request $r): void
    {
        $section = (string) $r->param('section');
        $id = $r->param('id') !== null ? Uuid::route($r->param('id')) : null;
        if (!in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true)
            || ($id === null && $section !== 'servicos')) {
            Response::redirect('/admin/' . rawurlencode($section));
        }

        $repository = new ManagementRepository();
        $record = $id ? $repository->findEditable($section, $id) : null;

        if ($id && !$record) {
            Session::flash('error', 'Registro não encontrado.');
            Response::redirect('/admin/' . rawurlencode($section));
        }

        View::render(
            'pages/admin/form',
            [
                'title' => ($id ? 'Editar ' : 'Novo ') . ucfirst($section),
                'active' => $section,
                'section' => $section,
                'record' => $record,
                'options' => $repository->formOptions(),
                'user' => Session::user()
            ]
        );
    }

    public function save(Request $r): void
    {
        $section = (string) $r->param('section');
        $idInput = $r->param('id');
        $id = $idInput !== null ? Uuid::parse($idInput) : null;

        if (!in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true)
            || ($idInput !== null && $id === null)
            || ($id === null && $section !== 'servicos')) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Página não encontrada'], 'auth');
            return;
        }

        try {
            $input = $r->all();
            if (in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true) && $id !== null) {
                (new AdminPasswordConfirmationService())->assertValid((string) (Session::user()['id'] ?? ''), $input['admin_password'] ?? null);
                unset($input['admin_password']);
                if ($section === 'clientes') unset($input['cpf']);
            }
            (new ManagementRepository())->save($section, $id, $input);
            Session::flash('success', $id ? 'Registro atualizado.' : 'Registro criado.');
            Response::redirect('/admin/' . rawurlencode($section));
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            if (in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true) && $id !== null) {
                $record = (new ManagementRepository())->findEditable($section, $id) ?? [];
                $fields = $section === 'clientes'
                    ? ['name', 'email', 'phone', 'birth_date']
                    : ($section === 'funcionarios'
                        ? ['name', 'email', 'phone', 'cpf', 'birth_date']
                        : ($section === 'servicos'
                            ? ['name', 'description', 'duration_minutes', 'price']
                            : ['owner_id', 'name', 'species', 'breed', 'coat_color', 'sex', 'birth_date', 'weight', 'allergies', 'behavior', 'special_needs', 'notes']));
                foreach ($fields as $key) {
                    if (isset($input[$key]) && is_string($input[$key])) $record[$key] = $input[$key];
                }
                View::render('pages/admin/form', [
                    'title' => 'Editar ' . ucfirst($section), 'active' => $section, 'section' => $section,
                    'record' => $record, 'options' => (new ManagementRepository())->formOptions(),
                    'user' => Session::user(),
                ]);
                return;
            }
            Response::redirect('/admin/' . rawurlencode($section) . ($id ? '/' . $id . '/editar' : '/novo'));
        }
    }
}
