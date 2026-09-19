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
use App\Validation\Validator;
use DomainException;
use InvalidArgumentException;

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
        try {
            (new PetRepository())->create((int) Session::user()['id'], $this->petData($r));
            Session::flash('success', 'Pet cadastrado com sucesso.');
            Response::redirect('/cliente/pets');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/pets/novo');
        }
    }

    public function petProfile(Request $r): void
    {
        $pet = $this->ownedPet((int) $r->param('id'));
        View::render(
            'pages/client/pet-profile',
            $this->base($pet['name'], 'pets', compact('pet'))
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
        try {
            if (!(new PetRepository())->update($id, (int) Session::user()['id'], $this->petData($r)))
                throw new DomainException('Pet não encontrado.');

            Session::flash('success', 'Dados do pet atualizados.');
            Response::redirect('/cliente/pets/' . $id);
        } catch (InvalidArgumentException | DomainException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/pets/' . $id . '/editar');
        }
    }

    public function deletePet(Request $r): void
    {
        if ((new PetRepository())->delete((int) $r->param('id'), (int) Session::user()['id']))
            Session::flash('success', 'Pet excluído.');
        else
            Session::flash('error', 'Pet não encontrado ou vinculado a um agendamento.');

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
        $step = max(1, min(5, (int) ($r->param('step') ?? 1)));
        $booking = new BookingService();
        $state = $booking->state();

        if ($step > $booking->nextIncompleteStep())
            Response::redirect('/cliente/agendamento/' . $booking->nextIncompleteStep());

        $pets = (new PetRepository())->allForUser((int) Session::user()['id']);
        $services = (new ServiceRepository())->active();
        $staff = (new UserRepository())->allStaff();
        $date = (string) ($r->input('date', $state['date'] ?? (new \DateTimeImmutable('tomorrow'))->format('Y-m-d')));
        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            Session::flash('error', 'Escolha uma data válida para consultar os horários.');
            $date = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
        }
        
        $slots = ($step === 3 && !empty($state['service_id'])) ? (new AppointmentRepository())->availableSlots($date, (int) $state['service_id']) : [];

        if ($step === 4) {
            $staff = (new AppointmentRepository())->availableStaff($state['date'] . ' ' . $state['time'], (int) $state['service_id']);
            if (!$staff) {
                Session::flash('error', 'Este horário acabou de ficar indisponível. Escolha outro.');
                Response::redirect('/cliente/agendamento/3');
            }
        }

        View::render(
            'pages/client/booking',
            $this->base('Novo Agendamento', 'appointments', compact('step', 'state', 'pets', 'services', 'staff', 'date', 'slots')) + ['error' => Session::flash('error')]
        );
    }

    public function saveBookingStep(Request $r): void
    {
        $step = max(1, min(5, (int) ($r->param('step') ?? 1)));

        try {
            $next = (new BookingService())->storeStep($step, $r->all(), (int) Session::user()['id']);

            if ($next === 0) {
                Session::flash('success', 'Agendamento solicitado com sucesso.');
                Response::redirect('/cliente/agendamentos');
            }

            Response::redirect('/cliente/agendamento/' . $next);
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/cliente/agendamento/' . min($step, (new BookingService())->nextIncompleteStep()));
        }
    }

    public function cancelAppointment(Request $r): void
    {
        $ok = (new AppointmentRepository())->cancelForUser((int) $r->param('id'), (int) Session::user()['id']);
        Session::flash($ok ? 'success' : 'error', $ok ? 'Agendamento cancelado.' : 'Não foi possível cancelar este agendamento.');
        Response::redirect('/cliente/agendamentos');
    }

    public function profile(Request $r): void
    {
        $profile = (new UserRepository())->findById((int) Session::user()['id']);
        View::render(
            'pages/client/profile',
            $this->base('Meu Perfil', 'profile', compact('profile'))
        );
    }

    public function updateProfile(Request $r): void
    {
        try {
            $data = [
                'name' => Validator::text($r->input('name'), 'Nome completo'),
                'phone' => Validator::phone($r->input('phone')),
                'birth_date' => Validator::date($r->input('birth_date'))
            ];

            (new UserRepository())->updateProfile((int) Session::user()['id'], $data);
            $fresh = (new UserRepository())->findById((int) Session::user()['id']);

            if ($fresh)
                Session::refreshUser($fresh);

            Session::flash('success', 'Perfil atualizado com sucesso.');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/cliente/perfil');
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
        $species = (string) $r->input('species');
        $sex = (string) $r->input('sex');

        if (!in_array($species, ['Cachorro', 'Gato'], true))
            throw new InvalidArgumentException('Selecione uma espécie válida.');

        if (!in_array($sex, ['Fêmea', 'Macho'], true))
            throw new InvalidArgumentException('Selecione o sexo do pet.');

        $weight = $r->input('weight');

        if ($weight !== null && $weight !== '' && (!is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 9999))
            throw new InvalidArgumentException('Informe um peso válido.');

        return [
            'name' => Validator::text($r->input('name'), 'Nome do pet', 1, 80),
            'breed' => Validator::text($r->input('breed'), 'Raça', 1, 100),
            'species' => $species,
            'sex' => $sex,
            'birth_date' => trim((string) $r->input('birth_date')) !== ''
                ? Validator::date($r->input('birth_date'), allowFuture: true)
                : null
        ] + $r->all();
    }
}
