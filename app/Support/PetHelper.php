<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class PetHelper
{
    private const KNOWN_PHOTOS = [
        'Luna' => 'pet-luna.png',
        'Mel' => 'pet-mel.png',
        'Toddy' => 'pet-toddy.png',
    ];

    public static function age(?string $birthDate): ?string
    {
        if (!$birthDate) {
            return null;
        }

        $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        if (!$birth) {
            $timestamp = strtotime($birthDate);
            if ($timestamp === false) {
                return null;
            }
            $birth = (new DateTimeImmutable())->setTimestamp($timestamp);
        }

        $today = new DateTimeImmutable('today');
        if ($birth > $today) {
            return null;
        }

        $diff = $birth->diff($today);
        if ($diff->y >= 1) {
            return $diff->y . ' ' . ($diff->y === 1 ? 'ano' : 'anos');
        }

        if ($diff->m >= 1) {
            return $diff->m . ' ' . ($diff->m === 1 ? 'mês' : 'meses');
        }

        return $diff->d . ' ' . ($diff->d === 1 ? 'dia' : 'dias');
    }

    public static function photo(array $pet): string
    {
        $species = mb_strtolower(trim((string) ($pet['species'] ?? '')));
        $breed = mb_strtolower(trim((string) ($pet['breed'] ?? '')));
        $name = trim((string) ($pet['name'] ?? ''));

        if (str_contains($species, 'gato') || str_contains($species, 'cat')) {
            return '/assets/images/pet-luna.png';
        }

        // Cães / Golden Retriever utilizam a imagem do Golden (pet-mel.png)
        return '/assets/images/pet-mel.png';
    }

    public static function genderLabel(string $sex): string
    {
        return strcasecmp($sex, 'Macho') === 0 ? 'Macho' : 'Fêmea';
    }

    public static function genderBadgeClass(string $sex): string
    {
        return strcasecmp($sex, 'Macho') === 0 ? 'pet-gender-male' : 'pet-gender-female';
    }

    public static function castratedLabel(string $sex): string
    {
        return strcasecmp($sex, 'Macho') === 0 ? 'CASTRADO' : 'CASTRADA';
    }

    public static function formatWeight(mixed $weight): string
    {
        if ($weight === null || $weight === '') {
            return 'Não informado';
        }

        $num = (float) $weight;
        if ((int) $num == $num) {
            return (int) $num . ' kg';
        }

        return number_format($num, 1, ',', '.') . ' kg';
    }

    public static function formatDate(?string $date): string
    {
        if (!$date) {
            return 'Não informado';
        }

        $ts = strtotime($date);
        return $ts ? date('d/m/Y', $ts) : 'Não informado';
    }

    public static function formatPrice(mixed $price): string
    {
        return 'R$ ' . number_format((float) $price, 2, ',', '.');
    }

    public static function statusLabel(string $status): string
    {
        if (in_array($status, ['completed', 'done'], true)) {
            return 'Realizado';
        }

        return AppointmentStatus::label($status);
    }
}
