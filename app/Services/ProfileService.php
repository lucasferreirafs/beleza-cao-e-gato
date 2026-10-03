<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AccountTokenRepository;
use App\Repositories\RememberTokenRepository;
use App\Repositories\UserRepository;
use App\Validation\Validator;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;

final class ProfileService
{
    public function update(string $userId, array $input, ?array $file = null): array
    {
        $users = new UserRepository();
        $current = $users->findById($userId);
        if (!$current) throw new InvalidArgumentException('Conta não encontrada.');
        foreach (['name', 'email', 'cpf', 'phone', 'birth_date', 'current_password', 'profile_version'] as $field) {
            if (isset($input[$field]) && !is_string($input[$field])) throw new InvalidArgumentException('Dados do perfil inválidos.');
        }
        if (($input['profile_version'] ?? '') !== $current['updated_at']) {
            throw new InvalidArgumentException('Seu perfil foi alterado em outra aba. Recarregue a página antes de salvar.');
        }
        $data = [
            'name' => Validator::text($input['name'] ?? '', 'Nome completo'),
            'email' => Validator::email($input['email'] ?? ''),
            'cpf' => trim($input['cpf'] ?? '') === '' && empty($current['cpf']) ? '' : Validator::cpf($input['cpf'] ?? ''),
            'phone' => trim($input['phone'] ?? '') === '' ? '' : Validator::phone($input['phone']),
            'birth_date' => trim($input['birth_date'] ?? '') === '' ? '' : Validator::date($input['birth_date']),
            'avatar_path' => $current['avatar_path'],
        ];
        $data['email_changed'] = $data['email'] !== $current['email'];
        $identityChanged = $data['email_changed'] || $data['cpf'] !== ($current['cpf'] ?? '');
        if ($identityChanged) {
            $credentials = $users->findByEmail($current['email']);
            if (!$credentials || !password_verify($input['current_password'] ?? '', $credentials['password_hash'])) {
                throw new InvalidArgumentException('Informe sua senha atual para alterar CPF ou e-mail.');
            }
        }
        if ($users->emailExists($data['email'], $userId)) throw new InvalidArgumentException('Este e-mail já está em uso.');
        if ($users->cpfExists($data['cpf'], $userId)) throw new InvalidArgumentException('Este CPF já está em uso.');

        $storage = null;
        $uploaded = null;
        $pdo = Database::connection();
        $code = null;
        try {
            if ($file !== null && ($file['error'] ?? null) !== UPLOAD_ERR_NO_FILE) {
                $storage = new SupabaseStorageService();
                $uploaded = $storage->upload(SupabaseStorageService::USER_BUCKET, $file);
                $data['avatar_path'] = $uploaded['path'];
            }
            $pdo->beginTransaction();
            $locked = $users->findById($userId, true);
            if (!$locked || $locked['updated_at'] !== $current['updated_at'] || $locked['status'] !== 'active') {
                throw new InvalidArgumentException('Seu perfil mudou durante o envio. Recarregue a página antes de salvar.');
            }
            $users->updateProfile($userId, $data);
            if ($data['email_changed']) {
                (new RememberTokenRepository())->revokeAll($userId);
                $code = (string) random_int(100000, 999999);
                (new AccountTokenRepository())->issue($userId, 'verify_email', $code, 900);
                // Links de recuperação do endereço antigo deixam de ser válidos.
                $pdo->prepare("UPDATE account_tokens SET consumed_at=now() WHERE user_id=:id AND purpose='reset_password' AND consumed_at IS NULL")
                    ->execute(['id' => $userId]);
            }
            $fresh = $users->findById($userId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($uploaded && $storage) $this->cleanup($storage, $uploaded['path']);
            if ($e instanceof PDOException && $e->getCode() === '23505') {
                throw new InvalidArgumentException('O e-mail ou CPF informado já está em uso.');
            }
            if ($e instanceof PDOException) throw new RuntimeException('Não foi possível salvar o perfil. Tente novamente.');
            throw $e;
        }
        if ($uploaded && $storage && $current['avatar_path']) $this->cleanup($storage, $current['avatar_path']);
        $sent = $code !== null ? (new EmailService())->verification($data['email'], $data['name'], $code) : null;
        return ['user' => $fresh, 'email_changed' => $data['email_changed'], 'code' => $code, 'sent' => $sent];
    }

    private function cleanup(SupabaseStorageService $storage, string $path): void
    {
        try { $storage->remove(SupabaseStorageService::USER_BUCKET, $path); }
        catch (Throwable) { error_log('Profile image cleanup pending: ' . $path); }
    }
}
