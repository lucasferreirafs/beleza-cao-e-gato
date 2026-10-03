<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{Database, Env};
use App\Repositories\AppointmentRepository;
use App\Services\StaffAgendaService;
Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Use somente o banco descartável booking_qa.\n"); exit(1);
}
date_default_timezone_set('America/Sao_Paulo');
$checks = 0;
function check(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
try {
    $pdo = Database::connection();
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $staff = (int) $pdo->query("SELECT id FROM users WHERE role='staff' LIMIT 1")->fetchColumn();
    $pet = (int) $pdo->query('SELECT id FROM pets LIMIT 1')->fetchColumn();
    $service = (int) $pdo->query('SELECT id FROM services ORDER BY duration_minutes LIMIT 1')->fetchColumn();
    $pdo->prepare("UPDATE users SET email='agenda-qa@example.invalid',password_hash=:hash,email_verified_at=now() WHERE id=:id")
        ->execute(['hash' => password_hash('Agenda-test-only1!', PASSWORD_DEFAULT), 'id' => $staff]);
    $other = (int) $pdo->query("INSERT INTO users(name,email,password_hash,role) VALUES ('Outro','other-agenda@example.invalid','unused','staff') RETURNING id")->fetchColumn();
    $day = (new DateTimeImmutable('+14 days'))->setTime(0, 0);
    $insert = function (int $assigned, string $time, string $status = 'pending', int $duration = 80) use ($pdo, $day, $pet, $service): int {
        $s = $pdo->prepare('INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes,notes) VALUES (:pet,:service,:staff,:at,:status,50,:duration,:notes) RETURNING id');
        $s->execute(['pet' => $pet, 'service' => $service, 'staff' => $assigned, 'at' => $day->format('Y-m-d') . ' ' . $time,
            'status' => $status, 'duration' => $duration, 'notes' => '<script>nota</script>']);
        return (int) $s->fetchColumn();
    };
    $first = $insert($staff, '08:00');
    $cancelled = $insert($staff, '11:00', 'cancelled');
    $foreign = $insert($other, '14:00');
    $agenda = (new StaffAgendaService())->data($staff, $day->format('Y-m-d'), 'day');
    $occupied = array_values(array_filter(array_column($agenda['days'][0]['items'], 'row')));
    check(count($occupied) === 1 && (int) $occupied[0]['id'] === $first, 'Exibiu atendimento de outro funcionário ou cancelado como ocupado.');
    check((int) $agenda['days'][0]['cancelled'][0]['id'] === $cancelled, 'Cancelamento não preservado.');
    $slots = array_merge(...array_column($agenda['days'][0]['items'], 'slots'));
    check(!in_array('09:30', $slots, true) && in_array('10:00', $slots, true), 'Ignorou duração ou intervalo de 30 minutos.');
    check(in_array('11:00', $slots, true), 'Cancelamento não liberou horário.');
    check($agenda['days'][0]['items'][0]['end']->format('H:i') === '09:20', 'Duração prevista incorreta.');
    $week = (new StaffAgendaService())->data($staff, $day->format('Y-m-d'), 'week');
    check(count($week['days']) === 7 && $week['start']->format('N') === '1', 'Semana não começa na segunda-feira.');
    foreach (['2030-02-30', ['invalid'], '1900-99-99'] as $invalid) {
        $selection = StaffAgendaService::selection($invalid, ['week']);
        check($selection['selected'] == $selection['today'] && $selection['view'] === 'day', 'Entrada inválida não normalizada.');
    }
    check((new AppointmentRepository())->staffAgenda($staff, $day->modify('+1 day'), $day->modify('+2 days')) === [], 'Consulta ultrapassou o período.');

    $cookie = ''; $code = 0; $location = '';
    $request = function (string $path, ?array $post = null, bool $ajax = false) use (&$cookie, &$code, &$location): string {
        $body = file_get_contents('http://localhost' . $path, false, stream_context_create(['http' => [
            'method' => $post === null ? 'GET' : 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: {$cookie}" . ($ajax ? "\r\nX-Requested-With: XMLHttpRequest" : ''),
            'content' => $post === null ? '' : http_build_query($post), 'ignore_errors' => true, 'follow_location' => 0]]));
        $location = '';
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+ (\d+)/', $header, $m)) $code = (int) $m[1];
            if (preg_match('/^Set-Cookie: (beleza_session=[^;]*)/', $header, $m)) $cookie = $m[1];
            if (preg_match('/^Location: (.*)$/', $header, $m)) $location = $m[1];
        }
        check($body !== false && $code < 500, 'Falha HTTP em ' . $path);
        return (string) $body;
    };
    preg_match('/name="_token" value="([^"]+)"/', $request('/login'), $csrf);
    $request('/login', ['_token' => $csrf[1], 'email' => 'agenda-qa@example.invalid', 'password' => 'Agenda-test-only1!']);
    $html = $request('/staff/agenda?date=' . $day->format('Y-m-d'));
    check($code === 200 && str_contains($html, 'Linha do Tempo Diária'), 'Agenda não renderizou.');
    check(str_contains($html, '&lt;script&gt;nota&lt;/script&gt;'), 'Observação não escapada.');
    check(!str_contains($html, 'data-appointment="' . $foreign . '"'), 'Vazou agendamento de outro funcionário.');
    preg_match('/name="_token" value="([^"]+)"/', $html, $csrf);
    $base = ['_token' => $csrf[1], 'source' => 'agenda', 'date' => $day->format('Y-m-d'), 'view' => 'week'];
    $request('/staff/agendamentos/' . $first . '/status', array_replace($base, ['_token' => 'invalid', 'status' => 'in_progress']), true);
    check($code === 419, 'CSRF ignorado.');
    $request('/staff/agendamentos/' . $foreign . '/status', $base + ['status' => 'in_progress'], true);
    check($code === 409, 'Alteração de outro funcionário permitida.');
    $result = json_decode($request('/staff/agendamentos/' . $first . '/status', $base + ['status' => 'in_progress'], true), true);
    check($code === 200 && $result['ok'], 'Falha ao iniciar.');
    $request('/staff/agendamentos/' . $first . '/status', $base + ['status' => 'completed']);
    check($location === '/staff/agenda?date=' . $day->format('Y-m-d') . '&view=week', 'Fallback perdeu data ou visualização.');
    $request('/staff/agendamentos/' . $first . '/status', $base + ['status' => 'in_progress'], true);
    check($code === 409, 'Reabriu concluído.');
    $request('/staff/agenda?date[]=invalid&view[]=week');
    check($code === 200, 'Filtro inválido causou erro.');
    echo "OK: {$checks} verificações de agenda, disponibilidade, autorização, CSRF e transições.\n";
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
