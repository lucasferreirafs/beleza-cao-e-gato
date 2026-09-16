<?php
declare(strict_types=1);
namespace App\Security;

use App\Core\Env;
use RuntimeException;

final class JwtService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = (string) Env::get('JWT_SECRET', '');

        if (strlen($this->secret) < 32)
            throw new RuntimeException('JWT_SECRET deve ter no mínimo 32 caracteres.');
    }

    public function issue(array $claims): string
    {
        $now = time();
        $claims += ['iat' => $now, 'exp' => $now + (int) Env::get('JWT_EXPIRATION', 3600), 'iss' => (string) Env::get('APP_URL', '')];
        $h = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $p = $this->encode($claims);

        return $h . '.' . $p . '.' . $this->b64(hash_hmac('sha256', $h . '.' . $p, $this->secret, true));
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            throw new RuntimeException('Token inválido.');

        [$h, $p, $s] = $parts;
        if (!hash_equals($this->b64(hash_hmac('sha256', $h . '.' . $p, $this->secret, true)), $s))
            throw new RuntimeException('Assinatura inválida.');

        $header = json_decode($this->unb64($h), true, 8, JSON_THROW_ON_ERROR);
        if (($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? null) !== 'JWT')
            throw new RuntimeException('Cabeçalho de token inválido.');

        $claims = json_decode($this->unb64($p), true, 16, JSON_THROW_ON_ERROR);
        if (($claims['exp'] ?? 0) < time())
            throw new RuntimeException('Token expirado.');

        $issuer = (string) Env::get('APP_URL', '');
        if ($issuer !== '' && ($claims['iss'] ?? null) !== $issuer)
            throw new RuntimeException('Emissor inválido.');

        return $claims;
    }

    private function encode(array $v): string
    {
        return $this->b64(json_encode($v, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function b64(string $v): string
    {
        return rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
    }

    private function unb64(string $v): string
    {
        $value = strtr($v, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode($value, true);

        if ($decoded === false) throw new RuntimeException('Codificação de token inválida.');
        
        return $decoded;
    }
}
