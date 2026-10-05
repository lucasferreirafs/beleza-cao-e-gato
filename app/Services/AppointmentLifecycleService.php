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
    private static ?\WeakMap $readMaintenance = null;

    /** Explicit HTTP scope; CLI maintenance keeps running on every call. */
    public static function beginRequest(): void
    {
        self::$readMaintenance = new \WeakMap();
    }

    public static function endRequest(): void
    {
        self::$readMaintenance = null;
    }

    public static function maintainBeforeRead(PDO $pdo): void
    {
        // Booking writes must revalidate under their lock, even after a prior read.
        // Never cache work in a caller-owned transaction: it may roll back.
        if ($pdo->inTransaction() || self::$readMaintenance === null) {
            self::cancelExpiredPending($pdo);
            return;
        }
        if (isset(self::$readMaintenance[$pdo])) return;
        self::cancelExpiredPending($pdo);
        // Only a successful, committed maintenance marks this connection as done.
        self::$readMaintenance[$pdo] = true;
    }

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
