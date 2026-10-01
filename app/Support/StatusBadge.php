<?php
declare(strict_types=1);

namespace App\Support;

final class StatusBadge
{
    public static function class(?string $status): string
    {
        return match ($status) {
            'active', 'completed' => 'status-success',
            'pending', 'pending_verification' => 'status-pending',
            'in_progress' => 'status-progress',
            'cancelled', 'blocked' => 'status-danger',
            'inactive' => 'status-muted',
            default => 'status-muted',
        };
    }
}
