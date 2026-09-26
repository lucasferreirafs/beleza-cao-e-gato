<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class RememberTokenRepository
{
    public function issue(int $userId, int $expires): string
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id=:id AND expires_at<=now()')->execute(['id' => $userId]);
        $pdo->prepare('INSERT INTO remember_tokens(selector,user_id,token_hash,expires_at)
            VALUES (:selector,:user,:hash,to_timestamp(:expires))')->execute([
                'selector' => $selector, 'user' => $userId, 'hash' => hash('sha256', $validator), 'expires' => $expires,
            ]);
        return $selector . ':' . $validator;
    }

    public function consume(string $cookie): ?array
    {
        if (!preg_match('/\A([a-f0-9]{32}):([a-f0-9]{64})\z/', $cookie, $parts)) return null;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('SELECT *,extract(epoch FROM expires_at)::bigint AS expiry,
                (previous_valid_until>now()) AS previous_valid FROM remember_tokens WHERE selector=:selector FOR UPDATE');
            $s->execute(['selector' => $parts[1]]);
            $token = $s->fetch();
            $user = $token ? (new UserRepository())->findById((int) $token['user_id']) : null;
            $hash = hash('sha256', $parts[2]);
            $current = $token && hash_equals($token['token_hash'], $hash);
            $previous = $token && $token['previous_valid'] && is_string($token['previous_hash']) && hash_equals($token['previous_hash'], $hash);
            if (!$token || (!$current && !$previous) || (int) $token['expiry'] <= time()
                || !$user || $user['status'] !== 'active') {
                $pdo->commit();
                return null;
            }
            $newCookie = null;
            if ($current) {
                $validator = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE remember_tokens SET previous_hash=token_hash,
                    previous_valid_until=now()+interval '60 seconds',token_hash=:hash WHERE selector=:selector")
                    ->execute(['hash' => hash('sha256', $validator), 'selector' => $parts[1]]);
                $newCookie = $parts[1] . ':' . $validator;
            }
            // Requisições simultâneas aceitam o token anterior sem sobrescrever o cookie novo.
            $pdo->commit();
            return ['user' => $user, 'cookie' => $newCookie, 'expires' => (int) $token['expiry']];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function revoke(string $cookie): void
    {
        if (!preg_match('/\A([a-f0-9]{32}):[a-f0-9]{64}\z/', $cookie, $parts)) return;
        Database::connection()->prepare('DELETE FROM remember_tokens WHERE selector=:selector')->execute(['selector' => $parts[1]]);
    }

    public function revokeAll(int $userId): void
    {
        Database::connection()->prepare('DELETE FROM remember_tokens WHERE user_id=:id')->execute(['id' => $userId]);
    }
}
