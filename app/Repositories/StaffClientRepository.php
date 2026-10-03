<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Diretório de leitura para funcionários: nunca selecionar dados de autenticação ou CPF. */
final class StaffClientRepository
{
    private const CLIENT_COLUMNS = 'id,name,email,phone,birth_date,status,avatar_path,created_at';
    private const PET_COLUMNS = 'id,owner_id,name,species,breed,sex,birth_date,weight,allergies,has_allergies,neutered,behavior,special_needs,notes,coat_color,photo_path,created_at,updated_at';

    public function paginate(string $search, int $page, int $perPage = 10): array
    {
        $pdo = Database::connection();
        $where = "role='client'";
        $params = [];
        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where .= " AND (name ILIKE :name ESCAPE '!' OR email ILIKE :email ESCAPE '!'";
            $params = ['name' => $pattern, 'email' => $pattern];
            $digits = preg_replace('/\D/', '', $search);
            if ($digits !== '' && preg_match('/^[0-9\s()+.\/-]+$/D', $search)) {
                $where .= " OR regexp_replace(coalesce(phone,''), '[^0-9]', '', 'g') LIKE :phone";
                $params['phone'] = '%' . $digits . '%';
            }
            $where .= ')';
        }
        $count = $pdo->prepare('SELECT count(*) FROM users WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $statement = $pdo->prepare('SELECT id,name,email,phone,status FROM users WHERE ' . $where . ' ORDER BY lower(name),id LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return ['clients' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function client(string $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT ' . self::CLIENT_COLUMNS . " FROM users WHERE id=:id AND role='client'");
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function pets(string $clientId): array
    {
        $statement = Database::connection()->prepare('SELECT ' . self::PET_COLUMNS . ' FROM pets WHERE owner_id=:owner ORDER BY lower(name),id');
        $statement->execute(['owner' => $clientId]);
        return $statement->fetchAll();
    }

    public function pet(string $clientId, string $petId): ?array
    {
        $statement = Database::connection()->prepare('SELECT ' . self::PET_COLUMNS . ' FROM pets WHERE id=:pet AND owner_id=:owner');
        $statement->execute(['pet' => $petId, 'owner' => $clientId]);
        return $statement->fetch() ?: null;
    }
}
