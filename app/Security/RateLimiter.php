<?php
declare(strict_types=1);
namespace App\Security;

final class RateLimiter
{
    private string $directory;

    public function __construct()
    {
        $this->directory = dirname(__DIR__, 2) . '/storage/rate-limits';
        if (!is_dir($this->directory)) mkdir($this->directory, 0770, true);
    }

    public function consume(string $key, int $limit, int $windowSeconds): int
    {
        $path = $this->directory . '/' . hash('sha256', $key) . '.json';
        $handle = fopen($path, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) return 0;

        try {
            $raw = stream_get_contents($handle);
            $timestamps = is_string($raw) ? json_decode($raw, true) : [];
        
            if (!is_array($timestamps)) $timestamps = [];

            $now = time();
            $timestamps = array_values(array_filter($timestamps, static fn(mixed $value): bool => is_int($value) && $value > $now - $windowSeconds));

            if (count($timestamps) >= $limit) return max(1, $windowSeconds - ($now - min($timestamps)));

            $timestamps[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($timestamps, JSON_THROW_ON_ERROR));
            fflush($handle);
            
            return 0;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function clear(string $key): void
    {
        $path = $this->directory . '/' . hash('sha256', $key) . '.json';
        if (is_file($path)) @unlink($path);
    }
}
