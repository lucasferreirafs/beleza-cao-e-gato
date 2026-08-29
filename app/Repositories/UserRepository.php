<?php

declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class UserRepository 
{
    public function findByEmail(string $email): ?array 
    {
        $statement = Database::connection()->prepare(
            '
                SELECT id, name, email, password_hash, role, status
                FROM users
                WHERE lower(email) = lower(:email)
                LIMIT 1
            ');
        $statement->execute(['email' => $email]);

        return $statement->fetch() ?: null;
    }
}