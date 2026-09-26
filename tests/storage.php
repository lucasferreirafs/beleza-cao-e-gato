<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\SupabaseStorageService;

// Testes sem rede: as respostas HTTP são simuladas, sem ler .env ou usar chaves reais.
$previousUrl = getenv('SUPABASE_URL');
$previousKey = getenv('SUPABASE_SERVICE_ROLE_KEY');
putenv('SUPABASE_URL=https://storage-test.supabase.co');
putenv('SUPABASE_SERVICE_ROLE_KEY=fake-service-role-key');

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$rejects = static function (callable $operation, string $expectedClass) use ($check): void {
    try {
        $operation();
    } catch (Throwable $exception) {
        $check($exception instanceof $expectedClass, 'Tipo incorreto de erro: ' . get_class($exception));
        $check(!str_contains($exception->getMessage(), 'fake-service-role-key'), 'Erro expôs a chave.');
        $check($exception->getPrevious() === null, 'Erro remoto foi exposto na cadeia de exceções.');
        return;
    }
    throw new RuntimeException('A operação inválida foi aceita.');
};

try {
    $requests = [];
    $status = 200;
    $responseBody = '{}';
    $transport = static function (string $method, string $url, array $headers, string $body) use (&$requests, &$status, &$responseBody): array {
        $requests[] = compact('method', 'url', 'headers', 'body');
        return ['status' => $status, 'body' => $responseBody];
    };
    $storage = new SupabaseStorageService($transport);
    // PNG válido, 1 x 1 pixel. type/size declarados pelo browser não são usados.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aB1sAAAAASUVORK5CYII=', true);

    $first = $storage->uploadImage('img-user', $png, 'foto pessoal.PNG');
    $check(preg_match('/\A[0-9a-f]{64}\.PNG\z/', $first['path']) === 1, 'Nome aleatório/extensão incorretos.');
    $check($first['mime'] === 'image/png' && $first['size'] === strlen($png), 'Metadados incorretos.');
    $check($requests[0]['method'] === 'POST', 'Upload não usa POST.');
    $check($requests[0]['url'] === 'https://storage-test.supabase.co/storage/v1/object/img-user/' . $first['path'], 'Endpoint de upload incorreto.');
    $check($requests[0]['body'] === $png, 'Conteúdo binário alterado.');
    $check(in_array('Content-Type: image/png', $requests[0]['headers'], true), 'MIME ausente.');
    $check(in_array('x-upsert: false', $requests[0]['headers'], true), 'Upload pode sobrescrever outro arquivo.');
    $check(in_array('Authorization: Bearer fake-service-role-key', $requests[0]['headers'], true), 'Autorização ausente.');
    $check(in_array('apikey: fake-service-role-key', $requests[0]['headers'], true), 'apikey ausente.');
    $check(!str_contains(json_encode($first), 'foto pessoal'), 'Nome original exposto.');
    $check(!str_contains(json_encode($first), 'fake-service-role-key'), 'Chave exposta no resultado.');

    $second = $storage->uploadImage('img-pets', $png, 'foto.png');
    $check($first['path'] !== $second['path'], 'Uploads compartilham o mesmo nome.');
    $check(str_contains($requests[1]['url'], '/img-pets/'), 'Bucket de pets incorreto.');
    $updated = $storage->updateImage('img-user', $first['path'], $png, 'nova.PNG');
    $check($requests[2]['method'] === 'PUT' && $updated['path'] === $first['path'], 'Atualização alterou o caminho.');
    $check($storage->publicUrl('img-user', $first['path']) === 'https://storage-test.supabase.co/storage/v1/object/public/img-user/' . $first['path'], 'URL pública incorreta.');
    $responseBody = json_encode(['signedURL' => '/object/sign/img-pets/' . $second['path'] . '?token=temporary']);
    $url = $storage->signedUrl('img-pets', $second['path'], 120);
    $check($url === 'https://storage-test.supabase.co/storage/v1/object/sign/img-pets/' . $second['path'] . '?token=temporary', 'URL assinada incorreta.');
    $signRequest = end($requests);
    $check($signRequest['method'] === 'POST' && json_decode($signRequest['body'], true) === ['expiresIn' => 120], 'Validade não enviada ao Storage.');

    $requestCount = count($requests);
    foreach (['other', '../img-user', 'img-user?x=y'] as $bucket) {
        $rejects(fn() => $storage->uploadImage($bucket, $png, 'foto.png'), InvalidArgumentException::class);
    }
    foreach (['foto.png', '../foto.png', $second['path'] . '?x=1', str_repeat('a', 64) . '.svg'] as $path) {
        $rejects(fn() => $storage->publicUrl('img-pets', $path), InvalidArgumentException::class);
        $rejects(fn() => $storage->updateImage('img-pets', $path, $png, 'foto.png'), InvalidArgumentException::class);
    }
    foreach (['foto.php', 'foto.svg', 'foto', 'foto.jpg'] as $name) {
        $rejects(fn() => $storage->uploadImage('img-user', $png, $name), InvalidArgumentException::class);
    }
    foreach (['', '<?php echo "invalid";', '<svg onload="alert(1)"></svg>', str_repeat('x', SupabaseStorageService::MAX_BYTES + 1)] as $invalidImage) {
        $rejects(fn() => $storage->uploadImage('img-user', $invalidImage, 'foto.png'), InvalidArgumentException::class);
    }
    // Simula dimensões excessivas no cabeçalho PNG sem alocar um bitmap enorme.
    foreach ([[8193, 1], [1, 8193], [6000, 6000]] as [$width, $height]) {
        $oversized = substr_replace($png, pack('NN', $width, $height), 16, 8);
        $rejects(fn() => $storage->uploadImage('img-user', $oversized, 'foto.png'), InvalidArgumentException::class);
    }
    $rejects(fn() => $storage->updateImage('img-user', str_repeat('a', 64) . '.jpg', $png, 'foto.png'), InvalidArgumentException::class);
    foreach ([0, -1, 604801] as $expiresIn) {
        $rejects(fn() => $storage->signedUrl('img-pets', $second['path'], $expiresIn), InvalidArgumentException::class);
    }
    foreach ([UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION] as $error) {
        $rejects(fn() => $storage->upload('img-user', ['error' => $error]), InvalidArgumentException::class);
    }
    $rejects(fn() => $storage->upload('img-user', []), InvalidArgumentException::class);
    $rejects(fn() => $storage->upload('img-user', ['error' => [UPLOAD_ERR_OK]]), InvalidArgumentException::class);
    $rejects(fn() => $storage->upload('img-user', ['error' => UPLOAD_ERR_OK, 'name' => 'foto.png', 'tmp_name' => __FILE__]), InvalidArgumentException::class);
    $check(count($requests) === $requestCount, 'Validação enviou arquivo inválido ao Storage.');

    // Respostas de falha nunca retornam sucesso nem expõem o corpo remoto.
    foreach ([301, 400, 401, 403, 404, 409, 413, 429, 500, 503] as $failureStatus) {
        $status = $failureStatus;
        $responseBody = 'remote error with fake-service-role-key';
        $rejects(fn() => $storage->uploadImage('img-user', $png, 'foto.png'), RuntimeException::class);
    }
    $status = 200;
    foreach (['invalid json', '{}', '{"signedURL":"https://untrusted.example/image"}', '{"signedURL":[]}'] as $invalidBody) {
        $responseBody = $invalidBody;
        $rejects(fn() => $storage->signedUrl('img-pets', $second['path']), RuntimeException::class);
    }
    $offline = new SupabaseStorageService(static function (): never {
        throw new RuntimeException('Timeout: fake-service-role-key');
    });
    $rejects(fn() => $offline->uploadImage('img-user', $png, 'foto.png'), RuntimeException::class);

    foreach (['', 'http://storage-test.supabase.co', 'https://user:password@storage-test.supabase.co', 'https://storage-test.supabase.co/storage/v1', 'https://storage-test.supabase.co?query=1'] as $badUrl) {
        putenv('SUPABASE_URL=' . $badUrl);
        $rejects(fn() => new SupabaseStorageService($transport), RuntimeException::class);
    }
    putenv('SUPABASE_URL=https://storage-test.supabase.co');
    putenv('SUPABASE_SERVICE_ROLE_KEY=');
    $rejects(fn() => new SupabaseStorageService($transport), RuntimeException::class);

    echo "Storage: {$checks} verificações OK (sem rede e sem credenciais reais).\n";
} finally {
    putenv($previousUrl === false ? 'SUPABASE_URL' : 'SUPABASE_URL=' . $previousUrl);
    putenv($previousKey === false ? 'SUPABASE_SERVICE_ROLE_KEY' : 'SUPABASE_SERVICE_ROLE_KEY=' . $previousKey);
}
