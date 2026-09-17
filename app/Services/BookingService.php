<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Session;
use App\Repositories\AppointmentRepository;
use App\Repositories\PetRepository;
use App\Repositories\ServiceRepository;
use DateTimeImmutable;
use DomainException;

final class BookingService
{
    public function state(): array { return (array) Session::get('booking', []); }

    public function nextIncompleteStep(): int
    {
        $state = $this->state();
        if (empty($state['pet_id'])) return 1;
        if (empty($state['service_id'])) return 2;
        if (empty($state['date']) || empty($state['time'])) return 3;
        if (!array_key_exists('staff_id', $state)) return 4;
        return 5;
    }

    public function storeStep(int $step, array $input, int $userId): int
    {
        if ($step < 1 || $step > 5 || $step > $this->nextIncompleteStep())
            throw new DomainException('Complete as etapas anteriores do agendamento.');

        $state = $this->state();
        if ($step === 1) {
            $petId = filter_var($input['pet_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$petId || !(new PetRepository())->findForUser((int) $petId, $userId)) throw new DomainException('Selecione um pet da sua conta.');
            $state = ['pet_id' => (int) $petId];
        } elseif ($step === 2) {
            $this->requirePrevious($state, 'pet_id');
            $serviceId = filter_var($input['service_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$serviceId || !(new ServiceRepository())->findActive((int) $serviceId)) throw new DomainException('Selecione um serviço disponível.');
            $state['service_id'] = (int) $serviceId;
            unset($state['date'], $state['time'], $state['staff_id']);
        } elseif ($step === 3) {
            $this->requirePrevious($state, 'service_id');
            $date = (string) ($input['date'] ?? '');
            $time = (string) ($input['time'] ?? '');
            $scheduled = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "{$date} {$time}");
            if (!$scheduled || $scheduled->format('Y-m-d H:i') !== "{$date} {$time}" || $scheduled <= new DateTimeImmutable() || !preg_match('/^(0[89]|1[0-7]):(00|30)$/', $time)) throw new DomainException('Escolha uma data e um horário futuro disponível.');
            if (!in_array($time, (new AppointmentRepository())->availableSlots($date, (int) $state['service_id']), true)) throw new DomainException('Este horário não está disponível.');
            if (($state['date'] ?? null) !== $date || ($state['time'] ?? null) !== $time)
                unset($state['staff_id']);
            $state['date'] = $date;
            $state['time'] = $time;
        } elseif ($step === 4) {
            $this->requirePrevious($state, 'time');
            $preference = (string) ($input['staff_id'] ?? '');
            $staffId = $preference === '' ? null : filter_var($preference, FILTER_VALIDATE_INT);
            if ($staffId !== null && (!$staffId || $staffId < 1))
                throw new DomainException('Selecione um profissional disponível.');
            $available = (new AppointmentRepository())->availableStaff($state['date'] . ' ' . $state['time'], (int) $state['service_id']);
            if (!$available)
                throw new DomainException('Este horário acabou de ficar indisponível. Escolha outro.');
            if ($staffId !== null) {
                $valid = array_filter($available, static fn(array $u): bool => (int) $u['id'] === $staffId);
                if (!$valid) throw new DomainException('Selecione um profissional disponível.');
            }
            $state['staff_id'] = $staffId ? (int) $staffId : null;
            $state['notes'] = mb_substr(trim((string) ($input['notes'] ?? '')), 0, 1000);
        } elseif ($step === 5) {
            foreach (['pet_id','service_id','date','time','staff_id'] as $key) $this->requirePrevious($state, $key);
            (new AppointmentRepository())->create($userId, ['pet_id' => $state['pet_id'], 'service_id' => $state['service_id'], 'staff_id' => $state['staff_id'] ?? null, 'scheduled_at' => $state['date'] . ' ' . $state['time'], 'notes' => $state['notes'] ?? null]);
            Session::forget('booking');
            return 0;
        }
        Session::put('booking', $state);
        return min(5, $step + 1);
    }

    private function requirePrevious(array $state, string $key): void
    {
        if (!array_key_exists($key, $state)) throw new DomainException('O agendamento expirou. Comece novamente.');
    }
}
