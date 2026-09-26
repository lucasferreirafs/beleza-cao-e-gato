<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class TermsService
{
    // Deve acompanhar a versão exibida em /termos.
    public const VERSION = '1.0';

    public static function validate(mixed $accepted, mixed $version): void
    {
        if (!in_array($accepted, ['1', 1, true], true)) {
            throw new InvalidArgumentException('Aceite os termos de uso para continuar.');
        }
        if ($version !== self::VERSION) {
            throw new InvalidArgumentException('Os termos foram atualizados. Recarregue a página e leia a versão atual.');
        }
    }
}
