<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Validation\Validator;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;

final class PasswordService
{
    /** @return array<string,mixed> Refreshed user row without password hash. */
    public function change(string $userId, array $input): array
    {
        foreach (['current_password', 'new_password', 'password_confirmation'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field]) || $input[$field] === '') {
                throw new InvalidArgumentException('Preencha todos os campos de senha.');
            }
        }

        $currentPassword = $input['current_password'];
        $newPassword = Validator::password($input['new_password']);
        if ($newPassword !== $input['password_confirmation']) {
            throw new InvalidArgumentException('A nova senha e a confirmação não coincidem.');
        }

        $pdo = Database::connection();
        $users = new UserRepository();
        $pdo->beginTransaction();
        try {
            $currentHash = $users->passwordHashById($userId, true);
            if ($currentHash === null || !password_verify($currentPassword, $currentHash)) {
                throw new InvalidArgumentException('A senha atual está incorreta.');
            }
            if (password_verify($newPassword, $currentHash)) {
                throw new InvalidArgumentException('A nova senha deve ser diferente da senha atual.');
            }

            $users->updatePassword($userId, $newPassword);
            $freshUser = $users->findById($userId);
            if ($freshUser === null) throw new RuntimeException('Não foi possível atualizar a senha.');
            $pdo->commit();

            return $freshUser;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($error instanceof PDOException) {
                throw new RuntimeException('Não foi possível atualizar a senha. Tente novamente.');
            }
            throw $error;
        }
    }
}
