<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare('SELECT id, name, email, password_hash, role, status, auth_version, terms_version, terms_accepted_at, avatar_path, email_verified_at FROM users WHERE lower(email) = lower(:email) LIMIT 1');
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }

    public function findById(string $id, bool $lock = false): ?array
    {
        $s = Database::connection()->prepare('SELECT id,name,email,role,status,phone,cpf,birth_date,email_verified_at,created_at,updated_at,auth_version,terms_version,terms_accepted_at,avatar_path FROM users WHERE id=:id' . ($lock ? ' FOR UPDATE' : ''));
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }

    public function createClient(array $data): string
    {
        $sql = '
            INSERT INTO users (name,email,password_hash,role,status,phone,cpf,birth_date,terms_version,terms_accepted_at)
            VALUES (:name,:email,:password_hash,\'client\',\'pending_verification\',:phone,:cpf,:birth_date,:terms_version,now())
            RETURNING id
        ';

        $s = Database::connection()->prepare($sql);
        $s->execute([
            'name' => $data['name'], 'email' => strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?: null, 'cpf' => $data['cpf'] ?: null,
            'birth_date' => $data['birth_date'] ?: null,
            'terms_version' => \App\Services\TermsService::VERSION,
        ]);
        return (string) $s->fetchColumn();
    }

    public function emailExists(string $email, ?string $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE lower(email)=lower(:email)' . ($exceptId ? ' AND id<>:id' : '') . ' LIMIT 1';
        $s = Database::connection()->prepare($sql);
        $params = ['email' => $email];

        if ($exceptId) $params['id'] = $exceptId;

        $s->execute($params);
        return (bool) $s->fetchColumn();
    }

    public function cpfExists(string $cpf, ?string $exceptId = null): bool
    {
        if ($cpf === '') return false;

        $sql = 'SELECT 1 FROM users WHERE cpf=:cpf' . ($exceptId ? ' AND id<>:id' : '') . ' LIMIT 1';
        $s = Database::connection()->prepare($sql);
        $params = ['cpf' => $cpf];

        if ($exceptId) $params['id'] = $exceptId;
        
        $s->execute($params);
        return (bool) $s->fetchColumn();
    }

    public function activate(string $id): void
    {
        $s = Database::connection()->prepare("UPDATE users SET status='active',email_verified_at=now(),updated_at=now() WHERE id=:id");
        $s->execute(['id' => $id]);
    }

    public function updatePassword(string $id, string $password): void
    {
        $s = Database::connection()->prepare('UPDATE users SET password_hash=:hash,auth_version=auth_version+1,updated_at=now() WHERE id=:id');
        $s->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
        (new RememberTokenRepository())->revokeAll($id);
    }

    public function passwordHashById(string $id, bool $lock = false): ?string
    {
        $s = Database::connection()->prepare('SELECT password_hash FROM users WHERE id=:id' . ($lock ? ' FOR UPDATE' : ''));
        $s->execute(['id' => $id]);
        $hash = $s->fetchColumn();
        return is_string($hash) ? $hash : null;
    }

    public function updateProfile(string $id, array $data): void
    {
        $s = Database::connection()->prepare('UPDATE users SET name=:name,phone=:phone,birth_date=:birth_date,
            cpf=:cpf,email=:email,avatar_path=:avatar_path,
            email_verified_at=CASE WHEN email<>:new_email THEN NULL ELSE email_verified_at END,
            status=CASE WHEN email<>:changed_email THEN \'pending_verification\' ELSE status END,
            auth_version=auth_version+:revoke,updated_at=clock_timestamp() WHERE id=:id');
        $s->execute([
            'name' => $data['name'], 'phone' => $data['phone'] ?: null,
            'birth_date' => $data['birth_date'] ?: null, 'cpf' => $data['cpf'] ?: null,
            'email' => $data['email'], 'new_email' => $data['email'], 'changed_email' => $data['email'],
            'avatar_path' => $data['avatar_path'], 'revoke' => $data['email_changed'] ? 1 : 0, 'id' => $id,
        ]);
    }

    public function acceptTerms(string $id): void
    {
        $s = Database::connection()->prepare('UPDATE users SET terms_accepted_at=CASE
            WHEN terms_version IS DISTINCT FROM :version OR terms_accepted_at IS NULL THEN now()
            ELSE terms_accepted_at END,terms_version=:current_version WHERE id=:id');
        $s->execute(['id' => $id, 'version' => \App\Services\TermsService::VERSION, 'current_version' => \App\Services\TermsService::VERSION]);
    }

    public function allStaff(): array
    {
        return Database::connection()->query("SELECT id,name,avatar_path FROM users WHERE role='staff' AND status='active' ORDER BY name,id")->fetchAll();
    }

    public function findActiveStaff(string $id): ?array
    {
        $s = Database::connection()->prepare("SELECT id,name,avatar_path FROM users WHERE id=:id AND role='staff' AND status='active'");
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }
}
