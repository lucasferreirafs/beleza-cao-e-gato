<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class AccountTokenRepository
{
    public function issue(int $userId, string $purpose, string $plainToken, int $ttlSeconds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('
            UPDATE account_tokens 
            SET consumed_at=now() 
            WHERE user_id=:user AND purpose=:purpose AND consumed_at IS NULL
        ')->execute(
                [
                    'user' => $userId,
                    'purpose' => $purpose
                ]
            );

        $s = $pdo->prepare("
            INSERT INTO account_tokens(user_id,purpose,token_hash,expires_at) 
            VALUES (:user,:purpose,:hash,now() + (:ttl || ' seconds')::interval)
        ");

        $s->execute(
            [
                'user' => $userId,
                'purpose' => $purpose,
                'hash' => password_hash($plainToken, PASSWORD_DEFAULT),
                'ttl' => $ttlSeconds
            ]
        );
    }

    public function consume(int $userId, string $purpose, string $plainToken): bool
    {
        $pdo = Database::connection();
        $s = $pdo->prepare('
            SELECT id,token_hash 
            FROM account_tokens 
            WHERE user_id=:user AND purpose=:purpose AND consumed_at IS NULL AND expires_at>now() 
            ORDER BY created_at DESC 
            LIMIT 5
        ');
        $s->execute(['user' => $userId, 'purpose' => $purpose]);

        foreach ($s->fetchAll() as $row) {
            if (!password_verify($plainToken, $row['token_hash']))
                continue;

            $u = $pdo->prepare('
                UPDATE account_tokens 
                SET consumed_at=now()
                WHERE id=:id AND consumed_at IS NULL
            ');

            $u->execute(['id' => $row['id']]);
            return $u->rowCount() === 1;
        }
        return false;
    }
}
