<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{
    Request,
    Response,
    Session,
    View
};
use App\Repositories\{
    AppointmentRepository,
    PetRepository,
    ServiceRepository,
    UserRepository
};
use App\Services\BookingService;
use App\Services\SupabaseStorageService;
use App\Support\PrivateImageResponse;
use App\Validation\Validator;
use DomainException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ClientController
{
    private function base(string $title, string $active, array $extra = []): array
    {
        return compact('title', 'active') + ['user' => Session::user()] + $extra;
    }

    public function pets(Request $r): void
    {
        $pets = (new PetRepository())->allForUser((int) Session::user()['id']);
        View::render(
            'pages/client/pets',
            $this->base('Meus Pets', 'pets', compact('pets'))
        );
    }

    public function petForm(Request $r): void
    {
        View::render(
            'pages/client/pet-form',
            $this->base(
                'Cadastrar Novo Pet',
                'pets',
                [
                    'pet' => null,
                    'formAction' => '/cliente/pets'
                ]
            )
        );
    }

    public function storePet(Request $r): void
    {
        $storage = null;
        $uploadedPath = null;
        try {
            $data = $this->petData($r);
            $data['photo_path'] = null;
            $file = $_FILES['photo'] ?? null;
            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $storage = new SupabaseStorageService();
                $upload = $storage->upload(SupabaseStorageService::PET_BUCKET, $file);
                $uploadedPath = $data['photo_path'] = $upload['path'];
            }
            (new PetRepository())->create((int) Session::user()['id'], $data);
            Session::flash('success', 'Pet cadastrado com sucesso.');
            Response::redirect('/cliente/pets');
        } catch (InvalidArgumentException $e) {
            if ($uploadedPath && $storage) $this->removePetPhoto($storage, $uploadedPath);
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/pets/novo');
        } catch (Throwable) {
            if ($uploadedPath && $storage) $this->removePetPhoto($storage, $uploadedPath);
            Session::flash('error', 'Não foi possível salvar o pet. Tente novamente.');
            Response::redirect('/cliente/pets/novo');
        }
    }

    public function petProfile(Request $r): void
    {
        $pet = $this->ownedPet((int) $r->param('id'));
        $appointments = (new AppointmentRepository())->allForPet((int) $pet['id'], (int) Session::user()['id']);
        View::render(
            'pages/client/pet-profile',
            $this->base($pet['name'], 'pets', compact('pet', 'appointments'))
        );
    }

    public function editPet(Request $r): void
    {
        $pet = $this->ownedPet((int) $r->param('id'));
        View::render(
            'pages/client/pet-form',
            $this->base('Editar Pet', 'pets', ['pet' => $pet, 'formAction' => '/cliente/pets/' . $pet['id']])
        );
    }

    public function updatePet(Request $r): void
    {
        $id = (int) $r->param('id');
        $current = $this->ownedPet($id);
        $storage = null;
        $uploadedPath = null;
        try {
            $data = $this->petData($r);
            $data['photo_path'] = $current['photo_path'] ?? null;
            $file = $_FILES['photo'] ?? null;
            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $storage = new SupabaseStorageService();
                $upload = $storage->upload(SupabaseStorageService::PET_BUCKET, $file);
                $uploadedPath = $data['photo_path'] = $upload['path'];
            }
            if (!(new PetRepository())->update($id, (int) Session::user()['id'], $data))
                throw new DomainException('Pet não encontrado.');

            if ($uploadedPath && !empty($current['photo_path'])) {
                try { $storage->remove(SupabaseStorageService::PET_BUCKET, $current['photo_path']); }
                catch (Throwable) { error_log('Pet photo cleanup pending: ' . $current['photo_path']); }
            }
            Session::flash('success', 'Dados do pet atualizados.');
            Response::redirect('/cliente/pets/' . $id);
        } catch (InvalidArgumentException | DomainException $e) {
            if ($uploadedPath && $storage) $this->removePetPhoto($storage, $uploadedPath);
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/pets/' . $id . '/editar');
        } catch (Throwable) {
            if ($uploadedPath && $storage) $this->removePetPhoto($storage, $uploadedPath);
            Session::flash('error', 'Não foi possível salvar o pet. Tente novamente.');
            Response::redirect('/cliente/pets/' . $id . '/editar');
        }
    }

    public function petPhoto(Request $r): void
    {
        $pet = $this->ownedPet((int) $r->param('id'));
        if (empty($pet['photo_path'])) { http_response_code(404); return; }
        if (PrivateImageResponse::isNotModified(SupabaseStorageService::PET_BUCKET, $pet['photo_path'])) return;
        try {
            $bytes = (new SupabaseStorageService())->download(SupabaseStorageService::PET_BUCKET, $pet['photo_path']);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Formato de imagem inválido.');
            PrivateImageResponse::send($bytes, $mime, SupabaseStorageService::PET_BUCKET, $pet['photo_path']);
        } catch (Throwable) { http_response_code(503); }
    }

    private function removePetPhoto(SupabaseStorageService $storage, string $path): void
    {
        try { $storage->remove(SupabaseStorageService::PET_BUCKET, $path); }
        catch (Throwable) { error_log('Pet photo cleanup pending: ' . $path); }
    }

    public function deletePet(Request $r): void
    {
        $repository = new PetRepository();
        $pet = $repository->findForUser((int) $r->param('id'), (int) Session::user()['id']);
        if ($pet && $repository->delete((int) $r->param('id'), (int) Session::user()['id'])) {
            if (!empty($pet['photo_path'])) {
                try { (new SupabaseStorageService())->remove(SupabaseStorageService::PET_BUCKET, $pet['photo_path']); }
                catch (Throwable) { error_log('Deleted pet photo cleanup pending: ' . $pet['photo_path']); }
            }
            Session::flash('success', 'Pet excluído.');
        } else {
            Session::flash('error', 'Pet não encontrado ou vinculado a um agendamento.');
        }

        Response::redirect('/cliente/pets');
    }

    public function services(Request $r): void
    {
        $services = (new ServiceRepository())->active();
        View::render(
            'pages/client/services',
            $this->base('Serviços Disponíveis', 'services', compact('services'))
        );
    }

    public function appointments(Request $r): void
    {
        $appointments = (new AppointmentRepository())->allForUser((int) Session::user()['id']);
        View::render(
            'pages/client/appointments',
            $this->base('Meus Agendamentos', 'appointments', compact('appointments'))
        );
    }

    public function booking(Request $r): void
    {
        $booking = new BookingService();
        $state = $booking->state();
        $step = BookingService::stepNumber((string) $r->param('step'));
        if ($step === null || $step > $booking->nextIncompleteStep()) {
            Response::redirect('/cliente/agendamento/' . BookingService::stepSlug($booking->nextIncompleteStep()));
        }
        if ((string) $r->param('step') !== BookingService::stepSlug($step)) {
            Response::redirect('/cliente/agendamento/' . BookingService::stepSlug($step));
        }
        $pets = (new PetRepository())->allForUser((int) Session::user()['id']);
        $services = (new ServiceRepository())->active();
        $staff = (new UserRepository())->allStaff();
        $serviceIds = $state['service_ids'] ?? [];
        $selectedServices = (new ServiceRepository())->findActiveMany($serviceIds);
        if ($step === 4 && isset($state['quote'])) $selectedServices = $state['quote'];
        $totalPriceCents = array_sum(array_map(static fn(array $service): int => (int) round((float) $service['price'] * 100), $selectedServices));
        $totalPrice = $totalPriceCents / 100;
        $totalDuration = array_sum(array_map(static fn(array $service): int => (int) $service['duration_minutes'], $selectedServices));
        $date = $r->input('date', $state['date'] ?? (new \DateTimeImmutable('tomorrow'))->format('Y-m-d'));
        $time = $r->input('time', $state['time'] ?? '');
        $date = is_string($date) ? $date : '';
        $time = is_string($time) ? $time : '';
        $slots = $step === 3
            ? (new AppointmentRepository())->availableSlots($date, $serviceIds, null, isset($state['pet_id']) ? (int) $state['pet_id'] : null)
            : [];
        $staffAvailability = [];
        if ($step === 3 && $time !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $staffAvailability = (new AppointmentRepository())->staffAvailability($date . ' ' . $time, $serviceIds, (int) $state['pet_id']);
        }

        View::render(
            'pages/client/booking',
            $this->base('Novo Agendamento', 'appointments', compact(
                'step', 'state', 'pets', 'services', 'selectedServices', 'totalPrice',
                'totalDuration', 'staff', 'staffAvailability', 'date', 'time', 'slots'
            )) + ['error' => Session::flash('error')]
        );
    }

    public function bookingAvailability(Request $r): void
    {
        $state = (new BookingService())->state();
        if (empty($state['service_ids']) || empty($state['pet_id'])) {
            Response::json(['error' => 'Complete as etapas anteriores.'], 409);
        }
        $date = $r->input('date');
        $time = $r->input('time', '');
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || (!is_string($time) || ($time !== '' && !preg_match('/^(0[89]|1[0-7]):(00|30)$/', $time)))) {
            Response::json(['error' => 'Data ou horário inválido.'], 422);
        }
        if (!AppointmentRepository::parseSchedule($date . ' 08:00')) Response::json(['error' => 'Data inválida.'], 422);
        try {
            $appointments = new AppointmentRepository();
            $slots = $appointments->availableSlots($date, $state['service_ids'], null, (int) $state['pet_id']);
            $staff = $time === '' ? [] : $appointments->staffAvailability($date . ' ' . $time, $state['service_ids'], (int) $state['pet_id']);
            Response::json(['slots' => $slots, 'staff' => $staff]);
        } catch (Throwable $e) {
            error_log('Booking availability failed: ' . get_debug_type($e) . ' code=' . $e->getCode());
            Response::json(['error' => 'Não foi possível consultar a agenda. Tente novamente.'], 503);
        }
    }

    public function staffPhoto(Request $r): void
    {
        $staff = (new UserRepository())->findActiveStaff((int) $r->param('id'));
        if (!$staff || empty($staff['avatar_path'])) { http_response_code(404); return; }
        if (PrivateImageResponse::isNotModified(SupabaseStorageService::USER_BUCKET, $staff['avatar_path'])) return;
        try {
            $bytes = (new SupabaseStorageService())->download(SupabaseStorageService::USER_BUCKET, $staff['avatar_path']);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Formato inválido.');
            PrivateImageResponse::send($bytes, $mime, SupabaseStorageService::USER_BUCKET, $staff['avatar_path']);
        } catch (Throwable) { http_response_code(503); }
    }

    public function saveBookingStep(Request $r): void
    {
        $step = BookingService::stepNumber((string) $r->param('step'));
        if ($step === null) Response::redirect('/cliente/agendamento/' . BookingService::stepSlug((new BookingService())->nextIncompleteStep()));

        try {
            $next = (new BookingService())->storeStep($step, $r->all(), (int) Session::user()['id']);

            if ($next === 0) {
                Session::flash('success', 'Agendamento solicitado com sucesso.');
                Response::redirect('/cliente/agendamentos');
            }

            Response::redirect('/cliente/agendamento/' . BookingService::stepSlug($next));
        } catch (DomainException $e) {
            if ($step === 4) {
                $state = (new BookingService())->state();
                $state['schedule_validated'] = false;
                Session::put('booking', $state);
            }
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/agendamento/' . BookingService::stepSlug(min($step, (new BookingService())->nextIncompleteStep())));
        } catch (Throwable $e) {
            error_log('Booking failed: ' . get_debug_type($e) . ' code=' . $e->getCode());
            Session::flash('error', 'Não foi possível concluir a reserva. Seus dados foram mantidos; tente novamente.');
            Response::redirect('/cliente/agendamento/' . BookingService::stepSlug($step));
        }
    }

    public function cancelAppointment(Request $r): void
    {
        $ok = (new AppointmentRepository())->cancelForUser((int) $r->param('id'), (int) Session::user()['id']);
        Session::flash($ok ? 'success' : 'error', $ok ? 'Agendamento cancelado.' : 'Não foi possível cancelar este agendamento.');
        Response::redirect('/cliente/agendamentos');
    }


    private function ownedPet(int $id): array
    {
        $pet = (new PetRepository())->findForUser($id, (int) Session::user()['id']);

        if (!$pet) {
            http_response_code(404);
            View::render(
                'pages/errors/404',
                ['title' => 'Pet não encontrado'],
                'auth'
            );
            exit;
        }
        return $pet;
    }

    private function petData(Request $r): array
    {
        foreach (['name', 'species', 'breed', 'sex', 'birth_date', 'weight', 'allergies_other', 'behavior', 'special_needs', 'coat_color', 'notes'] as $field) {
            $value = $r->input($field);
            if ($value !== null && !is_string($value)) {
                throw new InvalidArgumentException('Os dados do pet são inválidos.');
            }
        }
        $species = (string) $r->input('species');
        $sex = (string) $r->input('sex');

        if (!in_array($species, ['Cachorro', 'Gato'], true))
            throw new InvalidArgumentException('Selecione uma espécie válida.');

        if (!in_array($sex, ['Fêmea', 'Macho'], true))
            throw new InvalidArgumentException('Selecione o sexo do pet.');

        $weight = $r->input('weight');

        if ($weight !== null && $weight !== '' && (!is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 9999))
            throw new InvalidArgumentException('Informe um peso válido.');

        $hasAllergies = $r->input('has_allergies');
        if ($hasAllergies !== null && $hasAllergies !== '1') {
            throw new InvalidArgumentException('Informe se o pet tem alergias.');
        }
        $allergies = $hasAllergies === '1'
            ? Validator::text($r->input('allergies_other', ''), 'Descrição das alergias', 1, 1200)
            : '';
        $coatColor = trim((string) $r->input('coat_color', ''));
        if ($coatColor !== '') {
            $coatColor = Validator::text($coatColor, 'Cor do pelo', 1, 60);
        }

        return [
            'name' => Validator::text($r->input('name'), 'Nome do pet', 1, 80),
            'breed' => Validator::text($r->input('breed'), 'Raça', 1, 100),
            'species' => $species,
            'sex' => $sex,
            'birth_date' => trim((string) $r->input('birth_date')) !== ''
                ? Validator::date($r->input('birth_date'), allowFuture: true)
                : null,
            'allergies' => $allergies,
            'neutered' => $r->input('neutered') === '1',
            'coat_color' => $coatColor,
        ] + $r->all();
    }
}
