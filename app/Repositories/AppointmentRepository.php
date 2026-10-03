<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\BookingService;
use App\Services\AppointmentLifecycleService;
use App\Support\AppointmentStatus;
use DateTimeImmutable;
use DomainException;
use PDO;

final class AppointmentRepository
{
    private function rows(string $condition = 'true', array $params = [], string $order = 'a.scheduled_at DESC'): array
    {
        $pdo = Database::connection();
        AppointmentLifecycleService::cancelExpiredPending($pdo);
        $s = $pdo->prepare("SELECT a.*,p.name pet_name,p.weight pet_weight,p.coat_color pet_coat_color,
            p.allergies pet_allergies,p.special_needs pet_special_needs,p.behavior pet_behavior,p.notes pet_notes,
            p.species pet_species,p.breed pet_breed,p.sex pet_sex,p.birth_date pet_birth_date,p.neutered pet_neutered,
            c.name client_name,c.phone client_phone,c.email client_email,s.name service_name,
            s.duration_minutes legacy_duration,u.name staff_name
            FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users c ON c.id=p.owner_id
            JOIN services s ON s.id=a.service_id LEFT JOIN users u ON u.id=a.staff_id
            WHERE {$condition} ORDER BY {$order}");
        $s->execute($params);
        return (new AppointmentStorage($pdo))->hydrate($s->fetchAll());
    }

    public function allForUser(int $id): array { return $this->rows('p.owner_id=:owner', ['owner' => $id]); }
    public function allForPet(int $id, int $ownerId): array { return $this->rows('a.pet_id=:pet AND p.owner_id=:owner', ['pet' => $id, 'owner' => $ownerId]); }
    public function allForStaff(int $id, bool $today = false): array
    {
        return $this->rows('a.staff_id=:staff' . ($today ? ' AND a.scheduled_at::date=current_date' : ''), ['staff' => $id]);
    }
    public function allForAdmin(bool $recent = false): array { return $this->rows('true', [], $recent ? 'a.created_at DESC LIMIT 8' : 'a.scheduled_at DESC'); }

    public function staffAgenda(int $staffId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->rows('a.staff_id=:staff AND a.scheduled_at>=:start AND a.scheduled_at<:end',
            ['staff' => $staffId, 'start' => $start->format('c'), 'end' => $end->format('c')], 'a.scheduled_at ASC,a.id ASC');
    }

    public function nextPendingForStaff(int $staffId): ?array
    {
        return $this->rows("a.staff_id=:staff AND a.status='pending'
            AND a.scheduled_at >= clock_timestamp() - INTERVAL '30 minutes'",
            ['staff' => $staffId], 'a.scheduled_at ASC, a.id ASC LIMIT 1')[0] ?? null;
    }

    public function pendingTodayForStaff(int $staffId): array
    {
        return $this->rows("a.staff_id=:staff AND a.status='pending' AND a.scheduled_at::date=current_date",
            ['staff' => $staffId], 'a.scheduled_at ASC, a.id ASC');
    }

    public function inProgressTodayForStaff(int $staffId): array
    {
        return $this->rows("a.staff_id=:staff AND a.status='in_progress' AND a.scheduled_at::date=current_date",
            ['staff' => $staffId], 'a.scheduled_at ASC, a.id ASC');
    }

    public static function parseSchedule(string $value): ?DateTimeImmutable
    {
        try { $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value); }
        catch (\ValueError) { return null; }
        return $date && $date->format('Y-m-d H:i') === $value ? $date : null;
    }

    private function duration(array|int $ids): ?int
    {
        $ids = is_array($ids) ? $ids : [$ids];
        if (!$ids || count($ids) > BookingService::MAX_SERVICES) return null;
        foreach ($ids as $id) if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id < 1) return null;
        if (count(array_unique($ids)) !== count($ids)) return null;
        $services = (new ServiceRepository())->findActiveMany($ids);
        return count($services) === count($ids) ? array_sum(array_column($services, 'duration_minutes')) : null;
    }

    private function fits(DateTimeImmutable $start, int $duration): bool
    {
        return $start > new DateTimeImmutable() && preg_match('/^(0[89]|1[0-7]):(00|30)$/', $start->format('H:i'))
            && $start->modify('+' . $duration . ' minutes') <= $start->setTime(18, 0);
    }

    private function reservations(DateTimeImmutable $day): array
    {
        // Include earlier bookings that could extend into this day. Services are limited to 1440 minutes.
        return $this->rows("a.status <> 'cancelled' AND a.scheduled_at>=:from_time AND a.scheduled_at<:until_time", [
            'from_time' => $day->setTime(0, 0)->modify('-' . (BookingService::MAX_SERVICES + 1) . ' days')->format('c'),
            'until_time' => $day->setTime(0, 0)->modify('+2 days')->format('c'),
        ]);
    }

    private function overlaps(array $booking, DateTimeImmutable $start, int $duration): bool
    {
        $oldStart = new DateTimeImmutable($booking['scheduled_at']);
        $buffer = BookingService::BUFFER_MINUTES;
        return $oldStart->modify("-{$buffer} minutes") < $start->modify("+{$duration} minutes")
            && $oldStart->modify('+' . ((int) $booking['duration_minutes'] + $buffer) . ' minutes') > $start;
    }

    private function freeStaff(DateTimeImmutable $start, int $duration, array $staff, array $bookings, ?int $petId): array
    {
        $fits = $this->fits($start, $duration);
        foreach ($staff as &$person) {
            $person['available'] = $fits;
            if (!$fits) continue;
            foreach ($bookings as $booking) {
                if (($booking['staff_id'] === null || (int) $booking['staff_id'] === (int) $person['id']
                    || ($petId !== null && (int) $booking['pet_id'] === $petId)) && $this->overlaps($booking, $start, $duration)) {
                    $person['available'] = false;
                    break;
                }
            }
        }
        unset($person);
        return $staff;
    }

    public function availableSlots(string $date, array|int $services, ?int $staffId = null, ?int $petId = null): array
    {
        $day = self::parseSchedule($date . ' 08:00');
        $duration = $this->duration($services);
        if (!$day || !$duration) return [];
        $staff = (new UserRepository())->allStaff();
        if ($staffId !== null) $staff = array_values(array_filter($staff, static fn(array $s): bool => (int) $s['id'] === $staffId));
        $bookings = $this->reservations($day);
        $slots = [];
        for ($start = $day; $start < $day->setTime(18, 0); $start = $start->modify('+30 minutes')) {
            if (array_filter($this->freeStaff($start, $duration, $staff, $bookings, $petId), static fn(array $s): bool => $s['available'])) {
                $slots[] = $start->format('H:i');
            }
        }
        return $slots;
    }

    public function staffAvailability(string $scheduledAt, array|int $services, ?int $petId = null): array
    {
        $start = self::parseSchedule($scheduledAt);
        $duration = $this->duration($services);
        if (!$start || !$duration) return [];
        return $this->freeStaff($start, $duration, (new UserRepository())->allStaff(), $this->reservations($start), $petId);
    }

    public function availableStaff(string $at, array|int $services, ?int $petId = null): array
    {
        return array_values(array_filter($this->staffAvailability($at, $services, $petId), static fn(array $s): bool => $s['available']));
    }

    public function create(int $ownerId, array $data): int
    {
        $ids = $data['service_ids'] ?? (isset($data['service_id']) ? [$data['service_id']] : []);
        if (!is_array($ids) || !$ids || count($ids) > BookingService::MAX_SERVICES) throw new DomainException('Selecione serviços válidos.');
        foreach ($ids as $id) if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id < 1) throw new DomainException('Serviço inválido.');
        $ids = array_map('intval', array_values($ids));
        if (count($ids) !== count(array_unique($ids))) throw new DomainException('Serviços duplicados não são permitidos.');
        $petId = filter_var($data['pet_id'] ?? null, FILTER_VALIDATE_INT);
        $preference = $data['staff_id'] ?? null;
        $staffId = $preference === null || $preference === '' ? null : filter_var($preference, FILTER_VALIDATE_INT);
        if (!$petId || $petId < 1 || ($staffId !== null && (!$staffId || $staffId < 1))) throw new DomainException('Pet ou profissional inválido.');
        $at = $data['scheduled_at'] ?? '';
        $start = is_string($at) ? self::parseSchedule($at) : null;
        if (!$start) throw new DomainException('Data ou horário inválido.');
        $notes = $data['notes'] ?? null;
        if ($notes !== null && (!is_string($notes) || preg_match('//u', $notes) !== 1 || preg_match_all('/./us', $notes) > 1000)) throw new DomainException('As observações devem ter até 1000 caracteres.');

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $pdo->exec("SET LOCAL lock_timeout='5s'");
            // All booking and status mutations share this lock; recheck after acquiring it.
            $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
            $pet = $pdo->prepare('SELECT id FROM pets WHERE id=:pet AND owner_id=:owner FOR UPDATE');
            $pet->execute(['pet' => $petId, 'owner' => $ownerId]);
            if (!$pet->fetchColumn()) throw new DomainException('Selecione um pet da sua conta.');
            $s = $pdo->prepare('SELECT id,name,price,duration_minutes FROM services WHERE active=true AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id FOR SHARE');
            $s->execute($ids);
            $indexed = array_column($s->fetchAll(), null, 'id');
            if (count($indexed) !== count($ids)) throw new DomainException('Um serviço selecionado deixou de estar disponível.');
            $services = array_map(static fn(int $id): array => $indexed[$id], $ids);
            if (isset($data['expected_services'])) {
                $quote = static fn(array $items): array => array_map(static fn(array $s): array => [
                    (int) $s['id'], (string) $s['name'], (int) round((float) $s['price'] * 100), (int) $s['duration_minutes'],
                ], $items);
                if ($quote($services) !== $quote($data['expected_services'])) {
                    throw new DomainException('Os serviços foram atualizados. Revise o valor e o horário antes de confirmar.');
                }
            }
            $duration = array_sum(array_column($services, 'duration_minutes'));
            if (!$this->fits($start, $duration)) throw new DomainException('Escolha um horário futuro que comporte todos os serviços até as 18h.');
            $lock = $pdo->prepare("SELECT id FROM users WHERE role='staff' AND status='active'" . ($staffId !== null ? ' AND id=:staff' : '') . ' ORDER BY id FOR UPDATE');
            $lock->execute($staffId !== null ? ['staff' => $staffId] : []);
            $locked = array_map('intval', $lock->fetchAll(PDO::FETCH_COLUMN));
            $available = $this->availableStaff($at, $ids, (int) $petId);
            $assigned = null;
            foreach ($available as $person) {
                if (in_array((int) $person['id'], $locked, true)) { $assigned = (int) $person['id']; break; }
            }
            if ($assigned === null) throw new DomainException('Horário indisponível para o pet ou profissional selecionado. Escolha outra opção.');
            $cents = array_sum(array_map(static fn(array $s): int => (int) round((float) $s['price'] * 100), $services));
            $id = (new AppointmentStorage($pdo))->insert([
                'pet' => $petId, 'staff' => $assigned, 'scheduled_at' => $start->format('c'),
                'price' => number_format($cents / 100, 2, '.', ''), 'duration' => $duration, 'notes' => $notes,
            ], $services);
            $pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($error instanceof \PDOException && in_array($error->getCode(), ['55P03','40001','40P01','23P01'], true)) {
                throw new DomainException('A agenda foi atualizada durante a reserva. Consulte o horário e tente novamente.', 0, $error);
            }
            throw $error;
        }
    }

    public function cancelForUser(int $id, int $ownerId): bool { return $this->changeStatus($id, 'cancelled', null, $ownerId); }

    public function changeStatus(int $id, string $status, ?int $staffId = null, ?int $ownerId = null): bool
    {
        if (!isset(AppointmentStatus::editable()[$status])) return false;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec("SET LOCAL lock_timeout='5s'");
            $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
            $s = $pdo->prepare('SELECT a.*,p.owner_id FROM appointments a JOIN pets p ON p.id=a.pet_id WHERE a.id=:id FOR UPDATE OF a');
            $s->execute(['id' => $id]);
            $row = $s->fetch();
            if (!$row || ($staffId !== null && (int) $row['staff_id'] !== $staffId)
                || ($ownerId !== null && ((int) $row['owner_id'] !== $ownerId || $status !== 'cancelled'))) {
                $pdo->rollBack();
                return false;
            }
            // Read the database clock AFTER waiting for locks, not transaction-start now().
            $now = new DateTimeImmutable($pdo->query('SELECT clock_timestamp()')->fetchColumn());
            if (AppointmentLifecycleService::isExpiredPending($row, $now)) {
                $pdo->prepare("UPDATE appointments SET status='cancelled',updated_at=clock_timestamp() WHERE id=:id AND status='pending'")
                    ->execute(['id' => $id]);
                $pdo->commit(); // Persist expiration even when rejecting a late start.
                return $status === 'cancelled';
            }
            if (!AppointmentStatus::canTransition($row['status'], $status)
                || ($ownerId !== null && $row['status'] !== 'cancelled'
                    && ($row['status'] !== 'pending' || new DateTimeImmutable($row['scheduled_at']) <= $now))) {
                $pdo->rollBack();
                return false;
            }
            if ($row['status'] === $status) {
                $pdo->commit();
                return true; // Duplicate requests must not rewrite completed_at.
            }
            $s = $pdo->prepare("UPDATE appointments SET status=:status,
                completed_at=CASE WHEN CAST(:completed AS boolean) THEN clock_timestamp() ELSE completed_at END,updated_at=clock_timestamp() WHERE id=:id");
            $s->execute(['status' => $status, 'completed' => $status === 'completed' ? 'true' : 'false', 'id' => $id]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
