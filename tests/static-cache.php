<?php
declare(strict_types=1);

// HTTP somente contra Apache local. Nao acessa nem reinicializa o banco.
// Uso: php tests/static-cache.php [http://localhost:8080]
$base = rtrim($argv[1] ?? 'http://localhost:8080', '/');
if (!in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1', '[::1]'], true)) {
    throw new RuntimeException('Execute somente contra o ambiente local.');
}

function requestAsset(string $path, array $headers = []): array
{
    global $base;
    $responseHeaders = [];
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))][] = trim($value);
            }
            return strlen($line);
        },
    ]);
    $body = curl_exec($curl);
    if ($body === false) throw new RuntimeException(curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return ['status' => $status, 'headers' => $responseHeaders, 'body' => $body];
}

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$hash = hash_file('sha256', dirname(__DIR__) . '/public/assets/js/app.js');
$versioned = '/assets/js/app.js?v=' . $hash;
$longCache = 'public, max-age=31536000, immutable';
$response = requestAsset($versioned);
check($response['status'] === 200, 'Script versionado deve responder 200.');
check(($response['headers']['cache-control'] ?? []) === [$longCache], 'Cache longo ausente ou duplicado.');
check(hash('sha256', $response['body']) === $hash, 'Conteudo servido difere do script local.');
$etag = $response['headers']['etag'][0] ?? '';
check($etag !== '', 'ETag deve ser preservado.');
$conditional = requestAsset($versioned, ['If-None-Match: ' . $etag]);
check($conditional['status'] === 304 && $conditional['body'] === '', 'Revalidacao deve responder 304 sem corpo.');
check(($conditional['headers']['cache-control'] ?? []) === [$longCache], '304 deve preservar a politica de cache.');

foreach (['/assets/js/app.js', '/assets/js/app.js?v=123', '/assets/js/app.js?v=invalid',
    '/assets/css/main.css', '/assets/css/main.css?v=' . $hash, '/assets/images/pet.webp'] as $path) {
    $response = requestAsset($path);
    check($response['status'] === 200, 'Asset nao encontrado: ' . $path);
    check(($response['headers']['cache-control'] ?? []) === ['public, no-cache'], 'Asset deve revalidar: ' . $path);
}

foreach (['/assets/js/cache-test-not-found.js?v=' . $hash => 404, '/login?v=' . $hash => 200,
    '/conta/foto?v=' . $hash => 302] as $path => $status) {
    $response = requestAsset($path);
    check($response['status'] === $status, 'Status inesperado: ' . $path);
    $cache = implode(', ', $response['headers']['cache-control'] ?? []);
    check(!str_contains($cache, 'public') && !str_contains($cache, 'immutable'), 'Cache publico vazou para rota/erro: ' . $path);
    check(str_contains($cache, 'no-store'), 'Rota/erro deve preservar no-store: ' . $path);
}

echo "Cache HTTP OK: hash, conteudo, ETag/304, assets sem hash, CSS, imagem, 404, login e foto protegida.\n";
