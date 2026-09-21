<?php
declare(strict_types=1);

namespace App\Support;

final class AppointmentStatus
{
    private const LABELS = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
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
        return array_intersect_key(self::LABELS, array_flip([
            'pending',
            'confirmed',
            'in_progress',
            'completed',
            'cancelled',
        ]));
    }
}
