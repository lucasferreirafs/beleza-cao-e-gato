<?php
declare(strict_types=1);

// Destructive fixtures are allowed only in the dedicated, disposable local QA database.
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentStorage;
use App\Repositories\DashboardRepository;
use App\Repositories\ManagementRepository;
use App\Services\BookingService;

Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Use somente os contêineres locais de QA descritos em README.md.\n");
    exit(1);
}
date_default_timezone_set('America/Sao_Paulo');

if (($argv[1] ?? '') === '--worker') {
    $data = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    try {
        $id = (new AppointmentRepository())->create($data['owner'], $data['booking']);
        echo json_encode(['ok' => true, 'id' => $id]);
    } catch (DomainException $e) {
        echo json_encode(['ok' => false, 'conflict' => true]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => get_class($e) . ': ' . $e->getMessage()]);
    }
    exit;
}

$pdo = Database::connection();
$baseUrl = rtrim((string) Env::get('BOOKING_TEST_URL', 'http://localhost'), '/');
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
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
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
    if (!preg_match('/name="_token" value="([^"]+)"/', $response['body'], $match)) throw new RuntimeException('CSRF ausente.');
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}
function route(int $step): string { return '/cliente/agendamento/' . BookingService::stepSlug($step); }
function page(int $step, string $query = ''): array
{
    $page = request('GET', route($step) . $query);
    check($page['status'] === 200 && str_contains($page['body'], 'action="' . route($step) . '"'), 'Etapa não abriu: ' . $step);
    return $page;
}
function submit(int $step, array $data, int|string $next, ?array $source = null): array
{
    $response = request('POST', route($step), ['_token' => csrf($source ?? page($step))] + $data);
    $expected = is_int($next) ? route($next) : $next;
    check($response['status'] === 302 && $response['location'] === $expected, 'Destino incorreto no passo ' . $step . ': ' . ($response['location'] ?? $response['status']));
    return $response;
}
function rejects(callable $operation, string $message): void
{
    try { $operation(); } catch (DomainException) { check(true, $message); return; }
    throw new RuntimeException($message);
}
function login(string $email, string $password): void
{
    global $cookies;
    $cookies = [];
    $form = request('GET', '/login');
    $response = request('POST', '/login', ['_token' => csrf($form), 'email' => $email, 'password' => $password]);
    check($response['location'] === '/dashboard', 'Login falhou.');
}
function race(array $requests): array
{
    global $pdo;
    $pdo->beginTransaction();
    $pdo->query('SELECT pg_advisory_xact_lock(4242, 4)');
    $processes = [];
    try {
        foreach ($requests as $data) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __FILE__, '--worker', base64_encode(json_encode($data))],
                [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
            if (!is_resource($process)) throw new RuntimeException('Worker não iniciou.');
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        usleep(300000); // Keep both requests waiting on the same lock before releasing them.
        $pdo->commit();
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            check(proc_close($process) === 0 && $err === '', 'Erro no worker: ' . $err);
            $result = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
            check(!isset($result['error']), $result['error'] ?? 'Worker concluído.');
            $results[] = $result;
        }
        return $results;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

try {
    foreach (['legacy', 'duration-only', 'items-only', 'modern'] as $mode) {
        $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
        $uuidColumns = $pdo->query("SELECT table_name,column_name FROM information_schema.columns
            WHERE table_schema='public' AND udt_name='uuid'")->fetchAll();
        check(count($uuidColumns) === 13, 'Chaves primárias ou estrangeiras não foram convertidas para UUID.');
        check($pdo->query("SELECT current_setting('TimeZone')")->fetchColumn() === 'America/Sao_Paulo', 'Conexão fora do fuso brasileiro.');
        check((bool) $pdo->query('SELECT public.brazil_timestamp() IS NOT NULL')->fetchColumn(), 'Função de timestamp indisponível.');
        if (in_array($mode, ['legacy','duration-only'], true)) $pdo->exec('DROP TABLE appointment_services');
        if (in_array($mode, ['legacy','items-only'], true)) $pdo->exec('ALTER TABLE appointments DROP COLUMN duration_minutes');
        $schema = (new AppointmentStorage($pdo))->capabilities();
        check($schema['items'] === in_array($mode, ['items-only','modern'], true), 'Detecção de tabela incorreta.');
        check($schema['duration'] === in_array($mode, ['duration-only','modern'], true), 'Detecção de coluna incorreta.');
        $pdo->exec("UPDATE users SET status='blocked' WHERE role='staff'");
        $password = 'BookingQa-password1!';
        $people = [];
        foreach (['client','staff','staff','admin','client'] as $i => $role) {
            $s = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status,email_verified_at)
                VALUES (:name,:email,:hash,:role,'active',now()) RETURNING id");
            $s->execute(['name' => 'QA ' . $i, 'email' => 'qa' . $i . '@example.invalid',
                'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
            $people[] = (string) $s->fetchColumn();
        }
        [$owner,$staff1,$staff2,$admin,$otherOwner] = $people;
        $pets = [];
        for ($i = 0; $i < 5; $i++) {
            $s = $pdo->prepare("INSERT INTO pets(owner_id,name,species,breed,sex) VALUES (:owner,:name,'Cachorro','Teste','Macho') RETURNING id");
            $s->execute(['owner' => $owner, 'name' => 'Pet QA ' . $i]);
            $pets[] = (string) $s->fetchColumn();
        }
        $services = [];
        foreach ([[60,'71.25'],[30,'15.00']] as $i => [$duration,$price]) {
            $s = $pdo->prepare("INSERT INTO services(name,description,duration_minutes,price) VALUES (:name,'Serviço QA',:duration,:price) RETURNING id");
            $s->execute(['name' => 'Serviço QA ' . $i,'duration' => $duration,'price' => $price]);
            $services[] = (string) $s->fetchColumn();
        }
        $repo = new AppointmentRepository();
        $date = (new DateTimeImmutable('+60 days'))->format('Y-m-d');
        check($repo->availableSlots('2030-02-30', $services) === [], 'Data inválida foi aceita.');
        check($repo->availableSlots($date, [$services[0],$services[0]]) === [], 'Serviços duplicados foram aceitos.');
        check(!in_array('17:00', $repo->availableSlots($date, $services), true), 'Duração ultrapassa fechamento.');
        foreach (['client' => $owner, 'staff' => $staff1, 'admin' => $admin] as $role => $id) {
            $dash = new DashboardRepository();
            $role === 'admin' ? $dash->admin() : $dash->{$role}($id);
            check(true, 'Dashboard vazio com schema ' . $mode);
        }
        login('qa0@example.invalid', $password);
        check(request('GET', '/dashboard')['location'] === '/cliente', 'Redirecionamento após login incorreto.');
        check(request('GET', '/cliente')['status'] === 200, 'Dashboard falhou.');
        check(request('GET', '/cliente/agendamento/5')['location'] === route(1), 'Pulo de etapas permitido.');
        check(request('GET', '/cliente/agendamento/1')['location'] === route(1), 'Rota numérica não foi normalizada.');
        submit(1, ['service_ids' => []], 1);
        submit(1, ['service_ids' => [$services[0],$services[0]]], 1);
        submit(1, ['service_ids' => $services], 2);
        submit(2, ['pet_id' => -1], 2);
        submit(2, ['pet_id' => $pets[0]], 3);
        check(request('GET', '/cliente/agendamento/profissional')['location'] === route(3), 'Rota antiga não redirecionou para a etapa combinada.');
        $schedulePage = page(3);
        check(str_contains($schedulePage['body'], 'Data, horário e profissional'), 'Etapa combinada não exibe data, horário e profissional.');
        check(!str_contains($schedulePage['body'], 'Tem preferência por algum profissional?'), 'Etapa separada de profissional continua sendo exibida.');
        check(request('GET', '/cliente/agendamento/disponibilidade?date=2030-02-30')['status'] === 422, 'API aceitou data inexistente.');
        $availability = request('GET', '/cliente/agendamento/disponibilidade?date=' . $date);
        check($availability['status'] === 200 && (json_decode($availability['body'], true)['slots'] ?? []) !== [], 'API não consultou disponibilidade sem preferência salva.');
        submit(3, ['date' => '2000-01-01','time' => '10:00','staff_id' => ''], 3);
        submit(3, ['date' => $date,'time' => '10:00','staff_id' => 'inválido'], 3);
        $note = "Cuidado <script>alert(1)</script>\nObservação QA";
        submit(3, ['date' => $date,'time' => '10:00','staff_id' => $staff1,'notes' => $note], 4);
        $confirmation = page(4);
        check(str_contains($confirmation['body'], '86,25') && str_contains($confirmation['body'], '1h 30 min'), 'Total/duração incorretos.');
        check(str_contains($confirmation['body'], 'Serviço QA 0') && str_contains($confirmation['body'], 'Serviço QA 1'), 'Resumo perdeu serviços.');
        check(str_contains($confirmation['body'], '&lt;script&gt;') && !str_contains($confirmation['body'], '<script>alert(1)'), 'Observação não escapada.');
        check(request('POST', route(4), ['_token' => 'invalid'])['status'] === 419, 'CSRF foi ignorado.');
        submit(4, ['price' => '0.01'], '/cliente/agendamentos', $confirmation);
        $saved = $repo->allForUser($owner);
        check(count($saved) === 1 && count($saved[0]['services']) === 2, 'Não gravou uma reserva com dois serviços.');
        check((float) $saved[0]['price'] === 86.25 && $saved[0]['duration_minutes'] === 90 && $saved[0]['notes'] === $note, 'Snapshots ou nota incorretos.');
        submit(4, [], 1, $confirmation);
        check(count($repo->allForUser($owner)) === 1, 'Reenvio duplicou reserva.');
        check(request('GET', '/cliente')['status'] === 200 && request('GET', '/cliente/agendamentos')['status'] === 200, 'Leitura após reserva falhou.');

        $data = ['pet_id' => $pets[1],'service_ids' => $services,'staff_id' => $staff1,'scheduled_at' => $date . ' 10:00','notes' => '0'];
        rejects(fn() => $repo->create($owner, $data), 'Mesmo profissional foi reservado duas vezes.');
        rejects(fn() => $repo->create($owner, array_replace($data, ['pet_id' => $pets[0], 'staff_id' => $staff2])), 'Mesmo pet foi reservado com outra pessoa.');
        rejects(fn() => $repo->create($otherOwner, $data), 'Pet de outra conta foi aceito.');
        check(!in_array('11:30', $repo->availableSlots($date, $services, $staff1), true), 'Intervalo menor que 30 minutos foi aceito.');
        check(in_array('12:00', $repo->availableSlots($date, $services, $staff1), true), 'Limite exato de 30 minutos foi bloqueado.');
        check(in_array('08:00', $repo->availableSlots($date, $services[0], $staff1), true), 'Limite anterior exato bloqueado.');
        check(in_array('08:00', $repo->availableSlots($date, $services, $staff1), true), 'Limite anterior exato de 30 minutos foi bloqueado.');
        check(!in_array('08:30', $repo->availableSlots($date, $services, $staff1), true), 'Intervalo anterior menor que 30 minutos foi aceito.');
        check(!in_array('10:00', $repo->availableSlots($date, $services, null, $pets[0]), true), 'Pet ocupado ainda aparece disponível.');

        // Specific professional remains visible but disabled after choosing a busy time.
        submit(1, ['service_ids' => $services], 2);
        submit(2, ['pet_id' => $pets[1]], 3);
        submit(3, ['date' => $date,'time' => '10:00','staff_id' => $staff1], 3);
        $busy = page(3);
        check((bool) preg_match('/name="staff_id" value="' . $staff1 . '"[^>]*disabled/', $busy['body']), 'Profissional ocupado não ficou desabilitado.');
        check(str_contains($busy['body'], 'Indisponível'), 'Estado ocupado não foi exibido.');
        check(request('GET', route(4))['location'] === route(3), 'Etapa inválida permitiu confirmação.');
        submit(3, ['date' => $date,'time' => '10:00','staff_id' => '','notes' => '0'], 4);
        check(str_contains(page(4)['body'], 'summary-notes">0'), 'Observação zero foi ocultada.');
        submit(4, [], '/cliente/agendamentos');
        $rows = $repo->allForUser($owner);
        $automatic = current(array_filter($rows, fn(array $a): bool => $a['pet_id'] === $pets[1]));
        check($automatic['staff_id'] === $staff2, 'Sem preferência não atribuiu o profissional livre.');
        check(!in_array('10:00', $repo->availableSlots($date, $services), true), 'Horário sem equipe livre foi oferecido.');

        // Changing a service must not change already booked durations/prices.
        $pdo->prepare('UPDATE services SET duration_minutes=120,price=99 WHERE id=:id')->execute(['id' => $services[0]]);
        $snapshot = current(array_filter($repo->allForUser($owner), fn(array $a): bool => $a['pet_id'] === $pets[0]));
        check($snapshot['duration_minutes'] === 90 && (float) $snapshot['price'] === 86.25, 'Alterar catálogo modificou reserva existente.');
        $pdo->prepare('UPDATE services SET duration_minutes=60,price=71.25 WHERE id=:id')->execute(['id' => $services[0]]);

        // Price changed after review must require a new confirmation.
        $quoteDate = (new DateTimeImmutable('+61 days'))->format('Y-m-d');
        submit(1, ['service_ids' => $services], 2);
        submit(2, ['pet_id' => $pets[2]], 3);
        submit(3, ['date' => $quoteDate,'time' => '10:00','staff_id' => $staff1], 4);
        $review = page(4);
        $pdo->prepare('UPDATE services SET price=72.25 WHERE id=:id')->execute(['id' => $services[0]]);
        submit(4, [], 3, $review);
        check(str_contains(page(3)['body'], 'Os serviços foram atualizados'), 'Mudança de preço não foi explicada.');
        $pdo->prepare('UPDATE services SET price=71.25 WHERE id=:id')->execute(['id' => $services[0]]);

        foreach ([['qa1@example.invalid','/staff'],['qa3@example.invalid','/admin']] as [$email,$home]) {
            login($email,$password);
            check(request('GET',$home)['status'] === 200, 'Dashboard de equipe falhou.');
        }
        $management = new ManagementRepository();
        check(count($management->adminRows('agendamentos')) === 2, 'Listagem admin falhou.');
        check(count($management->staffRows('atendimentos',$staff1)) === 1, 'Listagem staff falhou.');
        $management->adminRows('servicos');
        check($repo->cancelForUser($snapshot['id'],$owner), 'Cancelamento falhou.');
        check(!$management->setAdminStatus('agendamentos',$snapshot['id'],'pending'), 'Reabertura de cancelado ocupou um slot liberado.');
        check(in_array('10:00',$repo->availableSlots($date,$services,$staff1),true), 'Cancelamento não liberou a agenda.');

        if (in_array($mode,['legacy','modern'],true)) {
            $raceDate = (new DateTimeImmutable('+65 days'))->format('Y-m-d');
            $base = array_replace($data, ['scheduled_at' => $raceDate . ' 10:00']);
            $requests = array_map(fn(string $pet): array => ['owner'=>$owner,'booking'=>array_replace($base,['pet_id'=>$pet])], [$pets[2],$pets[3]]);
            $result = race($requests);
            check(count(array_filter($result,fn(array $r): bool=>$r['ok'])) === 1, 'Concorrência duplicou o profissional.');
            $autoDate = (new DateTimeImmutable('+66 days'))->format('Y-m-d');
            $base = array_replace($data,['scheduled_at'=>$autoDate . ' 10:00','staff_id'=>null]);
            $requests = array_map(fn(string $pet): array => ['owner'=>$owner,'booking'=>array_replace($base,['pet_id'=>$pet])], [$pets[2],$pets[3],$pets[4]]);
            $result = race($requests);
            check(count(array_filter($result,fn(array $r): bool=>$r['ok'])) === 2, 'Concorrência sem preferência excedeu ou perdeu capacidade.');
            $petDate = (new DateTimeImmutable('+67 days'))->format('Y-m-d');
            $requests = array_map(fn(string $staff): array => ['owner'=>$owner,'booking'=>array_replace($data,['pet_id'=>$pets[4],'staff_id'=>$staff,'scheduled_at'=>$petDate . ' 10:00'])],[$staff1,$staff2]);
            check(count(array_filter(race($requests),fn(array $r): bool=>$r['ok'])) === 1, 'Concorrência reservou o mesmo pet duas vezes.');
        }
        // Upgrade compatibility records and run the migration twice to verify idempotence.
        if ($mode === 'legacy') {
            for ($i=0;$i<2;$i++) {
                $process = proc_open([PHP_BINARY,dirname(__DIR__).'/bin/migrate-phase4.php'],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
                $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
                fclose($pipes[1]);fclose($pipes[2]);
                check(proc_close($process)===0,'Migração falhou: '.$err.$out);
            }
            $s=$pdo->prepare('SELECT count(*) FROM appointment_services WHERE appointment_id=:id');
            $s->execute(['id'=>$snapshot['id']]);
            check((int)$s->fetchColumn()===2,'Migração perdeu serviços da reserva compatível.');
            check($repo->allForUser($owner)[0]['duration_minutes']===90,'Migração alterou a duração.');
        }
        // UUIDs must survive the admin CRUD and the client URLs without numeric casts.
        $newService = $management->save('servicos', null, [
            'name' => 'Serviço UUID', 'description' => 'Serviço de teste para UUID',
            'duration_minutes' => '30', 'price' => '12.50',
        ]);
        check(\App\Support\Uuid::parse($newService) === $newService
            && $management->findEditable('servicos', $newService)['name'] === 'Serviço UUID', 'CRUD de serviço não preservou UUID.');
        $newPet = $management->save('pets', null, [
            'owner_id' => $owner, 'name' => 'Pet UUID', 'species' => 'Gato', 'breed' => 'SRD',
            'sex' => 'Fêmea', 'birth_date' => '2023-01-01',
        ]);
        check(\App\Support\Uuid::parse($newPet) === $newPet
            && $management->findEditable('pets', $newPet)['owner_id'] === $owner, 'CRUD de pet não preservou UUID.');
        login('qa0@example.invalid', $password);
        check(request('GET', '/cliente/pets/' . $newPet)['status'] === 200, 'Rota do pet com UUID não abriu.');
        check(request('GET', '/cliente/pets/1')['status'] === 404, 'Rota de pet inválida não respondeu 404.');
        echo "OK: {$mode} — login, dashboards, etapas, CSRF, snapshots e agenda.\n";
    }
    echo "SUCESSO: {$checks} verificações; concorrência real executada nos schemas antigo e novo.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,get_class($e).': '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
