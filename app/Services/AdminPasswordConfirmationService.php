<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Security\RateLimiter;
use InvalidArgumentException;

/** Reusable password recheck for sensitive administrator mutations. */
final class AdminPasswordConfirmationService
{
    private const ATTEMPT_LIMIT = 5;
    private const WINDOW_SECONDS = 900;

    public function assertValid(string $adminId, mixed $password): void
    {
        if (!is_string($password) || $password === '' || strlen($password) > 1024) {
            throw new InvalidArgumentException('Informe novamente sua senha de administrador.');
        }

        $limiter = new RateLimiter();
        $key = 'admin-password-confirmation|' . $adminId;
        if ($limiter->consume($key, self::ATTEMPT_LIMIT, self::WINDOW_SECONDS) > 0) {
            throw new InvalidArgumentException('Muitas tentativas de confirmação. Aguarde alguns minutos e tente novamente.');
        }

        $statement = Database::connection()->prepare('SELECT password_hash FROM users
            WHERE id=:id AND role=\'admin\' AND status=\'active\'');
        $statement->execute(['id' => $adminId]);
        $hash = $statement->fetchColumn();

        if (!is_string($hash) || !password_verify($password, $hash)) {
            throw new InvalidArgumentException('Senha de administrador incorreta. A ação não foi realizada.');
        }

        $limiter->clear($key);
    }
}
