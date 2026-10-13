<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use PDOException;

final class PetRepository
{
    public function allForUser(string $userId): array
    {
        $s = Database::connection()->prepare('SELECT * FROM pets WHERE owner_id=:owner ORDER BY name');
        $s->execute(['owner' => $userId]);
        return $s->fetchAll();
    }

    /** @return array{pets:list<array<string,mixed>>,total:int,page:int,pages:int,perPage:int} */
    public function pageForUser(string $userId, int $page, int $perPage = 10): array
    {
        $perPage = max(1, min($perPage, 100));
        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT count(*) FROM pets WHERE owner_id=:owner');
        $count->execute(['owner' => $userId]);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT * FROM pets WHERE owner_id=:owner ORDER BY lower(name),id LIMIT :limit OFFSET :offset');
        $statement->bindValue(':owner', $userId, \PDO::PARAM_STR);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $statement->execute();

        return ['pets' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function create(string $ownerId, array $data): string
    {
        $sql = '
            INSERT INTO pets (owner_id,name,species,breed,sex,birth_date,weight,allergies,neutered,behavior,special_needs,notes,coat_color,photo_path)
            VALUES (:owner,:name,:species,:breed,:sex,:birth_date,:weight,:allergies,:neutered,:behavior,:special_needs,:notes,:coat_color,:photo_path)
            RETURNING id
        ';
        $s = Database::connection()->prepare($sql);
        $s->execute($this->params($ownerId, $data));
        return (string) $s->fetchColumn();
    }

    public function findForUser(string $id, string $ownerId): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM pets WHERE id=:id AND owner_id=:owner');
        $s->execute(['id' => $id, 'owner' => $ownerId]);
        return $s->fetch() ?: null;
    }

    public function update(string $id, string $ownerId, array $data): bool
    {
        $sql = '
            UPDATE pets 
            SET name=:name,species=:species,breed=:breed,sex=:sex,birth_date=:birth_date,weight=:weight,allergies=:allergies,neutered=:neutered,behavior=:behavior,special_needs=:special_needs,notes=:notes,coat_color=:coat_color,photo_path=:photo_path,updated_at=now()
            WHERE id=:id 
            AND owner_id=:owner';
        $s = Database::connection()->prepare($sql);

        $params = $this->params($ownerId, $data);
        $params['id'] = $id;
        $s->execute($params);
        return $s->rowCount() === 1;
    }

    public function delete(string $id, string $ownerId): bool
    {
        try {
            $s = Database::connection()->prepare('DELETE FROM pets WHERE id=:id AND owner_id=:owner');
            $s->execute(compact('id') + ['owner' => $ownerId]);
            return $s->rowCount() === 1;
        } catch (PDOException $e) {
            if ($e->getCode() === '23503')
                return false;
            throw $e;
        }
    }

    private function params(string $ownerId, array $data): array
    {
        $nullable = static fn(string $key): mixed => ($data[$key] ?? '') !== '' ? $data[$key] : null;
        return [
            'owner' => $ownerId,
            'name' => trim((string) ($data['name'] ?? '')),
            'species' => trim((string) ($data['species'] ?? '')),
            'breed' => trim((string) ($data['breed'] ?? '')),
            'sex' => trim((string) ($data['sex'] ?? '')),
            'birth_date' => $nullable('birth_date'),
            'weight' => $nullable('weight'),
            'allergies' => $nullable('allergies'),
            'neutered' => !empty($data['neutered']) ? 1 : 0,
            'behavior' => $nullable('behavior'),
            'special_needs' => $nullable('special_needs'),
            'notes' => $nullable('notes'),
            'coat_color' => $nullable('coat_color'),
            'photo_path' => $nullable('photo_path'),
        ];
    }
}
