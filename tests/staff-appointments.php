<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{Database, Env};
use App\Repositories\AppointmentRepository;
Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Use somente o banco descartável booking_qa.\n"); exit(1);
}
$checks = 0;
function verify(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
try {
    $pdo = Database::connection();
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $staff = (string) $pdo->query("SELECT id FROM users WHERE role='staff' LIMIT 1")->fetchColumn();
    $pet = (string) $pdo->query('SELECT id FROM pets LIMIT 1')->fetchColumn();
    $service = (string) $pdo->query("SELECT id FROM services WHERE name ILIKE '%tosa%' LIMIT 1")->fetchColumn();
    $pdo->prepare("UPDATE users SET email='staff-appointments@example.invalid',password_hash=:hash,email_verified_at=now() WHERE id=:id")
        ->execute(['hash' => password_hash('Appointments-only1!', PASSWORD_DEFAULT), 'id' => $staff]);
    $other = (string) $pdo->query("INSERT INTO users(name,email,password_hash,role) VALUES ('Outro','other-appointments@example.invalid','unused','staff') RETURNING id")->fetchColumn();
    $at = (new DateTimeImmutable('+14 days'))->setTime(11, 0);
    $insert = static function (string $assigned, DateTimeImmutable $when, string $status, string $notes) use ($pdo, $pet, $service): string {
        $s = $pdo->prepare('INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes,notes) VALUES (:pet,:service,:staff,:at,:status,80,80,:notes) RETURNING id');
        $s->execute(['pet' => $pet, 'service' => $service, 'staff' => $assigned, 'at' => $when->format('c'), 'status' => $status, 'notes' => $notes]);
        return (string) $s->fetchColumn();
    };
    $current = $insert($staff, $at, 'pending', '<script>observação</script>');
    $prior = $insert($staff, $at->modify('-40 days'), 'completed', 'Tosa anterior');
    $foreign = $insert($other, $at->modify('+1 day'), 'pending', 'Privado');
    $repo = new AppointmentRepository();
    verify($repo->findForStaff($current, $staff)['id'] == $current, 'Consulta do próprio atendimento falhou.');
    verify($repo->findForStaff($foreign, $staff) === null, 'Consulta expôs atendimento de outro funcionário.');
    verify($repo->lastGroomingForPet($pet, $current) !== null, 'Última tosa não encontrada.');

    $cookie = ''; $code = 0; $location = '';
    $request = static function (string $path, ?array $post = null) use (&$cookie, &$code, &$location): string {
        $body = file_get_contents('http://localhost' . $path, false, stream_context_create(['http' => [
            'method' => $post === null ? 'GET' : 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: {$cookie}",
            'content' => $post === null ? '' : http_build_query($post), 'ignore_errors' => true, 'follow_location' => 0]]));
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+ (\d+)/', $header, $m)) $code = (int) $m[1];
            if (preg_match('/^Set-Cookie: (beleza_session=[^;]*)/', $header, $m)) $cookie = $m[1];
            if (preg_match('/^Location: (.*)$/', $header, $m)) $location = $m[1];
        }
        verify($body !== false && $code < 500, 'Falha HTTP em ' . $path);
        return (string) $body;
    };
    preg_match('/name="_token" value="([^"]+)"/', $request('/login'), $csrf);
    $request('/login', ['_token' => $csrf[1], 'email' => 'staff-appointments@example.invalid', 'password' => 'Appointments-only1!']);
    $list = $request('/staff/atendimentos?date=' . $at->format('Y-m-d') . '&q=');
    verify($code === 200 && str_contains($list, 'Ver detalhes do atendimento') && !str_contains($list, 'Novo status'), 'Listagem ou limpeza das ações falhou.');
    verify(!str_contains($list, '/staff/atendimentos/' . $foreign), 'Listagem expôs atendimento de outro funcionário.');
    $empty = $request('/staff/atendimentos?date=' . $at->modify('+3 days')->format('Y-m-d'));
    verify(str_contains($empty, 'Nenhum atendimento encontrado'), 'Filtro por data falhou.');
    $detail = $request('/staff/atendimentos/' . $current);
    verify($code === 200 && str_contains($detail, 'Iniciar Atendimento') && str_contains($detail, '&lt;script&gt;observação&lt;/script&gt;'), 'Detalhes, ação ou escape falhou.');
    verify(str_contains($detail, 'Última tosa concluída'), 'Última tosa ausente.');
    $request('/staff/atendimentos/' . $foreign);
    verify($code === 404, 'Detalhes de outro funcionário acessíveis.');
    $request('/staff/atendimentos/' . $foreign . '/foto');
    verify($code === 404, 'Foto de outro funcionário acessível.');
    preg_match('/name="_token" value="([^"]+)"/', $detail, $csrf);
    $request('/staff/agendamentos/' . $current . '/status', ['_token' => 'invalid', 'source' => 'atendimento', 'status' => 'in_progress']);
    verify($code === 419, 'CSRF ignorado.');
    $request('/staff/agendamentos/' . $current . '/status', ['_token' => $csrf[1], 'source' => 'atendimento', 'status' => 'in_progress']);
    verify($code === 302 && $location === '/staff/atendimentos/' . $current, 'Transição ou retorno aos detalhes falhou.');
    $detail = $request('/staff/atendimentos/' . $current);
    verify(str_contains($detail, 'Concluir Atendimento'), 'Próxima ação incorreta.');
    echo "OK: {$checks} verificações de atendimentos, filtros, detalhes, autorização, CSRF e status.\n";
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
