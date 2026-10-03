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
    $pdo = Database::connection();
    $database = (string) $pdo->query('SELECT current_database()')->fetchColumn();
    $role = (string) $pdo->query('SELECT current_user')->fetchColumn();
    $expected = (string) Env::get('DB_DATABASE', 'postgres');
    if ($database === '' || $database !== $expected) {
        throw new RuntimeException('O banco conectado difere do configurado.');
    }
    $quotedDatabase = '"' . str_replace('"', '""', $database) . '"';
    $quotedRole = '"' . str_replace('"', '""', $role) . '"';
    $pdo->beginTransaction();
    try {
        $pdo->exec($schema);
        // TIMESTAMPTZ continua armazenando o instante UTC; novas conexões o exibem em São Paulo.
        $pdo->exec("ALTER DATABASE {$quotedDatabase} SET timezone TO 'America/Sao_Paulo'");
        // Poolers podem sobrescrever o padrão do banco; a role do aplicativo também herda o fuso.
        $pdo->exec("ALTER ROLE {$quotedRole} IN DATABASE {$quotedDatabase} SET timezone TO 'America/Sao_Paulo'");
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    echo "Schema aplicado no banco {$database}; fuso padrão: America/Sao_Paulo.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Falha ao aplicar o schema. Revise a conexão e os logs seguros da aplicação.\n");
    error_log('Schema migration failed: ' . get_debug_type($e) . ' code=' . $e->getCode());
    exit(1);
}
