<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use App\Support\Uuid;

/** Detects capabilities before issuing SQL; never changes schema during a request. */
final class AppointmentStorage
{
    public const PREFIX = '@beleza-booking:v1:';
    /** Shared only within this PHP request, isolated by PDO connection. */
    private static ?\WeakMap $capabilitiesByConnection = null;
    public function __construct(private PDO $pdo) {}

    /** CLI/tests that change schema on the same connection must invalidate it explicitly. */
    public static function clearCapabilities(PDO $pdo): void
    {
        if (self::$capabilitiesByConnection !== null) unset(self::$capabilitiesByConnection[$pdo]);
    }

    public function capabilities(): array
    {
        self::$capabilitiesByConnection ??= new \WeakMap();
        if (isset(self::$capabilitiesByConnection[$this->pdo])) return self::$capabilitiesByConnection[$this->pdo];
        $rows = $this->pdo->query("SELECT attrelid::regclass::text relation,attname FROM pg_attribute
            WHERE attrelid IN (to_regclass('appointments'),to_regclass('appointment_services'))
            AND attnum>0 AND NOT attisdropped")->fetchAll();
        $columns = [];
        foreach ($rows as $row) $columns[basename(str_replace('.', '/', $row['relation']))][] = $row['attname'];
        return self::$capabilitiesByConnection[$this->pdo] = [
            'duration' => in_array('duration_minutes', $columns['appointments'] ?? [], true),
            'items' => !array_diff(['appointment_id','service_id','position','service_name','price','duration_minutes'], $columns['appointment_services'] ?? []),
        ];
    }

    public static function encode(array $services, ?string $notes): string
    {
        return self::PREFIX . json_encode(['services' => $services, 'notes' => $notes], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public static function decode(?string $notes): ?array
    {
        if (!str_starts_with($notes ?? '', self::PREFIX)) return null;
        $data = json_decode(substr($notes, strlen(self::PREFIX)), true);
        if (!is_array($data) || !is_array($data['services'] ?? null) || !$data['services']
            || !array_key_exists('notes', $data) || ($data['notes'] !== null && !is_string($data['notes']))) {
            throw new RuntimeException('Metadados do agendamento inválidos.');
        }
        foreach ($data['services'] as $service) {
            if (!is_array($service) || Uuid::parse($service['id'] ?? null) === null || !is_string($service['name'] ?? null)
                || (int) ($service['duration_minutes'] ?? 0) < 1 || !is_numeric($service['price'] ?? null)) {
                throw new RuntimeException('Serviços do agendamento inválidos.');
            }
        }
        return $data;
    }

    public function hydrate(array $rows): array
    {
        if (!$rows) return [];
        $items = [];
        if ($this->capabilities()['items']) {
            foreach (array_chunk(array_column($rows, 'id'), 500) as $ids) {
                $s = $this->pdo->prepare('SELECT appointment_id,service_id id,service_name name,price,duration_minutes
                    FROM appointment_services WHERE appointment_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY position');
                $s->execute($ids);
                foreach ($s->fetchAll() as $item) $items[$item['appointment_id']][] = $item;
            }
        }
        foreach ($rows as &$row) {
            $payload = self::decode($row['notes'] ?? null);
            $services = $payload['services'] ?? $items[$row['id']] ?? [[
                'id' => $row['service_id'], 'name' => $row['service_name'],
                'price' => $row['price'], 'duration_minutes' => $row['duration_minutes'] ?? $row['legacy_duration'],
            ]];
            $row['services'] = $services;
            $row['service_name'] = implode(', ', array_column($services, 'name'));
            $row['duration_minutes'] = array_sum(array_map(static fn(array $s): int => (int) $s['duration_minutes'], $services));
            if ($payload !== null) $row['notes'] = $payload['notes'];
        }
        unset($row);
        return $rows;
    }

    public function insert(array $params, array $services): string
    {
        $schema = $this->capabilities();
        $params['service'] = $services[0]['id'];
        // Legacy TEXT holds a versioned envelope, including the unmodified user note.
        // Escape the reserved prefix on modern schemas too: user text is never trusted as metadata.
        if (!$schema['items'] || str_starts_with($params['notes'] ?? '', self::PREFIX)) {
            $params['notes'] = self::encode($services, $params['notes']);
        }
        if (!$schema['duration']) unset($params['duration']);
        $s = $this->pdo->prepare("INSERT INTO appointments
            (pet_id,service_id,staff_id,scheduled_at,status,price,notes" . ($schema['duration'] ? ',duration_minutes' : '') . ")
            VALUES (:pet,:service,:staff,:scheduled_at,'pending',:price,:notes" . ($schema['duration'] ? ',:duration' : '') . ') RETURNING id');
        $s->execute($params);
        $id = (string) $s->fetchColumn();
        if ($schema['items']) $this->writeItems($id, $services);
        return $id;
    }

    public function writeItems(string $id, array $services): void
    {
        $s = $this->pdo->prepare('INSERT INTO appointment_services
            (appointment_id,service_id,position,service_name,price,duration_minutes)
            VALUES (:appointment,:service,:position,:name,:price,:duration)');
        foreach ($services as $position => $service) {
            $s->execute(['appointment' => $id, 'service' => $service['id'], 'position' => $position + 1,
                'name' => $service['name'], 'price' => $service['price'], 'duration' => $service['duration_minutes']]);
        }
    }
}
