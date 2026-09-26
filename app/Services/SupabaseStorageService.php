<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/** Integração exclusiva do servidor com os buckets já existentes. */
final class SupabaseStorageService
{
    public const USER_BUCKET = 'img-user';
    public const PET_BUCKET = 'img-pets';
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    private readonly string $baseUrl;
    private readonly string $serviceRoleKey;
    private readonly Closure $transport;

    /**
     * O transporte opcional permite testes sem acessar o Storage.
     * Assinatura: (method, url, headers, body) => ['status' => int, 'body' => string].
     */
    public function __construct(?Closure $transport = null)
    {
        $url = rtrim(trim((string) Env::get('SUPABASE_URL', '')), '/');
        $parts = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts)
            || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || !empty($parts['path'])) {
            throw new RuntimeException('Configure SUPABASE_URL com a URL HTTPS do projeto.');
        }

        $key = trim((string) Env::get('SUPABASE_SERVICE_ROLE_KEY', ''));
        if ($key === '' || preg_match('/\s/', $key)) {
            throw new RuntimeException('Configure SUPABASE_SERVICE_ROLE_KEY no servidor.');
        }

        $this->baseUrl = $url . '/storage/v1';
        $this->serviceRoleKey = $key;
        $this->transport = $transport ?? $this->curlRequest(...);
    }

    /** Recebe um único item de $_FILES após autenticação, autorização e CSRF. */
    public function upload(string $bucket, array $file): array
    {
        return $this->uploadImage($bucket, $this->readUpload($file), $file['name']);
    }

    /** Atualiza um caminho obtido do banco após verificar o dono do registro. */
    public function update(string $bucket, string $path, array $file): array
    {
        return $this->updateImage($bucket, $path, $this->readUpload($file), $file['name']);
    }

    /** Para conteúdo já disponível no servidor; retorna bucket, path, mime e size. */
    public function uploadImage(string $bucket, string $contents, string $originalName): array
    {
        $this->validateBucket($bucket);
        [$extension, $mime] = $this->validateImage($contents, $originalName);
        // 256 bits aleatórios; o nome original nunca é enviado ao Storage.
        $path = bin2hex(random_bytes(32)) . '.' . $extension;
        $this->request('POST', '/object/' . $bucket . '/' . $path, $contents, [
            'Content-Type: ' . $mime,
            'Cache-Control: max-age=0',
            'x-upsert: false',
        ]);

        return ['bucket' => $bucket, 'path' => $path, 'mime' => $mime, 'size' => strlen($contents)];
    }

    /** Mantém o nome aleatório existente. Para trocar de formato, faça novo upload. */
    public function updateImage(string $bucket, string $path, string $contents, string $originalName): array
    {
        $object = $this->objectPath($bucket, $path);
        [, $mime] = $this->validateImage($contents, $originalName);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (self::MIME_TYPES[$extension] !== $mime) {
            throw new InvalidArgumentException('Para trocar o formato da imagem, envie um novo arquivo.');
        }

        $this->request('PUT', '/object/' . $object, $contents, [
            'Content-Type: ' . $mime,
            'Cache-Control: max-age=0',
        ]);

        return ['bucket' => $bucket, 'path' => $path, 'mime' => $mime, 'size' => strlen($contents)];
    }

    /** Só serve arquivos se o bucket já estiver configurado como público. */
    public function publicUrl(string $bucket, string $path): string
    {
        return $this->baseUrl . '/object/public/' . $this->objectPath($bucket, $path);
    }

    /** Recupera uma URL temporária para exibir arquivos, inclusive de buckets privados. */
    public function signedUrl(string $bucket, string $path, int $expiresIn = 3600): string
    {
        $object = $this->objectPath($bucket, $path);
        if ($expiresIn < 1 || $expiresIn > 604800) {
            throw new InvalidArgumentException('A validade da URL deve ser de 1 segundo a 7 dias.');
        }

        $body = $this->request('POST', '/object/sign/' . $object,
            json_encode(['expiresIn' => $expiresIn], JSON_THROW_ON_ERROR), ['Content-Type: application/json']);
        $data = json_decode($body, true);
        $signed = is_array($data) ? ($data['signedURL'] ?? null) : null;
        if (!is_string($signed) || !str_starts_with($signed, '/object/sign/' . $object . '?')
            || preg_match('/[\x00-\x20\x7f]/', $signed)) {
            throw new RuntimeException('O Storage retornou uma URL temporária inválida.');
        }

        return $this->baseUrl . $signed;
    }

    /** Remove somente objetos com nomes gerados por este serviço. */
    public function remove(string $bucket, string $path): void
    {
        $this->request('DELETE', '/object/' . $this->objectPath($bucket, $path), '', []);
    }

    /** Recupera bytes no servidor sem expor as credenciais ao navegador. */
    public function download(string $bucket, string $path): string
    {
        return $this->request('GET', '/object/authenticated/' . $this->objectPath($bucket, $path), '', []);
    }

    private function readUpload(array $file): string
    {
        if (!isset($file['error']) || !is_int($file['error'])) {
            throw new InvalidArgumentException('Envie uma única imagem.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem ultrapassa o limite de upload.',
                UPLOAD_ERR_PARTIAL => 'O upload foi interrompido. Envie a imagem novamente.',
                UPLOAD_ERR_NO_FILE => 'Selecione uma imagem.',
                default => 'Não foi possível receber a imagem. Tente novamente.',
            });
        }
        if (!isset($file['tmp_name'], $file['name']) || !is_string($file['tmp_name'])
            || !is_string($file['name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('O arquivo não é um upload válido.');
        }

        // Não confia em size/type recebidos no formulário e limita a leitura real.
        $contents = @file_get_contents($file['tmp_name'], false, null, 0, self::MAX_BYTES + 1);
        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler a imagem recebida.');
        }

        return $contents;
    }

    private function validateImage(string $contents, string $originalName): array
    {
        if ($contents === '' || strlen($contents) > self::MAX_BYTES) {
            throw new InvalidArgumentException('A imagem deve ter conteúdo e no máximo 5 MB.');
        }
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $expectedMime = self::MIME_TYPES[strtolower($extension)] ?? null;
        if ($expectedMime === null) {
            throw new InvalidArgumentException('Use imagens JPG, JPEG, PNG ou WebP.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $dimensions = @getimagesizefromstring($contents);
        if ($mime !== $expectedMime || $dimensions === false || ($dimensions['mime'] ?? '') !== $mime) {
            throw new InvalidArgumentException('O conteúdo da imagem não corresponde ao formato informado.');
        }
        if ($dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 8192
            || $dimensions[1] > 8192 || $dimensions[0] * $dimensions[1] > 25000000) {
            throw new InvalidArgumentException('A imagem excede as dimensões permitidas (8192 por lado e 25 megapixels).');
        }

        return [$extension, $mime];
    }

    private function validateBucket(string $bucket): void
    {
        if (!in_array($bucket, [self::USER_BUCKET, self::PET_BUCKET], true)) {
            throw new InvalidArgumentException('Bucket de imagens inválido.');
        }
    }

    private function objectPath(string $bucket, string $path): string
    {
        $this->validateBucket($bucket);
        if (!preg_match('/\A[a-f0-9]{64}\.(?:jpe?g|png|webp)\z/i', $path)) {
            throw new InvalidArgumentException('Caminho de imagem inválido.');
        }

        return $bucket . '/' . rawurlencode($path);
    }

    private function request(string $method, string $path, string $body, array $headers): string
    {
        $headers[] = 'apikey: ' . $this->serviceRoleKey;
        $headers[] = 'Authorization: Bearer ' . $this->serviceRoleKey;
        $headers[] = 'Accept: application/json';
        try {
            $response = ($this->transport)($method, $this->baseUrl . $path, $headers, $body);
        } catch (Throwable) {
            // Não propaga cURL error, corpo remoto ou exception anterior com credenciais.
            throw new RuntimeException('Não foi possível comunicar com o Storage. Tente novamente.');
        }
        $status = $response['status'] ?? 0;
        if (!is_int($status) || $status < 200 || $status >= 300) {
            throw new RuntimeException(match ($status) {
                401, 403 => 'O Storage recusou o acesso. Revise a configuração no servidor.',
                404 => 'O bucket ou a imagem não foi encontrado no Storage.',
                409 => 'Já existe uma imagem nesse caminho. Faça um novo upload.',
                413 => 'A imagem ultrapassa o limite permitido pelo Storage.',
                default => 'Não foi possível concluir a operação no Storage. Tente novamente.',
            });
        }
        if (!isset($response['body']) || !is_string($response['body'])) {
            throw new RuntimeException('O Storage retornou uma resposta inválida.');
        }

        return $response['body'];
    }

    private function curlRequest(string $method, string $url, array $headers, string $body): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('A extensão PHP cURL é necessária.');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Não foi possível iniciar a conexão com o Storage.');
        }
        try {
            curl_setopt_array($handle, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($handle);
            if ($response === false) {
                throw new RuntimeException('Falha de conexão com o Storage.');
            }

            return ['status' => (int) curl_getinfo($handle, CURLINFO_HTTP_CODE), 'body' => $response];
        } finally {
            curl_close($handle);
        }
    }
}
