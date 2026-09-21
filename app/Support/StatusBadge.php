<?php
declare(strict_types=1);

namespace App\Support;

final class StatusBadge
{
    public static function class(?string $status): string
    {
        return match ($status) {
            'active', 'completed', 'done' => 'status-success',
            'pending', 'pending_verification' => 'status-pending',
            'confirmed', 'scheduled' => 'status-confirmed',
            'in_progress' => 'status-progress',
            'cancelled', 'canceled', 'blocked' => 'status-danger',
            'inactive' => 'status-muted',
            default => 'status-muted',
        };
    }
}
