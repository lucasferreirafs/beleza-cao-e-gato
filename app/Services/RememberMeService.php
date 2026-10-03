<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Session;
use App\Repositories\RememberTokenRepository;
use App\Security\Csrf;

final class RememberMeService
{
    public const COOKIE = 'beleza_remember';
    public const TTL = 30 * 86400;

    public function issue(string $userId): void
    {
        $this->forget();
        $expires = time() + self::TTL;
        $cookie = (new RememberTokenRepository())->issue($userId, $expires);
        $this->setCookie($cookie, $expires);
    }

    public function restore(): void
    {
        if (Session::user() || !isset($_COOKIE[self::COOKIE])) return;
        $cookie = $_COOKIE[self::COOKIE];
        $result = is_string($cookie) ? (new RememberTokenRepository())->consume($cookie) : null;
        if (!$result) {
            $this->clearCookie();
            return;
        }
        Session::login($result['user'], true, $result['expires']);
        Csrf::regenerate();
        if ($result['cookie'] !== null) $this->setCookie($result['cookie'], $result['expires']);
    }

    public function forget(): void
    {
        $cookie = $_COOKIE[self::COOKIE] ?? '';
        if (is_string($cookie) && $cookie !== '') (new RememberTokenRepository())->revoke($cookie);
        $this->clearCookie();
    }

    public function clearCookie(): void
    {
        $this->setCookie('', time() - 3600);
        unset($_COOKIE[self::COOKIE]);
    }

    private function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => $expires, 'path' => '/', 'httponly' => true,
            'secure' => Env::bool('SESSION_SECURE') || Env::get('APP_ENV') === 'production', 'samesite' => 'Lax',
        ]);
    }
}
