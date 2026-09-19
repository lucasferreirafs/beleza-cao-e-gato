<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use PDOException;

final class PetRepository
{
    public function allForUser(int $userId): array
    {
        $s = Database::connection()->prepare('SELECT * FROM pets WHERE owner_id=:owner ORDER BY name');
        $s->execute(['owner' => $userId]);
        return $s->fetchAll();
    }

    public function create(int $ownerId, array $data): int
    {
        $sql = '
            INSERT INTO pets (owner_id,name,species,breed,sex,birth_date,weight,allergies,neutered,behavior,special_needs,notes) 
            VALUES (:owner,:name,:species,:breed,:sex,:birth_date,:weight,:allergies,:neutered,:behavior,:special_needs,:notes) 
            RETURNING id
        ';
        $s = Database::connection()->prepare($sql);
        $s->execute($this->params($ownerId, $data));
        return (int) $s->fetchColumn();
    }

    public function findForUser(int $id, int $ownerId): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM pets WHERE id=:id AND owner_id=:owner');
        $s->execute(['id' => $id, 'owner' => $ownerId]);
        return $s->fetch() ?: null;
    }

    public function update(int $id, int $ownerId, array $data): bool
    {
        $sql = '
            UPDATE pets 
            SET name=:name,species=:species,breed=:breed,sex=:sex,birth_date=:birth_date,weight=:weight,allergies=:allergies,neutered=:neutered,behavior=:behavior,special_needs=:special_needs,notes=:notes,updated_at=now() 
            WHERE id=:id 
            AND owner_id=:owner';
        $s = Database::connection()->prepare($sql);

        $params = $this->params($ownerId, $data);
        $params['id'] = $id;
        $s->execute($params);
        return $s->rowCount() === 1;
    }

    public function delete(int $id, int $ownerId): bool
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

    private function params(int $ownerId, array $data): array
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
            'notes' => $nullable('notes')
        ];
    }
}
