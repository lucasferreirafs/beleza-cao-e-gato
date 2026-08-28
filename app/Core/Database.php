<?php 

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database 
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if(self::$connection instanceof PDO) return self::$connection;

        $driver = (string) Env::get('DB_CONNECTION', Env::get('DB_DRIVER', 'pgsql'));

        if ($driver !== 'pgsql') throw new RuntimeException(("Somente PostgreSQL é suportado."));

        $host = (string) Env::get('DB_HOST');
        $port = (string) Env::get('DB_PORT', '5432');
        $name = (string) Env::get('DB_DATABASE', 'postgres');
        $ssl = (string) Env::get('DB_SSLMODE', 'require');
        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$ssl}";

        try {
            self::$connection = new PDO($dsn, (string) Env::get('DB_USERNAME'), (string) Env::get('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ]);

        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getCode());
            throw new RuntimeException('Não foi possível acessar o banco de dados.', 0, $e);
        }
        
        return self::$connection;
    }
}
