<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\StaffPetRepository;
use App\Support\Uuid;

/** Regras de consulta do diretório de pets visível aos funcionários. */
final class StaffPetService
{
    public static function selection(mixed $search, mixed $page): array
    {
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';
        $page = is_string($page) || is_int($page)
            ? filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]])
            : false;
        return ['search' => $search, 'page' => $page ?: 1];
    }

    public function listing(mixed $search, mixed $page): array
    {
        $selection = self::selection($search, $page);
        return (new StaffPetRepository())->paginate($selection['search'], $selection['page']) + ['search' => $selection['search']];
    }

    public function profile(mixed $id): ?array
    {
        $id = Uuid::parse($id);
        return $id === null ? null : (new StaffPetRepository())->profile($id);
    }
}
