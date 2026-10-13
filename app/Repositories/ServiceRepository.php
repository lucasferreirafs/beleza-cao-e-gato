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

    /** @return array{services:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function activePage(int $page, int $perPage = 6): array
    {
        $perPage = max(1, min($perPage, 100));
        $pdo = Database::connection();
        $total = (int) $pdo->query('SELECT count(*) FROM services WHERE active=true')->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT id,name,description,duration_minutes,price FROM services WHERE active=true ORDER BY lower(name),id LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $statement->execute();

        return ['services' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
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
