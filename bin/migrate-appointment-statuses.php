<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

if (PHP_SAPI !== 'cli') exit(1);
Env::load(dirname(__DIR__) . '/.env');
try {
    $sql = file_get_contents(dirname(__DIR__) . '/database/migrations/005_appointment_status_lifecycle.sql');
    if ($sql === false) throw new RuntimeException('Migração não encontrada.');
    $pdo = Database::connection();
    $pdo->beginTransaction();
    $pdo->exec("SET LOCAL lock_timeout='5s'");
    $pdo->exec($sql);
    $pdo->commit();
    echo "Status atualizados; dados preservados e pendentes vencidos cancelados.\n";
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Migração não aplicada. Verifique o log; nenhuma alteração parcial foi mantida.\n");
    error_log('Appointment status migration failed: ' . get_debug_type($error) . ' code=' . $error->getCode());
    exit(1);
}
