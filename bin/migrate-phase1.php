<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');
try {
    $sql = file_get_contents(dirname(__DIR__) . '/database/migrations/001_auth_profile.sql');
    if ($sql === false) {
        throw new RuntimeException('Migração não encontrada.');
    }
    $pdo = Database::connection();
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    echo "Fase 1 aplicada; dados existentes preservados.\n";
} catch (Throwable) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Não foi possível aplicar a migração da fase 1.\n");
    exit(1);
}
