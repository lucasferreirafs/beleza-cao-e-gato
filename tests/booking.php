<?php
declare(strict_types=1);

// Run from the project root: php tests/booking.php
// BOOKING_TEST_URL defaults to APP_URL. All committed fixtures are removed in finally.
$root = is_file(dirname(__DIR__) . '/vendor/autoload.php') ? dirname(__DIR__) : getcwd();
require $root . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppointmentRepository;
use App\Services\BookingService;

Env::load($root . '/.env');
if (!in_array(Env::get('APP_ENV'), ['development', 'test', 'testing'], true)) {
    fwrite(STDERR, "Execute somente em um ambiente de desenvolvimento/teste.\n");
    exit(1);
}
date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
$baseUrl = rtrim((string) Env::get('BOOKING_TEST_URL', Env::get('APP_URL', 'http://localhost:8080')), '/');
$cookies = [];
$checks = 0;

function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

function request(string $method, string $path, array $data = []): array
{
    global $baseUrl, $cookies;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    if ($cookies) {
        $pairs = [];
        foreach ($cookies as $name => $value) $pairs[] = $name . '=' . $value;
        $headers[] = 'Cookie: ' . implode('; ', $pairs);
    }
    $context = stream_context_create(['http' => [
        'method' => $method, 'header' => implode("\r\n", $headers),
        'content' => $method === 'POST' ? http_build_query($data) : '',
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30,
    ]]);
    $body = file_get_contents($baseUrl . $path, false, $context);
    if ($body === false) throw new RuntimeException('Falha HTTP: ' . $path);
    $status = 0;
    $location = null;
    foreach ($http_response_header as $header) {
        if (preg_match('#^HTTP/\S+ (\d+)#', $header, $match)) $status = (int) $match[1];
        if (preg_match('/^Location: (.+)$/i', $header, $match)) $location = trim($match[1]);
        if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $match)) $cookies[$match[1]] = $match[2];
    }
    check($status < 500, "HTTP {$status} em {$method} {$path}");
    return compact('status', 'location', 'body');
}

function csrf(array $response): string
{
    if (!preg_match('/name="_token" value="([^"]+)"/', $response['body'], $match))
        throw new RuntimeException('Token CSRF não encontrado.');
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}

function step(int $number, string $query = ''): array
{
    $page = request('GET', '/cliente/agendamento/' . $number . $query);
    check($page['status'] === 200, 'Passo ' . $number . ' não abriu.');
    check(str_contains($page['body'], 'action="/cliente/agendamento/' . $number . '"'), 'Formulário incorreto.');
    return $page;
}

function submit(int $number, array $page, array $data, string $destination): array
{
    $response = request('POST', '/cliente/agendamento/' . $number, ['_token' => csrf($page)] + $data);
    check($response['status'] === 302 && $response['location'] === $destination,
        'Redirecionamento inesperado ao enviar o passo ' . $number . ': ' . ($response['location'] ?? (string) $response['status']));
    return $response;
}

function rejects(callable $action, string $message): void
{
    try { $action(); } catch (DomainException) { check(true, $message); return; }
    throw new RuntimeException($message);
}

$pdo = Database::connection();
$repository = new AppointmentRepository();
$tag = 'booking-test-' . bin2hex(random_bytes(8));
$userIds = [];
$petId = null;
$serviceId = null;
$password = bin2hex(random_bytes(16)) . 'Aa1!';
$date = (new DateTimeImmutable('+60 days'))->format('Y-m-d');
$scheduledAt = $date . ' 10:00';
$exitCode = 0;

try {
    // Dedicated identities and service prevent tests from modifying existing records.
    foreach (['client', 'staff', 'staff'] as $index => $role) {
        $statement = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status,email_verified_at)
            VALUES (:name,:email,:password,:role,'active',now()) RETURNING id");
        $statement->execute(['name' => $tag . '-' . $index, 'email' => $tag . '-' . $index . '@example.invalid',
            'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
        $userIds[] = (int) $statement->fetchColumn();
    }
    [$ownerId, $staffId, $otherStaffId] = $userIds;
    $statement = $pdo->prepare("INSERT INTO pets(owner_id,name,species,breed,sex) VALUES (:owner,:name,'Cachorro','Teste','Macho') RETURNING id");
    $statement->execute(['owner' => $ownerId, 'name' => $tag]);
    $petId = (int) $statement->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO services(name,description,duration_minutes,price) VALUES (:name,'Serviço temporário de teste',60,71.25) RETURNING id");
    $statement->execute(['name' => $tag]);
    $serviceId = (int) $statement->fetchColumn();

    check($pdo->query('SHOW timezone')->fetchColumn() === Env::get('APP_TIMEZONE', 'America/Sao_Paulo'), 'Fuso do banco diverge da aplicação.');
    check(in_array('10:00', $repository->availableSlots($date, $serviceId), true), 'Consulta sem preferência falhou.');
    check(in_array('10:00', $repository->availableSlots($date, $serviceId, $staffId), true), 'Consulta com profissional falhou.');
    check($repository->availableSlots('2030-02-30', $serviceId) === [], 'Data inexistente foi aceita.');
    check($repository->availableSlots('invalida', $serviceId) === [], 'Data inválida foi aceita.');
    check($repository->availableSlots($date, -1) === [], 'Serviço inexistente foi aceito.');
    foreach ($repository->availableSlots(date('Y-m-d'), $serviceId, $staffId) as $time)
        check(new DateTimeImmutable(date('Y-m-d') . ' ' . $time) > new DateTimeImmutable(), 'Horário passado oferecido.');
    echo "OK: consulta real de horários, profissional e fuso.\n";

    $login = request('GET', '/login');
    $response = request('POST', '/login', ['_token' => csrf($login), 'email' => $tag . '-0@example.invalid', 'password' => $password]);
    check($response['location'] === '/dashboard', 'Login de teste falhou.');
    check(request('GET', '/cliente/agendamento/5')['location'] === '/cliente/agendamento/1', 'Etapas puderam ser puladas.');

    $page = step(1);
    check(str_contains($page['body'], 'value="' . $petId . '"'), 'Pet de teste não apareceu (verifique AUTH_BYPASS).');
    submit(5, $page, [], '/cliente/agendamento/1');
    submit(1, step(1), ['pet_id' => -1], '/cliente/agendamento/1');
    submit(1, step(1), ['pet_id' => $petId], '/cliente/agendamento/2');
    echo "OK: passo 1, seleção de pet e bloqueio de pet inválido.\n";

    submit(2, step(2), ['service_id' => -1], '/cliente/agendamento/2');
    submit(2, step(2), ['service_id' => $serviceId], '/cliente/agendamento/3');
    echo "OK: passo 2, seleção e validação de serviço.\n";

    $invalidDate = step(3, '?date=2030-02-30');
    check(str_contains($invalidDate['body'], 'Escolha uma data válida'), 'Data inválida na URL não foi tratada.');
    $page = step(3, '?date=' . $date);
    check(str_contains($page['body'], 'value="10:00"'), 'Passo 3 não mostrou os horários.');
    submit(3, $page, ['date' => $date, 'time' => '07:00'], '/cliente/agendamento/3');
    submit(3, step(3), ['date' => '2030-02-30', 'time' => '10:00'], '/cliente/agendamento/3');
    submit(3, step(3), ['date' => '2000-01-01', 'time' => '10:00'], '/cliente/agendamento/3');
    submit(3, step(3), ['date' => $date, 'time' => '10:00'], '/cliente/agendamento/4');
    check(request('GET', '/cliente/agendamento/5')['location'] === '/cliente/agendamento/4', 'Escolha de profissional foi pulada.');
    echo "OK: passo 3, data/horário e entradas inválidas, sem erro 500.\n";

    $page = step(4);
    check(str_contains($page['body'], 'value="' . $staffId . '"'), 'Profissional disponível não apareceu.');
    submit(4, $page, ['staff_id' => 'invalido'], '/cliente/agendamento/4');
    submit(4, step(4), ['staff_id' => $staffId, 'notes' => $tag], '/cliente/agendamento/5');
    echo "OK: passo 4, profissional específico e observações.\n";

    $page = step(5);
    check(str_contains($page['body'], '71,25') && str_contains($page['body'], '10:00'), 'Resumo incorreto.');
    $blocked = request('POST', '/cliente/agendamento/5', ['_token' => 'invalido']);
    check($blocked['status'] === 419, 'Confirmação sem CSRF foi aceita.');
    submit(5, $page, ['price' => '0.01'], '/cliente/agendamentos');
    $list = request('GET', '/cliente/agendamentos');
    check($list['status'] === 200 && str_contains($list['body'], $tag), 'Agendamento não apareceu na lista.');

    // A fresh connection proves COMMIT and independently verifies the stored timestamp.
    $verification = new PDO('pgsql:host=' . Env::get('DB_HOST') . ';port=' . Env::get('DB_PORT', '5432')
        . ';dbname=' . Env::get('DB_DATABASE', 'postgres') . ';sslmode=' . Env::get('DB_SSLMODE', 'require'),
        Env::get('DB_USERNAME'), Env::get('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $statement = $verification->prepare('SELECT *,extract(epoch FROM scheduled_at) AS epoch FROM appointments WHERE pet_id=:pet ORDER BY id');
    $statement->execute(['pet' => $petId]);
    $saved = $statement->fetchAll(PDO::FETCH_ASSOC);
    check(count($saved) === 1, 'Confirmação não gravou exatamente um agendamento.');
    $saved = $saved[0];
    check((int) $saved['service_id'] === $serviceId && (int) $saved['staff_id'] === $staffId, 'Relacionamentos incorretos.');
    check($saved['status'] === 'pending' && (float) $saved['price'] === 71.25 && $saved['notes'] === $tag, 'Dados persistidos incorretos.');
    check((int) $saved['epoch'] === (new DateTimeImmutable($scheduledAt))->getTimestamp(), 'Agendamento gravado no fuso incorreto.');
    submit(5, $page, [], '/cliente/agendamento/1');
    $statement->execute(['pet' => $petId]);
    check(count($statement->fetchAll()) === 1, 'Reenvio da confirmação duplicou o agendamento.');
    echo "OK: passo 5, COMMIT confirmado por outra conexão, preço, status e horário corretos; reenvio sem duplicação.\n";

    $data = ['pet_id' => $petId, 'service_id' => $serviceId, 'staff_id' => $staffId, 'scheduled_at' => $scheduledAt, 'notes' => $tag];
    rejects(fn() => $repository->create($ownerId, $data), 'Reserva duplicada foi aceita.');
    rejects(fn() => $repository->create($ownerId, array_replace($data, ['scheduled_at' => $date . ' 10:30'])), 'Sobreposição de duração foi aceita.');
    rejects(fn() => $repository->create($otherStaffId, array_replace($data, ['scheduled_at' => $date . ' 12:00'])), 'Pet de outra conta foi aceito.');
    rejects(fn() => $repository->create($ownerId, array_replace($data, ['scheduled_at' => '2000-01-01 10:00'])), 'Confirmação com data passada foi aceita.');
    check(!in_array('10:30', $repository->availableSlots($date, $serviceId, $staffId), true), 'Horário sobreposto ainda disponível.');
    check(in_array('11:00', $repository->availableSlots($date, $serviceId, $staffId), true), 'Horário adjacente foi bloqueado.');
    echo "OK: conflitos de horário e propriedade do pet protegidos.\n";

    submit(1, step(1), ['pet_id' => $petId], '/cliente/agendamento/2');
    submit(2, step(2), ['service_id' => $serviceId], '/cliente/agendamento/3');
    submit(3, step(3), ['date' => $date, 'time' => '10:30'], '/cliente/agendamento/4');
    $page = step(4);
    check(!str_contains($page['body'], 'name="staff_id" value="' . $staffId . '"'), 'Profissional ocupado apareceu.');
    submit(4, $page, ['staff_id' => $staffId], '/cliente/agendamento/4');
    submit(4, step(4), ['staff_id' => $otherStaffId], '/cliente/agendamento/5');
    // Returning to date/time invalidates the old professional selection.
    submit(3, step(3), ['date' => $date, 'time' => '12:00'], '/cliente/agendamento/4');
    check(request('GET', '/cliente/agendamento/5')['location'] === '/cliente/agendamento/4', 'Troca de horário manteve profissional antigo.');
    submit(4, step(4), ['staff_id' => '', 'notes' => $tag . '-automatic'], '/cliente/agendamento/5');
    submit(5, step(5), [], '/cliente/agendamentos');
    $statement->execute(['pet' => $petId]);
    $saved = $statement->fetchAll(PDO::FETCH_ASSOC);
    check(count($saved) === 2 && $saved[1]['staff_id'] !== null, 'Sem preferência não atribuiu um profissional.');
    check((int) $saved[1]['epoch'] === (new DateTimeImmutable($date . ' 12:00'))->getTimestamp(), 'Horário automático incorreto.');
    echo "OK: segundo fluxo completo sem preferência e alteração de horário.\n";

    // A slot can be taken after the summary was displayed: final confirmation must recheck.
    submit(1, step(1), ['pet_id' => $petId], '/cliente/agendamento/2');
    submit(2, step(2), ['service_id' => $serviceId], '/cliente/agendamento/3');
    submit(3, step(3), ['date' => $date, 'time' => '14:00'], '/cliente/agendamento/4');
    submit(4, step(4), ['staff_id' => $staffId], '/cliente/agendamento/5');
    $repository->create($ownerId, array_replace($data, ['scheduled_at' => $date . ' 14:00']));
    submit(5, step(5), [], '/cliente/agendamento/5');
    check(str_contains(step(5)['body'], 'indisponível'), 'Conflito na confirmação não foi explicado.');
    echo "OK: horário ocupado durante o preenchimento não gera reserva duplicada.\n";

    $_SESSION = ['booking' => ['pet_id' => $petId, 'service_id' => $serviceId, 'date' => $date, 'time' => '10:00', 'staff_id' => $staffId]];
    rejects(fn() => (new BookingService())->storeStep(5, [], $ownerId), 'Conflito não preservou transação.');
    check(isset($_SESSION['booking']) && !$pdo->inTransaction(), 'Estado ou rollback incorreto após falha.');
    echo "SUCESSO: {$checks} verificações.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($cookies) {
        try {
            $page = request('GET', '/cliente/agendamento/1');
            request('POST', '/logout', ['_token' => csrf($page)]);
        } catch (Throwable) { /* Fixture cleanup still runs when HTTP is unavailable. */ }
    }
    $pdo->beginTransaction();
    if ($petId !== null) {
        $pdo->prepare('DELETE FROM appointments WHERE pet_id=:pet')->execute(['pet' => $petId]);
        $pdo->prepare('DELETE FROM pets WHERE id=:pet AND owner_id=:owner')->execute(['pet' => $petId, 'owner' => $userIds[0]]);
    }
    if ($serviceId !== null) $pdo->prepare('DELETE FROM services WHERE id=:id AND name=:name')->execute(['id' => $serviceId, 'name' => $tag]);
    foreach ($userIds as $index => $id)
        $pdo->prepare('DELETE FROM users WHERE id=:id AND email=:email')->execute(['id' => $id, 'email' => $tag . '-' . $index . '@example.invalid']);
    $pdo->commit();
    echo "Dados temporários desta execução removidos.\n";
}
exit($exitCode);
