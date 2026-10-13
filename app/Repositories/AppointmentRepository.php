<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Env;
use App\Services\BookingService;
use App\Services\AppointmentLifecycleService;
use App\Support\AppointmentStatus;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PDO;
use App\Support\Uuid;

final class AppointmentRepository
{
    private function rows(string $condition = 'true', array $params = [], string $order = 'a.scheduled_at DESC'): array
    {
        $pdo = Database::connection();
        AppointmentLifecycleService::maintainBeforeRead($pdo);
        $s = $pdo->prepare("SELECT a.*,p.id pet_id,p.photo_path pet_photo_path,p.name pet_name,p.weight pet_weight,p.coat_color pet_coat_color,
            p.allergies pet_allergies,p.special_needs pet_special_needs,p.behavior pet_behavior,p.notes pet_notes,
            p.species pet_species,p.breed pet_breed,p.sex pet_sex,p.birth_date pet_birth_date,p.neutered pet_neutered,
            c.id owner_id,c.name client_name,c.phone client_phone,c.email client_email,s.name service_name,
            s.duration_minutes legacy_duration,u.name staff_name
            FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users c ON c.id=p.owner_id
            JOIN services s ON s.id=a.service_id LEFT JOIN users u ON u.id=a.staff_id
            WHERE {$condition} ORDER BY {$order}");
        $s->execute($params);
        return (new AppointmentStorage($pdo))->hydrate($s->fetchAll());
    }

    public function allForUser(string $id): array { return $this->rows('p.owner_id=:owner', ['owner' => $id]); }
    public function allForPet(string $id, string $ownerId): array { return $this->rows('a.pet_id=:pet AND p.owner_id=:owner', ['pet' => $id, 'owner' => $ownerId]); }

    /** @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function pageForPet(string $id, string $ownerId, int $page, int $perPage = 10): array
    {
        $perPage = max(1, min($perPage, 100));
        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT count(*) FROM appointments a JOIN pets p ON p.id=a.pet_id WHERE a.pet_id=:pet AND p.owner_id=:owner');
        $count->execute(['pet' => $id, 'owner' => $ownerId]);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        return [
            'rows' => $this->rows('a.pet_id=:pet AND p.owner_id=:owner', ['pet' => $id, 'owner' => $ownerId],
                "a.scheduled_at DESC,a.id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage)),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }
    public function allForStaff(string $id, bool $today = false): array
    {
        return $this->rows('a.staff_id=:staff' . ($today ? ' AND a.scheduled_at::date=current_date' : ''), ['staff' => $id]);
    }
    public function findForStaff(string $id, string $staffId): ?array
    {
        return $this->rows('a.id=:id AND a.staff_id=:staff', ['id' => $id, 'staff' => $staffId])[0] ?? null;
    }

    public function lastGroomingForPet(string $petId, string $exceptId): ?string
    {
        $rows = $this->rows("a.pet_id=:pet AND a.id<>:except AND a.status='completed'",
            ['pet' => $petId, 'except' => $exceptId], 'a.scheduled_at DESC');
        foreach ($rows as $row) {
            foreach ($row['services'] as $service) {
                if (stripos((string) $service['name'], 'tosa') !== false) return (string) ($row['completed_at'] ?: $row['scheduled_at']);
            }
        }
        return null;
    }
    public function allForAdmin(bool $recent = false): array { return $this->rows('true', [], $recent ? 'a.created_at DESC LIMIT 8' : 'a.scheduled_at DESC'); }

    /** @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function staffPage(string $staffId, string $search, string $date, int $page, int $perPage = 10): array
    {
        $perPage = max(1, min($perPage, 100));
        $pdo = Database::connection();
        $conditions = ['a.staff_id=:staff'];
        $params = ['staff' => $staffId];

        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $serviceItemsExist = (new AppointmentStorage($pdo))->capabilities()['items'];
            $serviceItemSearch = $serviceItemsExist
                ? "OR EXISTS (SELECT 1 FROM appointment_services item
                    WHERE item.appointment_id=a.id AND item.service_name ILIKE :service_item ESCAPE '!')"
                : '';
            $conditions[] = "(p.name ILIKE :pet ESCAPE '!' OR c.name ILIKE :client ESCAPE '!'
                OR s.name ILIKE :service ESCAPE '!' {$serviceItemSearch})";
            $params += ['pet' => $pattern, 'client' => $pattern, 'service' => $pattern];
            if ($serviceItemsExist) $params['service_item'] = $pattern;
        }

        if ($date !== '') {
            $timezone = new DateTimeZone((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
            $start = new DateTimeImmutable($date . ' 00:00:00', $timezone);
            $end = $start->modify('+1 day');
            $conditions[] = 'a.scheduled_at>=:date_start AND a.scheduled_at<:date_end';
            $params['date_start'] = $start->format('c');
            $params['date_end'] = $end->format('c');
        }

        $where = implode(' AND ', $conditions);
        $count = $pdo->prepare("SELECT count(*)
            FROM appointments a
            JOIN pets p ON p.id=a.pet_id
            JOIN users c ON c.id=p.owner_id
            JOIN services s ON s.id=a.service_id
            WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        return [
            'rows' => $this->rows($where, $params, "a.scheduled_at DESC,a.id DESC LIMIT {$perPage} OFFSET {$offset}"),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    /** @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function adminPage(string $search, string $date, string $status, int $page, int $perPage = 10): array
    {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $pdo = Database::connection();
        AppointmentLifecycleService::maintainBeforeRead($pdo);
        $conditions = ['true'];
        $params = [];

        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $serviceItemsExist = (new AppointmentStorage($pdo))->capabilities()['items'];
            $serviceSearch = $serviceItemsExist
                ? "OR EXISTS (SELECT 1 FROM appointment_services item
                    WHERE item.appointment_id=a.id AND item.service_name ILIKE :service ESCAPE '!')"
                : '';
            $conditions[] = "(p.name ILIKE :pet ESCAPE '!' OR c.name ILIKE :client ESCAPE '!'
                OR s.name ILIKE :legacy_service ESCAPE '!' {$serviceSearch})";
            $params += ['pet' => $pattern, 'client' => $pattern,
                'legacy_service' => $pattern];
            if ($serviceItemsExist) $params['service'] = $pattern;
        }

        if ($date !== '') {
            $timezone = new DateTimeZone((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
            $start = new DateTimeImmutable($date . ' 00:00:00', $timezone);
            $end = $start->modify('+1 day');
            $conditions[] = 'a.scheduled_at >= :date_start AND a.scheduled_at < :date_end';
            $params['date_start'] = $start->format('c');
            $params['date_end'] = $end->format('c');
        }

        if ($status !== '') {
            $conditions[] = 'a.status=:status';
            $params['status'] = $status;
        }

        $where = implode(' AND ', $conditions);
        $count = $pdo->prepare("SELECT count(*)
            FROM appointments a
            JOIN pets p ON p.id=a.pet_id
            JOIN users c ON c.id=p.owner_id
            JOIN services s ON s.id=a.service_id
            WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        return [
            'rows' => $this->rows($where, $params, "a.scheduled_at DESC,a.id DESC LIMIT {$perPage} OFFSET {$offset}"),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    public function staffAgenda(string $staffId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->rows('a.staff_id=:staff AND a.scheduled_at>=:start AND a.scheduled_at<:end',
            ['staff' => $staffId, 'start' => $start->format('c'), 'end' => $end->format('c')], 'a.scheduled_at ASC,a.id ASC');
    }

    public function nextPendingForStaff(string $staffId): ?array
    {
        return $this->rows("a.staff_id=:staff AND a.status='pending'
            AND a.scheduled_at >= clock_timestamp() - INTERVAL '30 minutes'",
            ['staff' => $staffId], 'a.scheduled_at ASC, a.id ASC LIMIT 1')[0] ?? null;
    }

    public function pendingTodayForStaff(string $staffId): array
    {
        return $this->rows("a.staff_id=:staff AND a.status='pending' AND a.scheduled_at::date=current_date",
            ['staff' => $staffId], 'a.scheduled_at ASC, a.id ASC');
    }

    public function inProgressTodayForStaff(string $staffId): array
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

    private function duration(array|string $ids): ?int
    {
        $ids = is_array($ids) ? $ids : [$ids];
        if (!$ids || count($ids) > BookingService::MAX_SERVICES) return null;
        foreach ($ids as $id) if (Uuid::parse($id) === null) return null;
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

    private function freeStaff(DateTimeImmutable $start, int $duration, array $staff, array $bookings, ?string $petId): array
    {
        $fits = $this->fits($start, $duration);
        foreach ($staff as &$person) {
            $person['available'] = $fits;
            if (!$fits) continue;
            foreach ($bookings as $booking) {
                if (($booking['staff_id'] === null || $booking['staff_id'] === $person['id']
                    || ($petId !== null && $booking['pet_id'] === $petId)) && $this->overlaps($booking, $start, $duration)) {
                    $person['available'] = false;
                    break;
                }
            }
        }
        unset($person);
        return $staff;
    }

    public function availableSlots(string $date, array|string $services, ?string $staffId = null, ?string $petId = null): array
    {
        $day = self::parseSchedule($date . ' 08:00');
        $duration = $this->duration($services);
        if (!$day || !$duration) return [];
        $staff = (new UserRepository())->allStaff();
        if ($staffId !== null) $staff = array_values(array_filter($staff, static fn(array $s): bool => $s['id'] === $staffId));
        $bookings = $this->reservations($day);
        $slots = [];
        for ($start = $day; $start < $day->setTime(18, 0); $start = $start->modify('+30 minutes')) {
            if (array_filter($this->freeStaff($start, $duration, $staff, $bookings, $petId), static fn(array $s): bool => $s['available'])) {
                $slots[] = $start->format('H:i');
            }
        }
        return $slots;
    }

    public function staffAvailability(string $scheduledAt, array|string $services, ?string $petId = null): array
    {
        $start = self::parseSchedule($scheduledAt);
        $duration = $this->duration($services);
        if (!$start || !$duration) return [];
        return $this->freeStaff($start, $duration, (new UserRepository())->allStaff(), $this->reservations($start), $petId);
    }

    public function availableStaff(string $at, array|string $services, ?string $petId = null): array
    {
        return array_values(array_filter($this->staffAvailability($at, $services, $petId), static fn(array $s): bool => $s['available']));
    }

    public function create(string $ownerId, array $data): string
    {
        $ids = $data['service_ids'] ?? (isset($data['service_id']) ? [$data['service_id']] : []);
        if (!is_array($ids) || !$ids || count($ids) > BookingService::MAX_SERVICES) throw new DomainException('Selecione serviços válidos.');
        foreach ($ids as $id) if (Uuid::parse($id) === null) throw new DomainException('Serviço inválido.');
        $ids = array_map('strtolower', array_values($ids));
        if (count($ids) !== count(array_unique($ids))) throw new DomainException('Serviços duplicados não são permitidos.');
        $petId = Uuid::parse($data['pet_id'] ?? null);
        $preference = $data['staff_id'] ?? null;
        $staffId = $preference === null || $preference === '' ? null : Uuid::parse($preference);
        if (!$petId || ($preference !== null && $preference !== '' && !$staffId)) throw new DomainException('Pet ou profissional inválido.');
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
            $services = array_map(static fn(string $id): array => $indexed[$id], $ids);
            if (isset($data['expected_services'])) {
                $quote = static fn(array $items): array => array_map(static fn(array $s): array => [
                    (string) $s['id'], (string) $s['name'], (int) round((float) $s['price'] * 100), (int) $s['duration_minutes'],
                ], $items);
                if ($quote($services) !== $quote($data['expected_services'])) {
                    throw new DomainException('Os serviços foram atualizados. Revise o valor e o horário antes de confirmar.');
                }
            }
            $duration = array_sum(array_column($services, 'duration_minutes'));
            if (!$this->fits($start, $duration)) throw new DomainException('Escolha um horário futuro que comporte todos os serviços até as 18h.');
            $lock = $pdo->prepare("SELECT id FROM users WHERE role='staff' AND status='active'" . ($staffId !== null ? ' AND id=:staff' : '') . ' ORDER BY id FOR UPDATE');
            $lock->execute($staffId !== null ? ['staff' => $staffId] : []);
            $locked = $lock->fetchAll(PDO::FETCH_COLUMN);
            $available = $this->availableStaff($at, $ids, $petId);
            $assigned = null;
            foreach ($available as $person) {
                if (in_array($person['id'], $locked, true)) { $assigned = $person['id']; break; }
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

    public function cancelForUser(string $id, string $ownerId): bool { return $this->changeStatus($id, 'cancelled', null, $ownerId); }

    public function changeStatus(string $id, string $status, ?string $staffId = null, ?string $ownerId = null): bool
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
            if (!$row || ($staffId !== null && $row['staff_id'] !== $staffId)
                || ($ownerId !== null && ($row['owner_id'] !== $ownerId || $status !== 'cancelled'))) {
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
