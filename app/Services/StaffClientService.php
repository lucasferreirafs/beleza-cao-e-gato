<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\StaffClientRepository;
use App\Support\Uuid;

/** Rotas staff autorizam o papel; o diretório permite leitura de todos os clientes. */
final class StaffClientService
{
    public static function selection(mixed $search, mixed $page): array
    {
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';
        $page = is_string($page) || is_int($page) ? filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]) : false;
        return ['search' => $search, 'page' => $page ?: 1];
    }

    public function listing(mixed $search, mixed $page): array
    {
        $selection = self::selection($search, $page);
        return (new StaffClientRepository())->paginate($selection['search'], $selection['page']) + ['search' => $selection['search']];
    }

    public function client(mixed $id): ?array
    {
        $id = Uuid::parse($id);
        return $id === null ? null : (new StaffClientRepository())->client($id);
    }

    public function profile(mixed $id): ?array
    {
        $client = $this->client($id);
        return $client === null ? null : ['client' => $client, 'pets' => (new StaffClientRepository())->pets($client['id'])];
    }

    public function pet(mixed $clientId, mixed $petId): ?array
    {
        $petId = Uuid::parse($petId);
        if ($petId === null) return null;
        $client = $this->client($clientId);
        if ($client === null) return null;
        $pet = (new StaffClientRepository())->pet($client['id'], $petId);
        return $pet === null ? null : ['client' => $client, 'pet' => $pet];
    }
}
