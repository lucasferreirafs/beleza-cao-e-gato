<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Consultas de leitura do diretório de pets da área do funcionário. */
final class StaffPetRepository
{
    private const PET_COLUMNS = 'p.id,p.owner_id,p.name,p.species,p.breed,p.sex,p.birth_date,p.weight,p.allergies,p.has_allergies,p.neutered,p.behavior,p.special_needs,p.notes,p.coat_color,p.photo_path,p.created_at,p.updated_at';

    public function paginate(string $search, int $page, int $perPage = 10): array
    {
        $pdo = Database::connection();
        $where = "u.role='client'";
        $params = [];
        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where .= " AND (p.name ILIKE :pet ESCAPE '!' OR p.breed ILIKE :breed ESCAPE '!' OR u.name ILIKE :owner ESCAPE '!')";
            $params = ['pet' => $pattern, 'breed' => $pattern, 'owner' => $pattern];
        }

        $count = $pdo->prepare('SELECT count(*) FROM pets p JOIN users u ON u.id=p.owner_id WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare('SELECT p.id,p.name pet_name,p.breed,u.name owner_name FROM pets p JOIN users u ON u.id=p.owner_id WHERE ' . $where . ' ORDER BY lower(p.name),p.id LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return ['pets' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function profile(string $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT ' . self::PET_COLUMNS . ',u.id owner_user_id,u.name owner_name FROM pets p JOIN users u ON u.id=p.owner_id WHERE p.id=:id AND u.role=\'client\'');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!$row) return null;

        $client = ['id' => $row['owner_user_id'], 'name' => $row['owner_name']];
        unset($row['owner_user_id'], $row['owner_name']);
        return ['pet' => $row, 'client' => $client];
    }
}
