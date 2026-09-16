<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$root = dirname(__DIR__);
Env::load($root . '/.env');
$schema = file_get_contents($root . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Schema não encontrado.\n");
    exit(1);
}
try {
    Database::connection()->exec($schema);
    echo "Schema aplicado com sucesso.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Falha ao aplicar o schema. Revise a conexão e os logs seguros da aplicação.\n");
    exit(1);
}
