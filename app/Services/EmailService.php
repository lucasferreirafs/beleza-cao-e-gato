<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use Closure;
use Throwable;

final class EmailService
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';
    private Closure $transport;

    /** Optional transport seam keeps email behavior testable without a Brevo account. */
    public function __construct(?Closure $transport = null)
    {
        $this->transport = $transport ?? self::sendHttpRequest(...);
    }

    public function verification(string $email, string $name, string $code): bool
    {
        $safeName = self::escape($name);
        $html = self::template(
            'Confirme seu e-mail',
            "Olá, {$safeName}! Use o código abaixo para confirmar o endereço de e-mail da sua conta.",
            self::escape($code),
            'Este código expira em 15 minutos. Se você não iniciou este cadastro, ignore esta mensagem.'
        );
        $text = "Olá, {$name}!\n\nSeu código para confirmar o e-mail é: {$code}\n\nEle expira em 15 minutos. Se você não iniciou este cadastro, ignore esta mensagem.";

        return $this->send($email, $name, 'Código de verificação', $html, $text);
    }

    public function passwordReset(string $email, string $name, string $url): bool
    {
        $safeName = self::escape($name);
        $safeUrl = self::escape($url);
        $html = self::template(
            'Redefina sua senha',
            "Olá, {$safeName}. Recebemos uma solicitação para redefinir a senha da sua conta.",
            '<a href="' . $safeUrl . '" style="display:inline-block;padding:13px 22px;border-radius:8px;background:#df4b16;color:#fff;text-decoration:none;font-weight:700">Redefinir senha</a>',
            'Este link expira em 30 minutos e só pode ser usado uma vez. Se você não solicitou a redefinição, ignore esta mensagem.'
        );
        $text = "Olá, {$name}.\n\nRedefina sua senha pelo link abaixo (válido por 30 minutos e de uso único):\n{$url}\n\nSe você não solicitou a redefinição, ignore esta mensagem.";

        return $this->send($email, $name, 'Redefinição de senha', $html, $text);
    }

    private function send(string $to, string $name, string $subject, string $html, string $text): bool
    {
        $apiKey = trim((string) Env::get('BREVO_API_KEY', ''));
        $from = trim((string) Env::get('MAIL_FROM', ''));
        $fromName = trim((string) Env::get('MAIL_FROM_NAME', 'Beleza Cão&Gato'));
        if ($fromName === '') $fromName = 'Beleza Cão&Gato';
        $replyTo = trim((string) Env::get('MAIL_REPLY_TO', ''));

        if ($apiKey === '' || !filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Email delivery configuration or recipient is invalid.');
            return false;
        }

        $payload = [
            'sender' => ['name' => $fromName, 'email' => $from],
            'to' => [['email' => $to, 'name' => $name]],
            'subject' => $subject,
            'htmlContent' => $html,
            'textContent' => $text,
        ];
        if ($replyTo !== '') {
            if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                error_log('Email reply-to configuration is invalid.');
                return false;
            }
            $payload['replyTo'] = ['email' => $replyTo, 'name' => $fromName];
        }

        try {
            $timeout = max(3, min(30, (int) Env::get('BREVO_TIMEOUT_SECONDS', 10)));
            $status = ($this->transport)(self::API_URL, [
                'accept: application/json',
                'api-key: ' . $apiKey,
                'content-type: application/json',
            ], json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $timeout);

            if ($status < 200 || $status >= 300) {
                error_log('Brevo email request failed with HTTP status ' . (int) $status . '.');
                return false;
            }

            return true;
        } catch (Throwable $error) {
            // Do not log exception text, request headers, payload, recipient or API key.
            error_log('Brevo email request failed (' . get_debug_type($error) . ').');
            return false;
        }
    }

    private static function sendHttpRequest(string $url, array $headers, string $body, int $timeout): int
    {
        if (!function_exists('curl_init')) {
            error_log('Brevo email request unavailable: PHP cURL extension is missing.');
            return 0;
        }

        $handle = curl_init($url);
        if ($handle === false) return 0;

        try {
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($handle);
            return $response === false ? 0 : (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        } finally {
            curl_close($handle);
        }
    }

    private static function template(string $title, string $intro, string $content, string $note): string
    {
        return '<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f3f6f5;font-family:Arial,sans-serif;color:#183f4b">'
            . '<div style="max-width:560px;margin:32px auto;padding:32px;background:#fff;border:1px solid #dfe7e4;border-radius:14px">'
            . '<p style="margin:0 0 22px;font-size:14px;font-weight:700;color:#df4b16">Beleza Cão&amp;Gato</p>'
            . '<h1 style="margin:0 0 16px;font-size:24px">' . self::escape($title) . '</h1>'
            . '<p style="line-height:1.6">' . $intro . '</p>'
            . '<div style="margin:24px 0;text-align:center;font-size:22px;font-weight:700;letter-spacing:2px">' . $content . '</div>'
            . '<p style="font-size:13px;line-height:1.6;color:#53676b">' . self::escape($note) . '</p>'
            . '</div></body></html>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
