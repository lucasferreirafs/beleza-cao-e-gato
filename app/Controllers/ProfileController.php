<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Database, Env, Request, Response, Session, View};
use App\Repositories\{RememberTokenRepository, UserRepository};
use App\Services\{ProfileService, RememberMeService, SupabaseStorageService};
use App\Support\Navigation;
use App\Support\PrivateImageResponse;
use InvalidArgumentException;
use RuntimeException;

final class ProfileController
{
    public function show(Request $request): void
    {
        // AuthMiddleware refreshes the full user on every authenticated request.
        // The development bypass skips that refresh, so keep its database lookup.
        $profile = Env::get('APP_ENV') === 'development' && Env::bool('AUTH_BYPASS')
            ? (new UserRepository())->findById(Session::user()['id'])
            : Session::user();
        View::render('pages/client/profile', [
            'title' => 'Meu Perfil', 'active' => 'profile', 'profile' => $profile,
            'user' => Session::user(), 'profileUrl' => Navigation::home(Session::user()['role']) . '/perfil',
        ]);
    }

    public function update(Request $request): void
    {
        try {
            $file = $_FILES['photo'] ?? null;
            if ($file !== null && !is_array($file)) throw new InvalidArgumentException('Upload inválido.');
            $result = (new ProfileService())->update(Session::user()['id'], $request->all(), $file);
            if ($result['email_changed']) {
                (new RememberMeService())->clearCookie();
                Session::logout();
                Session::start();
                Session::put('pending_verification_email', $result['user']['email']);
                if (!$result['sent'] && Env::get('APP_ENV') === 'development' && Env::bool('APP_DEBUG')) {
                    Session::flash('dev_code', $result['code']);
                }
                Session::flash('success', 'Perfil salvo. Confirme o novo e-mail antes de entrar novamente.');
                Response::redirect('/verificar-email');
            }
            Session::refreshUser($result['user']);
            Session::flash('success', 'Perfil atualizado com sucesso.');
        } catch (InvalidArgumentException | RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Navigation::home(Session::user()['role']) . '/perfil');
    }

    public function settings(Request $request): void
    {
        View::render('pages/client/settings', ['title' => 'Configurações', 'active' => 'configuracoes', 'user' => Session::user()]);
    }

    public function revokeRemembered(Request $request): void
    {
        $id = Session::user()['id'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            (new RememberTokenRepository())->revokeAll($id);
            $pdo->prepare('UPDATE users SET auth_version=auth_version+1 WHERE id=:id')->execute(['id' => $id]);
            $fresh = (new UserRepository())->findById($id);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        (new RememberMeService())->clearCookie();
        Session::forget('remember_until');
        Session::refreshUser($fresh);
        Session::flash('success', 'Acessos lembrados e outras sessões revogados. Esta sessão continua ativa.');
        Response::redirect(Navigation::home($fresh['role']) . '/configuracoes');
    }

    public function photo(Request $request): void
    {
        $path = Session::user()['avatar_path'] ?? null;
        if (!$path) { http_response_code(404); return; }
        if (PrivateImageResponse::isNotModified(SupabaseStorageService::USER_BUCKET, $path)) return;
        try {
            $bytes = (new SupabaseStorageService())->download(SupabaseStorageService::USER_BUCKET, $path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Formato inválido.');
            PrivateImageResponse::send($bytes, $mime, SupabaseStorageService::USER_BUCKET, $path);
        } catch (\Throwable) { http_response_code(503); }
    }
}
