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
        session_set_cookie_params(['httponly' => true, 'secure' => Env::bool('SESSION_SECURE'), 'samesite' => 'Lax', 'path' => '/']);
        session_start();
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
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
}