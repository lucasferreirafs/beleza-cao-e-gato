<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;
use DomainException;

final class AppointmentRepository
{
    public function allForUser(int $userId): array
    {
        $s = Database::connection()->prepare('
            SELECT a.*,p.name pet_name,sv.name service_name,sv.duration_minutes,u.name staff_name 
            FROM appointments a 
            JOIN pets p 
            ON p.id=a.pet_id 
            JOIN services sv 
            ON sv.id=a.service_id 
            LEFT JOIN users u 
            ON u.id=a.staff_id 
            WHERE p.owner_id=:owner 
            ORDER BY a.scheduled_at DESC
        ');

        $s->execute(['owner' => $userId]);
        return $s->fetchAll();
    }

    public function allForPet(int $petId, int $ownerId): array
    {
        $s = Database::connection()->prepare('
            SELECT a.*,p.name pet_name,sv.name service_name,sv.duration_minutes,u.name staff_name 
            FROM appointments a 
            JOIN pets p 
            ON p.id=a.pet_id 
            JOIN services sv 
            ON sv.id=a.service_id 
            LEFT JOIN users u 
            ON u.id=a.staff_id 
            WHERE a.pet_id=:pet AND p.owner_id=:owner 
            ORDER BY a.scheduled_at DESC
        ');

        $s->execute(['pet' => $petId, 'owner' => $ownerId]);
        return $s->fetchAll();
    }

    public function availableSlots(string $date, int $serviceId, ?int $staffId = null): array
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$day || $day->format('Y-m-d') !== $date)
            return [];

        $sql = "
            WITH settings AS (SELECT CAST(:booking_date AS date) AS booking_day, CAST(:staff AS bigint) AS staff_id),
            selected_service AS (SELECT duration_minutes 
            FROM services 
            WHERE id=:service 
            AND active=true), 
            slots AS (SELECT generate_series(settings.booking_day+time '08:00',settings.booking_day+time '17:30',interval '30 minutes') AS slot
            FROM settings) 
            SELECT to_char(slot,'HH24:MI') label 
            FROM slots,selected_service ss,settings
            WHERE slot>now() 
            AND EXISTS (SELECT 1 FROM users u WHERE u.role='staff' AND u.status='active' AND (settings.staff_id IS NULL OR u.id=settings.staff_id)
            AND NOT EXISTS (SELECT 1 FROM appointments a JOIN services booked ON booked.id=a.service_id WHERE a.staff_id=u.id AND a.status 
            NOT IN ('cancelled','canceled') 
            AND a.scheduled_at < slot + make_interval(mins=>ss.duration_minutes) 
            AND a.scheduled_at + make_interval(mins=>booked.duration_minutes) > slot)) 
            ORDER BY slot
        ";

        $s = Database::connection()->prepare($sql);
        $s->execute([
            'service' => $serviceId,
            'booking_date' => $date,
            'staff' => $staffId
        ]);
        return array_column($s->fetchAll(), 'label');
    }

    public function availableStaff(string $scheduledAt, int $serviceId): array
    {
        $s = Database::connection()->prepare("
            WITH requested AS (
                SELECT CAST(:scheduled_at AS timestamptz) AS start_at, duration_minutes
                FROM services WHERE id=:service AND active=true
            )
            SELECT u.id,u.name
            FROM users u CROSS JOIN requested r
            WHERE u.role='staff' AND u.status='active' AND r.start_at>now()
            AND NOT EXISTS (
                SELECT 1 FROM appointments a JOIN services booked ON booked.id=a.service_id
                WHERE a.staff_id=u.id AND a.status NOT IN ('cancelled','canceled')
                AND a.scheduled_at < r.start_at + make_interval(mins=>r.duration_minutes)
                AND a.scheduled_at + make_interval(mins=>booked.duration_minutes) > r.start_at
            )
            ORDER BY u.name,u.id
        ");
        $s->execute(['scheduled_at' => $scheduledAt, 'service' => $serviceId]);
        return $s->fetchAll();
    }

    public function create(int $ownerId, array $data): int
    {
        $scheduledAt = (string) ($data['scheduled_at'] ?? '');
        $scheduled = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $scheduledAt);

        if (!$scheduled || $scheduled->format('Y-m-d H:i') !== $scheduledAt
            || $scheduled <= new DateTimeImmutable()
            || !preg_match('/^(0[89]|1[0-7]):(00|30)$/', $scheduled->format('H:i')))
            throw new DomainException('Escolha uma data e um horário futuro disponível.');

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pet = $pdo->prepare('SELECT id FROM pets WHERE id=:pet AND owner_id=:owner');
            $pet->execute(['pet' => $data['pet_id'], 'owner' => $ownerId]);

            if (!$pet->fetchColumn())
                throw new DomainException('O pet selecionado não pertence à sua conta.');

            $service = $pdo->prepare('SELECT id,price,duration_minutes FROM services WHERE id=:service AND active=true');
            $service->execute(['service' => $data['service_id']]);
            $serviceData = $service->fetch();

            if (!$serviceData)
                throw new DomainException('O serviço selecionado não está disponível.');

            $staffId = !empty($data['staff_id']) ? (int) $data['staff_id'] : null;
        
            $lock = $pdo->prepare("SELECT id FROM users WHERE role='staff' AND status='active'"
                . ($staffId !== null ? ' AND id=:staff' : '') . ' ORDER BY id FOR UPDATE');
                
            $lock->execute($staffId !== null ? ['staff' => $staffId] : []);
            $lock->fetchAll();

            $availableStaff = null;
            foreach ($this->availableStaff($scheduledAt, (int) $data['service_id']) as $member) {
                if ($staffId === null || (int) $member['id'] === $staffId) {
                    $availableStaff = (int) $member['id'];
                    break;
                }
            }

            if (!$availableStaff)
                throw new DomainException('Este horário acabou de ficar indisponível. Escolha outro.');

            $s = $pdo->prepare("
                INSERT INTO appointments (pet_id,service_id,staff_id,scheduled_at,status,price,notes) 
                VALUES (:pet,:service,:staff,:scheduled_at,'pending',:price,:notes) 
                RETURNING id
            ");
            $s->execute([
                'pet' => $data['pet_id'],
                'service' => $data['service_id'],
                'staff' => $availableStaff,
                'scheduled_at' => $data['scheduled_at'],
                'price' => $serviceData['price'],
                'notes' => ($data['notes'] ?? '') ?: null
            ]);

            $id = (int) $s->fetchColumn();
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }

    public function cancelForUser(int $id, int $ownerId): bool
    {
        $s = Database::connection()->prepare("
            UPDATE appointments a 
            SET status='cancelled',updated_at=now() 
            FROM pets p 
            WHERE a.id=:id 
            AND p.id=a.pet_id 
            AND p.owner_id=:owner 
            AND a.status 
            IN ('pending','confirmed','scheduled') 
            AND a.scheduled_at>now()
        ");
        
        $s->execute(['id' => $id, 'owner' => $ownerId]);
        return $s->rowCount() === 1;
    }
}
