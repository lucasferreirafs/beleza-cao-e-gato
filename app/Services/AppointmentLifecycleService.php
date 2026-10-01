<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use Throwable;

final class AppointmentLifecycleService
{
    public const PENDING_TOLERANCE_MINUTES = 30;

    public static function isExpiredPending(array $appointment, DateTimeImmutable $now): bool
    {
        return ($appointment['status'] ?? null) === 'pending'
            && (new DateTimeImmutable($appointment['scheduled_at']))->modify('+' . self::PENDING_TOLERANCE_MINUTES . ' minutes') < $now;
    }

    /** Safe inside an existing booking transaction or as an independent maintenance job. */
    public static function cancelExpiredPending(?PDO $pdo = null): int
    {
        $pdo ??= Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();
        try {
            if ($ownsTransaction) $pdo->exec("SET LOCAL lock_timeout='5s'");
            // Same lock/order as booking creation and manual status transitions.
            $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
            $statement = $pdo->prepare("UPDATE appointments SET status='cancelled', updated_at=clock_timestamp()
                WHERE status='pending' AND scheduled_at < clock_timestamp() - CAST(:tolerance AS interval)");
            $statement->execute(['tolerance' => self::PENDING_TOLERANCE_MINUTES . ' minutes']);
            $count = $statement->rowCount();
            if ($ownsTransaction) $pdo->commit();
            return $count;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
