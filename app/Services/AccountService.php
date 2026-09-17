<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{
    Database,
    Env
};
use App\Repositories\{
    AccountTokenRepository,
    UserRepository
};
use App\Validation\Validator;
use InvalidArgumentException;

final class AccountService
{
    public function register(array $input): array
    {
        if (empty($input['terms']))
            throw new InvalidArgumentException('Aceite os termos de uso para continuar.');

        $data = [
            'name' => Validator::text($input['name'] ?? '', 'Nome completo'),
            'email' => Validator::email($input['email'] ?? ''),
            'phone' => Validator::phone($input['phone'] ?? ''),
            'cpf' => Validator::cpf($input['cpf'] ?? ''),
            'birth_date' => Validator::date($input['birth_date'] ?? ''),
            'password' => Validator::password($input['password'] ?? ''),
        ];

        if ($data['password'] !== (string) ($input['password_confirmation'] ?? ''))
            throw new InvalidArgumentException('As senhas não coincidem.');

        $users = new UserRepository();
        if ($users->emailExists($data['email']))
            throw new InvalidArgumentException('Já existe uma conta com este e-mail.');

        if ($users->cpfExists($data['cpf']))
            throw new InvalidArgumentException('Já existe uma conta com este CPF.');

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $userId = $users->createClient($data);
            $code = (string) random_int(100000, 999999);

            (new AccountTokenRepository())->issue($userId, 'verify_email', $code, 900);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
        $sent = (new EmailService())->verification($data['email'], $data['name'], $code);
        return ['id' => $userId, 'email' => $data['email'], 'code' => $code, 'sent' => $sent];
    }

    public function verify(string $email, string $code): bool
    {
        $user = (new UserRepository())->findByEmail(Validator::email($email));
        if (!$user || !preg_match('/^\d{6}$/', $code))
            return false;

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $valid = (new AccountTokenRepository())->consume((int) $user['id'], 'verify_email', $code);
            if ($valid)
                (new UserRepository())->activate((int) $user['id']);

            $pdo->commit();
            return $valid;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();

            throw $e;
        }
    }

    public function resendVerification(string $email): array
    {
        $user = (new UserRepository())->findByEmail(Validator::email($email));
        if (!$user || $user['status'] === 'active')
            return ['sent' => true, 'code' => null];

        $code = (string) random_int(100000, 999999);
        (new AccountTokenRepository())->issue((int) $user['id'], 'verify_email', $code, 900);

        return ['sent' => (new EmailService())->verification($user['email'], $user['name'], $code), 'code' => $code];
    }

    public function requestPasswordReset(string $email): ?string
    {
        $email = Validator::email($email);
        $user = (new UserRepository())->findByEmail($email);

        if (!$user || $user['status'] !== 'active')
            return null;

        $token = bin2hex(random_bytes(32));
        (new AccountTokenRepository())->issue((int) $user['id'], 'reset_password', $token, 1800);

        $url = rtrim((string) Env::get('APP_URL', ''), '/') . '/redefinir-senha?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);
        (new EmailService())->passwordReset($email, $user['name'], $url);

        return $url;
    }

    public function resetPassword(array $input): bool
    {
        $email = Validator::email($input['email'] ?? '');
        $password = Validator::password($input['password'] ?? '');
        if ($password !== (string) ($input['password_confirmation'] ?? ''))
            throw new InvalidArgumentException('As senhas não coincidem.');

        $user = (new UserRepository())->findByEmail($email);
        if (!$user)
            return false;

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $valid = (new AccountTokenRepository())->consume((int) $user['id'], 'reset_password', (string) ($input['token'] ?? ''));
            if ($valid)
                (new UserRepository())->updatePassword((int) $user['id'], $password);

            $pdo->commit();
            return $valid;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();

            throw $e;
        }
    }
}
