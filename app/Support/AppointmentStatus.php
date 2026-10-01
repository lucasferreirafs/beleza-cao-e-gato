<?php
declare(strict_types=1);

namespace App\Support;

final class AppointmentStatus
{
    private const LABELS = [
        'pending' => 'Pendente',
        'in_progress' => 'Em andamento',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado'
    ];

    public static function label(?string $status): string
    {
        return self::LABELS[$status ?? ''] ?? 'Status desconhecido';
    }

    /** @return array<string, string> */
    public static function editable(): array
    {
        return self::LABELS;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return isset(self::LABELS[$from], self::LABELS[$to]) && ($from === $to || in_array($to, match ($from) {
            'pending' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed'],
            default => [],
        }, true));
    }

    /** Current state and legal next states; terminal appointments cannot be reopened. */
    public static function optionsFor(string $status): array
    {
        return array_filter(self::LABELS, static fn(string $target): bool => self::canTransition($status, $target), ARRAY_FILTER_USE_KEY);
    }
}
