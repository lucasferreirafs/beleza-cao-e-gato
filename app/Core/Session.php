<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE)
            return;

        $sessionPath = dirname(__DIR__, 2) . '/storage/sessions';
        if (!is_dir($sessionPath))
            mkdir($sessionPath, 0770, true);

        session_save_path($sessionPath);
        session_name('beleza_session');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
        session_set_cookie_params(['httponly' => true, 'secure' => Env::bool('SESSION_SECURE'), 'samesite' => 'Lax', 'path' => '/']);
        session_start();
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();

        if ($remember) {
            $_SESSION['remember_until'] = time() + 60 * 60 * 24 * 30;
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), ['expires' => $_SESSION['remember_until'], 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        }
    }

    public static function refreshUser(array $user): void
    {
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }

    public static function flashOld(array $input): void
    {
        $_SESSION['_flash']['_old'] = $input;
    }

    public static function old(string $key, string $default = ''): string
    {
        $old = $_SESSION['_flash']['_old'] ?? [];
        return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
    }

    public static function consumeOld(): array
    {
        $old = $_SESSION['_flash']['_old'] ?? [];
        unset($_SESSION['_flash']['_old']);
        return $old;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
