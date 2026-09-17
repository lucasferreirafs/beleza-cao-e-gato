<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Env;

final class EmailService
{
    public function verification(string $email, string $name, string $code): bool
    {
        return $this->send($email, 'Código de verificação', "Olá, {$name}. Seu código de verificação é {$code}. Ele expira em 15 minutos.");
    }

    public function passwordReset(string $email, string $name, string $url): bool
    {
        return $this->send($email, 'Redefinição de senha', "Olá, {$name}. Use este link, válido por 30 minutos, para redefinir sua senha: {$url}");
    }

    private function send(string $to, string $subject, string $message): bool
    {
        $from = trim((string) Env::get('MAIL_FROM', ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) return false;
        
        $headers = "From: Beleza Cão&Gato <{$from}>\r\nContent-Type: text/plain; charset=UTF-8";
        return @mail($to, $subject, $message, $headers);
    }
}
