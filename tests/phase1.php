<?php
declare(strict_types=1);

// Integração real em desenvolvimento: somente contas e imagens criadas pelo teste.
$root = is_file(dirname(__DIR__) . '/vendor/autoload.php') ? dirname(__DIR__) : getcwd();
require $root . '/vendor/autoload.php';

use App\Core\{Database, Env};
use App\Repositories\{AccountTokenRepository, RememberTokenRepository, UserRepository};
use App\Services\{AccountService, RememberMeService, SupabaseStorageService, TermsService};
use App\Support\Navigation;

Env::load($root . '/.env');
if (!in_array(Env::get('APP_ENV'), ['development', 'test', 'testing'], true) || Env::bool('AUTH_BYPASS')) {
    fwrite(STDERR, "Execute em desenvolvimento/teste com AUTH_BYPASS=false.\n"); exit(1);
}
$base = rtrim((string) Env::get('PHASE1_TEST_URL', 'http://127.0.0.1'), '/');
$pdo = Database::connection();
$users = new UserRepository();
$tokens = new RememberTokenRepository();
$storage = new SupabaseStorageService();
$ids = [];
$paths = [];
$png = null;
$cookies = [];
$checks = 0;
$tag = 'phase1-' . bin2hex(random_bytes(7));
$password = bin2hex(random_bytes(12)) . 'Aa1!';

function check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
function request(string $method, string $path, array $data = [], bool $multipart = false): array {
    global $base, $cookies;
    $responseHeaders = [];
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 90,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int { $responseHeaders[] = trim($line); return strlen($line); },
    ]);
    if ($cookies) curl_setopt($handle, CURLOPT_COOKIE, implode('; ', array_map(static fn($key, $value) => $key . '=' . rawurlencode($value), array_keys($cookies), array_values($cookies))));
    if ($method === 'POST') curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $multipart ? $data : http_build_query($data)]);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    check(is_string($body) && $status > 0 && $status < 500, 'Falha HTTP em ' . $method . ' ' . $path . ': ' . $status);
    $location = null;
    foreach ($responseHeaders as $line) {
        if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $line, $match)) {
            if ($match[2] === '' || $match[2] === 'deleted') unset($cookies[$match[1]]);
            else $cookies[$match[1]] = rawurldecode($match[2]);
        }
        if (preg_match('/^Location: (.+)$/i', $line, $match)) $location = $match[1];
    }
    return ['body' => $body, 'status' => $status, 'location' => $location, 'headers' => $responseHeaders];
}
function csrf(array $page): string {
    if (!preg_match('/name="_token" value="([^"]+)"/', $page['body'], $m)) throw new RuntimeException('CSRF ausente.');
    return html_entity_decode($m[1], ENT_QUOTES);
}
function login(string $email, string $password, bool $remember = false): array {
    $page = request('GET', '/login');
    return request('POST', '/login', ['_token' => csrf($page), 'email' => $email, 'password' => $password,
        'remember' => $remember ? '1' : '0']);
}
function freshProfile(int $id, array $extra = []): array {
    global $users;
    $u = $users->findById($id);
    return $extra + ['name' => $u['name'], 'email' => $u['email'], 'cpf' => $u['cpf'] ?? '',
        'phone' => $u['phone'] ?? '', 'birth_date' => $u['birth_date'] ?? '', 'profile_version' => $u['updated_at']];
}
function cpfFixture(): string {
    $digits = [];
    for ($i = 0; $i < 9; $i++) $digits[] = random_int(0, 9);
    for ($i = 9; $i <= 10; $i++) {
        $sum = 0;
        for ($j = 0; $j < $i; $j++) $sum += $digits[$j] * ($i + 1 - $j);
        $remainder = ($sum * 10) % 11;
        $digits[] = $remainder === 10 ? 0 : $remainder;
    }
    return implode('', $digits);
}
function saveProfile(int $id, array $extra = [], bool $multipart = false): array {
    $page = request('GET', '/cliente/perfil');
    return request('POST', '/cliente/perfil', ['_token' => csrf($page)] + freshProfile($id, $extra), $multipart);
}

try {
    foreach (['client', 'client', 'staff', 'admin'] as $index => $role) {
        $s = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status,email_verified_at)
            VALUES (:name,:email,:hash,:role,'active',now()) RETURNING id");
        $s->execute(['name' => 'QA Fase Um ' . $index, 'email' => $tag . '-' . $index . '@example.invalid',
            'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
        $ids[] = (int) $s->fetchColumn();
    }
    $email = $tag . '-0@example.invalid';
    $page = request('GET', '/login');
    $r = request('POST', '/login', ['_token' => 'invalid', 'email' => $email, 'password' => $password]);
    check($r['status'] === 419, 'Login aceitou CSRF inválido.');
    foreach ([null, '0', 'false', [], ['1']] as $value) {
        try { (new AccountService())->register(['terms' => $value, 'terms_version' => '1.0']); throw new RuntimeException('Cadastro aceitou termos inválidos.'); }
        catch (InvalidArgumentException) { $checks++; }
    }
    try { TermsService::validate('1', 'old'); throw new RuntimeException('Versão antiga aceita.'); }
    catch (InvalidArgumentException) { $checks++; }
    $r = login($email, $password, true);
    check($r['location'] === '/dashboard', 'Login lembrado falhou.');
    $originalCookie = $cookies[RememberMeService::COOKIE] ?? '';
    check(preg_match('/\A[a-f0-9]{32}:[a-f0-9]{64}\z/', $originalCookie) === 1, 'Cookie persistente inválido.');
    $cookieHeaders = implode('\n', $r['headers']);
    check(str_contains($cookieHeaders, 'HttpOnly') && str_contains($cookieHeaders, 'SameSite=Lax') && str_contains(strtolower($cookieHeaders), 'expires='), 'Cookie sem proteção/expiração.');
    $user = $users->findById($ids[0]);
    check($user['terms_version'] === null && $user['terms_accepted_at'] === null, 'Login alterou os dados de aceite dos termos.');
    $s = $pdo->prepare('SELECT token_hash FROM remember_tokens WHERE user_id=:id'); $s->execute(['id' => $ids[0]]);
    check($s->fetchColumn() === hash('sha256', explode(':', $originalCookie)[1]), 'Token não foi salvo como hash.');
    unset($cookies['beleza_session']);
    $r = request('GET', '/cliente/perfil');
    check($r['status'] === 200 && str_contains($r['body'], 'QA Fase Um 0'), 'Acesso não restaurado após fechar o navegador.');
    $rotated = $cookies[RememberMeService::COOKIE];
    check($rotated !== $originalCookie, 'Token não foi rotacionado.');
    $jar = $cookies;
    $cookies = [RememberMeService::COOKIE => $originalCookie];
    check(request('GET', '/cliente/perfil')['status'] === 200, 'Requisição simultânea perdeu acesso durante rotação.');
    check($cookies[RememberMeService::COOKIE] === $originalCookie, 'Janela de tolerância reemitiu cookie antigo.');
    $cookies = $jar;
    $page = request('GET', '/cliente/perfil');
    check(str_contains($page['body'], 'name="email"') && substr_count($page['body'], 'readonly') === 2, 'Campos de identidade não bloqueados.');
    check(str_contains($page['body'], 'multipart/form-data') && str_contains($page['body'], '/cliente/configuracoes'), 'Perfil/dropdown incompleto.');
    $peerBefore = $users->findById($ids[1]);
    saveProfile($ids[0], ['name' => 'QA Nome Atualizado', 'id' => $ids[1], 'user_id' => $ids[1]]);
    check($users->findById($ids[0])['name'] === 'QA Nome Atualizado' && $users->findById($ids[1])['name'] === $peerBefore['name'], 'Perfil alterou outro usuário.');
    $cpf = cpfFixture();
    saveProfile($ids[0], ['cpf' => $cpf]);
    check($users->findById($ids[0])['cpf'] === null, 'CPF foi alterado sem senha.');
    saveProfile($ids[0], ['cpf' => $cpf, 'current_password' => $password]);
    check($users->findById($ids[0])['cpf'] === $cpf, 'CPF válido não foi salvo.');
    saveProfile($ids[0], ['cpf' => '11111111111', 'current_password' => $password]);
    check($users->findById($ids[0])['cpf'] === $cpf, 'CPF inválido aceito.');
    saveProfile($ids[0], ['email' => $peerBefore['email'], 'current_password' => $password]);
    check($users->findById($ids[0])['email'] === $email, 'E-mail duplicado aceito.');
    saveProfile($ids[0], ['name' => 'QA Sobrescrita', 'profile_version' => 'old']);
    check($users->findById($ids[0])['name'] === 'QA Nome Atualizado', 'Formulário antigo sobrescreveu os dados.');

    saveProfile($ids[0], ['photo' => new CURLFile(__FILE__, 'image/png', 'fake.png')], true);
    check($users->findById($ids[0])['avatar_path'] === null, 'Conteúdo PHP aceito como imagem.');
    $png = tempnam(sys_get_temp_dir(), 'phase1-image-');
    if ($png === false || file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aB1sAAAAASUVORK5CYII=', true)) === false) {
        throw new RuntimeException('Não foi possível criar a imagem temporária do teste.');
    }
    saveProfile($ids[0], ['photo' => new CURLFile($png, 'image/png', 'original.png')], true);
    $path = $users->findById($ids[0])['avatar_path'];
    check(is_string($path) && preg_match('/\A[a-f0-9]{64}\.png\z/', $path) === 1, 'Imagem não persistida com nome aleatório.');
    $paths[] = $path;
    check(hash_equals(hash_file('sha256', $png), hash('sha256', $storage->download('img-user', $path))), 'Upload não encontrado em img-user.');
    $photo = request('GET', '/conta/foto');
    check(hash_equals(hash_file('sha256', $png), hash('sha256', $photo['body'])), 'Imagem não recuperada pelo perfil autenticado.');
    saveProfile($ids[0], ['photo' => new CURLFile($png, 'image/png', 'second.png')], true);
    $newPath = $users->findById($ids[0])['avatar_path'];
    $paths[] = $newPath;
    check($newPath !== $path, 'Substituição sobrescreveu o mesmo nome.');
    // Downloads podem continuar em cache na CDN depois da exclusão. Apenas lê
    // os metadados; toda remoção continua sendo feita pela API do Storage.
    $s = $pdo->prepare("SELECT count(*) FROM storage.objects WHERE bucket_id='img-user' AND name=:path");
    $s->execute(['path' => $path]);
    check((int) $s->fetchColumn() === 0, 'Imagem antiga não removida.');
    $paths = [$newPath];

    $page = request('GET', '/cliente/configuracoes');
    request('POST', '/conta/revogar-acessos', ['_token' => csrf($page)]);
    check($tokens->consume($rotated) === null, 'Revogação manteve token válido.');
    check(request('GET', '/cliente/perfil')['status'] === 200, 'Revogação encerrou a sessão atual.');
    $page = request('GET', '/cliente/perfil');
    request('POST', '/logout', ['_token' => csrf($page)]);
    check(!isset($cookies[RememberMeService::COOKIE]), 'Logout manteve cookie persistente.');
    login($email, $password, false);
    unset($cookies['beleza_session']);
    check(request('GET', '/cliente/perfil')['location'] === '/login', 'Login comum persistiu sem sessão.');
    login($email, $password, true);
    $oldRemembered = $cookies[RememberMeService::COOKIE];
    $newEmail = $tag . '-changed@example.invalid';
    $r = saveProfile($ids[0], ['email' => $newEmail, 'current_password' => $password]);
    check($r['location'] === '/verificar-email', 'Troca de e-mail não exigiu verificação.');
    check($users->findById($ids[0])['status'] === 'pending_verification' && $tokens->consume($oldRemembered) === null, 'E-mail novo manteve acesso antigo.');
    (new AccountTokenRepository())->issue($ids[0], 'verify_email', '123456', 900);
    $page = request('GET', '/verificar-email');
    request('POST', '/verificar-email', ['_token' => csrf($page), 'email' => $newEmail, 'code' => '123456']);
    check(login($newEmail, $password)['location'] === '/dashboard', 'Login no e-mail confirmado falhou.');
    $page = request('GET', '/cliente/perfil'); request('POST', '/logout', ['_token' => csrf($page)]);

    foreach ([2 => 'staff', 3 => 'admin'] as $index => $prefix) {
        $cookies = [];
        check(login($tag . '-' . $index . '@example.invalid', $password)['location'] === '/dashboard', 'Login por papel falhou.');
        check(request('GET', '/' . $prefix . '/perfil')['status'] === 200, 'Perfil do papel indisponível.');
        check(request('GET', '/' . $prefix . '/configuracoes')['status'] === 200, 'Configurações do papel indisponíveis.');
        check(request('GET', '/cliente/perfil')['status'] === 403, 'Papel acessou área de cliente.');
    }
    $crumbs = Navigation::breadcrumbs('/cliente/pets/42/editar?tab=a', 'client', 'Editar Pet');
    check(array_column($crumbs, 'label') === ['Início', 'Meus pets', 'Editar Pet'], 'Breadcrumb de edição incorreto.');
    check(Navigation::breadcrumbs('/cliente', 'client', 'Início')[0]['url'] === null, 'Início duplicado.');
    check(str_contains(Navigation::breadcrumbs('/cliente/agendamento/3', 'client', 'Novo')[2]['label'], 'Etapa 3'), 'Etapa do agendamento incorreta.');
    echo "Fase 1: {$checks} verificações de integração OK.\n";
} finally {
    if (is_string($png) && is_file($png)) unlink($png);
    foreach ($paths as $path) {
        if (is_string($path)) {
            try { $storage->remove('img-user', $path); }
            catch (Throwable) { fwrite(STDERR, 'Limpeza de imagem pendente: ' . $path . PHP_EOL); }
        }
    }
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM users WHERE id=:id AND email LIKE :tag')->execute(['id' => $id, 'tag' => $tag . '%@example.invalid']);
    }
}
