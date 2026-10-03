<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class ServiceRepository
{
    public function active(): array
    {
        return Database::connection()->query('SELECT id,name,description,duration_minutes,price FROM services WHERE active=true ORDER BY name')->fetchAll();
    }

    public function findActive(string $id): ?array
    {
        $s = Database::connection()->prepare('SELECT id,name,description,duration_minutes,price FROM services WHERE id=:id AND active=true');
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }

    public function findActiveMany(array $ids): array
    {
        if (!$ids) return [];
        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $index => $id) {
            $key = 'service_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (string) $id;
        }
        $s = Database::connection()->prepare(
            'SELECT id,name,description,duration_minutes,price FROM services WHERE active=true AND id IN (' . implode(',', $placeholders) . ')'
        );
        $s->execute($params);
        $rows = [];
        foreach ($s->fetchAll() as $row) $rows[$row['id']] = $row;
        $ordered = [];
        foreach ($ids as $id) if (isset($rows[$id])) $ordered[] = $rows[$id];
        return $ordered;
    }
}
