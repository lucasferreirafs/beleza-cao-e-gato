<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\AppointmentStorage;
use App\Repositories\DashboardRepository;

Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Use somente o ambiente descartável de QA.\n");
    exit(1);
}
date_default_timezone_set('America/Sao_Paulo');
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
$pdo = Database::connection();
try {
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $staff = (int) $pdo->query("SELECT id FROM users WHERE role='staff' LIMIT 1")->fetchColumn();
    $pet = (int) $pdo->query('SELECT id FROM pets LIMIT 1')->fetchColumn();
    $pdo->prepare("UPDATE pets SET weight=7.2,coat_color='Caramelo',allergies='Frango & leite',special_needs='Evitar piso molhado',behavior='Ansioso durante a secagem',notes='<script>alert(1)</script>' WHERE id=:id")->execute(['id' => $pet]);
    $services = $pdo->query('SELECT id,name,price,duration_minutes FROM services ORDER BY id LIMIT 2')->fetchAll();
    $pdo->prepare("UPDATE users SET email='staff-dashboard@example.invalid',password_hash=:hash,email_verified_at=now() WHERE id=:id")
        ->execute(['hash' => password_hash('Dashboard-qa-only1!', PASSWORD_DEFAULT), 'id' => $staff]);
    $other = (int) $pdo->query("INSERT INTO users(name,email,password_hash,role) VALUES ('Outro profissional','other-dashboard@example.invalid','unused','staff') RETURNING id")->fetchColumn();
    $repository = new DashboardRepository();
    $empty = $repository->staff($staff);
    check($empty['nextAppointment'] === null && $empty['counts']['total'] === 0, 'Estado vazio incorreto.');
    $now = $empty['now'];
    $today = $now->setTime(0, 0);
    $monday = $today->modify('-' . ((int) $now->format('N') - 1) . ' days');
    $fixtures = [];
    $insert = function (DateTimeImmutable $at, string $status, ?int $assigned = null) use ($pdo, $staff, $pet, $services, &$fixtures): int {
        $s = $pdo->prepare('INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes) VALUES (:pet,:service,:staff,:at,:status,50,60) RETURNING id');
        $s->execute(['pet' => $pet, 'service' => $services[0]['id'], 'staff' => $assigned ?? $staff, 'at' => $at->format('c'), 'status' => $status]);
        $id = (int) $s->fetchColumn();
        $fixtures[] = ['at' => $at, 'status' => $status, 'staff' => $assigned ?? $staff];
        return $id;
    };
    $hero = $insert($now->modify('-5 minutes'), 'pending');
    (new AppointmentStorage($pdo))->writeItems($hero, $services);
    $insert($now->modify('+10 minutes'), 'pending');
    $insert($today->setTime(12, 0), 'in_progress');
    $insert($today->setTime(12, 0), 'cancelled');
    $insert($monday, 'completed');
    $insert($monday->modify('+7 days -1 second'), 'completed');
    $insert($monday->modify('-1 second'), 'completed');
    $insert($monday->modify('+7 days'), 'completed');
    $insert($now->modify('-10 minutes'), 'pending', $other);
    foreach (['today', 'week'] as $period) {
        $result = $repository->staff($staff, $period);
        $start = $period === 'today' ? $today : $monday;
        $end = $start->modify($period === 'today' ? '+1 day' : '+7 days');
        $expected = ['total' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0];
        foreach ($fixtures as $fixture) {
            if ($fixture['staff'] !== $staff || $fixture['at'] < $start || $fixture['at'] >= $end) continue;
            $expected['total']++;
            if (isset($expected[$fixture['status']])) $expected[$fixture['status']]++;
        }
        check($result['counts'] === $expected, 'Contadores/filtro incorretos: ' . $period);
        check((int) $result['nextAppointment']['id'] === $hero, 'Destaque ignorou atraso tolerado ou isolamento do funcionário.');
        check(count($result['nextAppointment']['services']) === 2, 'Destaque perdeu serviços.');
        check($result['nextAppointment']['duration_minutes'] === array_sum(array_column($services, 'duration_minutes')), 'Duração não foi somada.');
        check($result['periodStart'] == $start && $result['periodEnd'] == $end, 'Limites de período incorretos.');
    }
    check($repository->staff($staff, 'invalid')['period'] === 'today', 'Fallback inválido.');

    // Exercise real routing, authentication and rendering, including malicious array input.
    $cookie = '';
    $lastStatus = 0;
    $request = function (string $path, ?array $post = null, bool $ajax = false) use (&$cookie, &$lastStatus): string {
        $headers = 'Content-Type: application/x-www-form-urlencoded' . "\r\n" . ($cookie ? 'Cookie: ' . $cookie : '')
            . ($ajax ? "\r\nX-Requested-With: XMLHttpRequest\r\nAccept: application/json" : '');
        $body = file_get_contents('http://localhost' . $path, false, stream_context_create(['http' => [
            'method' => $post === null ? 'GET' : 'POST', 'header' => $headers,
            'content' => $post === null ? '' : http_build_query($post), 'ignore_errors' => true, 'follow_location' => 0,
        ]]));
        check($body !== false && !preg_match('/HTTP\/\S+ 5\d\d/', $http_response_header[0]), 'Erro HTTP: ' . $path);
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\//', $header)) $lastStatus = (int) substr($header, 9, 3);
            if (preg_match('/^Set-Cookie: (beleza_session=[^;]*)/', $header, $match)) $cookie = $match[1];
        }
        return (string) $body;
    };
    $login = $request('/login');
    preg_match('/name="_token" value="([^"]+)"/', $login, $token);
    $request('/login', ['_token' => $token[1], 'email' => 'staff-dashboard@example.invalid', 'password' => 'Dashboard-qa-only1!']);
    $authenticated = $request('/staff');
    preg_match('/name="_token" value="([^"]+)"/', $authenticated, $token);
    foreach (['today', 'week', 'invalid', 'array'] as $period) {
        $html = $request('/staff?' . ($period === 'array' ? 'period[]=week' : 'period=' . $period));
        check(str_contains($html, 'data-staff-countdown'), 'Dashboard autenticado não renderizou.');
        check(str_contains($html, 'value="' . ($period === 'week' ? 'week' : 'today') . '" selected'), 'Select não preservou período.');
        check(str_contains($html, 'Atrasado há'), 'Não mostrou tolerância.');
        check(str_contains($html, 'data-pet-details'), 'Não exibiu acesso ao resumo do pet.');
        check(str_contains($html, 'staff-health-alert'), 'Não sinalizou alergias/cuidados especiais.');
        check(str_contains($html, 'value="in_progress"'), 'Não ofereceu início rápido.');
        check(str_contains($html, '/assets/css/staff/dashboard.css?v='), 'CSS versionado ausente.');
        check(str_contains($html, '/assets/js/staff-appointments.js?v='), 'Script de ações ausente.');
        check(str_contains($html, 'class="staff-agenda-table"') && str_contains($html, 'data-label="Horário"')
            && str_contains($html, 'data-label="Ação"'), 'A tabela não contém os dados necessários para os cartões mobile.');
        check(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Não escapou dados no resumo.');
    }
    $ongoing = $request('/staff?period=week');
    check(str_contains($ongoing, 'staff-in-progress-item'), 'Dashboard não mostra serviços em andamento.');
    $started = json_decode($request('/staff/agendamentos/' . $hero . '/status', ['_token' => $token[1], 'status' => 'in_progress'], true), true, 512, JSON_THROW_ON_ERROR);
    check($lastStatus === 200 && $started['ok'] && $started['status'] === 'in_progress', 'Ação rápida para iniciar falhou.');
    $status = $pdo->prepare('SELECT status FROM appointments WHERE id=:id');
    $status->execute(['id' => $hero]);
    check($status->fetchColumn() === 'in_progress', 'O início não foi persistido.');
    $refreshed = $request('/staff?period=week');
    check(str_contains($refreshed, 'staff-in-progress-item'), 'Atendimento iniciado sumiu ao atualizar o dashboard.');
    check(str_contains($refreshed, 'value="completed"'), 'A conclusão não ficou disponível.');
    $finished = json_decode($request('/staff/agendamentos/' . $hero . '/status', ['_token' => $token[1], 'status' => 'completed'], true), true, 512, JSON_THROW_ON_ERROR);
    check($lastStatus === 200 && $finished['ok'] && $finished['status'] === 'completed', 'Ação rápida para concluir falhou.');
    $status->execute(['id' => $hero]);
    check($status->fetchColumn() === 'completed', 'Conclusão não persistiu.');
    $invalid = $request('/staff/agendamentos/' . $hero . '/status', ['_token' => $token[1], 'status' => 'pending'], true);
    check($lastStatus === 409 && json_decode($invalid, true)['ok'] === false, 'Permitiu reabrir um concluído.');
    echo "OK: {$checks} verificações do dashboard, filtros, limites, serviços e HTTP.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    exit(1);
}
