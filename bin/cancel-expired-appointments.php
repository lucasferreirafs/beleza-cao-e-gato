<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\AppointmentLifecycleService;

if (PHP_SAPI !== 'cli') exit(1);
Env::load(dirname(__DIR__) . '/.env');
try {
    $count = AppointmentLifecycleService::cancelExpiredPending();
    echo "Agendamentos cancelados automaticamente: {$count}\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Não foi possível executar o cancelamento automático.\n");
    error_log('Appointment expiration failed: ' . get_debug_type($error) . ' code=' . $error->getCode());
    exit(1);
}
