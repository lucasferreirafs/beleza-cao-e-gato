<?php
declare(strict_types=1);

namespace App\Support;

final class Uuid
{
    public const NIL = '00000000-0000-0000-0000-000000000000';

    public static function parse(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $value)) {
            return null;
        }
        return strtolower($value);
    }

    // Para parâmetros de rota inválidos: consulta sem correspondência, sem cast numérico nem SQL inválido.
    public static function route(mixed $value): string
    {
        return self::parse($value) ?? self::NIL;
    }
}
