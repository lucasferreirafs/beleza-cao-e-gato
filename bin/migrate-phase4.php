<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');
try {
    $sql = file_get_contents(dirname(__DIR__) . '/database/migrations/003_multi_service_booking.sql');
    if ($sql === false) throw new RuntimeException('Migração não encontrada.');
    $pdo = Database::connection();
    $pdo->beginTransaction();
    $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
    $pdo->exec($sql);
    $storage = new \App\Repositories\AppointmentStorage($pdo);
    $legacy = $pdo->prepare('SELECT id,notes FROM appointments WHERE notes LIKE :prefix FOR UPDATE');
    $legacy->execute(['prefix' => \App\Repositories\AppointmentStorage::PREFIX . '%']);
    foreach ($legacy->fetchAll() as $row) {
        $payload = \App\Repositories\AppointmentStorage::decode($row['notes']);
        if ($payload === null) continue;
        $pdo->prepare('DELETE FROM appointment_services WHERE appointment_id=:id')->execute(['id' => $row['id']]);
        $storage->writeItems((int) $row['id'], $payload['services']);
        $pdo->prepare('UPDATE appointments SET duration_minutes=:duration WHERE id=:id')->execute([
            'id' => $row['id'], 'duration' => array_sum(array_column($payload['services'], 'duration_minutes')),
        ]);
    }
    $pdo->commit();
    echo "Fase 4 aplicada; reservas existentes foram preservadas.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Não foi possível aplicar a migração da fase 4.\n");
    error_log('Phase 4 migration failed: ' . get_debug_type($e) . ' code=' . $e->getCode());
    exit(1);
}
