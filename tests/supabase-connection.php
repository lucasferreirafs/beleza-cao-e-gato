<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');

try {
    $row = Database::connection()->query(
        "SELECT current_database() AS database, current_setting('ssl') AS ssl, to_regclass('public.users') IS NOT NULL AS schema_ready"
    )->fetch();
    echo json_encode([
        'connected' => true,
        'database' => $row['database'],
        'ssl' => $row['ssl'],
        'schema_ready' => (bool) $row['schema_ready'],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode(['connected' => false, 'error' => $exception->getMessage()]) . PHP_EOL);
    exit(1);
}
