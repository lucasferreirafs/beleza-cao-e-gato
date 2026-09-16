<?php
declare(strict_types=1);
namespace App\Core;

final class Request
{
    private array $routeParams = [];
    public function method(): string
    {
        return strtoupper($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        return '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key): ?string
    {
        return $this->routeParams[$key] ?? null;
    }
    
    public function bearerToken(): ?string
    {
        return preg_match('/^Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $m) ? $m[1] : null;
    }
}
