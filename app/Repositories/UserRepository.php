<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE lower(email) = lower(:email) LIMIT 1');
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT id,name,email,role,status,phone,cpf,birth_date,email_verified_at,created_at FROM users WHERE id=:id');
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }

    public function createClient(array $data): int
    {
        $sql = '
            INSERT INTO users (name,email,password_hash,role,status,phone,cpf,birth_date) 
            VALUES (:name,:email,:password_hash,\'client\',\'pending_verification\',:phone,:cpf,:birth_date) 
            RETURNING id
        ';

        $s = Database::connection()->prepare($sql);
        $s->execute([
            'name' => $data['name'], 'email' => strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?: null, 'cpf' => $data['cpf'] ?: null,
            'birth_date' => $data['birth_date'] ?: null,
        ]);
        return (int) $s->fetchColumn();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE lower(email)=lower(:email)' . ($exceptId ? ' AND id<>:id' : '') . ' LIMIT 1';
        $s = Database::connection()->prepare($sql);
        $params = ['email' => $email];

        if ($exceptId) $params['id'] = $exceptId;

        $s->execute($params);
        return (bool) $s->fetchColumn();
    }

    public function cpfExists(string $cpf, ?int $exceptId = null): bool
    {
        if ($cpf === '') return false;

        $sql = 'SELECT 1 FROM users WHERE cpf=:cpf' . ($exceptId ? ' AND id<>:id' : '') . ' LIMIT 1';
        $s = Database::connection()->prepare($sql);
        $params = ['cpf' => $cpf];

        if ($exceptId) $params['id'] = $exceptId;
        
        $s->execute($params);
        return (bool) $s->fetchColumn();
    }

    public function activate(int $id): void
    {
        $s = Database::connection()->prepare("UPDATE users SET status='active',email_verified_at=now(),updated_at=now() WHERE id=:id");
        $s->execute(['id' => $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $s = Database::connection()->prepare('UPDATE users SET password_hash=:hash,updated_at=now() WHERE id=:id');
        $s->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public function updateProfile(int $id, array $data): void
    {
        $s = Database::connection()->prepare('UPDATE users SET name=:name,phone=:phone,birth_date=:birth_date,updated_at=now() WHERE id=:id');
        $s->execute(['name' => $data['name'], 'phone' => $data['phone'] ?: null, 'birth_date' => $data['birth_date'] ?: null, 'id' => $id]);
    }

    public function allStaff(): array
    {
        return Database::connection()->query("SELECT id,name,email,phone,status FROM users WHERE role='staff' AND status='active' ORDER BY name")->fetchAll();
    }
}
