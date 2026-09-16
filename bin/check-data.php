<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load($root . '/.env');

try {
    $pdo = Database::connection();
    $metrics = $pdo->query(<<<'SQL'
        SELECT
            (SELECT count(*) FROM users) AS users,
            (SELECT count(*) FROM users WHERE status = 'active') AS active_users,
            (SELECT count(*) FROM users WHERE password_hash LIKE '$2y$%' OR password_hash LIKE '$argon2%') AS hashed_passwords,
            (SELECT count(*) FROM pets) AS pets,
            (SELECT count(*) FROM services) AS services,
            (SELECT count(*) FROM services WHERE active = true) AS active_services,
            (SELECT count(*) FROM appointments) AS appointments,
            (SELECT count(*) FROM pets p LEFT JOIN users u ON u.id = p.owner_id WHERE u.id IS NULL) AS orphan_pets,
            (SELECT count(*) FROM appointments a LEFT JOIN pets p ON p.id = a.pet_id WHERE p.id IS NULL) AS orphan_appointments
        SQL)->fetch();

    $roles = $pdo->query('SELECT role, count(*) AS total FROM users GROUP BY role ORDER BY role')->fetchAll();

    echo json_encode([
        'read_ok' => true,
        'metrics' => array_map('intval', $metrics),
        'roles' => array_map(static fn(array $row): array => [
            'role' => $row['role'],
            'total' => (int) $row['total'],
        ], $roles),
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode(['read_ok' => false, 'error' => 'Falha ao consultar o schema esperado.']) . PHP_EOL);
    exit(1);
}
