<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class PetHelper
{
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
        if (!empty($pet['photo_path'])) {
            return '/cliente/pets/' . rawurlencode((string) $pet['id']) . '/foto?v=' . rawurlencode((string) $pet['photo_path']);
        }
        return '/assets/images/pet.webp';
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
        if ($status === 'completed') {
            return 'Realizado';
        }

        return AppointmentStatus::label($status);
    }
}
