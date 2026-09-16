<?php
declare(strict_types=1);
namespace App\Validation;

use DateTimeImmutable;
use InvalidArgumentException;

final class Validator
{
    public static function text(mixed $value, string $label, int $min = 2, int $max = 120): string
    {
        $value = trim((string) $value);
        $length = mb_strlen($value);

        if ($length < $min || $length > $max) throw new InvalidArgumentException("{$label} deve ter entre {$min} e {$max} caracteres.");

        return $value;
    }

    public static function email(mixed $value): string
    {
        $email = strtolower(trim((string) $value));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) throw new InvalidArgumentException('Informe um e-mail válido.');

        return $email;
    }

    public static function phone(mixed $value): string
    {
        $phone = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($phone === '') throw new InvalidArgumentException('O telefone é obrigatório.');
        if (strlen($phone) < 10 || strlen($phone) > 11) throw new InvalidArgumentException('Informe um telefone válido com DDD.');

        return $phone;
    }

    public static function cpf(mixed $value): string
    {
        $cpf = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($cpf === '') throw new InvalidArgumentException('O CPF é obrigatório.');

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) throw new InvalidArgumentException('Informe um CPF válido.');

        $digits = array_map('intval', str_split($cpf));
        for ($position = 9; $position <= 10; $position++) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) $sum += $digits[$i] * (($position + 1) - $i);
            $check = ($sum * 10) % 11;

            if ($check === 10) $check = 0;

            if ($digits[$position] !== $check) throw new InvalidArgumentException('Informe um CPF válido.');
        }
        return $cpf;
    }

    public static function date(mixed $value, bool $allowFuture = false): string
    {
        $raw = trim((string) $value);
        if ($raw === '') throw new InvalidArgumentException('A data de nascimento é obrigatória.');

        $format = str_contains($raw, '/') ? 'd/m/Y' : 'Y-m-d';
        $date = DateTimeImmutable::createFromFormat('!' . $format, $raw);
        if (!$date || $date->format($format) !== $raw) throw new InvalidArgumentException('Informe uma data válida.');

        if (!$allowFuture && $date > new DateTimeImmutable('today')) throw new InvalidArgumentException('A data não pode estar no futuro.');
        
        return $date->format('Y-m-d');
    }

    public static function password(mixed $value): string
    {
        $password = (string) $value;
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException('A senha deve ter 8+ caracteres, maiúscula, minúscula, número e símbolo.');
        }
        return $password;
    }
}
