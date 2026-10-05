<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppointmentRepository;
use App\Repositories\DashboardRepository;
use App\Services\AppointmentLifecycleService;
use App\Support\AppointmentStatus;

Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Execute somente nos contêineres descartáveis de QA.\n");
    exit(1);
}
date_default_timezone_set('America/Sao_Paulo');
if (($argv[1] ?? '') === '--worker') {
    $result = $argv[2] === 'start'
        ? (new AppointmentRepository())->changeStatus($argv[3], 'in_progress', $argv[4])
        : AppointmentLifecycleService::cancelExpiredPending();
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}

$checks = 0;
function check(bool $result, string $message): void
{
    global $checks;
    if (!$result) throw new RuntimeException($message);
    $checks++;
}

$pdo = Database::connection();
$repo = new AppointmentRepository();
try {
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $pet = $pdo->query('SELECT id,owner_id FROM pets ORDER BY id LIMIT 1')->fetch();
    $staff = (string) $pdo->query("SELECT id FROM users WHERE role='staff' LIMIT 1")->fetchColumn();
    $service = (string) $pdo->query('SELECT id FROM services LIMIT 1')->fetchColumn();
    $insert = function (string $status, string $offset) use ($pdo, $pet, $staff, $service): string {
        $s = $pdo->prepare("INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes)
            VALUES (:pet,:service,:staff,clock_timestamp()+CAST(:offset AS interval),:status,50,60) RETURNING id");
        $s->execute(['pet' => $pet['id'], 'service' => $service, 'staff' => $staff, 'offset' => $offset, 'status' => $status]);
        return (string) $s->fetchColumn();
    };
    $row = function (string $id) use ($pdo): array {
        $s = $pdo->prepare('SELECT * FROM appointments WHERE id=:id');
        $s->execute(['id' => $id]);
        return $s->fetch();
    };
    $migration = file_get_contents(dirname(__DIR__) . '/database/migrations/005_appointment_status_lifecycle.sql');
    $applyMigration = function () use ($pdo, $migration): void {
        $pdo->beginTransaction();
        try { $pdo->exec($migration); $pdo->commit(); }
        catch (Throwable $error) { $pdo->rollBack(); throw $error; }
    };

    $now = new DateTimeImmutable('2026-10-01T10:30:00-03:00');
    $sample = ['status' => 'pending', 'scheduled_at' => '2026-10-01T13:00:00+00:00'];
    check(!AppointmentLifecycleService::isExpiredPending($sample, $now), 'Cancelou no limite exato de 30 minutos.');
    check(AppointmentLifecycleService::isExpiredPending($sample, $now->modify('+1 microsecond')), 'Não cancelou após o limite.');
    foreach (['in_progress', 'completed', 'cancelled'] as $status) {
        check(!AppointmentLifecycleService::isExpiredPending(['status' => $status] + $sample, $now->modify('+1 day')), 'Expirou status não pendente.');
    }
    check(!AppointmentStatus::canTransition('pending', 'completed'), 'Pulou início do serviço.');
    check(!AppointmentStatus::canTransition('completed', 'pending'), 'Reabriu conclusão.');
    check(!AppointmentStatus::canTransition('cancelled', 'in_progress'), 'Reabriu cancelamento.');
    check(!AppointmentStatus::canTransition('pending', 'confirmed'), 'Aceitou status removido.');

    $expired = $insert('pending', '-31 minutes');
    $grace = $insert('pending', '-29 minutes');
    $future = $insert('pending', '+1 day');
    $ongoing = $insert('in_progress', '-2 hours');
    $complete = $insert('completed', '-2 hours');
    check(AppointmentLifecycleService::cancelExpiredPending() === 1, 'Sweep cancelou status/horário incorreto.');
    check($row($expired)['status'] === 'cancelled', 'Cancelamento não persistido.');
    foreach ([$grace => 'pending', $future => 'pending', $ongoing => 'in_progress', $complete => 'completed'] as $id => $status) {
        check($row($id)['status'] === $status, 'Sweep alterou atendimento protegido.');
    }
    check(AppointmentLifecycleService::cancelExpiredPending() === 0, 'Sweep não idempotente.');
    check(!$repo->changeStatus($grace, 'completed', $staff), 'Concluiu sem iniciar.');
    check(!$repo->changeStatus($grace, 'in_progress', App\Support\Uuid::NIL), 'Funcionário alheio alterou atendimento.');
    check(!$repo->cancelForUser($future, App\Support\Uuid::NIL), 'Tutor alheio cancelou atendimento.');
    check($repo->changeStatus($grace, 'in_progress', $staff), 'Não iniciou durante tolerância.');
    check(!$repo->cancelForUser($grace, $pet['owner_id']), 'Tutor cancelou serviço iniciado.');
    check($repo->changeStatus($grace, 'completed', $staff), 'Não concluiu serviço iniciado.');
    $completedAt = $row($grace)['completed_at'];
    check($completedAt !== null, 'Não gravou conclusão.');
    check($repo->changeStatus($grace, 'completed', $staff) && $row($grace)['completed_at'] === $completedAt, 'Envio repetido mudou data da conclusão.');
    check(!$repo->changeStatus($grace, 'pending'), 'Reabriu atendimento concluído.');
    check($repo->cancelForUser($future, $pet['owner_id']), 'Tutor não cancelou reserva futura.');
    check(!$repo->changeStatus($future, 'pending'), 'Reabriu vaga já liberada.');
    $late = $insert('pending', '-31 minutes');
    check(!$repo->changeStatus($late, 'in_progress', $staff) && $row($late)['status'] === 'cancelled', 'Início tardio venceu cancelamento.');
    $lazy = $insert('pending', '-31 minutes');
    $repo->allForUser($pet['owner_id']);
    check($row($lazy)['status'] === 'cancelled', 'Leitura não aplicou expiração.');
    $counts = (new DashboardRepository())->staff($staff)['counts'];
    check($counts['pending'] === 0, 'Métrica contou em andamento como pendente.');

    // HTTP reads share maintenance; writes and new requests must never skip it.
    AppointmentLifecycleService::beginRequest();
    try {
        $firstRead = $insert('pending', '-31 minutes');
        $repo->allForUser($pet['owner_id']);
        check($row($firstRead)['status'] === 'cancelled', 'Primeira leitura nao executou manutencao.');
        $afterRead = $insert('pending', '-31 minutes');
        $repo->allForUser($pet['owner_id']);
        check($row($afterRead)['status'] === 'pending', 'Leitura repetida executou manutencao novamente.');
        check(!$repo->changeStatus($afterRead, 'in_progress', $staff), 'Cache permitiu inicio vencido.');
        check($row($afterRead)['status'] === 'cancelled', 'Acao nao revalidou vencimento.');
        $rolledBack = $insert('pending', '-31 minutes');
        $pdo->beginTransaction();
        AppointmentLifecycleService::maintainBeforeRead($pdo);
        check($row($rolledBack)['status'] === 'cancelled', 'Transacao pulou manutencao devido ao cache.');
        $pdo->rollBack();
        check($row($rolledBack)['status'] === 'pending', 'Rollback nao restaurou fixture.');
        $pdo->beginTransaction();
        AppointmentLifecycleService::maintainBeforeRead($pdo);
        $pdo->commit();
        check($row($rolledBack)['status'] === 'cancelled', 'Transacao seguinte reutilizou trabalho revertido.');
        $nextRequest = $insert('pending', '-31 minutes');
        AppointmentLifecycleService::beginRequest();
        $repo->allForUser($pet['owner_id']);
        check($row($nextRequest)['status'] === 'cancelled', 'Nova requisicao reutilizou cache antigo.');
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
        AppointmentLifecycleService::endRequest();
    }

    // Both competing workers wait on the same lock; expired start must never win.
    $race = $insert('pending', '-31 minutes');
    $pdo->beginTransaction();
    $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
    $workers = [];
    foreach (['start', 'expire'] as $action) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--worker', $action, (string) $race, (string) $staff],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Worker indisponível.');
        fclose($pipes[0]);
        $workers[$action] = [$process, $pipes];
    }
    $pdo->commit();
    foreach ($workers as $action => [$process, $pipes]) {
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($process) === 0 && $err === '', 'Falha no worker: ' . $err);
        if ($action === 'start') check(json_decode($out) === false, 'Worker iniciou atendimento vencido.');
    }
    check($row($race)['status'] === 'cancelled', 'Concorrência deixou estado inválido.');

    // A request begun within grace may expire WHILE waiting for a booking lock.
    $waiting = $insert('pending', '-29 minutes -59 seconds');
    $pdo->beginTransaction();
    $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
    $process = proc_open([PHP_BINARY, __FILE__, '--worker', 'start', (string) $waiting, (string) $staff],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Worker indisponível.');
    fclose($pipes[0]);
    usleep(1500000);
    $pdo->commit();
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    check(proc_close($process) === 0 && $err === '' && json_decode($out) === false, 'Usou relógio anterior à espera por lock.');
    check($row($waiting)['status'] === 'cancelled', 'Expiração durante espera não persistida.');

    $pdo->exec('ALTER TABLE appointments DROP CONSTRAINT appointments_status_check');
    $legacy = $insert('confirmed', '+1 day');
    $legacyPast = $insert('confirmed', '-31 minutes');
    $done = $insert('done', '-1 day');
    $canceled = $insert('canceled', '+1 day');
    $scheduled = $insert('scheduled', '+1 day');
    $applyMigration();
    foreach ([$legacy => 'pending', $legacyPast => 'cancelled', $done => 'completed', $canceled => 'cancelled', $scheduled => 'pending'] as $id => $status) {
        check($row($id)['status'] === $status, 'Migração perdeu significado de legado.');
    }
    $applyMigration();
    check($row($legacy)['status'] === 'pending', 'Migração não repetível.');
    try { $insert('confirmed', '+1 day'); throw new RuntimeException('CHECK aceitou status removido.'); }
    catch (PDOException $error) { check($error->getCode() === '23514', 'Erro incorreto para CHECK.'); }
    $pdo->exec('ALTER TABLE appointments DROP CONSTRAINT appointments_status_check');
    $unknown = $insert('unknown', '+1 day');
    $rollbackLegacy = $insert('confirmed', '+1 day');
    try { $applyMigration(); throw new RuntimeException('Migração aceitou status desconhecido.'); }
    catch (PDOException $error) { check($error->getCode() === '23514' && $row($unknown)['status'] === 'unknown', 'Migração não abortou com segurança.'); }
    check($row($rollbackLegacy)['status'] === 'confirmed', 'Rollback manteve conversão parcial.');
    $pdo->prepare('DELETE FROM appointments WHERE id=:id')->execute(['id' => $unknown]);
    $applyMigration();
    echo "OK: {$checks} verificações de status, limites, autorização, concorrência e migração.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "FAIL: {$error->getMessage()}\n");
    exit(1);
}
