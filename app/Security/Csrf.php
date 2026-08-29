<?php
declare(strict_types=1);
namespace App\Security;

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function validate(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }
    
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
