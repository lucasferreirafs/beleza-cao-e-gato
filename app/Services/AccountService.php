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
        TermsService::validate($input['terms'] ?? null, $input['terms_version'] ?? null);

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
            $valid = (new AccountTokenRepository())->consume($user['id'], 'verify_email', $code);
            if ($valid)
                (new UserRepository())->activate($user['id']);

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
        (new AccountTokenRepository())->issue($user['id'], 'verify_email', $code, 900);

        return ['sent' => (new EmailService())->verification($user['email'], $user['name'], $code), 'code' => $code];
    }

    /** @return array{sent:bool,debugUrl:?string} */
    public function requestPasswordReset(string $email): array
    {
        $email = Validator::email($email);
        $user = (new UserRepository())->findByEmail($email);

        if (!$user || $user['status'] !== 'active')
            return ['sent' => true, 'debugUrl' => null];

        $baseUrl = rtrim(trim((string) Env::get('APP_URL', '')), '/');
        $urlParts = parse_url($baseUrl);
        $validBaseUrl = is_array($urlParts)
            && in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true)
            && filter_var((string) ($urlParts['host'] ?? ''), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            && !isset($urlParts['user']) && !isset($urlParts['pass'])
            && !isset($urlParts['query']) && !isset($urlParts['fragment'])
            && in_array((string) ($urlParts['path'] ?? ''), ['', '/'], true);
        if (!$validBaseUrl || (Env::get('APP_ENV') === 'production' && strtolower($urlParts['scheme']) !== 'https')) {
            error_log('Password recovery URL configuration is invalid.');
            return ['sent' => false, 'debugUrl' => null];
        }

        $token = bin2hex(random_bytes(32));
        $selector = (new AccountTokenRepository())->issue($user['id'], 'reset_password', $token, 1800);

        $url = $baseUrl . '/redefinir-senha?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token);
        $sent = (new EmailService())->passwordReset($email, $user['name'], $url);

        return ['sent' => $sent, 'debugUrl' => $sent ? null : $url];
    }

    public function isPasswordResetValid(string $selector, string $token): bool
    {
        if (!self::isUuid($selector) || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) return false;
        return (new AccountTokenRepository())->passwordResetOwner($selector, $token) !== null;
    }

    public function resetPassword(array $input): bool
    {
        $passwordInput = $input['password'] ?? null;
        $confirmation = $input['password_confirmation'] ?? null;
        if (!is_string($passwordInput) || !is_string($confirmation)) {
            throw new InvalidArgumentException('Preencha a nova senha e sua confirmação.');
        }

        $password = Validator::password($passwordInput);
        if ($password !== $confirmation)
            throw new InvalidArgumentException('As senhas não coincidem.');

        $selector = $input['selector'] ?? null;
        $token = $input['token'] ?? null;
        if (!is_string($selector) || !self::isUuid($selector)
            || !is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) return false;

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $userId = (new AccountTokenRepository())->passwordResetOwner($selector, $token, true);
            if ($userId !== null) (new UserRepository())->updatePassword($userId, $password);

            $pdo->commit();
            return $userId !== null;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();

            throw $e;
        }
    }

    private static function isUuid(string $value): bool
    {
        return (bool) preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/i', $value);
    }
}
