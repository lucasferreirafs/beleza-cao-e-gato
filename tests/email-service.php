<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\EmailService;

function verifyEmailTest(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

putenv('BREVO_API_KEY=qa-secret-never-printed');
putenv('BREVO_TIMEOUT_SECONDS=10');
putenv('MAIL_FROM=no-reply@example.test');
putenv('MAIL_FROM_NAME=Beleza Cão&Gato');
putenv('MAIL_REPLY_TO=');

$request = null;
$sender = new EmailService(static function (string $url, array $headers, string $body, int $timeout) use (&$request): int {
    $request = ['url' => $url, 'headers' => $headers, 'payload' => json_decode($body, true, 512, JSON_THROW_ON_ERROR), 'timeout' => $timeout];
    return 201;
});

verifyEmailTest($sender->verification('cliente@example.test', '<Ana & Bia>', '123456'), 'Verificação deve aceitar HTTP 201.');
verifyEmailTest($request['url'] === 'https://api.brevo.com/v3/smtp/email', 'Endpoint HTTPS da Brevo incorreto.');
verifyEmailTest(in_array('api-key: qa-secret-never-printed', $request['headers'], true), 'Cabeçalho da API ausente.');
verifyEmailTest($request['payload']['to'][0]['email'] === 'cliente@example.test', 'Destinatário incorreto.');
verifyEmailTest(str_contains($request['payload']['htmlContent'], '&lt;Ana &amp; Bia&gt;'), 'Nome não escapado no HTML.');
verifyEmailTest($request['payload']['textContent'] !== '', 'Alternativa de texto ausente.');
verifyEmailTest($request['timeout'] === 10, 'Timeout configurado incorretamente.');

$resetUrl = 'https://app.example.test/redefinir-senha?selector=00000000-0000-4000-8000-000000000001&token=' . str_repeat('a', 64);
$resetSender = new EmailService(static function (string $url, array $headers, string $body): int {
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (!str_contains($payload['htmlContent'], 'href="https://app.example.test/redefinir-senha?')) {
        throw new RuntimeException('Link de recuperação ausente.');
    }
    if (str_contains($payload['htmlContent'], 'email=') || !str_contains($payload['htmlContent'], 'selector=')) {
        throw new RuntimeException('O link deve usar seletor, sem expor e-mail.');
    }
    if (!str_contains($payload['htmlContent'], '>Redefinir senha</a>')) {
        throw new RuntimeException('Texto do botão de redefinição incorreto.');
    }
    return 202;
});
verifyEmailTest($resetSender->passwordReset('cliente@example.test', 'Cliente QA', $resetUrl), 'Recuperação deve aceitar HTTP 202.');

$failed = new EmailService(static fn(): int => 429);
verifyEmailTest(!$failed->verification('cliente@example.test', 'Cliente QA', '654321'), 'Resposta não 2xx deve falhar sem exceção pública.');
$transportError = new EmailService(static function (): int { throw new RuntimeException('simulated transport failure'); });
verifyEmailTest(!$transportError->verification('cliente@example.test', 'Cliente QA', '654321'), 'Falha de transporte deve ser tratada.');

$called = false;
putenv('BREVO_API_KEY=');
$unconfigured = new EmailService(static function () use (&$called): int { $called = true; return 201; });
verifyEmailTest(!$unconfigured->verification('cliente@example.test', 'Cliente QA', '123456'), 'Chave ausente deve falhar fechada.');
verifyEmailTest(!$called, 'Não deve enviar sem configuração válida.');

putenv('BREVO_API_KEY=');
putenv('BREVO_TIMEOUT_SECONDS=');
putenv('MAIL_FROM=');
putenv('MAIL_FROM_NAME=');
putenv('MAIL_REPLY_TO=');

echo "EmailService: payload, escaping, HTTPS, respostas de sucesso/erro e configuração ausente OK.\n";
