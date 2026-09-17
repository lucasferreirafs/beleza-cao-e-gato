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

    public function findActive(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT id,name,description,duration_minutes,price FROM services WHERE id=:id AND active=true');
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }
}
