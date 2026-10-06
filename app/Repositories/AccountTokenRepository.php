<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class AccountTokenRepository
{
    public function issue(string $userId, string $purpose, string $plainToken, int $ttlSeconds): string
    {
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();

        try {
            $pdo->prepare('SELECT id FROM users WHERE id=:user FOR UPDATE')->execute(['user' => $userId]);
            $pdo->prepare('
                UPDATE account_tokens
                SET consumed_at=now()
                WHERE user_id=:user AND purpose=:purpose AND consumed_at IS NULL
            ')->execute(['user' => $userId, 'purpose' => $purpose]);

            $statement = $pdo->prepare("
                INSERT INTO account_tokens(user_id,purpose,token_hash,expires_at)
                VALUES (:user,:purpose,:hash,now() + (:ttl || ' seconds')::interval)
                RETURNING id
            ");
            $statement->execute([
                'user' => $userId,
                'purpose' => $purpose,
                'hash' => password_hash($plainToken, PASSWORD_DEFAULT),
                'ttl' => $ttlSeconds,
            ]);
            $selector = $statement->fetchColumn();
            if (!is_string($selector) || $selector === '') {
                throw new \RuntimeException('Não foi possível emitir o token da conta.');
            }

            if ($ownsTransaction) $pdo->commit();
            return $selector;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function consume(string $userId, string $purpose, string $plainToken): bool
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('
            SELECT id,token_hash
            FROM account_tokens
            WHERE user_id=:user AND purpose=:purpose AND consumed_at IS NULL AND expires_at>now()
            ORDER BY created_at DESC
            LIMIT 5
        ');
        $statement->execute(['user' => $userId, 'purpose' => $purpose]);

        foreach ($statement->fetchAll() as $row) {
            if (!password_verify($plainToken, $row['token_hash'])) continue;

            $update = $pdo->prepare('
                UPDATE account_tokens
                SET consumed_at=now()
                WHERE id=:id AND consumed_at IS NULL
            ');
            $update->execute(['id' => $row['id']]);
            return $update->rowCount() === 1;
        }
        return false;
    }

    public function passwordResetOwner(string $selector, string $plainToken, bool $consume = false): ?string
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare("SELECT user_id, token_hash FROM account_tokens
            WHERE id=:id AND purpose='reset_password' AND consumed_at IS NULL AND expires_at>now()"
            . ($consume ? ' FOR UPDATE' : ''));
        $statement->execute(['id' => $selector]);
        $row = $statement->fetch();

        if (!is_array($row) || !is_string($row['token_hash'] ?? null)
            || !password_verify($plainToken, $row['token_hash'])) return null;

        if (!$consume) return is_string($row['user_id'] ?? null) ? $row['user_id'] : null;

        $update = $pdo->prepare("UPDATE account_tokens SET consumed_at=now()
            WHERE id=:id AND purpose='reset_password' AND consumed_at IS NULL AND expires_at>now()");
        $update->execute(['id' => $selector]);
        return $update->rowCount() === 1 && is_string($row['user_id'] ?? null) ? $row['user_id'] : null;
    }
}
