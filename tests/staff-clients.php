<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\{Database, Env};
use App\Repositories\{AppointmentRepository, ManagementRepository, StaffClientRepository};
use App\Services\StaffClientService;

Env::load(dirname(__DIR__) . '/.env');
if (Env::get('APP_ENV') !== 'testing' || Env::get('BOOKING_QA_ALLOW_RESET') !== '1'
    || Env::get('DB_DATABASE') !== 'booking_qa' || Env::get('DB_HOST') !== 'beleza-booking-qa-db') {
    fwrite(STDERR, "Use somente o banco descartável booking_qa.\n"); exit(1);
}
$checks = 0;
function checkClient(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}

try {
    $pdo = Database::connection();
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $staff = (string) $pdo->query("SELECT id FROM users WHERE role='staff' LIMIT 1")->fetchColumn();
    $password = 'Clients-only1!';
    $pdo->prepare("UPDATE users SET email='staff-clients@example.invalid',password_hash=:hash,email_verified_at=now() WHERE id=:id")
        ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $staff]);
    $name = 'AAA Maria <script>alert(1)</script> ' . str_repeat('Sobrenome ', 7);
    $email = str_repeat('a', 55) . '@' . str_repeat('b', 50) . '.example.invalid';
    $insert = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status,phone,cpf,birth_date) VALUES (:name,:email,'unused','client',:status,:phone,:cpf,'1992-04-15') RETURNING id");
    $ids = [];
    for ($i = 0; $i < 12; $i++) {
        $insert->execute(['name' => $i === 0 ? $name : ($i === 10 ? 'Percentual %_' : 'Cliente QA ' . $i),
            'email' => $i === 0 ? $email : 'client-' . $i . '@example.invalid', 'status' => $i === 0 ? 'blocked' : 'active',
            'phone' => $i === 0 ? '(11) 98765-4321' : null, 'cpf' => $i === 0 ? '98765432109' : null]);
        $ids[] = (string) $insert->fetchColumn();
    }
    $petInsert = $pdo->prepare("INSERT INTO pets(owner_id,name,species,breed,sex,weight,allergies,neutered,behavior,special_needs,notes,coat_color) VALUES (:owner,:name,'Cachorro','Raça de nome longo','Fêmea',12.5,:allergies,true,'Dócil','Atenção ao secador','Observações de cuidado','Caramelo') RETURNING id");
    $petInsert->execute(['owner' => $ids[0], 'name' => 'Luna ' . str_repeat('nome ', 10), 'allergies' => '<img src=x onerror=alert(1)>']);
    $petId = (string) $petInsert->fetchColumn();
    $petInsert->execute(['owner' => $ids[0], 'name' => 'Mel', 'allergies' => '']);

    $repo = new StaffClientRepository();
    $service = new StaffClientService();
    $list = $service->listing('', 1);
    checkClient($list['total'] === 13 && count($list['clients']) === 10, 'Listagem completa ou paginação incorreta.');
    checkClient(count(array_unique(array_column($list['clients'], 'id'))) === 10, 'Clientes duplicados por pet.');
    checkClient($service->listing('', 999)['page'] === 2, 'Página excedente não normalizada.');
    checkClient($service->listing([], [])['page'] === 1, 'Parâmetros não escalares falharam.');
    checkClient($service->listing('987654321', 1)['total'] === 1, 'Busca por telefone sem formatação falhou.');
    checkClient($service->listing('AAA maria', 1)['total'] === 1, 'Busca por nome falhou.');
    checkClient($service->listing($email, 1)['total'] === 1, 'Busca por e-mail falhou.');
    checkClient($service->listing('%_', 1)['total'] === 1, 'Busca interpretou curingas como SQL.');
    checkClient($service->listing("' OR 1=1 --", 1)['total'] === 0, 'Busca não tratou entrada SQL como texto.');
    $profile = $service->profile($ids[0]);
    checkClient(count($profile['pets']) === 2, 'Perfil não contém todos os pets.');
    foreach ([$profile['client'], ...$list['clients']] as $record) {
        checkClient(!array_intersect(['cpf', 'password_hash', 'auth_version', 'terms_version'], array_keys($record)), 'Consulta retornou dados restritos.');
    }
    checkClient($service->profile($staff) === null && $service->profile('invalid') === null, 'Aceitou funcionário ou UUID inválido como cliente.');
    checkClient($service->pet($ids[1], $petId) === null, 'Aceitou pet de outro tutor.');

    $cookie = '';
    $request = static function (string $path, ?array $post = null, array $headers = []) use (&$cookie): array {
        $handle = curl_init('http://localhost' . $path);
        $responseHeaders = [];
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => array_merge(['Cookie: ' . $cookie], $headers),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$cookie, &$responseHeaders): int {
                if (preg_match('/^Set-Cookie: (beleza_session=[^;]*)/i', $line, $m)) $cookie = $m[1];
                if (str_contains($line, ':')) { [$key, $value] = explode(':', $line, 2); $responseHeaders[strtolower(trim($key))] = trim($value); }
                return strlen($line);
            }]);
        if ($post !== null) curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
        $body = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        checkClient(is_string($body) && $code < 500, 'Falha HTTP em rota de clientes.');
        return ['body' => $body, 'code' => $code, 'headers' => $responseHeaders];
    };
    checkClient($request('/staff/clientes')['code'] === 302, 'Listagem acessível sem sessão.');
    checkClient($request('/staff/clientes/' . $ids[0] . '/foto', null, ['If-None-Match: *'])['code'] === 302, 'Foto acessível sem sessão.');
    preg_match('/name="_token" value="([^"]+)"/', $request('/login')['body'], $csrf);
    checkClient($request('/login', ['_token' => $csrf[1], 'email' => 'staff-clients@example.invalid', 'password' => $password])['code'] === 302, 'Login QA falhou.');
    $base = '/staff/clientes/' . $ids[0];
    foreach (['/staff/clientes', $base, $base . '/pets/' . $petId] as $path) {
        $response = $request($path);
        checkClient($response['code'] === 200, 'Página autorizada não abriu.');
        checkClient(!str_contains($response['body'], '98765432109'), 'CPF vazou na resposta.');
        checkClient(!str_contains($response['body'], '<script>alert(1)</script>'), 'Nome não escapado.');
        checkClient(str_contains($response['body'], '/assets/css/staff/clients.css?v='), 'CSS específico ausente.');
    }
    $petPage = $request($base . '/pets/' . $petId)['body'];
    checkClient(str_contains($petPage, '&lt;img src=x onerror=alert(1)&gt;'), 'Alergias não escapadas.');
    checkClient(str_contains($request('/staff/clientes?q=naoexiste')['body'], 'Nenhum cliente encontrado'), 'Estado vazio da busca ausente.');
    checkClient(str_contains($request('/staff/clientes/' . $ids[1])['body'], 'Nenhum pet cadastrado'), 'Estado vazio de pets ausente.');
    checkClient($request('/staff/clientes?q[]=x&page[]=2')['code'] === 200, 'Entrada em array causa erro.');
    foreach (['invalid', $staff, '00000000-0000-0000-0000-000000000000'] as $badId) checkClient($request('/staff/clientes/' . $badId)['code'] === 404, 'Cliente inválido não retorna 404.');
    checkClient($request('/staff/clientes/' . $ids[1] . '/pets/' . $petId)['code'] === 404, 'Perfil de pet ignora tutor.');
    checkClient($request('/staff/clientes/' . $ids[1] . '/pets/' . $petId . '/foto', null, ['If-None-Match: *'])['code'] === 404, '304 liberado antes da autorização.');
    $path = str_repeat('a', 64) . '.png';
    $pdo->prepare('UPDATE users SET avatar_path=:path WHERE id=:id')->execute(['path' => $path, 'id' => $ids[0]]);
    $photo = $request($base . '/foto', null, ['If-None-Match: *']);
    checkClient($photo['code'] === 304 && ($photo['headers']['cache-control'] ?? '') === 'private, no-cache, must-revalidate', 'Cache privado de avatar falhou.');
    $pdo->prepare('UPDATE pets SET photo_path=:path WHERE id=:id')->execute(['path' => $path, 'id' => $petId]);
    checkClient($request($base . '/pets/' . $petId . '/foto', null, ['If-None-Match: *'])['code'] === 304, 'Foto autorizada do pet falhou.');
    // Retira paths sintéticos antes do teste visual; nenhuma chamada remota ao Storage é necessária.
    $pdo->prepare('UPDATE users SET avatar_path=NULL WHERE id=:id')->execute(['id' => $ids[0]]);
    $pdo->prepare('UPDATE pets SET photo_path=NULL WHERE id=:id')->execute(['id' => $petId]);
    foreach (['client', 'admin'] as $role) {
        $pdo->prepare('UPDATE users SET role=:role WHERE id=:id')->execute(['role' => $role, 'id' => $staff]);
        checkClient($request('/staff/clientes')['code'] === 403, 'Papel indevido acessou diretório.');
        checkClient($request($base . '/foto', null, ['If-None-Match: *'])['code'] === 403, 'Papel indevido acessou foto.');
    }
    $serviceId = (string) $pdo->query('SELECT id FROM services WHERE active=true ORDER BY id LIMIT 1')->fetchColumn();
    $pendingInsert = $pdo->prepare("INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes)
        VALUES (:pet,:service,:staff,clock_timestamp() + interval '1 day','pending',45,45) RETURNING id");
    $pendingInsert->execute(['pet' => $petId, 'service' => $serviceId, 'staff' => $staff]);
    $pendingAppointment = (string) $pendingInsert->fetchColumn();
    $appointmentRepository = new AppointmentRepository();
    $staffAppointments = $appointmentRepository->staffPage($staff, 'Luna', '', 1);
    checkClient($staffAppointments['total'] === 1 && $staffAppointments['rows'][0]['id'] === $pendingAppointment, 'Busca e paginação do histórico de atendimentos falharam.');
    $emptyStaffAppointments = $appointmentRepository->staffPage($staff, 'inexistente', '', 999);
    checkClient($emptyStaffAppointments['page'] === 1 && $emptyStaffAppointments['total'] === 0, 'Página excedente ou lista vazia do histórico não foram normalizadas.');
    $dateQuery = $pdo->prepare("SELECT (scheduled_at AT TIME ZONE 'America/Sao_Paulo')::date::text FROM appointments WHERE id=:id");
    $dateQuery->execute(['id' => $pendingAppointment]);
    $scheduledDate = (string) $dateQuery->fetchColumn();
    checkClient($appointmentRepository->staffPage($staff, '', $scheduledDate, 1)['total'] === 1, 'Filtro de data do histórico de atendimentos falhou.');
    $staffAppointmentPage = $request('/staff/atendimentos?q=Luna&page=999');
    checkClient($staffAppointmentPage['code'] === 200
        && str_contains($staffAppointmentPage['body'], 'Mostrando 1 de 1 atendimento')
        && str_contains($staffAppointmentPage['body'], 'Página 1'), 'Rota e paginação visual do histórico de atendimentos falharam.');
    $management = new ManagementRepository();
    checkClient($management->demoteAdminStaff($staff) === 'active_appointments', 'Rebaixamento ignorou atendimento pendente.');
    $pdo->prepare("UPDATE appointments SET status='in_progress' WHERE id=:id")->execute(['id' => $pendingAppointment]);
    checkClient($management->demoteAdminStaff($staff) === 'active_appointments', 'Rebaixamento ignorou atendimento em andamento.');
    $pdo->prepare("UPDATE appointments SET status='cancelled' WHERE id=:id")->execute(['id' => $pendingAppointment]);

    $historyInsert = $pdo->prepare("INSERT INTO appointments(pet_id,service_id,staff_id,scheduled_at,status,price,duration_minutes)
        VALUES (:pet,:service,:staff,clock_timestamp() - interval '2 days','completed',45,45) RETURNING id");
    $historyInsert->execute(['pet' => $petId, 'service' => $serviceId, 'staff' => $staff]);
    $historicalAppointment = (string) $historyInsert->fetchColumn();
    $beforeDemotion = $pdo->prepare('SELECT auth_version FROM users WHERE id=:id');
    $beforeDemotion->execute(['id' => $staff]);
    $authVersion = (int) $beforeDemotion->fetchColumn();
    $pdo->prepare("UPDATE users SET status='blocked' WHERE id=:id")->execute(['id' => $staff]);

    checkClient($management->demoteAdminStaff($staff) === 'demoted', 'Rebaixamento de funcionário sem atendimentos abertos falhou.');
    $demoted = $pdo->prepare('SELECT role,status,auth_version FROM users WHERE id=:id');
    $demoted->execute(['id' => $staff]);
    $account = $demoted->fetch();
    checkClient($account['role'] === 'client' && $account['status'] === 'blocked', 'Papel alterado ou status da conta não preservado.');
    checkClient((int) $account['auth_version'] === $authVersion + 1, 'Sessões não foram invalidadas após rebaixamento.');
    $history = $pdo->prepare('SELECT 1 FROM appointments WHERE id=:id AND staff_id=:staff AND status=\'completed\'');
    $history->execute(['id' => $historicalAppointment, 'staff' => $staff]);
    checkClient((bool) $history->fetchColumn(), 'Histórico do funcionário foi removido ou desvinculado.');
    echo "OK: {$checks} verificações de diretório, privacidade, autorização, filtros, perfis e rebaixamento seguro.\n";
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
