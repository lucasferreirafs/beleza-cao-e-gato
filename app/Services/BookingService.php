<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\AppointmentRepository;
use App\Repositories\PetRepository;
use App\Repositories\ServiceRepository;
use DateTimeImmutable;
use DomainException;
use App\Support\Uuid;

final class BookingService
{
    public const STEPS = [
        'servico' => 1,
        'escolha-pet' => 2,
        'data-e-horario' => 3,
        'confirmacao' => 4,
    ];
    public const MAX_SERVICES = 10;
    public const BUFFER_MINUTES = 30;

    public static function stepNumber(string $step): ?int
    {
        // Normalize bookmarked or already-open links from the former separate step.
        if ($step === 'profissional') return 3;
        if (isset(self::STEPS[$step])) return self::STEPS[$step];
        $legacy = filter_var($step, FILTER_VALIDATE_INT);
        return $legacy !== false && $legacy >= 1 && $legacy <= 4 ? (int) $legacy : null;
    }

    public static function stepSlug(int $number): string
    {
        $slug = array_search($number, self::STEPS, true);
        return is_string($slug) ? $slug : 'servico';
    }

    public function state(): array
    {
        $state = (array) Session::get('booking', []);
        if (empty($state['service_ids']) && !empty($state['service_id'])) {
            $state['service_ids'] = [(string) $state['service_id']];
        }
        return $state;
    }

    public function nextIncompleteStep(): int
    {
        $state = $this->state();
        if (empty($state['service_ids'])) return 1;
        if (empty($state['pet_id'])) return 2;
        if (!array_key_exists('staff_id', $state) || empty($state['date']) || empty($state['time']) || empty($state['schedule_validated'])) return 3;
        return 4;
    }

    public function storeStep(int $step, array $input, string $userId): int
    {
        if ($step < 1 || $step > 4 || $step > $this->nextIncompleteStep()) {
            throw new DomainException('Complete as etapas anteriores do agendamento.');
        }

        $state = $this->state();
        $appointments = new AppointmentRepository();

        if ($step === 1) {
            $serviceIds = $input['service_ids'] ?? (isset($input['service_id']) ? [$input['service_id']] : []);
            if (!is_array($serviceIds) || count($serviceIds) < 1 || count($serviceIds) > self::MAX_SERVICES) {
                throw new DomainException('Selecione de 1 a ' . self::MAX_SERVICES . ' serviços.');
            }
            $normalized = [];
            foreach ($serviceIds as $serviceId) {
                $id = Uuid::parse($serviceId);
                if ($id === null) throw new DomainException('Um dos serviços selecionados é inválido.');
                $normalized[] = $id;
            }
            $normalized = array_values(array_unique($normalized));
            if (count($normalized) !== count($serviceIds)) throw new DomainException('Remova serviços duplicados da seleção.');
            $active = (new ServiceRepository())->findActiveMany($normalized);
            if (count($active) !== count($normalized)) throw new DomainException('Um serviço selecionado não está mais disponível.');
            $state['service_ids'] = $normalized;
            unset($state['service_id'], $state['date'], $state['time'], $state['schedule_validated'], $state['quote']);
            if (array_key_exists('pet_id', $state)) unset($state['staff_id']);
        } elseif ($step === 2) {
            $this->requirePrevious($state, 'service_ids');
            $petId = Uuid::parse($input['pet_id'] ?? null);
            if (!$petId || !(new PetRepository())->findForUser($petId, $userId)) {
                throw new DomainException('Selecione um pet da sua conta.');
            }
            $state['pet_id'] = $petId;
            unset($state['staff_id'], $state['date'], $state['time'], $state['schedule_validated'], $state['quote']);
        } elseif ($step === 3) {
            $this->requirePrevious($state, 'pet_id');
            $this->requirePrevious($state, 'service_ids');
            $date = $input['date'] ?? '';
            $time = $input['time'] ?? '';
            if (!is_string($date) || !is_string($time)) throw new DomainException('Escolha uma data e um horário válidos.');
            $scheduled = AppointmentRepository::parseSchedule($date . ' ' . $time);
            if (!$scheduled || $scheduled->format('Y-m-d H:i') !== $date . ' ' . $time
                || $scheduled <= new DateTimeImmutable() || !preg_match('/^(0[89]|1[0-7]):(00|30)$/', $time)) {
                throw new DomainException('Escolha uma data e um horário futuro disponível.');
            }

            $preference = $input['staff_id'] ?? ($state['staff_id'] ?? null);
            if ($preference !== null && !is_string($preference) && !is_int($preference)) {
                throw new DomainException('Seleção de profissional inválida.');
            }
            $staffId = $preference === '' || $preference === null ? null : Uuid::parse((string) $preference);
            if ($preference !== '' && $preference !== null && !$staffId) {
                throw new DomainException('Selecione um profissional válido.');
            }
            if ($staffId !== null && !(new \App\Repositories\UserRepository())->findActiveStaff($staffId)) {
                throw new DomainException('Este profissional não está disponível.');
            }
            $notes = $input['notes'] ?? '';
            if (!is_string($notes) || preg_match('//u', $notes) !== 1 || preg_match_all('/./us', $notes) > 1000) {
                throw new DomainException('As observações devem ter até 1000 caracteres.');
            }
            $state['staff_id'] = $staffId;
            $state['date'] = $date;
            $state['time'] = $time;
            $state['notes'] = trim($notes);
            $state['schedule_validated'] = false;
            Session::put('booking', $state);

            $freeStaff = $appointments->availableStaff($date . ' ' . $time, $state['service_ids'], $state['pet_id']);
            if (!$freeStaff) throw new DomainException('Este horário ficou indisponível. Escolha outro.');
            if ($staffId !== null && !in_array($staffId, array_column($freeStaff, 'id'), true)) {
                throw new DomainException('O profissional selecionado está indisponível neste horário. Escolha outro.');
            }
            if (!in_array($time, $appointments->availableSlots($date, $state['service_ids'], null, $state['pet_id']), true)) {
                throw new DomainException('Este horário não está mais disponível.');
            }
            $state['schedule_validated'] = true;
            $state['quote'] = (new ServiceRepository())->findActiveMany($state['service_ids']);
        } elseif ($step === 4) {
            foreach (['pet_id', 'service_ids', 'date', 'time'] as $key) $this->requirePrevious($state, $key);
            if (!array_key_exists('staff_id', $state)) throw new DomainException('Escolha um profissional ou selecione sem preferência.');
            $appointments->create($userId, [
                'pet_id' => $state['pet_id'],
                'service_ids' => $state['service_ids'],
                'staff_id' => $state['staff_id'],
                'scheduled_at' => $state['date'] . ' ' . $state['time'],
                'notes' => $state['notes'] ?? null,
                'expected_services' => $state['quote'] ?? [],
            ]);
            Session::forget('booking');
            return 0;
        }

        Session::put('booking', $state);
        return min(4, $step + 1);
    }

    private function requirePrevious(array $state, string $key): void
    {
        if (!array_key_exists($key, $state)) throw new DomainException('O agendamento expirou. Comece novamente.');
    }
}
